<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

/**
 * Class GetIcsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetIcsCommandHandler extends CommandHandler
{
    /**
     * @param GetIcsCommand $command
     *
     * @return CommandResult
     * @throws \UnexpectedValueException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws InvalidArgumentException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException
     */
    public function handle(GetIcsCommand $command)
    {
        $result = new CommandResult();

        $type = $command->getField('params')['type'] ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        /** @var Appointment|Event $reservation */
        $reservation = $reservationService->getReservationByBookingId((int)$command->getArg('id'));

        /** @var CustomerBooking $booking */
        $booking = $reservation->getBookings()->getItem((int)$command->getArg('id'));

        /** @var Service|Event $reservation */
        $bookable = null;

        switch ($type) {
            case Entities::APPOINTMENT:
                /** @var Service $bookable */
                $bookable = $reservationService->getBookableEntity([
                    'serviceId' => $reservation->getServiceId()->getValue(),
                    'providerId' => $reservation->getProviderId()->getValue()
                ]);

                break;

            case Entities::EVENT:
                /** @var Event $bookable */
                $bookable = $reservationService->getBookableEntity([
                    'eventId' => $reservation->getId()->getValue()
                ]);

                break;
        }

        $periods = $reservationService->getBookingPeriods($reservation, $booking, $bookable);

        $vCalendar = new Calendar(AMELIA_URL);

        foreach ($periods as $period) {
            $vEvent = new Event();

            $vEvent
                ->setDtStart(new \DateTime($period['start'], new \DateTimeZone('UTC')))
                ->setDtEnd(new \DateTime($period['end'], new \DateTimeZone('UTC')))
                ->setSummary($bookable->getName()->getValue());

            $vCalendar->addComponent($vEvent);
        }

        $result->setAttachment(true);

        $result->setFile([
            'name'    => 'cal.ics',
            'type'    => 'text/calendar; charset=utf-8',
            'content' => $vCalendar->render()
        ]);

        return $result;
    }
}
