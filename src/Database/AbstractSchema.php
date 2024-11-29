<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\SchemaInterface;

/**
 * @class AbstractSchema
 *
 * Database schema abstraction
 */
abstract class AbstractSchema implements SchemaInterface, InterfaceSchema
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

    /**
     * Database data type to universal data type conversion.
     *
     * @param      string   $type       Type name
     * @param      int      $len        Field length (in/out)
     * @param      mixed    $default    Default field value (in/out)
     *
     * @return     string
     */
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

    /**
     * Universal data type to database data tye conversion.
     *
     * @param      string   $type       Type name
     * @param      integer  $len        Field length (in/out)
     * @param      string   $default    Default field value (in/out)
     *
     * @return     string
     */
    public function udt2dbt(string $type, ?int &$len, &$default): string
    {
        return $type;
    }

    /**
     * Returns an array of all table names.
     *
     * @see        InterfaceSchema::db_get_tables
     *
     * @return     array<string>
     */
    public function getTables(): array
    {
        return $this->db_get_tables();
    }

    /**
     * Returns an array of columns (name and type) of a given table.
     *
     * @see        InterfaceSchema::db_get_columns
     *
     * @param      string $table Table name
     *
     * @return     array<string, array{type: string, len: int|null, null: bool, default: string}>
     */
    public function getColumns(string $table): array
    {
        return $this->db_get_columns($table);
    }

    /**
     * Returns an array of index of a given table.
     *
     * @see        InterfaceSchema::db_get_keys
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, primary: bool, unique: bool, cols: array<string>}>
     */
    public function getKeys(string $table): array
    {
        return $this->db_get_keys($table);
    }

    /**
     * Returns an array of indexes of a given table.
     *
     * @see        InterfaceSchema::db_get_index
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, type: string, cols: array<string>}>
     */
    public function getIndexes(string $table): array
    {
        return $this->db_get_indexes($table);
    }

    /**
     * Returns an array of foreign keys of a given table.
     *
     * @see        InterfaceSchema::db_get_references
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, c_cols: array<string>, p_table: string, p_cols: array<string>, update: string, delete: string}>
     */
    public function getReferences(string $table): array
    {
        return $this->db_get_references($table);
    }

    /**
     * Creates a table.
     *
     * @param      string                               $name    The name
     * @param      array<string, array<string, mixed>>  $fields  The fields
     */
    public function createTable(string $name, array $fields): void
    {
        $this->db_create_table($name, $fields);
    }

    /**
     * Creates a field.
     *
     * @param      string    $table    The table
     * @param      string    $name     The name
     * @param      string    $type     The type
     * @param      int|null  $len      The length
     * @param      bool      $null     The null
     * @param      mixed     $default  The default value
     */
    public function createField(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->db_create_field($table, $name, $type, $len, $null, $default);
    }

    /**
     * Creates a primary key.
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function createPrimary(string $table, string $name, array $fields): void
    {
        $this->db_create_primary($table, $name, $fields);
    }

    /**
     * Creates an unique key.
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function createUnique(string $table, string $name, array $fields): void
    {
        $this->db_create_unique($table, $name, $fields);
    }

    /**
     * Creates an index.
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      string           $type   The type
     * @param      array<string>    $fields The fields
     */
    public function createIndex(string $table, string $name, string $type, array $fields): void
    {
        $this->db_create_index($table, $name, $type, $fields);
    }

    /**
     * Creates a reference.
     *
     * @param      string           $name            The name
     * @param      string           $table           The table
     * @param      array<string>    $fields          The fields
     * @param      string           $foreign_table   The foreign table
     * @param      array<string>    $foreign_fields  The foreign fields
     * @param      string|false     $update          The update
     * @param      string|false     $delete          The delete
     */
    public function createReference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->db_create_reference($name, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    /**
     * Modify a field
     *
     * @param      string    $table    The table
     * @param      string    $name     The name
     * @param      string    $type     The type
     * @param      int|null  $len      The length
     * @param      bool      $null     The null
     * @param      mixed     $default  The default value
     */
    public function alterField(string $table, string $name, string $type, ?int $len, bool $null, $default): void
    {
        $this->db_alter_field($table, $name, $type, $len, $null, $default);
    }

    /**
     * Modify a primary key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The fields
     */
    public function alterPrimary(string $table, string $name, string $newname, array $fields): void
    {
        $this->db_alter_primary($table, $name, $newname, $fields);
    }

    /**
     * Modify a unique key
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The newname
     * @param      array<string>    $fields   The fields
     */
    public function alterUnique(string $table, string $name, string $newname, array $fields): void
    {
        $this->db_alter_unique($table, $name, $newname, $fields);
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
    public function alterIndex(string $table, string $name, string $newname, string $type, array $fields): void
    {
        $this->db_alter_index($table, $name, $newname, $type, $fields);
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
    public function alterReference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        $this->db_alter_reference($name, $newname, $table, $fields, $foreign_table, $foreign_fields, $update, $delete);
    }

    /**
     * Remove a unique key
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     */
    public function dropUnique(string $table, string $name): void
    {
        $this->db_drop_unique($table, $name);
    }

    /**
     * Flush stack
     */
    public function flushStack()
    {
    }
}
