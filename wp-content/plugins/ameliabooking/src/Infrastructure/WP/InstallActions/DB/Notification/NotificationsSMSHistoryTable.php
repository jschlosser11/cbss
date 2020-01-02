<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\String\Phone;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\AppointmentsTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\User\UsersTable;

/**
 * Class NotificationsSMSHistoryTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Notification
 *
 * @codingStandardsIgnoreFile
 */
class NotificationsSMSHistoryTable extends AbstractDatabaseTable
{

    const TABLE = 'notifications_sms_history';

    /**
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        global $wpdb;

        $table = self::getTableName();

        $phone = Phone::MAX_LENGTH;

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table) {
            $wpdb->query("ALTER TABLE {$table} DROP FOREIGN KEY {$table}_ibfk_1");
            $wpdb->query("ALTER TABLE {$table} DROP INDEX notificationId");
            $wpdb->query("ALTER TABLE {$table} DROP FOREIGN KEY {$table}_ibfk_2");
            $wpdb->query("ALTER TABLE {$table} DROP INDEX userId");
            $wpdb->query("ALTER TABLE {$table} DROP FOREIGN KEY {$table}_ibfk_3");
            $wpdb->query("ALTER TABLE {$table} DROP INDEX appointmentId");
        }

        return "CREATE TABLE {$table} (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `notificationId` INT(11) NOT NULL,
                    `userId` INT(11) NULL,
                    `appointmentId` INT(11) NULL,
                    `eventId` INT(11) NULL,
                    `logId` INT(11) NULL,
                    `dateTime` DATETIME NULL,
                    `text` VARCHAR(1600) NOT NULL,
                    `phone` VARCHAR({$phone}) NOT NULL,
                    `alphaSenderId` VARCHAR(11) NOT NULL,
                    `status` ENUM('prepared', 'accepted', 'queued', 'sent', 'failed', 'delivered', 'undelivered') NOT NULL DEFAULT 'prepared',
                    `price` DOUBLE NULL,
                    `segments` TINYINT(2) NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `id` (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
