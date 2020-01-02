<?php

namespace AmeliaBooking\Infrastructure\Repository\User;

use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Repository\User\CustomerRepositoryInterface;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Domain\ValueObjects\String\BookingStatus;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\WPUsersTable;

/**
 * Class UserRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class CustomerRepository extends UserRepository implements CustomerRepositoryInterface
{
    /**
     * @param     $criteria
     * @param int $itemsPerPage
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \Exception
     */
    public function getFiltered($criteria, $itemsPerPage = null)
    {
        try {
            $wpUserTable = WPUsersTable::getTableName();
            $bookingsTable = CustomerBookingsTable::getTableName();
            $appointmentsTable = AppointmentsTable::getTableName();

            $params = [
                ':type'                 => AbstractUser::USER_ROLE_CUSTOMER,
                ':bookingPendingStatus' => BookingStatus::PENDING,
                ':statusVisible'        => Status::VISIBLE,
            ];

            $limit = '';
            if ($itemsPerPage) {
                $params[':startingLimit'] = ($criteria['page'] - 1) * $itemsPerPage;
                $params[':itemsPerPage'] = $itemsPerPage;

                $limit = 'LIMIT :startingLimit, :itemsPerPage';
            }

            $order = '';
            if (!empty($criteria['sort'])) {
                $column = $criteria['sort'][0] === '-' ? substr($criteria['sort'], 1) : $criteria['sort'];
                $orderColumn = $column === 'customer' ? 'CONCAT(u.firstName, " ", u.lastName)' : 'lastAppointment';
                $orderDirection = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';
                $order = "ORDER BY {$orderColumn} {$orderDirection}";
            }

            $search = '';
            if (!empty($criteria['search'])) {
                $params[':search1'] = $params[':search2'] = $params[':search3'] = "%{$criteria['search']}%";

                $search = " AND (CONCAT(u.firstName, ' ', u.lastName) LIKE :search1
                            OR wpu.display_name LIKE :search2
                            OR u.note LIKE :search3)";
            }

            $statement = $this->connection->prepare(
                "SELECT 
                u.id as id,
                u.status as status,
                u.firstName as firstName,
                u.lastName as lastName,
                u.email as email,
                u.phone as phone,
                u.gender as gender,
                u.externalId as externalId,
                IF(u.birthday IS NOT NULL, u.birthday , '') as birthday,
                u.note as note,
                IF(wpu.display_name IS NOT NULL, wpu.display_name , '') as wpName,
                MAX(app.bookingStart) as lastAppointment,
                COUNT(cb.id) as totalAppointments,
                SUM(case when cb.status = :bookingPendingStatus then 1 else 0 end) as countPendingAppointments
                FROM {$this->table} as u
                LEFT JOIN {$wpUserTable} wpu ON u.externalId = wpu.id
                LEFT JOIN {$bookingsTable} cb ON u.id = cb.customerId
                LEFT JOIN {$appointmentsTable} app ON app.id = cb.appointmentId
                WHERE u.type = :type AND u.status = :statusVisible $search
                GROUP BY u.id
                {$order}
                {$limit}"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $row['id'] = (int)$row['id'];
            $row['externalId'] = $row['externalId'] === null ? $row['externalId'] : (int)$row['externalId'];
            $row['lastAppointment'] = $row['lastAppointment'] ?
                DateTimeService::getCustomDateTimeFromUtc($row['lastAppointment']) : $row['lastAppointment'];
            $items[] = $row;
        }

        return $items;
    }

    /**
     * @param $criteria
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function getCount($criteria)
    {
        $wpUserTable = WPUsersTable::getTableName();

        $params = [
            ':type'          => AbstractUser::USER_ROLE_CUSTOMER,
            ':statusVisible' => Status::VISIBLE,
        ];

        $search = '';
        if (!empty($criteria['search'])) {
            $params[':search1'] = $params[':search2'] = $params[':search3'] = "%{$criteria['search']}%";

            $search = " AND (CONCAT(u.firstName, ' ', u.lastName) LIKE :search1
                            OR wpu.display_name LIKE :search2
                            OR u.note LIKE :search3)";
        }

        try {
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) as count
                FROM {$this->table} as u 
                LEFT JOIN {$wpUserTable} wpu ON u.externalId = wpu.id
                WHERE u.type = :type AND u.status = :statusVisible $search"
            );

            $statement->execute($params);

            $rows = $statement->fetch()['count'];
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return $rows;
    }
}
