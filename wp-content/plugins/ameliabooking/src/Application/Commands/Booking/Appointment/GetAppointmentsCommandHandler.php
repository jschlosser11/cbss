<?php

namespace AmeliaBooking\Application\Commands\Booking\Appointment;

use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Booking\Appointment\CustomerBooking;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class GetAppointmentsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Booking\Appointment
 */
class GetAppointmentsCommandHandler extends CommandHandler
{
    /**
     * @param GetAppointmentsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetAppointmentsCommand $command)
    {
        if ($this->getContainer()->get('logged.in.user') === null ||
            !$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::APPOINTMENTS)) {
            throw new AccessDeniedException('You are not allowed to read appointments');
        }

        $result = new CommandResult();

        /** @var AbstractUser $user */
        $user = $this->container->get('logged.in.user');

        $userCanReadOthers = $this->container->getPermissionsService()->currentUserCanReadOthers(Entities::APPOINTMENTS);
        $userIsCustomer = !$userCanReadOthers && $user->getType() === Entities::CUSTOMER;
        $userIsProvider = !$userCanReadOthers && $user->getType() === Entities::PROVIDER;
        $userId = $user->getId() === null ? 0 : $user->getId()->getValue();

        /** @var AppointmentRepository $appointmentRepository */
        $appointmentRepository = $this->container->get('domain.booking.appointment.repository');
        /** @var SettingsService $settingsDomainService */
        $settingsDomainService = $this->container->get('domain.settings.service');

        // Get general settings
        $generalSettings = $settingsDomainService->getCategorySettings('general');

        $params = $command->getField('params');

        if ($params['dates']) {
            $params['dates'][0] ? $params['dates'][0] .= ' 00:00:00' : null;
            $params['dates'][1] ? $params['dates'][1] .= ' 23:59:59' : null;
        }

        /** @var Collection $appointments */
        $appointments = $appointmentRepository->getFiltered($params);

        $occupiedTimes = [];

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointmentKey => $appointment) {
            // remove appointments/bookings for other customers if user is customer
            if ($userIsCustomer) {
                /** @var CustomerBooking $booking */
                foreach ($appointment->getBookings()->getItems() as $bookingKey => $booking) {
                    if ($booking->getCustomerId()->getValue() !== $userId) {
                        $appointment->getBookings()->deleteItem($bookingKey);
                    }
                }

                if ($appointment->getBookings()->length() === 0) {
                    $serviceTimeBefore = $appointment->getService()->getTimeBefore() ?
                        $appointment->getService()->getTimeBefore()->getValue() : 0;

                    $serviceTimeAfter = $appointment->getService()->getTimeAfter() ?
                        $appointment->getService()->getTimeAfter()->getValue() : 0;

                    $occupiedTimeStart = DateTimeService::getCustomDateTimeObject(
                        $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
                    )->modify('-' . $serviceTimeBefore . ' second')->format('H:i:s');

                    $occupiedTimeEnd = DateTimeService::getCustomDateTimeObject(
                        $appointment->getBookingEnd()->getValue()->format('Y-m-d H:i:s')
                    )->modify('+' . $serviceTimeAfter . ' second')->format('H:i:s');

                    $occupiedTimes[$appointment->getBookingStart()->getValue()->format('Y-m-d')][] =
                        [
                            'employeeId' => $appointment->getProviderId()->getValue(),
                            'startTime' => $occupiedTimeStart,
                            'endTime' => $occupiedTimeEnd,
                        ];

                    $appointments->deleteItem($appointmentKey);
                }
            }

            // remove appointments for other providers if user is provider
            if ($userIsProvider && $appointment->getProviderId()->getValue() !== $userId) {
                $appointments->deleteItem($appointmentKey);
            }
        }

        /** @var Appointment $appointment */
        foreach ($appointments->getItems() as $appointment) {
            /** @var CustomerBooking $booking */
            foreach ($appointment->getBookings()->getItems() as $booking) {
                if ($booking->getCustomFields() && json_decode($booking->getCustomFields()->getValue(), true) === null) {
                    $booking->setCustomFields(null);
                }
            }
        }

        if (!$appointments instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointments');

            return $result;
        }

        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $groupedAppointments = [];

        foreach ($appointments->keys() as $appointmentKey) {
            /** @var Appointment $appointment */
            $appointment = $appointments->getItem($appointmentKey);

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$generalSettings['minimumTimeRequirementPriorToCanceling']} seconds");

            $date = $appointment->getBookingStart()->getValue()->format('Y-m-d');

            $groupedAppointments[$date]['date'] = $date;
            $groupedAppointments[$date]['appointments'][] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable' => $currentDateTime <= $minimumCancelTime,
                    'past'       => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved appointments');
        $result->setData([
            Entities::APPOINTMENTS => $groupedAppointments,
            'occupied' => $occupiedTimes
        ]);

        return $result;
    }
}
