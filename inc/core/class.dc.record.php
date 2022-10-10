<?php
/**
 * @brief Dotclear dcRecord class
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcRecord implements Iterator, Countable
{
    /**
     * Record object
     *
     * @var null|record
     */
    protected $dynamic;

    /**
     * Static record object
     *
     * @var null|staticRecord|extStaticRecord
     */
    protected $static;

    /**
     * Constructs a new instance.
     *
     * @param      record|staticRecord|extStaticRecord            $record    The record
     */
    public function __construct($record)
    {
        if ($record instanceof extStaticRecord || $record instanceof staticRecord) {
            $this->static = $record;
        } else {
            $this->dynamic = $record;
        }
    }

    /**
     * To staticRecord
     *
     * Converts the dynamic record to a {@link staticRecord} instance.
     *
     * Note:    All dcRecord methods (unless staticRecord spécific, see at end of this file) will try first
     *          with the extStaticRecord|staticRecord instance, if exist.
     *
     * @return     self  Static representation of the object.
     */
    public function toStatic(): self
    {
        if ($this->dynamic instanceof record) {
            $this->static = $this->dynamic->toStatic();
        }

        return $this;
    }

    /**
     * To extStaticRecord
     *
     * Converts the static record to a {@link extStaticRecord} instance.
     *
     * Notes:
     *
     *  - The static record is created from the dynamic one if it does not exist
     *  - All dcRecord methods (unless (ext)staticRecord spécific, see at end of this file) will try first
     *  with the extStaticRecord|staticRecord instance, if exist.
     *
     * @return     self  Static representation of the object.
     */
    public function toExtStatic(): self
    {
        if ($this->static instanceof extStaticRecord) {
            return $this;
        }

        if (!$this->static) {
            // Convert to static if necessary
            $this->static = $this->dynamic->toStatic();
        }
        $this->static = new extStaticRecord($this->static);

        return $this;
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
        // Search method in staticRecord instance first
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $extensions = $this->static->extensions();
            if (isset($extensions[$f])) {
                return $extensions[$f]($this, ...$args);
            }
        }
        // Then search method in record instance
        if ($this->dynamic instanceof record) {
            $extensions = $this->dynamic->extensions();
            if (isset($extensions[$f])) {
                return $extensions[$f]($this, ...$args);
            }
        }

        trigger_error('Call to undefined method ' . $f . '()', E_USER_ERROR);
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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->field($n);
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->field($n);
        }

        return null;
    }

    /**
     * Check if a field exists
     *
     * @param      string|int  $n      Field name|position
     *
     * @return     bool
     */
    public function exists($n): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->exists($n);
        } elseif ($this->dynamic instanceof record) {
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
     *
     * @return bool
     */
    public function __isset(string $n): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->__isset($n);
        } elseif ($this->dynamic instanceof record) {
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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $this->static->extend($class);
        } elseif ($this->dynamic instanceof record) {
            $this->dynamic->extend($class);
        }
    }

    /**
     * Returns record extensions.
     *
     * @return  array
     */
    public function extensions(): array
    {
        $extensions = [];
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $extensions = array_merge($extensions, $this->static->extensions());
        }
        if ($this->dynamic instanceof record) {
            $extensions = array_merge($extensions, $this->dynamic->extensions());
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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->index($row);
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->index($row);
        }

        return null;
    }

    /**
     * One step move index
     *
     * This method moves index forward and return true until index is not
     * the last one. You can use it to loop over record. Example:
     * <code>
     * while ($rs->fetch()) {
     *     echo $rs->field1;
     * }
     * </code>
     *
     * @return bool
     */
    public function fetch(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->fetch();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->fetch();
        }

        return false;
    }

    /**
     * Moves index to first position.
     *
     * @return bool
     */
    public function moveStart(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->moveStart();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->moveStart();
        }

        return false;
    }

    /**
     * Moves index to last position.
     *
     * @return bool
     */
    public function moveEnd(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->moveEnd();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->moveEnd();
        }

        return false;
    }

    /**
     * Moves index to next position.
     *
     * @return bool
     */
    public function moveNext(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->moveNext();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->moveNext();
        }

        return false;
    }

    /**
     * Moves index to previous position.
     *
     * @return bool
     */
    public function movePrev(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->movePrev();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->movePrev();
        }

        return false;
    }

    /**
     * Check if index is at last position
     *
     * @return bool
     */
    public function isEnd(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->isEnd();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->isEnd();
        }

        return true;
    }

    /**
     * Check if index is at first position
     *
     * @return bool
     */
    public function isStart(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->isStart();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->isStart();
        }

        return true;
    }

    /**
     * Check if record is empty (no result)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->isEmpty();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->isEmpty();
        }

        return true;
    }

    /**
     * Get columns
     *
     * @return array    array of columns, with name as key and type as value.
     */
    public function columns(): array
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->columns();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->columns();
        }

        return [];
    }

    /**
     * Get record rows
     *
     * @return     array
     */
    public function rows(): array
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->rows();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->rows();
        }

        return [];
    }

    /**
     * @return array    current rows.
     */
    public function row(): array
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->row();
        } elseif ($this->dynamic instanceof record) {
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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->count();
        } elseif ($this->dynamic instanceof record) {
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
        return $this;
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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $this->static->fetch();
        } elseif ($this->dynamic instanceof record) {
            $this->dynamic->fetch();
        }
    }

    /**
     * @see Iterator::rewind
     */
    public function rewind(): void
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $this->static->rewind();
        } elseif ($this->dynamic instanceof record) {
            $this->dynamic->rewind();
        }
    }

    /**
     * @see Iterator::valid
     */
    public function valid(): bool
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->valid();
        } elseif ($this->dynamic instanceof record) {
            return $this->dynamic->valid();
        }

        return false;
    }

    /**
     * dcRecord from array
     *
     * Returns a new instance of object from an associative array.
     *
     * @param array        $data        Data array
     *
     * @return dcRecord
     */
    public static function newFromArray(?array $data): self
    {
        return new self(staticRecord::newFromArray($data));
    }

    // Methods valid on staticRecord or extStaticRecord instance only
    // --------------------------------------------------------------

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
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            return $this->static->set($n, $v);
        }
    }

    /**
     * Sorts values by a field in a given order.
     *
     * @param string|int    $field        Field name|position
     * @param string        $order        Sort type (asc or desc)
     *
     * @return mixed
     */
    public function sort($field, string $order = 'asc')
    {
        if ($this->static instanceof extStaticRecord || $this->static instanceof staticRecord) {
            $this->static->sort($field, $order);
        }
    }

    // Methods valid on extStaticRecord instance only
    // ----------------------------------------------

    /**
     * Lexically sorts values by a field in a given order.
     *
     * @param      string  $field  The field
     * @param      string  $order  The order
     */
    public function lexicalSort(string $field, string $order = 'asc')
    {
        if ($this->static instanceof extStaticRecord) {
            $this->static->lexicalSort($field, $order);
        }
    }
}
