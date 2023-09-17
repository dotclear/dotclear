<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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

    /**
     * @return  null|ActionDescriptor
     */
    public function get(int $offset): null|ActionDescriptor
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

    /**
     * @return  false|ActionDescriptor
     */
    #[\ReturnTypeWillChange]
    public function current(): false|ActionDescriptor
    {
        return current($this->stack);
    }

    /**
     * @return  null|int
     */
    #[\ReturnTypeWillChange]
    public function key(): null|int
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
