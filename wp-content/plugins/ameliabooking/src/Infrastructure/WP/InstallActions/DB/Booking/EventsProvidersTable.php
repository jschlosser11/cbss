<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class EventsPeriodsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking
 */
class EventsProvidersTable extends AbstractDatabaseTable
{

    const TABLE = 'events_to_providers';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $eventTable = EventsTable::getTableName();
        $userTable = UsersTable::getTableName();

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `eventId` INT(11) NOT NULL,
                   `userId` INT(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`eventId`) REFERENCES {$eventTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT FOREIGN KEY (`userId`) REFERENCES {$userTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
