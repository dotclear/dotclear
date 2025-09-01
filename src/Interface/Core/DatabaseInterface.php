<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Database\Structure;
use Dotclear\Interface\Database\ConnectionInterface;

/**
 * @brief   Database handler interface.
 *
 * @since   2.36
 */
interface DatabaseInterface
{
    /**
     * Get dabatase connection handler instance.
     *
     * If <var>$driver</var> is given, a new instance is set until a new driver is given.
     *
     * @param   string  $driver         Driver name
     * @param   string  $host           Database hostname
     * @param   string  $database       Database name
     * @param   string  $user           User ID
     * @param   string  $password       Password
     * @param   bool    $persistent     Persistent connection
     * @param   string  $prefix         Database tables prefix
     */
    public function con(string $driver = '', string $host = '', string $database = '', string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface;

    /**
     * Get new dabatase connection handler instance.
     *
     * @param   string  $driver         Driver name
     * @param   string  $host           Database hostname
     * @param   string  $database       Database name
     * @param   string  $user           User ID
     * @param   string  $password       Password
     * @param   bool    $persistent     Persistent connection
     * @param   string  $prefix         Database tables prefix
     */
    public function newCon(string $driver, string $host, string $database, string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface;

    /**
     * Get database structure handler.
     *
     * The handler uses current connexion.
     * Each call to this method MUST return a new instance.
     *
     * @return  Structure   The database structure handler
     */
    public function structure(): Structure;

    /**
     * Get combo of available database drivers.
     *
     * @return  array<string, string>   The drivers name/driver pairs
     */
    public function combo(): array;
}
