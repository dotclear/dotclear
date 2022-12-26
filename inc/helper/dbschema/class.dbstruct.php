<?php
/**
 * @class dbStruct
 *
 * @package Clearbricks
 * @subpackage DBSchema
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dbStruct
{
    /**
     * @var mixed instance
     */
    protected $con;

    /**
     * @var string DB table prefix
     */
    protected $prefix;

    /**
     * Stack of DB tables
     *
     * @var        array
     */
    protected $tables = [];

    /**
     * Stack of References (foreign keys)
     *
     * @var        array
     */
    protected $references = [];

    /**
     * Constructs a new instance.
     *
     * @param      mixed   $con     The DB handle
     * @param      string  $prefix  The DB table prefix
     */
    public function __construct($con, string $prefix = '')
    {
        $this->con    = &$con;
        $this->prefix = $prefix;
    }

    /**
     * Get driver name
     *
     * @return     string
     */
    public function driver(): string
    {
        return $this->con->driver();
    }

    /**
     * Set a new table
     *
     * @param      string         $name   The name
     *
     * @return     dbStructTable  The database structure table.
     */
    public function table(string $name): dbStructTable
    {
        $this->tables[$name] = new dbStructTable($name);

        return $this->tables[$name];
    }

    /**
     * Gets the specified table (create it if necessary).
     *
     * @param      string         $name   The table name
     *
     * @return     dbStructTable  The database structure table.
     */
    public function __get(string $name): dbStructTable
    {
        if (!isset($this->tables[$name])) {
            return $this->table($name);
        }

        return $this->tables[$name];
    }

    /**
     * Populate dbSchema instance from database structure
     */
    public function reverse(): void
    {
        $schema = dbSchema::init($this->con);

        # Get tables
        $tables = $schema->getTables();

        foreach ($tables as $table_name) {
            if ($this->prefix && strpos($table_name, $this->prefix) !== 0) {
                continue;
            }

            $table = $this->table($table_name);

            # Get fields
            $fields = $schema->getColumns($table_name);

            foreach ($fields as $field_name => $field) {
                $type = $schema->dbt2udt($field['type'], $field['len'], $field['default']);
                $table->field($field_name, $type, $field['len'], $field['null'], $field['default'], true);
            }

            # Get keys
            $keys = $schema->getKeys($table_name);

            foreach ($keys as $key) {
                $fields = $key['cols'];

                if ($key['primary']) {
                    $table->primary($key['name'], ...$fields);
                } elseif ($key['unique']) {
                    $table->unique($key['name'], ...$fields);
                }
            }

            # Get indexes
            $indexes = $schema->getIndexes($table_name);
            foreach ($indexes as $index) {
                $table->index($index['name'], $index['type'], ...$index['cols']);
            }

            # Get foreign keys
            $references = $schema->getReferences($table_name);
            foreach ($references as $reference) {
                $table->reference($reference['name'], $reference['c_cols'], $reference['p_table'], $reference['p_cols'], $reference['update'], $reference['delete']);
            }
        }
    }

    /**
     * Synchronize this schema taken from database with $schema.
     *
     * @param      dbStruct $s Structure to synchronize with
     */
    public function synchronize(dbStruct $s)
    {
        $this->tables = [];
        $this->reverse();

        if (!($s instanceof self)) {
            throw new Exception('Invalid database schema');
        }

        $tables = $s->getTables();

        $table_create     = [];
        $key_create       = [];
        $index_create     = [];
        $reference_create = [];

        $field_create     = [];
        $field_update     = [];
        $key_update       = [];
        $index_update     = [];
        $reference_update = [];

        $got_work = false;

        $schema = dbSchema::init($this->con);

        foreach ($tables as $tname => $t) {
            if (!$this->tableExists($tname)) {
                # Table does not exist, create table
                $table_create[$tname] = $t->getFields();

                # Add keys, indexes and references
                $keys       = $t->getKeys();
                $indexes    = $t->getIndexes();
                $references = $t->getReferences();

                foreach ($keys as $k => $v) {
                    $key_create[$tname][$this->prefix . $k] = $v;
                }
                foreach ($indexes as $k => $v) {
                    $index_create[$tname][$this->prefix . $k] = $v;
                }
                foreach ($references as $k => $v) {
                    $v['p_table']                                 = $this->prefix . $v['p_table'];
                    $reference_create[$tname][$this->prefix . $k] = $v;
                }

                $got_work = true;
            } else { # Table exists
                # Check new fields to create
                $fields = $t->getFields();
                /* @phpstan-ignore-next-line */
                $db_fields = $this->tables[$tname]->getFields();
                foreach ($fields as $fname => $f) {
                    /* @phpstan-ignore-next-line */
                    if (!$this->tables[$tname]->fieldExists($fname)) {
                        # Field doest not exist, create it
                        $field_create[$tname][$fname] = $f;
                        $got_work                     = true;
                    } elseif ($this->fieldsDiffer($db_fields[$fname], $f)) {
                        # Field exists and differs from db version
                        $field_update[$tname][$fname] = $f;
                        $got_work                     = true;
                    }
                }

                # Check keys to add or upgrade
                $keys = $t->getKeys();
                /* @phpstan-ignore-next-line */
                $db_keys = $this->tables[$tname]->getKeys();

                foreach ($keys as $kname => $k) {
                    if ($k['type'] == 'primary' && $this->con->syntax() == 'mysql') {
                        $kname = 'PRIMARY';
                    } else {
                        $kname = $this->prefix . $kname;
                    }

                    /* @phpstan-ignore-next-line */
                    $db_kname = $this->tables[$tname]->keyExists($kname, $k['type'], $k['cols']);
                    if (!$db_kname) {
                        # Key does not exist, create it
                        $key_create[$tname][$kname] = $k;
                        $got_work                   = true;
                    } elseif ($this->keysDiffer($db_kname, $db_keys[$db_kname]['cols'], $kname, $k['cols'])) {
                        # Key exists and differs from db version
                        $key_update[$tname][$db_kname] = array_merge(['name' => $kname], $k);
                        $got_work                      = true;
                    }
                }

                # Check index to add or upgrade
                $idx = $t->getIndexes();
                /* @phpstan-ignore-next-line */
                $db_idx = $this->tables[$tname]->getIndexes();

                foreach ($idx as $iname => $i) {
                    $iname = $this->prefix . $iname;
                    /* @phpstan-ignore-next-line */
                    $db_iname = $this->tables[$tname]->indexExists($iname, $i['type'], $i['cols']);

                    if (!$db_iname) {
                        # Index does not exist, create it
                        $index_create[$tname][$iname] = $i;
                        $got_work                     = true;
                    } elseif ($this->indexesDiffer($db_iname, $db_idx[$db_iname], $iname, $i)) {
                        # Index exists and differs from db version
                        $index_update[$tname][$db_iname] = array_merge(['name' => $iname], $i);
                        $got_work                        = true;
                    }
                }

                # Check references to add or upgrade
                $ref = $t->getReferences();
                /* @phpstan-ignore-next-line */
                $db_ref = $this->tables[$tname]->getReferences();

                foreach ($ref as $rname => $r) {
                    $rname        = $this->prefix . $rname;
                    $r['p_table'] = $this->prefix . $r['p_table'];
                    /* @phpstan-ignore-next-line */
                    $db_rname = $this->tables[$tname]->referenceExists($rname, $r['c_cols'], $r['p_table'], $r['p_cols']);

                    if (!$db_rname) {
                        # Reference does not exist, create it
                        $reference_create[$tname][$rname] = $r;
                        $got_work                         = true;
                    } elseif ($this->referencesDiffer($db_rname, $db_ref[$db_rname], $rname, $r)) {
                        $reference_update[$tname][$db_rname] = array_merge(['name' => $rname], $r);
                        $got_work                            = true;
                    }
                }
            }
        }

        if (!$got_work) {
            return;
        }

        # Create tables
        foreach ($table_create as $table => $fields) {
            $schema->createTable($table, $fields);
        }

        # Create new fields
        foreach ($field_create as $tname => $fields) {
            foreach ($fields as $fname => $f) {
                $schema->createField($tname, $fname, $f['type'], $f['len'], $f['null'], $f['default']);
            }
        }

        # Update fields
        foreach ($field_update as $tname => $fields) {
            foreach ($fields as $fname => $f) {
                $schema->alterField($tname, $fname, $f['type'], $f['len'], $f['null'], $f['default']);
            }
        }

        # Create new keys
        foreach ($key_create as $tname => $keys) {
            foreach ($keys as $kname => $k) {
                if ($k['type'] == 'primary') {
                    $schema->createPrimary($tname, $kname, $k['cols']);
                } elseif ($k['type'] == 'unique') {
                    $schema->createUnique($tname, $kname, $k['cols']);
                }
            }
        }

        # Update keys
        foreach ($key_update as $tname => $keys) {
            foreach ($keys as $kname => $k) {
                if ($k['type'] == 'primary') {
                    $schema->alterPrimary($tname, $kname, $k['name'], $k['cols']);
                } elseif ($k['type'] == 'unique') {
                    $schema->alterUnique($tname, $kname, $k['name'], $k['cols']);
                }
            }
        }

        # Create indexes
        foreach ($index_create as $tname => $index) {
            foreach ($index as $iname => $i) {
                $schema->createIndex($tname, $iname, $i['type'], $i['cols']);
            }
        }

        # Update indexes
        foreach ($index_update as $tname => $index) {
            foreach ($index as $iname => $i) {
                $schema->alterIndex($tname, $iname, $i['name'], $i['type'], $i['cols']);
            }
        }

        # Create references
        foreach ($reference_create as $tname => $ref) {
            foreach ($ref as $rname => $r) {
                $schema->createReference($rname, $tname, $r['c_cols'], $r['p_table'], $r['p_cols'], $r['update'], $r['delete']);
            }
        }

        # Update references
        foreach ($reference_update as $tname => $ref) {
            foreach ($ref as $rname => $r) {
                $schema->alterReference($rname, $r['name'], $tname, $r['c_cols'], $r['p_table'], $r['p_cols'], $r['update'], $r['delete']);
            }
        }

        # Flush execution stack
        $schema->flushStack();

        return
        count($table_create) + count($key_create) + count($index_create) + count($reference_create) + count($field_create) + count($field_update) + count($key_update) + count($index_update) + count($reference_update);
    }

    /**
     * Gets the tables.
     *
     * @return     array  The tables.
     */
    public function getTables(): array
    {
        $tables = [];
        foreach ($this->tables as $table => $properties) {
            $tables[$this->prefix . $table] = $properties;
        }

        return $tables;
    }

    /**
     * Determines if table exists.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if table exists, False otherwise.
     */
    public function tableExists(string $name): bool
    {
        return isset($this->tables[$name]);
    }

    /**
     * Check if two fields are the same
     *
     * @param      array  $dst_field  The destination field
     * @param      array  $src_field  The source field
     *
     * @return     bool
     */
    private function fieldsDiffer(array $dst_field, array $src_field): bool
    {
        $d_type    = $dst_field['type'];
        $d_len     = (int) $dst_field['len'];
        $d_default = $dst_field['default'];
        $d_null    = $dst_field['null'];

        $s_type    = $src_field['type'];
        $s_len     = (int) $src_field['len'];
        $s_default = $src_field['default'];
        $s_null    = $src_field['null'];

        return $d_type != $s_type || $d_len != $s_len || $d_default != $s_default || $d_null != $s_null;
    }

    /**
     * Check if two keys are the same
     *
     * @param      string  $dst_name    The destination name
     * @param      array   $dst_fields  The destination fields
     * @param      string  $src_name    The source name
     * @param      array   $src_fields  The source fields
     *
     * @return     bool
     */
    private function keysDiffer(string $dst_name, array $dst_fields, string $src_name, array $src_fields): bool
    {
        return $dst_name != $src_name || $dst_fields != $src_fields;
    }

    /**
     * Check if two indexes are the same
     *
     * @param      string  $dst_name  The destination name
     * @param      array   $dst_idx   The destination index
     * @param      string  $src_name  The source name
     * @param      array   $src_idc   The source idc
     *
     * @return     bool
     */
    private function indexesDiffer(string $dst_name, array $dst_idx, string $src_name, array $src_idc): bool
    {
        return $dst_name != $src_name || $dst_idx['cols'] != $src_idc['cols'] || $dst_idx['type'] != $src_idc['type'];
    }

    /**
     * Check if two references are the same
     *
     * @param      string  $dst_name  The destination name
     * @param      array   $dst_ref   The destination reference
     * @param      string  $src_name  The source name
     * @param      array   $src_ref   The source reference
     *
     * @return     bool
     */
    private function referencesDiffer(string $dst_name, array $dst_ref, string $src_name, array $src_ref): bool
    {
        return $dst_name != $src_name || $dst_ref['c_cols'] != $src_ref['c_cols'] || $dst_ref['p_table'] != $src_ref['p_table'] || $dst_ref['p_cols'] != $src_ref['p_cols'] || $dst_ref['update'] != $src_ref['update'] || $dst_ref['delete'] != $src_ref['delete'];
    }
}

/**
 * @class dbStructTable
 *
 * @package Clearbricks
 * @subpackage DBSchema
 */
class dbStructTable
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
    Universal data types supported by dbSchema

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
     * @return     dbStructTable|self
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
     * @return     dbStructTable|self
     */
    public function __call(string $name, $properties): dbStructTable
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
     * @return     dbStructTable|self
     */
    public function primary(string $name, ...$fields): dbStructTable
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
     * @return     dbStructTable|self
     */
    public function unique(string $name, ...$fields): dbStructTable
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
     * @return     dbStructTable|self
     */
    public function index(string $name, string $type, ...$fields): dbStructTable
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
     * @return     dbStructTable|self
     */
    protected function newKey(string $type, string $name, array $fields): dbStructTable
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
