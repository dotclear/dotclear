<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Driver\Mysqli;

use Dotclear\App;
use Dotclear\Database\AbstractHandler;
use Exception;
use mysqli;
use mysqli_result;

/**
 * @class Handler
 *
 * MySQL Database handler
 */
class Handler extends AbstractHandler
{
    /**
     * Enables weak locks if true
     *
     * @var        bool
     */
    public static bool $weak_locks = true;

    /**
     * Driver name
     *
     * @var        string
     */
    protected string $__driver = 'mysqli';

    /**
     * SQL Syntax supported
     *
     * @var        string
     */
    protected string $__syntax = 'mysql';

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
        if (!function_exists('mysqli_connect')) {
            throw new Exception('PHP MySQLi functions are not available');
        }

        $port   = abs((int) ini_get('mysqli.default_port'));
        $socket = '';
        if (str_contains($host, ':')) {
            // Port or socket given
            $bits   = explode(':', $host);
            $host   = array_shift($bits);
            $socket = array_shift($bits);
            if (abs((int) $socket) > 0) {
                // TCP/IP connection on given port
                $port   = abs((int) $socket);
                $socket = '';
            } else {
                // Socket connection
                $port = 0;
            }
        }
        if (($link = @mysqli_connect($host, $user, $password, $database, $port, $socket)) === false) {
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
        // No pconnect wtih mysqli, below code is for comatibility
        return $this->db_connect($host, $user, $password, $database);
    }

    /**
     * Post connection helper
     *
     * @param      mixed  $handle   The DB handle
     */
    private function db_post_connect($handle): void
    {
        if (version_compare($this->db_version($handle), '4.1', '>=')) {
            $this->db_query($handle, 'SET NAMES utf8');
            $this->db_query($handle, 'SET CHARACTER SET utf8');
            $this->db_query($handle, "SET COLLATION_CONNECTION = 'utf8_general_ci'");
            $this->db_query($handle, "SET COLLATION_SERVER = 'utf8_general_ci'");
            $this->db_query($handle, "SET CHARACTER_SET_SERVER = 'utf8'");
            if (version_compare($this->db_version($handle), '8.0', '<')) {
                // Setting CHARACTER_SET_DATABASE is obosolete for MySQL 8.0+
                $this->db_query($handle, "SET CHARACTER_SET_DATABASE = 'utf8'");
            }
            $handle->set_charset('utf8');
        }
    }

    /**
     * Close DB connection
     *
     * @param      mixed  $handle  The DB handle
     */
    public function db_close($handle): void
    {
        if ($handle instanceof mysqli) {
            $handle->close();
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
        if ($handle instanceof mysqli) {
            $v = $handle->server_version;

            return sprintf('%s.%s.%s', ($v - ($v % 10000)) / 10000, ($v - ($v % 100)) % 10000 / 100, $v % 100);
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
        if ($handle instanceof mysqli) {
            $res = @$handle->query($query);
            if ($res === false) {
                $msg = (string) $this->db_last_error($handle);
                if (App::config()->devMode()) {
                    $msg .= ' SQL=[' . $query . ']';
                }

                throw new Exception($msg);
            }

            return $res;
        }

        return null;
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
        return $res instanceof mysqli_result ? $res->field_count : 0;
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
        return $res instanceof mysqli_result ? (int) $res->num_rows : 0;
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
        if ($res instanceof mysqli_result) {
            $res->field_seek($position);
            $finfo = $res->fetch_field();

            return $finfo->name;    // @phpstan-ignore-line
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
        if ($res instanceof mysqli_result) {
            $res->field_seek($position);
            $finfo = $res->fetch_field();

            return $this->_convert_types((string) $finfo->type); // @phpstan-ignore-line
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
        if ($res instanceof mysqli_result) {
            $v = $res->fetch_assoc();

            return $v ?? false;
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
        return $res instanceof mysqli_result ? $res->data_seek($row) : false;
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
        return $handle instanceof mysqli ? (int) $handle->affected_rows : 0;
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
        if ($handle instanceof mysqli && ($e = $handle->error)) {
            return $e . ' (' . $handle->errno . ')';
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
        return $handle instanceof mysqli ? $handle->real_escape_string((string) $str) : addslashes((string) $str);
    }

    /**
     * Locks a table
     *
     * @param      string  $table  The table
     */
    public function db_write_lock(string $table): void
    {
        try {
            $this->execute('LOCK TABLES ' . $this->escapeSystem($table) . ' WRITE');
        } catch (Exception $e) {
            # As lock is a privilege in MySQL, we can avoid errors with weak_locks static var
            if (!self::$weak_locks) {
                throw $e;
            }
        }
    }

    /**
     * Unlock tables
     */
    public function db_unlock(): void
    {
        try {
            $this->execute('UNLOCK TABLES');
        } catch (Exception $e) {
            if (!self::$weak_locks) {
                throw $e;
            }
        }
    }

    /**
     * Optimize a table
     *
     * @param      string  $table  The table
     */
    public function vacuum(string $table): void
    {
        $this->execute('OPTIMIZE TABLE ' . $this->escapeSystem($table));
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
        $pattern = str_replace('%M', '%i', $pattern);

        return 'DATE_FORMAT(' . $field . ',' . "'" . $this->escapeStr($pattern) . "')";
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
                $res[]      = $v['field'] . ($v['collate'] ? ' COLLATE utf8_unicode_ci' : '') . ' ' . $v['order'];
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
        $fmt = '%s COLLATE utf8_unicode_ci';
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
     * Get a CONCAT fragment
     *
     * @param   mixed     $args
     *
     * @return     string
     */
    public function concat(...$args): string
    {
        return 'CONCAT(' . implode(',', $args) . ')';
    }

    /**
     * Escape a string
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function escapeSystem(string $str): string
    {
        return '`' . $str . '`';
    }

    /**
     * Get type label
     *
     * @param      string  $id     The identifier
     *
     * @return     string
     */
    protected function _convert_types(string $id)
    {
        $id2type = [
            1 => 'int',
            2 => 'int',
            3 => 'int',
            8 => 'int',
            9 => 'int',

            16 => 'int', //BIT type recognized as unknown with mysql adapter

            4   => 'real',
            5   => 'real',
            246 => 'real',

            253 => 'string',
            254 => 'string',

            10 => 'date',
            11 => 'time',
            12 => 'datetime',
            13 => 'year',

            7 => 'timestamp',

            252 => 'blob',

        ];

        return $id2type[(int) $id] ?? 'unknown';
    }
}
