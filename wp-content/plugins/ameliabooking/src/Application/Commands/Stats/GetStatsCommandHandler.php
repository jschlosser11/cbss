<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Application\Commands\Stats;

use AmeliaBooking\Application\Commands\CommandHandler;
use AmeliaBooking\Application\Commands\CommandResult;
use AmeliaBooking\Application\Common\Exceptions\AccessDeniedException;
use AmeliaBooking\Application\Services\Stats\StatsService;
use AmeliaBooking\Domain\Collection\AbstractCollection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Entity\Entities;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\Services\Settings\SettingsService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\Booking\Appointment\AppointmentRepository;

/**
 * Class GetStatsCommandHandler
 *
 * @package AmeliaBooking\Application\Commands\Stats
 */
class GetStatsCommandHandler extends CommandHandler
{
    /**
     * @param GetStatsCommand $command
     *
     * @return CommandResult
     * @throws \Slim\Exception\ContainerValueNotFoundException
     * @throws AccessDeniedException
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function handle(GetStatsCommand $command)
    {
        if (!$this->getContainer()->getPermissionsService()->currentUserCanRead(Entities::DASHBOARD)) {
            throw new AccessDeniedException('You are not allowed to read coupons.');
        }

        $result = new CommandResult();

        /** @var AppointmentRepository $appointmentRepo */
        $appointmentRepo = $this->container->get('domain.booking.appointment.repository');

        /** @var StatsService $statsAS */
        $statsAS = $this->container->get('application.stats.service');
        /** @var SettingsService $settingsDS */
        $settingsDS = $this->container->get('domain.settings.service');

        $startDate = $command->getField('params')['dates'][0] . ' 00:00:00';
        $endDate = $command->getField('params')['dates'][1] . ' 23:59:59';

        $previousPeriodStart = DateTimeService::getCustomDateTimeObject($startDate);
        $previousPeriodEnd = DateTimeService::getCustomDateTimeObject($endDate);

        $numberOfDays = $previousPeriodEnd->diff($previousPeriodStart)->days + 1;

        $serviceStatsParams = ['dates' => [$startDate, $endDate]];
        $customerStatsParams = ['dates' => [$startDate, $endDate]];
        $locationStatsParams = ['dates' => [$startDate, $endDate]];
        $employeeStatsParams = ['dates' => [$startDate, $endDate]];
        $appointmentStatsParams = ['dates' => [$startDate, $endDate], 'status' => BookingStatus::APPROVED];

        // Statistic
        $selectedPeriodStatistics = $statsAS->getRangeStatisticsData($appointmentStatsParams);
        $previousPeriodStatistics = $statsAS->getRangeStatisticsData(
            array_merge($appointmentStatsParams, [
                'dates' => [
                    $previousPeriodStart->modify("-{$numberOfDays} day")->format('Y-m-d H:i:s'),
                    $previousPeriodEnd->modify("-{$numberOfDays} day")->format('Y-m-d H:i:s'),
                ]
            ])
        );

        // Charts
        $customersStats = $statsAS->getCustomersStats($customerStatsParams);

        $employeesStats = $statsAS->getEmployeesStats($employeeStatsParams);

        $servicesStats = $statsAS->getServicesStats($serviceStatsParams);

        $locationsStats = $statsAS->getLocationsStats($locationStatsParams);

        // Today Appointments
        $upcomingAppointments = $appointmentRepo->getFiltered(['dates' => [
            DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
        ]
        ]);

        if (!$upcomingAppointments instanceof AbstractCollection) {
            $result->setResult(CommandResult::RESULT_ERROR);
            $result->setMessage('Could not get appointments');

            return $result;
        }

        // Get general settings
        $generalSettings = $settingsDS->getCategorySettings('general');

        // Get current date time object
        $currentDateTime = DateTimeService::getNowDateTimeObject();

        $upcomingAppointmentsArr = [];

        $todayApprovedAppointmentsCount = 0;
        $todayPendingAppointmentsCount = 0;

        $todayDateString = explode(' ', DateTimeService::getNowDateTime())[0];

        foreach ($upcomingAppointments->keys() as $appointmentKey) {
            /** @var Appointment $appointment */
            $appointment = $upcomingAppointments->getItem($appointmentKey);

            if ($appointment->getBookingStart()->getValue()->format('Y-m-d') === $todayDateString) {
                if ($appointment->getStatus()->getValue() === BookingStatus::APPROVED) {
                    $todayApprovedAppointmentsCount++;
                }

                if ($appointment->getStatus()->getValue() === BookingStatus::PENDING) {
                    $todayPendingAppointmentsCount++;
                }
            }

            $minimumCancelTime = DateTimeService::getCustomDateTimeObject(
                $appointment->getBookingStart()->getValue()->format('Y-m-d H:i:s')
            )->modify("-{$generalSettings['minimumTimeRequirementPriorToCanceling']} seconds");

            $upcomingAppointmentsArr[] = array_merge(
                $appointment->toArray(),
                [
                    'cancelable' => $currentDateTime <= $minimumCancelTime,
                    'past'       => $currentDateTime >= $appointment->getBookingStart()->getValue()
                ]
            );
        }

        $result->setResult(CommandResult::RESULT_SUCCESS);
        $result->setMessage('Successfully retrieved coupons.');
        $result->setData(
            [
                'count'                => [
                    'approved' => $todayApprovedAppointmentsCount,
                    'pending'  => $todayPendingAppointmentsCount,
                ],
                'selectedPeriodStats'  => $selectedPeriodStatistics,
                'previousPeriodStats'  => $previousPeriodStatistics,
                'employeesStats'       => $employeesStats,
                'servicesStats'        => $servicesStats,
                'locationsStats'       => $locationsStats,
                'customersStats'       => $customersStats,
                Entities::APPOINTMENTS => array_slice($upcomingAppointmentsArr, 0, 10),
                'appointmentsCount'    => 10
            ]
        );

        return $result;
    }
}
