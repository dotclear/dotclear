<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Driver\Mysqli;

use Dotclear\Database\AbstractSchema;

/**
 * @class Schema
 *
 * MySQL Database schema Handler
 */
class Schema extends AbstractSchema
{
    /**
     * Translate DB type to universal type
     *
     * @param      string       $type     The type
     * @param      int|null     $len      The length
     * @param      mixed        $default  The default valule
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
            case 'datetime':
                # DATETIME real type is TIMESTAMP
                if ($default == "'1970-01-01 00:00:00'") {
                    # Bad hack
                    $default = 'now()';
                }

                return 'timestamp';
            case 'integer':
            case 'mediumint':
                if ($len == 11) {
                    $len = 0;
                }

                return 'integer';
            case 'bigint':
                if ($len == 20) {
                    $len = 0;
                }

                break;
            case 'tinyint':
            case 'smallint':
                if ($len == 6) {
                    $len = 0;
                }

                return 'smallint';
            case 'numeric':
                $len = 0;

                break;
            case 'tinytext':
            case 'longtext':
                return 'text';
        }

        return $type;
    }

    /**
     * Translate universal type to DB type
     *
     * @param      string       $type     The type
     * @param      int|null     $len      The length
     * @param      mixed        $default  The default value
     *
     * @return     string
     */
    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        $type = parent::udt2dbt($type, $len, $default);

        switch ($type) {
            case 'real':
                return 'float';
            case 'float':
                return 'double';
            case 'timestamp':
                if ($default == 'now()') {
                    # MySQL does not support now() default value...
                    $default = "'1970-01-01 00:00:00'";
                }

                return 'datetime';
            case 'text':
                $len = 0;

                return 'longtext';
        }

        return $type;
    }

    /**
     * Get DB tables
     *
     * @return     array<string>
     */
    public function db_get_tables(): array
    {
        $sql = 'SHOW TABLES';
        $rs  = $this->con->select($sql);

        $res = [];
        while ($rs->fetch()) {
            $res[] = $rs->f(0);
        }

        return $res;
    }

    /**
     * Get table fields
     *
     * @param      string  $table  The table
     *
     * @return     array<string, array{type: string, len: int|null, null: bool, default: string}>
     */
    public function db_get_columns(string $table): array
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->con->escapeSystem($table);
        $rs  = $this->con->select($sql);

        $res = [];
        while ($rs->fetch()) {
            $field   = trim((string) $rs->f('Field'));
            $type    = trim((string) $rs->f('Type'));
            $null    = strtolower((string) $rs->f('Null')) == 'yes';
            $default = $rs->f('Default');

            $len = null;
            if (preg_match('/^(.+?)\(([\d,]+)\)$/si', $type, $m)) {
                $type = $m[1];
                $len  = (int) $m[2];
            }

            // $default from db is a string and is NULL in schema so upgrade failed.
            if (strtoupper((string) $default) == 'NULL') {
                $default = null;
            } elseif ($default != '' && !is_numeric($default)) {
                $default = "'" . $default . "'";
            }

            $res[$field] = [
                'type'    => $type,
                'len'     => $len,
                'null'    => $null,
                'default' => (string) $default,
            ];
        }

        return $res;
    }

    /**
     * Get table keys
     *
     * @param      string  $table  The table
     *
     * @return     array<array{name: int|string, primary: bool, unique: bool, cols: array<string>}>
     */
    public function db_get_keys(string $table): array
    {
        $sql = 'SHOW INDEX FROM ' . $this->con->escapeSystem($table);
        $rs  = $this->con->select($sql);

        $t   = [];
        $res = [];
        while ($rs->fetch()) {
            $key_name = $rs->f('Key_name');
            $unique   = $rs->f('Non_unique') == 0;
            $seq      = $rs->f('Seq_in_index');
            $col_name = $rs->f('Column_name');

            if ($key_name == 'PRIMARY' || $unique) {
                $t[$key_name]['cols'][$seq] = $col_name;
                $t[$key_name]['unique']     = $unique;
            }
        }

        foreach ($t as $name => $idx) {
            ksort($idx['cols']);

            $res[] = [
                'name'    => $name,
                'primary' => $name == 'PRIMARY',
                'unique'  => $idx['unique'],
                'cols'    => array_values($idx['cols']),
            ];
        }

        return $res;
    }

    /**
     * Get table's indexes
     *
     * @param      string  $table  The table
     *
     * @return     array<array{name: int|string, type: string, cols: array<string>}>
     */
    public function db_get_indexes(string $table): array
    {
        $sql = 'SHOW INDEX FROM ' . $this->con->escapeSystem($table);
        $rs  = $this->con->select($sql);

        $t   = [];
        $res = [];
        while ($rs->fetch()) {
            $key_name = $rs->f('Key_name');
            $unique   = $rs->f('Non_unique') == 0;
            $seq      = $rs->f('Seq_in_index');
            $col_name = $rs->f('Column_name');
            $type     = $rs->f('Index_type');

            if ($key_name != 'PRIMARY' && !$unique) {
                $t[$key_name]['cols'][$seq] = $col_name;
                $t[$key_name]['type']       = $type;
            }
        }

        foreach ($t as $name => $idx) {
            ksort($idx['cols']);

            $res[] = [
                'name' => $name,
                'type' => $idx['type'],
                'cols' => $idx['cols'],
            ];
        }

        return $res;
    }

    /**
     * Get references
     *
     * @param      string  $table  The table
     *
     * @return     array<array{name: string, c_cols: array<string>, p_table: string, p_cols: array<string>, update: string|null, delete: string|null}>
     */
    public function db_get_references(string $table): array
    {
        $sql = 'SHOW CREATE TABLE ' . $this->con->escapeSystem($table);
        $rs  = $this->con->select($sql);

        $s = $rs->f(1);

        $res = [];

        $n = preg_match_all('/^\s*CONSTRAINT\s+`(.+?)`\s+FOREIGN\s+KEY\s+\((.+?)\)\s+REFERENCES\s+`(.+?)`\s+\((.+?)\)(.*?)$/msi', (string) $s, $match);
        if ($n > 0) {
            foreach ($match[1] as $i => $name) {
                # Columns transformation
                $st_cols = (string) str_replace('`', '', $match[2][$i]);
                $t_cols  = explode(',', $st_cols);
                $sr_cols = (string) str_replace('`', '', $match[4][$i]);
                $r_cols  = explode(',', $sr_cols);

                # ON UPDATE|DELETE
                $on        = trim((string) $match[5][$i], ', ');
                $on_delete = null;
                $on_update = null;
                if ($on != '') {
                    if (preg_match('/ON DELETE (.+?)(?:\s+ON|$)/msi', $on, $m)) {
                        $on_delete = strtolower(trim((string) $m[1]));
                    }
                    if (preg_match('/ON UPDATE (.+?)(?:\s+ON|$)/msi', $on, $m)) {
                        $on_update = strtolower(trim((string) $m[1]));
                    }
                }

                $res[] = [
                    'name'    => $name,
                    'c_cols'  => $t_cols,
                    'p_table' => $match[3][$i],
                    'p_cols'  => $r_cols,
                    'update'  => $on_update,
                    'delete'  => $on_delete,
                ];
            }
        }

        return $res;
    }

    /**
     * Create a table
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

            $a[] = $this->con->escapeSystem($n) . ' ' .
                $type . $len . ' ' . $null . ' ' . $default;
        }

        $sql = 'CREATE TABLE ' . $this->con->escapeSystem($name) . " (\n" .
        implode(",\n", $a) .
            "\n) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ";

        $this->con->execute($sql);
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
        } elseif ($default !== false) {
            $default = 'DEFAULT ' . $default;
        } else {
            $default = '';
        }

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ADD COLUMN ' . $this->con->escapeSystem($name) . ' ' . $type . ((int) $len > 0 ? '(' . (int) $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    /**
     * Create a primary key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      array<string>    $fields   The cols
     */
    public function db_create_primary(string $table, string $name, array $fields): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT PRIMARY KEY (' . implode(',', $c) . ') ';

        $this->con->execute($sql);
    }

    /**
     * Create a unique key
     *
     * @param      string           $table   The table
     * @param      string           $name    The name
     * @param      array<string>    $fields  The fields
     */
    public function db_create_unique(string $table, string $name, array $fields): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT UNIQUE KEY ' . $this->con->escapeSystem($name) . ' ' .
        '(' . implode(',', $c) . ') ';

        $this->con->execute($sql);
    }

    /**
     * Create an index
     *
     * @param      string           $table   The table
     * @param      string           $name    The name
     * @param      string           $type    The type
     * @param      array<string>    $fields  The fields
     */
    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD INDEX ' . $this->con->escapeSystem($name) . ' USING ' . $type . ' ' .
        '(' . implode(',', $c) . ') ';

        $this->con->execute($sql);
    }

    /**
     * Create a reference
     *
     * @param      string           $name            The name
     * @param      string           $table           The table
     * @param      array<string>    $fields          The fields
     * @param      string           $foreign_table   The foreign table
     * @param      array<string>    $foreign_fields  The foreign fields
     * @param      string|bool      $update          The update
     * @param      string|bool      $delete          The delete
     */
    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);
        $p = array_map(fn ($field) => $this->con->escapeSystem($field), $foreign_fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT ' . $name . ' FOREIGN KEY ' .
        '(' . implode(',', $c) . ') ' .
        'REFERENCES ' . $this->con->escapeSystem($foreign_table) . ' ' .
        '(' . implode(',', $p) . ') ';

        if ($update) {
            $sql .= 'ON UPDATE ' . $update . ' ';
        }
        if ($delete) {
            $sql .= 'ON DELETE ' . $delete . ' ';
        }

        $this->con->execute($sql);
    }

    /**
     * Modify a field
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $type     The type
     * @param      int|null         $len      The length
     * @param      bool             $null     The null
     * @param      mixed            $default  The default
     */
    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type = $this->udt2dbt($type, $len, $default);

        if ($default === null) {
            $default = 'DEFAULT NULL';
        } elseif ($default !== false) {
            $default = 'DEFAULT ' . $default;
        } else {
            $default = '';
        }

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'CHANGE COLUMN ' . $this->con->escapeSystem($name) . ' ' . $this->con->escapeSystem($name) . ' ' . $type . ($len > 0 ? '(' . $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    /**
     * Modify a primary key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The cols
     */
    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP PRIMARY KEY, ADD PRIMARY KEY ' .
        '(' . implode(',', $c) . ') ';

        $this->con->execute($sql);
    }

    /**
     * Modify a unique key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The fields
     */
    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name) . ', ' .
        'ADD UNIQUE ' . $this->con->escapeSystem($newname) . ' ' .
        '(' . implode(',', $c) . ') ';

        $this->con->execute($sql);
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
        $c = array_map(fn ($field) => $this->con->escapeSystem($field), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name) . ', ' .
        'ADD INDEX ' . $this->con->escapeSystem($newname) . ' ' .
        'USING ' . $type . ' ' .
        '(' . implode(',', $c) . ') ';

        $this->con->execute($sql);
    }

    /**
     * Modify a reference
     *
     * @param      string           $name            The name
     * @param      string           $newname         The newname
     * @param      string           $table           The table
     * @param      array<string>    $fields          The fields
     * @param      string           $foreign_table   The foreign table
     * @param      array<string>    $foreign_fields  The foreign fields
     * @param      false|string     $update          The update
     * @param      false|string     $delete          The delete
     */
    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP FOREIGN KEY ' . $this->con->escapeSystem($name);

        $this->con->execute($sql);
        $this->createReference($newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    /**
     * Remove a unique key
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     */
    public function db_drop_unique(string $table, string $name): void
    {
        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name);
        $this->con->execute($sql);
    }
}
