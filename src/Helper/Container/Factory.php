<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Container;

/**
 * @brief   Factory services helper.
 *
 * This is the stack of interface / class
 * used by Container to load services.
 *
 * @since   2.28
 */
class Factory
{
    /**
     * The factory services stack.
     *
     * @var     array<string,string|callable>   $stack
     */
    private array $stack = [];

    /**
     * The factories statistics.
     *
     * This fill statistics for all containers using this factory class.
     *
     * @var     array<string, array<string, mixed>>  $stats
     */
    private static array $stats = [
        '*' => ['count' => 0],
        //'container_id' => [
        //    'factory' => ['service_id' => 'service_callback']
        //    'default' => ['service_id' => 'service_callback']
        //],
    ];

    /**
     * Constructor.
     *
     * @param   string  $id             The container ID.
     * @param   bool    $rewritable     Service can be overridden.
     */
    public function __construct(
        public readonly string $id,
        public readonly bool $rewritable = true
    ) {
    }

    /**
     * Set a service definiton.
     *
     * @param   string              $service        The service name (commonly the interface name)
     * @param   string|callable     $callback       The service calss name or callback
     * @param   bool                $from_factory   Does service def comes from factory. (used for stats)
     */
    public function set(string $service, string|callable $callback, bool $from_factory = true): void
    {
        if ($this->rewritable || !array_key_exists($service, $this->stack)) {
            $this->stack[$service] = $callback;
            self::$stats[$this->id][$from_factory ? 'factory' : 'default'][$service] = is_string($callback) ? $callback : 'closure';
        }
    }

    /**
     * Get a service definiton.
     *
     * Return NULL if srevice does not exist.
     *
     * @param   string $service     The service name
     *
     * @return null|string|callable     The service definiton
     */
    public function get($service): null|string|callable
    {
        return $this->stack[$service] ?? null;
    }

    /**
     * Check if a service is set.
     *
     * @param   string $service     The service name
     *
     * @return  bool    Ture if service definition exists
     */
    public function has($service): bool
    {
        return array_key_exists($service, $this->stack);
    }

    /**
     * Dump services definitons stack.
     *
     * @return  array<string,string|callable>   The stack
     */
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Increment service call count.
     */
    public function increment(string $service): void
    {
        self::$stats[$this->id]['count'][$service] = (self::$stats[$this->id]['count'][$service] ?? 0) + 1;
        ++self::$stats['*']['count'];
    }

    /**
     * Get factories statistics.
     *
     * Returns staticstics of all containers using this factory class.
     *
     * @return  array<string, array<string, mixed>>
     */
    public static function getStats(): array
    {
        return self::$stats;
    }
}
