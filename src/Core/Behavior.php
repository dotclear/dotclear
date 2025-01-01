<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\BehaviorInterface;

/**
 * @brief   Behavior handler.
 *
 * @since   2.28, behavior features have been grouped in this class
 */
class Behavior implements BehaviorInterface
{
    /**
     * The behaviors stack.
     *
     * @var    array<string,array<int,callable>>    $stack
     */
    private array $stack = [];

    public function addBehavior(string $behavior, $func): void
    {
        if (is_callable($func)) {   // @phpstan-ignore-line waiting to put callable type in method signature
            $this->stack[$behavior][] = $func;
        }
    }

    public function addBehaviors(array $behaviors): void
    {
        foreach ($behaviors as $behavior => $func) {
            $this->addBehavior($behavior, $func);
        }
    }

    public function hasBehavior(string $behavior): bool
    {
        return isset($this->stack[$behavior]);
    }

    public function getBehavior(string $behavior): array
    {
        return $behavior === '' || $this->stack === [] || !$this->hasBehavior($behavior) ? [] : $this->stack[$behavior];
    }

    public function getBehaviors(): array
    {
        return $this->stack;
    }

    public function callBehavior(string $behavior, ...$args): string
    {
        $res = '';
        foreach ($this->getBehavior($behavior) as $f) {
            $res .= $f(...$args);
        }

        return $res;
    }
}
