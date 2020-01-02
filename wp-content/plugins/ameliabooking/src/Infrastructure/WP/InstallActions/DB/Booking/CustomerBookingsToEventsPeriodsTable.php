<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomerBookingsToEventsPeriodsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class CustomerBookingsToEventsPeriodsTable extends AbstractDatabaseTable
{

    const TABLE = 'customer_bookings_to_events_periods';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();

        $eventPeriodsTable = EventsPeriodsTable::getTableName();
        $customerBookingsTable = CustomerBookingsTable::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `customerBookingId` INT(11) NOT NULL,
                    `eventPeriodId` INT(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `bookingEventPeriod` (`customerBookingId` ,`eventPeriodId`),
                    CONSTRAINT `{$wpdb->prefix}amelia_c_b_t_e_p_ibfk_1` FOREIGN KEY (`customerBookingId`) REFERENCES {$customerBookingsTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `{$wpdb->prefix}amelia_c_b_t_e_p_ibfk_2` FOREIGN KEY (`eventPeriodId`) REFERENCES {$eventPeriodsTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
