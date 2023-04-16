<?php
/**
 * @class UpdateStatement
 *
 * Update Statement : small utility to build update queries
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use cursor;
use dcCore;

class UpdateStatement extends SqlStatement
{
    protected $set;

    /**
     * Constructs a new instance.
     *
     * @param      mixed         $con     The DB handle
     * @param      null|string   $syntax  The syntax
     */
    public function __construct($con = null, ?string $syntax = null)
    {
        $this->set = [];

        parent::__construct($con, $syntax);
    }

    /**
     * from() alias
     *
     * @param mixed     $c      the reference clause(s)
     * @param boolean   $reset  reset previous reference first
     *
     * @return self instance, enabling to chain calls
     */
    public function reference($c, bool $reset = false): UpdateStatement
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
    public function ref($c, bool $reset = false): UpdateStatement
    {
        return $this->from($c, $reset);
    }

    /**
     * Adds update value(s)
     *
     * @param mixed     $c      the udpate values(s)
     * @param boolean   $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function set($c, bool $reset = false): UpdateStatement
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
    public function sets($c, bool $reset = false): UpdateStatement
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
        # --BEHAVIOR-- coreBeforeUpdateWhereStatement -- SqlStatement
        if (class_exists('dcCore')) {
            dcCore::app()->callBehavior('coreBeforeUpdateWhereStatement', $this);
        }

        $query = '';

        // Where clause(s)
        if (count($this->where)) {
            $query .= 'WHERE ' . join(' AND ', $this->where) . ' ';
        }

        // Direct where clause(s)
        if (count($this->cond)) {
            if (!count($this->where)) {
                // Hack to cope with the operator included in top of each condition
                $query .= 'WHERE ' . ($this->syntax === 'sqlite' ? '1' : 'TRUE') . ' ';
            }
            $query .= join(' ', $this->cond) . ' ';
        }

        // Generic clause(s)
        if (count($this->sql)) {
            $query .= join(' ', $this->sql) . ' ';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateWhereStatement -- SqlStatement, string
        if (class_exists('dcCore')) {
            dcCore::app()->callBehavior('coreAfterUpdateWhereStatement', $this, $query);
        }

        return $query;
    }

    /**
     * Returns the update statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeUpdateStatement -- SqlStatement
        if (class_exists('dcCore')) {
            dcCore::app()->callBehavior('coreBeforeUpdateStatement', $this);
        }

        // Check if source given
        if (!count($this->from)) {
            trigger_error(__('SQL UPDATE requires a FROM source'), E_USER_ERROR);

            return '';  // @phpstan-ignore-line
        }

        // Query
        $query = 'UPDATE ';

        // Reference
        $query .= $this->from[0] . ' ';

        // Value(s)
        if (is_countable($this->set) ? count($this->set) : 0) {
            if (count($this->columns)) {
                $sets        = [];
                $formatValue = fn ($v) => is_string($v) ? $this->quote($v) : $v;
                for ($i = 0; $i < min(count($this->set), count($this->columns)) ; $i++) {
                    $sets[] = $this->columns[$i] . ' = ' . $formatValue($this->set[$i]);
                }
                $query .= 'SET ' . join(', ', $sets) . ' ';
            }
        }

        // Where
        $query .= $this->whereStatement();

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateStatement -- SqlStatement, string
        if (class_exists('dcCore')) {
            dcCore::app()->callBehavior('coreAfterUpdateStatement', $this, $query);
        }

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
