<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\Uninstaller;

use Countable;
use Iterator;

/**
 * @brief   Cleaner actions cleaners stack.
 * @ingroup Uninstaller
 *
 * @implements Iterator<int,ActionDescriptor>
 */
class ActionsCleanersStack implements Countable, Iterator
{
    /**
     * The actions cleaners stack.
     *
     * @var     array<int,ActionDescriptor>     $stack
     */
    private array $stack = [];

    public function exists(int $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    public function get(int $offset): ?ActionDescriptor
    {
        return $this->stack[$offset] ?? null;
    }

    public function set(ActionDescriptor $value): void
    {
        $this->stack[] = $value;
    }

    public function unset(int $offset): void
    {
        unset($this->stack[$offset]);
    }

    public function rewind(): void
    {
        reset($this->stack);
    }

    #[\ReturnTypeWillChange]
    public function current(): false|ActionDescriptor
    {
        return current($this->stack);
    }

    #[\ReturnTypeWillChange]
    public function key(): ?int
    {
        return key($this->stack);
    }

    public function next(): void
    {
        next($this->stack);
    }

    public function valid(): bool
    {
        return key($this->stack) !== null;
    }

    public function count(): int
    {
        return count($this->stack);
    }
}
