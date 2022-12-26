<?php
/**
 * @interface i_dbSchema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
interface i_dbSchema
{
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
     * @return     array
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
     * @return     array
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
     * @return     array
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
     * @return     array
     */
    public function db_get_references(string $table): array;

    /**
     * Create table
     *
     * @param      string  $name    The name
     * @param      array   $fields  The fields
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
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      array   $fields The fields
     */
    public function db_create_primary(string $table, string $name, array $fields): void;

    /**
     * Create unique field
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      array   $fields The fields
     */
    public function db_create_unique(string $table, string $name, array $fields): void;

    /**
     * Create index
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      string  $type   The type
     * @param      array   $fields The fields
     */
    public function db_create_index(string $table, string $name, string $type, array $fields): void;

    /**
     * Create reference
     *
     * @param      string       $name               The name
     * @param      string       $table              The table
     * @param      array        $fields             The fields
     * @param      string       $foreign_table      The foreign table
     * @param      array        $foreign_fields     The foreign fields
     * @param      bool|string  $update             The update
     * @param      bool|string  $delete             The delete
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
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The new name
     * @param      array   $fields   The fields
     */
    public function db_alter_primary(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify unique field
     *
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The new name
     * @param      array   $fields   The fields
     */
    public function db_alter_unique(string $table, string $name, string $newname, array $fields): void;

    /**
     * Modify index
     *
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The new name
     * @param      string  $type     The type
     * @param      array   $fields   The fields
     */
    public function db_alter_index(string $table, string $name, string $newname, string $type, array $fields): void;

    /**
     * Modify reference
     *
     * @param      string       $name               The name
     * @param      string       $newname            The new name
     * @param      string       $table              The table
     * @param      array        $fields             The fields
     * @param      string       $foreign_table      The foreign table
     * @param      array        $foreign_fields     The foreign fields
     * @param      bool|string  $update             The update
     * @param      bool|string  $delete             The delete
     */
    public function db_alter_reference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void;

    /**
     * Drop unique
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     */
    public function db_drop_unique(string $table, string $name): void;
}

/**
 * @class dbSchema
 *
 * @package Clearbricks
 * @subpackage DBSchema
 */
class dbSchema
{
    /**
     * @var mixed DB handle
     */
    protected $con;

    /**
     * Constructs a new instance.
     *
     * @param      mixed  $con    The DB handle
     */
    public function __construct($con)
    {
        $this->con = &$con;
    }

    /**
     * Initializes the driver.
     *
     * @param      mixed  $con    The DB handle
     *
     * @return     mixed
     */
    public static function init($con)
    {
        $driver       = $con->driver();
        $driver_class = $driver . 'Schema';

        if (!class_exists($driver_class)) {
            if (file_exists(__DIR__ . '/class.' . $driver . '.dbschema.php')) {
                require __DIR__ . '/class.' . $driver . '.dbschema.php';
            } else {
                trigger_error('Unable to load DB schema layer for ' . $driver, E_USER_ERROR);
                exit(1);    // @phpstan-ignore-line
            }
        }

        return new $driver_class($con);
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
     * @see        i_dbSchema::db_get_tables
     *
     * @return     array<string>
     */
    public function getTables(): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_tables();
    }

    /**
     * Returns an array of columns (name and type) of a given table.
     *
     * @see        i_dbSchema::db_get_columns
     *
     * @param      string $table Table name
     *
     * @return     array<string>
     */
    public function getColumns(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_columns($table);
    }

    /**
     * Returns an array of index of a given table.
     *
     * @see        i_dbSchema::db_get_keys
     *
     * @param      string $table Table name
     *
     * @return     array<string>
     */
    public function getKeys(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_keys($table);
    }

    /**
     * Returns an array of indexes of a given table.
     *
     * @see        i_dbSchema::db_get_index
     *
     * @param      string $table Table name
     *
     * @return     array<string>
     */
    public function getIndexes(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_indexes($table);
    }

    /**
     * Returns an array of foreign keys of a given table.
     *
     * @see        i_dbSchema::db_get_references
     *
     * @param      string $table Table name
     *
     * @return     array<string>
     */
    public function getReferences(string $table): array
    {
        /* @phpstan-ignore-next-line */
        return $this->db_get_references($table);
    }

    /**
     * Creates a table.
     *
     * @param      string  $name    The name
     * @param      array   $fields  The fields
     */
    public function createTable(string $name, array $fields): void
    {
        /* @phpstan-ignore-next-line */
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
        /* @phpstan-ignore-next-line */
        $this->db_create_field($table, $name, $type, $len, $null, $default);
    }

    /**
     * Creates a primary key.
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      array   $fields The fields
     */
    public function createPrimary(string $table, string $name, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_create_primary($table, $name, $fields);
    }

    /**
     * Creates an unique key.
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      array   $fields The fields
     */
    public function createUnique(string $table, string $name, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_create_unique($table, $name, $fields);
    }

    /**
     * Creates an index.
     *
     * @param      string  $table  The table
     * @param      string  $name   The name
     * @param      string  $type   The type
     * @param      array   $fields The fields
     */
    public function createIndex(string $table, string $name, string $type, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_create_index($table, $name, $type, $fields);
    }

    /**
     * Creates a reference.
     *
     * @param      string       $name            The name
     * @param      string       $table           The table
     * @param      array        $fields          The fields
     * @param      string       $foreign_table   The foreign table
     * @param      array        $foreign_fields  The foreign fields
     * @param      string|bool  $update          The update
     * @param      string|bool  $delete          The delete
     */
    public function createReference(string $name, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        /* @phpstan-ignore-next-line */
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
        /* @phpstan-ignore-next-line */
        $this->db_alter_field($table, $name, $type, $len, $null, $default);
    }

    /**
     * Modify a primary key
     *
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The newname
     * @param      array   $fields   The fields
     */
    public function alterPrimary(string $table, string $name, string $newname, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_alter_primary($table, $name, $newname, $fields);
    }

    /**
     * Modify a unique key
     *
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The newname
     * @param      array   $fields   The fields
     */
    public function alterUnique(string $table, string $name, string $newname, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_alter_unique($table, $name, $newname, $fields);
    }

    /**
     * Modify an index
     *
     * @param      string  $table    The table
     * @param      string  $name     The name
     * @param      string  $newname  The newname
     * @param      string  $type     The type
     * @param      array   $fields   The fields
     */
    public function alterIndex(string $table, string $name, string $newname, string $type, array $fields): void
    {
        /* @phpstan-ignore-next-line */
        $this->db_alter_index($table, $name, $newname, $type, $fields);
    }

    /**
     * Modify a reference (foreign key)
     *
     * @param      string       $name            The name
     * @param      string       $newname         The newname
     * @param      string       $table           The table
     * @param      array        $fields          The fields
     * @param      string       $foreign_table   The foreign table
     * @param      array        $foreign_fields  The foreign fields
     * @param      string|bool  $update          The update
     * @param      string|bool  $delete          The delete
     */
    public function alterReference(string $name, string $newname, string $table, array $fields, string $foreign_table, array $foreign_fields, $update, $delete): void
    {
        /* @phpstan-ignore-next-line */
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
        /* @phpstan-ignore-next-line */
        $this->db_drop_unique($table, $name);
    }

    /**
     * Flush stack
     */
    public function flushStack()
    {
    }
}
