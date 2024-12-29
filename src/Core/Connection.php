<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Exception\DatabaseException;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\SchemaInterface;

/**
 * @brief   Database connection handler.
 *
 * Handle Dotclear default database connection
 * from one of the following drivers:
 * * mysqli (fallback for mysql)
 * * mysqlimb4
 * * pgsql
 * * sqlite
 *
 * We keep Connection::init() as third party class use it,
 * following class use App::newConnectionFromValues() to instanciate database connection:
 * * Plugins\importExport\ModuleImportDc1
 * * Plugins\importExport\src\ModuleImportWp
 * * Process\Install\Wizard.
 */
abstract class Connection implements ConnectionInterface
{
    /**
     * Initialize connection handler.
     *
     * @param   string  $driver         Driver name
     * @param   string  $host           Database hostname
     * @param   string  $database       Database name
     * @param   string  $user           User ID
     * @param   string  $password       Password
     * @param   bool    $persistent     Persistent connection
     * @param   string  $prefix         Database tables prefix
     */
    public static function init(string $driver, string $host, string $database, string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface
    {
        // PHP 7.0 mysql driver is obsolete, map to mysqli
        if ($driver === 'mysql') {
            $driver = 'mysqli';
        }

        return new (self::getDriverNamespace(ConnectionInterface::class, 'Handler', $driver))($host, $database, $user, $password, $persistent, $prefix);    // @phpstan-ignore-line
    }

    /**
     * Get new dabatase schema handler instance.
     *
     * @return  SchemaInterface     The database schema handler instance
     */
    public function schema(): SchemaInterface
    {
        return new (self::getDriverNamespace(SchemaInterface::class, 'Schema', $this->driver()))($this);    // @phpstan-ignore-line
    }

    /**
     * Get the fully qualified database handler class name.
     *
     * @throws  DatabaseException
     *
     * @param   string  $interface  The interface class name
     * @param   string  $class      The handler class name (Handler or Schema)
     * @param   string  $driver     The driver name
     *
     * @return  string  The fully qualifed database hanlder name
     */
    private static function getDriverNamespace(string $interface, string $class, string $driver): string
    {
        $ns = in_array($driver, ['mysqli', 'mysqlimb4', 'pgsql', 'sqlite']) ? 'Dotclear\\Database\\Driver\\' . ucfirst($driver) . '\\' . $class : '';

        if (!is_subclass_of($ns, $interface)) {
            throw new DatabaseException(sprintf('Database %s class %s does not exist or does not inherit %s', $class, $ns, $interface));
        }

        return $ns;
    }
}
