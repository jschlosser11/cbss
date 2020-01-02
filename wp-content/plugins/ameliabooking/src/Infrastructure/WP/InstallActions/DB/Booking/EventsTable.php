<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Domain\ValueObjects\String\Description;

/**
 * Class EventsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsTable extends AbstractDatabaseTable
{

    const TABLE = 'events';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $name = Name::MAX_LENGTH;
        $description = Description::MAX_LENGTH;
        $color = Color::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `parentId` INT(11),
                   `name` varchar({$name}) NOT NULL default '',
                   `status` ENUM('approved','pending','canceled','rejected') NOT NULL,
                   `bookingOpens` DATETIME NULL,
                   `bookingCloses` DATETIME NULL,
                   `recurringCycle` ENUM('daily', 'weekly', 'monthly', 'yearly') NULL,
                   `recurringOrder` int(11) NULL,
                   `recurringUntil` DATETIME NULL,
                   `maxCapacity` int(11) NOT NULL,
                   `price` double NOT NULL,
                   `locationId` INT(11) NULL,
                   `customLocation` VARCHAR({$name}) NULL,
                   `description` TEXT({$description}) NULL,
                   `color` varchar({$color}) NULL NULL,
                   `show` TINYINT(1) NOT NULL DEFAULT 1,
                   `notifyParticipants` TINYINT(1) NOT NULL,
                   `created` DATETIME NOT NULL,
                   `settings` text({$description}) NULL DEFAULT NULL,
                   PRIMARY KEY (`id`),
                   CONSTRAINT FOREIGN KEY (`parentId`) REFERENCES {$table}(`id`)
                   ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
