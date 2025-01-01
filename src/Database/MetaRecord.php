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
     *
     * @var null|Record
     */
    protected $dynamic;

    /**
     * Static record object
     *
     * @var null|StaticRecord
     */
    protected $static;

    /**
     * Constructs a new instance.
     *
     * @param      Record|StaticRecord            $record    The record
     */
    public function __construct($record)
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
        if ($this->dynamic instanceof Record) {
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
        // Search method in StaticRecord instance first
        if ($this->static instanceof StaticRecord) {
            $extensions = $this->static->extensions();
            if (isset($extensions[$f])) {
                return $extensions[$f]($this, ...$args);
            }
        }
        // Then search method in record instance
        if ($this->dynamic instanceof Record) {
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
     * Get field value
     *
     * @param      string|int  $n      Field name|position
     *
     * @return     mixed
     */
    public function field($n)
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->field($n);
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->field($n);
        }

        return null;
    }

    /**
     * Check if a field exists
     *
     * @param      string|int  $n      Field name|position
     */
    public function exists($n): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->exists($n);
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->exists($n);
        }

        return false;
    }

    /**
     * Field isset
     *
     * Returns true if a field exists (magic method from PHP 5.1).
     *
     * @param string        $n        Field name
     */
    public function __isset(string $n): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->__isset($n);
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            $this->static->extend($class);
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            $extensions = [...$extensions, ...$this->static->extensions()];
        }
        if ($this->dynamic instanceof Record) {
            $extensions = [...$extensions, ...$this->dynamic->extensions()];
        }

        return $extensions;
    }

    /**
     * Get current index
     *
     * @param      int   $row    The row
     *
     * @return     mixed
     */
    public function index(?int $row = null)
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->index($row);
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            return $this->static->fetch();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->fetch();
        }

        return false;
    }

    /**
     * Moves index to first position.
     */
    public function moveStart(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->moveStart();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->moveStart();
        }

        return false;
    }

    /**
     * Moves index to last position.
     */
    public function moveEnd(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->moveEnd();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->moveEnd();
        }

        return false;
    }

    /**
     * Moves index to next position.
     */
    public function moveNext(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->moveNext();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->moveNext();
        }

        return false;
    }

    /**
     * Moves index to previous position.
     */
    public function movePrev(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->movePrev();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->movePrev();
        }

        return false;
    }

    /**
     * Check if index is at last position
     */
    public function isEnd(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->isEnd();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->isEnd();
        }

        return true;
    }

    /**
     * Check if index is at first position
     */
    public function isStart(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->isStart();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->isStart();
        }

        return true;
    }

    /**
     * Check if record is empty (no result)
     */
    public function isEmpty(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->isEmpty();
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            return $this->static->columns();
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            return $this->static->rows();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->rows();
        }

        return [];
    }

    /**
     * @return array<mixed>    current rows.
     */
    public function row(): array
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->row();
        } elseif ($this->dynamic instanceof Record) {
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
        if ($this->static instanceof StaticRecord) {
            return $this->static->count();
        } elseif ($this->dynamic instanceof Record) {
            return $this->dynamic->count();
        }

        return 0;
    }

    // Iterator methods

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
        return $this->index();
    }

    /**
     * @see Iterator::next
     */
    public function next(): void
    {
        if ($this->static instanceof StaticRecord) {
            $this->static->fetch();
        } elseif ($this->dynamic instanceof Record) {
            $this->dynamic->fetch();
        }
    }

    /**
     * @see Iterator::rewind
     */
    public function rewind(): void
    {
        if ($this->static instanceof StaticRecord) {
            $this->static->rewind();
        } elseif ($this->dynamic instanceof Record) {
            $this->dynamic->rewind();
        }
    }

    /**
     * @see Iterator::valid
     */
    public function valid(): bool
    {
        if ($this->static instanceof StaticRecord) {
            return $this->static->valid();
        } elseif ($this->dynamic instanceof Record) {
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
     *
     * @return mixed
     */
    public function set($n, $v)
    {
        if ($this->static instanceof StaticRecord) {
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
        if ($this->static instanceof StaticRecord) {
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
        if ($this->static instanceof StaticRecord) {
            $this->static->lexicalSort($field, $order);
        }
    }
}
