<?php

namespace AmeliaBooking\Application\Commands\Notification;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Factory\Notification\NotificationFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Notification\NotificationRepository;

/**
 * Class UpdateNotificationStatusCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Notification
 */
class UpdateNotificationStatusCommandHandler extends CommandHandler
{
    public $mandatoryFields = [
        'status'
    ];

    /**
     * @param UpdateNotificationStatusCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateNotificationStatusCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::NOTIFICATIONS)) {
            throw new AccessDeniedException('You are not allowed to update notification');
        }

        $notificationId = (int)$command->getArg('id');

        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var NotificationRepository $notificationRepo */
        $notificationRepo = $this->container->get('domain.notification.repository');

        $currentNotification = $notificationRepo->getById($notificationId);

        $notification = NotificationFactory::create([
            'name'       => $currentNotification->getName()->getValue(),
            'status'     => $command->getField('status'),
            'type'       => $currentNotification->getType()->getValue(),
            'sendTo'     => $currentNotification->getSendTo()->getValue(),
            'subject'    => $currentNotification->getSubject()->getValue(),
            'content'    => $currentNotification->getContent()->getValue(),
            'time'       => $currentNotification->getTime() ? $currentNotification->getTime()->getValue() : null,
            'entity'     => $command->getField('entity'),
            'timeBefore' => $currentNotification->getTimeBefore() ?
                $currentNotification->getTimeBefore()->getValue() : null,
            'timeAfter'  => $currentNotification->getTimeAfter() ?
                $currentNotification->getTimeAfter()->getValue() : null
        ]);

        if (!$notification instanceof Notification) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update notification entity.');

            return $result;
        }

        if ($notificationRepo->update($notificationId, $notification)) {
            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated notification.');
            $result->setData([
                Entities::NOTIFICATION => $notification->toArray()
            ]);
        }

        return $result;
    }
}
