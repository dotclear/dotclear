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
 * @brief   The helper to serve factories.
 *
 * To add a third party service :
 * @code{php}
 * require_once path_to_this_file/Factories.php;
 * Factories::addService('core', VersionInterface::class, MyCoreVersionService::class);
 * Factories::addService('core', AuthInterface::class, fn ($container) => new \My\Core\Auth(ConnectionInterface $con));
 * @endcode
 *
 * * Third party service MUST be accessible from an Autoloader.
 * * This MUST be done before any call to App.
 * * Once a container is instanciated, changes to factories stack have no effects.
 * * Service class arguments are automagically added if they exist in container.
 *
 * @since   2.28
 */
class Factories
{
    /**
     * The containers services stack.
     *
     * @var     array<string, Factory>   $stack
     */
    private static array $stack = [];

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
     * An existing container can not be overridden.
     *
     * @param   string  $container  The container ID
     * @param   bool    $overwrite  Orverwrite a service already set
     */
    public static function addContainer(string $container, bool $overwrite = false): void
    {
        if (!self::hasContainer($container)) {
            self::$stack[$container] = new Factory($container, $overwrite);
        }
    }

    /**
     * Check if a container service is set.
     *
     * @param   string  $container  The container ID
     * @param   string  $service    The service ID (commonly interface class name)
     *
     * @return  bool    True if exists
     */
    public static function hasService(string $container, string $service): bool
    {
        return self::hasContainer($container) && self::$stack[$container]->has($service);
    }

    /**
     * Add a container service.
     *
     * If container does not exist,
     * it will be created on the fly with orverwrite option to false.
     *
     * @param   string              $container  The container ID
     * @param   string              $service    The service ID (commonly interface class name)
     * @param   string|callable     $callback   The third party service class name or callback
     */
    public static function addService(string $container, string $service, string|callable $callback): void
    {
        self::addContainer($container);
        self::$stack[$container]->set($service, $callback);
    }

    /**
     * Get a container factory.
     *
     * A factory is a stack of services definitons.
     *
     * @param   string  $container  The container ID
     *
     * @return  Factory     The factory services definition
     */
    public static function getFactory(string $container): Factory
    {
        return self::$stack[$container] ?? new Factory($container);
    }
}
