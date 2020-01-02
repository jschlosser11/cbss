<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Payment;

use AmeliaBooking\Domain\Entity\Payment\Payment;
use AmeliaBooking\Domain\Factory\Payment\PaymentFactory;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Repository\Payment\PaymentRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Connection;

/**
 * Class PaymentRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Payment
 */
class PaymentRepository extends AbstractRepository implements PaymentRepositoryInterface
{
    /** @var string */
    protected $appointmentsTable;

    /** @var string */
    protected $bookingsTable;

    /** @var string */
    protected $servicesTable;

    /** @var string */
    protected $usersTable;

    /** @var string */
    protected $eventsTable;

    /** @var string */
    protected $eventsProvidersTable;

    /** @var string */
    protected $eventsPeriodsTable;

    /** @var string */
    protected $customerBookingsToEventsPeriodsTable;


    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $appointmentsTable
     * @param string     $bookingsTable
     * @param string     $servicesTable
     * @param string     $usersTable
     * @param string     $eventsTable
     * @param string     $eventsProvidersTable
     * @param string     $eventsPeriodsTable
     * @param string     $customerBookingsToEventsPeriodsTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $appointmentsTable,
        $bookingsTable,
        $servicesTable,
        $usersTable,
        $eventsTable,
        $eventsProvidersTable,
        $eventsPeriodsTable,
        $customerBookingsToEventsPeriodsTable
    ) {
        parent::__construct($connection, $table);

        $this->appointmentsTable = $appointmentsTable;
        $this->bookingsTable = $bookingsTable;
        $this->servicesTable = $servicesTable;
        $this->usersTable = $usersTable;
        $this->eventsTable = $eventsTable;
        $this->eventsProvidersTable = $eventsProvidersTable;
        $this->eventsPeriodsTable = $eventsPeriodsTable;
        $this->customerBookingsToEventsPeriodsTable = $customerBookingsToEventsPeriodsTable;
    }

    const FACTORY = PaymentFactory::class;

    /**
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO
                {$this->table} 
                (
                `customerBookingId`, `amount`, `dateTime`, `status`, `gateway`, `gatewayTitle`, `data`
                ) VALUES (
                :customerBookingId, :amount, :dateTime, :status, :gateway, :gatewayTitle, :data
                )"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int     $id
     * @param Payment $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':customerBookingId' => $data['customerBookingId'],
            ':amount'            => $data['amount'],
            ':dateTime'          => DateTimeService::getCustomDateTimeInUtc($data['dateTime']),
            ':status'            => $data['status'],
            ':gateway'           => $data['gateway'],
            ':gatewayTitle'      => $data['gatewayTitle'],
            ':data'              => $data['data'],
            ':id'                => $id,
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `customerBookingId` = :customerBookingId,
                `amount`            = :amount,
                `dateTime`          = :dateTime,
                `status`            = :status,
                `gateway`           = :gateway,
                `gatewayTitle`      = :gatewayTitle,
                `data`              = :data
                WHERE
                id = :id"
            );

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
        }

        return $response;
    }

    /**
     * @param array $criteria
     * @param int   $itemsPerPage
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getFiltered($criteria, $itemsPerPage = null)
    {
        $params = [];
        $appointmentParams = [];
        $eventParams = [];
        $whereAppointment = [];
        $whereEvent = [];

        $limit = '';
        if ($itemsPerPage) {
            $params[':startingLimit'] = ($criteria['page'] - 1) * $itemsPerPage;
            $params[':itemsPerPage'] = $itemsPerPage;

            $limit = 'LIMIT :startingLimit, :itemsPerPage';
        }

        if ($criteria['dates']) {
            $whereAppointment[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentAppointmentFrom AND :paymentAppointmentTo)";
            $appointmentParams[':paymentAppointmentFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams[':paymentAppointmentTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

            $whereEvent[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentEventFrom AND :paymentEventTo)";
            $eventParams[':paymentEventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $eventParams[':paymentEventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (!empty($criteria['customerId'])) {
            $appointmentParams[':customerAppointmentId'] = $criteria['customerId'];
            $whereAppointment[] = 'cb.customerId = :customerAppointmentId';

            $eventParams[':customerEventId'] = $criteria['customerId'];
            $whereEvent[] = 'cb.customerId = :customerEventId';
        }

        if (!empty($criteria['providerId'])) {
            $appointmentParams[':providerAppointmentId'] = $criteria['providerId'];
            $whereAppointment[] = 'a.providerId = :providerAppointmentId';

            $eventParams[':providerEventId'] = $criteria['providerId'];
            $whereEvent[] = 'epu.userId = :providerEventId';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;
                $queryServices[] = $param;
                $appointmentParams[$param] = $value;
            }

            $whereAppointment[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['status'])) {
            $appointmentParams[':statusAppointment'] = $criteria['status'];
            $whereAppointment[] = 'p.status = :statusAppointment';

            $eventParams[':statusEvent'] = $criteria['status'];
            $whereEvent[] = 'p.status = :statusEvent';
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ((array)$criteria['events'] as $index => $value) {
                $param = ':event' . $index;
                $queryEvents[] = $param;
                $eventParams[$param] = $value;
            }

            $whereEvent[] = "p.customerBookingId IN (SELECT cbe.customerBookingId
              FROM {$this->eventsTable} e
              INNER JOIN {$this->eventsPeriodsTable} ep ON ep.eventId = e.id
              INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.eventPeriodId = ep.id 
              WHERE e.id IN (" . implode(', ', $queryEvents) . '))';
        }

        $whereAppointment = $whereAppointment ? ' AND ' . implode(' AND ', $whereAppointment) : '';
        $whereEvent = $whereEvent ? ' AND ' . implode(' AND ', $whereEvent) : '';

        $appointmentQuery = "SELECT
                p.id AS id,
                p.customerBookingId AS customerBookingId,
                p.amount AS amount,
                p.dateTime AS dateTime,
                p.status AS status,
                p.gateway AS gateway,
                p.gatewayTitle AS gatewayTitle,
                a.providerId AS providerId,
                cb.customerId AS customerId,
                a.serviceId AS serviceId,
                a.id AS appointmentId,
                a.bookingStart AS bookingStart,
                s.name AS bookableName,
                cu.firstName AS customerFirstName,
                cu.lastName AS customerLastName,
                cu.email AS customerEmail,
                pu.firstName AS providerFirstName,
                pu.lastName AS providerLastName,
                pu.email AS providerEmail
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
            INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
            INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId
            WHERE 1=1 $whereAppointment";

        $eventQuery = "SELECT
                p.id AS id,
                p.customerBookingId AS customerBookingId,
                p.amount AS amount,
                p.dateTime AS dateTime,
                p.status AS status,
                p.gateway AS gateway,
                p.gatewayTitle AS gatewayTitle,
                NULL AS providerId,
                cb.customerId AS customerId,
                NULL AS serviceId,
                NULL AS appointmentId,
                NULL AS bookingStart,
                NULL AS bookableName,
                cu.firstName AS customerFirstName,
                cu.lastName AS customerLastName,
                cu.email AS customerEmail,
                NULL AS providerFirstName,
                NULL AS providerLastName,
                NULL AS providerEmail
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
            INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.customerBookingId = cb.id
            INNER JOIN {$this->eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
            LEFT JOIN {$this->eventsProvidersTable} epu ON epu.eventId = ep.eventId
            WHERE 1=1 $whereEvent";

        if (isset($criteria['events'], $criteria['services'])) {
            return [];
        } elseif (isset($criteria['services'])) {
            $paymentQuery = "{$appointmentQuery}";
            $params = array_merge($params, $appointmentParams);
        } elseif (isset($criteria['events'])) {
            $paymentQuery = "{$eventQuery}";
            $params = array_merge($params, $eventParams);
        } else {
            $paymentQuery = "({$appointmentQuery}) UNION ALL ({$eventQuery})";
            $params = array_merge($params, $appointmentParams, $eventParams);
        }

        try {
            $statement = $this->connection->prepare(
                "{$paymentQuery}
                ORDER BY dateTime
                $limit"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as &$row) {
            $result[(int)$row['customerBookingId']] = [
                'id' =>  (int)$row['id'],
                'dateTime' =>  DateTimeService::getCustomDateTimeFromUtc($row['dateTime']),
                'bookingStart' =>  DateTimeService::getCustomDateTimeFromUtc($row['bookingStart']),
                'status' =>  $row['status'],
                'gateway' =>  $row['gateway'],
                'gatewayTitle' =>  $row['gatewayTitle'],
                'name' => $row['bookableName'],
                'customerBookingId' =>  (int)$row['customerBookingId'],
                'amount' =>  (float)$row['amount'],
                'providers' =>  (int)$row['providerId'] ? [
                    [
                        'id' => (int)$row['providerId'],
                        'fullName' => $row['providerFirstName'] . ' ' . $row['providerLastName'],
                        'email' => $row['providerEmail'],
                    ]
                ] : [],
                'customerId' =>  (int)$row['customerId'],
                'serviceId' =>  (int)$row['serviceId'],
                'appointmentId' =>  (int)$row['appointmentId'],
                'bookableName' => $row['bookableName'],
                'customerFirstName' => $row['customerFirstName'],
                'customerLastName' => $row['customerLastName'],
                'customerEmail' => $row['customerEmail']
            ];
        }

        return $result;
    }

    /**
     * @param array $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        $params = [];
        $appointmentParams = [];
        $eventParams = [];
        $whereAppointment = [];
        $whereEvent = [];

        if (isset($criteria['dates'])) {
            $whereAppointment[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentAppointmentFrom AND :paymentAppointmentTo)";
            $appointmentParams[':paymentAppointmentFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $appointmentParams[':paymentAppointmentTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);

            $whereEvent[] = "(DATE_FORMAT(p.dateTime, '%Y-%m-%d %H:%i:%s') BETWEEN :paymentEventFrom AND :paymentEventTo)";
            $eventParams[':paymentEventFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $eventParams[':paymentEventTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (!empty($criteria['customerId'])) {
            $appointmentParams[':customerAppointmentId'] = $criteria['customerId'];
            $whereAppointment[] = 'cb.customerId = :customerAppointmentId';

            $eventParams[':customerEventId'] = $criteria['customerId'];
            $whereEvent[] = 'cb.customerId = :customerEventId';
        }

        if (!empty($criteria['providerId'])) {
            $appointmentParams[':providerAppointmentId'] = $criteria['providerId'];
            $whereAppointment[] = 'a.providerId = :providerAppointmentId';

            $eventParams[':providerEventId'] = $criteria['providerId'];
            $whereEvent[] = 'epu.userId = :providerEventId';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;
                $queryServices[] = $param;
                $appointmentParams[$param] = $value;
            }

            $whereAppointment[] = 'a.serviceId IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['status'])) {
            $appointmentParams[':statusAppointment'] = $criteria['status'];
            $whereAppointment[] = 'p.status = :statusAppointment';

            $eventParams[':statusEvent'] = $criteria['status'];
            $whereEvent[] = 'p.status = :statusEvent';
        }

        if (!empty($criteria['events'])) {
            $queryEvents = [];

            foreach ((array)$criteria['events'] as $index => $value) {
                $param = ':event' . $index;
                $queryEvents[] = $param;
                $eventParams[$param] = $value;
            }

            $whereEvent[] = "p.customerBookingId IN (SELECT cbe.customerBookingId
              FROM {$this->eventsTable} e
              INNER JOIN {$this->eventsPeriodsTable} ep ON ep.eventId = e.id
              INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.eventPeriodId = ep.id 
              WHERE e.id IN (" . implode(', ', $queryEvents) . '))';
        }

        $whereAppointment = $whereAppointment ? ' AND ' . implode(' AND ', $whereAppointment) : '';
        $whereEvent = $whereEvent ? ' AND ' . implode(' AND ', $whereEvent) : '';

        $appointmentQuery = "SELECT
                COUNT(*) AS appointmentsCount,
                0 AS eventsCount
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->appointmentsTable} a ON a.id = cb.appointmentId
            INNER JOIN {$this->servicesTable} s ON s.id = a.serviceId
            INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
            INNER JOIN {$this->usersTable} pu ON pu.id = a.providerId
            WHERE 1=1 $whereAppointment";

        $eventQuery = "SELECT
                0 AS appointmentsCount,
                COUNT(*) AS eventsCount
            FROM {$this->table} p
            INNER JOIN {$this->bookingsTable} cb ON cb.id = p.customerBookingId
            INNER JOIN {$this->usersTable} cu ON cu.id = cb.customerId
            INNER JOIN {$this->customerBookingsToEventsPeriodsTable} cbe ON cbe.customerBookingId = cb.id
            INNER JOIN {$this->eventsPeriodsTable} ep ON ep.id = cbe.eventPeriodId
            LEFT JOIN {$this->eventsProvidersTable} epu ON epu.eventId = ep.eventId
            WHERE 1=1 $whereEvent";

        if (isset($criteria['events'], $criteria['services'])) {
            return [];
        } elseif (isset($criteria['services'])) {
            $paymentQuery = "{$appointmentQuery}";
            $params = array_merge($params, $appointmentParams);
        } elseif (isset($criteria['events'])) {
            $paymentQuery = "{$eventQuery}";
            $params = array_merge($params, $eventParams);
        } else {
            $paymentQuery = "({$appointmentQuery}) UNION ALL ({$eventQuery})";
            $params = array_merge($params, $appointmentParams, $eventParams);
        }

        try {
            $statement = $this->connection->prepare(
                "{$paymentQuery}"
            );

            $statement->execute($params);

            $row1 = $statement->fetch()['appointmentsCount'];
            $row2 = $statement->fetch()['eventsCount'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $row1 + $row2;
    }

    /**
     * @param int $status
     */
    public function findByStatus($status)
    {
        // TODO: Implement findByStatus() method.
    }
}
