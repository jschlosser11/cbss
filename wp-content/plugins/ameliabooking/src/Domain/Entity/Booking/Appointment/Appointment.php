<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking\Appointment;

use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\AbstractBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\String\BookingType;
use AmeliaBooking\Domain\ValueObjects\String\Token;

/**
 * Class Appointment
 *
 * @package AmeliaBooking\Domain\Entity\Booking\Appointment
 */
class Appointment extends AbstractBooking
{
    /** @var Id */
    private $serviceId;

    /** @var Service */
    private $service;

    /** @var Id */
    private $providerId;

    /** @var Provider */
    private $provider;

    /** @var Id */
    private $locationId;

    /** @var Location */
    private $location;

    /** @var Token */
    private $googleCalendarEventId;

    /** @var DateTimeValue */
    protected $bookingStart;

    /** @var DateTimeValue */
    protected $bookingEnd;

    /**
     * Appointment constructor.
     *
     * @param DateTimeValue $bookingStart
     * @param DateTimeValue $bookingEnd
     * @param bool          $notifyParticipants
     * @param Id            $serviceId
     * @param Id            $providerId
     */
    public function __construct(
        DateTimeValue $bookingStart,
        DateTimeValue $bookingEnd,
        $notifyParticipants,
        Id $serviceId,
        Id $providerId
    ) {
        parent::__construct($notifyParticipants);
        $this->bookingStart = $bookingStart;
        $this->bookingEnd = $bookingEnd;
        $this->serviceId = $serviceId;
        $this->providerId = $providerId;
    }

    /**
     * @return Id
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param Id $serviceId
     */
    public function setServiceId(Id $serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param Service $service
     */
    public function setService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @return Id
     */
    public function getProviderId()
    {
        return $this->providerId;
    }

    /**
     * @param Id $providerId
     */
    public function setProviderId(Id $providerId)
    {
        $this->providerId = $providerId;
    }

    /**
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param Provider $provider
     */
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return Id
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @param Id $locationId
     */
    public function setLocationId(Id $locationId)
    {
        $this->locationId = $locationId;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     */
    public function setLocation(Location $location)
    {
        $this->location = $location;
    }

    /**
     * @return Token
     */
    public function getGoogleCalendarEventId()
    {
        return $this->googleCalendarEventId;
    }

    /**
     * @param Token $googleCalendarEventId
     */
    public function setGoogleCalendarEventId($googleCalendarEventId)
    {
        $this->googleCalendarEventId = $googleCalendarEventId;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingStart()
    {
        return $this->bookingStart;
    }

    /**
     * @param DateTimeValue $bookingStart
     */
    public function setBookingStart(DateTimeValue $bookingStart)
    {
        $this->bookingStart = $bookingStart;
    }

    /**
     * @return DateTimeValue
     */
    public function getBookingEnd()
    {
        return $this->bookingEnd;
    }

    /**
     * @param DateTimeValue $bookingEnd
     */
    public function setBookingEnd(DateTimeValue $bookingEnd)
    {
        $this->bookingEnd = $bookingEnd;
    }

    /**
     * @return BookingType
     */
    public function getType()
    {
        return new Bookingtype(Entities::APPOINTMENT);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'serviceId'             => $this->getServiceId()->getValue(),
                'providerId'            => $this->getProviderId()->getValue(),
                'locationId'            => null !== $this->getLocationId() ? $this->getLocationId()->getValue() : null,
                'provider'              => null !== $this->getProvider() ? $this->getProvider()->toArray() : null,
                'service'               => null !== $this->getService() ? $this->getService()->toArray() : null,
                'location'              => null !== $this->getLocation() ? $this->getLocation()->toArray() : null,
                'googleCalendarEventId' => null !== $this->getGoogleCalendarEventId() ?
                    $this->getGoogleCalendarEventId()->getValue() : null,
                'bookingStart'          => $this->getBookingStart()->getValue()->format('Y-m-d H:i:s'),
                'bookingEnd'            => $this->getBookingEnd()->getValue()->format('Y-m-d H:i:s'),
                'type'                  => $this->getType()->getValue()
            ]
        );
    }
}
