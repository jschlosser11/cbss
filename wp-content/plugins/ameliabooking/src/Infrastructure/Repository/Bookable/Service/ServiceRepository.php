<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\Repository\Bookable\Service;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Services\DateTime\DateTimeService;
use AmeliaBooking\Infrastructure\Connection;
use AmeliaBooking\Domain\Entity\Bookable\Service\Service;
use AmeliaBooking\Domain\Factory\Bookable\Service\ServiceFactory;
use AmeliaBooking\Infrastructure\Common\Exceptions\NotFoundException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Domain\Repository\Bookable\Service\ServiceRepositoryInterface;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;

/**
 * Class ServiceRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository\Service
 */
class ServiceRepository extends AbstractRepository implements ServiceRepositoryInterface
{

    const FACTORY = ServiceFactory::class;

    /** @var string */
    protected $providerServicesTable;

    /** @var string */
    protected $extrasTable;

    /** @var string */
    protected $serviceViewsTable;

    /** @var string */
    protected $galleriesTable;

    /**
     * @param Connection $connection
     * @param string     $table
     * @param string     $providerServicesTable
     * @param string     $extrasTable
     * @param string     $serviceViewsTable
     * @param string     $galleriesTable
     */
    public function __construct(
        Connection $connection,
        $table,
        $providerServicesTable,
        $extrasTable,
        $serviceViewsTable,
        $galleriesTable
    ) {
        parent::__construct($connection, $table);
        $this->providerServicesTable = $providerServicesTable;
        $this->extrasTable = $extrasTable;
        $this->serviceViewsTable = $serviceViewsTable;
        $this->galleriesTable = $galleriesTable;
    }

    /**
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getAllArrayIndexedById()
    {
        try {
            $statement = $this->connection->query("SELECT
                s.id AS service_id,
                s.name AS service_name,
                s.description AS service_description,
                s.color AS service_color,
                s.price AS service_price,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.maxCapacity AS service_maxCapacity,
                s.minCapacity AS service_minCapacity,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                s.bringingAnyone as service_bringingAnyone,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.position AS service_position,
                s.show AS service_show,
                s.aggregatedPrice AS service_aggregatedPrice,
                s.settings AS service_settings,

                e.id AS extra_id,
                e.name AS extra_name,
                e.price AS extra_price,
                e.maxQuantity AS extra_maxQuantity,
                e.duration AS extra_duration,
                e.position AS extra_position,
                e.aggregatedPrice AS extra_aggregatedPrice,
                e.description AS extra_description,
                
                g.id AS gallery_id,
                g.pictureFullPath AS gallery_picture_full,
                g.pictureThumbPath AS gallery_picture_thumb,
                g.position AS gallery_position
              FROM {$this->table} s
              LEFT JOIN {$this->extrasTable} e ON e.serviceId = s.id
              LEFT JOIN {$this->galleriesTable} g ON g.entityId = s.id
              ORDER BY s.position, s.name ASC, e.position ASC, g.position ASC");
            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param Service $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':color'            => $data['color'],
            ':price'            => $data['price'],
            ':status'           => $data['status'],
            ':categoryId'       => $data['categoryId'],
            ':minCapacity'      => $data['minCapacity'],
            ':maxCapacity'      => $data['maxCapacity'],
            ':duration'         => $data['duration'],
            ':timeBefore'       => $data['timeBefore'],
            ':timeAfter'        => $data['timeAfter'],
            ':bringingAnyone'   => $data['bringingAnyone'] ? 1 : 0,
            ':show'             => $data['show'] ? 1 : 0,
            ':aggregatedPrice'  => $data['aggregatedPrice'] ? 1 : 0,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position'],
            ':settings'         => $data['settings'],
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO 
                {$this->table} 
                (
                `name`, 
                `description`, 
                `color`, 
                `price`, 
                `status`, 
                `categoryId`, 
                `minCapacity`,
                `maxCapacity`,
                `duration`,
                `timeBefore`,
                `timeAfter`,
                `bringingAnyone`,
                `show`,
                `aggregatedPrice`,
                `pictureFullPath`,
                `pictureThumbPath`,
                `position`,
                `settings`
                ) VALUES (
                :name,
                :description,
                :color,
                :price,
                :status,
                :categoryId,
                :minCapacity,
                :maxCapacity,
                :duration,
                :timeBefore,
                :timeAfter,
                :bringingAnyone,
                :show,
                :aggregatedPrice,
                :pictureFullPath,
                :pictureThumbPath,
                :position,
                :settings
                )"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException($result->getMessage());
            }

            return $this->connection->lastInsertId();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }
    }

    /**
     * @param int     $serviceId
     * @param Service $entity
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function update($serviceId, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':name'             => $data['name'],
            ':description'      => $data['description'],
            ':color'            => $data['color'],
            ':price'            => $data['price'],
            ':status'           => $data['status'],
            ':categoryId'       => $data['categoryId'],
            ':minCapacity'      => $data['minCapacity'],
            ':maxCapacity'      => $data['maxCapacity'],
            ':duration'         => $data['duration'],
            ':timeBefore'       => $data['timeBefore'],
            ':timeAfter'        => $data['timeAfter'],
            ':bringingAnyone'   => $data['bringingAnyone'] ? 1 : 0,
            ':show'             => $data['show'] ? 1 : 0,
            ':aggregatedPrice'  => $data['aggregatedPrice'] ? 1 : 0,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':position'         => $data['position'],
            ':settings'         => $data['settings'],
            ':id'               => $serviceId
        ];


        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET
                `name`              = :name,
                `description`       = :description,
                `color`             = :color,
                `price`             = :price,
                `status`            = :status,
                `categoryId`        = :categoryId,
                `minCapacity`       = :minCapacity,
                `maxCapacity`       = :maxCapacity,
                `duration`          = :duration,
                `timeBefore`        = :timeBefore,
                `timeAfter`         = :timeAfter,
                `bringingAnyone`    = :bringingAnyone,
                `show`              = :show,
                `aggregatedPrice`   = :aggregatedPrice,
                `pictureFullPath`   = :pictureFullPath,
                `pictureThumbPath`  = :pictureThumbPath,
                `position`          = :position,
                `settings`          = :settings
                WHERE
                id = :id"
            );

            $result = $statement->execute($params);

            if (!$result) {
                throw new QueryExecutionException('Unable to save data in ' . __CLASS__);
            }

            return $result;
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to save data in ' . __CLASS__ . $e->getMessage());
        }
    }

    /**
     * @param int $serviceId
     * @param int $userId
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getProviderServicesWithExtras($serviceId, $userId)
    {
        try {
            $statement = $this->connection->prepare("SELECT
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
                s.bringingAnyone as service_bringingAnyone,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.aggregatedPrice AS service_aggregatedPrice,
                s.settings AS service_settings,

                e.id AS extra_id,
                e.name AS extra_name,
                e.price AS extra_price,
                e.maxQuantity AS extra_maxQuantity,
                e.duration AS extra_duration,
                e.aggregatedPrice AS extra_aggregatedPrice,
                e.position AS extra_position
              FROM {$this->table} s
              INNER JOIN {$this->providerServicesTable} ps ON s.id = ps.serviceId
              LEFT JOIN {$this->extrasTable} e ON e.serviceId = s.id
              WHERE ps.userId = :userId AND ps.serviceId = :serviceId");

            $statement->bindParam(':userId', $userId);
            $statement->bindParam(':serviceId', $serviceId);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by ids in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param $criteria
     *
     * @return Collection
     * @throws QueryExecutionException
     */
    public function getByCriteria($criteria)
    {
        $params = [];
        $where = [];

        $order = 'ORDER BY s.name ASC';
        if (isset($criteria['sort'])) {
            if ($criteria['sort'] === '') {
                $order = 'ORDER BY s.position';
            } else {
                $orderColumn = strpos($criteria['sort'], 'name') !== false ? 's.name' : 's.price';
                $orderDirection = $criteria['sort'][0] === '-' ? 'DESC' : 'ASC';
                $order = "ORDER BY {$orderColumn} {$orderDirection}";
            }
        }

        if (!empty($criteria['search'])) {
            $params[':search'] = "%{$criteria['search']}%";

            $where[] = 's.name LIKE :search';
        }

        if (!empty($criteria['services'])) {
            $queryServices = [];

            foreach ((array)$criteria['services'] as $index => $value) {
                $param = ':service' . $index;
                $queryServices[] = $param;
                $params[$param] = $value;
            }

            $where[] = 's.id IN (' . implode(', ', $queryServices) . ')';
        }

        if (!empty($criteria['categories'])) {
            $queryCategories = [];

            foreach ((array)$criteria['categories'] as $index => $value) {
                $param = ':category' . $index;
                $queryCategories[] = $param;
                $params[$param] = $value;
            }

            $where[] = 's.categoryId IN (' . implode(', ', $queryCategories) . ')';
        }

        if (!empty($criteria['providers'])) {
            $queryProviders = [];

            foreach ((array)$criteria['providers'] as $index => $value) {
                $param = ':provider' . $index;
                $queryProviders[] = $param;
                $params[$param] = $value;
            }

            $where[] = 'ps.userId IN (' . implode(', ', $queryProviders) . ')';
        }

        if (!empty($criteria['status'])) {
            $params[':status'] = $criteria['status'];

            $where[] = 's.status = :status';
        }

        $where = $where ? ' AND ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare(
                "SELECT
                s.id AS service_id,
                s.name AS service_name,
                s.description AS service_description,
                s.color AS service_color,
                s.price AS service_price,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.maxCapacity AS service_maxCapacity,
                s.minCapacity AS service_minCapacity,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                s.bringingAnyone AS service_bringingAnyone,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.show AS service_show,
                s.aggregatedPrice AS service_aggregatedPrice,
                
                e.id AS extra_id,
                e.name AS extra_name,
                e.price AS extra_price,
                e.maxQuantity AS extra_maxQuantity,
                e.duration AS extra_duration,
                e.position AS extra_position,
                e.aggregatedPrice AS extra_aggregatedPrice,
                e.description AS extra_description,
                                
                g.id AS gallery_id,
                g.pictureFullPath AS gallery_picture_full,
                g.pictureThumbPath AS gallery_picture_thumb,
                g.position AS gallery_position
                
                FROM {$this->table} s
                LEFT JOIN {$this->extrasTable} e ON e.serviceId = s.id
                LEFT JOIN {$this->providerServicesTable} ps ON ps.serviceId = s.id
                LEFT JOIN {$this->galleriesTable} g ON g.entityId = s.id
                WHERE 1 = 1 $where
                $order"
            );

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows);
    }

    /**
     * @param int $serviceId
     *
     * @return Service
     * @throws QueryExecutionException
     */
    public function getByIdWithExtras($serviceId)
    {
        try {
            $statement = $this->connection->prepare(
                "SELECT
                s.id AS service_id,
                s.name AS service_name,
                s.description AS service_description,
                s.color AS service_color,
                s.price AS service_price,
                s.status AS service_status,
                s.categoryId AS service_categoryId,
                s.maxCapacity AS service_maxCapacity,
                s.minCapacity AS service_minCapacity,
                s.duration AS service_duration,
                s.timeBefore AS service_timeBefore,
                s.timeAfter AS service_timeAfter,
                s.bringingAnyone AS service_bringingAnyone,
                s.priority AS service_priority,
                s.pictureFullPath AS service_picture_full,
                s.pictureThumbPath AS service_picture_thumb,
                s.aggregatedPrice AS service_aggregatedPrice,
                
                e.id AS extra_id,
                e.name AS extra_name,
                e.description AS extra_description,
                e.price AS extra_price,
                e.maxQuantity AS extra_maxQuantity,
                e.duration AS extra_duration,
                e.aggregatedPrice AS extra_aggregatedPrice,
                e.position AS extra_position
                FROM {$this->table} s
                LEFT JOIN {$this->extrasTable} e ON e.serviceId = s.id
                WHERE s.id = :serviceId
                ORDER BY s.id, e.id"
            );

            $statement->bindParam(':serviceId', $serviceId);

            $statement->execute();

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to find by id in ' . __CLASS__, $e->getCode(), $e);
        }

        return call_user_func([static::FACTORY, 'createCollection'], $rows)->getItem($serviceId);
    }

    /**
     * @param $serviceId
     * @param $status
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function updateStatusById($serviceId, $status)
    {
        $params = [
            ':id'     => $serviceId,
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
     * Return an array of services with the number of appointments for the given date period.
     * Keys of the array are Services IDs.
     *
     * @param $criteria
     *
     * @return array
     * @throws QueryExecutionException
     * @throws \AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException
     */
    public function getAllNumberOfAppointments($criteria)
    {
        $appointmentTable = AppointmentsTable::getTableName();

        $params = [];
        $where = [];

        if ($criteria['dates']) {
            $where[] = "(DATE_FORMAT(a.bookingStart, '%Y-%m-%d %H:%i:%s') BETWEEN :bookingFrom AND :bookingTo)";
            $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (isset($criteria['status'])) {
            $where[] = 's.status = :status';
            $params[':status'] = $criteria['status'];
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare("SELECT
                s.id,
                s.name,
                COUNT(a.providerId) AS appointments
            FROM {$this->table} s
            INNER JOIN {$appointmentTable} a ON s.id = a.serviceId
            $where
            GROUP BY serviceId");

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    /**
     * Return an array of services with the number of views for the given date period.
     * Keys of the array are Services IDs.
     *
     * @param $criteria
     *
     * @return array
     * @throws QueryExecutionException
     */
    public function getAllNumberOfViews($criteria)
    {
        $params = [];
        $where = [];

        if ($criteria['dates']) {
            $where[] = "(DATE_FORMAT(sv.date, '%Y-%m-%d %H:%i:%s') BETWEEN :bookingFrom AND :bookingTo)";
            $params[':bookingFrom'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][0]);
            $params[':bookingTo'] = DateTimeService::getCustomDateTimeInUtc($criteria['dates'][1]);
        }

        if (isset($criteria['status'])) {
            $where[] = 's.status = :status';
            $params[':status'] = $criteria['status'];
        }

        $where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $statement = $this->connection->prepare("SELECT
            s.id,
            s.name,
            SUM(sv.views) AS views
            FROM {$this->table} s
            INNER JOIN {$this->serviceViewsTable} sv ON sv.serviceId = s.id 
            $where
            GROUP BY s.id");

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $result = [];

        foreach ($rows as $row) {
            $result[$row['id']] = $row;
        }

        return $result;
    }

    /**
     * @param $serviceId
     *
     * @return string
     * @throws QueryExecutionException
     */
    public function addViewStats($serviceId)
    {
        $date = DateTimeService::getNowDate();

        $params = [
            ':serviceId' => $serviceId,
            ':date'      => $date,
            ':views'     => 1
        ];

        try {
            // Check if there is already data for this provider for this date
            $statement = $this->connection->prepare(
                "SELECT COUNT(*) AS count 
                FROM {$this->serviceViewsTable} AS pv 
                WHERE pv.serviceId = :serviceId 
                AND pv.date = :date"
            );

            $statement->bindParam(':serviceId', $serviceId);
            $statement->bindParam(':date', $date);
            $statement->execute();
            $count = $statement->fetch()['count'];

            if (!$count) {
                $statement = $this->connection->prepare(
                    "INSERT INTO {$this->serviceViewsTable}
                    (`serviceId`, `date`, `views`)
                    VALUES 
                    (:serviceId, :date, :views)"
                );
            } else {
                $statement = $this->connection->prepare(
                    "UPDATE {$this->serviceViewsTable} pv SET pv.views = pv.views + :views
                    WHERE pv.serviceId = :serviceId
                    AND pv.date = :date"
                );
            }

            $response = $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        if (!$response) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return true;
    }

    /**
     * @param int $serviceId
     *
     * @return mixed
     * @throws QueryExecutionException
     */
    public function deleteViewStats($serviceId)
    {
        $params = [
            ':serviceId'  => $serviceId,
        ];

        try {
            $statement = $this->connection->prepare(
                "DELETE FROM {$this->serviceViewsTable} WHERE serviceId = :serviceId"
            );

            return $statement->execute($params);
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to delete data from ' . __CLASS__, $e->getCode(), $e);
        }
    }
}
