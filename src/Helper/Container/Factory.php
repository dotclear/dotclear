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
     * Factories statistics.
     *
     * Globals for all factories using this class.
     *
     * @var     array<string, array<string, array<string, string>>>     $statistics
     */
    private static array $statistics = [
        'factory' => [
            //'container_id' => ['service' => 'callback'],
        ],
        'container' => [
            //'container_id' => ['service' => 'callback'],
        ],
    ];

    /**
     * The factory services stack.
     *
     * @var     array<string,string|callable>   $stack
     */
    private array $stack = [];

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
            $this->stack[$service]                                                          = $callback;
            self::$statistics[$from_factory ? 'factory' : 'container'][$this->id][$service] = is_string($callback) ? $callback : 'closure';
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
    public function get(string $service): null|string|callable
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
     * Get factories statistics.
     *
     * @return  array<string, array<string, array<string, string>>>
     */
    public static function getStatistics(): array
    {
        return self::$statistics;
    }
}
