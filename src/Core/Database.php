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

use Dotclear\Database\ContainerHandler;
use Dotclear\Exception\DatabaseException;
use Dotclear\Interface\Core\DatabaseInterface;
use Dotclear\Interface\Database\ConnectionInterface;

/**
 * @brief   Database handler.
 *
 * @since   2.36
 */
class Database implements DatabaseInterface
{
    /**
     * Database connection handlers container.
     */
    protected ContainerHandler $container_handler;

    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
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

        if (!isset($this->container_handler)) {
            $this->container_handler = new ContainerHandler();
        }

        // Stop on unknown driver
        if ($driver === '' || !$this->container_handler->has($driver)) {
            throw new DatabaseException(sprintf('Database handler %s does not exist', $driver));
        }

        return $this->container_handler->get($driver, $reload, host: $host, database: $database, user: $user, password: $password, persistent: $persistent, prefix: $prefix);
    }
}
