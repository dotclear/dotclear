<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Driver\Sqlite;

use Dotclear\Database\AbstractSchema;
use Exception;

/**
 * @class Schema
 *
 * SQLite Database schema Handler
 */
class Schema extends AbstractSchema
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $table_hist = [];

    /**
     * Stack for tables creation
     *
     * @var array<string, array<string>>
     */
    private array $table_stack = [];

    /**
     * Execution stack
     *
     * @var array<string>
     */
    private array $x_stack = [];

    /**
     * Translate DB type to universal type
     *
     * @param      string    $type     The type
     * @param      int|null  $len      The length
     * @param      mixed     $default  The default value
     *
     * @return     string
     */
    public function dbt2udt(string $type, ?int &$len, &$default): string
    {
        $type = parent::dbt2udt($type, $len, $default);

        switch ($type) {
            case 'float':
                return 'real';

            case 'double':
                return 'float';

            case 'timestamp':
                # DATETIME real type is TIMESTAMP
                if ($default === "'1970-01-01 00:00:00'") {
                    # Bad hack
                    $default = 'now()';
                }

                return 'timestamp';

            case 'integer':
            case 'mediumint':
            case 'bigint':
            case 'tinyint':
            case 'smallint':
            case 'numeric':
                return 'integer';

            case 'tinytext':
            case 'longtext':
                return 'text';
        }

        return $type;
    }

    /**
     * Translate universal type to DB type
     *
     * @param string    $type       The type
     * @param int       $len        The length
     * @param mixed     $default    The default value
     *
     * @return string
     */
    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        $type = parent::udt2dbt($type, $len, $default);

        switch ($type) {
            case 'integer':
            case 'smallint':
            case 'bigint':
                return 'integer';

            case 'real':
            case 'float:':
                return 'real';

            case 'date':
            case 'time':
                return 'timestamp';

            case 'timestamp':
                if ($default === 'now()') {
                    # SQLite does not support now() default value...
                    $default = "'1970-01-01 00:00:00'";
                }

                return $type;
        }

        return $type;
    }

    public function flushStack(): void
    {
        foreach ($this->table_stack as $table => $def) {
            $sql = 'CREATE TABLE ' . $table . ' (' . implode(', ', $def) . ')';
            $this->con->execute($sql);
        }

        foreach ($this->x_stack as $x) {
            $this->con->execute($x);
        }
    }

    /**
     * Get DB tables
     *
     * @return     array<string>
     */
    public function db_get_tables(): array
    {
        $res = [];
        $sql = "SELECT * FROM sqlite_master WHERE type = 'table'";
        $rs  = $this->con->select($sql);

        $res = [];
        while ($rs->fetch()) {
            $res[] = $rs->tbl_name;
        }

        return $res;
    }

    /**
     * Get DB table's fields
     *
     * @param   string  $table  The table name
     *
     * @return     array<string, array{type: string, len: int|null, null: bool, default: string}>
     */
    public function db_get_columns(string $table): array
    {
        $sql = 'PRAGMA table_info(' . $this->con->escapeSystem($table) . ')';
        $rs  = $this->con->select($sql);

        $res = [];
        while ($rs->fetch()) {
            $field   = trim($rs->name);
            $type    = trim($rs->type);
            $null    = $rs->notnull == 0;
            $default = trim((string) $rs->dflt_value);

            $len = null;
            if (preg_match('/^(.+?)\(([\d,]+)\)$/si', $type, $m)) {
                $type = $m[1];
                $len  = (int) $m[2];
            }

            $res[$field] = [
                'type'    => $type,
                'len'     => $len,
                'null'    => $null,
                'default' => $default,
            ];
        }

        return $res;
    }

    /**
     * Get DB table's keys
     *
     * @param   string  $table  The table name
     *
     * @return     array<array{name: string, primary: bool, unique: bool, cols: array<string>}>
     */
    public function db_get_keys(string $table): array
    {
        $res = [];

        # Get primary keys first
        $sql = "SELECT sql FROM sqlite_master WHERE type='table' AND name='" . $this->con->escapeStr($table) . "'";
        $rs  = $this->con->select($sql);

        if ($rs->isEmpty()) {
            return [];
        }

        # Get primary keys
        $n = preg_match_all('/^\s*CONSTRAINT\s+([^,]+?)\s+PRIMARY\s+KEY\s+\((.+?)\)/msi', $rs->sql, $match);
        if ($n > 0) {
            foreach ($match[1] as $i => $name) {
                $cols  = preg_split('/\s*,\s*/', $match[2][$i]);
                $res[] = [
                    'name'    => $name,
                    'primary' => true,
                    'unique'  => false,
                    'cols'    => $cols,
                ];
            }
        }

        # Get unique keys
        $n = preg_match_all('/^\s*CONSTRAINT\s+([^,]+?)\s+UNIQUE\s+\((.+?)\)/msi', $rs->sql, $match);
        if ($n > 0) {
            foreach ($match[1] as $i => $name) {
                $cols  = preg_split('/\s*,\s*/', $match[2][$i]);
                $res[] = [
                    'name'    => $name,
                    'primary' => false,
                    'unique'  => true,
                    'cols'    => $cols,
                ];
            }
        }

        return $res;    // @phpstan-ignore-line
    }

    /**
     * Get DB table's indexes
     *
     * @param   string  $table  The table name
     *
     * @return     array<array{name: string, type: string, cols: array<string>}>
     */
    public function db_get_indexes(string $table): array
    {
        $sql = 'PRAGMA index_list(' . $this->con->escapeSystem($table) . ')';
        $rs  = $this->con->select($sql);

        $res = [];
        while ($rs->fetch()) {
            if (preg_match('/^sqlite_/', $rs->name)) {
                continue;
            }

            $idx  = $this->con->select('PRAGMA index_info(' . $this->con->escapeSystem($rs->name) . ')');
            $cols = [];
            while ($idx->fetch()) {
                $cols[] = $idx->name;
            }

            $res[] = [
                'name' => $rs->name,
                'type' => 'btree',
                'cols' => $cols,
            ];
        }

        return $res;
    }

    /**
     * Get DB table's references
     *
     * @param   string  $table  The table name
     *
     * @return     array<array{name: string, c_cols: array<string>, p_table: string, p_cols: array<string>, update: string, delete: string}>
     */
    public function db_get_references(string $table): array
    {
        $sql = 'SELECT * FROM sqlite_master WHERE type=\'trigger\' AND tbl_name = \'%1$s\' AND name LIKE \'%2$s_%%\' ';
        $res = [];

        # Find constraints on table
        $bir = $this->con->select(sprintf($sql, $this->con->escapeStr($table), 'bir'));
        $bur = $this->con->select(sprintf($sql, $this->con->escapeStr($table), 'bur'));

        if ($bir->isEmpty() || $bur->isempty()) {
            return $res;
        }

        while ($bir->fetch()) {
            # Find child column and parent table and column
            if (!preg_match('/FROM\s+(.+?)\s+WHERE\s+(.+?)\s+=\s+NEW\.(.+?)\s*?\) IS\s+NULL/msi', $bir->sql, $m)) {
                continue;
            }

            $c_col   = $m[3];
            $p_table = $m[1];
            $p_col   = $m[2];

            # Find on update
            $on_update = 'restrict';
            $aur       = $this->con->select(sprintf($sql, $this->con->escapeStr($p_table), 'aur'));
            while ($aur->fetch()) {
                if (!preg_match('/AFTER\s+UPDATE/msi', $aur->sql)) {
                    continue;
                }

                if (preg_match('/UPDATE\s+' . $table . '\s+SET\s+' . $c_col . '\s*=\s*NEW.' . $p_col .
                    '\s+WHERE\s+' . $c_col . '\s*=\s*OLD\.' . $p_col . '/msi', $aur->sql)) {
                    $on_update = 'cascade';

                    break;
                }

                if (preg_match('/UPDATE\s+' . $table . '\s+SET\s+' . $c_col . '\s*=\s*NULL' .
                    '\s+WHERE\s+' . $c_col . '\s*=\s*OLD\.' . $p_col . '/msi', $aur->sql)) {
                    $on_update = 'set null';

                    break;
                }
            }

            # Find on delete
            $on_delete = 'restrict';
            $bdr       = $this->con->select(sprintf($sql, $this->con->escapeStr($p_table), 'bdr'));
            while ($bdr->fetch()) {
                if (!preg_match('/BEFORE\s+DELETE/msi', $bdr->sql)) {
                    continue;
                }

                if (preg_match('/DELETE\s+FROM\s+' . $table . '\s+WHERE\s+' . $c_col . '\s*=\s*OLD\.' . $p_col . '/msi', $bdr->sql)) {
                    $on_delete = 'cascade';

                    break;
                }

                if (preg_match('/UPDATE\s+' . $table . '\s+SET\s+' . $c_col . '\s*=\s*NULL' .
                    '\s+WHERE\s+' . $c_col . '\s*=\s*OLD\.' . $p_col . '/msi', $bdr->sql)) {
                    $on_update = 'set null';

                    break;
                }
            }

            $res[] = [
                'name'    => substr($bir->name, 4),
                'c_cols'  => [$c_col],
                'p_table' => $p_table,
                'p_cols'  => [$p_col],
                'update'  => $on_update,
                'delete'  => $on_delete,
            ];
        }

        return $res;
    }

    /**
     * Create table
     *
     * @param      string                   $name    The name
     * @param      array<string, mixed>     $fields  The fields
     */
    public function db_create_table(string $name, array $fields): void
    {
        $a = [];

        foreach ($fields as $n => $f) {
            $type    = $f['type'];
            $len     = (int) $f['len'];
            $default = $f['default'];
            $null    = $f['null'];

            $type = $this->udt2dbt($type, $len, $default);
            $len  = $len > 0 ? '(' . $len . ')' : '';
            $null = $null ? 'NULL' : 'NOT NULL';

            if ($default === null) {
                $default = 'DEFAULT NULL';
            } elseif ($default !== false) {
                $default = 'DEFAULT ' . $default . ' ';
            } else {
                $default = '';
            }

            $a[] = $n . ' ' . $type . $len . ' ' . $null . ' ' . $default;
        }

        $this->table_stack[$name][] = implode(', ', $a);
        $this->table_hist[$name]    = $fields;
    }

    /**
     * Create a field
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $type     The type
     * @param      int|null         $len      The length
     * @param      bool             $null     The null
     * @param      mixed            $default  The default
     */
    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type = $this->udt2dbt($type, $len, $default);

        if ($default === null) {
            $default = 'DEFAULT NULL';
        } elseif ($default !== false) {     // @phpstan-ignore-line
            $default = 'DEFAULT ' . $default . ' ';
        } else {
            $default = '';
        }

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ADD COLUMN ' . $this->con->escapeSystem($name) . ' ' . $type . ($len > 0 ? '(' . $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    /**
     * Create a primary key
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function db_create_primary(string $table, string $name, array $fields): void
    {
        $this->table_stack[$table][] = 'CONSTRAINT ' . $name . ' PRIMARY KEY (' . implode(',', $fields) . ')';
    }

    /**
     * Create a unique key
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function db_create_unique(string $table, string $name, array $fields): void
    {
        $this->table_stack[$table][] = 'CONSTRAINT ' . $name . ' UNIQUE (' . implode(',', $fields) . ')';
    }

    /**
     * Create an index
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      string           $type   The type
     * @param      array<string>    $fields The fields
     */
    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
        $this->x_stack[] = 'CREATE INDEX ' . $name . ' ON ' . $table . ' (' . implode(',', $fields) . ')';
    }

    /**
     * Create reference
     *
     * @param      string           $name            The name
     * @param      string           $table           The table
     * @param      array<string>    $fields          The fields
     * @param      string           $foreign_table   The foreign table
     * @param      array<string>    $foreign_fields  The foreign fields
     * @param      false|string     $update          The update
     * @param      false|string     $delete          The delete
     *
     * @throws     Exception
     */
    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        if (!isset($this->table_hist[$table])) {
            return;
        }

        if (count($fields) > 1 || count($foreign_fields) > 1) {
            throw new Exception('SQLite UDBS does not support multiple columns foreign keys');
        }

        $c_col = $fields[0];
        $p_col = $foreign_fields[0];

        $update = $update !== false ? strtolower($update) : '';
        $delete = $delete !== false ? strtolower($delete) : '';

        $cnull = $this->table_hist[$table][$c_col]['null'];

        # Create constraint
        $this->x_stack[] = 'CREATE TRIGGER bir_' . $name . ' ' .
            'BEFORE INSERT ON ' . $table . ' ' .
            'FOR EACH ROW BEGIN ' .
            'SELECT RAISE(ROLLBACK,\'insert on table "' . $table . '" violates foreign key constraint "' . $name . '"\')' . ' ' .
            'WHERE ' .
            ($cnull ? 'NEW.' . $c_col . ' IS NOT NULL AND ' : '') .
            '(SELECT ' . $p_col . ' FROM ' . $foreign_table . ' WHERE ' . $p_col . ' = NEW.' . $c_col . ') IS NULL; ' .
            'END;';

        # Update constraint
        $this->x_stack[] = 'CREATE TRIGGER bur_' . $name . ' ' .
            'BEFORE UPDATE ON ' . $table . ' ' .
            'FOR EACH ROW BEGIN ' .
            'SELECT RAISE(ROLLBACK,\'update on table "' . $table . '" violates foreign key constraint "' . $name . '"\')' . ' ' .
            'WHERE ' .
            ($cnull ? 'NEW.' . $c_col . ' IS NOT NULL AND ' : '') .
            '(SELECT ' . $p_col . ' FROM ' . $foreign_table . ' WHERE ' . $p_col . ' = NEW.' . $c_col . ') IS NULL; ' .
            'END;';

        # ON UPDATE
        if ($update === 'cascade') {
            $this->x_stack[] = 'CREATE TRIGGER aur_' . $name . ' ' .
                'AFTER UPDATE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'UPDATE ' . $table . ' SET ' . $c_col . ' = NEW.' . $p_col . ' WHERE ' . $c_col . ' = OLD.' . $p_col . '; ' .
                'END;';
        } elseif ($update === 'set null') {
            $this->x_stack[] = 'CREATE TRIGGER aur_' . $name . ' ' .
                'AFTER UPDATE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'UPDATE ' . $table . ' SET ' . $c_col . ' = NULL WHERE ' . $c_col . ' = OLD.' . $p_col . '; ' .
                'END;';
        } else { # default on restrict
            $this->x_stack[] = 'CREATE TRIGGER burp_' . $name . ' ' .
                'BEFORE UPDATE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'SELECT RAISE (ROLLBACK,\'update on table "' . $foreign_table . '" violates foreign key constraint "' . $name . '"\')' . ' ' .
                'WHERE (SELECT ' . $c_col . ' FROM ' . $table . ' WHERE ' . $c_col . ' = OLD.' . $p_col . ') IS NOT NULL; ' .
                'END;';
        }

        # ON DELETE
        if ($delete === 'cascade') {
            $this->x_stack[] = 'CREATE TRIGGER bdr_' . $name . ' ' .
                'BEFORE DELETE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'DELETE FROM ' . $table . ' WHERE ' . $c_col . ' = OLD.' . $p_col . '; ' .
                'END;';
        } elseif ($delete === 'set null') {
            $this->x_stack[] = 'CREATE TRIGGER bdr_' . $name . ' ' .
                'BEFORE DELETE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'UPDATE ' . $table . ' SET ' . $c_col . ' = NULL WHERE ' . $c_col . ' = OLD.' . $p_col . '; ' .
                'END;';
        } else {
            $this->x_stack[] = 'CREATE TRIGGER bdr_' . $name . ' ' .
                'BEFORE DELETE ON ' . $foreign_table . ' ' .
                'FOR EACH ROW BEGIN ' .
                'SELECT RAISE (ROLLBACK,\'delete on table "' . $foreign_table . '" violates foreign key constraint "' . $name . '"\')' . ' ' .
                'WHERE (SELECT ' . $c_col . ' FROM ' . $table . ' WHERE ' . $c_col . ' = OLD.' . $p_col . ') IS NOT NULL; ' .
                'END;';
        }
    }

    /**
     * Modify a field
     *
     * @param      string     $table    The table
     * @param      string     $name     The name
     * @param      string     $type     The type
     * @param      int|null   $len      The length
     * @param      bool       $null     The null
     * @param      mixed      $default  The default
     *
     * @throws     Exception
     */
    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type = $this->udt2dbt($type, $len, $default);
        if ($type != 'integer' && $type != 'text' && $type != 'timestamp') {
            throw new Exception('SQLite fields cannot be changed.');
        }
    }

    /**
     * Modify a primary key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The fields
     *
     * @throws     Exception
     * @return never
     */
    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
        throw new Exception('SQLite primary key cannot be changed.');
    }

    /**
     * Modify a unique key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The fields
     *
     * @throws     Exception
     * @return never
     */
    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
        throw new Exception('SQLite unique index cannot be changed.');
    }

    /**
     * Modify an index
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      string           $type     The type
     * @param      array<string>    $fields   The fields
     */
    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $this->con->execute('DROP INDEX IF EXISTS ' . $name);
        $this->con->execute('CREATE INDEX ' . $newname . ' ON ' . $table . ' (' . implode(',', $fields) . ')');
    }

    /**
     * Modify a reference (foreign key)
     *
     * @param      string           $name            The name
     * @param      string           $newname         The newname
     * @param      string           $table           The table
     * @param      array<string>    $fields          The fields
     * @param      string           $foreign_table   The foreign table
     * @param      array<string>    $foreign_fields  The foreign fields
     * @param      string|false     $update          The update
     * @param      string|false     $delete          The delete
     */
    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->con->execute('DROP TRIGGER IF EXISTS bur_' . $name);
        $this->con->execute('DROP TRIGGER IF EXISTS burp_' . $name);
        $this->con->execute('DROP TRIGGER IF EXISTS bir_' . $name);
        $this->con->execute('DROP TRIGGER IF EXISTS aur_' . $name);
        $this->con->execute('DROP TRIGGER IF EXISTS bdr_' . $name);

        $this->table_hist[$table] = $this->db_get_columns($table);
        $this->db_create_reference($newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    /**
     * Remove a unique key
     *
     * @param      string     $table  The table
     * @param      string     $name   The name
     *
     * @throws     Exception
     * @return never
     */
    public function db_drop_unique(string $table, string $name): void
    {
        throw new Exception('SQLite unique index cannot be removed.');
    }
}
