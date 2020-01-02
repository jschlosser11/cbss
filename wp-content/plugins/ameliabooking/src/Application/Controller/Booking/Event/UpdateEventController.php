<?php

namespace AmeliaBooking\Application\Controller\Booking\Event;

use AmeliaBooking\Application\Commands\Booking\Event\UpdateEventCommand;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Controller\Controller;
use AmeliaBooking\Domain\Events\DomainEventBus;
use Slim\Http\Request;

/**
 * Class UpdateEventController
 *
 * @package AmeliaBooking\Application\Controller\Booking\Event
 */
class UpdateEventController extends Controller
{
    /**
     * Fields for event that can be received from front-end
     *
     * @var array
     */
    public $allowedFields = [
        'id',
        'parentId',
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
        'customLocation',
        'settings',
        'applyGlobally'
    ];

    /**
     * Instantiates the Update Event command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return UpdateEventCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new UpdateEventCommand($args);
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
        $eventBus->emit('EventEdited', $result);
    }
}
