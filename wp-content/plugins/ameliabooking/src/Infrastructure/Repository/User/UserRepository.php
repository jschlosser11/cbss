<?php

namespace AmeliaBooking\Infrastructure\Repository\User;

use AmeliaBooking\Domain\Collection\Collection;
use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\Entity\User\AbstractUser;
use AmeliaBooking\Domain\Entity\User\Admin;
use AmeliaBooking\Domain\Entity\User\Customer;
use AmeliaBooking\Domain\Entity\User\Manager;
use AmeliaBooking\Domain\Entity\User\Provider;
use AmeliaBooking\Domain\Factory\User\UserFactory;
use AmeliaBooking\Domain\Repository\User\UserRepositoryInterface;
use AmeliaBooking\Domain\ValueObjects\String\Status;
use AmeliaBooking\Infrastructure\Common\Exceptions\QueryExecutionException;
use AmeliaBooking\Infrastructure\Repository\AbstractRepository;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;

/**
 * Class UserRepository
 *
 * @package AmeliaBooking\Infrastructure\Repository
 */
class UserRepository extends AbstractRepository implements UserRepositoryInterface
{

    const FACTORY = UserFactory::class;

    /**
     * @param AbstractUser $entity
     *
     * @return int
     * @throws QueryExecutionException
     */
    public function add($entity)
    {
        $data = $entity->toArray();

        $params = [
            ':type'             => $data['type'],
            ':status'           => $data['status'] ?: 'visible',
            ':externalId'       => $data['externalId'] ?: null,
            ':firstName'        => $data['firstName'],
            ':lastName'         => $data['lastName'],
            ':email'            => $data['email'],
            ':note'             => isset($data['note']) ? $data['note'] : null,
            ':phone'            => isset($data['phone']) ? $data['phone'] : null,
            ':gender'           => isset($data['gender']) ? $data['gender'] : null,
            ':birthday'         => $data['birthday'] ? $data['birthday']->format('Y-m-d') : null,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath']
        ];

        try {
            $statement = $this->connection->prepare(
                "INSERT INTO {$this->table} (
                `type`,
                `status`,
                `externalId`,
                `firstName`,
                `lastName`,
                `email`,
                `note`,
                `phone`,
                `gender`,
                `birthday`,
                `pictureFullPath`,
                `pictureThumbPath`
                ) VALUES (
                :type,
                :status,
                :externalId,
                :firstName,
                :lastName,
                :email,
                :note,
                :phone,
                :gender,
                STR_TO_DATE(:birthday, '%Y-%m-%d'),
                :pictureFullPath,
                :pictureThumbPath
                )"
            );

            $res = $statement->execute($params);

            if (!$res) {
                throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
            }
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to add data in ' . __CLASS__);
        }

        return $this->connection->lastInsertId();
    }

    /**
     * @param int          $id
     * @param AbstractUser $entity
     *
     * @return bool
     * @throws QueryExecutionException
     */
    public function update($id, $entity)
    {
        $data = $entity->toArray();

        $params = [
            ':type'             => $data['type'],
            ':externalId'       => $data['externalId'] ?: null,
            ':firstName'        => $data['firstName'],
            ':lastName'         => $data['lastName'],
            ':email'            => $data['email'],
            ':note'             => isset($data['note']) ? $data['note'] : null,
            ':phone'            => $data['phone'] ?: null,
            ':gender'           => isset($data['gender']) ? $data['gender'] : null,
            ':birthday'         => $data['birthday'] ? $data['birthday']->format('Y-m-d') : null,
            ':pictureFullPath'  => $data['pictureFullPath'],
            ':pictureThumbPath' => $data['pictureThumbPath'],
            ':id'               => $id
        ];

        try {
            $statement = $this->connection->prepare(
                "UPDATE {$this->table}
                SET 
                `type` = :type,
                `externalId` = :externalId,
                `firstName` = :firstName,
                `lastName` = :lastName,
                `email` = :email,
                `note` = :note,
                `phone` = :phone,
                `gender` = :gender,
                `birthday` = STR_TO_DATE(:birthday, '%Y-%m-%d'),
                `pictureFullPath` = :pictureFullPath,
                `pictureThumbPath` = :pictureThumbPath
                WHERE 
                id = :id"
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
     * @param $externalId
     *
     * @return Admin|Customer|Manager|Provider|bool
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function findByExternalId($externalId)
    {
        try {
            $statement = $this->connection->prepare("SELECT * FROM {$this->table} WHERE externalId = :id");
            $statement->bindParam(':id', $externalId);
            $statement->execute();
            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException(
                'Unable to find by external id in ' . __CLASS__,
                $e->getCode(),
                $e
            );
        }

        if (!$row) {
            return false;
        }

        return UserFactory::create($row);
    }

    /**
     * @param $type
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllByType($type)
    {
        $params = [
            ':type' => $type,
        ];

        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE type = :type');

            $statement->execute($params);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * Returns Collection of all customers and other users that have at least one booking
     *
     * @return Collection
     * @throws QueryExecutionException
     * @throws InvalidArgumentException
     */
    public function getAllWithAllowedBooking()
    {
        try {
            $bookingsTable = CustomerBookingsTable::getTableName();

            $statement = $this->connection->query("
            SELECT
            u.id AS id,
            u.firstName AS firstName,
            u.lastName AS lastName,
            u.email AS email,
            u.note AS note,
            u.phone AS phone,
            u.gender AS gender,
            u.status AS status
            FROM {$this->table} u
            LEFT JOIN {$bookingsTable} cb ON cb.customerId = u.id
            WHERE u.type = 'customer' OR (cb.id IS NOT NULL AND u.type IN ('admin', 'provider', 'manager'))
            GROUP BY u.id
            ORDER BY CONCAT(firstName, ' ', lastName) 
            ");

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
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
     * @return Collection
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getAllVisible()
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE status = :status');

            $statement->execute([
                ':status' => Status::VISIBLE
            ]);

            $rows = $statement->fetchAll();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = call_user_func([static::FACTORY, 'create'], $row);
        }

        return new Collection($items);
    }

    /**
     * @param int $email
     *
     * @return Admin|Customer|Manager|Provider
     * @throws InvalidArgumentException
     * @throws QueryExecutionException
     */
    public function getByEmail($email)
    {
        try {
            $statement = $this->connection->prepare($this->selectQuery() . ' WHERE LOWER(email) = LOWER(:email)');

            $statement->execute(array(
                ':email' => $email
            ));

            $row = $statement->fetch();
        } catch (\Exception $e) {
            throw new QueryExecutionException('Unable to get data from ' . __CLASS__, $e->getCode(), $e);
        }

        if (!$row) {
            return null;
        }

        return UserFactory::create($row);
    }
}
