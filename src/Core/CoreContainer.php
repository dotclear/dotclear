<?php
/**
 * Core container.
 *
 * Core container serves uniq instances of main Core classes using CoreFactory.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

// classes that move to \Dotclear\Core
use dcAuth;
use dcError;
use dcLog;
use dcMedia;
use dcMeta;
use dcNotices;
use dcPlugins;
use dcPostMedia;
use dcRestServer;
use dcThemes;
//
use Dotclear\Core\Frontend\Url;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;
use Exception;

class CoreContainer
{
    /** @var    array<string,mixed>     Unique instances stack */
    private array $stack = [];

    /** @var    CoreContainer   CoreContainer unique instance */
    private static CoreContainer $instance;

    /** @var    CoreFactoryInterface    CoreFactory instance */
    private CoreFactoryInterface $factory;

    /// @name Container methods
    //@{
    /**
     * Constructor.
     *
     * @param   string  $factory_class  The Core factory class name
     */
    public function __construct(string $factory_class) {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        // Factory class, implement all methods of CoreContainer,
        // third party Core factory MUST implements CoreFactoryInterface and SHOULD extends CoreFactory
        if (!class_exists($factory_class) || !is_subclass_of($factory_class, CoreFactoryInterface::class)) {
            $factory_class = CoreFactory::class;
        }

        self::$instance = $this;
        $this->factory  = new $factory_class($this);
    }

    /**
     * Get unique instance of a core object.
     *
     * @param   string  $id The object ID.
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
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

    public static function behavior(): Behavior
    {
        return self::$instance->get('behavior');
    }

    public static function blogs(): Blogs
    {
        return self::$instance->get('blogs');
    }

    public static function con(): AbstractHandler
    {
        return self::$instance->get('con');
    }

    public static function error(): dcError
    {
        return self::$instance->get('error');
    }

    public static function filter(): Filter
    {
        return self::$instance->get('filter');
    }

    public static function formater(): Formater
    {
        return self::$instance->get('formater');
    }

    public static function log(): dcLog
    {
        return self::$instance->get('log');
    }

    public static function media(): dcMedia
    {
        return self::$instance->get('media');
    }

    public static function meta(): dcMeta
    {
        return self::$instance->get('meta');
    }

    public static function nonce(): Nonce
    {
        return self::$instance->get('nonce');
    }

    public static function notice(): dcNotices
    {
        return self::$instance->get('notice');
    }

    public static function plugins(): dcPlugins
    {
        return self::$instance->get('plugins');
    }

    public static function postMedia(): dcPostMedia
    {
        return self::$instance->get('postMedia');
    }

    public static function postTypes(): PostTypes
    {
        return self::$instance->get('postTypes');
    }

    public static function rest(): dcRestServer
    {
        return self::$instance->get('rest');
    }

    public static function session(): Session
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

    public static function users(): Users
    {
        return self::$instance->get('users');
    }

    public static function version(): Version
    {
        return self::$instance->get('version');
    }
    //@}
}
