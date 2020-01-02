<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Domain\Entity\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\Number\Float\Price;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\Id;
use AmeliaBooking\Domain\ValueObjects\Number\Integer\IntegerValue;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Domain\ValueObjects\Priority;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Domain\Entity\Bookable\AbstractBookable;
use AmeliaBooking\Domain\ValueObjects\Duration;
use AmeliaBooking\Domain\ValueObjects\PositiveDuration;

/**
 * Class Service
 *
 * @package AmeliaBooking\Domain\Entity\Bookable\Service
 */
class Service extends AbstractBookable
{
    /** @var  IntegerValue */
    private $minCapacity;

    /** @var  IntegerValue */
    private $maxCapacity;

    /** @var  PositiveDuration */
    private $duration;

    /** @var  Duration */
    private $timeBefore;

    /** @var  Duration */
    private $timeAfter;

    /** @var BooleanValueObject */
    private $bringingAnyone;

    /** @var Priority */
    private $priority;

    /** @var Collection */
    private $gallery;

    /** @var  Status */
    protected $status;

    /** @var  Id */
    protected $categoryId;

    /** @var  BooleanValueObject */
    protected $show;

    /** @var  BooleanValueObject */
    protected $aggregatedPrice;

    /**
     * Service constructor.
     *
     * @param Name             $name
     * @param Price            $price
     * @param Status           $status
     * @param Id               $categoryId
     * @param IntegerValue     $minCapacity
     * @param IntegerValue     $maxCapacity
     * @param PositiveDuration $duration
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        Name $name,
        Price $price,
        Status $status,
        Id $categoryId,
        IntegerValue $minCapacity,
        IntegerValue $maxCapacity,
        PositiveDuration $duration
    ) {

        if (!$duration->getValue()) {
            throw new InvalidArgumentException('Duration cannot be zero.');
        }

        parent::__construct($name, $price);
        $this->minCapacity = $minCapacity;
        $this->maxCapacity = $maxCapacity;
        $this->duration = $duration;
        $this->categoryId = $categoryId;
        $this->status = $status;
    }

    /**
     * @return Id
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @param Id $categoryId
     */
    public function setCategoryId(Id $categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * @return Status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param Status $status
     */
    public function setStatus(Status $status)
    {
        $this->status = $status;
    }

    /**
     * @return IntegerValue
     */
    public function getMinCapacity()
    {
        return $this->minCapacity;
    }

    /**
     * @param IntegerValue $minCapacity
     */
    public function setMinCapacity(IntegerValue $minCapacity)
    {
        $this->minCapacity = $minCapacity;
    }

    /**
     * @return IntegerValue
     */
    public function getMaxCapacity()
    {
        return $this->maxCapacity;
    }

    /**
     * @param IntegerValue $maxCapacity
     */
    public function setMaxCapacity(IntegerValue $maxCapacity)
    {
        $this->maxCapacity = $maxCapacity;
    }

    /**
     * @return PositiveDuration
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param PositiveDuration $duration
     */
    public function setDuration(PositiveDuration $duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return Duration
     */
    public function getTimeBefore()
    {
        return $this->timeBefore;
    }

    /**
     * @param Duration $timeBefore
     */
    public function setTimeBefore(Duration $timeBefore)
    {
        $this->timeBefore = $timeBefore;
    }

    /**
     * @return Duration
     */
    public function getTimeAfter()
    {
        return $this->timeAfter;
    }

    /**
     * @param Duration $timeAfter
     */
    public function setTimeAfter(Duration $timeAfter)
    {
        $this->timeAfter = $timeAfter;
    }

    /**
     * @return BooleanValueObject
     */
    public function getBringingAnyone()
    {
        return $this->bringingAnyone;
    }

    /**
     * @param BooleanValueObject $bringingAnyone
     */
    public function setBringingAnyone(BooleanValueObject $bringingAnyone)
    {
        $this->bringingAnyone = $bringingAnyone;
    }

    /**
     * @return BooleanValueObject
     */
    public function getShow()
    {
        return $this->show;
    }

    /**
     * @param BooleanValueObject $show
     */
    public function setShow(BooleanValueObject $show)
    {
        $this->show = $show;
    }

    /**
     * @return BooleanValueObject
     */
    public function getAggregatedPrice()
    {
        return $this->aggregatedPrice;
    }

    /**
     * @param BooleanValueObject $aggregatedPrice
     */
    public function setAggregatedPrice(BooleanValueObject $aggregatedPrice)
    {
        $this->aggregatedPrice = $aggregatedPrice;
    }

    /**
     * @return Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Priority $priority
     */
    public function setPriority(Priority $priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return Collection
     */
    public function getGallery()
    {
        return $this->gallery;
    }

    /**
     * @param Collection $gallery
     */
    public function setGallery(Collection $gallery)
    {
        $this->gallery = $gallery;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            [
                'minCapacity'     => $this->getMinCapacity() ? $this->getMinCapacity()->getValue() : null,
                'maxCapacity'     => $this->getMaxCapacity() ? $this->getMaxCapacity()->getValue() : null,
                'duration'        => $this->getDuration() ? $this->getDuration()->getValue() : null,
                'timeBefore'      => $this->getTimeBefore() ? $this->getTimeBefore()->getValue() : null,
                'timeAfter'       => $this->getTimeAfter() ? $this->getTimeAfter()->getValue() : null,
                'bringingAnyone'  => $this->getBringingAnyone() ? $this->getBringingAnyone()->getValue() : null,
                'show'            => $this->getShow() ? $this->getShow()->getValue() : null,
                'aggregatedPrice' => $this->getAggregatedPrice() ? $this->getAggregatedPrice()->getValue() : null,
                'status'          => $this->getStatus()->getValue(),
                'categoryId'      => $this->getCategoryId()->getValue(),
                'priority'        => $this->getPriority() ? $this->getPriority()->getValue() : [],
                'gallery'         => $this->getGallery() ? $this->getGallery()->toArray() : []
            ]
        );
    }
}
