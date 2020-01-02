<?php

namespace AmeliaBooking\Application\Controller\Entities;

use AmeliaBooking\Application\Commands\Entities\GetEntitiesCommand;
use AmeliaBooking\Application\Controller\Controller;
use Slim\Http\Request;

/**
 * Class GetEntitiesController
 *
 * @package AmeliaBooking\Application\Controller\Entities
 */
class GetEntitiesController extends Controller
{
    /**
     * Instantiates the Get Entities command to hand it over to the Command Handler
     *
     * @param Request $request
     * @param         $args
     *
     * @return GetEntitiesCommand
     * @throws \RuntimeException
     */
    protected function instantiateCommand(Request $request, $args)
    {
        $command = new GetEntitiesCommand($args);
        $command->setField('params', (array)$request->getQueryParams());
        $requestBody = $request->getParsedBody();
        $this->setCommandFields($command, $requestBody);

        return $command;
    }
}
