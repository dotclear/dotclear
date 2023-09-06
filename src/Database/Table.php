<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Exception;

/**
 * @class Table
 *
 * Database Table structure Handler
 */
class Table
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var        bool
     */
    protected $has_primary = false;

    /**
     * @var        array
     */
    protected $fields = [];

    /**
     * @var        array
     */
    protected $keys = [];

    /**
     * @var        array
     */
    protected $indexes = [];

    /**
     * @var        array
     */
    protected $references = [];

    /**
    Universal data types supported by AbstractSchema

    SMALLINT    : signed 2 bytes integer
    INTEGER    : signed 4 bytes integer
    BIGINT    : signed 8 bytes integer
    REAL        : signed 4 bytes floating point number
    FLOAT    : signed 8 bytes floating point number
    NUMERIC    : exact numeric type

    DATE        : Calendar date (day, month and year)
    TIME        : Time of day
    TIMESTAMP    : Date and time

    CHAR        : A fixed n-length character string
    VARCHAR    : A variable length character string
    TEXT        : A variable length of text
     */
    protected $allowed_types = [
        'smallint', 'integer', 'bigint', 'real', 'float', 'numeric',
        'date', 'time', 'timestamp',
        'char', 'varchar', 'text',
    ];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Gets the fields.
     *
     * @return     array  The fields.
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Gets the keys.
     *
     * @return     array  The keys.
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Gets the indexes.
     *
     * @return     array  The indexes.
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * Gets the references.
     *
     * @return     array  The references.
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * Determines if field exists.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if field exists, False otherwise.
     */
    public function fieldExists(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Determines if key exists.
     *
     * @param      string            $name      The name
     * @param      string            $type      The type
     * @param      array             $fields    The fields
     *
     * @return     bool|string
     */
    public function keyExists(string $name, string $type, array $fields)
    {
        # Look for key with the same name
        if (isset($this->keys[$name])) {
            return $name;
        }

        # Look for key with the same columns list and type
        foreach ($this->keys as $key_name => $key) {
            if ($key['cols'] == $fields && $key['type'] == $type) {
                # Same columns and type, return new name
                return $key_name;
            }
        }

        return false;
    }

    /**
     * Determines if index exists.
     *
     * @param      string            $name      The name
     * @param      string            $type      The type
     * @param      array             $fields    The fields
     *
     * @return     bool|string
     */
    public function indexExists(string $name, string $type, array $fields)
    {
        # Look for key with the same name
        if (isset($this->indexes[$name])) {
            return $name;
        }

        # Look for index with the same columns list and type
        foreach ($this->indexes as $index_name => $index) {
            if ($index['cols'] == $fields && $index['type'] == $type) {
                # Same columns and type, return new name
                return $index_name;
            }
        }

        return false;
    }

    /**
     * Determines if reference exists.
     *
     * @param      string            $name              The reference name
     * @param      array             $local_fields      The local fields
     * @param      string            $foreign_table     The foreign table
     * @param      array             $foreign_fields    The foreign fields
     *
     * @return     bool|string
     */
    public function referenceExists(string $name, array $local_fields, string $foreign_table, array $foreign_fields)
    {
        if (isset($this->references[$name])) {
            return $name;
        }

        # Look for reference with same chil columns, parent table and columns
        foreach ($this->references as $reference_name => $reference) {
            if ($local_fields == $reference['c_cols'] && $foreign_table == $reference['p_table'] && $foreign_fields == $reference['p_cols']) {
                # Only name differs, return new name
                return $reference_name;
            }
        }

        return false;
    }

    /**
     * Define a table field
     *
     * @param      string     $name     The name
     * @param      string     $type     The type
     * @param      int|null   $len      The length
     * @param      bool       $null     Null value allowed
     * @param      mixed      $default  The default value
     * @param      bool       $to_null  Set type to null if type unknown
     *
     * @throws     Exception
     *
     * @return     Table|self
     */
    public function field(string $name, string $type, ?int $len, bool $null = true, $default = false, bool $to_null = false)
    {
        $type = strtolower($type);

        if (!in_array($type, $this->allowed_types)) {
            if ($to_null) {
                $type = null;
            } else {
                throw new Exception('Invalid data type ' . $type . ' in schema');
            }
        }

        $this->fields[$name] = [
            'type'    => $type,
            'len'     => (int) $len,
            'default' => $default,
            'null'    => (bool) $null,
        ];

        return $this;
    }

    /**
     * Set field
     *
     * @param      string  $name   The name
     * @param      mixed   $properties   The arguments
     *
     * @return     Table|self
     */
    public function __call(string $name, $properties): Table
    {
        return $this->field($name, ...$properties);
    }

    /**
     * Set a primary index
     *
     * @param      string         $name         The name
     * @param      mixed          ...$fields    The cols
     *
     * @throws     Exception
     *
     * @return     Table|self
     */
    public function primary(string $name, ...$fields): Table
    {
        if ($this->has_primary) {
            throw new Exception(sprintf('Table %s already has a primary key', $this->name));
        }

        return $this->newKey('primary', $name, $fields);
    }

    /**
     * Set an unique index
     *
     * @param      string         $name       The name
     * @param      mixed          ...$fields  The fields
     *
     * @return     Table|self
     */
    public function unique(string $name, ...$fields): Table
    {
        return $this->newKey('unique', $name, $fields);
    }

    /**
     * Set an index
     *
     * @param      string              $name        The name
     * @param      string              $type        The type
     * @param      mixed               ...$fields   The fields
     *
     * @return     Table|self
     */
    public function index(string $name, string $type, ...$fields): Table
    {
        $this->checkCols($fields);

        $this->indexes[$name] = [
            'type' => strtolower($type),
            'cols' => $fields,
        ];

        return $this;
    }

    /**
     * Set a reference
     *
     * @param      string           $name            The reference name
     * @param      array|string     $local_fields    The local fields
     * @param      string           $foreign_table   The foreign table
     * @param      array|string     $foreign_fields  The foreign fields
     * @param      bool|string      $update          The update
     * @param      bool|string      $delete          The delete
     */
    public function reference(string $name, $local_fields, string $foreign_table, $foreign_fields, $update = false, $delete = false): void
    {
        if (!is_array($foreign_fields)) {
            $foreign_fields = [$foreign_fields];
        }
        if (!is_array($local_fields)) {
            $local_fields = [$local_fields];
        }

        $this->checkCols($local_fields);

        $this->references[$name] = [
            'c_cols'  => $local_fields,
            'p_table' => $foreign_table,
            'p_cols'  => $foreign_fields,
            'update'  => $update,
            'delete'  => $delete,
        ];
    }

    /**
     * Set a new key (index)
     *
     * @param      string              $type    The type
     * @param      string              $name    The name
     * @param      array               $fields  The fields
     *
     * @return     Table|self
     */
    protected function newKey(string $type, string $name, array $fields): Table
    {
        $this->checkCols($fields);

        $this->keys[$name] = [
            'type' => $type,
            'cols' => $fields,
        ];

        if ($type == 'primary') {
            $this->has_primary = true;
        }

        return $this;
    }

    /**
     * Ccheck if field(s) exists
     *
     * @param      array      $fields   The fields
     *
     * @throws     Exception
     */
    protected function checkCols(array $fields): void
    {
        foreach ($fields as $field) {
            if (!preg_match('/^\(.*?\)$/', $field) && !isset($this->fields[$field])) {
                throw new Exception(sprintf('Field %s does not exist in table %s', $field, $this->name));
            }
        }
    }
}
