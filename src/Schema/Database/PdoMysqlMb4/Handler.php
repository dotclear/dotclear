<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\PdoMysqlMb4;

use Dotclear\Exception\DatabaseException;
use Dotclear\Schema\Database\PdoMysql\Handler as PdoMysqlHandler;
use Dotclear\Schema\Database\Mysqlimb4\Schema;
use Dotclear\Interface\Database\SchemaInterface;
use PDO;

/**
 * @class Handler
 *
 * MySQL (utf8mb4) Database handler
 */
class Handler extends PdoMysqlHandler
{
    public const HANDLER_NAME   = 'MySQL full UTF-8 (PDO)';
    public const HANDLER_DRIVER = 'pdomysqlmb4';

    public function schema(): SchemaInterface
    {
        // Use database schema from mysqli mb4 driver
        return new Schema($this);
    }

    public function db_dsn(string $host, string $user, string $password, string $database): string
    {
        return str_replace('charset=utf8;', 'charset=utf8mb4;', parent::db_dsn($host, $user, $password, $database));
    }

    protected function db_post_connect(PDO $handle): void
    {
        if (version_compare($this->db_version($handle), '5.7.7', '>=')) {
            $this->db_query($handle, 'SET NAMES utf8mb4');
            $this->db_query($handle, 'SET CHARACTER SET utf8mb4');
            $this->db_query($handle, "SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
            $this->db_query($handle, "SET COLLATION_SERVER = 'utf8mb4_unicode_ci'");
            $this->db_query($handle, "SET CHARACTER_SET_SERVER = 'utf8mb4'");
            if (version_compare($this->db_version($handle), '8.0', '<')) {
                // Setting CHARACTER_SET_DATABASE is obosolete for MySQL 8.0+
                $this->db_query($handle, "SET CHARACTER_SET_DATABASE = 'utf8mb4'");
            }
        } else {
            throw new DatabaseException('Unable to connect to an utf8mb4 database');
        }
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
                $res[] = $v['field'] . ($v['collate'] === true ? ' COLLATE utf8mb4_unicode_ci' : '') . ' ' . $order;
            }
        }

        return $res === [] ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    public function lexFields(...$args): string
    {
        $res = [];
        $fmt = '%s COLLATE utf8mb4_unicode_ci';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(fn (string $i): string => sprintf($fmt, $i), $v);
            }
        }

        return implode(',', $res);
    }
}
