<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core
 * @brief       Dotclear core services
 */

namespace Dotclear\Core;

// Default core class from elsewhere, see Container::getDefaultServices()
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Module\Plugins;
use Dotclear\Module\Themes;

// Container helpers
use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\Factory;
use Exception;

// Container interfaces
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\CacheInterface;
use Dotclear\Interface\Core\CategoriesInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\DeprecatedInterface;
use Dotclear\Interface\Core\ErrorInterface;
use Dotclear\Interface\Core\FilterInterface;
use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\LangInterface;
use Dotclear\Interface\Core\LexicalInterface;
use Dotclear\Interface\Core\LogInterface;
use Dotclear\Interface\Core\MediaInterface;
use Dotclear\Interface\Core\MetaInterface;
use Dotclear\Interface\Core\NonceInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Dotclear\Interface\Core\PluginsInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\RestInterface;
use Dotclear\Interface\Core\SchemaInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\TaskInterface;
use Dotclear\Interface\Core\ThemesInterface;
use Dotclear\Interface\Core\TrackbackInterface;
use Dotclear\Interface\Core\UrlInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Interface\Core\VersionInterface;

/**
 * @brief   The core container.
 *
 * This container contents all methods related to
 * core class and callable from App::
 *
 * Core container services takes
 * dotclear core interface name as key and
 * a fully qualified class name or a callback as value.
 *
 * Available container methods are explicitly set
 * in this class to keep track of returned types.
 *
 * @since   2.28
 */
class Core extends Container
{
    /**
     * Dotclear core container ID.
     *
     * @var     string  CONTAINER_ID
     */
    public const CONTAINER_ID = 'core';

    /**
     * Core "singleton" instance.
     *
     * @var    Core  $instance
     */
    protected static Core $instance;

    /**
     * Constructor gets container services.
     *
     * @throws  Exception
     *
     * @param   ConfigInterface     $config     The config
     * @param   Factory             $factory    The factory (third party services)
     */
    public function __construct(
        protected ConfigInterface $config,
        Factory $factory
    ) {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }

        parent::__construct($factory);

        self::$instance = $this;
    }

    /**
     * Get config instance.
     *
     * @return  ConfigInterface     The config interface.
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }

    /**
     * Get default Dotclear services definitions.
     *
     * This adds default Core class to the App.
     *
     * @return  array<string,callable>  The default core services
     */
    protected function getDefaultServices(): array
    {
        return [
            ConfigInterface::class        => fn ($container) => $container->getConfig(),
            AuthInterface::class          => fn ($container) => Auth::init(),
            Backend::class                => Backend::class,
            BehaviorInterface::class      => Behavior::class,
            BlogInterface::class          => fn ($container) => $container->blogLoader()->getBlog(),
            BlogLoaderInterface::class    => BlogLoader::class,
            BlogSettingsInterface::class  => BlogSettings::class,
            BlogsInterface::class         => Blogs::class,
            BlogWorkspaceInterface::class => BlogWorkspace::class,
            CacheInterface::class         => Cache::class,
            CategoriesInterface::class    => Categories::class,
            ConnectionInterface::class    => function ($container, string $driver = '', string $host = '', string $database = '', string $user = '', string $password = '', bool $persistent = false, string $prefix = '') {
                if (empty($driver)) {
                    $driver     = $container->config()->dbDriver();
                    $host       = $container->config()->dbHost();
                    $database   = $container->config()->dbName();
                    $user       = $container->config()->dbUser();
                    $password   = $container->config()->dbPassword();
                    $persistent = $container->config()->dbPersist();
                    $prefix     = $container->config()->dbPrefix();
                }

                return Connection::init($driver, $host, $database, $user, $password, $persistent, $prefix);
            },
            ErrorInterface::class           => Error::class,
            DeprecatedInterface::class      => Deprecated::class,
            FilterInterface::class          => Filter::class,
            FormaterInterface::class        => Formater::class,
            Frontend::class                 => Frontend::class,
            LangInterface::class            => Lang::class,
            LexicalInterface::class         => Lexical::class,
            LogInterface::class             => Log::class,
            MediaInterface::class           => Media::class,
            MetaInterface::class            => Meta::class,
            NonceInterface::class           => Nonce::class,
            NoticeInterface::class          => Notice::class,
            PluginsInterface::class         => Plugins::class,
            PostMediaInterface::class       => PostMedia::class,
            PostTypesInterface::class       => PostTypes::class,
            RestInterface::class            => Rest::class,
            SchemaInterface::class          => fn ($container, ConnectionInterface $con) => Schema::init($con),
            SessionInterface::class         => Session::class,
            TaskInterface::class            => Task::class,
            ThemesInterface::class          => Themes::class,
            TrackbackInterface::class       => Trackback::class,
            UrlInterface::class             => Url::class,
            UsersInterface::class           => Users::class,
            UserPreferencesInterface::class => UserPreferences::class,
            UserWorkspaceInterface::class   => UserWorkspace::class,
            VersionInterface::class         => Version::class,
        ];
    }

    /**
     * Authentication handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.AuthInterface
     * @see     Uses default core service Dotclear.Core.Auth
     */
    public static function auth(): AuthInterface
    {
        return self::$instance->get(AuthInterface::class);
    }

    /**
     * Backend Utility.
     *
     * @see     Dotclear.Core.Backend.Utility
     */
    public static function backend(): Backend
    {
        return self::$instance->get(Backend::class);
    }

    /**
     * Behavior handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BehaviorInterface
     * @see     Uses default core service Dotclear.Core.Behavior
     */
    public static function behavior(): BehaviorInterface
    {
        return self::$instance->get(BehaviorInterface::class);
    }

    /**
     * Blog handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BlogInterface
     * @see     Uses default core service Dotclear.Core.Blog
     */
    public static function blog(): BlogInterface
    {
        return self::$instance->get(BlogInterface::class, true);
    }

    /**
     * Blog loader.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BlogLoaderInterface
     * @see     Uses default core service Dotclear.Core.BlogLoader
     */
    public static function blogLoader(): BlogLoaderInterface
    {
        return self::$instance->get(BlogLoaderInterface::class);
    }

    /**
     * Blog settings.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BlogSettingsInterface
     * @see     Uses default core service Dotclear.Core.BlogSettings
     *
     * @param   string  $blog_id    The blog ID
     */
    public static function blogSettings(?string $blog_id): BlogSettingsInterface
    {
        return self::$instance->get(BlogSettingsInterface::class, true, blog_id: $blog_id);
    }

    /**
     * Blogs handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BlogsInterface
     * @see     Uses default core service Dotclear.Core.Blogs
     */
    public static function blogs(): BlogsInterface
    {
        return self::$instance->get(BlogsInterface::class);
    }

    /**
     * Blog settings workspace.
     *
     * @see     Calls core container service Dotclear.Interface.Core.BlogWorkspaceInterface
     * @see     Uses default core service Dotclear.Core.BlogWorkspace
     */
    public static function blogWorkspace(): BlogWorkspaceInterface
    {
        return self::$instance->get(BlogWorkspaceInterface::class);
    }

    /**
     * Cache handler.
     *
     * @see     Uses default core service Dotclear.Core.BlogWorkspace
     * @see     Calls core container service Dotclear.Interface.Core.CacheInterface
     */
    public static function cache(): CacheInterface
    {
        return self::$instance->get(CacheInterface::class);
    }

    /**
     * Categories handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.CategoriesInterface
     * @see     Uses default core service Dotclear.Core.Categories
     */
    public static function categories(): CategoriesInterface
    {
        return self::$instance->get(CategoriesInterface::class);
    }

    /**
     * Create a new database connection from given values.
     *
     * @see     Calls core container service Dotclear.Interface.Core.ConnectionInterface
     * @see     Uses default core service Dotclear.Core.Connection
     * @see     Dotclear.Database.InterfaceHandler  Dotclear.Database.AbstractHandler
     *
     * @param   string  $driver         Driver name
     * @param   string  $host           Database hostname
     * @param   string  $database       Database name
     * @param   string  $user           User ID
     * @param   string  $password       Password
     * @param   bool    $persistent     Persistent connection
     * @param   string  $prefix         Database tables prefix
     */
    public static function newConnectionFromValues(string $driver, string $host, string $database, string $user = '', string $password = '', bool $persistent = false, string $prefix = ''): ConnectionInterface
    {
        return self::$instance->get(ConnectionInterface::class, true, $driver, $host, $database, $user, $password, $persistent, $prefix);
    }

    /**
     * Connection handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.ConnectionInterface
     * @see     Uses default core service Dotclear.Core.Connection
     * @see     Dotclear.Database.InterfaceHandler  Dotclear.Database.AbstractHandler
     */
    public static function con(): ConnectionInterface
    {
        return self::$instance->get(ConnectionInterface::class);
    }

    /**
     * Application configuration handler.
     *
     * @see     Dotclear.Config     Dotclear.Interface.ConfigInterface
     */
    public static function config(): ConfigInterface
    {
        return self::$instance->getConfig();
    }

    /**
     * Deprecated methods handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.DeprecatedInterface
     * @see     Uses default core service Dotclear.Core.Deprecated
     */
    public static function deprecated(): DeprecatedInterface
    {
        return self::$instance->get(DeprecatedInterface::class);
    }

    /**
     * Error handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.ErrorInterface
     * @see     Uses default core service Dotclear.Core.Error
     */
    public static function error(): ErrorInterface
    {
        return self::$instance->get(ErrorInterface::class);
    }

    /**
     * Wiki and HTML filters handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.FilterInterface
     * @see     Uses default core service Dotclear.Core.Filter
     */
    public static function filter(): FilterInterface
    {
        return self::$instance->get(FilterInterface::class);
    }

    /**
     * Text formaters handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.FormaterInterface
     * @see     Uses default core service Dotclear.Core.Formater
     */
    public static function formater(): FormaterInterface
    {
        return self::$instance->get(FormaterInterface::class);
    }

    /**
     * Frontend Utility.
     *
     * @see     Dotclear.Core.Frontend.Utility
     */
    public static function frontend(): Frontend
    {
        return self::$instance->get(Frontend::class);
    }

    /**
     * Lang setter.
     *
     * @see     Calls core container service Dotclear.Interface.Core.LangInterface
     * @see     Uses default core service Dotclear.Core.Lang
     */
    public static function lang(): LangInterface
    {
        return self::$instance->get(LangInterface::class);
    }

    /**
     * Lexical helper.
     *
     * @see     Calls core container service Dotclear.Interface.Core.LexicalInterface
     * @see     Uses default core service Dotclear.Core.Lexical
     */
    public static function lexical(): LexicalInterface
    {
        return self::$instance->get(LexicalInterface::class);
    }

    /**
     * Logs handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.LogInterface
     * @see     Uses default core service Dotclear.Core.Log
     */
    public static function log(): LogInterface
    {
        return self::$instance->get(LogInterface::class);
    }

    /**
     * Media handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.MediaInterface
     * @see     Uses default core service Dotclear.Core.Media
     */
    public static function media(): MediaInterface
    {
        return self::$instance->get(MediaInterface::class);
    }

    /**
     * Meta handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.MetaInterface
     * @see     Uses default core service Dotclear.Core.Meta
     */
    public static function meta(): MetaInterface
    {
        return self::$instance->get(MetaInterface::class);
    }

    /**
     * Form nonce handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.NonceInterface
     * @see     Uses default core service Dotclear.Core.Nonce
     */
    public static function nonce(): NonceInterface
    {
        return self::$instance->get(NonceInterface::class);
    }

    /**
     * Notices handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.NoticeInterface
     * @see     Uses default core service Dotclear.Core.Notice
     */
    public static function notice(): NoticeInterface
    {
        return self::$instance->get(NoticeInterface::class);
    }

    /**
     * Plugins handler.
     *
     * @see     Calls core container service Dotclear.Interface.Module.PluginsInterface
     * @see     Uses default core service Dotclear.Module.Plugins
     */
    public static function plugins(): PluginsInterface
    {
        return self::$instance->get(PluginsInterface::class);
    }

    /**
     * Post media handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.PostMediaInterface
     * @see     Uses default core service Dotclear.Core.PostMedia
     */
    public static function postMedia(): PostMediaInterface
    {
        return self::$instance->get(PostMediaInterface::class);
    }

    /**
     * Post types handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.PostTypesInterface
     * @see     Uses default core service Dotclear.Core.PostTypes
     */
    public static function postTypes(): PostTypesInterface
    {
        return self::$instance->get(PostTypesInterface::class);
    }

    /**
     * REST servcie handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.RestInterface
     * @see     Uses default core service Dotclear.Core.Rest
     */
    public static function rest(): RestInterface
    {
        return self::$instance->get(RestInterface::class);
    }

    /**
     * Database schema handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.SchemaInterface
     * @see     Uses default core service Dotclear.Core.Schema
     * @see     Dotclear.Database.AbstractSchema    Dotclear.Database.InterfaceSchema
     */
    public static function schema(ConnectionInterface $con): SchemaInterface
    {
        return self::$instance->get(SchemaInterface::class, true, $con);
    }

    /**
     * Session handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.SessionInterface
     * @see     Uses default core service Dotclear.Core.Session
     * @see     Dotclear.Database.Session
     */
    public static function session(): SessionInterface
    {
        return self::$instance->get(SessionInterface::class);
    }

    /**
     * Application task handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.TaskInterface
     * @see     Uses default core service Dotclear.Core.Task
     */
    public static function task(): TaskInterface
    {
        return self::$instance->get(TaskInterface::class);
    }

    /**
     * Themes handler.
     *
     * @see     Calls core container service Dotclear.Interface.Module.ThemesInterface
     * @see     Uses default core service Dotclear.Module.Themes
     */
    public static function themes(): ThemesInterface
    {
        return self::$instance->get(ThemesInterface::class);
    }

    /**
     * Trackbacks handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.TrackbacksInterface
     * @see     Uses default core service Dotclear.Core.Trackbacks
     */
    public static function trackback(): TrackbackInterface
    {
        return self::$instance->get(TrackbackInterface::class);
    }

    /**
     * Blog URL handler.
     *
     * @see     Calls core container service Dotclear.Interface.Frontend.UrlInterface
     * @see     Uses default core service Dotclear.Frontend.Url
     */
    public static function url(): UrlInterface
    {
        return self::$instance->get(UrlInterface::class);
    }

    /**
     * Users handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.UsersInterface
     * @see     Uses default core service Dotclear.Core.Users
     */
    public static function users(): UsersInterface
    {
        return self::$instance->get(UsersInterface::class);
    }

    /**
     * User preferences handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.PreferencesInterface
     * @see     Uses default core service Dotclear.Core.Preferences
     */
    public static function userPreferences(string $user_id, ?string $workspace = null): UserPreferencesInterface
    {
        return self::$instance->get(UserPreferencesInterface::class, reload: true, user_id: $user_id, workspace: $workspace);
    }

    /**
     * User preferences workspace handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.UserPreferencesInterface
     * @see     Uses default core service Dotclear.Core.UserPreferences
     */
    public static function userWorkspace(): UserWorkspaceInterface
    {
        return self::$instance->get(UserWorkspaceInterface::class);
    }

    /**
     * Modules version handler.
     *
     * @see     Calls core container service Dotclear.Interface.Core.VersionInterface
     * @see     Uses default core service Dotclear.Core.Version
     */
    public static function version(): VersionInterface
    {
        return self::$instance->get(VersionInterface::class);
    }
}
