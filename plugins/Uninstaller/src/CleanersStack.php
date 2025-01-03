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
use Dotclear\App;
use Exception;

/**
 * @brief   The cleaners stack.
 * @ingroup Uninstaller
 *
 * @implements Iterator<string,CleanerParent>
 */
class CleanersStack implements Countable, Iterator
{
    /**
     * The cleaner stack.
     *
     * @var     array<string,CleanerParent>     $stack
     */
    private array $stack = [];

    /**
     * Contructor load cleaners.
     *
     * @see     This module src\Prepend.php file to know how to add custom cleaner.
     */
    public function __construct()
    {
        # --BEHAVIOR-- UninstallerCleanersConstruct: CleanersStack
        App::behavior()->callBehavior('UninstallerCleanersConstruct', $this);
    }

    public function exists(string $offset): bool
    {
        return isset($this->stack[$offset]);
    }

    public function get(string $offset): ?CleanerParent
    {
        return $this->stack[$offset] ?? null;
    }

    public function set(CleanerParent $value): CleanersStack
    {
        if (!isset($this->stack[$value->id])) {
            $this->stack[$value->id] = $value;
        }

        return $this;
    }

    public function unset(string $offset): void
    {
        unset($this->stack[$offset]);
    }

    public function rewind(): void
    {
        reset($this->stack);
    }

    #[\ReturnTypeWillChange]
    public function current(): false|CleanerParent
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

    /**
     * Execute cleaner action on an value.
     *
     * @param   string  $id         The cleaner id
     * @param   string  $action     The action id
     * @param   string  $ns         The value
     *
     * @return  bool    The success
     */
    public function execute(string $id, string $action, string $ns): bool
    {
        if (!isset($this->stack[$id])) {
            throw new Exception(sprintf(__('Unknown cleaner "%s"'), $id));
        }
        if (in_array($ns, [My::id(), My::path()])) {
            throw new Exception(__("Unsintaller can't remove itself"));
        }

        # --BEHAVIOR-- UninstallerBeforeAction: string, string, string
        App::behavior()->callBehavior('UninstallerBeforeAction', $id, $action, $ns);

        return $this->stack[$id]->execute($action, $ns);
    }
}
