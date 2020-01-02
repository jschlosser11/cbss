<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBookingExtra;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Booking\Reservation;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Booking\Appointment\CustomerBookingFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\CustomerBookingEventPeriodFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Token;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingExtraRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\CustomerBookingRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\CustomerBookingEventPeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;

/**
 * Class EventReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class EventReservationService extends AbstractReservationService
{
    /**
     * @return string
     */
    public function getType()
    {
        return Entities::EVENT;
    }

    /**
     * @param array $eventData
     * @param bool  $inspectAvailability
     * @param bool  $save
     *
     * @return Reservation|null
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\BookingUnavailableException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function book($eventData, $inspectAvailability, $save)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Event $event */
        $event = $eventRepository->getById($eventData['eventId']);

        $booking = CustomerBookingFactory::create(
            array_merge($eventData['bookings'][0], ['status' => BookingStatus::APPROVED])
        );

        $booking->setStatus(new BookingStatus(BookingStatus::APPROVED));

        $personsCount = 0;

        /** @var CustomerBooking $customerBooking */
        foreach ($event->getBookings()->getItems() as $customerBooking) {
            if ($customerBooking->getStatus()->getValue() === BookingStatus::APPROVED) {
                $personsCount += $customerBooking->getPersons()->getValue();
            }
        }

        if ($inspectAvailability &&
            !$this->isBookable($event, null, DateTimeService::getNowDateTimeObject()) &&
            $personsCount + $booking->getPersons()->getValue() > $event->getMaxCapacity()->getValue()
        ) {
            throw new BookingUnavailableException('');
        }

        $booking->setAggregatedPrice(new BooleanValueObject(true));

        if ($save) {
            /** @var CustomerBookingRepository $bookingRepository */
            $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

            /** @var CustomerBookingExtraRepository $bookingExtraRepository */
            $bookingExtraRepository = $this->container->get('domain.booking.customerBookingExtra.repository');

            /** @var CustomerBookingEventPeriodRepository $bookingEventPeriodRepository */
            $bookingEventPeriodRepository = $this->container->get('domain.booking.customerBookingEventPeriod.repository');

            $booking->setPrice(new Price($event->getPrice()->getValue()));
            $booking->setToken(new Token());

            $bookingId = $bookingRepository->add($booking);

            /** @var CustomerBookingExtra $bookingExtra */
            foreach ($booking->getExtras()->getItems() as $bookingExtra) {
                $bookingExtra->setCustomerBookingId(new Id($bookingId));
                $bookingExtraId = $bookingExtraRepository->add($bookingExtra);
                $bookingExtra->setId(new Id($bookingExtraId));
            }

            $booking->setId(new Id($bookingId));

            $this->addPayment(
                $bookingId,
                $eventData['payment'],
                $this->getPaymentAmount($booking, $event),
                $event->getPeriods()->getItem(0)->getPeriodStart()->getValue()
            );

            /** @var EventPeriod $eventPeriod */
            foreach ($event->getPeriods()->getItems() as $eventPeriod) {
                $bookingEventPeriod = CustomerBookingEventPeriodFactory::create([
                    'eventPeriodId' => $eventPeriod->getId()->getValue(),
                    'customerBookingId' => $bookingId
                ]);

                $bookingEventPeriodRepository->add($bookingEventPeriod);
            }

            $event->getBookings()->addItem($booking, $booking->getId()->getValue());
        }

        $reservation = new Reservation();

        $reservation->setBookable($event);
        $reservation->setBooking($booking);
        $reservation->setReservation($event);
        $reservation->setIsStatusChanged(new BooleanValueObject(false));

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
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws BookingCancellationException
     * @throws NotFoundException
     */
    public function updateStatus($bookingId, $requestedStatus, $token)
    {
        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        /** @var CustomerBooking $booking */
        $booking = $bookingRepository->getById($bookingId);

        /** @var Event $reservation */
        $event = $this->getReservationByBookingId($bookingId);

        $this->inspectToken($booking, $token);

        if ($requestedStatus === BookingStatus::CANCELED) {
            $this->inspectMinimumCancellationTime($event->getPeriods()->getItem(0)->getPeriodStart()->getValue());
        }

        $booking->setStatus(new BookingStatus($requestedStatus));

        /** @var CustomerBookingRepository $bookingRepository */
        $bookingRepository = $this->container->get('domain.booking.customerBooking.repository');

        $bookingRepository->update($bookingId, $booking);

        return [
            Entities::EVENT            => $event->toArray(),
            'appointmentStatusChanged' => false,
            Entities::BOOKING          => $booking->toArray()
        ];
    }

    /**
     * @param Event            $reservation
     * @param CustomerBooking  $booking
     * @param AbstractBookable $bookable
     *
     * @return array
     */
    public function getBookingPeriods($reservation, $booking, $bookable)
    {
        $dates = [];

        /** @var EventPeriod $period */
        foreach ($reservation->getPeriods()->getItems() as $period) {
            $dates[] = [
                'start' => DateTimeService::getCustomDateTimeInUtc(
                    $period->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
                ),
                'end'   => DateTimeService::getCustomDateTimeInUtc(
                    $period->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
                )
            ];
        }

        return $dates;
    }

    /**
     * @param array $data
     *
     * @return AbstractBookable
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getBookableEntity($data)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        return $eventRepository->getById($data['eventId']);
    }

    /**
     * @param Event $bookable
     *
     * @return boolean
     */
    public function isAggregatedPrice($bookable)
    {
        return true;
    }

    /**
     * @param BooleanValueObject $bookableAggregatedPrice
     * @param BooleanValueObject $extraAggregatedPrice
     *
     * @return boolean
     */
    public function isExtraAggregatedPrice($extraAggregatedPrice, $bookableAggregatedPrice) {
        return true;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Event            $bookable
     * @param CustomerBooking  $booking
     * @param Event            $reservation
     * @param string           $paymentGateway
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getInfo($bookable, $booking, $reservation, $paymentGateway)
    {
        $dateTimeValues = [];

        /** @var EventPeriod $period */
        foreach ($bookable->getPeriods()->getItems() as $period) {
            $dateTimeValues[] = [
                'start' => $period->getPeriodStart()->getValue()->format('Y-m-d H:i'),
                'end'   => $period->getPeriodEnd()->getValue()->format('Y-m-d H:i')
            ];
        }

        $info = [
            'type'               => Entities::EVENT,
            'eventId'            => $bookable->getId()->getValue(),
            'name'               => $bookable->getName()->getValue(),
            'couponId'           => $booking->getCoupon() ? $booking->getCoupon()->getId()->getValue() : '',
            'couponCode'         => $booking->getCoupon() ? $booking->getCoupon()->getCode()->getValue() : '',
            'dateTimeValues'     => $dateTimeValues,
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
     * @return Event
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationById($id)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        return $eventRepository->getById($id);
    }

    /**
     * @param int $id
     *
     * @return Event
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function getReservationByBookingId($id)
    {
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Collection $events */
        $events = $eventRepository->getByBookingIds([$id]);

        /** @var Event $event */
        return $events->length() ? $events->getItem($events->keys()[0]) : null;
    }

    /**
     * @param Event     $reservation
     * @param Event     $bookable
     * @param \DateTime $dateTime
     *
     * @return boolean
     *
     * @throws InvalidArgumentException
     */
    public function isBookable($reservation, $bookable, $dateTime)
    {
        $persons = 0;

        /** @var CustomerBooking $booking */
        foreach ($reservation->getBookings()->getItems() as $booking) {
            if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                $persons += $booking->getPersons()->getValue();
            }
        }

        $bookingCloses = $reservation->getBookingCloses() ?
            $reservation->getBookingCloses()->getValue() :
            $reservation->getPeriods()->getItem(0)->getPeriodStart()->getValue();

        $bookingOpens = $reservation->getBookingOpens() ?
            $reservation->getBookingOpens()->getValue() :
            $reservation->getCreated()->getValue();

        return $dateTime > $bookingOpens &&
            $dateTime < $bookingCloses &&
            $reservation->getMaxCapacity()->getValue() - $persons > 0 &&
            in_array($reservation->getStatus()->getValue(), [BookingStatus::APPROVED, BookingStatus::PENDING], true);
    }
}
