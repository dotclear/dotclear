<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Interface\Database\ConnectionInterface;
use Dotclear\Interface\Database\SchemaInterface;

/**
 * @brief   Database schema abstraction
 */
abstract class AbstractSchema implements SchemaInterface
{
    /**
     * Constructs a new instance.
     *
     * @param   ConnectionInterface     $con    The DB handler
     */
    public function __construct(
        protected ConnectionInterface $con
    ) {
    }

    public function dbt2udt(string $type, ?int &$len, &$default): string
    {
        $map = [
            'bool'              => 'boolean',
            'int2'              => 'smallint',
            'int'               => 'integer',
            'int4'              => 'integer',
            'int8'              => 'bigint',
            'float4'            => 'real',
            'double precision'  => 'float',
            'float8'            => 'float',
            'decimal'           => 'numeric',
            'character varying' => 'varchar',
            'character'         => 'char',
        ];

        return $map[$type] ?? $type;
    }

    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        return $type;
    }

    public function getTables(): array
    {
        return $this->db_get_tables();
    }

    public function getColumns(string $table): array
    {
        return $this->db_get_columns($table);
    }

    public function getKeys(string $table): array
    {
        return $this->db_get_keys($table);
    }

    public function getIndexes(string $table): array
    {
        return $this->db_get_indexes($table);
    }

    public function getReferences(string $table): array
    {
        return $this->db_get_references($table);
    }

    public function createTable(string $name, array $fields): void
    {
        $this->db_create_table($name, $fields);
    }

    public function createField(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->db_create_field($table, $name, $type, $len, $null, $default);
    }

    public function createPrimary(string $table, string $name, array $fields): void
    {
        $this->db_create_primary($table, $name, $fields);
    }

    public function createUnique(string $table, string $name, array $fields): void
    {
        $this->db_create_unique($table, $name, $fields);
    }

    public function createIndex(string $table, string $name, string $type, array $fields): void
    {
        $this->db_create_index($table, $name, $type, $fields);
    }

    public function createReference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->db_create_reference($name, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function alterField(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->db_alter_field($table, $name, $type, $len, $null, $default);
    }

    public function alterPrimary(string $table, string $name, string $newname, array $fields): void
    {
        $this->db_alter_primary($table, $name, $newname, $fields);
    }

    public function alterUnique(string $table, string $name, string $newname, array $fields): void
    {
        $this->db_alter_unique($table, $name, $newname, $fields);
    }

    public function alterIndex(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $this->db_alter_index($table, $name, $newname, $type, $fields);
    }

    public function alterReference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->db_alter_reference($name, $newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    public function dropUnique(string $table, string $name): void
    {
        $this->db_drop_unique($table, $name);
    }

    public function flushStack()
    {
    }
}
