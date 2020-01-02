<?php

namespace AmeliaBooking\Application\Commands\User;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Domain\Entity\User\AbstractUser;

/**
 * Class GetCurrentUserCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User
 */
class GetCurrentUserCommandHandler extends CommandHandler
{
    /**
     * @param GetCurrentUserCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetCurrentUserCommand $command)
    {
        $result = new CommandResult();

        $this->checkMandatoryFields($command);

        /** @var AbstractUser $user */
        $user = $this->getContainer()->get('logged.in.user');

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved current user');
        $result->setData([
            Entities::USER => $user ? $user->toArray() : null
        ]);

        return $result;
    }
}
