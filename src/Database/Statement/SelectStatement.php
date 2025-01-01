<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use Dotclear\App;
use Dotclear\Database\MetaRecord;

/**
 * @class SelectStatement
 *
 * Select Statement : small utility to build select queries
 */
class SelectStatement extends SqlStatement
{
    /**
     * @var array<string>
     */
    protected array $join = [];

    /**
     * @var array<string>
     */
    protected array $union = [];

    /**
     * @var array<string>
     */
    protected array $having = [];

    /**
     * @var array<string>
     */
    protected array $order = [];

    /**
     * @var array<string>
     */
    protected array $group = [];

    /**
     * @var null|int|string
     */
    protected $limit;

    /**
     * @var null|int|string
     */
    protected $offset;

    protected bool $distinct = false;

    /**
     * Constructs a new instance.
     *
     * @param      mixed         $con     The DB handle
     * @param      null|string   $syntax  The syntax
     */
    public function __construct($con = null, ?string $syntax = null)
    {
        parent::__construct($con, $syntax);
    }

    /**
     * Adds JOIN clause(s) (applied on first from item only)
     *
     * @param mixed     $c      the join clause(s)
     * @param boolean   $reset  reset previous join(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function join($c, bool $reset = false): SelectStatement
    {
        if ($reset) {
            $this->join = [];
        }
        if (is_array($c)) {
            $this->join = [...$this->join, ...$c];
        } else {
            $this->join[] = $c;
        }

        return $this;
    }

    /**
     * Adds UNION clause(s)
     *
     * @param mixed     $c      the union clause(s)
     * @param boolean   $reset  reset previous union(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function union($c, bool $reset = false): SelectStatement
    {
        if ($reset) {
            $this->union = [];
        }
        if (is_array($c)) {
            $this->union = [...$this->union, ...$c];
        } else {
            $this->union[] = $c;
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
    public function having($c, bool $reset = false): SelectStatement
    {
        if ($reset) {
            $this->having = [];
        }
        if (is_array($c)) {
            $this->having = [...$this->having, ...$c];
        } else {
            $this->having[] = $c;
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
    public function order($c, bool $reset = false): SelectStatement
    {
        if ($reset) {
            $this->order = [];
        }
        if (is_array($c)) {
            $this->order = [...$this->order, ...$c];
        } else {
            $this->order[] = $c;
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
    public function group($c, bool $reset = false): SelectStatement
    {
        if ($reset) {
            $this->group = [];
        }
        if (is_array($c)) {
            $this->group = [...$this->group, ...$c];
        } else {
            $this->group[] = $c;
        }

        return $this;
    }

    /**
     * group() alias
     *
     * @param mixed     $c      the clause(s)
     * @param boolean   $reset  reset previous group(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function groupBy($c, bool $reset = false): SelectStatement
    {
        return $this->group($c, $reset);
    }

    /**
     * Defines the LIMIT for select
     *
     * @param mixed $limit (limit or [offset,limit])
     *
     * @return self instance, enabling to chain calls
     */
    public function limit($limit): SelectStatement
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
     *
     * @return self instance, enabling to chain calls
     */
    public function offset(int $offset): SelectStatement
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Defines the DISTINCT flag for select
     *
     *
     * @return self instance, enabling to chain calls
     */
    public function distinct(bool $distinct = true): SelectStatement
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
        # --BEHAVIOR-- coreBeforeSelectStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeSelectStatement', $this);

        // Check if source given
        if ($this->from === []) {
            trigger_error(__('SQL SELECT requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'SELECT ' . ($this->distinct ? 'DISTINCT ' : '');

        // Specific column(s) or all (*)
        if ($this->columns !== []) {
            $query .= implode(', ', $this->columns) . ' ';
        } else {
            $query .= '* ';
        }

        // Table(s) and Join(s)
        $query .= 'FROM ' . $this->from[0] . ' ';
        if ($this->join !== []) {
            $query .= implode(' ', $this->join) . ' ';
        }
        if (count($this->from) > 1) {
            $query = trim($query) . ', ' . implode(', ', array_slice($this->from, 1)) . ' '; // All other from(s)
        }

        // Where clause(s)
        if ($this->where !== []) {
            $query .= 'WHERE ' . implode(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if ($this->cond !== []) {
            if ($this->where === []) {
                // Hack to cope with the operator included in top of each condition
                $query .= 'WHERE ' . ($this->syntax === 'sqlite' ? '1' : 'TRUE') . ' ';
            }
            $query .= implode(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if ($this->sql !== []) {
            $query .= implode(' ', $this->sql) . ' ';
        }

        // Group by clause (columns or aliases)
        if ($this->group !== []) {
            $query .= 'GROUP BY ' . implode(', ', $this->group) . ' ';
        }

        // Having clause(s)
        if ($this->having !== []) {
            $query .= 'HAVING ' . implode(' AND ', $this->having) . ' ';
        }

        // Union clause(s)
        if ($this->union !== []) {
            $query .= 'UNION ' . implode(' UNION ', $this->union) . ' ';
        }

        // Clauses applied on result
        // -------------------------

        // Order by clause (columns or aliases and optionnaly order ASC/DESC)
        if ($this->order !== []) {
            $query .= 'ORDER BY ' . implode(', ', $this->order) . ' ';
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

        # --BEHAVIOR-- coreAfertSelectStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterSelectStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
     *
     * @return     MetaRecord  record
     */
    public function select(): ?MetaRecord
    {
        if ($this->con && ($sql = $this->statement())) {
            return new MetaRecord($this->con->select($sql));
        }

        return null;
    }

    /**
     * select() alias
     *
     * @return     MetaRecord  record
     */
    public function run(): ?MetaRecord
    {
        return $this->select();
    }
}
