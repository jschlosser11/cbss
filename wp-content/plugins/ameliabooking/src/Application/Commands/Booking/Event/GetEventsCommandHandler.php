<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Booking\Event\EventPeriod;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;

/**
 * Class GetEventsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventsCommandHandler extends CommandHandler
{
    /**
     * @param GetEventsCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetEventsCommand $command)
    {
        $result = new CommandResult();

        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $itemsPerPage = $settingsDS->getSetting('general', 'itemsPerPage');

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get(Entities::EVENT);

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        $params = $command->getField('params');

        $isFrontEnd = isset($params['page']);

        /** @var AbstractUser $currentUser */
        $currentUser = null;

        $isCustomer = true;
        $isLoggedInCustomer = false;
        $isProvider = false;

        if (!$isFrontEnd) {
            $currentUser = $this->container->get('logged.in.user');

            $isCustomer = $currentUser === null ||
                ($currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER);

            $isLoggedInCustomer = $currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_CUSTOMER;

            $isProvider = $currentUser && $currentUser->getType() === AbstractUser::USER_ROLE_PROVIDER;

            if ($isProvider) {
                $params['providers'] = [$currentUser->getId()->getValue()];
            }
        }

        if (isset($params['dates'][0])) {
            $params['dates'][0] ? $params['dates'][0] .= ' 00:00:00' : null;
        }

        if (isset($params['dates'][1])) {
            $params['dates'][1] ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        $filteredEventIds = $eventRepository->getFilteredIds($params, $itemsPerPage);

        /** @var Collection $events */
        $events = $filteredEventIds ?
            $eventRepository->getFiltered(array_merge($params, ['ids' => array_column($filteredEventIds, 'id')])) :
            new Collection();

        if (!$events instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get events');

            return $result;
        }

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $eventsArray = [];

        /** @var Event $event */
        foreach ($events->getItems() as $event) {
            if (($isFrontEnd || $isCustomer) && !$event->getShow()->getValue()) {
                continue;
            }

            if (!$isFrontEnd && $isProvider) {
                $isProviderEvent = false;

                /** @var Provider $provider */
                foreach ($event->getProviders()->getItems() as $provider) {
                    if ($provider->getId()->getValue() === $currentUser->getId()->getValue()) {
                        $isProviderEvent = true;
                    }
                }

                if (!$isProviderEvent) {
                    continue;
                }
            }

            $persons = 0;

            /** @var CustomerBooking $booking */
            foreach ($event->getBookings()->getItems() as $booking) {
                if ($booking->getStatus()->getValue() === BookingStatus::APPROVED) {
                    $persons += $booking->getPersons()->getValue();
                }
            }

            if ($isCustomer) {
                if ($isLoggedInCustomer && !$isFrontEnd) {
                    $removeEvent = true;

                    foreach ($event->getBookings()->getItems() as $key => $booking) {
                        if ($booking->getCustomerId()->getValue() === $currentUser->getId()->getValue()) {
                            $removeEvent = false;
                        }
                    }

                    if ($removeEvent) {
                        continue;
                    }
                } else {
                    $event->setBookings(new Collection());
                }
            }

            if ($isFrontEnd && $settingsDS->getSetting('general', 'showClientTimeZone')) {
                /** @var EventPeriod $period */
                foreach ($event->getPeriods()->getItems() as $period) {
                    $period->getPeriodStart()->getValue()->setTimezone(new \DateTimeZone('UTC'));
                    $period->getPeriodEnd()->getValue()->setTimezone(new \DateTimeZone('UTC'));
                }
            }

            $bookingOpens = $event->getBookingOpens() ?
                $event->getBookingOpens()->getValue() : $event->getCreated()->getValue();

            $bookingCloses = $event->getBookingCloses() ?
                $event->getBookingCloses()->getValue() : $event->getPeriods()->getItem(0)->getPeriodStart()->getValue();

            $eventsArray[] = array_merge(
                $event->toArray(),
                [
                    'bookable'   => $reservationService->isBookable($event, null, $currentDateTime),
                    'opened'     => ($currentDateTime > $bookingOpens) && ($currentDateTime < $bookingCloses),
                    'closed'     => $currentDateTime > $bookingCloses,
                    'places'     => $event->getMaxCapacity()->getValue() - $persons
                ]
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved events');
        $result->setData([
            Entities::EVENTS => $eventsArray,
            'count'     => (int)$eventRepository->getFilteredIdsCount($params)
        ]);

        return $result;
    }
}
