<?php

namespace AmeliaBooking\Application\Commands\Bookable\Service;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Bookable\Service\ServiceRepository;

/**
 * Class UpdateServicesPositionsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Bookable\Category
 */
class UpdateServicesPositionsCommandHandler extends CommandHandler
{
    /**
     * @param UpdateServicesPositionsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws InvalidArgumentException
     */
    public function handle(UpdateServicesPositionsCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWrite(Entities::SERVICES)) {
            throw new AccessDeniedException('You are not allowed to update bookable services positions.');
        }

        $result = new CommandResult();

        $services = [];

        foreach ((array)$command->getFields()['services'] as $service) {
            $service = ServiceFactory::create($service);
            if (!$service instanceof Service) {
                $result->setResult(CommandResult::RESULT_ERROR);
                $result->setMessage('Could not update bookable services positions.');

                return $result;
            }

            $services[] = $service;
        }

        /** @var ServiceRepository $serviceRepository */
        $serviceRepository = $this->container->get('domain.bookable.service.repository');
        foreach ($services as $service) {
            $serviceRepository->update($service->getId()->getValue(), $service);
        }

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get('domain.settings.service');

        $settings = $settingsService->getAllSettingsCategorized();

        $settings['general']['sortingServices'] = $command->getFields()['sorting'];

        $settingsService->setAllSettings($settings);

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully updated bookable services positions.');

        return $result;
    }
}
