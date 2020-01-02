<?php

namespace AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable;

use AmeliaBooking\Domain\Common\Exceptions\InvalidArgumentException;
use AmeliaBooking\Domain\ValueObjects\Picture;
use AmeliaBooking\Domain\ValueObjects\String\Color;
use AmeliaBooking\Domain\ValueObjects\String\Description;
use AmeliaBooking\Domain\ValueObjects\String\Name;
use AmeliaBooking\Infrastructure\WP\InstallActions\DB\AbstractDatabaseTable;

/**
 * Class BookableServiceTable
 *
 * @package AmeliaBooking\Infrastructure\WP\InstallActions\DB\Bookable
 */
class ServicesTable extends AbstractDatabaseTable
{

    const TABLE = 'services';

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public static function buildTable()
    {
        $table = self::getTableName();
        $categoryTable = CategoriesTable::getTableName();

        $name = Name::MAX_LENGTH;
        $description = Description::MAX_LENGTH;
        $color = Color::MAX_LENGTH;
        $picture = Picture::MAX_LENGTH;

        return "CREATE TABLE {$table} (
                   `id` int(11) NOT NULL AUTO_INCREMENT,
                   `name` varchar({$name}) NOT NULL default '',
                   `description` text({$description}) NULL,
                   `color` varchar({$color}) NOT NULL default '',
                   `price` double NOT NULL,
                   `status` ENUM('hidden', 'visible', 'disabled') NOT NULL default 'visible',
                   `categoryId` int(11) NOT NULL,
                   `minCapacity` int(11)  NOT NULL,
                   `maxCapacity` int(11)  NOT NULL,
                   `duration` int(11)  NOT NULL,
                   `timeBefore` int(11) NULL DEFAULT 0,
                   `timeAfter` int(11) NULL DEFAULT 0,
                   `bringingAnyone` TINYINT(1) NULL DEFAULT 1,
                   `priority` ENUM('least_expensive', 'most_expensive', 'least_occupied', 'most_occupied') NOT NULL,
                   `pictureFullPath` varchar ({$picture}) NULL,
                   `pictureThumbPath` varchar ({$picture}) NULL,
                   `position` int(11) default 0,
                   `show` TINYINT(1) DEFAULT 1,
                   `aggregatedPrice` TINYINT(1) DEFAULT 1,
                   `settings` text({$description}) NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    FOREIGN KEY (`categoryId`) REFERENCES {$categoryTable}(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_general_ci";
    }
}
