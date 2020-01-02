<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Common\Exceptions\BookingCancellationException;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\Reservation\ReservationServiceInterface;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\Translations\BackendStrings;

/**
 * Class CancelBookingCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class CancelBookingCommandHandler extends CommandHandler
{
    /**
     * @param CancelBookingCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     * @throws AccessDeniedException
     * @throws \Interop\Container\Exception\ContainerException
     * @throws NotFoundException
     */
    public function handle(CancelBookingCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanWriteStatus(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to update booking status');
        }

        $result = new CommandResult();

        $type = $command->getField('type') ?: Entities::APPOINTMENT;

        /** @var ReservationServiceInterface $reservationService */
        $reservationService = $this->container->get('application.reservation.service')->get($type);

        try {
            $bookingData = $reservationService->updateStatus(
                (int)$command->getArg('id'),
                BookingStatus::CANCELED,
                null
            );

            $result->setResult(CommandResult::RESULT_SUCCESS);
            $result->setMessage('Successfully updated booking status');
            $result->setData(array_merge(
                $bookingData,
                [
                    'type'    => $type,
                    'status'  => BookingStatus::CANCELED,
                    'message' =>
                        BackendStrings::getAppointmentStrings()['appointment_status_changed'] . BookingStatus::CANCELED
                ]
            ));
        } catch (BookingCancellationException $e) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('You are not allowed to update booking status');
            $result->setData([
                'cancelBookingUnavailable' => true
            ]);
        }

        return $result;
    }
}
