<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Countable;
use Iterator;

/**
 * @brief   MetaRecord class
 *
 * @implements Iterator<int, array<mixed>>
 */
#[\AllowDynamicProperties]
class MetaRecord implements Iterator, Countable
{
    /**
     * Record object
     */
    protected Record $dynamic;

    /**
     * Static record object
     */
    protected StaticRecord $static;

    /**
     * Constructs a new instance.
     */
    public function __construct(Record|StaticRecord $record)
    {
        if ($record instanceof StaticRecord) {
            $this->static = $record;
        } else {
            $this->dynamic = $record;
        }
    }

    /**
     * To static Record
     *
     * Converts the dynamic record to a {@link StaticRecord} instance.
     *
     * Note:    All MetaRecord methods (unless StaticRecord specific, see at end of this file) will try first
     *          with the StaticRecord instance, if exist.
     *
     * @return     self  Static representation of the object.
     */
    public function toStatic(): self
    {
        if ($this->hasDynamic()) {
            $this->static = $this->dynamic->toStatic();
        }

        return $this;
    }

    /**
     * Alias of toStatic()
     *
     * @return     self  Static representation of the object.
     *
     * @deprecated Since 2.26 use toStatic() instead
     */
    public function toExtStatic(): self
    {
        return $this->toStatic();
    }

    /**
     * Check if MetaRecord has static data
     */
    public function hasStatic(): bool
    {
        return isset($this->static);
    }

    /**
     * Check if MetaRecord has dynamic data
     */
    public function hasDynamic(): bool
    {
        return isset($this->dynamic);
    }

    /**
     * Magic call
     *
     * Magic call function. Calls function added by {@link extend()} if exists, passing it
     * self object and arguments.
     *
     * @param string $f     Function name
     * @param mixed  $args  Arguments
     */
    public function __call(string $f, mixed $args): mixed
    {
        // Search method in StaticRecord instance first
        if ($this->hasStatic()) {
            $extensions = $this->static->extensions();
            if (isset($extensions[$f])) {
                return $extensions[$f]($this, ...$args);
            }
        }

        // Then search method in record instance
        if ($this->hasDynamic()) {
            $extensions = $this->dynamic->extensions();
            if (isset($extensions[$f])) {
                return $extensions[$f]($this, ...$args);
            }
        }

        trigger_error('Call to undefined method ' . $f . '()', E_USER_WARNING);

        return null;
    }

    /**
     * Magic get
     *
     * Alias for {@link field()}.
     *
     * @param string|int    $n        Field name or field position
     */
    public function __get(string|int $n): mixed
    {
        return $this->field($n);
    }

    /**
     * Get field
     *
     * Alias for {@link field()}.
     *
     * @param string|int    $n        Field name or field position
     */
    public function f(string|int $n): mixed
    {
        return $this->field($n);
    }

    /**
     * Get field value
     *
     * @param      string|int  $n      Field name|position
     */
    public function field(string|int $n): mixed
    {
        return $this->hasStatic() ? $this->static->field($n) : $this->dynamic->field($n);
    }

    /**
     * Check if a field exists
     *
     * @param      string|int  $n      Field name|position
     */
    public function exists(string|int $n): bool
    {
        return $this->hasStatic() ? $this->static->exists($n) : $this->dynamic->exists($n);
    }

    /**
     * Field isset
     *
     * Returns true if a field exists.
     *
     * @param string        $n        Field name
     */
    public function __isset(string $n): bool
    {
        return $this->hasStatic() ? $this->static->__isset($n) : $this->dynamic->__isset($n);
    }

    /**
     * Extend record
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
        if ($this->hasStatic()) {
            $this->static->extend($class);
        }

        if ($this->hasDynamic()) {
            $this->dynamic->extend($class);
        }
    }

    /**
     * Returns record extensions.
     *
     * @return  array<string, callable>
     */
    public function extensions(): array
    {
        $extensions = [];
        if ($this->hasStatic()) {
            $extensions = [...$this->static->extensions()];
        }

        if ($this->hasDynamic()) {
            return [...$extensions, ...$this->dynamic->extensions()];
        }

        return $extensions;
    }

    /**
     * Get current index
     *
     * @param      int   $row    The row
     *
     * @return ($row is null ? int : bool)
     */
    public function index(?int $row = null): bool|int
    {
        return $this->hasStatic() ? $this->static->index($row) : $this->dynamic->index($row);
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
     */
    public function fetch(): bool
    {
        return $this->hasStatic() ? $this->static->fetch() : $this->dynamic->fetch();
    }

    /**
     * Moves index to first position.
     */
    public function moveStart(): bool
    {
        return $this->hasStatic() ? $this->static->moveStart() : $this->dynamic->moveStart();
    }

    /**
     * Moves index to last position.
     */
    public function moveEnd(): bool
    {
        return $this->hasStatic() ? $this->static->moveEnd() : $this->dynamic->moveEnd();
    }

    /**
     * Moves index to next position.
     */
    public function moveNext(): bool
    {
        return $this->hasStatic() ? $this->static->moveNext() : $this->dynamic->moveNext();
    }

    /**
     * Moves index to previous position.
     */
    public function movePrev(): bool
    {
        return $this->hasStatic() ? $this->static->movePrev() : $this->dynamic->movePrev();
    }

    /**
     * Check if index is at last position
     */
    public function isEnd(): bool
    {
        return $this->hasStatic() ? $this->static->isEnd() : $this->dynamic->isEnd();
    }

    /**
     * Check if index is at first position
     */
    public function isStart(): bool
    {
        return $this->hasStatic() ? $this->static->isStart() : $this->dynamic->isStart();
    }

    /**
     * Check if record is empty (no result)
     */
    public function isEmpty(): bool
    {
        return $this->hasStatic() ? $this->static->isEmpty() : $this->dynamic->isEmpty();
    }

    /**
     * Get columns
     *
     * @return array<string>    array of columns.
     */
    public function columns(): array
    {
        return $this->hasStatic() ? $this->static->columns() : $this->dynamic->columns();
    }

    /**
     * Get record rows
     *
     * @return     array<array<mixed>>
     */
    public function rows(): array
    {
        return $this->hasStatic() ? $this->static->rows() : $this->dynamic->rows();
    }

    /**
     * @return array<mixed>    current rows.
     */
    public function row(): array
    {
        return $this->hasStatic() ? $this->static->row() : $this->dynamic->row();
    }

    // Countable methods

    /**
     * @return int    number of rows in record
     */
    public function count(): int
    {
        return $this->hasStatic() ? $this->static->count() : $this->dynamic->count();
    }

    // Iterator methods

    /**
     * Warning: This method will return the current instance rather than current element
     *
     * @see Iterator::current
     */
    #[\ReturnTypeWillChange]
    public function current(): mixed
    {
        // @phpstan-ignore return.type (should return mixed rather than MetaRecord instance)
        return $this;
    }

    /**
     * @see Iterator::key
     */
    public function key(): mixed
    {
        return $this->index();
    }

    /**
     * @see Iterator::next
     */
    public function next(): void
    {
        if ($this->hasStatic()) {
            $this->static->fetch();
        } else {
            $this->dynamic->fetch();
        }
    }

    /**
     * @see Iterator::rewind
     */
    public function rewind(): void
    {
        if ($this->hasStatic()) {
            $this->static->rewind();
        } else {
            $this->dynamic->rewind();
        }
    }

    /**
     * @see Iterator::valid
     */
    public function valid(): bool
    {
        return $this->hasStatic() ? $this->static->valid() : $this->dynamic->valid();
    }

    /**
     * MetaRecord from array
     *
     * Returns a new instance of object from an associative array.
     *
     * @param array<mixed>        $data        Data array
     */
    public static function newFromArray(?array $data): self
    {
        return new self(StaticRecord::newFromArray($data));
    }

    /**
     * Get value (from the 1st row if any) from a SQL select using a count(), max(), … at 1st column
     *
     * May be useful where `$recordset->f(0)` was used, then replace `...->f(0)` by `...->cardinal()`
     *
     * @since 2.38
     *
     * @param  bool  $cast  Set to true to get integer value only
     *
     * @return ($cast is true ? int : ?int)
     */
    public function cardinal(bool $cast = true): ?int
    {
        if ($this->count() > 0 && $this->exists(0)) {
            // At least one row with one column
            $index = $this->hasDynamic() ? $this->dynamic->index() : 0;
            if ($index > 0) {
                // Back to first row
                $this->dynamic->moveStart();
            }

            $cardinal = $this->intField(0, !$cast);

            if ($index > 0) {
                // Back to previous position
                $this->dynamic->index($index);
            }

            return $cardinal;
        }

        return $cast ? 0 : null;
    }

    // Typed field() aliases
    // ---------------------

    /**
     * Get field value as string (or null if not string and $null_allowed is true)
     *
     * @since 2.39
     *
     * @param  string|int   $n              Field name|position
     * @param  bool         $null_allowed   If true then return null if field has no value
     *
     * @return ($null_allowed is true ? null|string : string)
     */
    public function strField(string|int $n, bool $null_allowed = false): ?string
    {
        if (is_string($value = $this->field($n))) {
            return $value;
        }

        return $null_allowed ? null : '';
    }

    /**
     * Get field value as int (or null if not numeric and $null_allowed is true)
     *
     * @since 2.39
     *
     * @param  string|int   $n              Field name|position
     * @param  bool         $null_allowed   If true then return null if field has no value
     *
     * @return ($null_allowed is true ? null|int : int)
     */
    public function intField(string|int $n, bool $null_allowed = false): ?int
    {
        if (is_numeric($value = $this->field($n))) {
            return (int) $value;
        }

        return $null_allowed ? null : 0;
    }

    /**
     * Get field value as bool (or null if not bool and $null_allowed is true)
     *
     * @since 2.39
     *
     * @param  string|int   $n              Field name|position
     * @param  bool         $null_allowed   If true then return null if field has no value
     *
     * @return ($null_allowed is true ? null|bool : bool)
     */
    public function boolField(string|int $n, bool $null_allowed = false): ?bool
    {
        if (is_scalar($value = $this->field($n))) {
            return (bool) $value;
        }

        return $null_allowed ? null : false;
    }

    // Methods valid on StaticRecord instance only
    // -------------------------------------------

    /**
     * Changes value of a given field in the current row.
     *
     * @param string|int    $n            Field name|position
     * @param mixed         $v            Field value
     */
    public function set(string|int $n, mixed $v): ?false
    {
        if ($this->hasStatic()) {
            return $this->static->set($n, $v);
        }

        return null;
    }

    /**
     * Sorts values by a field in a given order.
     *
     * @param string|int    $field        Field name|position
     * @param string        $order        Sort type (asc or desc)
     */
    public function sort(string|int $field, string $order = 'asc'): void
    {
        if ($this->hasStatic()) {
            $this->static->sort($field, $order);
        }
    }

    /**
     * Lexically sorts values by a field in a given order.
     *
     * @param      string  $field  The field
     * @param      string  $order  The order
     */
    public function lexicalSort(string $field, string $order = 'asc'): void
    {
        if ($this->hasStatic()) {
            $this->static->lexicalSort($field, $order);
        }
    }
}
