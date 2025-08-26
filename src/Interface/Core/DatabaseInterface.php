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

use Dotclear\Interface\Database\ConnectionInterface;
use Dotclear\Interface\Database\SchemaInterface;

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
     * If <var>$driver</var> is given, a new instance is returned.
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
     * Get new dabatase schema handler instance.
     *
     * If <var>$driver</var> is empty, current connection driver is used.
     *
     * @param   string  $driver     Driver name
     *
     * @return  SchemaInterface     The database schema handler instance
     */
    public function schema(string $driver = ''): SchemaInterface;
}
