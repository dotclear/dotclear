<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Database;

use Dotclear\Helper\Text;

/**
 * @class StaticRecord
 *
 * Query Result Static Record Class
 *
 * Unlike Record parent class, this one contains all results in an associative array.
 */
class StaticRecord extends Record
{
    /**
     * Data array
     *
     * @var array<array<array-key, mixed>>   $__data
     */
    public $__data = [];

    /**
     * Sort field name/index
     */
    private null|string|int $__sortfield = null;

    /**
     * Sort order (1 or -1)
     */
    private ?int $__sortsign = null;

    /**
     * Constructs a new instance.
     *
     * @param mixed     $result  The result
     * @param null|array{con: ?AbstractHandler, cols: int, rows: int, info: array{name: string[], type: string[]}}    $info    The information
     *
     * @todo Refine PHP structure of given $result parameter, it should be an array of row, each row is an array of mixed. See Record class for inheritance implication.
     */
    public function __construct(mixed $result, ?array $info)
    {
        $null_info = [
            'con'  => null,
            'cols' => 0,
            'rows' => 0,
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];
        if (is_array($result)) {
            $this->__info = $info ?? $null_info;
            $this->__data = $result;    // @phpstan-ignore assign.propertyType (don't know yet exact structure of $result)
        } else {
            parent::__construct($result, $info ?? $null_info);
            $this->__data = parent::getData();
        }
    }

    /**
     * Static record from array
     *
     * Returns a new instance of object from an associative array.
     *
     * @param array<array-key, mixed>        $data        Data array
     */
    public static function newFromArray(?array $data): StaticRecord
    {
        if (!is_array($data)) {
            $data = [];
        }

        $data = array_values($data);

        $cols = $data === [] || !is_array($data[0]) ? 0 : count($data[0]);

        $info = [
            'con'  => null,
            'cols' => $cols,
            'rows' => count($data),
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];

        return new self($data, $info);
    }

    /**
     * Get field value
     *
     * @param      string|int  $n      Field name|position
     */
    public function field(string|int $n): mixed
    {
        return $this->__data[$this->__index][$n] ?? null;
    }

    /**
     * Check if a field exists
     *
     * @param      string|int  $n      Field name|position
     */
    public function exists(string|int $n): bool
    {
        return isset($this->__data[$this->__index][$n]);
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
        if ($row === null) {
            return $this->__index;
        }

        if ($row < 0 || $row + 1 > $this->__info['rows']) {
            return false;
        }

        $this->__index = $row;

        return true;
    }

    /**
     * @return array<array-key, mixed>    current rows.
     */
    public function row(): array
    {
        return $this->__data[$this->__index] ?? [];
    }

    /**
     * Get record rows
     *
     * @return     array<array<array-key, mixed>>
     */
    public function rows(): array
    {
        return $this->__data;
    }

    /**
     * Changes value of a given field in the current row.
     *
     * @param string|int    $n            Field name|position
     * @param mixed         $v            Field value
     */
    public function set(string|int $n, mixed $v): ?false
    {
        if ($this->__info['rows'] === 0) {
            return false;
        }

        $this->__data[$this->__index][$n] = $v;

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
        if (!isset($this->__data[0][$field])) {
            return;
        }

        $this->__sortfield = $field;
        $this->__sortsign  = strtolower($order) === 'asc' ? 1 : -1;

        usort($this->__data, $this->sortCallback(...));

        $this->__sortfield = null;
        $this->__sortsign  = null;
    }

    /**
     * Sort callback
     *
     * @param      mixed   $first      First term to compare
     * @param      mixed   $second     Second term to compare
     */
    private function sortCallback(mixed $first, mixed $second): int
    {
        if ($this->__sortfield === null) {
            return 0;
        }

        if (!is_array($first) || !is_array($second)) {
            return 0;
        }

        if (!isset($first[$this->__sortfield]) || !isset($second[$this->__sortfield])) {
            return 0;
        }

        $first_value  = $first[$this->__sortfield];
        $second_value = $second[$this->__sortfield];

        // Numeric values
        if (is_numeric($first_value) && is_numeric($second_value)) {
            $first_value  = (float) $first_value  * $this->__sortsign;
            $second_value = (float) $second_value * $this->__sortsign;

            return $first_value <=> $second_value;
        }

        if (is_string($first_value) && is_string($second_value)) {
            return strcmp($first_value, $second_value) * $this->__sortsign;
        }

        return 0;
    }

    /**
     * Lexically sort.
     *
     * @param      string  $field  The field
     * @param      string  $order  The order
     */
    public function lexicalSort(string $field, string $order = 'asc'): void
    {
        $this->__sortfield = $field;
        $this->__sortsign  = strtolower($order) === 'asc' ? 1 : -1;

        usort($this->__data, $this->lexicalSortCallback(...));

        $this->__sortfield = null;
        $this->__sortsign  = null;
    }

    /**
     * Lexical sort callback
     *
     * @param      mixed   $first       First term to compare
     * @param      mixed   $second      Second term to compare
     */
    private function lexicalSortCallback(mixed $first, mixed $second): int
    {
        if ($this->__sortfield === null) {
            return 0;
        }

        if (!is_array($first) || !is_array($second)) {
            return 0;
        }

        if (!isset($first[$this->__sortfield]) || !isset($second[$this->__sortfield])) {
            return 0;
        }

        $first_value  = $first[$this->__sortfield];
        $second_value = $second[$this->__sortfield];

        // Numeric values
        if (is_numeric($first_value) && is_numeric($second_value)) {
            $first_value  = (float) $first_value  * $this->__sortsign;
            $second_value = (float) $second_value * $this->__sortsign;

            return $first_value <=> $second_value;
        }

        if (is_string($first_value) && is_string($second_value)) {
            return strcoll(strtolower(Text::removeDiacritics($first_value)), strtolower(Text::removeDiacritics($second_value))) * $this->__sortsign;
        }

        return 0;
    }
}
