<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ExtrasTable;

/**
 * Class CustomerBookingsToExtrasTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class CustomerBookingsToExtrasTable extends AbstractDatabaseTable
{

    const TABLE = 'customer_bookings_to_extras';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $extrasTable = ExtrasTable::getTableName();
        $customerBookingsTable = CustomerBookingsTable::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `customerBookingId` INT(11) NOT NULL,
                    `extraId` INT(11) NOT NULL,
                    `quantity` INT(11) NOT NULL,
                    `price` DOUBLE NOT NULL,
                    `aggregatedPrice` TINYINT(1) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `bookingExtra` (`customerBookingId` ,`extraId`),
                    CONSTRAINT 
                    FOREIGN KEY (`customerBookingId`) REFERENCES {$customerBookingsTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT
                    FOREIGN KEY (`extraId`) REFERENCES {$extrasTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
