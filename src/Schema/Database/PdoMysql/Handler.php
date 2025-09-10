<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\PdoMysql;

use Dotclear\Database\AbstractPdoHandler;
use Dotclear\Interface\Database\SchemaInterface;
use Dotclear\Schema\Database\Mysqli\Schema;
use Exception;
use PDO;
use PDOStatement;

/**
 * @class Handler
 *
 * MySQL Database handler
 */
class Handler extends AbstractPdoHandler
{
    public const HANDLER_NAME   = 'MySQL (PDO)';
    public const HANDLER_DRIVER = 'pdomysql';
    public const HANDLER_SYNTAX = 'mysql';
    public const HANDLER_PDO    = 'mysql';

    /**
     * Enables weak locks if true
     */
    public static bool $weak_locks = true;

    public function schema(): SchemaInterface
    {
        // Use database schema from standard mysqli driver
        return new Schema($this);
    }

    public function db_dsn(string $host, string $user, string $password, string $database): string
    {
        return parent::db_dsn($host, $user, $password, $database) . 'charset=utf8;';
    }

    protected function db_post_connect(PDO $handle): void
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
        }
    }

    public function db_field_type($res, int $position): string
    {
        if ($res instanceof PDOStatement) {
            $type = $res->getColumnMeta($position)['pdo_type'] ?? '';

            if ($type !== '') {
                return $this->_convert_types((string) $type); // @phpstan-ignore-line
            }
        }

        return '';
    }

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

    public function vacuum(string $table): void
    {
        $this->execute('OPTIMIZE TABLE ' . $this->escapeSystem($table));
    }

    public function dateFormat(string $field, string $pattern): string
    {
        $pattern = str_replace('%M', '%i', $pattern);

        return 'DATE_FORMAT(' . $field . ',' . "'" . $this->escapeStr($pattern) . "')";
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
                $res[] = $v['field'] . ($v['collate'] === true ? ' COLLATE utf8_unicode_ci' : '') . ' ' . $order;
            }
        }

        return $res === [] ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    public function lexFields(...$args): string
    {
        $res = [];
        $fmt = '%s COLLATE utf8_unicode_ci';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(fn (string $i): string => sprintf($fmt, $i), $v);
            }
        }

        return implode(',', $res);
    }

    public function concat(...$args): string
    {
        return 'CONCAT(' . implode(',', $args) . ')';
    }

    public function escapeSystem(string $str): string
    {
        return '`' . $str . '`';
    }

    /**
     * Get type label
     *
     * @param      string  $id     The identifier
     */
    protected function _convert_types(string $id): string
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
