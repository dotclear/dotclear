<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\PdoPgsql;

use Dotclear\Database\AbstractPdoHandler;
use Dotclear\Database\StaticRecord;
use Dotclear\Interface\Database\SchemaInterface;
use Dotclear\Schema\Database\Pgsql\Schema;
use Exception;
use PDO;

/**
 * @class Handler
 *
 * PostgreSQL Database handler
 *
 * This class adds a method for PostgreSQL only: {@link callFunction()}.
 */
class Handler extends AbstractPdoHandler
{
    public const HANDLER_NAME   = 'PostgreSQL (PDO)';
    public const HANDLER_DRIVER = 'pdopgsql';
    public const HANDLER_SYNTAX = 'postgresql';
    public const HANDLER_PDO    = 'pgsql';

    protected ?string $utf8_unicode_ci = null;

    public function schema(): SchemaInterface
    {
        // Use database schema from standard pgsql driver
        return new Schema($this);
    }

    protected function db_post_connect(PDO $handle): void
    {
        if (version_compare($this->db_version($handle), '9.1') >= 0) {
            // Only for PostgreSQL 9.1+
            $result = $this->db_query($handle, "SELECT * FROM pg_collation WHERE (collcollate LIKE '%.utf8')");

            if ($result !== false && $result->rowCount() > 0) {
                $row = $result->fetch();
                if ($row !== false) {
                    $this->utf8_unicode_ci = '"' . $row['collname'] . '"';
                }
            }
        }
    }

    public function db_search_path($handle, $path): string
    {
        if ($handle instanceof PDO) {
            $searchpath = explode('.', $path, 2);
            if (count($searchpath) > 1) {
                $this->db_exec($handle, 'SET search_path TO ' . $searchpath[0] . ',public;');
            }
        }

        return $path;
    }

    public function db_write_lock(string $table): void
    {
        $this->execute('BEGIN');
        $this->execute('LOCK TABLE ' . $this->escapeSystem($table) . ' IN EXCLUSIVE MODE');
    }

    public function vacuum(string $table): void
    {
        $this->execute('VACUUM FULL ' . $this->escapeSystem($table));
    }

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

        return $res === [] ? '' : ' ORDER BY ' . implode(',', $res) . ' ';
    }

    public function lexFields(...$args): string
    {
        $res = [];
        $fmt = $this->utf8_unicode_ci ? '%s COLLATE ' . $this->utf8_unicode_ci : 'LOWER(%s)';
        foreach ($args as $v) {
            if (is_string($v)) {
                $res[] = sprintf($fmt, $v);
            } elseif (is_array($v)) {
                $res = array_map(fn ($i): string => sprintf($fmt, $i), $v);
            }
        }

        return implode(',', $res);
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
     */
    public function callFunction(string $name, ...$data): StaticRecord
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
