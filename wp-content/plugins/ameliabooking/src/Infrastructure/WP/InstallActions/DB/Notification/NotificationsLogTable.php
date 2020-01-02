<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class NotificationsLogTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 */
class NotificationsLogTable extends AbstractDatabaseTable
{

    const TABLE = 'notifications_log';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $notificationTable = NotificationsTable::getTableName();
        $appointmentTable = AppointmentsTable::getTableName();
        $eventTable = EventsTable::getTableName();
        $userTable = UsersTable::getTableName();

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `notificationId` INT(11) NOT NULL,
                    `userId` INT(11) NOT NULL,
                    `appointmentId` INT(11) NULL,
                    `eventId` INT(11) NULL,
                    `sentDateTime` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`notificationId`) REFERENCES {$notificationTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,  
                    CONSTRAINT FOREIGN KEY (`userId`) REFERENCES {$userTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT FOREIGN KEY (`appointmentId`) REFERENCES {$appointmentTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT FOREIGN KEY (`eventId`) REFERENCES {$eventTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
