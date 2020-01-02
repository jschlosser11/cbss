<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking;

use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;

/**
 * Class Reservation
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
class Reservation
{
    /** @var Appointment|Event */
    private $reservation;

    /** @var CustomerBooking */
    private $booking;

    /** @var  AbstractBookable */
    private $bookable;

    /** @var BooleanValueObject */
    private $isNewUser;

    /** @var BooleanValueObject */
    private $isStatusChanged;

    /** @var array */
    private $uploadedCustomFieldFilesInfo;

    /**
     * @return Appointment|Event
     */
    public function getReservation()
    {
        return $this->reservation;
    }

    /**
     * @param Appointment|Event $reservation
     */
    public function setReservation($reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * @return CustomerBooking
     */
    public function getBooking()
    {
        return $this->booking;
    }

    /**
     * @param CustomerBooking $booking
     */
    public function setBooking(CustomerBooking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * @return AbstractBookable
     */
    public function getBookable()
    {
        return $this->bookable;
    }

    /**
     * @param AbstractBookable $bookable
     */
    public function setBookable(AbstractBookable $bookable)
    {
        $this->bookable = $bookable;
    }

    /**
     * @return BooleanValueObject
     */
    public function isNewUser()
    {
        return $this->isNewUser;
    }

    /**
     * @param BooleanValueObject $isNewUser
     */
    public function setIsNewUser(BooleanValueObject $isNewUser)
    {
        $this->isNewUser = $isNewUser;
    }

    /**
     * @return BooleanValueObject
     */
    public function isStatusChanged()
    {
        return $this->isStatusChanged;
    }

    /**
     * @param BooleanValueObject $isStatusChanged
     */
    public function setIsStatusChanged(BooleanValueObject $isStatusChanged)
    {
        $this->isStatusChanged = $isStatusChanged;
    }

    /**
     * @return array
     */
    public function getUploadedCustomFieldFilesInfo()
    {
        return $this->uploadedCustomFieldFilesInfo;
    }

    /**
     * @param array $uploadedCustomFieldFilesInfo
     */
    public function setUploadedCustomFieldFilesInfo(array $uploadedCustomFieldFilesInfo)
    {
        $this->uploadedCustomFieldFilesInfo = $uploadedCustomFieldFilesInfo;
    }
}
