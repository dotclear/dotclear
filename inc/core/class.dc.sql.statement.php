<?php
/**
 * @brief Select query statement builder
 *
 * dcSelectStatement is a class used to build select queries
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/**
 * SQL Statement : small utility to build SQL queries
 */
class dcSqlStatement
{
    protected $core;
    protected $con;

    protected $ctx; // Context (may be useful for behaviour's callback)

    protected $columns;
    protected $from;
    protected $where;
    protected $cond;
    protected $sql;

    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(&$core, $ctx = null)
    {
        $this->core = &$core;
        $this->con  = &$core->con;
        $this->ctx  = $ctx;

        $this->columns =
        $this->from    =
        $this->where   =
        $this->cond    =
        $this->sql     =
        array();
    }

    /**
     * Magic getter method
     *
     * @param      string  $property  The property
     *
     * @return     mixed   property value if property exists
     */
    public function __get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        trigger_error('Unknown property ' . $property, E_USER_ERROR);
        return;
    }

    /**
     * Magic setter method
     *
     * @param      string  $property  The property
     * @param      mixed   $value     The value
     *
     * @return     self
     */
    public function __set($property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            trigger_error('Unknown property ' . $property, E_USER_ERROR);
        }
        return $this;
    }

    /**
     * Adds context
     *
     * @param mixed     $c      the context(s)
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function ctx($c)
    {
        $this->ctx = $c;
        return $this;
    }

    /**
     * Adds column(s)
     *
     * @param mixed     $c      the column(s)
     * @param boolean   $reset  reset previous column(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function columns($c, $reset = false)
    {
        if ($reset) {
            $this->columns = array();
        }
        if (is_array($c)) {
            $this->columns = array_merge($this->columns, $c);
        } else {
            array_push($this->columns, $c);
        }
        return $this;
    }

    /**
     * columns() alias
     *
     * @param      mixed    $c      the column(s)
     * @param      boolean  $reset  reset previous column(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function column($c, $reset = false)
    {
        return $this->columns($c, $reset);
    }

    /**
     * Adds FROM clause(s)
     *
     * @param mixed     $c      the from clause(s)
     * @param boolean   $reset  reset previous from(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function from($c, $reset = false)
    {
        if ($reset) {
            $this->from = array();
        }
        // Remove comma on beginning of clause(s) (legacy code)
        if (is_array($c)) {
            $filter = function ($v) {
                return trim(ltrim($v, ','));
            };
            $c          = array_map($filter, $c); // Cope with legacy code
            $this->from = array_merge($this->from, $c);
        } else {
            $c = trim(ltrim($c, ',')); // Cope with legacy code
            array_push($this->from, $c);
        }
        return $this;
    }

    /**
     * Adds WHERE clause(s) condition (each will be AND combined in statement)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous where(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function where($c, $reset = false)
    {
        if ($reset) {
            $this->where = array();
        }
        if (is_array($c)) {
            $this->where = array_merge($this->where, $c);
        } else {
            array_push($this->where, $c);
        }
        return $this;
    }

    /**
     * Adds additional WHERE clause condition(s) (including an operator at beginning)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function cond($c, $reset = false)
    {
        if ($reset) {
            $this->cond = array();
        }
        if (is_array($c)) {
            $this->cond = array_merge($this->cond, $c);
        } else {
            array_push($this->cond, $c);
        }
        return $this;
    }

    /**
     * Adds generic clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous generic clause(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function sql($c, $reset = false)
    {
        if ($reset) {
            $this->sql = array();
        }
        if (is_array($c)) {
            $this->sql = array_merge($this->sql, $c);
        } else {
            array_push($this->sql, $c);
        }
        return $this;
    }

    // Helpers

    /**
     * Escape a value
     *
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function escape($value)
    {
        return $this->con->escape($value);
    }

    /**
     * Quote and escape a value if necessary (type string)
     *
     * @param      mixed    $value   The value
     * @param      boolean  $escape  The escape
     *
     * @return     string
     */
    public function quote($value, $escape = true)
    {
        return
            (is_string($value) ? "'" : '') .
            ($escape ? $this->con->escape($value) : $value) .
            (is_string($value) ? "'" : '');
    }

    /**
     * Return an SQL IN (…) fragment
     *
     * @param      mixed  $list   The list
     *
     * @return     string
     */
    public function in($list)
    {
        return $this->con->in($list);
    }

    /**
     * Return an SQL formatted date
     *
     * @param   string    $field     Field name
     * @param   string    $pattern   Date format
     *
     * @return     string
     */
    public function dateFormat($field, $pattern)
    {
        return $this->con->dateFormat($field, $pattern);
    }

    /**
     * Return an SQL formatted REGEXP clause
     *
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function regexp($value)
    {
        if ($this->con->syntax() == 'mysql') {
            $clause = "REGEXP '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } elseif ($this->con->syntax() == 'postgresql') {
            $clause = "~ '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } else {
            $clause = "LIKE '" .
            $this->escape(preg_replace(array('%', '_', '!'), array('!%', '!_', '!!'), $value)) .
                "%' ESCAPE '!'";
        }
        return $clause;
    }

    /**
     * Compare two SQL queries
     *
     * May be used for debugging purpose as:
     *
     * if (!$sql->isSame($sql->statement(), $oldRequest)) {
     *     trigger_error('SQL statement error: ' . $sql->statement() . ' / ' . $oldRequest, E_USER_ERROR);
     * }
     *
     * @param      string   $local     The local
     * @param      string   $external  The external
     *
     * @return     boolean  True if same, False otherwise.
     */
    public function isSame($local, $external)
    {
        $filter = function ($s) {
            $s        = strtoupper($s);
            $patterns = array(
                '\s+' => ' ', // Multiple spaces/tabs -> one space
                ' \)' => ')', // <space>) -> )
                ' ,'  => ',', // <space>, -> ,
                '\( ' => '(' // (<space> -> (
            );
            foreach ($patterns as $pattern => $replace) {
                $s = preg_replace('!' . $pattern . '!', $replace, $s);
            }
            return trim($s);
        };
        return ($filter($local) === $filter($external));
    }
}

/**
 * Select Statement : small utility to build select queries
 */
class dcSelectStatement extends dcSqlStatement
{
    protected $join;
    protected $having;
    protected $order;
    protected $group;
    protected $limit;
    protected $offset;
    protected $distinct;

    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(&$core, $ctx = null)
    {
        $this->join   =
        $this->having =
        $this->order  =
        $this->group  =
        array();

        $this->limit    = null;
        $this->offset   = null;
        $this->distinct = false;

        parent::__construct($core, $ctx);
    }

    /**
     * Adds JOIN clause(s) (applied on first from item only)
     *
     * @param mixed     $c      the join clause(s)
     * @param boolean   $reset  reset previous join(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function join($c, $reset = false)
    {
        if ($reset) {
            $this->join = array();
        }
        if (is_array($c)) {
            $this->join = array_merge($this->join, $c);
        } else {
            array_push($this->join, $c);
        }
        return $this;
    }

    /**
     * Adds HAVING clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous having(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function having($c, $reset = false)
    {
        if ($reset) {
            $this->having = array();
        }
        if (is_array($c)) {
            $this->having = array_merge($this->having, $c);
        } else {
            array_push($this->having, $c);
        }
        return $this;
    }

    /**
     * Adds ORDER BY clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous order(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function order($c, $reset = false)
    {
        if ($reset) {
            $this->order = array();
        }
        if (is_array($c)) {
            $this->order = array_merge($this->order, $c);
        } else {
            array_push($this->order, $c);
        }
        return $this;
    }

    /**
     * Adds GROUP BY clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous group(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function group($c, $reset = false)
    {
        if ($reset) {
            $this->group = array();
        }
        if (is_array($c)) {
            $this->group = array_merge($this->group, $c);
        } else {
            array_push($this->group, $c);
        }
        return $this;
    }

    /**
     * Defines the LIMIT for select
     *
     * @param mixed $limit
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function limit($limit)
    {
        $offset = null;
        if (is_array($limit)) {
            // Keep only values
            $limit = array_values($limit);
            // If 2 values, [0] -> offset, [1] -> limit
            // If 1 value, [0] -> limit
            if (isset($limit[1])) {
                $offset = $limit[0];
                $limit  = $limit[1];
            } else {
                $limit = limit[0];
            }
        }
        $this->limit = $limit;
        if ($offset !== null) {
            $this->offset = $offset;
        }
        return $this;
    }

    /**
     * Defines the OFFSET for select
     *
     * @param integer $offset
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Defines the DISTINCT flag for select
     *
     * @param boolean $distinct
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Returns the select statement
     *
     * @return string the statement
     */
    public function statement()
    {
        # --BEHAVIOR-- coreBeforeSelectStatement
        $this->core->callBehavior('coreBeforeSelectStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL SELECT requires a FROM source'), E_USER_ERROR);
            return '';
        }

        // Query
        $query = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '');

        // Specific column(s) or all (*)
        if (count($this->columns)) {
            $query .= join(', ', $this->columns) . ' ';
        } else {
            $query .= '* ';
        }

        // Table(s) and Join(s)
        $query .= 'FROM ' . $this->from[0] . ' ';
        $query .= join(' ', $this->join) . ' ';
        if (count($this->from) > 1) {
            array_shift($this->from);
            $query .= ', ' . join(', ', $this->from) . ' '; // All other from(s)
        }

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                $query .= 'WHERE 1 '; // Hack to cope with the operator included in top of each condition
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        // Group by clause (columns or aliases)
        if (count($this->group)) {
            $query .= 'GROUP BY ' . join(', ', $this->group) . ' ';
        }

        // Having clause(s)
        if (count($this->having)) {
            $query .= 'HAVING ' . join(' AND ', $this->having) . ' ';
        }

        // Order by clause (columns or aliases and optionnaly order ASC/DESC)
        if (count($this->order)) {
            $query .= 'ORDER BY ' . join(', ', $this->order) . ' ';
        }

        // Limit clause
        if ($this->limit !== null) {
            $query .= 'LIMIT ' . $this->limit . ' ';
        }

        // Offset clause
        if ($this->offset !== null) {
            $query .= 'OFFSET ' . $this->offset . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertSelectStatement
        $this->core->callBehavior('coreAfterSelectStatement', $this, $query);

        return $query;
    }
}

/**
 * Delete Statement : small utility to build delete queries
 */
class dcDeleteStatement extends dcSqlStatement
{
    /**
     * Returns the delete statement
     *
     * @return string the statement
     */
    public function statement()
    {
        # --BEHAVIOR-- coreBeforeDeleteStatement
        $this->core->callBehavior('coreBeforeDeleteStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL DELETE requires a FROM source'), E_USER_ERROR);
            return '';
        }

        // Query
        $query = 'DELETE ';

        // Table
        $query .= 'FROM ' . $this->from[0] . ' ';

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                $query .= 'WHERE 1 '; // Hack to cope with the operator included in top of each condition
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertDeleteStatement
        $this->core->callBehavior('coreAfterDeleteStatement', $this, $query);

        return $query;
    }
}

/**
 * Update Statement : small utility to build update queries
 */
class dcUpdateStatement extends dcSqlStatement
{
    protected $set;

    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(&$core, $ctx = null)
    {
        $this->set = array();

        parent::__construct($core, $ctx);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the reference clause(s)
     * @param boolean   $reset  reset previous reference first
     *
     * @return dcUpdateStatement self instance, enabling to chain calls
     */
    public function reference($c, $reset = false)
    {
        return $this->from($c, $reset);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the reference clause(s)
     * @param boolean   $reset  reset previous reference first
     *
     * @return dcUpdateStatement self instance, enabling to chain calls
     */
    public function ref($c, $reset = false)
    {
        return $this->reference($c, $reset);
    }

    /**
     * Adds update value(s)
     *
     * @param mixed     $c      the udpate values(s)
     * @param boolean   $reset  reset previous update value(s) first
     *
     * @return dcUpdateStatement self instance, enabling to chain calls
     */
    public function set($c, $reset = false)
    {
        if ($reset) {
            $this->set = array();
        }
        if (is_array($c)) {
            $this->set = array_merge($this->set, $c);
        } else {
            array_push($this->set, $c);
        }
        return $this;
    }

    /**
     * set() alias
     *
     * @param      mixed    $c      the update value(s)
     * @param      boolean  $reset  reset previous update value(s) first
     *
     * @return dcUpdateStatement self instance, enabling to chain calls
     */
    public function sets($c, $reset = false)
    {
        return $this->set($c, $reset);
    }

    /**
     * Returns the WHERE part of update statement
     *
     * Useful to construct the where clause used with cursor->update() method
     */
    public function whereStatement()
    {
        # --BEHAVIOR-- coreBeforeUpdateWhereStatement
        $this->core->callBehavior('coreBeforeUpdateWhereStatement', $this);

        $query = '';

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                $query .= 'WHERE 1 '; // Hack to cope with the operator included in top of each condition
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateWhereStatement
        $this->core->callBehavior('coreAfterUpdateWhereStatement', $this, $query);

        return $query;
    }

    /**
     * Returns the update statement
     *
     * @return string the statement
     */
    public function statement()
    {
        # --BEHAVIOR-- coreBeforeUpdateStatement
        $this->core->callBehavior('coreBeforeUpdateStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL UPDATE requires an INTO source'), E_USER_ERROR);
            return '';
        }

        // Query
        $query = 'UPDATE ';

        // Reference
        $query .= $this->from[0] . ' ';

        // Value(s)
        if (count($this->set)) {
            $query .= 'SET ' . join(', ', $this->set) . ' ';
        }

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                $query .= 'WHERE 1 '; // Hack to cope with the operator included in top of each condition
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateStatement
        $this->core->callBehavior('coreAfterUpdateStatement', $this, $query);

        return $query;
    }
}

/**
 * Insert Statement : small utility to build insert queries
 */
class dcInsertStatement extends dcSqlStatement
{
    protected $lines;

    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(&$core, $ctx = null)
    {
        $this->lines = array();

        parent::__construct($core, $ctx);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the into clause(s)
     * @param boolean   $reset  reset previous into first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function into($c, $reset = false)
    {
        return $this->into($c, $reset);
    }

    /**
     * Adds update value(s)
     *
     * @param mixed     $c      the insert values(s)
     * @param boolean   $reset  reset previous insert value(s) first
     *
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function lines($c, $reset = false)
    {
        if ($reset) {
            $this->lines = array();
        }
        if (is_array($c)) {
            $this->lines = array_merge($this->lines, $c);
        } else {
            array_push($this->lines, $c);
        }
        return $this;
    }

    /**
     * line() alias
     *
     * @param      mixed    $c      the insert value(s)
     * @param      boolean  $reset  reset previous insert value(s) first
     *
     * @return dcInsertStatement self instance, enabling to chain calls
     */
    public function line($c, $reset = false)
    {
        return $this->lines($c, $reset);
    }

    /**
     * Returns the insert statement
     *
     * @return string the statement
     */
    public function statement()
    {
        # --BEHAVIOR-- coreBeforeInsertStatement
        $this->core->callBehavior('coreBeforeInsertStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL INSERT requires an INTO source'), E_USER_ERROR);
            return '';
        }

        // Query
        $query = 'INSERT ';

        // Reference
        $query .= 'INTO ' . $this->from[0] . ' ';

        // Column(s)
        if (count($this->columns)) {
            $query .= '(' . join(', ', $this->columns) . ') ';
        }

        // Value(s)
        $query .= 'VALUES ';
        if (count($this->lines)) {
            $raws = array();
            foreach ($this->lines as $line) {
                $raws[] = '(' . join(', ', $line) . ')';
            }
            $query .= join(', ', $raws);
        } else {
            // Use SQL default values (useful only if SQL strict mode is off or if every columns has a defined default value)
            $query .= '()';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertInsertStatement
        $this->core->callBehavior('coreAfterInsertStatement', $this, $query);

        return $query;
    }
}
