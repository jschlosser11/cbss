<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class CustomFieldsOptionsTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\CustomField
 */
class CustomFieldsOptionsTable extends AbstractDatabaseTable
{

    const TABLE = 'custom_fields_options';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $customFieldsTable = CustomFieldsTable::getTableName();

        return "CREATE TABLE {$table} (
                   `id` INT(11) NOT NULL AUTO_INCREMENT,
                   `customFieldId` int(11) NOT NULL,
                   `label` VARCHAR(255) NOT NULL DEFAULT '',
                   `position` int(11) NOT NULL,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (customFieldId) REFERENCES {$customFieldsTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
