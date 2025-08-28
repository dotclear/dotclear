<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Database;

/**
 * @brief   Database schema handler interface.
 *
 * @since   2.28
 */
interface SchemaInterface
{
    /// @name Methods implemented by the driver
    ///@{

    /**
     * This method should return an array of all tables in database for the current connection.
     *
     * @return     array<string>
     */
    public function db_get_tables(): array;

    /**
     * This method should return an associative array of columns in given table
     * <var>$table</var> with column names in keys. Each line value is an array
     * with following values:
     *
     * - [type] data type (string)
     * - [len] data length (integer or null)
     * - [null] is null? (boolean)
     * - [default] default value (string)
     *
     * @param      string $table Table name
     *
     * @return     array<string, array{type: string, len: int|null, null: bool, default: string}>
     */
    public function db_get_columns(string $table): array;

    /**
     * This method should return an array of keys in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] index name (string)
     * - [primary] primary key (boolean)
     * - [unique] unique key (boolean)
     * - [cols] columns (array)
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, primary: bool, unique: bool, cols: array<string>}>
     */
    public function db_get_keys(string $table): array;

    /**
     * This method should return an array of indexes in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] index name (string)
     * - [type] index type (string)
     * - [cols] columns (array)
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, type: string, cols: array<string>}>
     */
    public function db_get_indexes(string $table): array;

    /**
     * This method should return an array of foreign keys in given table
     * <var>$table</var>. Each line value is an array with following values:
     *
     * - [name] key name (string)
     * - [c_cols] child columns (array)
     * - [p_table] parent table (string)
     * - [p_cols] parent columns (array)
     * - [update] on update statement (string)
     * - [delete] on delete statement (string)
     *
     * @param      string $table Table name
     *
     * @return     array<array{name: string, c_cols: array<string>, p_table: string, p_cols: array<string>, update: string, delete: string}>
     */
    public function db_get_references(string $table): array;

    /**
     * Create table
     *
     * @param      string                   $name    The name
     * @param      array<string, mixed>     $fields  The fields
     */
    public function db_create_table(string $name, array $fields): void;

    /**
     * Create a field
     *
     * @param      string    $table    The table
     * @param      string    $name     The name
     * @param      string    $type     The type
     * @param      int|null  $len      The length
     * @param      bool      $null     The null
     * @param      mixed     $default  The default
     */
    public function db_create_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    /**
     * Create primary index
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function db_create_primary(string $table, string $name, array $fields): void;

    /**
     * Create unique field
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      array<string>    $fields The fields
     */
    public function db_create_unique(string $table, string $name, array $fields): void;

    /**
     * Create index
     *
     * @param      string           $table  The table
     * @param      string           $name   The name
     * @param      string           $type   The type
     * @param      array<string>    $fields The fields
     */
    public function db_create_index(string $table, string $name, string $type, array $fields): void;

    /**
     * Create reference
     *
     * @param      string           $name               The name
     * @param      string           $table              The table
     * @param      array<string>    $fields             The fields
     * @param      string           $foreign_table      The foreign table
     * @param      array<string>    $foreign_fields     The foreign fields
     * @param      false|string     $update             The update
     * @param      false|string     $delete             The delete
     */
    public function db_create_reference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void;

    /**
     * Modify field
     *
     * @param      string    $table    The table
     * @param      string    $name     The name
     * @param      string    $type     The type
     * @param      int|null  $len      The length
     * @param      bool      $null     The null
     * @param      mixed     $default  The default
     */
    public function db_alter_field(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    /**
     * Modify primary index
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The new name
     * @param      array<string>    $fields   The fields
     */
    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify unique field
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The new name
     * @param      array<string>    $fields   The fields
     */
    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify index
     *
     * @param      string           $table    The table
     * @param      string           $name     The name
     * @param      string           $newname  The new name
     * @param      string           $type     The type
     * @param      array<string>    $fields   The fields
     */
    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void;

    /**
     * Modify reference
     *
     * @param      string           $name               The name
     * @param      string           $newname            The new name
     * @param      string           $table              The table
     * @param      array<string>    $fields             The fields
     * @param      string           $foreign_table      The foreign table
     * @param      array<string>    $foreign_fields     The foreign fields
     * @param      false|string     $update             The update
     * @param      false|string     $delete             The delete
     */
    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void;

    /**
     * Drop unique
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     */
    public function db_drop_unique(string $table, string $name): void;

    ///@}

    /// @name Methods implemented by the handler
    ///@{

    /**
     * Database data type to universal data type conversion.
     *
     * @param   string  $type       Type name
     * @param   int     $len        Field length (in/out)
     * @param   mixed   $default    Default field value (in/out)
     */
    public function dbt2udt(string $type, ?int &$len, &$default): string;

    /**
     * Universal data type to database data tye conversion.
     *
     * @param   string      $type       Type name
     * @param   integer     $len        Field length (in/out)
     * @param   string      $default    Default field value (in/out)
     */
    public function udt2dbt(string $type, ?int &$len, &$default): string;

    /**
     * Returns an array of all table names.
     *
     * @see     InterfaceSchema::db_get_tables
     *
     * @return  array<string>
     */
    public function getTables(): array;

    /**
     * Returns an array of columns (name and type) of a given table.
     *
     * @see     InterfaceSchema::db_get_columns
     *
     * @param   string  $table  Table name
     *
     * @return  array<string, array{type: string, len: int|null, null: bool, default: string}>
     */
    public function getColumns(string $table): array;

    /**
     * Returns an array of keys of a given table.
     *
     * @see     InterfaceSchema::db_get_keys
     *
     * @param   string  $table  Table name
     *
     * @return  array<array{name: string, primary: bool, unique: bool, cols: array<string>}>
     */
    public function getKeys(string $table): array;

    /**
     * Returns an array of indexes of a given table.
     *
     * @see     InterfaceSchema::db_get_index
     *
     * @param   string  $table  Table name
     *
     * @return  array<array{name: string, type: string, cols: array<string>}>
     */
    public function getIndexes(string $table): array;

    /**
     * Returns an array of foreign keys of a given table.
     *
     * @see     InterfaceSchema::db_get_references
     *
     * @param   string  $table  Table name
     *
     * @return  array<array{name: string, c_cols: array<string>, p_table: string, p_cols: array<string>, update: string, delete: string}>
     */
    public function getReferences(string $table): array;

    /**
     * Creates a table.
     *
     * @param   string                                  $name    The name
     * @param   array<string, array<string, mixed>>     $fields  The fields
     */
    public function createTable(string $name, array $fields): void;

    /**
     * Creates a field.
     *
     * @param   string      $table      The table
     * @param   string      $name       The name
     * @param   string      $type       The type
     * @param   int|null    $len        The length
     * @param   bool        $null       The null
     * @param   mixed       $default    The default value
     */
    public function createField(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    /**
     * Creates a primary key.
     *
     * @param   string          $table      The table
     * @param   string          $name       The name
     * @param   array<string>   $fields     The fields
     */
    public function createPrimary(string $table, string $name, array $fields): void;

    /**
     * Creates an unique key.
     *
     * @param   string          $table      The table
     * @param   string          $name       The name
     * @param   array<string>   $fields     The fields
     */
    public function createUnique(string $table, string $name, array $fields): void;

    /**
     * Creates an index.
     *
     * @param   string          $table  The table
     * @param   string          $name   The name
     * @param   string          $type   The type
     * @param   array<string>   $fields The fields
     */
    public function createIndex(string $table, string $name, string $type, array $fields): void;

    /**
     * Create reference
     *
     * @param   string          $name               The name
     * @param   string          $table              The table
     * @param   array<string>   $fields             The fields
     * @param   string          $foreign_table      The foreign table
     * @param   array<string>   $foreign_fields     The foreign fields
     * @param   false|string    $update             The update
     * @param   false|string    $delete             The delete
     */
    public function createReference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void;

    /**
     * Modify a field.
     *
     * @param   string      $table      The table
     * @param   string      $name       The name
     * @param   string      $type       The type
     * @param   int|null    $len        The length
     * @param   bool        $null       The null
     * @param   mixed       $default    The default value
     */
    public function alterField(string $table, string $name, string $type, ?int $len, bool $null, $default): void;

    /**
     * Modify a primary key.
     *
     * @param   string          $table      The table
     * @param   string          $name       The name
     * @param   string          $newname    The newname
     * @param   array<string>   $fields     The fields
     */
    public function alterPrimary(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify a unique key.
     *
     * @param   string          $table      The table
     * @param   string          $name       The name
     * @param   string          $newname    The newname
     * @param   array<string>   $fields     The fields
     */
    public function alterUnique(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify an index.
     *
     * @param   string          $table      The table
     * @param   string          $name       The name
     * @param   string          $newname    The newname
     * @param   string          $type       The type
     * @param   array<string>   $fields     The fields
     */
    public function alterIndex(string $table, string $name, string $newname, string $type, array $fields): void;

    /**
     * Modify reference
     *
     * @param   string          $name               The name
     * @param   string          $newname            The new name
     * @param   string          $table              The table
     * @param   array<string>   $fields             The fields
     * @param   string          $foreign_table      The foreign table
     * @param   array<string>   $foreign_fields     The foreign fields
     * @param   false|string    $update             The update
     * @param   false|string    $delete             The delete
     */
    public function alterReference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void;

    /**
     * Remove a unique key.
     *
     * @param   string  $table  The table
     * @param   string  $name   The name
     */
    public function dropUnique(string $table, string $name): void;

    /**
     * Flush stack.
     *
     * @return  void
     */
    public function flushStack();

    ///@}
}
