<?php

namespace AmeliaBooking\Application\Services\Location;

use AmeliaBooking\Domain\Entity\Location\Location;
use AmeliaBooking\Infrastructure\Common\Container;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;
use AmeliaBooking\Infrastructure\Repository\Booking\Event\EventRepository;
use AmeliaBooking\Infrastructure\Repository\Location\LocationRepository;
use AmeliaBooking\Infrastructure\Repository\Location\ProviderLocationRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\PeriodRepository;
use AmeliaBooking\Infrastructure\Repository\Schedule\SpecialDayPeriodRepository;

/**
 * Class LocationApplicationService
 *
 * @package AmeliaBooking\Application\Services\Location
 */
class LocationApplicationService
{

    private $container;

    /**
     * LocationApplicationService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     *
     * @param Location $location
     *
     * @return boolean
     *
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     */
    public function delete($location)
    {
        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->container->get('domain.booking.event.repository');

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->container->get('domain.locations.repository');

        /** @var PeriodRepository $periodRepository */
        $periodRepository = $this->container->get('domain.schedule.period.repository');

        /** @var SpecialDayPeriodRepository $specialDayPeriodRepository */
        $specialDayPeriodRepository = $this->container->get('domain.schedule.specialDay.period.repository');

        /** @var ProviderLocationRepository $providerLocationRepo */
        $providerLocationRepo = $this->container->get('domain.bookable.service.providerLocation.repository');

        return $eventRepository->unsetByEntityId($location->getId()->getValue(), 'locationId') &&
            $appointmentRepository->unsetByEntityId($location->getId()->getValue(), 'locationId') &&
            $periodRepository->unsetByEntityId($location->getId()->getValue(), 'locationId') &&
            $specialDayPeriodRepository->unsetByEntityId($location->getId()->getValue(), 'locationId') &&
            $providerLocationRepo->deleteByEntityId($location->getId()->getValue(), 'locationId') &&
            $locationRepository->deleteViewStats($location->getId()->getValue()) &&
            $locationRepository->delete($location->getId()->getValue());
    }
}
