<?php
/**
 * @author Slavko Babic
 * @date   2017-08-21
 */

namespace AmeliaBooking\Infrastructure\DB\PDO;

use \PDO;

/**
 * Class Connection
 *
 * @package AmeliaBooking\Infrastructure\DB\PDO
 */
class Connection extends \AmeliaBooking\Infrastructure\Connection
{
    /** @var PDO $pdo */
    protected $pdo;

    /** @var string $dns */
    private $dns;

    /** @var string $driver */
    private $driver = 'mysql';

    /**
     * Connection constructor.
     *
     * @param string $database
     * @param string $username
     * @param string $password
     * @param string $host
     * @param int    $port
     * @param string $charset
     */
    public function __construct(
        $host,
        $database,
        $username,
        $password,
        $charset = 'utf8',
        $port = 3306
    ) {
        parent::__construct(
            $host,
            $database,
            $username,
            $password,
            $charset,
            $port
        );

        $this->handler = new PDO(
            $this->dns(),
            $this->username,
            $this->password,
            $this->getOptions()
        );

        $this->handler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->handler->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->handler->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->handler->exec('SET SESSION sql_mode = "TRADITIONAL"');
    }

    /**
     * @return string
     */
    private function dns()
    {
        if ($this->dns) {
            return $this->dns;
        }

        $this->socketHandler();

        return $this->dns = "{$this->driver}:host={$this->host};port={$this->port}';dbname={$this->database}";
    }

    /**
     * @return array
     */
    private function getOptions()
    {
        $options = [
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION,
        ];

        if (defined('DB_CHARSET')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'set names ' . DB_CHARSET;
        }

        return $options;
    }
}
