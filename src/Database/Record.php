<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Countable;
use Iterator;
use ReflectionClass;

/**
 * @class Record
 *
 * Query Result Record Class
 *
 * This class acts as an iterator over database query result. It does not fetch
 * all results on instantiation and thus, depending on database engine, should not
 * fill PHP process memory.
 *
 * @implements Iterator<int, array<mixed>>
 */
class Record implements Iterator, Countable
{
    /**
     * Database resource link
     *
     * @var mixed
     */
    protected $__link;

    /**
     * List of static functions that extend Record
     *
     * @var        array<string, callable>
     */
    protected $__extend = [];

    /**
     * Current result position
     *
     * @var        int
     */
    protected $__index = 0;

    /**
     * Current result row content
     *
     * @var        array<mixed>
     */
    protected $__row = [];

    /**
     * Fetch occured once?
     *
     * @var        bool
     */
    private $__fetch = false;

    /**
     * Constructor
     *
     * Creates class instance from result link and some informations.
     * <var>$info</var> is an array with the following content:
     *
     * - con => database object instance
     * - cols => number of columns
     * - rows => number of rows
     * - info[name] => an array with columns names
     * - info[type] => an array with columns types
     *
     * @param mixed                     $__result      Resource result
     * @param array<string, mixed>      $__info        Information array
     */
    public function __construct(
        protected $__result,
        protected array $__info
    ) {
        $this->__link = $this->__info['con']->link();

        // Move to first row
        $this->index(0);
    }

    /**
     * To StaticRecord
     *
     * Converts this Record to a {@link StaticRecord} instance.
     */
    public function toStatic(): StaticRecord
    {
        if ($this instanceof StaticRecord) {
            return $this;
        }

        return new StaticRecord($this->__result, $this->__info);
    }

    /**
     * Magic call
     *
     * Magic call function. Calls function added by {@link extend()} if exists, passing it
     * self object and arguments.
     *
     * @param string $f     Function name
     * @param mixed  $args  Arguments
     *
     * @return mixed
     */
    public function __call(string $f, $args)
    {
        if (isset($this->__extend[$f])) {
            return $this->__extend[$f]($this, ...$args);
        }

        trigger_error('Call to undefined method Record::' . $f . '()', E_USER_WARNING);
    }

    /**
     * Magic get
     *
     * Alias for {@link field()}.
     *
     * @param string|int    $n        Field name or field position
     *
     * @return mixed
     */
    public function __get($n)
    {
        return $this->field($n);
    }

    /**
     * Get field
     *
     * Alias for {@link field()}.
     *
     * @param string|int    $n        Field name or field position
     *
     * @return mixed
     */
    public function f($n)
    {
        return $this->field($n);
    }

    /**
     * Get field
     *
     * Retrieve field value by its name or column position.
     *
     * @param string|int    $n        Field name or field position
     *
     * @return mixed
     */
    public function field($n)
    {
        return $this->__row[$n] ?? null;
    }

    /**
     * Field exists
     *
     * Returns true if a field exists.
     *
     * @param string|int     $n        Field name or field position
     *
     * @return bool
     */
    public function exists($n): bool
    {
        return isset($this->__row[$n]);
    }

    /**
     * Field isset
     *
     * Returns true if a field exists (magic method from PHP 5.1).
     *
     * @param string        $n        Field name
     *
     * @return bool
     */
    public function __isset(string $n): bool
    {
        return isset($this->__row[$n]);
    }

    /**
     * Extend Record
     *
     * Extends this instance capabilities by adding all public static methods of
     * <var>$class</var> to current instance. Class methods should take at least
     * this record as first parameter.
     *
     * @see __call()
     *
     * @param string    $class        Class name
     */
    public function extend(string $class): void
    {
        if (!class_exists($class)) {
            return;
        }

        $c = new ReflectionClass($class);
        foreach ($c->getMethods() as $m) {
            if ($m->isStatic() && $m->isPublic()) {
                $this->__extend[$m->name] = [$class, $m->name]; // @phpstan-ignore-line
            }
        }
    }

    /**
     * Returns Record extensions.
     *
     * @return  array<string, callable>
     */
    public function extensions(): array
    {
        return $this->__extend;
    }

    /**
     * Sets the row data from result.
     *
     * @return     bool
     */
    private function setRow(): bool
    {
        $this->__row = $this->__info['con']->db_fetch_assoc($this->__result);

        if ($this->__row !== false) {
            foreach (array_keys($this->__row) as $k) {
                $this->__row[] = &$this->__row[$k];
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the current index position (0 is first) or move to <var>$row</var> if
     * specified.
     *
     * @param int    $row            Row number to move
     *
     * @return int|boolean
     */
    public function index(?int $row = null)
    {
        if ($row === null) {
            return $this->__index === null ? 0 : $this->__index;
        }

        if ($row < 0 || $row + 1 > $this->__info['rows']) {
            return false;
        }

        if ($this->__info['con']->db_result_seek($this->__result, (int) $row)) {
            $this->__index = $row;
            $this->setRow();
            $this->__info['con']->db_result_seek($this->__result, (int) $row);

            return true;
        }

        return false;
    }

    /**
     * One step move index
     *
     * This method moves index forward and return true until index is not
     * the last one. You can use it to loop over record. Example:
     * ```php
     * while ($rs->fetch()) {
     *     echo $rs->field1;
     * }
     * ```
     *
     * @return bool
     */
    public function fetch(): bool
    {
        if (!$this->__fetch) {
            $this->__fetch = true;
            $i             = -1;
        } else {
            $i = $this->__index;
        }

        if (!$this->index($i + 1)) {
            $this->__fetch = false;
            $this->__index = 0;

            return false;
        }

        return true;
    }

    /**
     * Moves index to first position.
     *
     * @return bool
     */
    public function moveStart(): bool
    {
        $this->__fetch = false;

        return (bool) $this->index(0);
    }

    /**
     * Moves index to last position.
     *
     * @return bool
     */
    public function moveEnd(): bool
    {
        return (bool) $this->index((int) $this->__info['rows'] - 1);
    }

    /**
     * Moves index to next position.
     *
     * @return bool
     */
    public function moveNext(): bool
    {
        return (bool) $this->index($this->__index + 1);
    }

    /**
     * Moves index to previous position.
     *
     * @return bool
     */
    public function movePrev(): bool
    {
        return (bool) $this->index($this->__index - 1);
    }

    /**
     * @return bool   true if index is at last position
     */
    public function isEnd(): bool
    {
        return $this->__index + 1 === $this->count();
    }

    /**
     * @return bool    true if index is at first position.
     */
    public function isStart(): bool
    {
        return $this->__index <= 0;
    }

    /**
     * @return bool    true if record contains no result.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return int    number of rows in record
     */
    public function count(): int
    {
        return $this->__info['rows'];
    }

    /**
     * @return array<string>   array of columns names
     */
    public function columns(): array
    {
        return $this->__info['info']['name'];
    }

    /**
     * @return array<array<mixed>>    all rows in record.
     */
    public function rows(): array
    {
        return $this->getData();
    }

    /**
     * All data
     *
     * Returns an array of all rows in record. This method is called by rows().
     *
     * @return array<array<mixed>>
     */
    protected function getData(): array
    {
        $res = [];

        if ($this->count() === 0) {
            return $res;
        }

        $this->__info['con']->db_result_seek($this->__result, 0);
        while (($r = $this->__info['con']->db_fetch_assoc($this->__result)) !== false) {
            foreach (array_keys($r) as $k) {
                $r[] = &$r[$k];
            }
            $res[] = $r;
        }
        $this->__info['con']->db_result_seek($this->__result, $this->__index);

        return $res;
    }

    /**
     * @return array<mixed>    current rows.
     */
    public function row()
    {
        return $this->__row;
    }

    /* Iterator methods */

    /**
     * @see Iterator::current
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this;   // @phpstan-ignore-line
    }

    /**
     * @see Iterator::key
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->index();  // @phpstan-ignore-line
    }
    /**
     * @see Iterator::next
     */
    public function next(): void
    {
        $this->fetch();
    }

    /**
     * @see Iterator::rewind
     */
    public function rewind(): void
    {
        $this->moveStart();
        $this->fetch();
    }

    /**
     * @see Iterator::valid
     */
    public function valid(): bool
    {
        return $this->__fetch;
    }
}
