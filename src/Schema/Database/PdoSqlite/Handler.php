<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\PdoSqlite;

use Collator;
use Dotclear\Database\AbstractPdoHandler;
use PDO;

/**
 * @class Handler
 *
 * SQLite Database handler
 */
class Handler extends AbstractPdoHandler
{
    public const HANDLER_NAME   = 'SQLite (PDO)';
    public const HANDLER_DRIVER = 'pdosqlite';
    public const HANDLER_SYNTAX = 'sqlite';
    public const HANDLER_PDO    = 'sqlite';

    /**
     * UTF-8 Collator (if class exists)
     *
     * @var        mixed    $utf8_unicode_ci
     */
    protected $utf8_unicode_ci;

    protected bool $vacuum = false;

    public function db_dsn(string $host, string $user, string $password, string $database): string
    {
        return static::HANDLER_PDO . ':' . $database;
    }

    protected function db_post_connect(PDO $handle): void
    {
        $this->db_exec($handle, 'PRAGMA short_column_names = 1');
        $this->db_exec($handle, 'PRAGMA encoding = "UTF-8"');
        $handle->sqliteCreateFunction('now', $this->now(...), 0);
        if (class_exists('Collator')) {
            $this->utf8_unicode_ci = new Collator('root');
            if (!$handle->sqliteCreateCollation('utf8_unicode_ci', $this->utf8_unicode_ci->compare(...))) {
                $this->utf8_unicode_ci = null;
            }
        }
    }

    public function db_close($handle): void
    {
        if ($handle instanceof PDO) {
            if ($this->vacuum) {
                $this->db_exec($handle, 'VACUUM');
            }
            $handle       = null;
            $this->__link = null;
        }
    }

    public function escapeSystem(string $str): string
    {
        return "'" . $this->escapeStr($str) . "'";
    }

    public function db_write_lock(string $table): void
    {
        $this->execute('BEGIN EXCLUSIVE TRANSACTION');
    }

    public function vacuum(string $table): void
    {
        $this->vacuum = true;
    }

    public function dateFormat(string $field, string $pattern): string
    {
        return "strftime('" . $this->escapeStr($pattern) . "'," . $field . ')';
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
            } elseif (is_array($v) && !empty($v['field'])) {    // @phpstan-ignore-line
                $v          = array_merge($default, $v);
                $v['order'] = (strtoupper((string) $v['order']) === 'DESC' ? 'DESC' : '');
                if ($v['collate']) {
                    if ($this->utf8_unicode_ci instanceof Collator) {
                        $res[] = $v['field'] . ' COLLATE utf8_unicode_ci ' . $v['order'];
                    } else {
                        $res[] = 'LOWER(' . $v['field'] . ') ' . $v['order'];
                    }
                } else {
                    $res[] = $v['field'] . ' ' . $v['order'];
                }
            }
        }

        return $res === [] ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    public function lexFields(...$args): string
    {
        $res = [];
        $fmt = $this->utf8_unicode_ci instanceof Collator ? '%s COLLATE utf8_unicode_ci' : 'LOWER(%s)';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(fn (string $i): string => sprintf($fmt, $i), $v);
            }
        }

        return implode(',', $res);
    }

    # Internal SQLite function that adds NOW() SQL function.
    public function now(): string|false
    {
        return date('Y-m-d H:i:s');
    }
}
