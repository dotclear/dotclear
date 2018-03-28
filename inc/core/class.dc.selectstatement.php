<?php
/**
 * @brief Select query statement builder
 *
 * dcSelectStatement is a class used to build select queries
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Bruno Hondelatte & Association Dotclear
 * @copyright GPL-2.0-only
 */

/**
 * Select Statement : small utility to build select queries
 */
class dcSelectStatement
{
    protected $columns;
    protected $from;
    protected $join;
    protected $where;
    protected $and;
    protected $sql;
    protected $having;
    protected $order;
    protected $group;
    protected $limit;
    protected $offset;
    protected $distinct;

    /**
     * Class constructor
     *
     * @param mixed $from   optional from clause(s)
     */
    public function __construct($from = null)
    {
        $this->columns =
        $this->from    =
        $this->join    =
        $this->where   =
        $this->cond    =
        $this->sql     =
        $this->having  =
        $this->order   =
        $this->group   =
        array();
        $this->limit    = null;
        $this->offset   = null;
        $this->distinct = false;

        if ($from !== null) {
            if (is_array($from)) {
                $this->froms($from);
            } else {
                $this->from($from);
            }
        }
    }

    /**
     * Adds a new column
     *
     * @param $c the column
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function column($c)
    {
        array_push($this->columns, $c);
        return $this;
    }
    /**
     * adds a list of columns
     *
     * @param array $c the list of columns
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function columns($c)
    {
        $this->columns = array_merge($this->columns, $c);
        return $this;
    }

    /**
     * Adds a FROM clause
     *
     * @param string $c the from clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function from($c)
    {
        $c = trim(ltrim($c, ',')); // Cope with legacy code
        array_push($this->from, $c);
        return $this;
    }

    /**
     * Adds a list of FROM clauses
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function froms($c)
    {
        $c          = array_map(trim(ltrim($c, ',')), $c); // Cope with legacy code
        $this->from = array_merge($this->from, $c);
        return $this;
    }

    /**
     * Adds a JOIN clause (applied on first from item only)
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function join($c)
    {
        array_push($this->join, $c);
        return $this;
    }

    /**
     * Adds a list of JOIN clauses (applied on first from item only)
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function joins($c)
    {
        $this->join = array_merge($this->join, $c);
        return $this;
    }

    /**
     * Adds a WHERE clause condition (each will be AND combined in statement)
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function where($c)
    {
        array_push($this->where, $c);
        return $this;
    }

    /**
     * Adds a list of WHERE clauses (each will be AND combined in statement)
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function wheres($c)
    {
        $this->where = array_merge($this->where, $c);
        return $this;
    }

    /**
     * Adds a WHERE clause additional condition (including an operator at beginning)
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function cond($c) {
        array_push($this->cond, $c);
        return $this;
    }

    /**
     * Adds a list of WHERE clause additional conditions (each including an operator at beginning)
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function conds($c)
    {
        $this->cond = array_merge($this->cond, $c);
        return $this;
    }

    /**
     * Adds a generic clause
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function sql($c)
    {
        array_push($this->sql, $c);
        return $this;
    }

    /**
     * Adds a list of generic clauses
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function sqls($c)
    {
        $this->sql = array_merge($this->sql, $c);
        return $this;
    }

    /**
     * Adds a HAVING clause
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function having($c)
    {
        array_push($this->having, $c);
        return $this;
    }

    /**
     * Adds a list of HAVING clauses (will be AND combined in statement)
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function havings($c)
    {
        $this->having = array_merge($this->having, $c);
        return $this;
    }

    /**
     * Adds an ORDER BY clause
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function order($c)
    {
        array_push($this->order, $c);
        return $this;
    }

    /**
     * Adds a list of ORDER BY clauses
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function orders($c)
    {
        $this->order = array_merge($this->order, $c);
        return $this;
    }

    /**
     * Adds an GROUP BY clause
     *
     * @param string $c the clause
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function group($c)
    {
        array_push($this->group, $c);
        return $this;
    }

    /**
     * Adds a list of GROUP BY clauses
     *
     * @param array $c the list of clauses
     * @return dcSelectStatement self instance, enabling to chain calls
     */
    public function groups($c)
    {
        $this->order = array_merge($this->order, $c);
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
    public function distinct($distinct)
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

        // Order by clause (columns or aliases and optionnaly order ASC/DESC)
        if (count($this->order)) {
            $query .= 'ORDER BY ' . join(', ', $this->order) . ' ';
        }

        // Group by clause (columns or aliases)
        if (count($this->group)) {
            $query .= 'GROUP BY ' . join(', ', $this->group) . ' ';
        }

        // Having clause(s)
        if (count($this->having)) {
            $query .= 'HAVING ' . join(' AND ', $this->having) . ' ';
        }

        // Limit clause
        if ($this->limit !== null) {
            $query .= 'LIMIT ' . $this->limit . ' ';
        }

        // Offset clause
        if ($this->offset !== null) {
            $query .= 'OFFSET ' . $this->offset . ' ';
        }

        return $query;
    }
}
