<?php

namespace AmeliaBooking\Application\Services\Reservation;

use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Infrastructure\Common\Container;

/**
 * Class ReservationService
 *
 * @package AmeliaBooking\Application\Services\Reservation
 */
class ReservationService
{
    protected $container;

    /**
     * AbstractReservationService constructor.
     *
     * @param Container $container
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $type
     * @return ReservationServiceInterface
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function get($type) {
        switch ($type) {
            case (Entities::APPOINTMENT):
                /** @var ReservationServiceInterface $appointmentReservationService */
                $appointmentReservationService = $this->container->get('application.reservation.appointment.service');

                return $appointmentReservationService;

            case (Entities::EVENT):
                /** @var ReservationServiceInterface $eventReservationService */
                $eventReservationService = $this->container->get('application.reservation.event.service');

                return $eventReservationService;
        }
    }
}
