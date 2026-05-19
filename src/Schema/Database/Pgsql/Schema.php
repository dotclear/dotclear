<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Database\Pgsql;

use Dotclear\Database\AbstractSchema;
use Dotclear\Database\MetaRecord;

/**
 * @class Schema
 *
 * PostgreSQL Database schema Handler
 */
class Schema extends AbstractSchema
{
    /**
     * @var        array<string, string>    $ref_actions_map
     */
    protected $ref_actions_map = [
        'a' => 'no action',
        'r' => 'restrict',
        'c' => 'cascade',
        'n' => 'set null',
        'd' => 'set default',
    ];

    public function db_get_tables(): array
    {
        $sql = 'SELECT table_name ' .
            'FROM information_schema.tables ' .
            'WHERE table_schema = current_schema() ';

        $rs = new MetaRecord($this->con->select($sql));

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
        $sql = 'SELECT column_name, udt_name, character_maximum_length, ' .
        'is_nullable, column_default ' .
        'FROM information_schema.columns ' .
        "WHERE table_name = '" . $this->con->escapeStr($table) . "' ";

        $rs = new MetaRecord($this->con->select($sql));

        /**
         * @var array<string, array{type: string, len: int, null: bool, default: mixed}>
         */
        $res = [];
        while ($rs->fetch()) {
            $field   = trim($rs->strField('column_name'));
            $type    = trim($rs->strField('udt_name'));
            $null    = strtolower($rs->strField('is_nullable')) === 'yes';
            $default = $rs->field('column_default');
            $len     = $rs->intField('character_maximum_length');

            if (is_string($default)) {
                $default = (string) preg_replace('/::([\w\d\s]*)$/', '', $default);
                $default = (string) preg_replace('/^\((-?\d*)\)$/', '$1', $default);

                // $default from db is a string and is NULL in schema so upgrade failed.
                if (strtoupper($default) === 'NULL') {
                    $default = null;
                }
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
        $sql = 'SELECT DISTINCT ON(cls.relname) cls.oid, cls.relname as idxname, indisunique::integer, indisprimary::integer, ' .
        'indnatts, tab.relname as tabname, contype, amname ' .
        'FROM pg_index idx ' .
        'JOIN pg_class cls ON cls.oid=indexrelid ' .
        'JOIN pg_class tab ON tab.oid=indrelid ' .
        'LEFT OUTER JOIN pg_tablespace ta on ta.oid=cls.reltablespace ' .
        'JOIN pg_namespace n ON n.oid=tab.relnamespace ' .
        'JOIN pg_am am ON am.oid=cls.relam ' .
        "LEFT JOIN pg_depend dep ON (dep.classid = cls.tableoid AND dep.objid = cls.oid AND dep.refobjsubid = '0') " .
        'LEFT OUTER JOIN pg_constraint con ON (con.tableoid = dep.refclassid AND con.oid = dep.refobjid) ' .
        'LEFT OUTER JOIN pg_description des ON des.objoid=con.oid ' .
        'LEFT OUTER JOIN pg_description desp ON (desp.objoid=con.oid AND desp.objsubid = 0) ' .
        "WHERE tab.relname = '" . $this->con->escapeStr($table) . "' " .
            "AND contype IN ('p','u') " .
            'ORDER BY cls.relname ';

        $rs = new MetaRecord($this->con->select($sql));

        $res = [];
        while ($rs->fetch()) {
            $k = [
                'name'    => $rs->strField('idxname'),
                'primary' => $rs->boolField('indisprimary'),
                'unique'  => $rs->boolField('indisunique'),
                'cols'    => [],
            ];

            $indnatts = $rs->intField('indnatts');
            $oid      = $rs->intField('oid');   // oid may be cast to int with PostgreSQL
            for ($i = 1; $i <= $indnatts; $i++) {
                $cols        = new MetaRecord($this->con->select('SELECT pg_get_indexdef(' . $oid . '::oid, ' . $i . ', true);'));
                $k['cols'][] = $cols->strField(0);
            }

            $res[] = $k;
        }

        return $res;
    }

    public function db_get_indexes(string $table): array
    {
        $sql = 'SELECT DISTINCT ON(cls.relname) cls.oid, cls.relname as idxname, n.nspname, ' .
        'indnatts, tab.relname as tabname, contype, amname ' .
        'FROM pg_index idx ' .
        'JOIN pg_class cls ON cls.oid=indexrelid ' .
        'JOIN pg_class tab ON tab.oid=indrelid ' .
        'LEFT OUTER JOIN pg_tablespace ta on ta.oid=cls.reltablespace ' .
        'JOIN pg_namespace n ON n.oid=tab.relnamespace ' .
        'JOIN pg_am am ON am.oid=cls.relam ' .
        "LEFT JOIN pg_depend dep ON (dep.classid = cls.tableoid AND dep.objid = cls.oid AND dep.refobjsubid = '0') " .
        'LEFT OUTER JOIN pg_constraint con ON (con.tableoid = dep.refclassid AND con.oid = dep.refobjid) ' .
        'LEFT OUTER JOIN pg_description des ON des.objoid=con.oid ' .
        'LEFT OUTER JOIN pg_description desp ON (desp.objoid=con.oid AND desp.objsubid = 0) ' .
        "WHERE tab.relname = '" . $this->con->escapeStr($table) . "' " .
            'AND conname IS NULL ' .
            'ORDER BY cls.relname ';

        $rs = new MetaRecord($this->con->select($sql));

        $res = [];
        while ($rs->fetch()) {
            $k = [
                'name' => $rs->strField('idxname'),
                'type' => $rs->strField('amname'),
                'cols' => [],
            ];

            $indnatts = $rs->intField('indnatts');
            $oid      = $rs->intField('oid');   // oid may be cast to int with PostgreSQL
            for ($i = 1; $i <= $indnatts; $i++) {
                $cols        = new MetaRecord($this->con->select('SELECT pg_get_indexdef(' . $oid . '::oid, ' . $i . ', true);'));
                $k['cols'][] = $cols->strField(0);
            }

            $res[] = $k;
        }

        return $res;
    }

    public function db_get_references(string $table): array
    {
        $sql = 'SELECT ct.oid, conname, condeferrable, condeferred, confupdtype, ' .
        'confdeltype, confmatchtype, conkey, confkey, conrelid, confrelid, cl.relname as fktab, ' .
        'cr.relname as reftab ' .
        'FROM pg_constraint ct ' .
        'JOIN pg_class cl ON cl.oid=conrelid ' .
        'JOIN pg_namespace nl ON nl.oid=cl.relnamespace ' .
        'JOIN pg_class cr ON cr.oid=confrelid ' .
        'JOIN pg_namespace nr ON nr.oid=cr.relnamespace ' .
        "WHERE contype='f' " .
        "AND cl.relname = '" . $this->con->escapeStr($table) . "' " .
        'ORDER BY conname ';

        $rs = new MetaRecord($this->con->select($sql));

        $cols_sql = 'SELECT a1.attname as conattname, a2.attname as confattname ' .
            'FROM pg_attribute a1, pg_attribute a2 ' .
            'WHERE a1.attrelid=%1$s::oid AND a1.attnum=%2$s ' .
            'AND a2.attrelid=%3$s::oid AND a2.attnum=%4$s ';

        $res = [];
        while ($rs->fetch()) {
            $conkey  = (string) preg_replace('/[^\d]/', '', $rs->strField('conkey'));
            $confkey = (string) preg_replace('/[^\d]/', '', $rs->strField('confkey'));

            $k = [
                'name'    => $rs->strField('conname'),
                'c_cols'  => [],
                'p_table' => $rs->strField('reftab'),
                'p_cols'  => [],
                'update'  => $this->ref_actions_map[$rs->strField('confupdtype')],
                'delete'  => $this->ref_actions_map[$rs->strField('confdeltype')],
            ];

            $conrelid  = $rs->intField('conrelid');
            $confrelid = $rs->intField('confrelid');
            $cols      = new MetaRecord($this->con->select(sprintf($cols_sql, $conrelid, $conkey, $confrelid, $confkey)));
            while ($cols->fetch()) {
                $k['c_cols'][] = $cols->strField('conattname');
                $k['p_cols'][] = $cols->strField('confattname');
            }

            $res[] = $k;
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

            $a[] = $n . ' ' .
                $type . $len . ' ' . $null . ' ' . $default;
        }

        $sql = 'CREATE TABLE ' . $this->con->escapeSystem($name) . " (\n" .
        implode(",\n", $a) .
            "\n)";

        $this->con->execute($sql);
    }

    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $type    = $this->udt2dbt($type, $len, $default);
        $default = $this->con->formatValue($default);
        if ($default !== '') {
            $default = 'DEFAULT ' . $default;
        }

        $sql = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $name . ' ' . $type . ($len > 0 ? '(' . $len . ')' : '') . ' ' . ($null ? 'NULL' : 'NOT NULL') . ' ' . $default;

        $this->con->execute($sql);
    }

    public function db_create_primary(string $table, string $name, array $fields): void
    {
        $sql = 'ALTER TABLE ' . $table . ' ' .
        'ADD CONSTRAINT ' . $name . ' PRIMARY KEY (' . implode(',', $fields) . ')';

        $this->con->execute($sql);
    }

    public function db_create_unique(string $table, string $name, array $fields): void
    {
        $sql = 'ALTER TABLE ' . $table . ' ' .
        'ADD CONSTRAINT ' . $name . ' UNIQUE (' . implode(',', $fields) . ')';

        $this->con->execute($sql);
    }

    public function db_create_index(string $table, string $name, string $type, array $fields): void
    {
        $sql = 'CREATE INDEX ' . $name . ' ON ' . $table . ' USING ' . $type .
        '(' . implode(',', $fields) . ')';

        $this->con->execute($sql);
    }

    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
        $sql = 'ALTER TABLE ' . $table . ' ' .
        'ADD CONSTRAINT ' . $name . ' FOREIGN KEY ' .
        '(' . implode(',', $fields) . ') ' .
        'REFERENCES ' . $foreign_table . ' ' .
        '(' . implode(',', $foreign_fields) . ')';

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

        $sql = 'ALTER TABLE ' . $table . ' ALTER COLUMN ' . $name . ' TYPE ' . $type . ($len > 0 ? '(' . $len . ')' : '');
        $this->con->execute($sql);

        $default = $this->con->formatValue($default);
        $default = $default !== '' ? 'SET DEFAULT ' . $default : 'DROP DEFAULT';

        $sql = 'ALTER TABLE ' . $table . ' ALTER COLUMN ' . $name . ' ' . $default;
        $this->con->execute($sql);

        $null = $null ? 'DROP NOT NULL' : 'SET NOT NULL';
        $sql  = 'ALTER TABLE ' . $table . ' ALTER COLUMN ' . $name . ' ' . $null;
        $this->con->execute($sql);
    }

    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void
    {
        $sql = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name;
        $this->con->execute($sql);

        $this->createPrimary($table, $newname, $fields);
    }

    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void
    {
        $sql = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name;
        $this->con->execute($sql);

        $this->createUnique($table, $newname, $fields);
    }

    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $sql = 'DROP INDEX ' . $name;
        $this->con->execute($sql);

        $this->createIndex($table, $newname, $type, $fields);
    }

    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, false|string $update, false|string $delete): void
    {
        $sql = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name;
        $this->con->execute($sql);

        $this->createReference($newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function db_drop_unique(string $table, string $name): void
    {
        $sql = 'ALTER TABLE ' . $table . ' DROP CONSTRAINT ' . $name;
        $this->con->execute($sql);
    }
}
