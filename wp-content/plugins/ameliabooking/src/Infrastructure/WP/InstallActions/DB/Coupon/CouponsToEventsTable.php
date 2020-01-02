<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;

/**
 * Class CouponsToServicesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Coupon
 */
class CouponsToEventsTable extends AbstractDatabaseTable
{

    const TABLE = 'coupons_to_events';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $couponTable = CouponsTable::getTableName();
        $eventTable = EventsTable::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `couponId` int(11) NOT NULL,
                   `eventId` int(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`couponId`) REFERENCES {$couponTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE,
                    FOREIGN KEY (`eventId`) REFERENCES {$eventTable}(`id`)
                    ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
