<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;

/**
 * Class ProvidersSpecialDayPeriodTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersSpecialDayPeriodTable extends AbstractDatabaseTable
{

    const TABLE = 'providers_to_specialdays_periods';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();
        $specialTable = ProvidersSpecialDayTable::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `specialDayId` int(11) NOT NULL,
                  `startTime` time NOT NULL,
                  `endTime` time NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`),
                  CONSTRAINT `{$wpdb->prefix}amelia_p_t_s_p_ibfk_1` FOREIGN KEY (specialDayId) REFERENCES {$specialTable}(id) ON DELETE CASCADE
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
                  ADD CONSTRAINT `{$wpdb->prefix}amelia_p_t_s_p_ibfk_2` FOREIGN KEY(locationId) REFERENCES {$locationTable}(id) ON DELETE SET NULL ON UPDATE CASCADE",
            ] : [];

        return $x;
    }
}
