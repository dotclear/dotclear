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
 *
 * @psalm-no-seal-properties
 * @psalm-no-seal-methods
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
        if ($this->hasStatic()) {
            return $this->static->field($n);
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->field($n);
        }

        return null;
    }

    /**
     * Check if a field exists
     *
     * @param      string|int  $n      Field name|position
     */
    public function exists(string|int $n): bool
    {
        if ($this->hasStatic()) {
            return $this->static->exists($n);
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->exists($n);
        }

        return false;
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
        if ($this->hasStatic()) {
            return $this->static->__isset($n);
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->__isset($n);
        }

        return false;
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
            $extensions = [...$extensions, ...$this->static->extensions()];
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
     */
    public function index(?int $row = null): mixed
    {
        if ($this->hasStatic()) {
            return $this->static->index($row);
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->index($row);
        }

        return null;
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
        if ($this->hasStatic()) {
            return $this->static->fetch();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->fetch();
        }

        return false;
    }

    /**
     * Moves index to first position.
     */
    public function moveStart(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->moveStart();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->moveStart();
        }

        return false;
    }

    /**
     * Moves index to last position.
     */
    public function moveEnd(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->moveEnd();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->moveEnd();
        }

        return false;
    }

    /**
     * Moves index to next position.
     */
    public function moveNext(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->moveNext();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->moveNext();
        }

        return false;
    }

    /**
     * Moves index to previous position.
     */
    public function movePrev(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->movePrev();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->movePrev();
        }

        return false;
    }

    /**
     * Check if index is at last position
     */
    public function isEnd(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->isEnd();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->isEnd();
        }

        return true;
    }

    /**
     * Check if index is at first position
     */
    public function isStart(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->isStart();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->isStart();
        }

        return true;
    }

    /**
     * Check if record is empty (no result)
     */
    public function isEmpty(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->isEmpty();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->isEmpty();
        }

        return true;
    }

    /**
     * Get columns
     *
     * @return array<string>    array of columns.
     */
    public function columns(): array
    {
        if ($this->hasStatic()) {
            return $this->static->columns();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->columns();
        }

        return [];
    }

    /**
     * Get record rows
     *
     * @return     array<array<mixed>>
     */
    public function rows(): array
    {
        if ($this->hasStatic()) {
            return $this->static->rows();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->rows();
        }

        return [];
    }

    /**
     * @return array<mixed>    current rows.
     */
    public function row(): array
    {
        if ($this->hasStatic()) {
            return $this->static->row();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->row();
        }

        return [];
    }

    // Countable methods

    /**
     * @return int    number of rows in record
     */
    public function count(): int
    {
        if ($this->hasStatic()) {
            return $this->static->count();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->count();
        }

        return 0;
    }

    // Iterator methods

    /**
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
    #[\ReturnTypeWillChange]
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
        } elseif ($this->hasDynamic()) {
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
        } elseif ($this->hasDynamic()) {
            $this->dynamic->rewind();
        }
    }

    /**
     * @see Iterator::valid
     */
    public function valid(): bool
    {
        if ($this->hasStatic()) {
            return $this->static->valid();
        }
        if ($this->hasDynamic()) {
            return $this->dynamic->valid();
        }

        return false;
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

    // Methods valid on StaticRecord instance only
    // -------------------------------------------

    /**
     * Changes value of a given field in the current row.
     *
     * @param string|int    $n            Field name|position
     * @param mixed         $v            Field value
     */
    public function set(string|int $n, mixed $v): ?bool
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
    public function sort($field, string $order = 'asc'): void
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
