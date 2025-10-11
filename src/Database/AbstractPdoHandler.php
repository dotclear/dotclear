<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\App;
use Dotclear\Exception\DatabaseException;
use PDO;
use PDOException;
use PDOStatement;

/**
 * @brief   Database handler abstraction
 */
abstract class AbstractPdoHandler extends AbstractHandler
{
    public const HANDLER_PDO = 'undefined';

    public static function precondition(): void
    {
        if (!class_exists('PDO') || !in_array(static::HANDLER_PDO, PDO::getAvailableDrivers())) {
            throw new DatabaseException(sprintf('PHP %s PDO driver is not available', static::HANDLER_NAME));
        }
    }

    /**
     * Build PDO Data Source Name.
     */
    public function db_dsn(string $host, string $user, string $password, string $database): string
    {
        $dsn = static::HANDLER_PDO . ':';

        if ($host !== '') {
            $port = false;
            if (str_contains($host, ':')) {
                $bits = explode(':', $host);
                $host = array_shift($bits);
                $port = abs((int) array_shift($bits));
            }
            $dsn .= 'host=' . addslashes($host) . ';';

            if ($port) {
                $dsn .= 'port=' . $port . ';';
            }
        }
        if ($database !== '') {
            $dsn .= 'dbname=' . addslashes($database) . ';';
        }
        if ($user !== '') {
            $dsn .= 'user=' . addslashes($user) . ';';
        }
        if ($password !== '') {
            $dsn .= 'password=' . addslashes($password) . ';';
        }

        return $dsn;
    }

    public function db_connect(string $host, string $user, string $password, string $database)
    {
        self::precondition();

        try {
            $link = new PDO($this->db_dsn($host, $user, $password, $database));
        } catch (PDOException) {
            throw new DatabaseException('Unable to connect to database');
        }

        $this->db_post_connect($link);

        return $link;
    }

    public function db_pconnect(string $host, string $user, string $password, string $database)
    {
        self::precondition();

        try {
            $link = new PDO(sprintf('%s:dbname=%s;host=%s;', static::HANDLER_PDO, $database, $host), null, null, [PDO::ATTR_PERSISTENT => true]);
        } catch (PDOException) {
            throw new DatabaseException('Unable to connect to database');
        }

        $this->db_post_connect($link);

        return $link;
    }

    /**
     * Post connection helper.
     *
     * @param   PDO     $handle     The DB handle
     */
    protected function db_post_connect(PDO $handle): void
    {
    }

    public function db_close($handle): void
    {
        if ($handle instanceof PDO) {
            $handle       = null;
            $this->__link = null;
        }
    }

    public function db_version($handle): string
    {
        return $handle instanceof PDO ? $handle->getAttribute(PDO::ATTR_SERVER_VERSION) : '';
    }

    public function db_search_path($handle, $path): string
    {
        return $path;
    }

    public function db_query($handle, string $query)
    {
        if ($handle instanceof PDO) {
            $res = $handle->query($query);
            if ($res === false) {
                $msg = (string) $this->db_last_error($handle);
                if (App::config()->devMode()) {
                    $msg .= ' SQL=[' . $query . ']';
                }

                throw new DatabaseException($msg);
            }

            return $res;
        }

        return null;
    }

    public function db_exec($handle, string $query)
    {
        return $this->db_query($handle, $query);
    }

    public function db_num_fields($res): int
    {
        return $res instanceof PDOStatement ? $res->columnCount() : 0;
    }

    public function db_num_rows($res): int
    {
        return 0;
    }

    public function db_field_name($res, int $position): string
    {
        return $res instanceof PDOStatement ? ($res->getColumnMeta($position)['name'] ?? '') : '';
    }

    public function db_field_type($res, int $position): string
    {
        if ($res instanceof PDOStatement) {
            $m = $res->getColumnMeta($position);

            if ($m !== false) {
                return match ($m['pdo_type']) {
                    PDO::PARAM_BOOL => 'boolean',
                    PDO::PARAM_NULL => 'null',
                    PDO::PARAM_INT  => 'integer',
                    default         => 'varchar',
                };
            }
        }

        return '';
    }

    public function db_fetch_assoc($res): false|array
    {
        if ($res instanceof PDOStatement) {
            return [];
        }

        return false;
    }

    public function db_result_seek($res, int $row): bool
    {
        return false;
    }

    public function db_changes($handle, $res): int
    {
        return $res instanceof PDOStatement ? $res->rowCount() : 0;
    }

    public function db_last_error($handle): string|false
    {
        if ($handle instanceof PDO) {
            $err = $handle->errorInfo();

            return $err[2] . ' (' . (int) $err[1] . ')';
        }

        return false;
    }

    public function db_write_lock(string $table): void
    {
        $this->execute('BEGIN EXCLUSIVE TRANSACTION');
    }

    public function db_unlock(): void
    {
        $this->execute('END');
    }

    public function db_escape_string($str, $handle = null): string
    {
        return $handle instanceof PDO ? trim((string) $handle->quote($str), "'") : addslashes((string) $str);
    }

    public function select(string $sql): StaticRecord
    {
        $result              = $this->db_query($this->__link, $sql);
        $this->__last_result = &$result;

        $info = [
            'con'  => &$this,
            'cols' => $this->db_num_fields($result),
            'rows' => 0,
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];
        for ($i = 0; $i < $info['cols']; $i++) {
            $info['info']['name'][] = $this->db_field_name($result, $i);
            $info['info']['type'][] = $this->db_field_type($result, $i);
        }

        $data = [];
        if ($result) {
            while ($r = $result->fetch(PDO::FETCH_ASSOC)) {
                $R = [];
                foreach ($r as $k => $v) {
                    $k     = (string) preg_replace('/^(.*)\./', '', (string) $k);
                    $R[$k] = $v;
                    $R[]   = &$R[$k];
                }
                $data[] = $R;
            }

            $info['rows'] = count($data);
            $result->closeCursor();
        }

        return new StaticRecord($data, $info);
    }

    public function begin(): void
    {
        if ($this->__link instanceof PDO) {
            $this->__link->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->__link instanceof PDO) {
            $this->__link->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->__link instanceof PDO) {
            $this->__link->rollBack();
        }
    }
}
