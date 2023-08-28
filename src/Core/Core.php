<?php
/**
 * Core.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcAuth;
use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;
use Exception;

final class Core
{
    /** @var    string  Session table name */
    public const SESSION_TABLE_NAME = 'session';

    /** @var array<string,mixed> Unique instances stack */
    private array $stack = [];

    /** @var    Backend  Backend Utility instance  */
    private static Backend $backend;

    /** @var    null|dcBlog     dcBlog instance */
    private static ?dcBlog $blog;

    /** @var    Frontend  Frontend Utility instance  */
    private static Frontend $frontend;

    /** @var Core   Core unique instance */
    private static Core $instance;

    /// @name Container methods
    //@{
    /**
     * Constructor.
     *
     * @param   string  $factory_class  The Core factory class name
     */
    public function __construct(
        protected string $factory_class
    ) {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        // Factory class, implement all methods of Core, 
        // third party Core factory MUST implements CoreFactoryInterface and SHOULD extends CoreFactory
        if (!class_exists($this->factory_class) || !is_subclass_of($this->factory_class, CoreFactoryInterface::class)) {
            throw new Exception('Core factory class ' . $this->factory_class . ' does not inherit CoreFactoryInterface.');
        }
        self::$instance = $this;
    }

    /**
     * Get unique instance of a core object.
     *
     * @param   string  $id The object ID.
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
            return $this->stack[$id] ?? $this->stack[$id] = (new $this->factory_class($this))->{$id}();
        }

        throw new Exception('Can not call ' . $id . ' on Core factory class ' . $this->factory_class);
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
        return method_exists($this->factory_class, $id);
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

    public static function filter(): Filter
    {
        return self::$instance->get('filter');
    }

    public static function formater(): Formater
    {
        return self::$instance->get('formater');
    }

    public static function nonce(): Nonce
    {
        return self::$instance->get('nonce');
    }

    public static function postTypes(): PostTypes
    {
        return self::$instance->get('postTypes');
    }

    public static function session(): Session
    {
        return self::$instance->get('session');
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

    /// @name Core methods
    //@{
    /**
     * Get backend Utility.
     *
     * @return  Backend
     */
    public static function backend(): Backend
    {
        // Instanciate Backend instance
        if (!isset(self::$backend)) {
            self::$backend = new Backend();
            dcCore::app()->admin = self::$backend; // deprecated
        }

        return self::$backend;
    }

    /**
     * Get frontend Utility.
     *
     * @return  Frontend
     */
    public static function frontend(): Frontend
    {
        // Instanciate Backend instance
        if (!isset(self::$frontend)) {
            self::$frontend = new Frontend();
            dcCore::app()->public = self::$frontend; // deprecated
        }

        return self::$frontend;
    }
    //@}

    /// @name Current blog methods
    //@{
    /**
     * Get current blog
     *
     * @return null|dcBlog
     */
    public static function blog(): ?dcBlog
    {
        return self::$blog;
    }

    /**
     * Sets the blog to use.
     *
     * @param      string  $id     The blog ID
     */
    public static function setBlog($id): void
    {
        self::$blog = new dcBlog($id);
        dcCore::app()->blog = self::$blog; // deprecated
    }

    /**
     * Unsets blog property
     */
    public static function unsetBlog(): void
    {
        self::$blog = null;
        dcCore::app()->blog = null; // deprecated
    }
    //@}
}