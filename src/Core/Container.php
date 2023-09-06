<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\ContainerInterface;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\CategoriesInterface;
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
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Interface\Core\VersionInterface;
use Dotclear\Interface\Module\ModulesInterface;

use Dotclear\Core\Frontend\Url;
use Exception;

/**
 * Core container.
 *
 * Core container search factory for requested methods.
 *
 * Available container methods are explicitly set
 * in this class to keep track of returned types.
 *
 * Third party core factory MUST implements
 * Dotclear\Interface\Core\FactoryInterface
 * and SHOULD extends Factory.
 *
 * Dotclear default factory will be used at least.
 *
 * @see Factories
 */
class Container implements ContainerInterface
{
    /**
     * Container "singleton" instance.
     *
     * @var    Container    $instance
     */
    private static Container $instance;

    /**
     * Stack of loaded factory objects.
     *
     * @var    array<string,mixed>  $stack
     */
    private array $stack = [];

    /**
     * Factory instance.
     *
     * @var    FactoryInterface     $factory
     */
    private FactoryInterface $factory;

    /**
     * The FactoryInterface methods list.
     *
     * @var    array<int,string>   $methods
     */
    private array $methods = [];

    /// @name Container methods
    //@{
    /**
     * Constructor instanciates core factory.
     *
     * @throws  Exception 
     *
     * @param   string  $class  The factory full class name
     */
    public function __construct(string $class)
    {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        self::$instance = $this;

        // Check factory requirements
        if (empty($class) || !is_subclass_of($class, FactoryInterface::class)) {
            // Else get dotclear default factory
            $class = Factory::class;
        }

        // Create Factory instance
        $this->factory = new $class($this);

        // Get required methods once
        $this->methods = get_class_methods(FactoryInterface::class);
    }

    /**
     * Get instance of a core object.
     *
     * By default, an object is instanciated once.
     *
     * @param   string  $id         The object ID
     * @param   bool    $reload     Force reload of the class
     * @param   mixed   ...$args    The method arguments
     */
    public function get(string $id, bool $reload = false, ...$args)
    {
        if (!$reload && array_key_exists($id, $this->stack)) {
            return $this->stack[$id];
        }

        if ($this->has($id)) {
            return $this->stack[$id] = $this->factory->{$id}(...$args);
        }

        throw new Exception('Call to undefined factory method ' . $id);
    }

    public function has(string $id): bool
    {
        return in_array($id, $this->methods);
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

    public static function blogSettings(?string $blog_id): BlogSettingsInterface
    {
        return self::$instance->get('blogSettings', reload: true, blog_id: $blog_id);
    }

    public static function blogs(): BlogsInterface
    {
        return self::$instance->get('blogs');
    }

    public static function blogWorkspace(): BlogWorkspaceInterface
    {
        return self::$instance->get('blogWorkspace');
    }

    public static function categories(): CategoriesInterface
    {
        return self::$instance->get('categories');
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

    public static function userPreferences(string $user_id, ?string $workspace = null): UserPreferencesInterface
    {
        return self::$instance->get('userPreferences', reload: true, user_id: $user_id, workspace: $workspace);
    }

    public static function userWorkspace(): UserWorkspaceInterface
    {
        return self::$instance->get('userWorkspace');
    }

    public static function version(): VersionInterface
    {
        return self::$instance->get('version');
    }
    //@}
}
