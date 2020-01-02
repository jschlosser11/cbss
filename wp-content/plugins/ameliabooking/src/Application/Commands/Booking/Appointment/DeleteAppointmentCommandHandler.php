<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\CustomField\CustomFieldApplicationService;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Application\Services\Booking\AppointmentApplicationService;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\ValueObjects\BooleanValueObject;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class DeleteAppointmentCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class DeleteAppointmentCommandHandler extends CommandHandler
{
    /**
     * @param DeleteAppointmentCommand $command
     *
     * @return CommandResult
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws QueryExecutionException
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(DeleteAppointmentCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanDelete(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to delete appointment');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');

        /** @var AppointmentApplicationService $appointmentApplicationService */
        $appointmentApplicationService = $this->container->get('application.booking.appointment.service');

        /** @var CustomFieldApplicationService $customFieldService */
        $customFieldService = $this->container->get('application.customField.service');

        /** @var Appointment $appointment */
        $appointment = $appointmentRepository->getById($command->getArg('id'));

        if (!$appointment instanceof Appointment) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete appointment');

            return $result;
        }

        $appointmentRepository->beginTransaction();

        if (!$appointmentApplicationService->delete($appointment)) {
            $appointmentRepository->rollback();

            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not delete appointment');

            return $result;
        }

        // Set status to rejected, to send the notification that appointment is rejected
        $appointment->setStatus(new BookingStatus(BookingStatus::REJECTED));

        /** @var CustomerBooking $customerBooking */
        foreach ($appointment->getBookings()->getItems() as $customerBooking) {
            $bookingStatus = $customerBooking->getStatus()->getValue();

            if ($bookingStatus === BookingStatus::PENDING || $bookingStatus === BookingStatus::APPROVED) {
                $customerBooking->setChangedStatus(new BooleanValueObject(true));
            }

            $customerBooking->setStatus(new BookingStatus(BookingStatus::REJECTED));
        }

        $appointmentRepository->commit();

        $customFieldService->deleteUploadedFilesForDeletedBookings(
            new Collection(),
            $appointment->getBookings()
        );

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully deleted appointment');
        $result->setData([
            Entities::APPOINTMENT => $appointment->toArray()
        ]);

        return $result;
    }
}
