<?php

/**
 * @package Dotclear
 * @subpackage Core
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
     * @var    array<string,callable[]>    $stack
     */
    private array $stack = [];

    /**
     * Adds a new behavior to behaviors stack.
     *
     * <var>$func</var> must be a valid and callable callback.
     *
     * @param   string      $behavior   The behavior
     * @param   callable    $func       The function
     */
    public function addBehavior(string $behavior, $func): void
    {
        if (is_callable($func)) {   // @phpstan-ignore-line waiting to put callable type in method signature
            $this->stack[$behavior][] = $func;
        }
    }

    /**
     * Adds new behaviors to behaviors stack. Each row must
     * contains the behavior and a valid callable callback.
     *
     * @param   array<string,callable>   $behaviors  The behaviors
     */
    public function addBehaviors(array $behaviors): void
    {
        foreach ($behaviors as $behavior => $func) {
            $this->addBehavior($behavior, $func);
        }
    }

    /**
     * Determines if behavior exists in behaviors stack.
     *
     * @param   string  $behavior   The behavior
     *
     * @return  bool    True if behavior exists, False otherwise.
     */
    public function hasBehavior(string $behavior): bool
    {
        return isset($this->stack[$behavior]);
    }

    /**
     * Gets the given behavior stack.
     *
     * @param   string  $behavior   The behavior
     *
     * @return  array<int,callable>     The behaviors.
     */
    public function getBehavior(string $behavior): array
    {
        return $behavior === '' || $this->stack === [] || !$this->hasBehavior($behavior) ? [] : $this->stack[$behavior];
    }

    /**
     * Gets the behaviors stack.
     *
     * @return  array<string,array<int,callable>>   The behaviors.
     */
    public function getBehaviors(): array
    {
        return $this->stack;
    }

    /**
     * Calls every function in behaviors stack for a given behavior and returns
     * concatened result of each function.
     *
     * Every parameters added after <var>$behavior</var> will be pass to
     * behavior calls.
     *
     * @param   string  $behavior   The behavior
     * @param   mixed[] $args       The arguments
     *
     * @return  string  Behavior concatened result
     */
    public function callBehavior(string $behavior, ...$args): string
    {
        $res = '';
        foreach ($this->getBehavior($behavior) as $f) {
            $res .= $f(...$args);
        }

        return $res;
    }
}
