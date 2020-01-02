<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\User\UserApplicationService;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Infrastructure\Repository\User\UserRepository;

/**
 * Class UpdateCustomerCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class UpdateCustomerCommandHandler extends CommandHandler
{
    /**
     * @param UpdateCustomerCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(UpdateCustomerCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to update customer.');
        }

        $result = new CommandResult();
        $this->checkMandatoryFields($command);

        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get('domain.users.repository');
        $userId = $command->getArg('id');

        $oldUser = $userRepository->getById($userId);

        $command->setField('id', $userId);

        $newUser = UserFactory::create($command->getFields());

        if (!$newUser instanceof AbstractUser) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not update user.');

            return $result;
        }

        $userRepository->beginTransaction();

        if ($userRepository->getByEmail($newUser->getEmail()->getValue()) &&
            $oldUser->getEmail()->getValue() !== $newUser->getEmail()->getValue()) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Email already exist.');

            return $result;
        }

        if ($userRepository->update($userId, $newUser)) {
            if ($command->getField('externalId') === 0) {
                /** @var UserApplicationService $userAS */
                $userAS = $this->getContainer()->get('application.user.service');

                $userAS->setWpUserIdForNewUser($userId, $newUser);
            }

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated user');
            $result->setData([
                Entities::USER => $newUser->toArray()
            ]);

            $userRepository->commit();

            return $result;
        }

        $userRepository->rollback();

        return $result;
    }
}
