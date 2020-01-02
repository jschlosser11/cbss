<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Bookable\BookableApplicationService;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;

/**
 * Class AppointmentReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class AppointmentReservationService extends AbstractReservationService
{
    /**
     * @return string
     */
    public function getType()
    {
        return Entities::APPOINTMENT;
    }

    /**
     * @param array $appointmentData
     * @param bool  $inspectTimeSlot
     * @param bool  $save
     *
     * @return Reservation
     *
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\CustomerBookedException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function book($appointmentData, $inspectTimeSlot, $save)
    {
        /** @var AppointmentApplicationService $appointmentAS */
        $appointmentAS = $this->container->get('application.booking.appointment.service');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        $appointmentStatusChanged = false;

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService($appointmentData['serviceId'], $appointmentData['providerId']);

        /** @var Collection $existingAppointments */
        $existingAppointments = $appointmentRepo->getFiltered([
            'dates'     => [$appointmentData['bookingStart'], $appointmentData['bookingStart']],
            'services'  => [$appointmentData['serviceId']],
            'providers' => [$appointmentData['providerId']]
        ]);

        /** @var Appointment $existingAppointment */
        $existingAppointment = $existingAppointments->length() ?
            $existingAppointments->getItem($existingAppointments->keys()[0]) : null;

        if ($existingAppointment) {
            /** @var Appointment $appointment */
            $appointment = AppointmentFactory::create($existingAppointment->toArray());

            if (!empty($appointmentData['locationId'])) {
                $appointment->setLocationId(new Id($appointmentData['locationId']));
            }

            $booking = CustomerBookingFactory::create($appointmentData['bookings'][0]);
            $booking->setAppointmentId($appointment->getId());
            $booking->setPrice($appointment->getService()->getPrice());
        } else {
            /** @var Appointment $appointment */
            $appointment = $appointmentAS->build($appointmentData, $service);

            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($appointment->getBookings()->keys()[0]);
        }

        if ($inspectTimeSlot) {
            /** @var TimeSlotService $timeSlotService */
            $timeSlotService = $this->container->get('application.timeSlot.service');

            // if not new appointment, check if customer has already made booking
            if ($appointment->getId() !== null) {
                foreach ($appointment->getBookings()->keys() as $bookingKey) {
                    /** @var CustomerBooking $customerBooking */
                    $customerBooking = $appointment->getBookings()->getItem($bookingKey);

                    if ($customerBooking->getStatus()->getValue() !== BookingStatus::CANCELED &&
                        $booking->getCustomerId()->getValue() === $customerBooking->getCustomerId()->getValue()) {
                        throw new CustomerBookedException('');
                    }
                }
            }

            $selectedExtras = [];

            foreach ($booking->getExtras()->keys() as $extraKey) {
                $selectedExtras[] = [
                    'id'       => $booking->getExtras()->getItem($extraKey)->getExtraId()->getValue(),
                    'quantity' => $booking->getExtras()->getItem($extraKey)->getQuantity()->getValue(),
                ];
            }

            if (!$timeSlotService->isSlotFree(
                $appointment->getServiceId()->getValue(),
                $appointment->getBookingStart()->getValue(),
                $appointment->getProviderId()->getValue(),
                $selectedExtras,
                null,
                $booking->getPersons()->getValue(),
                true
            )) {
                throw new BookingUnavailableException('');
            }
        }

        if ($save) {
            if ($existingAppointment) {
                $appointment->getBookings()->addItem($booking);
                $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);
                $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);
                $appointment->setStatus(new BookingStatus($appointmentStatus));
                $appointmentStatusChanged = $appointmentAS->isAppointmentStatusChanged(
                    $appointment,
                    $existingAppointment
                );

                try {
                    $appointmentAS->update($existingAppointment, $appointment, $service, $appointmentData['payment']);
                } catch (QueryExecutionException $e) {
                    throw $e;
                }
            } else {
                try {
                    $appointmentAS->add($appointment, $service, $appointmentData['payment']);
                } catch (QueryExecutionException $e) {
                    throw $e;
                }
            }
        }

        $reservation = new Reservation();

        $reservation->setBookable($service);
        $reservation->setBooking($booking);
        $reservation->setReservation($appointment);
        $reservation->setIsStatusChanged(new BooleanValueObject($appointmentStatusChanged));

        return $reservation;
    }

    /**
     * @param int    $bookingId
     * @param string $requestedStatus
     * @param string $token
     *
     * @return array
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws BookingCancellationException
     */
    public function updateStatus($bookingId, $requestedStatus, $token)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById($bookingId);

        $this->inspectToken($booking, $token);

        $appointmentId = $booking->getAppointmentId()->getValue();

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getById($appointmentId);

        if ($requestedStatus === BookingStatus::CANCELED) {
            $this->inspectMinimumCancellationTime($appointment->getBookingStart()->getValue());
        }

        /** @var Service $service */
        $service = $bookableAS->getAppointmentService(
            $appointment->getServiceId()->getValue(),
            $appointment->getProviderId()->getValue()
        );

        $appointment->getBookings()->getItem($bookingId)->setStatus(new BookingStatus($requestedStatus));
        $booking->setStatus(new BookingStatus($requestedStatus));

        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);

        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenChangingBookingStatus(
            $service,
            $bookingsCount,
            $appointment->getStatus()->getValue()
        );

        $appointmentRepository->beginTransaction();

        try {
            $bookingRepository->updateStatusById($bookingId, $requestedStatus);
            $appointmentRepository->updateStatusById($appointmentId, $appointmentStatus);
        } catch (QueryExecutionException $e) {
            $appointmentRepository->rollback();
            throw $e;
        }

        $appStatusChanged = false;

        if ($appointment->getStatus()->getValue() !== $appointmentStatus) {
            $appointment->setStatus(new BookingStatus($appointmentStatus));
            $appStatusChanged = true;
        }

        $appointmentRepository->commit();

        return [
            Entities::APPOINTMENT      => $appointment->toArray(),
            'appointmentStatusChanged' => $appStatusChanged,
            Entities::BOOKING          => $booking->toArray()
        ];
    }

    /**
     * @param Appointment      $reservation
     * @param CustomerBooking  $booking
     * @param Service          $bookable
     *
     * @return array
     * @throws InvalidArgumentException
     */
    public function getBookingPeriods($reservation, $booking, $bookable)
    {
        $duration = $bookable->getDuration()->getValue();

        /** @var CustomerBookingExtra $bookingExtra */
        foreach ($booking->getExtras()->getItems() as $bookingExtra) {
            /** @var Extra $extra */
            $extra = $bookable->getExtras()->getItem($bookingExtra->getExtraId()->getValue());

            $duration += ($extra->getDuration() ? $extra->getDuration()->getValue() : 0);
        }

        return [
            [
                'start' => DateTimeService::getCustomDateTimeInUtc(
                    $reservation->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                ),
                'end'   => DateTimeService::getCustomDateTimeInUtc(
                    DateTimeService::getCustomDateTimeObject(
                        $reservation->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    )->modify("+{$duration} seconds")->format('Y-m-d H:i:s')
                )
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws NotFoundException
     */
    public function getBookableEntity($data)
    {
        /** @var BookableApplicationService $bookableAS */
        $bookableAS = $this->container->get('application.bookable.service');

        return $bookableAS->getAppointmentService($data['serviceId'], $data['providerId']);
    }

    /**
     * @param Service $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable)
    {
        return $bookable->getAggregatedPrice()->getValue();
    }

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice)
    {
        return $extraAggregatedPrice === null ?
            $bookableAggregatedPrice->getValue() : $extraAggregatedPrice->getValue();
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Service          $bookable
     * @param CustomerBooking  $booking
     * @param Appointment      $reservation
     * @param string           $paymentGateway
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getInfo($bookable, $booking, $reservation, $paymentGateway)
    {
        $info = [
            'type'               => Entities::APPOINTMENT,
            'serviceId'          => $bookable->getId()->getValue(),
            'providerId'         => $reservation->getProviderId()->getValue(),
            'locationId'         => $reservation->getLocationId() ? $reservation->getLocationId()->getValue() : null,
            'name'               => $bookable->getName()->getValue(),
            'couponId'           => $booking->getCoupon() ? $booking->getCoupon()->getId()->getValue() : '',
            'couponCode'         => $booking->getCoupon() ? $booking->getCoupon()->getCode()->getValue() : '',
            'bookingStart'       => $reservation->getBookingStart()->getValue()->format('Y-m-d H:i'),
            'dateTimeValues'     => [
                [
                    'start' => $reservation->getBookingStart()->getValue()->format('Y-m-d H:i'),
                    'end'   => '',
                ]
            ],
            'notifyParticipants' => $reservation->isNotifyParticipants(),
            'bookings'           => [
                [
                    'customerId'   => $booking->getCustomer()->getId() ?
                        $booking->getCustomer()->getId()->getValue() : null,
                    'customer'     => [
                        'email'      => $booking->getCustomer()->getEmail()->getValue(),
                        'externalId' => $booking->getCustomer()->getExternalId() ?
                            $booking->getCustomer()->getExternalId()->getValue() : null,
                        'firstName'  => $booking->getCustomer()->getFirstName()->getValue(),
                        'id'         => $booking->getCustomer()->getId()
                            ? $booking->getCustomer()->getId()->getValue() : null,
                        'lastName'   => $booking->getCustomer()->getLastName()->getValue(),
                        'phone'      => $booking->getCustomer()->getPhone()->getValue()
                    ],
                    'persons'      => $booking->getPersons()->getValue(),
                    'extras'       => [],
                    'status'       => $booking->getStatus()->getValue(),
                    'utcOffset'    => $booking->getUtcOffset() ? $booking->getUtcOffset()->getValue() : null,
                    'customFields' => $booking->getCustomFields() ?
                        json_decode($booking->getCustomFields()->getValue(), true) : null
                ]
            ],
            'payment'            => [
                'gateway' => $paymentGateway
            ]
        ];

        foreach ($booking->getExtras()->keys() as $extraKey) {
            /** @var CustomerBookingExtra $bookingExtra */
            $bookingExtra = $booking->getExtras()->getItem($extraKey);

            $info['bookings'][0]['extras'][] = [
                'extraId'  => $bookingExtra->getExtraId()->getValue(),
                'quantity' => $bookingExtra->getQuantity()->getValue()
            ];
        }

        return $info;
    }

    /**
     * @param int $id
     *
     * @return Appointment
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function getReservationById($id)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Appointment $appointment */
        return $appointmentRepository->getById($id);
    }

    /**
     * @param int $id
     *
     * @return Appointment
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var Appointment $appointment */
        return $appointmentRepository->getByBookingId($id);
    }

    /**
     * @param Appointment $reservation
     * @param Service     $bookable
     * @param \DateTime   $dateTime
     *
     * @return boolean
     *
     */
    public function isBookable($reservation, $bookable, $dateTime)
    {
        return true;
    }
}
