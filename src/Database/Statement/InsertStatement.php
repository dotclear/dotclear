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
use Dotclear\Interface\Database\ConnectionInterface;

/**
 * @class InsertStatement
 *
 * Insert Statement : small utility to build insert queries
 */
class InsertStatement extends SqlStatement
{
    /**
     * @var array<array-key, string|string[]>     $lines
     */
    protected array $lines = [];

    /**
     * from() alias
     *
     * @param null|string|string[]  $c      the into clause(s)
     * @param boolean               $reset  reset previous into first
     *
     * @return self instance, enabling to chain calls
     */
    public function into(null|string|array $c, bool $reset = false): InsertStatement
    {
        $this->from($c, $reset);

        return $this;
    }

    /**
     * Adds update value(s)
     *
     * @param string|string[]   $c      the insert values(s)
     * @param boolean           $reset  reset previous insert value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function lines(string|array $c, bool $reset = false): InsertStatement
    {
        if ($reset) {
            $this->lines = [];
        }

        $this->lines = is_array($c) ? [...$this->lines, ...$c] : [...$this->lines, $c];

        return $this;
    }

    /**
     * line() alias
     *
     * @param      string    $c      the insert value(s)
     * @param      boolean   $reset  reset previous insert value(s) first
     *
     * @return self instance, enabling to chain calls
     */
    public function line(string $c, bool $reset = false): InsertStatement
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

        $rows = [];
        foreach ($c as $line) {
            if (is_array($line)) {
                $values = array_map(fn ($value): string => $this->formatValue($value, true), $line);
                $rows[] = implode(', ', $values);
            } else {
                $rows[] = $this->formatValue($line, false);
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
            trigger_error(__('SQL INSERT requires an INTO source.'), E_USER_WARNING);
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
                if (is_array($line)) {
                    $values = array_map(fn (string $value): string => $this->formatValue($value, false), $line);
                    $row    = implode(', ', $values);
                } else {
                    $row = $this->formatValue($line, false);
                }

                $rows[] = '(' . $row . ')';
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
        if ($this->con instanceof ConnectionInterface && ($sql = $this->statement())) {
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
