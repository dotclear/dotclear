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
 * @brief   Cleaner actions stack.
 * @ingroup Uninstaller
 *
 * @implements Iterator<string,ActionsCleanersStack>
 */
class ActionsStack implements Countable, Iterator
{
    /**
     * The actions stack.
     *
     * @var     array<string,ActionsCleanersStack>  $stack
     */
    private array $stack = [];

    public function exists(string $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    public function get(string $offset): ActionsCleanersStack
    {
        if (!$this->exists($offset)) {
            $this->set($offset, new ActionsCleanersStack());
        }

        return $this->stack[$offset];
    }

    public function set(string $offset, ActionsCleanersStack $value): void
    {
        $this->stack[$offset] = $value;
    }

    public function unset(string $offset): void
    {
        unset($this->stack[$offset]);
    }

    public function rewind(): void
    {
        reset($this->stack);
    }

    /**
     * @return  false|ActionsCleanersStack
     */
    #[\ReturnTypeWillChange]
    public function current(): false|ActionsCleanersStack
    {
        return current($this->stack);
    }

    #[\ReturnTypeWillChange]
    public function key(): ?string
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
