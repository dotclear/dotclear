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
use Dotclear\Database\ContainerSchema;
use Dotclear\Exception\DatabaseException;
use Dotclear\Interface\Core\ConfigInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\DatabaseInterface;
use Dotclear\Interface\Core\SchemaInterface;

/**
 * @brief   Database handler.
 *
 * @since   2.36
 */
class Database implements DatabaseInterface
{
    protected ContainerHandler $container_handler;
    protected ContainerSchema $container_schema;

    public function __construct(
        protected ConfigInterface $config
    ) {
    }

    public function con(string $driver = '', string $host = '', string $database = '', string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface
    {
        // Reload connection handler if driver is set
        $reload = !empty($driver);

        // If driver is not set, we use parameters from config
        if ($driver === '') {
            $driver     = $this->config->dbDriver();
            $host       = $this->config->dbHost();
            $database   = $this->config->dbName();
            $user       = $this->config->dbUser();
            $password   = $this->config->dbPassword();
            $persistent = $this->config->dbPersist();
            $prefix     = $this->config->dbPrefix();
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

    public function schema(string $driver): SchemaInterface
    {
        if (!isset($this->container_schema)) {
            $this->container_schema  = new ContainerSchema();
        }

        if (!$this->container_schema->has($driver)) {
            throw new DatabaseException(sprintf('Database schema %s does not exist', $driver));
        }

        return $this->container_schema->get($driver, true, $this->con());
    }
}
