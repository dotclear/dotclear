<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Database\Structure;
use Dotclear\Exception\DatabaseException;
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factories;
use Dotclear\Interface\Core\DatabaseInterface;
use Dotclear\Interface\Database\ConnectionInterface;

/**
 * @brief   Database handler.
 *
 * If container service in an anonymous function
 * we can not check it extends ConnectionInterface.
 *
 * @since   2.36
 */
class Database extends Container implements DatabaseInterface
{
    public const CONTAINER_ID = 'database';

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
        parent::__construct(Factories::getFactory(static::CONTAINER_ID));
    }

    public function getDefaultServices(): array
    {
        return [    // @phpstan-ignore-line
            'mysqli'      => \Dotclear\Schema\Database\Mysqli\Handler::class,
            'mysqlimb4'   => \Dotclear\Schema\Database\Mysqlimb4\Handler::class,
            'pgsql'       => \Dotclear\Schema\Database\Pgsql\Handler::class,
            'pdomysql'    => \Dotclear\Schema\Database\PdoMysql\Handler::class,
            'pdomysqlmb4' => \Dotclear\Schema\Database\PdoMysqlMb4\Handler::class,
            'pdosqlite'   => \Dotclear\Schema\Database\PdoSqlite\Handler::class,
            'pdopgsql'    => \Dotclear\Schema\Database\PdoPgsql\Handler::class,
        ];
    }

    public function con(string $driver = '', string $host = '', string $database = '', string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface
    {
        // Reload connection handler if driver is set
        $reload = $driver !== '';

        // If driver is not set, we use parameters from config
        if ($driver === '') {
            $driver     = $this->core->config()->dbDriver();
            $host       = $this->core->config()->dbHost();
            $database   = $this->core->config()->dbName();
            $user       = $this->core->config()->dbUser();
            $password   = $this->core->config()->dbPassword();
            $persistent = $this->core->config()->dbPersist();
            $prefix     = $this->core->config()->dbPrefix();
        }

        // PHP 7.0 mysql driver is obsolete, map to mysqli
        if ($driver === 'mysql') {
            $driver = 'mysqli';
        }
        // Standardized name from driver dc >= 2.36
        if ($driver === 'sqlite') {
            $driver = 'pdosqlite';
        }

        // Stop on unknown driver
        $class = $this->factory->get($driver);
        if ($driver === '' || !$this->has($driver) || (is_string($class) && !is_subclass_of($class, ConnectionInterface::class))) {
            throw new DatabaseException(sprintf('Database handler %s does not exist', $driver));
        }

        return $this->get($driver, $reload, host: $host, database: $database, user: $user, password: $password, persistent: $persistent, prefix: $prefix);
    }

    public function structure(): Structure
    {
        return new Structure($this->con(), $this->con()->prefix());
    }

    public function combo(): array
    {
        $res = [];
        foreach ($this->factory->dump() as $driver => $service) {
            if (is_string($service)) {
                // service is a class
                if (is_subclass_of($service, ConnectionInterface::class)) {
                    try {
                        // check if driver is useable
                        $service::precondition();
                    } catch (DatabaseException) {
                        continue;
                    }

                    $res[__($service::HANDLER_NAME)] = $service::HANDLER_DRIVER;
                }
            } else {
                // or maybe an anonymous function
                $res[$driver] = $driver;
            }
        }

        return $res;
    }
}
