<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Core\Connection;

/**
 * @class AbstractHandler
 *
 * Database handler abstraction
 */
abstract class AbstractHandler extends Connection
{
    /**
     * Driver name
     *
     * @var        string
     */
    protected string $__driver;

    /**
     * Syntax name
     *
     * @var        string
     */
    protected string $__syntax;

    /**
     * Database driver version
     *
     * @var        string
     */
    protected string $__version;

    /**
     * Database driver handle (resource)
     *
     * @var mixed
     */
    protected $__link;

    /**
     * Database tables prefix.
     *
     * @var string
     */
    protected string $__prefix = '';

    /**
     * Last result resource link
     *
     * @var mixed
     */
    protected $__last_result;

    /**
     * Database name
     *
     * @var string
     */
    protected string $__database;

    /**
     * @param string    $host        Database hostname
     * @param string    $database    Database name
     * @param string    $user        User ID
     * @param string    $password    Password
     * @param bool      $persistent  Persistent connection
     */
    public function __construct(string $host, string $database, string $user = '', string $password = '', bool $persistent = false, string $prefix = '')
    {
        if ($persistent) {
            $this->__link = $this->db_pconnect($host, $user, $password, $database);
        } else {
            $this->__link = $this->db_connect($host, $user, $password, $database);
        }

        $this->__version  = $this->db_version($this->__link);
        $this->__database = $database;

        if ($prefix !== '') {
            $this->__prefix = $this->db_search_path($this->__link, $prefix);
        }
    }

    /**
     * Closes database connection.
     */
    public function close(): void
    {
        $this->db_close($this->__link);
    }

    /**
     * Returns database driver name
     *
     * @return string
     */
    public function driver(): string
    {
        return $this->__driver;
    }

    /**
     * Returns database SQL syntax name
     *
     * @return string
     */
    public function syntax(): string
    {
        return $this->__syntax;
    }

    /**
     * Returns database driver version
     *
     * @return string
     */
    public function version(): string
    {
        return $this->__version;
    }

    /**
     * Returns database table prefix
     *
     * @return  string
     */
    public function prefix(): string
    {
        return $this->__prefix;
    }

    /**
     * Returns current database name
     *
     * @return string
     */
    public function database(): string
    {
        return $this->__database;
    }

    /**
     * Returns link resource
     *
     * @return mixed
     */
    public function link()
    {
        return $this->__link;
    }

    /**
     * Run query and get results
     *
     * Executes a query and return a {@link record} object.
     *
     * @param string    $sql            SQL query
     *
     * @return Record
     */
    public function select(string $sql): Record
    {
        /* @phpstan-ignore-next-line */
        $result = $this->db_query($this->__link, $sql);

        $this->__last_result = &$result;

        $info         = [];
        $info['con']  = &$this;
        $info['cols'] = $this->db_num_fields($result);
        $info['rows'] = $this->db_num_rows($result);
        $info['info'] = [];

        for ($i = 0; $i < $info['cols']; $i++) {
            $info['info']['name'][] = $this->db_field_name($result, $i);
            $info['info']['type'][] = $this->db_field_type($result, $i);
        }

        return new Record($result, $info);
    }

    /**
     * Return an empty record
     *
     * Return an empty {@link record} object (without any information).
     *
     * @return Record
     */
    public function nullRecord(): Record
    {
        $result = false;

        $info         = [];
        $info['con']  = &$this;
        $info['cols'] = 0; // no fields
        $info['rows'] = 0; // no rows
        $info['info'] = ['name' => [], 'type' => []];

        return new Record($result, $info);
    }

    /**
     * Run query
     *
     * Executes a query and return true if succeed
     *
     * @param string    $sql            SQL query
     *
     * @return bool true
     */
    public function execute(string $sql): bool
    {
        $result = $this->db_exec($this->__link, $sql);

        $this->__last_result = &$result;

        return true;
    }

    /**
     * Begin transaction
     *
     * Begins a transaction. Transaction should be {@link commit() commited}
     * or {@link rollback() rollbacked}.
     */
    public function begin(): void
    {
        $this->execute('BEGIN');
    }

    /**
     * Commit transaction
     *
     * Commits a previoulsy started transaction.
     */
    public function commit(): void
    {
        $this->execute('COMMIT');
    }

    /**
     * Rollback transaction
     *
     * Rollbacks a previously started transaction.
     */
    public function rollback(): void
    {
        $this->execute('ROLLBACK');
    }

    /**
     * Aquiere write lock
     *
     * This method lock the given table in write access.
     *
     * @param string    $table        Table name
     */
    public function writeLock(string $table): void
    {
        $this->db_write_lock($table);
    }

    /**
     * Release lock
     *
     * This method releases an acquiered lock.
     */
    public function unlock(): void
    {
        $this->db_unlock();
    }

    /**
     * Vacuum the table given in argument.
     *
     * @param string    $table        Table name
     */
    public function vacuum(string $table): void
    {
    }

    /**
     * Changed rows
     *
     * Returns the number of lines affected by the last DELETE, INSERT or UPDATE
     * query.
     *
     * @return int
     */
    public function changes(): int
    {
        return $this->db_changes($this->__link, $this->__last_result);
    }

    /**
     * Last error
     *
     * Returns the last database error or false if no error.
     *
     * @return string|false
     */
    public function error()
    {
        $err = $this->db_last_error($this->__link);

        if (!$err) {
            return false;
        }

        return $err;
    }

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
     *
     * @return string
     */
    public function dateFormat(string $field, string $pattern): string
    {
        return
        'TO_CHAR(' . $field . ',' . "'" . $this->escape($pattern) . "')";   // @phpstan-ignore-line
    }

    /**
     * Query Limit
     *
     * Returns a LIMIT query fragment. <var>$arg1</var> could be an array of
     * offset and limit or an integer which is only limit. If <var>$arg2</var>
     * is given and <var>$arg1</var> is an integer, it would become limit.
     *
     * @param array<mixed>|int      $arg1        array or integer with limit intervals
     * @param int|null              $arg2        integer or null
     *
     * @return string
     */
    public function limit($arg1, ?int $arg2 = null): string
    {
        if (is_array($arg1)) {
            $arg1 = array_values($arg1);
            $arg2 = $arg1[1] ?? null;
            $arg1 = $arg1[0];
        }

        if ($arg2 === null) {
            $sql = ' LIMIT ' . (int) $arg1 . ' ';
        } else {
            $sql = ' LIMIT ' . $arg2 . ' OFFSET ' . (int) $arg1 . ' ';
        }

        return $sql;
    }

    /**
     * IN fragment
     *
     * Returns a IN query fragment where $in could be an array, a string,
     * an integer or null
     *
     * @param array<mixed>|string|int|null        $in        "IN" values
     *
     * @return string
     */
    public function in($in): string
    {
        if (is_null($in)) {
            return ' IN (NULL) ';
        } elseif (is_string($in)) {
            return " IN ('" . $this->escapeStr($in) . "') ";
        } elseif (is_array($in)) {
            foreach ($in as $i => $v) {
                if (is_null($v)) {
                    $in[$i] = 'NULL';
                } elseif (is_string($v)) {
                    $in[$i] = "'" . $this->escapeStr($v) . "'";
                }
            }

            return ' IN (' . implode(',', $in) . ') ';
        }

        return ' IN (' . (int) $in . ') ';
    }

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
     *
     * @return string
     */
    public function orderBy(...$args): string
    {
        $default = [
            'order'   => '',
            'collate' => false,
        ];
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = $v;
            } elseif (is_array($v) && !empty($v['field'])) {    // @phpstan-ignore-line
                $v          = array_merge($default, $v);
                $v['order'] = (strtoupper((string) $v['order']) == 'DESC' ? 'DESC' : '');
                $res[]      = ($v['collate'] ? 'LOWER(' . $v['field'] . ')' : $v['field']) . ' ' . $v['order'];
            }
        }

        return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    /**
     * Field name(s) fragment (using generic UTF8 collating sequence if available else using SQL LOWER function)
     *
     * Returns a fields list where args could be an array or a string
     *
     * array param: list of field names
     * string param: field name
     *
     * @param   array<string>|string     $args
     *
     * @return string
     */
    public function lexFields(...$args): string
    {
        $fmt = 'LOWER(%s)';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(fn ($i) => sprintf($fmt, $i), $v);
            }
        }

        return empty($res) ? '' : implode(',', $res);
    }

    /**
     * Concat strings
     *
     * Returns SQL concatenation of methods arguments. Theses arguments
     * should be properly escaped when needed.
     *
     * @param   mixed     $args
     *
     * @return string
     */
    public function concat(...$args): string
    {
        return implode(' || ', $args);
    }

    /**
     * Escape string
     *
     * Returns SQL protected string or array values.
     *
     * @param string|array<string, mixed>    $i        String or array to protect
     *
     * @return string|array<string, string>
     */
    public function escape($i)
    {
        if (is_array($i)) {
            foreach ($i as $k => $s) {
                $i[$k] = $this->escapeStr((string) $s);
            }

            return $i;
        }

        return $this->escapeStr($i);
    }

    /**
     * Escape string
     *
     * Returns SQL protected string value.
     *
     * @param string    $str        String to protect
     *
     * @return string
     */
    public function escapeStr(string $str): string
    {
        return $this->db_escape_string($str, $this->__link);
    }

    /**
     * System escape string
     *
     * Returns SQL system protected string.
     *
     * @param string        $str        String to protect
     *
     * @return string
     */
    public function escapeSystem(string $str): string
    {
        return '"' . $str . '"';
    }

    /**
     * Cursor object
     *
     * Returns a new instance of {@link Cursor} class on <var>$table</var> for
     * the current connection.
     *
     * @param string        $table    Target table
     *
     * @return Cursor
     */
    public function openCursor(string $table): Cursor
    {
        return new Cursor($this, $table);
    }
}
