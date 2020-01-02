<?php

namespace AmeliaBooking\Infrastructure\Repository\Notification;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\Notification\Notification;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Factory\Booking\Event\EventFactory;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsToEventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsPeriodsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsProvidersTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;

/**
 * Class NotificationRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Notification
 */
class NotificationLogRepository extends AbstractRepository
{

    /** @var string */
    protected $notificationsTable;

    /** @var string */
    protected $appointmentsTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $usersTable;

    /**
     * NotificationLogRepository constructor.
     *
     * @param Connection $connection
     * @param string     $table
     * @param string     $notificationsTable
     * @param string     $appointmentsTable
     * @param string     $bookingsTable
     * @param string     $usersTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $notificationsTable,
        $appointmentsTable,
        $bookingsTable,
        $usersTable
    ) {
        parent::__construct($connection, $table);
        $this->notificationsTable = $notificationsTable;
        $this->appointmentsTable = $appointmentsTable;
        $this->bookingsTable = $bookingsTable;
        $this->usersTable = $usersTable;
    }

    /**
     * @param Notification $notification
     * @param AbstractUser $user
     * @param int|null     $appointmentId
     * @param int|null     $eventId
     *
     * @return bool|mixed
     *
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function add($notification, $user, $appointmentId = null, $eventId = null)
    {
        $notificationData = $notification->toArray();
        $userData = $user->toArray();

        $params = [
            ':notificationId' => $notificationData['id'],
            ':userId'         => $userData['id'],
            ':appointmentId'  => $appointmentId,
            ':eventId'        => $eventId,
            ':sentDateTime'   => DateTimeService::getNowDateTimeInUtc()
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (`notificationId`, `userId`, `appointmentId`, `eventId`, `sentDateTime`)
                VALUES (:notificationId, :userId, :appointmentId, :eventId, :sentDateTime)"
            );

            $res = $statement->execute($params);
            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }
    }

    /**
     * Return a collection of tomorrow appointments where customer notification is not sent and should be.
     *
     * @param $notificationType
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getCustomersNextDayAppointments($notificationType)
    {
        $startCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                WHERE a.bookingStart BETWEEN $startCurrentDate AND $endCurrentDate
                AND cb.status = 'approved'
                AND a.notifyParticipants = 1 AND
                a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'customer_appointment_next_day_reminder' AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow events where customer notification is not sent and should be.
     *
     * @param $notificationType
     *
     * @return Collection
     *
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getCustomersNextDayEvents($notificationType)
    {
        $eventsTable = EventsTable::getTableName();
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();

        $startCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                WHERE ep.periodStart BETWEEN {$startCurrentDate} AND {$endCurrentDate}
                AND cb.status = 'approved'
                AND e.notifyParticipants = 1 AND
                e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'customer_event_next_day_reminder' AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow appointments where provider notification is not sent and should be.
     *
     * @param $notificationType
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getProvidersNextDayAppointments($notificationType)
    {
        $startCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.persons AS booking_persons
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                WHERE a.bookingStart BETWEEN $startCurrentDate AND $endCurrentDate
                AND cb.status = 'approved' 
                AND a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'provider_appointment_next_day_reminder' AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of tomorrow events where provider notification is not sent and should be.
     *
     * @param $notificationType
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getProvidersNextDayEvents($notificationType)
    {
        $eventsTable = EventsTable::getTableName();
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();
        $eventsProvidersTable = EventsProvidersTable::getTableName();

        $startCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(0, 0, 0)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        $endCurrentDate = "STR_TO_DATE('" .
            DateTimeService::getCustomDateTimeObjectInUtc(
                DateTimeService::getNowDateTimeObject()->setTime(23, 59, 59)->format('Y-m-d H:i:s')
            )->modify('+1 day')->format('Y-m-d H:i:s') . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    
                    pu.id AS provider_id,
                    pu.firstName AS provider_firstName,
                    pu.lastName AS provider_lastName,
                    pu.email AS provider_email,
                    pu.note AS provider_note,
                    pu.phone AS provider_phone,
                    pu.gender AS provider_gender,
                    pu.pictureFullPath AS provider_pictureFullPath,
                    pu.pictureThumbPath AS provider_pictureThumbPath,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.customFields AS booking_customFields,
                    cb.persons AS booking_persons
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$this->usersTable} pu ON pu.id = epr.userId
                WHERE ep.periodStart BETWEEN {$startCurrentDate} AND {$endCurrentDate}
                AND cb.status = 'approved' 
                AND e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'provider_event_next_day_reminder' AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find events in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Return a collection of today's past appointments where follow up notification is not sent and should be.
     *
     * @param Notification $notification
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getFollowUpAppointments($notification)
    {
        try {
            $notificationType = $notification->getType()->getValue();

            $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

            $statement = $this->connection->query(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons
                FROM {$this->appointmentsTable} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                WHERE a.bookingEnd BETWEEN DATE_SUB({$currentDateTime}, INTERVAL 172800 SECOND) AND {$currentDateTime}
                AND DATE_ADD(a.bookingEnd, INTERVAL {$notification->getTimeAfter()->getValue()} SECOND)
                  < {$currentDateTime}
                AND a.notifyParticipants = 1 
                AND cb.status = 'approved' 
                AND a.id NOT IN (
                    SELECT nl.appointmentId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'customer_appointment_follow_up' 
                    AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return AppointmentFactory::createCollection($rows);
    }

    /**
     * Return a collection of today's past appointments where follow up notification is not sent and should be.
     *
     * @param Notification $notification
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getFollowUpEvents($notification)
    {
        $eventsTable = EventsTable::getTableName();
        $eventsPeriodsTable = EventsPeriodsTable::getTableName();
        $customerBookingsEventsPeriods = CustomerBookingsToEventsPeriodsTable::getTableName();
        $eventsProvidersTable = EventsProvidersTable::getTableName();

        try {
            $notificationType = $notification->getType()->getValue();

            $statement = $this->connection->query(
                "SELECT
                    e.id AS event_id,
                    e.name AS event_name,
                    e.status AS event_status,
                    e.bookingOpens AS event_bookingOpens,
                    e.bookingCloses AS event_bookingCloses,
                    e.recurringCycle AS event_recurringCycle,
                    e.recurringOrder AS event_recurringOrder,
                    e.recurringUntil AS event_recurringUntil,
                    e.maxCapacity AS event_maxCapacity,
                    e.price AS event_price,
                    e.description AS event_description,
                    e.color AS event_color,
                    e.show AS event_show,
                    e.locationId AS event_locationId,
                    e.customLocation AS event_customLocation,
                    e.parentId AS event_parentId,
                    e.created AS event_created,
                    e.notifyParticipants AS event_notifyParticipants,
                    
                    ep.id AS event_periodId,
                    ep.periodStart AS event_periodStart,
                    ep.periodEnd AS event_periodEnd,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.persons AS booking_persons
                FROM {$eventsTable} e
                INNER JOIN {$eventsPeriodsTable} ep ON ep.eventId = e.id
                INNER JOIN {$customerBookingsEventsPeriods} cbe ON cbe.eventPeriodId = ep.id
                INNER JOIN {$this->bookingsTable} cb ON cb.id = cbe.customerBookingId
                LEFT JOIN {$eventsProvidersTable} epr ON epr.eventId = e.id
                LEFT JOIN {$this->usersTable} pu ON pu.id = epr.userId
                WHERE e.notifyParticipants = 1 
                AND cb.status = 'approved' 
                AND e.id NOT IN (
                    SELECT nl.eventId 
                    FROM {$this->table} nl 
                    INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                    WHERE n.name = 'customer_event_follow_up' 
                    AND n.type = '{$notificationType}'
                )"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find events in ' . __CLASS__, $e->getCode(), $e);
        }

        return EventFactory::createCollection($rows);
    }

    /**
     * Returns a collection of customers that have birthday on today's date and where notification is not sent
     *
     * @param $notificationType
     *
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getBirthdayCustomers($notificationType)
    {
        $currentDate = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d')";

        $params = [
            ':type'          => AbstractUser::USER_ROLE_CUSTOMER,
            ':statusVisible' => Status::VISIBLE,
        ];

        try {
            $statement = $this->connection->prepare(
                "SELECT * FROM {$this->usersTable} as u 
                WHERE 
                u.type = :type AND
                u.status = :statusVisible AND
                MONTH(birthday) = MONTH({$currentDate}) AND
                DAY(u.birthday) = DAY({$currentDate}) AND 
                u.id NOT IN (
                  SELECT nl.userID 
                  FROM {$this->table} nl 
                  INNER JOIN {$this->notificationsTable} n ON nl.notificationId = n.id 
                  WHERE n.name = 'customer_birthday_greeting' AND n.type = '{$notificationType}'
                )"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([UserFactory::class, 'create'], $row);
        }

        return new Collection($items);
    }
}
