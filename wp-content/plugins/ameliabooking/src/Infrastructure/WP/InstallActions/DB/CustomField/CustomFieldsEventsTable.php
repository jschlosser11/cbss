<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Booking\EventsTable;

/**
 * Class CustomFieldsEventsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField
 */
class CustomFieldsEventsTable extends AbstractDatabaseTable
{

    const TABLE = 'custom_fields_events';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $customFieldsTable = CustomFieldsTable::getTableName();
        $eventTable = EventsTable::getTableName();

        return "CREATE TABLE {$table} (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `customFieldId` int(11) NOT NULL,
                  `eventId` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  FOREIGN KEY (`customFieldId`) REFERENCES {$customFieldsTable}(id) ON DELETE CASCADE,
                  FOREIGN KEY (`eventId`) REFERENCES {$eventTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
