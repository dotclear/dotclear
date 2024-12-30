<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Interface.Core
 * @brief       Dotclear core services interfaces
 *
 * Third party core services MUST implement these interfaces.
 */

namespace Dotclear\Interface\Core;

use Dotclear\Database\Cursor;
use Dotclear\Database\InterfaceHandler;
use Dotclear\Database\Record;

/**
 * @brief   Database connection handler interface.
 *
 * @see     \Dotclear\Database\InterfaceHandler
 *
 * @since   2.28
 */
interface ConnectionInterface extends InterfaceHandler
{
    /**
     * Closes database connection.
     */
    public function close(): void;

    /**
     * Returns database schema handler
     */
    public function schema(): SchemaInterface;

    /**
     * Returns database driver name
     */
    public function driver(): string;

    /**
     * Returns database SQL syntax name
     */
    public function syntax(): string;

    /**
     * Returns database driver version
     */
    public function version(): string;

    /**
     * Returns database table prefix
     */
    public function prefix(): string;

    /**
     * Returns current database name
     */
    public function database(): string;

    /**
     * Returns link resource
     *
     * @return mixed
     */
    public function link();

    /**
     * Run query and get results
     *
     * Executes a query and return a {@link record} object.
     *
     * @param string    $sql            SQL query
     */
    public function select(string $sql): Record;

    /**
     * Return an empty record
     *
     * Return an empty {@link record} object (without any information).
     */
    public function nullRecord(): Record;

    /**
     * Run query
     *
     * Executes a query and return true if succeed
     *
     * @param string    $sql            SQL query
     */
    public function execute(string $sql): bool;

    /**
     * Begin transaction
     *
     * Begins a transaction. Transaction should be {@link commit() commited}
     * or {@link rollback() rollbacked}.
     */
    public function begin(): void;

    /**
     * Commit transaction
     *
     * Commits a previoulsy started transaction.
     */
    public function commit(): void;

    /**
     * Rollback transaction
     *
     * Rollbacks a previously started transaction.
     */
    public function rollback(): void;

    /**
     * Aquiere write lock
     *
     * This method lock the given table in write access.
     *
     * @param string    $table        Table name
     */
    public function writeLock(string $table): void;

    /**
     * Release lock
     *
     * This method releases an acquiered lock.
     */
    public function unlock(): void;

    /**
     * Vacuum the table given in argument.
     *
     * @param string    $table        Table name
     */
    public function vacuum(string $table): void;

    /**
     * Changed rows
     *
     * Returns the number of lines affected by the last DELETE, INSERT or UPDATE
     * query.
     */
    public function changes(): int;

    /**
     * Last error
     *
     * Returns the last database error or false if no error.
     *
     * @return string|false
     */
    public function error();

    /**
     * Date formatting
     *
     * Returns a query fragment with date formater.
     *
     * The following modifiers are accepted:
     *
     * - %d : Day of the month, numeric
     * - %H : Hour 24 (00..23)
     * - %M : Minute (00..59)
     * - %m : Month numeric (01..12)
     * - %S : Seconds (00..59)
     * - %Y : Year, numeric, four digits
     *
     * @param string    $field            Field name
     * @param string    $pattern          Date format
     */
    public function dateFormat(string $field, string $pattern): string;

    /**
     * Query Limit
     *
     * Returns a LIMIT query fragment. <var>$arg1</var> could be an array of
     * offset and limit or an integer which is only limit. If <var>$arg2</var>
     * is given and <var>$arg1</var> is an integer, it would become limit.
     *
     * @param array<mixed>|int      $arg1        array or integer with limit intervals
     * @param int|null              $arg2        integer or null
     */
    public function limit($arg1, ?int $arg2 = null): string;

    /**
     * IN fragment
     *
     * Returns a IN query fragment where $in could be an array, a string,
     * an integer or null
     *
     * @param array<mixed>|string|int|null        $in        "IN" values
     */
    public function in($in): string;

    /**
     * ORDER BY fragment
     *
     * Returns a ORDER BY query fragment where arguments could be an array or a string
     *
     * array param:
     *    key      : decription
     *    field    : field name (string)
     *    collate  : True or False (boolean) (Alphabetical order / Binary order)
     *    order    : ASC or DESC (string) (Ascending order / Descending order)
     *
     * string param field name (Binary ascending order)
     *
     * @param   array<string, mixed>|string     $args
     */
    public function orderBy(...$args): string;

    /**
     * Field name(s) fragment (using generic UTF8 collating sequence if available else using SQL LOWER function)
     *
     * Returns a fields list where args could be an array or a string
     *
     * array param: list of field names
     * string param: field name
     *
     * @param   array<string>|string     $args
     */
    public function lexFields(...$args): string;

    /**
     * Concat strings
     *
     * Returns SQL concatenation of methods arguments. Theses arguments
     * should be properly escaped when needed.
     *
     * @param   array<mixed>     $args
     */
    public function concat(...$args): string;

    /**
     * Escape string
     *
     * Returns SQL protected string or array values.
     *
     * @param string|array<string, mixed>    $i        String or array to protect
     *
     * @return string|array<string, string>
     */
    public function escape($i);

    /**
     * Escape string
     *
     * Returns SQL protected string value.
     *
     * @param string    $str        String to protect
     */
    public function escapeStr(string $str): string;

    /**
     * System escape string
     *
     * Returns SQL system protected string.
     *
     * @param string        $str        String to protect
     */
    public function escapeSystem(string $str): string;

    /**
     * Cursor object
     *
     * Returns a new instance of {@link Cursor} class on <var>$table</var> for
     * the current connection.
     *
     * @param string        $table    Target table
     */
    public function openCursor(string $table): Cursor;
}
