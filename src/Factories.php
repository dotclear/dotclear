<?php
/**
 * Dotclear fatories handler.
 *
 * @package Dotclear
 *
 * @since 2.28
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

/**
 * Factories.
 *
 * To add a third party Factory :
 * require_once path_to_this_file/Factories.php;
 * Factories::addFactory('core', MyCoreFactory::class);
 *
 * * Third party factory must be accessible from Dotclear Autoloaer.
 * * This MUST be done before any call to App.
 * * Once a container is instanciated, changes to factories stack have no effects.
 * * By default 'core' container is available.
 */
class Factories
{
    /** @var    array<string,array<int,string>> The containers factories stack */
    private static array $stack = [
        'core' => [], // for now only core has factory, see src\Interface\FactoryInterface
    ];

    /**
     * Check if a container is set.
     *
     * @param   string  $container  The container ID
     *
     * @return  bool    True if exists
     */
    public static function hasContainer(string $container): bool
    {
        return array_key_exists($container, self::$stack);
    }

    /**
     * Add a container.
     *
     * @param   string  $container  The container ID
     */
    public static function addContainer(string $container): void
    {
        if (!self::hasContainer($container)) {
            self::$stack[$container] = [];
        }
    }

    /**
     * Check if a container factory is set.
     *
     * @param   string  $container  The container ID
     * @param   string  $factory    The factory class name
     *
     * @return  bool    True if exists
     */
    public static function hasFactory(string $container, string $factory): bool
    {
        return self::hasContainer($container) && in_array($factory, self::$stack[$container]);
    }

    /**
     * Prepend a factory.
     *
     * This adds a factory at the top of the container factories list
     *
     * @param   string  $container  The container ID
     * @param   string  $factory    The factory class name
     */
    public static function prependFactory(string $container, string $factory): void
    {
        if (self::hasContainer($container)) {
            array_unshift(self::$stack[$container], $factory);
        }
    }

    /**
     * Append a factory.
     *
     * This adds a factory at the end of the container factories list
     *
     * @param   string  $container  The container ID
     * @param   string  $factory    The factory class name
     */
    public static function appendFactory(string $container, string $factory): void
    {
        if (self::hasContainer($container)) {
            array_push(self::$stack[$container], $factory);
        }
    }

    /**
     * Get a container factories list.
     *
     * @param   string  $container  The container ID
     *
     * @return  array<int,string>   The factories class names
     */
    public static function getFactories(string $container): array
    {
        return self::$stack[$container] ?? [];
    }
}
