<?php

namespace AmeliaBooking\Domain\Services\Booking;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Gallery\GalleryImage;
use AmeliaBooking\Domain\Factory\Gallery\GalleryImageFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\DateTime\DateTimeValue;
use AmeliaBooking\Domain\ValueObjects\Recurring;
use AmeliaBooking\Domain\ValueObjects\String\Cycle;

/**
 * Class EventDomainService
 *
 * @package AmeliaBooking\Domain\Services\Booking
 */
class EventDomainService
{
    /**
     * @param Recurring  $recurring
     * @param Collection $eventPeriods
     *
     * @return Collection
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getRecurringEventsPeriods($recurring, $eventPeriods)
    {
        $recurringPeriods = new Collection();

        if (!($recurring && $recurring->getCycle() && $recurring->getUntil())) {
            return $recurringPeriods;
        }

        $modifyCycle = 'days';
        $modifyBaseValue = 0;

        switch ($recurring->getCycle()->getValue()) {
            case (Cycle::WEEKLY):
                $modifyCycle = 'days';
                $modifyBaseValue = 7;
                break;

            case (Cycle::MONTHLY):
                $modifyCycle = 'months';
                $modifyBaseValue = 1;
                break;

            case (Cycle::YEARLY):
                $modifyCycle = 'years';
                $modifyBaseValue = 1;
                break;
        }

        $hasMoreRecurringPeriods = true;

        $recurringOrder = 1;

        while ($hasMoreRecurringPeriods) {
            $periods = new Collection();

            $modifyValue = $recurringOrder * $modifyBaseValue;

            /** @var EventPeriod $eventPeriod **/
            foreach ($eventPeriods->getItems() as $eventPeriod) {
                /** @var \DateTime $periodStart **/
                $periodStart = DateTimeService::getCustomDateTimeObject(
                    $eventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
                )->modify("+{$modifyValue} {$modifyCycle}");

                /** @var \DateTime $periodEnd **/
                $periodEnd = DateTimeService::getCustomDateTimeObject(
                    $eventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
                )->modify("+{$modifyValue} {$modifyCycle}");

                /** @var EventPeriod $newEventPeriod **/
                $newEventPeriod = new EventPeriod();

                $newEventPeriod->setPeriodStart(new DateTimeValue($periodStart));
                $newEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));

                $periods->addItem($newEventPeriod);

                if ($periodStart > $recurring->getUntil()->getValue()) {
                    $hasMoreRecurringPeriods = false;
                }
            }

            if ($hasMoreRecurringPeriods) {
                $recurringPeriods->addItem($periods);
                $recurringOrder++;
            }
        }

        return $recurringPeriods;
    }

    /**
     * @param Collection $eventPeriods
     *
     * @return Collection
     *
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getClonedEventPeriods($eventPeriods)
    {
        $clonedPeriods = new Collection();

        /** @var EventPeriod $eventPeriod **/
        foreach ($eventPeriods->getItems() as $eventPeriod) {
            /** @var \DateTime $periodStart **/
            $periodStart = DateTimeService::getCustomDateTimeObject(
                $eventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
            );

            /** @var \DateTime $periodEnd **/
            $periodEnd = DateTimeService::getCustomDateTimeObject(
                $eventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
            );

            /** @var EventPeriod $newEventPeriod **/
            $newEventPeriod = new EventPeriod();

            $newEventPeriod->setPeriodStart(new DateTimeValue($periodStart));
            $newEventPeriod->setPeriodEnd(new DateTimeValue($periodEnd));

            $clonedPeriods->addItem($newEventPeriod);
        }

        return $clonedPeriods;
    }

    /**
     * @param Event      $followingEvent
     * @param Event      $originEvent
     * @param Collection $clonedOriginEventPeriods
     *
     * @return void
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function buildFollowingEvent($followingEvent, $originEvent, $clonedOriginEventPeriods)
    {
        $followingEvent->setName($originEvent->getName());
        $followingEvent->setPrice($originEvent->getPrice());
        $followingEvent->setMaxCapacity($originEvent->getMaxCapacity());
        $followingEvent->setTags($originEvent->getTags());
        $followingEvent->setProviders($originEvent->getProviders());

        $followingEventGallery = new Collection();

        /** @var GalleryImage $image **/
        foreach ($originEvent->getGallery()->getItems() as $image) {
            $followingEventGallery->addItem(
                GalleryImageFactory::create([
                    'id'               => null,
                    'entityId'         => $followingEvent->getId() ? $followingEvent->getId()->getValue() : null,
                    'entityType'       => $image->getEntityType()->getValue(),
                    'pictureFullPath'  => $image->getPicture()->getFullPath(),
                    'pictureThumbPath' => $image->getPicture()->getThumbPath(),
                    'position'         => $image->getPosition()->getValue(),
                ])
            );
        }

        $followingEvent->setGallery($followingEventGallery);

        if ($originEvent->getSettings()) {
            $followingEvent->setSettings($originEvent->getSettings());
        }

        $followingEvent->setBookingOpens($originEvent->getBookingOpens());

        $followingEvent->setBookingCloses($originEvent->getBookingCloses());

        if ($originEvent->getLocationId()) {
            $followingEvent->setLocationId($originEvent->getLocationId());
        }

        if ($originEvent->getCustomLocation()) {
            $followingEvent->setCustomLocation($originEvent->getCustomLocation());
        }

        if ($originEvent->getTags()) {
            $followingEvent->setTags($originEvent->getTags());
        }

        if ($originEvent->getDescription()) {
            $followingEvent->setDescription($originEvent->getDescription());
        }

        if ($originEvent->getColor()) {
            $followingEvent->setColor($originEvent->getColor());
        }

        if ($originEvent->getShow()) {
            $followingEvent->setShow($originEvent->getShow());
        }

        $modifyCycle = 'days';
        $modifyBaseValue = 0;

        switch ($originEvent->getRecurring()->getCycle()->getValue()) {
            case (Cycle::WEEKLY):
                $modifyCycle = 'days';
                $modifyBaseValue = 7;
                break;

            case (Cycle::MONTHLY):
                $modifyCycle = 'months';
                $modifyBaseValue = 1;
                break;

            case (Cycle::YEARLY):
                $modifyCycle = 'years';
                $modifyBaseValue = 1;
                break;
        }

        /** @var EventPeriod $followingEventPeriod */
        foreach ($followingEvent->getPeriods()->getItems() as $key => $followingEventPeriod) {
            if ($clonedOriginEventPeriods->keyExists($key)) {
                /** @var EventPeriod $clonedOriginEventPeriod */
                $clonedOriginEventPeriod = $clonedOriginEventPeriods->getItem($key);

                $modifyValue = $modifyBaseValue * ($followingEvent->getRecurring()->getOrder()->getValue() - 1);

                $followingEventPeriod->setPeriodStart(new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject(
                        $clonedOriginEventPeriod->getPeriodStart()->getValue()->format('Y-m-d H:i:s')
                    )->modify("+{$modifyValue} {$modifyCycle}")
                ));
                $followingEventPeriod->setPeriodEnd(new DateTimeValue(
                    DateTimeService::getCustomDateTimeObject(
                        $clonedOriginEventPeriod->getPeriodEnd()->getValue()->format('Y-m-d H:i:s')
                    )->modify("+{$modifyValue} {$modifyCycle}")
                ));
            }
        }
    }
}
