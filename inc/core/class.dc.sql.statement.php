<?php
/**
 * @brief SQL query statement builder
 *
 * dcSqlStatement is a class used to build SQL queries
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
    public function __construct(dcCore &$core, $ctx = null)
    {
        $this->core = &$core;
        $this->con  = &$core->con;
        $this->ctx  = $ctx;

        $this->columns = $this->from = $this->where = $this->cond = $this->sql = [];
    }

    /**
     * Magic getter method
     *
     * @param      string  $property  The property
     *
     * @return     mixed   property value if property exists
     */
    public function __get(string $property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
        trigger_error('Unknown property ' . $property, E_USER_ERROR);
    }

    /**
     * Magic setter method
     *
     * @param      string  $property  The property
     * @param      mixed   $value     The value
     *
     * @return     self
     */
    public function __set(string $property, $value)
    {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        } else {
            trigger_error('Unknown property ' . $property, E_USER_ERROR);
        }

        return $this;
    }

    /**
     * Magic isset method
     *
     * @param      string  $property  The property
     *
     * @return     bool
     */
    public function __isset(string $property): bool
    {
        if (property_exists($this, $property)) {
            return isset($this->$property);
        }

        return false;
    }

    /**
     * Magic unset method
     *
     * @param      string  $property  The property
     */
    public function __unset(string $property)
    {
        if (property_exists($this, $property)) {
            unset($this->$property);
        }
    }

    /**
     * Magic invoke method
     *
     * Alias of statement()
     *
     * @return     string
     */
    public function __invoke(): string
    {
        return $this->statement();
    }

    /**
     * Returns a SQL dummy statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        return '';
    }

    /**
     * Adds context
     *
     * @param mixed     $c      the context(s)
     *
     * @return self instance, enabling to chain calls
     */
    public function ctx($c): dcSqlStatement
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
     * @return self instance, enabling to chain calls
     */
    public function columns($c, bool $reset = false): dcSqlStatement
    {
        if ($reset) {
            $this->columns = [];
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
     * @param mixed     $c      the column(s)
     * @param boolean   $reset  reset previous column(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function fields($c, bool $reset = false): dcSqlStatement
    {
        return $this->columns($c, $reset);
    }

    /**
     * columns() alias
     *
     * @param      mixed    $c      the column(s)
     * @param      boolean  $reset  reset previous column(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function column($c, bool $reset = false): dcSqlStatement
    {
        return $this->columns($c, $reset);
    }

    /**
     * column() alias
     *
     * @param      mixed    $c      the column(s)
     * @param      boolean  $reset  reset previous column(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function field($c, bool $reset = false): dcSqlStatement
    {
        return $this->column($c, $reset);
    }

    /**
     * Adds FROM clause(s)
     *
     * @param mixed     $c      the from clause(s)
     * @param boolean   $reset  reset previous from(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function from($c, bool $reset = false): dcSqlStatement
    {
        $filter = function ($v) {
            return trim(ltrim($v, ','));
        };
        if ($reset) {
            $this->from = [];
        }
        // Remove comma on beginning of clause(s) (legacy code)
        if (is_array($c)) {
            $c          = array_map($filter, $c);   // Cope with legacy code
            $this->from = array_merge($this->from, $c);
        } else {
            $c = $filter($c);   // Cope with legacy code
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
     * @return self instance, enabling to chain calls
     */
    public function where($c, bool $reset = false): dcSqlStatement
    {
        $filter = function ($v) {
            return preg_replace('/^\s*(AND|OR)\s*/i', '', $v);
        };
        if ($reset) {
            $this->where = [];
        }
        if (is_array($c)) {
            $c           = array_map($filter, $c);  // Cope with legacy code
            $this->where = array_merge($this->where, $c);
        } else {
            $c = $filter($c);   // Cope with legacy code
            array_push($this->where, $c);
        }

        return $this;
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous where(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function on($c, bool $reset = false): dcSqlStatement
    {
        return $this->where($c, $reset);
    }

    /**
     * Adds additional WHERE clause condition(s) (including an operator at beginning)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function cond($c, bool $reset = false): dcSqlStatement
    {
        if ($reset) {
            $this->cond = [];
        }
        if (is_array($c)) {
            $this->cond = array_merge($this->cond, $c);
        } else {
            array_push($this->cond, $c);
        }

        return $this;
    }

    /**
     * Adds additional WHERE AND clause condition(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function and($c, bool $reset = false): dcSqlStatement
    {
        return $this->cond(array_map(function ($v) {return 'AND ' . $v;}, is_array($c) ? $c : [$c]), $reset);
    }

    /**
     * Helper to group some AND parts
     *
     * @param      mixed  $c      the parts}
     *
     * @return     string
     */
    public function andGroup($c): string
    {
        $group = '(' . implode(' AND ', is_array($c) ? $c : [$c]) . ')';

        return $group === '()' ? '' : $group;
    }

    /**
     * Adds additional WHERE OR clause condition(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous condition(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function or($c, bool $reset = false): dcSqlStatement
    {
        return $this->cond(array_map(function ($v) {return 'OR ' . $v;}, is_array($c) ? $c : [$c]), $reset);
    }

    /**
     * Helper to group some OR parts
     *
     * @param      mixed  $c      the parts}
     *
     * @return     string
     */
    public function orGroup($c): string
    {
        $group = '(' . implode(' OR ', is_array($c) ? $c : [$c]) . ')';

        return $group === '()' ? '' : $group;
    }

    /**
     * Adds generic clause(s)
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous generic clause(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function sql($c, bool $reset = false): dcSqlStatement
    {
        if ($reset) {
            $this->sql = [];
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
    public function escape(string $value): string
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
    public function quote($value, bool $escape = true): string
    {
        return "'" . ($escape ? $this->con->escape($value) : $value) . "'";
    }

    /**
     * Return an SQL IN (…) fragment
     *
     * @param      mixed  $list   The list
     *
     * @return     string
     */
    public function in($list): string
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
    public function dateFormat(string $field, string $pattern): string
    {
        return $this->con->dateFormat($field, $pattern);
    }

    /**
     * Return an SQL formatted like
     *
     * @param      string  $field    The field
     * @param      string  $pattern  The pattern
     *
     * @return     string
     */
    public function like(string $field, string $pattern): string
    {
        return $field . ' LIKE ' . $this->quote($pattern);
    }

    /**
     * Return an SQL formatted REGEXP clause
     *
     * @param      string  $value  The value
     *
     * @return     string
     */
    public function regexp(string $value): string
    {
        if ($this->con->syntax() == 'mysql') {
            $clause = "REGEXP '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } elseif ($this->con->syntax() == 'postgresql') {
            $clause = "~ '^" . $this->escape(preg_quote($value)) . "[0-9]+$'";
        } else {
            $clause = "LIKE '" .
            $this->escape(preg_replace(['%', '_', '!'], ['!%', '!_', '!!'], $value)) . "%' ESCAPE '!'"; // @phpstan-ignore-line
        }

        return $clause;
    }

    /**
     * Return an DISTINCT clause
     *
     * @param      string       $field     The field
     *
     * @return     string
     */
    public function unique(string $field): string
    {
        return 'DISTINCT ' . $field;
    }

    /**
     * Return an COUNT(…) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     * @param      bool         $unique    Unique values only
     *
     * @return     string
     */
    public function count(string $field, ?string $as = null, bool $unique = false): string
    {
        return 'COUNT(' . ($unique ? $this->unique($field) : $field) . ')' . ($as ? ' as ' . $as : '');
    }

    /**
     * Return an AVG(…) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     *
     * @return     string
     */
    public function avg(string $field, ?string $as = null): string
    {
        return 'AVG(' . $field . ')' . ($as ? ' as ' . $as : '');
    }

    /**
     * Return an MAX(…) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     *
     * @return     string
     */
    public function max(string $field, ?string $as = null): string
    {
        return 'MAX(' . $field . ')' . ($as ? ' as ' . $as : '');
    }

    /**
     * Return an MIN(…) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     *
     * @return     string
     */
    public function min(string $field, ?string $as = null): string
    {
        return 'MIN(' . $field . ')' . ($as ? ' as ' . $as : '');
    }

    /**
     * Return an SUM(…) clause
     *
     * @param      string       $field     The field
     * @param      null|string  $as        Optional alias
     *
     * @return     string
     */
    public function sum(string $field, ?string $as = null): string
    {
        return 'SUM(' . $field . ')' . ($as ? ' as ' . $as : '');
    }

    /**
     * Compare two SQL queries
     *
     * May be used for debugging purpose as:
     *
     * if (!$sql->isSame($sql->statement(), $strReq)) {
     *     trigger_error('SQL statement error: ' . $sql->statement() . ' / ' . $strReq, E_USER_ERROR);
     * }
     *
     * @param      string   $local     The local
     * @param      string   $external  The external
     *
     * @return     boolean  True if same, False otherwise.
     */
    public function isSame(string $local, string $external): bool
    {
        $filter = function ($s) {
            $s        = strtoupper($s);
            $patterns = [
                '\s+' => ' ', // Multiple spaces/tabs -> one space
                ' \)' => ')', // <space>) -> )
                ' ,'  => ',', // <space>, -> ,
                '\( ' => '(', // (<space> -> (
            ];
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
    public function __construct(dcCore &$core, $ctx = null)
    {
        $this->join = $this->having = $this->order = $this->group = [];

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
     * @return self instance, enabling to chain calls
     */
    public function join($c, bool $reset = false): dcSelectStatement
    {
        if ($reset) {
            $this->join = [];
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
     * @return self instance, enabling to chain calls
     */
    public function having($c, bool $reset = false): dcSelectStatement
    {
        if ($reset) {
            $this->having = [];
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
     * @return self instance, enabling to chain calls
     */
    public function order($c, bool $reset = false): dcSelectStatement
    {
        if ($reset) {
            $this->order = [];
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
     * @return self instance, enabling to chain calls
     */
    public function group($c, bool $reset = false): dcSelectStatement
    {
        if ($reset) {
            $this->group = [];
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
     * @return self instance, enabling to chain calls
     */
    public function limit($limit): dcSelectStatement
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
                $limit = $limit[0];
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
     * @return self instance, enabling to chain calls
     */
    public function offset(int $offset): dcSelectStatement
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Defines the DISTINCT flag for select
     *
     * @param boolean $distinct
     * @return self instance, enabling to chain calls
     */
    public function distinct(bool $distinct = true): dcSelectStatement
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Returns the select statement
     *
     * @return string the statement
     */
    public function statement(): string
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
            $query .= ', ' . join(', ', array_slice($this->from, 1)) . ' '; // All other from(s)
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

    /**
     * Run the SQL select query and return result
     *
     * @return     mixed  record or staticRecord (for sqlite)
     */
    public function select()
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->select($sql);
        }

        return null;
    }

    /**
     * select() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->select();
    }
}

/**
 * Join (sub)Statement : small utility to build join query fragments
 */
class dcJoinStatement extends dcSqlStatement
{
    protected $type;

    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(dcCore &$core, $ctx = null)
    {
        $this->type = null;

        parent::__construct($core, $ctx);
    }

    /**
     * Defines the type for join
     *
     * @param string $type
     * @return self instance, enabling to chain calls
     */
    public function type(string $type = ''): dcJoinStatement
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Returns the join fragment
     *
     * @return string the fragment
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeDeleteStatement
        $this->core->callBehavior('coreBeforeJoinStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL JOIN requires a source'), E_USER_ERROR);

            return '';
        }

        // Query
        $query = 'JOIN ';

        if ($this->type) {
            // LEFT, RIGHT, …
            $query = $this->type . ' ' . $query;
        }

        // Table
        $query .= ' ' . $this->from[0] . ' ';

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'ON ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertSelectStatement
        $this->core->callBehavior('coreAfterJoinStatement', $this, $query);

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
    public function statement(): string
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

    /**
     * Run the SQL select query and return result
     *
     * @return     bool
     */
    public function delete(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * delete() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->delete();
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
    public function __construct(dcCore &$core, $ctx = null)
    {
        $this->set = [];

        parent::__construct($core, $ctx);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the reference clause(s)
     * @param boolean   $reset  reset previous reference first
     *
     * @return self instance, enabling to chain calls
     */
    public function reference($c, bool $reset = false): dcUpdateStatement
    {
        $this->from($c, $reset);

        return $this;
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the reference clause(s)
     * @param boolean   $reset  reset previous reference first
     *
     * @return self instance, enabling to chain calls
     */
    public function ref($c, bool $reset = false): dcUpdateStatement
    {
        return $this->reference($c, $reset);
    }

    /**
     * Adds update value(s)
     *
     * @param mixed     $c      the udpate values(s)
     * @param boolean   $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function set($c, bool $reset = false): dcUpdateStatement
    {
        if ($reset) {
            $this->set = [];
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
     * @return self instance, enabling to chain calls
     */
    public function sets($c, bool $reset = false): dcUpdateStatement
    {
        return $this->set($c, $reset);
    }

    /**
     * Returns the WHERE part of update statement
     *
     * Useful to construct the where clause used with cursor->update() method
     *
     * @return string The where part of update statement
     */
    public function whereStatement(): string
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
    public function statement(): string
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

    /**
     * Run the SQL update query
     *
     * @param      cursor|null  $cur    The cursor
     *
     * @return     bool
     */
    public function update(?cursor $cur = null): bool
    {
        if ($cur) {
            return $cur->update($this->whereStatement());
        }

        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * update() alias
     *
     * @param      cursor|null  $cur    The cursor
     *
     * @return     bool
     */
    public function run(?cursor $cur = null): bool
    {
        return $this->update($cur);
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
    public function __construct(dcCore &$core, $ctx = null)
    {
        $this->lines = [];

        parent::__construct($core, $ctx);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the into clause(s)
     * @param boolean   $reset  reset previous into first
     *
     * @return self instance, enabling to chain calls
     */
    public function into($c, bool $reset = false): dcInsertStatement
    {
        $this->from($c, $reset);

        return $this;
    }

    /**
     * Adds update value(s)
     *
     * @param mixed     $c      the insert values(s)
     * @param boolean   $reset  reset previous insert value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function lines($c, bool $reset = false): dcInsertStatement
    {
        if ($reset) {
            $this->lines = [];
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
     * @return self instance, enabling to chain calls
     */
    public function line($c, bool $reset = false): dcInsertStatement
    {
        return $this->lines($c, $reset);
    }

    /**
     * Returns the insert statement
     *
     * @return string the statement
     */
    public function statement(): string
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
            $raws = [];
            foreach ($this->lines as $line) {
                $raws[] = '(' . join(', ', $line) . ')';
            }
            $query .= join(', ', $raws);
        } else {
            // Use SQL default values
            // (useful only if SQL strict mode is off or if every columns has a defined default value)
            $query .= '()';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertInsertStatement
        $this->core->callBehavior('coreAfterInsertStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     bool  true
     */
    public function insert(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * insert() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->insert();
    }
}

/**
 * Truncate Statement : small utility to build truncate queries
 */
class dcTruncateStatement extends dcSqlStatement
{
    /**
     * Class constructor
     *
     * @param dcCore    $core   dcCore instance
     * @param mixed     $ctx    optional context
     */
    public function __construct(dcCore &$core, $ctx = null)
    {
        parent::__construct($core, $ctx);
    }

    /**
     * Returns the truncate statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeInsertStatement
        $this->core->callBehavior('coreBeforeTruncateStatement', $this);

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL TRUNCATE TABLE requires a table source'), E_USER_ERROR);

            return '';
        }

        // Query
        $query = 'TRUNCATE ';

        // Reference
        $query .= 'TABLE ' . $this->from[0] . ' ';

        $query = trim($query);

        # --BEHAVIOR-- coreAfertInsertStatement
        $this->core->callBehavior('coreAfterTruncateStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     bool
     */
    public function truncate(): bool
    {
        if ($this->con && ($sql = $this->statement())) {
            return $this->con->execute($sql);
        }

        return false;
    }

    /**
     * truncate() alias
     *
     * @return     bool
     */
    public function run(): bool
    {
        return $this->truncate();
    }
}
