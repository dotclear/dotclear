<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database\Statement;

use Dotclear\Database\Cursor;
use Dotclear\App;

/**
 * @class UpdateStatement
 *
 * Update Statement : small utility to build update queries
 */
class UpdateStatement extends SqlStatement
{
    /**
     * List of fields
     *
     * @var        array<string>
     */
    protected array $sets = [];

    /**
     * List of values
     *
     * @var        array<mixed>
     */
    protected array $values = [];

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
        $this->from($c, $reset);

        return $this;
    }

    /**
     * Adds update set(s) (column = value)
     *
     * @param string|array<string>     $c      the udpate values(s)
     * @param boolean                  $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function set($c, bool $reset = false): UpdateStatement
    {
        if ($reset) {
            $this->sets = [];
        }
        if (is_array($c)) {
            $this->sets = [...$this->sets, ...$c];
        } else {
            $this->sets[] = $c;
        }

        return $this;
    }

    /**
     * set() alias
     *
     * @param      string|array<string>     $c      the update value(s)
     * @param      boolean                  $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function sets($c, bool $reset = false): UpdateStatement
    {
        return $this->set($c, $reset);
    }

    /**
     * Adds update value(s) (needs fields/columns)
     *
     * @param mixed|array<mixed>    $c      the udpate values(s)
     * @param boolean               $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function value($c, bool $reset = false): UpdateStatement
    {
        if ($reset) {
            $this->values = [];
        }
        if (is_array($c)) {
            $this->values = [...$this->values, ...$c];
        } else {
            $this->values[] = $c;
        }

        return $this;
    }

    /**
     * value() alias
     *
     * @param      mixed|array<mixed>   $c      the update value(s)
     * @param      boolean              $reset  reset previous update value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function values($c, bool $reset = false): UpdateStatement
    {
        return $this->value($c, $reset);
    }

    /**
     * Returns the WHERE part of update statement
     *
     * Useful to construct the where clause used with Cursor->update() method
     *
     * @return string The where part of update statement
     */
    public function whereStatement(): string
    {
        # --BEHAVIOR-- coreBeforeUpdateWhereStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeUpdateWhereStatement', $this);

        $query = '';

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

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateWhereStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterUpdateWhereStatement', $this, $query);

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
        App::behavior()->callBehavior('coreBeforeUpdateStatement', $this);

        // Check if source given
        if ($this->from === []) {
            trigger_error(__('SQL UPDATE requires a FROM source'), E_USER_WARNING);
        }

        // Query
        $query = 'UPDATE ';

        // Reference
        $query .= $this->from[0] . ' ';

        $sets = [];
        // Value(s)
        if (count($this->values) && count($this->columns)) {
            $formatValue = fn ($v) => is_string($v) ? $this->quote($v) : (is_null($v) ? 'NULL' : $v);
            for ($i = 0; $i < min(count($this->values), count($this->columns)) ; $i++) {
                $sets[] = $this->columns[$i] . ' = ' . $formatValue($this->values[$i]);
            }
        }
        // Set(s)
        if ($this->sets !== []) {
            $sets = array_merge($sets, $this->sets);
        }
        if ($sets !== []) {
            $query .= 'SET ' . implode(', ', $sets) . ' ';
        }

        // Where
        $query .= $this->whereStatement();

        $query = trim($query);

        # --BEHAVIOR-- coreAfertUpdateStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterUpdateStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL update query
     *
     * @param      Cursor|null  $cur    The Cursor
     */
    public function update(?Cursor $cur = null): bool
    {
        if ($cur instanceof Cursor) {
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
     * @param      Cursor|null  $cur    The Cursor
     */
    public function run(?Cursor $cur = null): bool
    {
        return $this->update($cur);
    }
}
