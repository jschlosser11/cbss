<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\CustomerBookingsTable;

/**
 * Class PaymentsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Payment
 */
class PaymentsTable extends AbstractDatabaseTable
{

    const TABLE = 'payments';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();

        $customerBookingTable = CustomerBookingsTable::getTableName();

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `customerBookingId` int(11) NOT NULL,
                   `amount` DOUBLE NOT NULL default 0,
                   `dateTime` datetime NULL,
                   `status` ENUM('paid', 'pending') NOT NULL,
                   `gateway` ENUM('onSite', 'payPal', 'stripe', 'wc') NOT NULL,
                   `gatewayTitle` varchar(255) NULL,
                   `data` text NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT FOREIGN KEY (`customerBookingId`) REFERENCES {$customerBookingTable}(`id`)
                    ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
