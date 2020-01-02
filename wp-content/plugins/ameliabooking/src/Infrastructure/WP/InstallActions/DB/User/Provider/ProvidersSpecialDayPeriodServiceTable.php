<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;

/**
 * Class ProvidersEventPeriodServiceTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\Provider
 */
class ProvidersSpecialDayPeriodServiceTable extends AbstractDatabaseTable
{

    const TABLE = 'providers_to_specialdays_periods_services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();
        $specialDayPeriodTable = ProvidersSpecialDayPeriodTable::getTableName();
        $serviceTable = ServicesTable::getTableName();

        return "CREATE TABLE {$table}  (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `periodId` int(11) NOT NULL,
                  `serviceId` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `id` (`id`),
                  CONSTRAINT `{$wpdb->prefix}amelia_p_t_s_p_s_ibfk_1` FOREIGN KEY (periodId) REFERENCES {$specialDayPeriodTable}(id) ON DELETE CASCADE,
                  CONSTRAINT `{$wpdb->prefix}amelia_p_t_s_p_s_ibfk_2` FOREIGN KEY (serviceId) REFERENCES {$serviceTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
