<?php

namespace AmeliaBooking\Application\Commands\PaymentGateway;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Services\Payment\PayPalService;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class PayPalPaymentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\PaymentGateway
 */
class PayPalPaymentCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'amount',
        'couponCode',
        'bookings',
        'bookable'
    ];

    /**
     * @param PayPalPaymentCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(PayPalPaymentCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        /** @var AbstractUser $user */
        $user = $customerAS->getNewOrExistingCustomer($command->getField('bookings')[0]['customer'], $result);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            return $result;
        }

        /** @var CustomerBooking $booking */
        $booking = CustomerBookingFactory::create($command->getField('bookings')[0]);

        /** @var AbstractBookable $bookable */
        $bookable = $reservationService->getBookableEntity($command->getField('bookable'));

        if ($command->getField('couponCode')) {
            /** @var CouponApplicationService $couponAS */
            $couponAS = $this->container->get('application.coupon.service');

            try {
                /** @var Coupon $coupon */
                $coupon = $couponAS->processCoupon(
                    $command->getField('couponCode'),
                    $bookable->getId()->getValue(),
                    $type,
                    ($user && $user->getId()) ? $user->getId()->getValue() : null,
                    true
                );
            } catch (CouponUnknownException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData([
                    'couponUnknown' => true
                ]);

                return $result;
            } catch (CouponInvalidException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData([
                    'couponInvalid' => true
                ]);

                return $result;
            }

            $booking->setCoupon($coupon);
        }

        $paymentAmount = $reservationService->getPaymentAmount($booking, $bookable);

        if (!$paymentAmount) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData([
                'paymentSuccessful' => false,
                'onSitePayment' => true
            ]);

            return $result;
        }

        /** @var PayPalService $paymentService */
        $paymentService = $this->container->get('infrastructure.payment.payPal.service');

        $response = $paymentService->execute(
            [
                'returnUrl' => AMELIA_ACTION_URL . '/payment/payPal/callback&status=true',
                'cancelUrl' => AMELIA_ACTION_URL . '/payment/payPal/callback&status=false',
                'amount'    => $paymentAmount,
            ]
        );

        if (!$response->isSuccessful()) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['payment_error']);
            $result->setData([
                'paymentSuccessful' => false
            ]);

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setData([
            'paymentID'            => $response->getData()['id'],
            'transactionReference' => $response->getTransactionReference(),
        ]);

        return $result;
    }
}
