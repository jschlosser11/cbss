<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class ProvidersSpecialDayTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersSpecialDayTable extends AbstractDatabaseTable
{

    const TABLE = 'providers_to_specialdays';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $usersTable = UsersTable::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `userId` int(11) NOT NULL,
                  `startDate` date NOT NULL,
                  `endDate` date NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`),
                  FOREIGN KEY (userId) REFERENCES {$usersTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
