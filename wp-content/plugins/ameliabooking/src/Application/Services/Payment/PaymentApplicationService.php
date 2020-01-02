<?php

namespace AmeliaBooking\Application\Services\Payment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Services\Payment\CurrencyService;
use AmeliaBooking\Infrastructure\Services\Payment\PayPalService;
use AmeliaBooking\Infrastructure\Services\Payment\StripeService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class PaymentApplicationService
 *
 * @package AmeliaBooking\Application\Services\Payment
 */
class PaymentApplicationService
{

    private $container;

    /**
     * PaymentApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $params
     * @param int   $itemsPerPage
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getPaymentsData($params, $itemsPerPage)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        $paymentsData = $paymentRepository->getFiltered($params, $itemsPerPage);

        $bookingIds = [];

        foreach ($paymentsData as $payment) {
            if (!$payment['appointmentId']) {
                $bookingIds[] = $payment['customerBookingId'];
            }
        }

        /** @var Collection $events */
        $events = $eventRepository->getByBookingIds($bookingIds);

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            /** @var CustomerBooking $booking */
            foreach ($event->getBookings()->getItems() as $booking) {
                if (array_key_exists($booking->getId()->getValue(), $paymentsData)) {
                    $paymentsData[$booking->getId()->getValue()]['bookingStart'] =
                        $event->getPeriods()->getItem(0)->getPeriodStart()->getValue()->format('Y-m-d H:i:s');

                    /** @var Provider $provider */
                    foreach ($event->getProviders()->getItems() as $provider) {
                        $paymentsData[$booking->getId()->getValue()]['providers'][] = [
                            'id' => $provider->getId()->getValue(),
                            'fullName' => $provider->getFullName(),
                            'email' => $provider->getEmail()->getValue(),
                        ];
                    }

                    $paymentsData[$booking->getId()->getValue()]['eventId'] = $event->getId()->getValue();
                    $paymentsData[$booking->getId()->getValue()]['name'] = $event->getName()->getValue();
                }
            }
        }

        return $paymentsData;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param array         $paymentData
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     *
     * @return boolean
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function processPayment($result, $paymentData, $reservation, $bookingType)
    {
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($bookingType->getValue());

        $paymentAmount = $reservationService->getPaymentAmount($reservation->getBooking(), $reservation->getBookable());

        if (!$paymentAmount && ($paymentData['gateway'] === 'stripe' || $paymentData['gateway'] === 'payPal')) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData([
                'paymentSuccessful' => false,
                'onSitePayment' => true
            ]);
            return false;
        }

        switch ($paymentData['gateway']) {
            case ('payPal'):
                /** @var PayPalService $paymentService */
                $paymentService = $this->container->get('infrastructure.payment.payPal.service');

                $response = $paymentService->complete([
                    'transactionReference' => $paymentData['data']['transactionReference'],
                    'PayerID'              => $paymentData['data']['PayerId'],
                    'amount'               => $paymentAmount,
                ]);

                if (!$response->isSuccessful()) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
                    $result->setData([
                        'paymentSuccessful' => false
                    ]);

                    return false;
                }

                break;

            case ('stripe'):
                /** @var StripeService $paymentService */
                $paymentService = $this->container->get('infrastructure.payment.stripe.service');

                /** @var CurrencyService $currencyService */
                $currencyService = $this->container->get('infrastructure.payment.currency.service');

                try {
                    $response = $paymentService->execute([
                        'paymentMethodId' => !empty($paymentData['data']['paymentMethodId']) ?
                            $paymentData['data']['paymentMethodId'] : null,
                        'paymentIntentId' => !empty($paymentData['data']['paymentIntentId']) ?
                            $paymentData['data']['paymentIntentId'] : null,
                        'amount'          => $currencyService->getAmountInFractionalUnit(new Price($paymentAmount)),
                    ]);
                } catch (\Exception $e) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
                    $result->setData([
                        'paymentSuccessful' => false
                    ]);

                    return false;
                }

                if (isset($response['requiresAction'])) {
                    $result->setResult(CommandResult::RESULT_SUCCESS);
                    $result->setData([
                        'paymentIntentClientSecret' => $response['paymentIntentClientSecret'],
                        'requiresAction'            => $response['requiresAction']
                    ]);

                    return false;
                }

                if (empty($response['paymentSuccessful'])) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
                    $result->setData([
                        'paymentSuccessful' => false
                    ]);

                    return false;
                }

                break;

            case ('onSite'):
                if ($paymentAmount) {
                    /** @var SettingsService $settingsService */
                    $settingsService = $this->container->get('domain.settings.service');

                    $payments = $settingsService->getCategorySettings('payments');

                    if ($payments['onSite'] === false) {
                        $result->setResult(CommandResult::RESULT_ERROR);
                        $result->setData(['paymentError' => true]);

                        return false;
                    }

                    $bookablePayments = $this->getAvailablePayments($reservation->getBookable());

                    if (isset($bookablePayments['onSite']) && $bookablePayments['onSite'] === false) {
                        $result->setResult(CommandResult::RESULT_ERROR);
                        $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
                        $result->setData([
                            'paymentSuccessful' => false
                        ]);

                        return false;
                    }
                }

                break;
        }

        return true;
    }

    /**
     * @param AbstractBookable $bookable
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getAvailablePayments($bookable)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $generalPayments = $settingsService->getCategorySettings('payments');

        if ($bookable->getSettings()) {
            $hasAvailablePayments = false;

            $bookableSettings = json_decode($bookable->getSettings()->getValue(), true);

            if ($generalPayments['onSite'] === true &&
                isset($bookableSettings['payments']['onSite']) &&
                $bookableSettings['payments']['onSite'] === true
            ) {
                $hasAvailablePayments = true;
            }

            if ($generalPayments['payPal']['enabled'] === true &&
                isset($bookableSettings['payments']['payPal']['enabled']) &&
                $bookableSettings['payments']['payPal']['enabled'] === true
            ) {
                $hasAvailablePayments = true;
            }

            if ($generalPayments['stripe']['enabled'] === true &&
                isset($bookableSettings['payments']['stripe']['enabled']) &&
                $bookableSettings['payments']['stripe']['enabled'] === true
            ) {
                $hasAvailablePayments = true;
            }

            return $hasAvailablePayments ? $bookableSettings['payments'] : $generalPayments;
        }

        return $generalPayments;
    }
}
