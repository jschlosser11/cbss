<?php

namespace AmeliaBooking\Application\Controller\User\Provider;

use AmeliaBooking\Application\Commands\User\Provider\UpdateProviderCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateProviderController
 *
 * @package AmeliaBooking\Application\Controller\User
 */
class UpdateProviderController extends Controller
{
    /**
     * Fields for provider that can be received from front-end
     *
     * @var array
     */
    protected $allowedFields = [
        'type',
        'firstName',
        'lastName',
        'birthday',
        'email',
        'externalId',
        'locationId',
        'avatar',
        'phone',
        'note',
        'gender',
        'serviceList',
        'weekDayList',
        'specialDayList',
        'timeOutList',
        'periodList',
        'dayOffList',
        'pictureFullPath',
        'pictureThumbPath',
        'googleCalendar'
    ];

    /**
     * Instantiates the Update Provider command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateProviderCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateProviderCommand($args);
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }

    /**
     * @param DomainEventBus $eventBus
     * @param CommandResult  $result
     *
     * @return void
     */
    protected function emitSuccessEvent(DomainEventBus $eventBus, CommandResult $result)
    {
        $eventBus->emit('provider.updated', $result);
    }
}
