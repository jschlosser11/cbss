<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;

/**
 * Class ProvidersPeriodTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersPeriodTable extends AbstractDatabaseTable
{

    const TABLE = 'providers_to_periods';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $weekDayTable = ProvidersWeekDayTable::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `weekDayId` int(11) NOT NULL,
                  `startTime` time NOT NULL,
                  `endTime` time NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`),
                  FOREIGN KEY (weekDayId) REFERENCES {$weekDayTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }

    /**
     * @return array
     * @throws InvalidArgumentException
     */
    public static function alterTable()
    {
        $table = self::getTableName();
        $locationTable = LocationsTable::getTableName();

        global $wpdb;

        $x = ($wpdb->get_var("SHOW COLUMNS FROM `{$table}` LIKE 'locationId'") !== 'locationId') ?
            [
                "ALTER TABLE {$table} ADD COLUMN `locationId` int(11) NULL,
                  ADD FOREIGN KEY (locationId) REFERENCES {$locationTable}(id) ON DELETE SET NULL ON UPDATE CASCADE",
            ] : [];

        return $x;
    }
}
