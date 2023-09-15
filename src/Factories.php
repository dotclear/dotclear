<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear;

/**
 * Dotclear fatories handler.
 *
 * To add a third party Factory :
 * @code{php}
 * require_once path_to_this_file/Factories.php;
 * Factories::addFactory('core', MyCoreFactory::class);
 * @endcode
 *
 * * Third party factory must be accessible from Autoloaer.
 * * A container factory is set once.
 * * This MUST be done before any call to App.
 * * Once a container is instanciated, changes to factories stack have no effects.
 * * By default 'core' container is available.
 *
 * @since 2.28
 */
class Factories
{
    /**
     * The containers factories stack.
     *
     * @var     array<string,string>    $stack
     */
    private static array $stack = [
        'core' => '', // for now only core has factory, see Dotclear\Interface\Core\FactoryInterface
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
     * Check if a container factory is set.
     *
     * @param   string  $container  The container ID
     * @param   string  $factory    The factory class name
     *
     * @return  bool    True if exists
     */
    public static function hasFactory(string $container, string $factory): bool
    {
        return self::hasContainer($container) && self::$stack[$container] == $factory;
    }

    /**
     * Add a container factory.
     *
     * @param   string  $container  The container ID
     * @param   string  $factory    The factory class name
     */
    public static function addFactory(string $container, string $factory): void
    {
        if (!self::hasFactory($container, $factory)) {
            self::$stack[$container] = $factory;
        }
    }

    /**
     * Get a container factory.
     *
     * @param   string  $container  The container ID
     *
     * @return  string   The factory class name
     */
    public static function getFactory(string $container): string
    {
        return self::$stack[$container] ?? '';
    }
}
