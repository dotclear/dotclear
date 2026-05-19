<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\Mysqli;

use Dotclear\Database\AbstractSchema;
use Dotclear\Database\MetaRecord;

/**
 * @class Schema
 *
 * MySQL Database schema Handler
 */
class Schema extends AbstractSchema
{
    public function dbt2udt(string $type, int &$len, &$default): string
    {
        $type = parent::dbt2udt($type, $len, $default);

        switch ($type) {
            case 'float':
                return 'real';
            case 'double':
                return 'float';
            case 'datetime':
                // DATETIME real type is TIMESTAMP
                if ($default == "'1970-01-01 00:00:00'") {
                    // Bad hack for MySQL < 5.7
                    $default = 'now()';
                }

                return 'timestamp';
            case 'integer':
            case 'mediumint':
                if ($len === 11) {
                    $len = 0;
                }

                return 'integer';
            case 'bigint':
                if ($len === 20) {
                    $len = 0;
                }

                break;
            case 'tinyint':
            case 'smallint':
                if ($len === 6) {
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

    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        $type = parent::udt2dbt($type, $len, $default);

        switch ($type) {
            case 'real':
                return 'float';
            case 'float':
                return 'double';
            case 'timestamp':
                if ($default === 'now()') {
                    // MySQL < 5.7 does not support now() default value...
                    $default = "'1970-01-01 00:00:00'";
                }

                return 'datetime';
            case 'text':
                $len = 0;

                return 'longtext';
        }

        return $type;
    }

    public function db_get_tables(): array
    {
        $sql = 'SHOW TABLES';
        $rs  = new MetaRecord($this->con->select($sql));

        $res = [];
        while ($rs->fetch()) {
            $name = $rs->strField(0);
            if ($name !== '') {
                $res[] = $name;
            }
        }

        return $res;
    }

    public function db_get_columns(string $table): array
    {
        $sql = 'SHOW COLUMNS FROM ' . $this->con->escapeSystem($table);
        $rs  = new MetaRecord($this->con->select($sql));

        /**
         * @var array<string, array{type: string, len: int, null: bool, default: mixed}>
         */
        $res = [];
        while ($rs->fetch()) {
            $field   = trim($rs->strField('Field'));
            $type    = trim($rs->strField('Type'));
            $null    = strtolower($rs->strField('Null')) === 'yes';
            $default = $rs->field('Default');

            $len = 0;
            if (preg_match('/^(.+?)\(([\d,]+)\)$/si', $type, $m)) {
                $type = $m[1];
                $len  = (int) $m[2];
            }

            // $default from db is a string and is NULL in schema so upgrade failed.
            if (is_string($default) && strtoupper($default) === 'NULL') {
                $default = null;
            } elseif (is_string($default) && $default !== '' && !is_numeric($default)) {
                $default = "'" . $default . "'";
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

    public function db_get_keys(string $table): array
    {
        $sql = 'SHOW INDEX FROM ' . $this->con->escapeSystem($table);
        $rs  = new MetaRecord($this->con->select($sql));

        $t   = [];
        $res = [];
        while ($rs->fetch()) {
            $key_name = $rs->strField('Key_name');
            $unique   = !$rs->boolField('Non_unique');
            $seq      = $rs->intField('Seq_in_index');
            $col_name = $rs->strField('Column_name');

            if ($key_name === 'PRIMARY' || $unique) {
                $t[$key_name]['cols'][$seq] = $col_name;
                $t[$key_name]['unique']     = $unique;
            }
        }

        foreach ($t as $name => $idx) {
            ksort($idx['cols']);

            $res[] = [
                'name'    => $name,
                'primary' => $name === 'PRIMARY',
                'unique'  => $idx['unique'],
                'cols'    => array_values($idx['cols']),
            ];
        }

        return $res;
    }

    public function db_get_indexes(string $table): array
    {
        $sql = 'SHOW INDEX FROM ' . $this->con->escapeSystem($table);
        $rs  = new MetaRecord($this->con->select($sql));

        $t   = [];
        $res = [];
        while ($rs->fetch()) {
            $key_name = $rs->strField('Key_name');
            $unique   = !$rs->boolField('Non_unique');
            $seq      = $rs->intField('Seq_in_index');
            $col_name = $rs->strField('Column_name');
            $type     = $rs->strField('Index_type');

            if ($key_name !== 'PRIMARY' && !$unique) {
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

    public function db_get_references(string $table): array
    {
        $sql = 'SHOW CREATE TABLE ' . $this->con->escapeSystem($table);
        $rs  = new MetaRecord($this->con->select($sql));

        $s = $rs->strField(1);

        $res = [];

        $n = preg_match_all('/^\s*CONSTRAINT\s+`(.+?)`\s+FOREIGN\s+KEY\s+\((.+?)\)\s+REFERENCES\s+`(.+?)`\s+\((.+?)\)(.*?)$/msi', (string) $s, $match);
        if ($n > 0) {
            foreach ($match[1] as $i => $name) {
                # Columns transformation
                $st_cols = str_replace('`', '', $match[2][$i]);
                $t_cols  = explode(',', $st_cols);
                $sr_cols = str_replace('`', '', $match[4][$i]);
                $r_cols  = explode(',', $sr_cols);

                # ON UPDATE|DELETE
                $on        = trim($match[5][$i], ', ');
                $on_delete = null;
                $on_update = null;
                if ($on !== '') {
                    if (preg_match('/ON DELETE (.+?)(?:\s+ON|$)/msi', $on, $m)) {
                        $on_delete = strtolower(trim($m[1]));
                    }
                    if (preg_match('/ON UPDATE (.+?)(?:\s+ON|$)/msi', $on, $m)) {
                        $on_update = strtolower(trim($m[1]));
                    }
                }

                $res[] = [
                    'name'    => $name,
                    'c_cols'  => $t_cols,
                    'p_table' => $match[3][$i],
                    'p_cols'  => $r_cols,
                    'update'  => (string) $on_update,
                    'delete'  => (string) $on_delete,
                ];
            }
        }

        return $res;
    }

    public function db_create_table(string $name, array $fields): void
    {
        $a = [];

        foreach ($fields as $n => $f) {
            $type    = $f['type'];
            $len     = $f['len'];
            $default = $f['default'];
            $null    = $f['null'];

            $type = $this->udt2dbt($type, $len, $default);
            $len  = $len > 0 ? '(' . $len . ')' : '';
            $null = $null ? 'NULL' : 'NOT NULL';

            $default = $this->con->formatValue($default);
            if ($default !== '') {
                $default = 'DEFAULT ' . $default;
            }

            $a[] = $this->con->escapeSystem($n) . ' ' .
                $type . $len . ' ' . $null . ' ' . $default;
        }

        $sql = 'CREATE TABLE ' . $this->con->escapeSystem($name) . " (\n" .
        implode(",\n", $a) .
            "\n) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin";

        $this->con->execute($sql);
    }

    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type = $this->udt2dbt($type, $len, $default);

        $default = $this->con->formatValue($default);
        if ($default !== '') {
            $default = 'DEFAULT ' . $default;
        }

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ADD COLUMN ' . $this->con->escapeSystem($name) . ' ' . $type . ((int) $len > 0 ? '(' . (int) $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    public function db_create_primary(string $table, string $name, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT PRIMARY KEY (' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_create_unique(string $table, string $name, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT UNIQUE KEY ' . $this->con->escapeSystem($name) . ' ' .
        '(' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD INDEX ' . $this->con->escapeSystem($name) . ' USING ' . $type . ' ' .
        '(' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);
        $p = array_map($this->con->escapeSystem(...), $foreign_fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'ADD CONSTRAINT ' . $name . ' FOREIGN KEY ' .
        '(' . implode(',', $c) . ') ' .
        'REFERENCES ' . $this->con->escapeSystem($foreign_table) . ' ' .
        '(' . implode(',', $p) . ')';

        if ($update) {
            $sql .= ' ON UPDATE ' . $update;
        }
        if ($delete) {
            $sql .= ' ON DELETE ' . $delete;
        }

        $this->con->execute($sql);
    }

    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type = $this->udt2dbt($type, $len, $default);

        $default = $this->con->formatValue($default);
        if ($default !== '') {
            $default = 'DEFAULT ' . $default;
        }

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'CHANGE COLUMN ' . $this->con->escapeSystem($name) . ' ' . $this->con->escapeSystem($name) . ' ' . $type . ($len > 0 ? '(' . $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP PRIMARY KEY, ADD PRIMARY KEY ' .
        '(' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name) . ', ' .
        'ADD UNIQUE ' . $this->con->escapeSystem($newname) . ' ' .
        '(' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $c = array_map($this->con->escapeSystem(...), $fields);

        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name) . ', ' .
        'ADD INDEX ' . $this->con->escapeSystem($newname) . ' ' .
        'USING ' . $type . ' ' .
        '(' . implode(',', $c) . ')';

        $this->con->execute($sql);
    }

    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP FOREIGN KEY ' . $this->con->escapeSystem($name);

        $this->con->execute($sql);
        $this->createReference($newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function db_drop_unique(string $table, string $name): void
    {
        $sql = 'ALTER TABLE ' . $this->con->escapeSystem($table) . ' ' .
        'DROP INDEX ' . $this->con->escapeSystem($name);
        $this->con->execute($sql);
    }
}
