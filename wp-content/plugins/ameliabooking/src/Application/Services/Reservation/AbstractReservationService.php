<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Coupon\CouponApplicationService;
use AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService;
use AmeliaBooking\Application\Services\Payment\PaymentApplicationService;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CouponInvalidException;
use AmeliaBooking\Domain\Common\Exceptions\CouponUnknownException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Booking\Validator;
use AmeliaBooking\Domain\Entity\Coupon\Coupon;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Domain\ValueObjects\String\PaymentStatus;
use AmeliaBooking\Domain\ValueObjects\String\PaymentType;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;
use AmeliaBooking\Infrastructure\WP\Translations\FrontendStrings;

/**
 * Class AbstractReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
abstract class AbstractReservationService implements ReservationServiceInterface
{
    protected $container;

    /**
     * AbstractReservationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param array     $data
     * @param Validator $validator
     * @param bool      $save
     *
     * @return CommandResult
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Exception
     */
    public function process($data, $validator, $save)
    {
        $result = new CommandResult();

        $type = $data['type'] ?: Entities::APPOINTMENT;

        /** @var CustomerBookingRepository $customerBookingRepository */
        $customerBookingRepository = $this->container->get('domain.booking.appointment.repository');

        $customerBookingRepository->beginTransaction();

        /** @var Reservation $reservation */
        $reservation = $this->processBooking($result, $data, $validator, $save);

        if ($result->getResult() === CommandResult::RESULT_ERROR) {
            $customerBookingRepository->rollback();
            return $result;
        }

        /** @var PaymentApplicationService $paymentAS */
        $paymentAS = $this->container->get('application.payment.service');

        $paymentCompleted = $paymentAS->processPayment($result, $data['payment'], $reservation, new BookingType($type));

        if (!$paymentCompleted || $result->getResult() === CommandResult::RESULT_ERROR) {
            $customerBookingRepository->rollback();
            return $result;
        }

        $this->finalize($result, $reservation, new BookingType($type));

        $customerBookingRepository->commit();

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param CommandResult $result
     * @param array         $appointmentData
     * @param Validator     $validator
     * @param bool          $save
     *
     * @return Reservation|null
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function processBooking($result, $appointmentData, $validator, $save)
    {
        /** @var CouponApplicationService $couponAS */
        $couponAS = $this->container->get('application.coupon.service');

        $appointmentData['bookings'][0]['info'] = json_encode([
            'firstName' => $appointmentData['bookings'][0]['customer']['firstName'],
            'lastName'  => $appointmentData['bookings'][0]['customer']['lastName'],
            'phone'     => $appointmentData['bookings'][0]['customer']['phone'],
        ]);

        /** @var Customer $user */
        $user = null;

        $newUserId = null;

        // Create a new user if doesn't exists. For adding appointment from the front-end.
        if (!$appointmentData['bookings'][0]['customerId'] && !$appointmentData['bookings'][0]['customer']['id']) {
            /** @var CustomerApplicationService $customerAS */
            $customerAS = $this->container->get('application.user.customer.service');

            /** @var UserRepository $userRepository */
            $userRepository = $this->container->get('domain.users.repository');

            $user = $customerAS->getNewOrExistingCustomer($appointmentData['bookings'][0]['customer'], $result);

            if ($result->getResult() === CommandResult::RESULT_ERROR) {
                return null;
            }

            if ($save && !$user->getId()) {
                if (!($newUserId = $userRepository->add($user))) {
                    $result->setResult(CommandResult::RESULT_ERROR);
                    $result->setData(['emailError' => true]);

                    return null;
                }

                $user->setId(new Id($newUserId));
            }

            if ($user->getId()) {
                $appointmentData['bookings'][0]['customerId'] = $user->getId()->getValue();
                $appointmentData['bookings'][0]['customer']['id'] = $user->getId()->getValue();
            }
        }

        if ($validator->hasCustomFieldsValidation()) {
            /** @var CustomFieldApplicationService $customFieldService */
            $customFieldService = $this->container->get('application.customField.service');

            $appointmentData['uploadedCustomFieldFilesInfo'] = [];

            if ($appointmentData['bookings'][0]['customFields']) {
                $appointmentData['uploadedCustomFieldFilesInfo'] = $customFieldService->processCustomFields(
                    $appointmentData['bookings'][0]['customFields']
                );
            }

            $appointmentData['bookings'][0]['customFields'] = $appointmentData['bookings'][0]['customFields'] ?
                json_encode($appointmentData['bookings'][0]['customFields']) : null;
        }

        /** @var Coupon $coupon */
        $coupon = null;

        // Inspect if coupon is existing and valid if sent from the front-end.
        if (!empty($appointmentData['couponCode'])) {
            try {
                $entityId = null;

                switch ($appointmentData['type']) {
                    case Entities::APPOINTMENT:
                        $entityId = $appointmentData['serviceId'];
                        break;

                    case Entities::EVENT:
                        $entityId = $appointmentData['eventId'];

                        break;
                }

                $coupon = $couponAS->processCoupon(
                    $appointmentData['couponCode'],
                    $entityId,
                    $appointmentData['type'],
                    ($user && $user->getId()) ?
                        $user->getId()->getValue() : $appointmentData['bookings'][0]['customer']['id'],
                    $validator->hasCouponValidation()
                );
            } catch (CouponUnknownException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData([
                    'couponUnknown' => true
                ]);

                return null;
            } catch (CouponInvalidException $e) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage($e->getMessage());
                $result->setData([
                    'couponInvalid' => true
                ]);

                return null;
            }

            if ($coupon) {
                $appointmentData['bookings'][0]['coupon'] = $coupon->toArray();
                $appointmentData['bookings'][0]['couponId'] = $coupon->getId()->getValue();
            }
        }

        try {
            $reservation = $this->book($appointmentData, $validator->hasTimeSlotValidation(), $save);

            $reservation->setIsNewUser(new BooleanValueObject($newUserId !== null));

            if (array_key_exists('uploadedCustomFieldFilesInfo', $appointmentData)) {
                $reservation->setUploadedCustomFieldFilesInfo($appointmentData['uploadedCustomFieldFilesInfo']);
            }

            return $reservation;
        } catch (CustomerBookedException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['customer_already_booked']);
            $result->setData([
                'customerAlreadyBooked' => true
            ]);

            return null;
        } catch (BookingUnavailableException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage(FrontendStrings::getCommonStrings()['time_slot_unavailable']);
            $result->setData([
                'timeSlotUnavailable' => true
            ]);

            return null;
        }
    }

    /**
     * @param CommandResult $result
     * @param Reservation   $reservation
     * @param BookingType   $bookingType
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\ForbiddenFileUploadException
     */
    public function finalize($result, $reservation, $bookingType)
    {
        /** @var CustomerApplicationService $customerApplicationService */
        $customerApplicationService = $this->container->get('application.user.customer.service');

        $customerApplicationService->setWPUserForCustomer(
            $reservation->getBooking()->getCustomer(),
            $reservation->isNewUser()->getValue()
        );

        /** @var CustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        $customFieldService->saveUploadedFiles(
            $reservation->getBooking()->getId()->getValue(),
            $reservation->getUploadedCustomFieldFilesInfo(),
            ''
        );

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully added booking');
        $result->setData([
            'type'                     => $bookingType->getValue(),
            $bookingType->getValue()   => $reservation->getReservation()->toArray(),
            Entities::BOOKING          => $reservation->getBooking()->toArray(),
            'utcTime'                  => $this->getBookingPeriods(
                $reservation->getReservation(),
                $reservation->getBooking(),
                $reservation->getBookable()
            ),
            'appointmentStatusChanged' => $reservation->isStatusChanged()->getValue()
        ]);
    }

    /**
     * @param CustomerBooking  $booking
     * @param Service|Event $bookable
     *
     * @return float
     *
     * @throws InvalidArgumentException
     */
    public function getPaymentAmount($booking, $bookable)
    {
        $price = (float)$bookable->getPrice()->getValue() *
            ($this->isAggregatedPrice($bookable) ? $booking->getPersons()->getValue() : 1);

        /** @var CustomerBookingExtra $customerBookingExtra */
        foreach ($booking->getExtras()->getItems() as $customerBookingExtra) {
            /** @var Extra $extra */
            $extra = $bookable->getExtras()->getItem($customerBookingExtra->getExtraId()->getValue());

            $isExtraAggregatedPrice = $extra->getAggregatedPrice() === null ? $this->isAggregatedPrice($bookable) :
                $extra->getAggregatedPrice()->getValue();

            $price += (float)$extra->getPrice()->getValue() *
                ($isExtraAggregatedPrice ? $booking->getPersons()->getValue() : 1) *
                $customerBookingExtra->getQuantity()->getValue();
        }

        if ($booking->getCoupon()) {
            $price -= $price / 100 *
                ($booking->getCoupon()->getDiscount()->getValue() ?: 0) +
                ($booking->getCoupon()->getDeduction()->getValue() ?: 0);
        }

        return $price;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param int       $bookingId
     * @param array     $paymentData
     * @param float     $amount
     * @param \DateTime $dateTime
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    function addPayment($bookingId, $paymentData, $amount, $dateTime)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        $paymentStatus = PaymentStatus::PENDING;

        switch ($paymentData['gateway']) {
            case (PaymentType::WC):
                $paymentStatus = $paymentData['status'];
                break;
            case (PaymentType::PAY_PAL):
                $paymentStatus = PaymentStatus::PAID;
                break;
            case (PaymentType::STRIPE):
                $paymentStatus = PaymentStatus::PAID;
                break;
        }

        $paymentAmount = $paymentData['gateway'] === PaymentType::ON_SITE ? 0 : $amount;

        $payment = PaymentFactory::create([
            'customerBookingId' => $bookingId,
            'amount'            => $paymentAmount,
            'status'            => $paymentStatus,
            'gateway'           => $paymentData['gateway'],
            'dateTime'          => ($paymentData['gateway'] === PaymentType::ON_SITE) ?
                $dateTime->format('Y-m-d H:i:s') : DateTimeService::getNowDateTimeObject()->format('Y-m-d H:i:s'),
            'gatewayTitle'      => isset($paymentData['gatewayTitle']) ? $paymentData['gatewayTitle'] : ''
        ]);

        if (!$payment instanceof Payment) {
            throw new InvalidArgumentException('Unknown type');
        }

        return $paymentRepository->add($payment);
    }

    /**
     * @param CustomerBooking $booking
     * @param string          $token
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws AccessDeniedException
     */
    function inspectToken($booking, $token)
    {
        $loggedInUser = $this->container->get('logged.in.user');

        if (($token !== null && $token !== $booking->getToken()->getValue()) ||
            (
                $token === null && $loggedInUser &&
                $loggedInUser->getId() !== null &&
                $loggedInUser->getId()->getValue() !== $booking->getCustomerId()->getValue()
            )
        ) {
            throw new AccessDeniedException('You are not allowed to update booking status');
        }

        return true;
    }

    /**
     * @param \DateTime $bookingStart
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws BookingCancellationException
     */
    function inspectMinimumCancellationTime($bookingStart)
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');

        $minimumCancelTime = $settingsService->getCategorySettings('general')['minimumTimeRequirementPriorToCanceling'];

        if (DateTimeService::getNowDateTimeObject() >=
            DateTimeService::getCustomDateTimeObject(
                $bookingStart->format('Y-m-d H:i:s'))->modify("-{$minimumCancelTime} second")
        ) {
            throw new BookingCancellationException(
                BackendStrings::getCalendarStrings()['booking_cancel_exception']
            );
        }

        return true;
    }
}
