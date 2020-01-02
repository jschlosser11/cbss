<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable\ServicesTable;

/**
 * Class CustomFieldsServicesTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField
 */
class CustomFieldsServicesTable extends AbstractDatabaseTable
{

    const TABLE = 'custom_fields_services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $customFieldsTable = CustomFieldsTable::getTableName();
        $serviceTable = ServicesTable::getTableName();

        return "CREATE TABLE {$table} (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `customFieldId` int(11) NOT NULL,
                  `serviceId` int(11) NOT NULL,
                  PRIMARY KEY (`id`),
                  FOREIGN KEY (`customFieldId`) REFERENCES {$customFieldsTable}(id) ON DELETE CASCADE,
                  FOREIGN KEY (`serviceId`) REFERENCES {$serviceTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
