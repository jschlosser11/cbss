<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\AddEventCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class AddEventController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class AddEventController extends Controller
{
    /**
     * Fields for appointment that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'name',
        'periods',
        'bookingOpens',
        'bookingCloses',
        'recurring',
        'maxCapacity',
        'price',
        'providers',
        'tags',
        'description',
        'gallery',
        'color',
        'show',
        'locationId',
        'settings',
        'customLocation'
    ];

    /**
     * Instantiates the Add Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return AddEventCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new AddEventCommand($args);
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
        $eventBus->emit('EventAdded', $result);
    }
}
