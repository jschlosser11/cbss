<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Location\LocationsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class AppointmentsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class AppointmentsTable extends AbstractDatabaseTable
{

    const TABLE = 'appointments';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $serviceTable = ServicesTable::getTableName();
        $usersTable = UsersTable::getTableName();

        $description = Description::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `status` ENUM('approved', 'pending', 'canceled', 'rejected') NULL,
                   `bookingStart` DATETIME NOT NULL,
                   `bookingEnd` DATETIME NOT NULL,
                   `notifyParticipants` TINYINT(1) NOT NULL,
                   `serviceId` INT(11) NOT NULL,
                   `providerId` INT(11) NOT NULL,
                   `internalNotes` TEXT({$description}) NULL,
                   `googleCalendarEventId` VARCHAR(255) NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`serviceId`) REFERENCES {$serviceTable}(`id`) 
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT FOREIGN KEY (`providerId`) REFERENCES {$usersTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
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
                "ALTER TABLE {$table} ADD COLUMN `locationId` INT(11) NULL,
                  ADD FOREIGN KEY (locationId) REFERENCES {$locationTable}(id) ON DELETE SET NULL ON UPDATE CASCADE",
            ] : [];

        return $x;
    }
}
