<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Driver\Pgsql;

use Dotclear\App;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Record;
use Exception;
use PgSql\Connection;
use PgSql\Result;

/**
 * @class Handler
 *
 * PostgreSQL Database handler
 *
 * This class adds a method for PostgreSQL only: {@link callFunction()}.
 */
class Handler extends AbstractHandler
{
    protected string $__driver = 'pgsql';
    protected string $__syntax = 'postgresql';

    protected ?string $utf8_unicode_ci = null;

    /**
     * Gets the PostgreSQL connection string.
     *
     * @param      string  $host      The host
     * @param      string  $user      The user
     * @param      string  $password  The password
     * @param      string  $database  The database
     *
     * @return     string  The connection string.
     */
    private function get_connection_string(string $host, string $user, string $password, string $database): string
    {
        $str  = '';
        $port = false;

        if ($host) {
            if (str_contains($host, ':')) {
                $bits = explode(':', $host);
                $host = array_shift($bits);
                $port = abs((int) array_shift($bits));
            }
            $str .= "host = '" . addslashes($host) . "' ";

            if ($port) {
                $str .= 'port = ' . $port . ' ';
            }
        }
        if ($user) {
            $str .= "user = '" . addslashes($user) . "' ";
        }
        if ($password) {
            $str .= "password = '" . addslashes($password) . "' ";
        }
        if ($database) {
            $str .= "dbname = '" . addslashes($database) . "' ";
        }

        return $str;
    }

    /**
     * Open a DB connection
     *
     * @param      string     $host      The host
     * @param      string     $user      The user
     * @param      string     $password  The password
     * @param      string     $database  The database
     *
     * @throws     Exception
     *
     * @return     mixed
     */
    public function db_connect(string $host, string $user, string $password, string $database)
    {
        if (!function_exists('pg_connect')) {
            throw new Exception('PHP PostgreSQL functions are not available');
        }

        $str = $this->get_connection_string($host, $user, $password, $database);

        if (($link = @pg_connect($str)) === false) {
            throw new Exception('Unable to connect to database');
        }

        $this->db_post_connect($link);

        return $link;
    }

    /**
     * Open a persistant DB connection
     *
     * @param      string  $host      The host
     * @param      string  $user      The user
     * @param      string  $password  The password
     * @param      string  $database  The database
     *
     * @return     mixed
     */
    public function db_pconnect(string $host, string $user, string $password, string $database)
    {
        if (!function_exists('pg_pconnect')) {
            throw new Exception('PHP PostgreSQL functions are not available');
        }

        $str = $this->get_connection_string($host, $user, $password, $database);

        if (($link = @pg_pconnect($str)) === false) {
            throw new Exception('Unable to connect to database');
        }

        $this->db_post_connect($link);

        return $link;
    }

    /**
     * Post connection helper
     *
     * @param      mixed  $handle   The DB handle
     */
    private function db_post_connect($handle): void
    {
        if (version_compare($this->db_version($handle), '9.1') >= 0) {
            // Only for PostgreSQL 9.1+
            $result = $this->db_query($handle, "SELECT * FROM pg_collation WHERE (collcollate LIKE '%.utf8')");
            if ($this->db_num_rows($result) > 0) {
                $this->db_result_seek($result, 0);
                $row = $this->db_fetch_assoc($result);
                if ($row !== false) {
                    $this->utf8_unicode_ci = '"' . $row['collname'] . '"';
                }
            }
        }
    }

    /**
     * Close DB connection
     *
     * @param      mixed  $handle  The DB handle
     */
    public function db_close($handle): void
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            pg_close($handle);
        }
    }

    /**
     * Get DB version
     *
     * @param      mixed  $handle  The handle
     *
     * @return     string
     */
    public function db_version($handle): string
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            return pg_parameter_status($handle, 'server_version') ?: '';
        }

        return '';
    }

    /**
     * Parse database tables path
     *
     * @param   mixed   $handle     The handle
     * @param   string  $path       The tables path
     *
     * @return  string
     */
    public function db_search_path($handle, $path): string
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            $searchpath = explode('.', $path, 2);
            if (count($searchpath) > 1) {
                $path  = $searchpath[1];
                $query = 'SET search_path TO ' . $searchpath[0] . ',public;';
                @pg_query($handle, $query);
            }
        }

        return $path;
    }

    /**
     * Execute a DB query
     *
     * @param      mixed      $handle  The handle
     * @param      string     $query   The query
     *
     * @throws     Exception
     *
     * @return     mixed
     */
    public function db_query($handle, string $query)
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            $res = @pg_query($handle, $query);
            if ($res === false) {
                $msg = (string) $this->db_last_error($handle);
                if (App::config()->devMode()) {
                    $msg .= ' SQL=[' . $query . ']';
                }

                throw new Exception($msg);
            }

            return $res;
        }
    }

    /**
     * db_query() alias
     *
     * @param      mixed   $handle  The handle
     * @param      string  $query   The query
     *
     * @return     mixed
     */
    public function db_exec($handle, string $query)
    {
        return $this->db_query($handle, $query);
    }

    /**
     * Get number of fields in result
     *
     * @param      mixed  $res    The resource
     *
     * @return     int
     */
    public function db_num_fields($res): int
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_num_fields($res);
        }

        return 0;
    }

    /**
     * Get number of rows in result
     *
     * @param      mixed  $res    The resource
     *
     * @return     int
     */
    public function db_num_rows($res): int
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_num_rows($res);
        }

        return 0;
    }

    /**
     * Get field name in result
     *
     * @param      mixed   $res       The resource
     * @param      int     $position  The position
     *
     * @return     string
     */
    public function db_field_name($res, int $position): string
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_field_name($res, $position);
        }

        return '';
    }

    /**
     * Get field type in result
     *
     * @param      mixed   $res       The resource
     * @param      int     $position  The position
     *
     * @return     string
     */
    public function db_field_type($res, int $position): string
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_field_type($res, $position);
        }

        return '';
    }

    /**
     * Fetch result data
     *
     * @param      mixed  $res    The resource
     *
     * @return     array<mixed>|false
     */
    public function db_fetch_assoc($res)
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_fetch_assoc($res);
        }

        return false;
    }

    /**
     * Seek in result
     *
     * @param      mixed   $res    The resource
     * @param      int     $row    The row
     *
     * @return     bool
     */
    public function db_result_seek($res, int $row): bool
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_result_seek($res, (int) $row);
        }

        return false;
    }

    /**
     * Get number of affected rows in last INSERT, DELETE or UPDATE query
     *
     * @param      mixed   $handle  The DB handle
     * @param      mixed   $res     The resource
     *
     * @return     int
     */
    public function db_changes($handle, $res): int
    {
        if (class_exists(\PgSql\Result::class) && $res instanceof Result) {
            return pg_affected_rows($res);
        }

        return 0;
    }

    /**
     * Get last query error, if any
     *
     * @param      mixed       $handle  The handle
     *
     * @return     false|string
     */
    public function db_last_error($handle)
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            return pg_last_error($handle);
        }

        return false;
    }

    /**
     * Escape a string (to be used in a SQL query)
     *
     * @param      mixed   $str     The string
     * @param      mixed   $handle  The DB handle
     *
     * @return     string
     */
    public function db_escape_string($str, $handle = null): string
    {
        if (class_exists(\PgSql\Connection::class) && $handle instanceof Connection) {
            return pg_escape_string($handle, (string) $str);
        }

        return addslashes((string) $str);
    }

    /**
     * Locks a table
     *
     * @param      string  $table  The table
     */
    public function db_write_lock(string $table): void
    {
        $this->execute('BEGIN');
        $this->execute('LOCK TABLE ' . $this->escapeSystem($table) . ' IN EXCLUSIVE MODE');
    }

    /**
     * Unlock tables
     */
    public function db_unlock(): void
    {
        $this->execute('END');
    }

    /**
     * Optimize a table
     *
     * @param      string  $table  The table
     */
    public function vacuum(string $table): void
    {
        $this->execute('VACUUM FULL ' . $this->escapeSystem($table));
    }

    /**
     * Get a date to be used in SQL query
     *
     * @param      string  $field    The field
     * @param      string  $pattern  The pattern
     *
     * @return     string
     */
    public function dateFormat(string $field, string $pattern): string
    {
        $rep = [
            '%d' => 'DD',
            '%H' => 'HH24',
            '%M' => 'MI',
            '%m' => 'MM',
            '%S' => 'SS',
            '%Y' => 'YYYY',
        ];

        $pattern = str_replace(array_keys($rep), array_values($rep), $pattern);

        return 'TO_CHAR(' . $field . ',' . "'" . $this->escapeStr($pattern) . "')";
    }

    /**
     * Get an ORDER BY fragment to be used in a SQL query
     *
     * @param      mixed  ...$args  The arguments
     *
     * @return     string
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
            } elseif (is_array($v) && !empty($v['field'])) {
                $v          = array_merge($default, $v);
                $v['order'] = (strtoupper((string) $v['order']) == 'DESC' ? 'DESC' : '');
                if ($v['collate']) {
                    if ($this->utf8_unicode_ci) {
                        $res[] = $v['field'] . ' COLLATE ' . $this->utf8_unicode_ci . ' ' . $v['order'];
                    } else {
                        $res[] = 'LOWER(' . $v['field'] . ') ' . $v['order'];
                    }
                } else {
                    $res[] = $v['field'] . ' ' . $v['order'];
                }
            }
        }

        return empty($res) ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    /**
     * Get fields concerned by lexical sort
     *
     * @param      mixed  ...$args  The arguments
     *
     * @return     string
     */
    public function lexFields(...$args): string
    {
        $fmt = $this->utf8_unicode_ci ? '%s COLLATE ' . $this->utf8_unicode_ci : 'LOWER(%s)';
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
     * Function call
     *
     * Calls a PostgreSQL function an returns the result as a {@link Record}.
     * After <var>$name</var>, you can add any parameters you want to append
     * them to the PostgreSQL function. You don't need to escape string in
     * arguments.
     *
     * @param string    $name    Function name
     * @param mixed     ...$data
     *
     * @return    Record
     */
    public function callFunction(string $name, ...$data): Record
    {
        foreach ($data as $k => $v) {
            if (is_null($v)) {
                $data[$k] = 'NULL';
            } elseif (is_string($v)) {
                $data[$k] = "'" . $this->escapeStr($v) . "'";
            } elseif (is_array($v)) {
                $data[$k] = $v[0];
            } else {
                $data[$k] = $v;
            }
        }

        $req = 'SELECT ' . $name . "(\n" .
        implode(",\n", array_values($data)) .
            "\n) ";

        return $this->select($req);
    }
}
