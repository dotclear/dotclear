<?php
/**
 * Session handler.
 *
 * Transitionnal class.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\BehaviorInterface;

class Behavior implements BehaviorInterface
{
    /** @var    array<string,array<int,callable>>   The behaviors stack */
    private array $stack = [];

    public function addBehavior(string $behavior, $func): void
    {
        if (is_callable($func)) {
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
        return empty($behavior) || empty($this->stack) || !$this->hasBehavior($behavior) ? [] : $this->stack[$behavior];
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
