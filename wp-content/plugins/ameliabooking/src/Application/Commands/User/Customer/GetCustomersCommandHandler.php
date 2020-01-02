<?php

namespace AmeliaBooking\Application\Commands\User\Customer;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\User\CustomerRepository;

/**
 * Class GetCustomersCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\User\Customer
 */
class GetCustomersCommandHandler extends CommandHandler
{
    /**
     * @param GetCustomersCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws AccessDeniedException
     */
    public function handle(GetCustomersCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::CUSTOMERS)) {
            throw new AccessDeniedException('You are not allowed to read customers.');
        }

        $result = new CommandResult();

        /** @var CustomerRepository $customerRepository */
        $customerRepository = $this->getContainer()->get('domain.users.customers.repository');

        /** @var SettingsService $settingsService */
        $settingsService = $this->container->get('domain.settings.service');
        $itemsPerPage = $settingsService->getSetting('general', 'itemsPerPage');

        $users = $customerRepository->getFiltered($command->getField('params'), $itemsPerPage);

        foreach ($users as &$user) {
            $user['wpUserPhotoUrl'] = $this->container->get('user.avatar')->getAvatar($user['externalId']);

            $user = array_map(function ($v) {
                return (null === $v) ? '' : $v;
            }, $user);
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved users.');
        $result->setData([
            Entities::USER . 's' => $users,
            'filteredCount'      => (int)$customerRepository->getCount($command->getField('params')),
            'totalCount'         => (int)$customerRepository->getCount([])
        ]);

        return $result;
    }
}
