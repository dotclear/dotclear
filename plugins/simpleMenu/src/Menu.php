<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use Countable;
use Iterator;

/**
 * @brief   The menu object.
 * @ingroup simpleMenu
 *
 * @implements Iterator<int, MenuItem>
 *
 * @phpstan-import-type TSimpleMenuItem from MenuItem
 */
class Menu implements Countable, Iterator
{
    /**
     * @param MenuItem[] $items List of menu items
     */
    public function __construct(
        protected array $items = []
    ) {
    }

    /**
     * Get current list of menu items
     *
     * @return MenuItem[]
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Add an item at the end of the menu
     */
    public function add(MenuItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Remove an item from the menu
     *
     * @param  int    $index Index of the item to remove
     *
     * @return bool   True if item removed, false otherwise
     */
    public function remove(int $index): bool
    {
        if ($index >= 0 && $index < count($this->items)) {
            unset($this->items[$index]);

            return true;
        }

        return false;
    }

    /**
     * Check if a menu item is in the menu
     *
     * @param  string $label Menu item label
     */
    public function has(string $label): bool
    {
        foreach ($this->items as $item) {
            if ($item->getLabel() === $label) {
                return true;
            }
        }

        return false;
    }

    // Coutable methods

    public function count(): int
    {
        return count($this->items);
    }

    // Iterator methods

    public function current(): MenuItem|false
    {
        return current($this->items);
    }

    public function key(): ?int
    {
        $key = key($this->items);
        if (is_string($key)) {
            if (is_numeric($key)) {
                $key = (int) $key;
            } else {
                // We don't allow non-numeric string as key (up to now)
                $key = null;
            }
        }

        return $key;
    }

    public function next(): void
    {
        next($this->items);
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    public function valid(): bool
    {
        return key($this->items) !== null;
    }

    /**
     * Get an array of menu items
     *
     * May be used to store menu in settings
     *
     * @return array<int, TSimpleMenuItem>
     */
    public function getArray(): array
    {
        $list = [];
        foreach ($this->items as $item) {
            $list[] = $item->getArray();
        }

        return $list;
    }
}
