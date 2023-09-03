<?php
/**
 * Core container.
 *
 * Core container search factories for requested methods.
 * Available container methods are explicitly set
 * to keep track of returned types.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Factories;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\ErrorInterface;
use Dotclear\Interface\Core\FactoryInterface;
use Dotclear\Interface\Core\FilterInterface;
use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\LogInterface;
use Dotclear\Interface\Core\MediaInterface;
use Dotclear\Interface\Core\MetaInterface;
use Dotclear\Interface\Core\NonceInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\RestInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Interface\Core\VersionInterface;
use Dotclear\Interface\Module\ModulesInterface;

use Dotclear\Core\Frontend\Url;
use Exception;

class Container
{
    /** @var    array<string,mixed>     Unique instances stack */
    private array $stack = [];

    /** @var    Container   Container unique instance */
    private static Container $instance;

    /** @var    array<string,FactoryInterface>    Factory instance */
    private array $factories = [];

    /** @var    array<string,string>    Method / Factory pairs stack */
    private array $methods = [];

    /// @name Container methods
    //@{
    /**
     * Constructor.
     *
     * Instanciate all available core factories.
     */
    public function __construct()
    {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }

        self::$instance = $this;

        // Get required methods from Factory interface
        $methods = get_class_methods(FactoryInterface::class);

        // Get third party core factorie
        $factories = Factories::getFactories('core');
        
        // Append dotclear default core factory to the end of stack
        array_push($factories, Factory::class);

        // loop trhough factories
        foreach ($factories as $class) {
            // Third party core factory MUST implements FactoryInterface and SHOULD extends Factory
            if (!class_exists($class) || !is_subclass_of($class, FactoryInterface::class)) {
                continue;
            }

            // Instanciate factory
            $this->factories[$class] = new $class($this);

            foreach($methods as $method) {
                if (!method_exists($this->factories[$class], $method)) {
                    continue;
                }

                // Set method / factory pairs
                $this->methods[$method] = $class;
            }
        }
    }

    /**
     * Get instance of a core object.
     *
     * By default, an object is instanciated once.
     *
     * @param   string  $id         The object ID
     * @param   bool    $reload     Force reload of the class
     */
    public function get(string $id, bool $reload = false)
    {
        if (!$reload && array_key_exists($id, $this->stack)) {
            return $this->stack[$id];
        }

        if (array_key_exists($id, $this->methods)) {
            return $this->stack[$id] = $this->factories[$this->methods[$id]]->{$id}();
        }

        throw new Exception('Call to undefined factory method ' . $id);
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
        foreach ($this->factories as $factory) {
            if (method_exists($factory, $id)) {
                return true;
            }
        }

        return false;
    }

    //@}

    /// @name Core container methods
    //@{
    public static function auth(): AuthInterface
    {
        return self::$instance->get('auth');
    }

    public static function behavior(): BehaviorInterface
    {
        return self::$instance->get('behavior');
    }

    public static function blog(): BlogInterface
    {
        return self::$instance->get('blog', reload: true);
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

    public static function media(): MediaInterface
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

    public static function plugins(): ModulesInterface
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

    public static function themes(): ModulesInterface
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
