<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Exception\DatabaseException;
use Dotclear\Interface\Database\ConnectionInterface;
use Dotclear\Interface\Database\SchemaInterface;

/**
 * @brief   Database handler abstraction
 */
abstract class AbstractHandler implements ConnectionInterface
{
    /**
     * Driver name
     */
    protected string $__driver = '';

    /**
     * Syntax name
     */
    protected string $__syntax = '';

    /**
     * Database driver version
     */
    protected string $__version;

    /**
     * Database driver handle (resource)
     *
     * @var mixed   $__link
     */
    protected $__link;

    /**
     * Database tables prefix.
     */
    protected string $__prefix = '';

    /**
     * Last result resource link
     *
     * @var mixed   $__last_result
     */
    protected $__last_result;

    /**
     * Database name
     */
    protected string $__database;

    /**
     * Constructs a new instance.
     *
     * @param string    $host        Database hostname
     * @param string    $database    Database name
     * @param string    $user        User ID
     * @param string    $password    Password
     * @param bool      $persistent  Persistent connection
     */
    public function __construct(string $host, string $database, string $user = '', string $password = '', bool $persistent = false, string $prefix = '')
    {
        if ($this->__driver === '') {
            $this->__driver = static::HANDLER_DRIVER;
        }
        if ($this->__syntax === '') {
            $this->__syntax = static::HANDLER_SYNTAX;
        }

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

    public static function precondition(): void
    {
        // by default do not throw Exception for backward compatibility to dc < 2.36
    }

    public function close(): void
    {
        $this->db_close($this->__link);
    }

    public function driver(): string
    {
        return $this->__driver;
    }

    public function schema(): SchemaInterface
    {
        // If handler does not provide method schema(), we try a class named Schema in same namespace
        $class = substr(static::class, 0, (int) strrpos(static::class, '\\')) . '\\Schema';

        if (!class_exists($class) || !is_subclass_of($class, SchemaInterface::class)) {
            throw new DatabaseException('Undefined database schema handler');
        }

        return new $class($this);
    }

    public function syntax(): string
    {
        return $this->__syntax;
    }

    public function version(): string
    {
        return $this->__version;
    }

    public function prefix(): string
    {
        return $this->__prefix;
    }

    public function database(): string
    {
        return $this->__database;
    }

    public function link()
    {
        return $this->__link;
    }

    public function select(string $sql): Record
    {
        $result = $this->db_query($this->__link, $sql);

        $this->__last_result = &$result;

        $info = [
            'con'  => &$this,
            'cols' => $this->db_num_fields($result),
            'rows' => $this->db_num_rows($result),
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];
        for ($i = 0; $i < $info['cols']; $i++) {
            $info['info']['name'][] = $this->db_field_name($result, $i);
            $info['info']['type'][] = $this->db_field_type($result, $i);
        }

        return new Record($result, $info);
    }

    public function nullRecord(): Record
    {
        $result = null;

        $info = [
            'con'  => &$this,
            'cols' => 0, // no fields
            'rows' => 0, // no rows,
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];

        return new Record($result, $info);
    }

    public function execute(string $sql): bool
    {
        $result = $this->db_exec($this->__link, $sql);

        $this->__last_result = &$result;

        return true;
    }

    public function begin(): void
    {
        $this->execute('BEGIN');
    }

    public function commit(): void
    {
        $this->execute('COMMIT');
    }

    public function rollback(): void
    {
        $this->execute('ROLLBACK');
    }

    public function writeLock(string $table): void
    {
        $this->db_write_lock($table);
    }

    public function unlock(): void
    {
        $this->db_unlock();
    }

    public function vacuum(string $table): void
    {
    }

    public function changes(): int
    {
        return $this->db_changes($this->__link, $this->__last_result);
    }

    public function error()
    {
        $err = $this->db_last_error($this->__link);

        if (!$err) {
            return false;
        }

        return $err;
    }

    public function dateFormat(string $field, string $pattern): string
    {
        return
        'TO_CHAR(' . $field . ',' . "'" . $this->escapeStr($pattern) . "')";
    }

    public function limit($arg1, ?int $arg2 = null): string
    {
        if (is_array($arg1)) {
            $arg1 = array_values($arg1);
            $arg2 = $arg1[1] ?? null;
            $arg1 = $arg1[0];
        }

        return $arg2 === null ? ' LIMIT ' . (int) $arg1 . ' ' : ' LIMIT ' . $arg2 . ' OFFSET ' . (int) $arg1 . ' ';
    }

    public function in($in): string
    {
        if (is_null($in)) {
            return ' IN (NULL) ';
        }

        if (is_string($in)) {
            return " IN ('" . $this->escapeStr($in) . "') ";
        }

        if (is_array($in)) {
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

    public function orderBy(...$args): string
    {
        $res     = [];
        $default = [
            'order'   => '',
            'collate' => false,
        ];
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = $v;
            } elseif (!empty($v['field'])) {
                $v     = array_merge($default, $v);
                $order = strtoupper($v['order']);
                $order = ($order === 'DESC' ? $order : '');
                $res[] = ($v['collate'] ? 'LOWER(' . $v['field'] . ')' : $v['field']) . ' ' . $order;
            }
        }

        return $res === [] ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    public function lexFields(...$args): string
    {
        $res = [];
        $fmt = 'LOWER(%s)';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {   // @phpstan-ignore-line: PHPDoc is not certain — to be refined
                $res = array_map(fn (string $i): string => sprintf($fmt, $i), $v);
            }
        }

        return implode(',', $res);
    }

    public function concat(...$args): string
    {
        return implode(' || ', $args);
    }

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

    public function escapeStr(string $str): string
    {
        return $this->db_escape_string($str, $this->__link);
    }

    public function escapeSystem(string $str): string
    {
        return '"' . $str . '"';
    }

    public function openCursor(string $table): Cursor
    {
        return new Cursor($this, $table);
    }
}
