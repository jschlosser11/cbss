<?php

namespace AmeliaBooking\Infrastructure\Repository\Booking\Appointment;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Entity\Booking\Appointment\Appointment;
use AmeliaBooking\Domain\Factory\Booking\Appointment\AppointmentFactory;
use AmeliaBooking\Domain\Repository\Booking\Appointment\AppointmentRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\Connection;

/**
 * Class AppointmentRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Booking\Appointment
 */
class AppointmentRepository extends AbstractRepository implements AppointmentRepositoryInterface
{

    const FACTORY = AppointmentFactory::class;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $customerBookingsExtrasTable;

    /** @var string */
    protected $extrasTable;

    /** @var string */
    protected $usersTable;

    /** @var string */
    protected $paymentsTable;

    /** @var string */
    protected $couponsTable;

    /** @var string */
    protected $providersLocationTable;

    /** @var string */
    protected $providerServicesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $servicesTable
     * @param string     $bookingsTable
     * @param string     $customerBookingsExtrasTable
     * @param string     $extrasTable
     * @param string     $usersTable
     * @param string     $paymentsTable
     * @param string     $couponsTable
     * @param string     $providersLocationTable
     * @param string     $providerServicesTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $servicesTable,
        $bookingsTable,
        $customerBookingsExtrasTable,
        $extrasTable,
        $usersTable,
        $paymentsTable,
        $couponsTable,
        $providersLocationTable,
        $providerServicesTable
    ) {
        parent::__construct($connection, $table);

        $this->servicesTable = $servicesTable;
        $this->bookingsTable = $bookingsTable;
        $this->customerBookingsExtrasTable = $customerBookingsExtrasTable;
        $this->extrasTable = $extrasTable;
        $this->usersTable = $usersTable;
        $this->paymentsTable = $paymentsTable;
        $this->couponsTable = $couponsTable;
        $this->providersLocationTable = $providersLocationTable;
        $this->providerServicesTable = $providerServicesTable;
    }

    /**
     * @param int $id
     *
     * @return Appointment
     * @throws QueryExecutionException
     */
    public function getById($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.utcOffset AS booking_utcOffset,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$this->paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId
                WHERE a.id = :appointmentId
                ORDER BY a.bookingStart"
            );

            $statement->bindParam(':appointmentId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointment by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($id);
    }

    /**
     * @param int $id
     *
     * @return Appointment
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getByBookingId($id)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.utcOffset AS booking_utcOffset,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    cb.couponId AS booking_couponId,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                LEFT JOIN {$this->paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId
                WHERE a.id = (
                  SELECT cb2.appointmentId FROM {$this->bookingsTable} cb2 WHERE cb2.id = :customerBookingId
                )
                ORDER BY a.bookingStart"
            );

            $statement->bindParam(':customerBookingId', $id);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointment by id in ' . __CLASS__, $e->getCode(), $e);
        }

        /** @var Collection $appointments */
        $appointments = call_user_func([static::FACTORY, 'createCollection'], $rows);

        return $appointments->length() ? $appointments->getItem($appointments->keys()[0]) : null;
    }

    /**
     * @param Appointment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':bookingStart'       => DateTimeService::getCustomDateTimeInUtc($data['bookingStart']),
            ':bookingEnd'         => DateTimeService::getCustomDateTimeInUtc($data['bookingEnd']),
            ':notifyParticipants' => $data['notifyParticipants'],
            ':internalNotes'      => $data['internalNotes'] ?: '',
            ':status'             => $data['status'],
            ':serviceId'          => $data['serviceId'],
            ':providerId'         => $data['providerId'],
            ':locationId'         => $data['locationId'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} 
                (
                `bookingStart`,
                `bookingEnd`,
                `notifyParticipants`,
                `internalNotes`,
                `status`,
                `locationId`,
                `serviceId`,
                `providerId`
                )
                VALUES (
                :bookingStart,
                :bookingEnd,
                :notifyParticipants,
                :internalNotes,
                :status,
                :locationId,
                :serviceId,
                :providerId
                )"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }
    }

    /**
     * @param int         $id
     * @param Appointment $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':id'                    => $id,
            ':bookingStart'          => DateTimeService::getCustomDateTimeInUtc($data['bookingStart']),
            ':bookingEnd'            => DateTimeService::getCustomDateTimeInUtc($data['bookingEnd']),
            ':notifyParticipants'    => $data['notifyParticipants'],
            ':internalNotes'         => $data['internalNotes'],
            ':status'                => $data['status'],
            ':locationId'            => $data['locationId'],
            ':serviceId'             => $data['serviceId'],
            ':providerId'            => $data['providerId'],
            ':googleCalendarEventId' => $data['googleCalendarEventId']
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `bookingStart` = :bookingStart,
                `bookingEnd` = :bookingEnd, 
                `notifyParticipants` = :notifyParticipants,
                `internalNotes` = :internalNotes,
                `status` = :status,
                `locationId` = :locationId,
                `serviceId` = :serviceId,
                `providerId` = :providerId,
                `googleCalendarEventId` = :googleCalendarEventId
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }
    }

    /**
     * @param int $id
     * @param int $status
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function updateStatusById($id, $status)
    {
        $params = [
            ':id'     => $id,
            ':status' => $status
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `status` = :status
                WHERE id = :id"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $res;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }
    }

    /**
     * Returns array of current appointments where keys are Provider ID's
     * and array values are Appointments Data (modified by service padding time)
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getCurrentAppointments()
    {
        try {
            $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

            $statement = $this->connection->query("SELECT
                a.bookingStart AS bookingStart,
                a.bookingEnd AS bookingEnd,
                a.providerId AS providerId,
                a.serviceId AS serviceId,
                s.timeBefore AS timeBefore,
                s.timeAfter AS timeAfter
                FROM {$this->table} a
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                WHERE {$currentDateTime} >= a.bookingStart
                AND {$currentDateTime} <= a.bookingEnd
                ORDER BY a.bookingStart");

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $row['bookingStart'] = DateTimeService::getCustomDateTimeObjectFromUtc($row['bookingStart'])
                ->modify('-' . ($row['timeBefore'] ?: '0') . ' seconds')
                ->format('Y-m-d H:i:s');

            $row['bookingEnd'] = DateTimeService::getCustomDateTimeObjectFromUtc($row['bookingEnd'])
                ->modify('+' . ($row['timeAfter'] ?: '0') . ' seconds')
                ->format('Y-m-d H:i:s');

            $result[$row['providerId']] = $row;
        }

        return $result;
    }

    /**
     * @param array $providerIds
     * @param int   $excludeAppointmentId
     * @param array $statuses
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFutureAppointments($providerIds = [], $excludeAppointmentId = null, $statuses = [])
    {
        $currentDateTime = "STR_TO_DATE('" . DateTimeService::getNowDateTimeInUtc() . "', '%Y-%m-%d %H:%i:%s')";

        try {
            $params = [];

            $where = [];

            if (!empty($statuses)) {
                $queryStatuses = [];

                foreach ((array)$statuses as $index => $value) {
                    $param = ':status' . $index;
                    $queryStatuses[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'cb.status IN (' . implode(', ', $queryStatuses) . ')';
            }

            if (!empty($providerIds)) {
                $queryProviders = [];

                foreach ((array)$providerIds as $index => $value) {
                    $param = ':provider' . $index;
                    $queryProviders[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
            }

            if ($excludeAppointmentId) {
                $where[] = 'a.id != :appointmentId ';
                $params[':appointmentId'] = $excludeAppointmentId;
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare("SELECT
                a.id AS appointment_id,
                a.bookingStart AS appointment_bookingStart,
                a.bookingEnd AS appointment_bookingEnd,
                a.providerId AS appointment_providerId,
                a.serviceId AS appointment_serviceId,
                a.locationId AS appointment_locationId,
                a.status AS appointment_status,
                a.googleCalendarEventId AS appointment_google_calendar_event_id,
                
                s.id AS service_id,
                s.name AS service_name,
                s.description AS service_description,
                s.color AS service_color,
                ps.price AS service_price,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                ps.maxCapacity AS service_maxCapacity,
                ps.minCapacity AS service_minCapacity,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                                
                cb.id AS booking_id,
                cb.customerId AS booking_customerId,
                cb.status AS booking_status,
                cb.price AS booking_price,
                cb.persons AS booking_persons
                
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                INNER JOIN {$this->providerServicesTable} ps ON (ps.serviceId = s.id AND ps.userId = a.providerId)
                AND a.bookingStart >= {$currentDateTime}
                $where ORDER BY a.bookingStart");

            $statement->execute($params);
            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find appointments in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param array $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria)
    {
        try {
            $params = [];
            $where = [];

            if (isset($criteria['search'])) {
                $params[':search1'] = $params[':search2'] = $params[':search3'] = "%{$criteria['search']}%";

                $where[] = "(
                    CONCAT(cu.firstName, ' ', cu.lastName) LIKE :search1
                    OR CONCAT(pu.firstName, ' ', pu.lastName) LIKE :search2
                    OR s.name LIKE :search3
                    )";
            }

            if (!empty($criteria['dates'])) {
                if (isset($criteria['dates'][0], $criteria['dates'][1])) {
                    $where[] = "(DATE_FORMAT(a.bookingStart, '%Y-%m-%d %H:%i:%s') BETWEEN :bookingFrom AND :bookingTo)";
                    $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                    $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
                } elseif (isset($criteria['dates'][0])) {
                    $where[] = "(DATE_FORMAT(a.bookingStart, '%Y-%m-%d %H:%i:%s') >= :bookingFrom)";
                    $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
                } elseif (isset($criteria['dates'][1])) {
                    $where[] = "(DATE_FORMAT(a.bookingStart, '%Y-%m-%d %H:%i:%s') <= :bookingTo)";
                    $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
                } else {
                    $where[] = "(DATE_FORMAT(a.bookingStart, '%Y-%m-%d %H:%i:%s') > :bookingFrom)";
                    $params[':bookingFrom'] = DateTimeService::getNowDateTimeInUtc();
                }
            }

            if (!empty($criteria['services'])) {
                $queryServices = [];

                foreach ((array)$criteria['services'] as $index => $value) {
                    $param = ':service' . $index;
                    $queryServices[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
            }

            if (!empty($criteria['providers'])) {
                $queryProviders = [];

                foreach ((array)$criteria['providers'] as $index => $value) {
                    $param = ':provider' . $index;
                    $queryProviders[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'a.providerId IN (' . implode(', ', $queryProviders) . ')';
            }

            if (isset($criteria['customerId'])) {
                $where[] = 'cb.customerId = :customerId';
                $params[':customerId'] = $criteria['customerId'];
            }

            if (isset($criteria['providerId'])) {
                $where[] = 'a.providerId = :providerId';
                $params[':providerId'] = $criteria['providerId'];
            }

            if (array_key_exists('status', $criteria)) {
                $where[] = 'a.status = :status';
                $params[':status'] = $criteria['status'];
            }

            if (array_key_exists('bookingStatus', $criteria)) {
                $where[] = 'cb.status = :bookingStatus';
                $params[':bookingStatus'] = $criteria['bookingStatus'];
            }

            if (!empty($criteria['locations'])) {
                $queryLocations = [];

                foreach ((array)$criteria['locations'] as $index => $value) {
                    $param = ':location' . $index;
                    $queryLocations[] = $param;
                    $params[$param] = $value;
                }

                $where[] = 'pl.locationId IN (' . implode(', ', $queryLocations) . ')';
            }

            if (isset($criteria['bookingId'])) {
                $where[] = 'cb.id = :bookingId';
                $params[':bookingId'] = $criteria['bookingId'];
            }

            if (isset($criteria['bookingCouponId'])) {
                $where[] = 'cb.couponId = :bookingCouponId';
                $params[':bookingCouponId'] = $criteria['bookingCouponId'];
            }

            $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $statement = $this->connection->prepare(
                "SELECT
                    a.id AS appointment_id,
                    a.bookingStart AS appointment_bookingStart,
                    a.bookingEnd AS appointment_bookingEnd,
                    a.notifyParticipants AS appointment_notifyParticipants,
                    a.internalNotes AS appointment_internalNotes,
                    a.status AS appointment_status,
                    a.serviceId AS appointment_serviceId,
                    a.providerId AS appointment_providerId,
                    a.locationId AS appointment_locationId,
                    a.googleCalendarEventId AS appointment_google_calendar_event_id,
                    
                    cb.id AS booking_id,
                    cb.customerId AS booking_customerId,
                    cb.status AS booking_status,
                    cb.price AS booking_price,
                    cb.persons AS booking_persons,
                    cb.customFields AS booking_customFields,
                    cb.info AS booking_info,
                    cb.aggregatedPrice AS booking_aggregatedPrice,
                    
                    cbe.id AS bookingExtra_id,
                    cbe.extraId AS bookingExtra_extraId,
                    cbe.customerBookingId AS bookingExtra_customerBookingId,
                    cbe.quantity AS bookingExtra_quantity,
                    cbe.price AS bookingExtra_price,
                    cbe.aggregatedPrice AS bookingExtra_aggregatedPrice,
                    
                    cu.id AS customer_id,
                    cu.firstName AS customer_firstName,
                    cu.lastName AS customer_lastName,
                    cu.email AS customer_email,
                    cu.note AS customer_note,
                    cu.phone AS customer_phone,
                    cu.gender AS customer_gender,
                    
                    pu.id AS provider_id,
                    pu.firstName AS provider_firstName,
                    pu.lastName AS provider_lastName,
                    pu.email AS provider_email,
                    pu.note AS provider_note,
                    pu.phone AS provider_phone,
                    pu.gender AS provider_gender,
                    
                    s.id AS service_id,
                    s.name AS service_name,
                    s.description AS service_description,
                    s.color AS service_color,
                    s.price AS service_price,
                    s.status AS service_status,
                    s.categoryId AS service_categoryId,
                    s.minCapacity AS service_minCapacity,
                    s.maxCapacity AS service_maxCapacity,
                    s.timeAfter AS service_timeAfter,
                    s.timeBefore AS service_timeBefore,
                    s.duration AS service_duration,
                    
                    p.id AS payment_id,
                    p.amount AS payment_amount,
                    p.dateTime AS payment_dateTime,
                    p.status AS payment_status,
                    p.gateway AS payment_gateway,
                    p.gatewayTitle AS payment_gatewayTitle,
                    p.data AS payment_data,
                    
                    c.id AS coupon_id,
                    c.code AS coupon_code,
                    c.discount AS coupon_discount,
                    c.deduction AS coupon_deduction,
                    c.limit AS coupon_limit,
                    c.customerLimit AS coupon_customerLimit,
                    c.status AS coupon_status
                FROM {$this->table} a
                INNER JOIN {$this->bookingsTable} cb ON cb.appointmentId = a.id
                INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
                INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId
                INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
                LEFT JOIN {$this->paymentsTable} p ON p.customerBookingId = cb.id
                LEFT JOIN {$this->customerBookingsExtrasTable} cbe ON cbe.customerBookingId = cb.id
                LEFT JOIN {$this->providersLocationTable} pl ON pl.userId = a.providerId
                LEFT JOIN {$this->couponsTable} c ON c.id = cb.couponId
                $where
                ORDER BY a.bookingStart"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @return Collection $criteria
     * @throws QueryExecutionException
     */
    public function getAppointmentsWithoutBookings()
    {
        try {
            $statement = $this->connection->query(
                "SELECT
                a.id AS appointment_id,
                a.bookingStart AS appointment_bookingStart,
                a.bookingEnd AS appointment_bookingEnd,
                a.providerId AS appointment_providerId,
                a.serviceId AS appointment_serviceId,
                a.status AS appointment_status,
                a.googleCalendarEventId as appointment_google_calendar_event_id,
                a.notifyParticipants AS appointment_notifyParticipants
            FROM {$this->table} a WHERE (
                  SELECT COUNT(*) FROM {$this->bookingsTable} cb WHERE a.id = cb.appointmentId
                ) = 0"
            );

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }
}
