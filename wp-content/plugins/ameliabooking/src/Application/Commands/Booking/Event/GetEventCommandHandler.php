<?php

namespace AmeliaBooking\Application\Commands\Booking\Event;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\CustomerApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Event\Event;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;

/**
 * Class GetEventCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Event
 */
class GetEventCommandHandler extends CommandHandler
{
    /**
     * @param GetEventCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function handle(GetEventCommand $command)
    {
        /** @var AbstractUser  $currentUser */
        $currentUser = $this->getContainer()->get('logged.in.user');

        if ($currentUser === null ||
            !$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::EVENTS)) {
            throw new AccessDeniedException('You are not allowed to read events');
        }

        $result = new CommandResult();

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var Event $event */
        $event = $eventRepository->getById((int)$command->getField('id'));

        /** @var CustomerApplicationService $customerAS */
        $customerAS = $this->container->get('application.user.customer.service');

        $customerAS->removeBookingsForOtherCustomers($currentUser, new Collection([$event]));

        if (!$event instanceof Event) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get event');

            return $result;
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved event');
        $result->setData([
            Entities::EVENT => $event->toArray()
        ]);

        return $result;
    }
}
