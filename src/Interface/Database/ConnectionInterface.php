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

namespace Dotclear\Interface\Database;

use Dotclear\Database\Cursor;
use Dotclear\Database\Record;

/**
 * @brief   Database connection handler interface.
 *
 * @since   2.28
 */
interface ConnectionInterface
{
    /**
     * Database handler name.
     *
     * @var    string  HANDLER_NAME
     */
    public const HANDLER_NAME = 'undefined';

    /**
     * Database handler driver.
     *
     * @var    string  HANDLER_DRIVER
     */
    public const HANDLER_DRIVER = 'undefined';

    /**
     * Database handler syntax.
     *
     * @var    string  SHANDLER_YNTAX
     */
    public const HANDLER_SYNTAX = 'undefined';

    /// @name Methods implemented by the driver
    ///@{

    /**
     * Open connection
     *
     * This method should open a database connection and return a new resource link.
     *
     * @param string    $host           Database server host
     * @param string    $user           Database user name
     * @param string    $password       Database password
     * @param string    $database       Database name
     *
     * @return mixed
     */
    public function db_connect(string $host, string $user, string $password, string $database);

    /**
     * Open persistent connection
     *
     * This method should open a persistent database connection and return a new resource link.
     *
     * @param string    $host           Database server host
     * @param string    $user           Database user name
     * @param string    $password       Database password
     * @param string    $database       Database name
     *
     * @return mixed
     */
    public function db_pconnect(string $host, string $user, string $password, string $database);

    /**
     * Close connection
     *
     * This method should close resource link.
     *
     * @param mixed    $handle        Resource link
     */
    public function db_close($handle): void;

    /**
     * Database version
     *
     * This method should return database version number.
     *
     * @param mixed     $handle        Resource link
     */
    public function db_version($handle): string;

    /**
     * Database tables prefix
     *
     * This method should return database tables parsed prefix.
     *
     * @param   mixed   $handle     The handle
     * @param   string  $path       The tables path
     */
    public function db_search_path($handle, $path): string;

    /**
     * Database query
     *
     * This method should run an SQL query and return a resource result.
     *
     * @param mixed     $handle       Resource link
     * @param string    $query        SQL query string
     *
     * @return mixed
     */
    public function db_query($handle, string $query);

    /**
     * Database exec query
     *
     * This method should run an SQL query and return a resource result.
     *
     * @param mixed     $handle       Resource link
     * @param string    $query        SQL query string
     *
     * @return mixed
     */
    public function db_exec($handle, string $query);

    /**
     * Result columns count
     *
     * This method should return the number of fields in a result.
     *
     * @param mixed   $res           Resource result
     */
    public function db_num_fields($res): int;

    /**
     * Result rows count
     *
     * This method should return the number of rows in a result.
     *
     * @param mixed     $res            Resource result
     */
    public function db_num_rows($res): int;

    /**
     * Field name
     *
     * This method should return the name of the field at the given position
     * <var>$position</var>.
     *
     * @param mixed    $res            Resource result
     * @param int      $position       Field position
     */
    public function db_field_name($res, int $position): string;

    /**
     * Field type
     *
     * This method should return the field type a the given position
     * <var>$position</var>.
     *
     * @param mixed     $res            Resource result
     * @param int       $position       Field position
     */
    public function db_field_type($res, int $position): string;

    /**
     * Fetch result
     *
     * This method should fetch one line of result and return an associative array
     * with field name as key and field value as value.
     *
     * @param mixed     $res            Resource result
     *
     * @return array<mixed>|false
     */
    public function db_fetch_assoc($res);

    /**
     * Move result Cursor
     *
     * This method should move result Cursor on given row position <var>$row</var>
     * and return true on success.
     *
     * @param mixed     $res        Resource result
     * @param int       $row        Row position
     */
    public function db_result_seek($res, int $row): bool;

    /**
     * Affected rows
     *
     * This method should return number of rows affected by INSERT, UPDATE or
     * DELETE queries.
     *
     * @param mixed      $handle         Resource link
     * @param mixed      $res            Resource result
     */
    public function db_changes($handle, $res): int;

    /**
     * Last error
     *
     * This method should return the last error string for the current connection.
     *
     * @param mixed     $handle        Resource link
     */
    public function db_last_error($handle): false|string;

    /**
     * Escape string
     *
     * This method should return an escaped string for the current connection.
     *
     * @param mixed     $str            String to escape
     * @param mixed     $handle         Resource link
     */
    public function db_escape_string($str, $handle = null): string;

    /**
     * Acquiere Write lock
     *
     * This method should lock the given table in write access.
     *
     * @param string    $table        Table name
     */
    public function db_write_lock(string $table): void;

    /**
     * Release lock
     *
     * This method should releases an acquiered lock.
     */
    public function db_unlock(): void;

    ///@}

    /// @name Methods implemented by the handler
    ///@{

    /**
     * Closes database connection.
     */
    public function close(): void;

    /**
     * Returns database schema handler.
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
     * @param   array<string, mixed>|string     ...$args
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
     * @param   array<string>|string     ...$args
     */
    public function lexFields(...$args): string;

    /**
     * Concat strings
     *
     * Returns SQL concatenation of methods arguments. Theses arguments
     * should be properly escaped when needed.
     *
     * @param   mixed   ...$args
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

    ///@}
}
