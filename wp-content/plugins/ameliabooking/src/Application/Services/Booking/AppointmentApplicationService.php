<?php

namespace AmeliaBooking\Application\Services\Booking;

use AmeliaBooking\Application\Services\TimeSlot\TimeSlotService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Extra;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Booking\AppointmentDomainService;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Infrastructure\Repository\Payment\PaymentRepository;

/**
 * Class AppointmentApplicationService
 *
 * @package AmeliaBooking\Application\Services\Booking
 */
class AppointmentApplicationService
{
    private $container;

    /**
     * AppointmentApplicationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param array   $data
     * @param Service $service
     *
     * @return Appointment
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function build($data, $service)
    {
        /** @var AppointmentDomainService $appointmentDS */
        $appointmentDS = $this->container->get('domain.booking.appointment.service');

        $data['bookingEnd'] = $data['bookingStart'];

        $appointment = AppointmentFactory::create($data);

        if (!$appointment instanceof Appointment) {
            return null;
        }

        $duration = $service->getDuration()->getValue();

        $includedExtrasIds = [];

        foreach ($appointment->getBookings()->keys() as $customerBookingKey) {
            $customerBooking = $appointment->getBookings()->getItem($customerBookingKey);

            foreach ((array)$customerBooking->getExtras()->keys() as $extraKey) {
                /** @var CustomerBookingExtra $customerBookingExtra */
                $customerBookingExtra = $customerBooking->getExtras()->getItem($extraKey);

                $extraId = $customerBookingExtra->getExtraId()->getValue();

                /** @var Extra $extra */
                $extra = $service->getExtras()->getItem($extraId);

                $extraDuration = $extra->getDuration() ? $extra->getDuration()->getValue() : 0;
                $extraQuantity = $customerBookingExtra->getQuantity() ?
                    $customerBookingExtra->getQuantity()->getValue() : 0;

                if (!in_array($extraId, $includedExtrasIds, true)) {
                    $includedExtrasIds[] = $extraId;
                    $duration += ($extraDuration * $extraQuantity);
                }

                $customerBookingExtra->setPrice(new Price($extra->getPrice()->getValue()));
            }

            $customerBooking->setPrice(new Price($service->getPrice()->getValue()));
        }

        // Set appointment status based on booking statuses
        $bookingsCount = $appointmentDS->getBookingsStatusesCount($appointment);
        $appointmentStatus = $appointmentDS->getAppointmentStatusWhenEditAppointment($service, $bookingsCount);
        $appointment->setStatus(new BookingStatus($appointmentStatus));

        $appointment->setBookingEnd(
            new DateTimeValue(
                DateTimeService::getCustomDateTimeObject($data['bookingStart'])->modify('+' . $duration . ' second')
            )
        );

        return $appointment;
    }


    /**
     * @param Appointment $appointment
     * @param Service     $service
     * @param array       $paymentData
     *
     * @return Appointment
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function add($appointment, $service, $paymentData)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        $appointmentId = $appointmentRepository->add($appointment);
        $appointment->setId(new Id($appointmentId));

        /** @var CustomerBooking $customerBooking */
        foreach ($appointment->getBookings()->keys() as $customerBookingKey) {
            $customerBooking = $appointment->getBookings()->getItem($customerBookingKey);

            $customerBooking->setAppointmentId($appointment->getId());
            $customerBooking->setAggregatedPrice(new BooleanValueObject($service->getAggregatedPrice()->getValue()));
            $customerBooking->setToken(new Token());
            $customerBookingId = $bookingRepository->add($customerBooking);

            /** @var CustomerBookingExtra $customerBookingExtra */
            foreach ($customerBooking->getExtras()->keys() as $cbExtraKey) {
                $customerBookingExtra = $customerBooking->getExtras()->getItem($cbExtraKey);

                /** @var Extra $serviceExtra */
                $serviceExtra = $service->getExtras()->getItem($customerBookingExtra->getExtraId()->getValue());

                $customerBookingExtra->setAggregatedPrice(new BooleanValueObject(
                    $reservationService->isExtraAggregatedPrice(
                        $serviceExtra->getAggregatedPrice(),
                        $service->getAggregatedPrice()
                    )
                ));
                $customerBookingExtra->setCustomerBookingId(new Id($customerBookingId));
                $customerBookingExtraId = $customerBookingExtraRepository->add($customerBookingExtra);
                $customerBookingExtra->setId(new Id($customerBookingExtraId));
            }

            $customerBooking->setId(new Id($customerBookingId));

            $reservationService->addPayment(
                $customerBookingId,
                $paymentData,
                $reservationService->getPaymentAmount($customerBooking, $service),
                $appointment->getBookingStart()->getValue()
            );
        }

        return $appointment;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Appointment $oldAppointment
     * @param Appointment $newAppointment
     * @param Service     $service
     * @param array       $paymentData
     *
     * @return bool
     * @throws \Slim\Exception\ContainerException
     * @throws \InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function update($oldAppointment, $newAppointment, $service, $paymentData)
    {
        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');
        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');
        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::APPOINTMENT);

        $appointmentRepo->update($oldAppointment->getId()->getValue(), $newAppointment);

        $existingBookingIds = [];
        $existingExtraIds = [];

        foreach ((array)$newAppointment->getBookings()->keys() as $appointmentKey) {
            if (!($newBooking = $newAppointment->getBookings()->getItem($appointmentKey)) instanceof CustomerBooking) {
                throw new InvalidArgumentException('Unknown type');
            }

            // Update Booking if ID exist
            if ($newBooking->getId() && $newBooking->getId()->getValue()) {
                $bookingRepository->update($newBooking->getId()->getValue(), $newBooking);

                if ($oldAppointment->getServiceId()->getValue() !== $newAppointment->getServiceId()->getValue()) {
                    $bookingRepository->updatePrice($newBooking->getId()->getValue(), $newBooking);
                }
            }

            // Add Booking if ID does not exist
            if ($newBooking->getId() === null || ($newBooking->getId()->getValue() === 0)) {
                $newBooking->setAppointmentId($newAppointment->getId());
                $newBooking->setToken(new Token());
                $newBooking->setAggregatedPrice(new BooleanValueObject($service->getAggregatedPrice()->getValue()));
                $newBookingId = $bookingRepository->add($newBooking);

                $newBooking->setId(new Id($newBookingId));

                $reservationService->addPayment(
                    $newBookingId,
                    $paymentData,
                    $reservationService->getPaymentAmount($newBooking, $service),
                    $newAppointment->getBookingStart()->getValue()
                );
            }

            $existingBookingIds[] = $newBooking->getId()->getValue();

            $existingExtraIds[$newBooking->getId()->getValue()] = [];

            foreach ((array)$newBooking->getExtras()->keys() as $extraKey) {
                if (!($newExtra = $newBooking->getExtras()->getItem($extraKey)) instanceof CustomerBookingExtra) {
                    throw new InvalidArgumentException('Unknown type');
                }

                // Update Extra if ID exist
                /** @var CustomerBookingExtra $newExtra */
                if ($newExtra->getId() && $newExtra->getId()->getValue()) {
                    $customerBookingExtraRepository->update($newExtra->getId()->getValue(), $newExtra);
                }

                // Add Extra if ID does not exist
                if ($newExtra->getId() === null || ($newExtra->getId()->getValue() === 0)) {
                    /** @var Extra $serviceExtra */
                    $serviceExtra = $service->getExtras()->getItem($newExtra->getExtraId()->getValue());

                    $newExtra->setAggregatedPrice(new BooleanValueObject(
                        $reservationService->isExtraAggregatedPrice(
                            $serviceExtra->getAggregatedPrice(),
                            $service->getAggregatedPrice()
                        )
                    ));
                    $newExtra->setCustomerBookingId($newBooking->getId());
                    $newExtraId = $customerBookingExtraRepository->add($newExtra);

                    $newExtra->setId(new Id($newExtraId));
                }

                $existingExtraIds[$newBooking->getId()->getValue()][$newExtra->getId()->getValue()] = true;
            }
        }

        // Delete if not exist
        foreach ((array)$oldAppointment->getBookings()->keys() as $bookingKey) {
            /** @var CustomerBooking $oldBooking */
            if (!($oldBooking = $oldAppointment->getBookings()->getItem($bookingKey)) instanceof CustomerBooking) {
                throw new InvalidArgumentException('Unknown type');
            }

            foreach ((array)$oldBooking->getExtras()->keys() as $extraKey) {
                if (!($oldExtra = $oldBooking->getExtras()->getItem($extraKey)) instanceof CustomerBookingExtra) {
                    throw new InvalidArgumentException('Unknown type');
                }

                if (!isset($existingExtraIds[$oldBooking->getId()->getValue()][$oldExtra->getId()->getValue()])) {
                    $customerBookingExtraRepository->delete($oldExtra->getId()->getValue());
                }
            }

            if (!in_array($oldBooking->getId()->getValue(), $existingBookingIds, true)) {
                $bookingId = $oldBooking->getId()->getValue();

                $paymentRepository->deleteByEntityId($bookingId, 'customerBookingId');

                $bookingRepository->delete($bookingId);
            }
        }

        return true;
    }

    /**
     * @param Appointment $appointment
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function delete($appointment)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBookingExtraRepository $customerBookingExtraRepository */
        $customerBookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get('domain.payment.repository');

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            if (
                !$paymentRepository->deleteByEntityId($booking->getId()->getValue(), 'customerBookingId') ||
                !$customerBookingExtraRepository->deleteByEntityId($booking->getId()->getValue(), 'customerBookingId') ||
                !$bookingRepository->delete($booking->getId()->getValue())
            ) {
                return false;
            }
        }

        if (!$appointmentRepository->delete($appointment->getId()->getValue())) {
            return false;
        }

        return true;
    }

    /**
     * @param Appointment $appointment
     * @param Appointment $oldAppointment
     *
     * @return bool
     */
    public function isAppointmentStatusChanged($appointment, $oldAppointment)
    {
        return $appointment->getStatus()->getValue() !== $oldAppointment->getStatus()->getValue();
    }

    /**
     * @param Appointment $appointment
     * @param Appointment $oldAppointment
     *
     * @return bool
     */
    public function isAppointmentRescheduled($appointment, $oldAppointment)
    {
        $start = $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');
        $end = $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        $oldStart = $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');
        $oldEnd = $oldAppointment->getBookingStart()->getValue()->format('Y-m-d H:i:s');

        return $start !== $oldStart || $end !== $oldEnd;
    }

    /**
     * Return required time for the appointment in seconds by summing service duration, service time before and after
     * and each passed extra.
     *
     * @param Service    $service
     * @param Collection $extras
     * @param array      $selectedExtras
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getAppointmentRequiredTime($service, $extras = null, $selectedExtras = null)
    {
        $requiredTime =
            $service->getTimeBefore()->getValue() +
            $service->getDuration()->getValue() +
            $service->getTimeAfter()->getValue();

        if ($extras) {
            foreach ($extras->keys() as $extraKey) {
                $requiredTime += ($extras->getItem($extraKey)->getDuration()->getValue() *
                    array_column($selectedExtras, 'quantity', 'id')[$extras->getItem($extraKey)->getId()->getValue()]);
            }
        }

        return $requiredTime;
    }

    /**
     * Return required time for the appointment in seconds by summing service duration, service time before and after
     * and extras.
     *
     * @param Appointment $appointment
     * @param Service     $service
     *
     * @return mixed
     */
    public function getAppointmentLengthTime($appointment, $service)
    {
        $requiredTime = $service->getDuration()->getValue();

        $selectedExtrasQuantities = [];

        /** @var CustomerBooking $booking */
        foreach ($appointment->getBookings()->getItems() as $booking) {
            /** @var CustomerBookingExtra $bookingExtra */
            foreach ($booking->getExtras()->getItems() as $bookingExtra) {
                $extraId = $bookingExtra->getExtraId()->getValue();

                if (!array_key_exists($extraId, $selectedExtrasQuantities)) {
                    $selectedExtrasQuantities[$extraId] = $bookingExtra->getQuantity()->getValue();
                } elseif ($selectedExtrasQuantities[$extraId] > $bookingExtra->getQuantity()->getValue()) {
                    $selectedExtrasQuantities[$extraId] = $bookingExtra->getQuantity()->getValue();
                }
            }
        }

        /** @var Extra $extra */
        foreach ($service->getExtras()->getItems() as $extra) {
            $extraId = $extra->getId()->getValue();

            if (array_key_exists($extraId, $selectedExtrasQuantities)) {
                $extraDuration = $extra->getDuration() ? $extra->getDuration()->getValue() : 0;

                $requiredTime += $extraDuration * $selectedExtrasQuantities[$extraId];
            }
        }

        return $requiredTime;
    }

    /**
     * @param Appointment $appointment
     * @param boolean     $isCustomer
     *
     * @return boolean
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function canBeBooked($appointment, $isCustomer)
    {
        /** @var TimeSlotService $timeSlotService */
        $timeSlotService = $this->container->get('application.timeSlot.service');

        $selectedExtras = [];

        foreach ($appointment->getBookings()->keys() as $bookingKey) {
            /** @var CustomerBooking $booking */
            $booking = $appointment->getBookings()->getItem($bookingKey);

            foreach ($booking->getExtras()->keys() as $extraKey) {
                $selectedExtras[] = [
                    'id'       => $booking->getExtras()->getItem($extraKey)->getExtraId()->getValue(),
                    'quantity' => $booking->getExtras()->getItem($extraKey)->getQuantity()->getValue(),
                ];
            }
        }

        return $timeSlotService->isSlotFree(
            $appointment->getServiceId()->getValue(),
            $appointment->getBookingStart()->getValue(),
            $appointment->getProviderId()->getValue(),
            $selectedExtras,
            $appointment->getId() ? $appointment->getId()->getValue() : null,
            null,
            $isCustomer
        );
    }
}
