<?php
/**
 * Core container.
 *
 * Core container serves uniq instances of main Core classes using Factory.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\ErrorInterface;
use Dotclear\Interface\Core\FactoryInterface;
use Dotclear\Interface\Core\FilterInterface;
use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\LogInterface;
use Dotclear\Interface\Core\MetaInterface;
use Dotclear\Interface\Core\NonceInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\RestInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Interface\Core\VersionInterface;

// classes that move to \Dotclear\Core
use dcAuth;
use dcBlog;
use dcMedia;
use dcPlugins;
use dcThemes;
//
use Dotclear\Core\Frontend\Url;
use Exception;

class Container
{
    /** @var    array<string,mixed>     Unique instances stack */
    private array $stack = [];

    /** @var    Container   Container unique instance */
    private static Container $instance;

    /** @var    FactoryInterface    Factory instance */
    private FactoryInterface $factory;

    /// @name Container methods
    //@{
    /**
     * Constructor.
     *
     * @param   string  $factory_class  The Core factory class name
     */
    public function __construct(string $factory_class)
    {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        // Factory class, implement all methods of Container,
        // third party Core factory MUST implements FactoryInterface and SHOULD extends Factory
        if (!class_exists($factory_class) || !is_subclass_of($factory_class, FactoryInterface::class)) {
            $factory_class = Factory::class;
        }

        self::$instance = $this;
        $this->factory  = new $factory_class($this);
    }

    /**
     * Get instance of a core object.
     *
     * By default, instances are uniques.
     *
     * @param   string  $id         The object ID
     * @param   bool    $reload     Force reload of the class
     */
    public function get(string $id, bool $reload = false)
    {
        if ($this->has($id)) {
            if ($reload) {
                $this->stack[$id] = $this->factory->{$id}();
            }

            return $this->stack[$id] ?? $this->stack[$id] = $this->factory->{$id}();
        }

        throw new Exception('Can not call ' . $id . ' on Core factory class ' . $this->factory::class);
    }

    /**
     * Check if core object exists.
     *
     * @param   string  $id The object ID.
     *
     * @return  bool    True if it exists
     */
    public function has(string $id): bool
    {
        return method_exists($this->factory, $id);
    }
    //@}

    /// @name Core container methods
    //@{
    public static function auth(): dcAuth
    {
        return self::$instance->get('auth');
    }

    public static function behavior(): BehaviorInterface
    {
        return self::$instance->get('behavior');
    }

    public static function blog(): ?dcBlog
    {
        return self::$instance->get('blog', true);
    }

    public static function blogLoader(): BlogLoaderInterface
    {
        return self::$instance->get('blogLoader');
    }

    public static function blogs(): BlogsInterface
    {
        return self::$instance->get('blogs');
    }

    public static function con(): ConnectionInterface
    {
        return self::$instance->get('con');
    }

    public static function error(): ErrorInterface
    {
        return self::$instance->get('error');
    }

    public static function filter(): FilterInterface
    {
        return self::$instance->get('filter');
    }

    public static function formater(): FormaterInterface
    {
        return self::$instance->get('formater');
    }

    public static function log(): LogInterface
    {
        return self::$instance->get('log');
    }

    public static function media(): dcMedia
    {
        return self::$instance->get('media');
    }

    public static function meta(): MetaInterface
    {
        return self::$instance->get('meta');
    }

    public static function nonce(): NonceInterface
    {
        return self::$instance->get('nonce');
    }

    public static function notice(): NoticeInterface
    {
        return self::$instance->get('notice');
    }

    public static function plugins(): dcPlugins
    {
        return self::$instance->get('plugins');
    }

    public static function postMedia(): PostMediaInterface
    {
        return self::$instance->get('postMedia');
    }

    public static function postTypes(): PostTypesInterface
    {
        return self::$instance->get('postTypes');
    }

    public static function rest(): RestInterface
    {
        return self::$instance->get('rest');
    }

    public static function session(): SessionInterface
    {
        return self::$instance->get('session');
    }

    public static function themes(): dcThemes
    {
        return self::$instance->get('themes');
    }

    public static function url(): Url
    {
        return self::$instance->get('url');
    }

    public static function users(): UsersInterface
    {
        return self::$instance->get('users');
    }

    public static function version(): VersionInterface
    {
        return self::$instance->get('version');
    }
    //@}
}
