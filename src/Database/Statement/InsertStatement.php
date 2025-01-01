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

/**
 * @class InsertStatement
 *
 * Insert Statement : small utility to build insert queries
 */
class InsertStatement extends SqlStatement
{
    /**
     * @var array<mixed>
     */
    protected array $lines = [];

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
     * @param mixed     $c      the into clause(s)
     * @param boolean   $reset  reset previous into first
     *
     * @return self instance, enabling to chain calls
     */
    public function into($c, bool $reset = false): InsertStatement
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
    public function lines($c, bool $reset = false): InsertStatement
    {
        if ($reset) {
            $this->lines = [];
        }
        if (is_array($c)) {
            $this->lines = [...$this->lines, ...$c];
        } else {
            $this->lines[] = [$c];
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
    public function line($c, bool $reset = false): InsertStatement
    {
        return $this->lines([$c], $reset);
    }

    /**
     * Adds update value(s), usually given as array of array of values
     *
     * @param array<mixed>      $c      the insert values(s)
     * @param boolean           $reset  reset previous insert value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function values(array $c, bool $reset = false): InsertStatement
    {
        if ($reset) {
            $this->lines = [];
        }
        $rows        = [];
        $formatValue = fn ($v) => is_string($v) ? $this->quote($v) : (is_null($v) ? 'NULL' : $v);
        foreach ($c as $line) {
            if (is_array($line)) {
                $values = array_map($formatValue, $line);
                $rows[] = implode(', ', $values);
            } else {
                $rows[] = $line;
            }
        }
        if ($rows !== []) {
            $this->lines($rows);
        }

        return $this;
    }

    /**
     * Returns the insert statement
     *
     * @return string the statement
     */
    public function statement(): string
    {
        # --BEHAVIOR-- coreBeforeInsertStatement -- SqlStatement
        App::behavior()->callBehavior('coreBeforeInsertStatement', $this);

        // Check if source given
        if ($this->from === []) {
            trigger_error(__('SQL INSERT requires an INTO source'), E_USER_WARNING);
        }

        // Query
        $query = 'INSERT ';

        // Reference
        $query .= 'INTO ' . $this->from[0] . ' ';

        // Column(s)
        if ($this->columns !== []) {
            $query .= '(' . implode(', ', $this->columns) . ') ';
        }

        // Value(s)
        $query .= 'VALUES ';
        if ($this->lines !== []) {
            $rows = [];
            foreach ($this->lines as $line) {
                $rows[] = '(' . (is_array($line) ? implode(', ', $line) : $line) . ')';
            }
            $query .= implode(', ', $rows);
        } else {
            // Use SQL default values
            // (useful only if SQL strict mode is off or if every columns has a defined default value)
            $query .= '()';
        }

        $query = trim($query);

        # --BEHAVIOR-- coreAfertInsertStatement -- SqlStatement, string
        App::behavior()->callBehavior('coreAfterInsertStatement', $this, $query);

        return $query;
    }

    /**
     * Run the SQL select query and return result
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
     */
    public function run(): bool
    {
        return $this->insert();
    }
}
