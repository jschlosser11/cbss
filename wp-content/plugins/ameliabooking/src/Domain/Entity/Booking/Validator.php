<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Booking;

/**
 * Class Validator
 *
 * @package AmeliaBooking\Domain\Entity\Booking
 */
class Validator
{
    /** @var boolean */
    private $couponValidation = true;

    /** @var boolean */
    private $timeSlotValidation = true;

    /** @var  boolean */
    private $customFieldsValidation = true;

    /**
     * @return boolean
     */
    public function hasCouponValidation()
    {
        return $this->couponValidation;
    }

    /**
     * @param boolean $couponValidation
     */
    public function setCouponValidation($couponValidation)
    {
        $this->couponValidation = $couponValidation;
    }

    /**
     * @return boolean
     */
    public function hasTimeSlotValidation()
    {
        return $this->timeSlotValidation;
    }

    /**
     * @param boolean $timeSlotValidation
     */
    public function setTimeSlotValidation($timeSlotValidation)
    {
        $this->timeSlotValidation = $timeSlotValidation;
    }

    /**
     * @return boolean
     */
    public function hasCustomFieldsValidation()
    {
        return $this->customFieldsValidation;
    }

    /**
     * @param boolean $customFieldsValidation
     */
    public function setCustomFieldsValidation($customFieldsValidation)
    {
        $this->customFieldsValidation = $customFieldsValidation;
    }
}
