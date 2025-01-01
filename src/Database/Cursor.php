<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Database\Statement\InsertStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Exception;

/**
 * @class Cursor
 *
 * This class implements facilities to insert or update in a table.
 */
class Cursor
{
    /**
     * @var        AbstractHandler
     */
    private $__con;

    /**
     * @var        array<string, mixed>
     */
    private array $__data = [];

    private string $__table;

    /**
     * Constructor
     *
     * Init Cursor object on a given table. Note that you can init it with
     * {@link AbstractHandler::openCursor() openCursor()} method of your connection object.
     *
     * Example:
     * ```php
     *    $cur = $con->openCursor('table');
     *    $cur->field1 = 1;
     *    $cur->field2 = 'foo';
     *    $cur->insert(); // Insert field ...
     *
     *    $cur->update('WHERE field3 = 4'); // ... or update field
     * ```
     *
     * @see AbstractHandler::openCursor()
     *
     * @param AbstractHandler   $con      Connection object
     * @param string            $table    Table name
     */
    public function __construct(AbstractHandler $con, string $table)
    {
        $this->__con = &$con;
        $this->setTable($table);
    }

    /**
     * Set table
     *
     * Changes working table and resets data
     *
     * @param string    $table    Table name
     */
    public function setTable(string $table): void
    {
        $this->__table = $table;
        $this->__data  = [];
    }

    /**
     * Set field
     *
     * Set value <var>$value</var> to a field named <var>$name</var>. Value could be
     * an string, an integer, a float, a null value or an array.
     *
     * If value is an array, its first value will be interpreted as a SQL
     * command. String values will be automatically escaped.
     *
     * @see __set()
     *
     * @param string    $name        Field name
     * @param mixed     $value       Field value
     */
    public function setField(string $name, $value): void
    {
        $this->__data[$name] = is_array($value) ? $value[0] : $value;
    }

    /**
     * Unset field
     *
     * Remove a field from data set.
     *
     * @param string    $name        Field name
     */
    public function unsetField(string $name): void
    {
        unset($this->__data[$name]);
    }

    /**
     * Field exists
     *
     * @return boolean    true if field named <var>$name</var> exists
     */
    public function isField(string $name): bool
    {
        return isset($this->__data[$name]);
    }

    /**
     * Field value
     *
     * @see __get()
     *
     * @return mixed    value for a field named <var>$name</var>
     */
    public function getField(string $name)
    {
        return $this->__data[$name] ?? null;
    }

    /**
     * Set Field
     *
     * Magic alias for {@link setField()}
     *
     * @param string    $name        Field name
     * @param mixed     $value       Field value
     */
    public function __set(string $name, $value): void
    {
        $this->setField($name, $value);
    }

    /**
     * Field value
     *
     * Magic alias for {@link getField()}
     *
     * @return mixed    value for a field named <var>$n</var>
     */
    public function __get(string $name)
    {
        return $this->getField($name);
    }

    /**
     * Empty data set
     *
     * Removes all data from data set
     */
    public function clean(): void
    {
        $this->__data = [];
    }

    /**
     * Get insert query
     *
     * Returns the generated INSERT query
     */
    public function getInsert(): string
    {
        $sql = new InsertStatement($this->__con);
        $sql
            ->into($this->__table)
            ->columns(array_keys($this->__data))
            ->values([array_values($this->__data)])
        ;

        return $sql->statement();
    }

    /**
     * Get update query
     *
     * Returns the generated UPDATE query
     *
     * @param string    $where        WHERE condition
     */
    public function getUpdate(string $where): string
    {
        // Legacy: remove WHERE from beginning of $where arg
        $where = trim((string) preg_replace('/^(?:\s*)WHERE(.*?)$/i', '$1', $where, 1));

        $sql = new UpdateStatement($this->__con);
        $sql
            ->from($this->__table)
            ->columns(array_keys($this->__data))
            ->values(array_values($this->__data))
            ->where($where)
        ;

        return $sql->statement();
    }

    /**
     * Execute insert query
     *
     * Executes the generated INSERT query
     */
    public function insert(): bool
    {
        if ($this->__table === '') {
            throw new Exception('No table name.');
        }

        $insReq = $this->getInsert();

        $this->__con->execute($insReq);

        return true;
    }

    /**
     * Execute update query
     *
     * Executes the generated UPDATE query
     *
     * @param string    $where        WHERE condition
     */
    public function update(string $where): bool
    {
        if ($this->__table === '') {
            throw new Exception('No table name.');
        }

        $updReq = $this->getUpdate($where);
        $this->__con->execute($updReq);

        return true;
    }
}
