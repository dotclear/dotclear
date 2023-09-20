<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

// Default core class from elsewhere, see Container::getDefaultServices()
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
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
     * Constructor gets factory services.
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
            AuthInterface::class          => fn ($container) => Auth::init(),
            Backend::class                => Backend::class,
            BehaviorInterface::class      => Behavior::class,
            BlogInterface::class          => fn ($container) => $container->blogLoader()->getBlog(),
            BlogLoaderInterface::class    => BlogLoader::class,
            BlogSettingsInterface::class  => BlogSettings::class,
            BlogsInterface::class         => Blogs::class,
            BlogWorkspaceInterface::class => BlogWorkspace::class,
            CacheInterface::class         => function ($container) {
                return new Cache(
                    $container->config()->cacheRoot()
                );
            },
            CategoriesInterface::class => Categories::class,
            ConnectionInterface::class => function ($container) {
                return AbstractHandler::init(
                    driver: $container->config()->dbDriver(),
                    host: $container->config()->dbHost(),
                    database: $container->config()->dbName(),
                    user: $container->config()->dbUser(),
                    password: $container->config()->dbPassword(),
                    persistent: $container->config()->dbPersist(),
                    prefix: $container->config()->dbPrefix()
                );
            },
            ErrorInterface::class      => Error::class,
            DeprecatedInterface::class => Deprecated::class,
            FilterInterface::class     => Filter::class,
            FormaterInterface::class   => Formater::class,
            Frontend::class            => Frontend::class,
            LexicalInterface::class    => Lexical::class,
            LogInterface::class        => Log::class,
            MediaInterface::class      => Media::class,
            MetaInterface::class       => Meta::class,
            NonceInterface::class      => Nonce::class,
            NoticeInterface::class     => Notice::class,
            PluginsInterface::class    => Plugins::class,
            PostMediaInterface::class  => PostMedia::class,
            PostTypesInterface::class  => PostTypes::class,
            RestInterface::class       => Rest::class,
            SessionInterface::class    => function ($container) {
                return new Session(
                    con: $container->con(),
                    table : $container->con()->prefix() . Session::SESSION_TABLE_NAME,
                    cookie_name: $container->config()->sessionName(),
                    cookie_secure: $container->config()->adminSsl(),
                    ttl: $container->config()->sessionTtl()
                );
            },
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

    public static function auth(): AuthInterface
    {
        return self::$instance->get(AuthInterface::class);
    }

    public static function backend(): Backend
    {
        return self::$instance->get(Backend::class);
    }

    public static function behavior(): BehaviorInterface
    {
        return self::$instance->get(BehaviorInterface::class);
    }

    public static function blog(): BlogInterface
    {
        return self::$instance->get(BlogInterface::class, reload: true);
    }

    public static function blogLoader(): BlogLoaderInterface
    {
        return self::$instance->get(BlogLoaderInterface::class);
    }

    public static function blogSettings(?string $blog_id): BlogSettingsInterface
    {
        return self::$instance->get(BlogSettingsInterface::class, reload: true, blog_id: $blog_id);
    }

    public static function blogs(): BlogsInterface
    {
        return self::$instance->get(BlogsInterface::class);
    }

    public static function blogWorkspace(): BlogWorkspaceInterface
    {
        return self::$instance->get(BlogWorkspaceInterface::class);
    }

    public static function cache(): CacheInterface
    {
        return self::$instance->get(CacheInterface::class);
    }

    public static function categories(): CategoriesInterface
    {
        return self::$instance->get(CategoriesInterface::class);
    }

    public static function con(): ConnectionInterface
    {
        return self::$instance->get(ConnectionInterface::class);
    }

    public static function config(): ConfigInterface
    {
        return self::$instance->getConfig();
    }

    public static function deprecated(): DeprecatedInterface
    {
        return self::$instance->get(DeprecatedInterface::class);
    }

    public static function error(): ErrorInterface
    {
        return self::$instance->get(ErrorInterface::class);
    }

    public static function filter(): FilterInterface
    {
        return self::$instance->get(FilterInterface::class);
    }

    public static function formater(): FormaterInterface
    {
        return self::$instance->get(FormaterInterface::class);
    }

    public static function frontend(): Frontend
    {
        return self::$instance->get(Frontend::class);
    }

    public static function lexical(): LexicalInterface
    {
        return self::$instance->get(LexicalInterface::class);
    }

    public static function log(): LogInterface
    {
        return self::$instance->get(LogInterface::class);
    }

    public static function media(): MediaInterface
    {
        return self::$instance->get(MediaInterface::class);
    }

    public static function meta(): MetaInterface
    {
        return self::$instance->get(MetaInterface::class);
    }

    public static function nonce(): NonceInterface
    {
        return self::$instance->get(NonceInterface::class);
    }

    public static function notice(): NoticeInterface
    {
        return self::$instance->get(NoticeInterface::class);
    }

    public static function plugins(): PluginsInterface
    {
        return self::$instance->get(PluginsInterface::class);
    }

    public static function postMedia(): PostMediaInterface
    {
        return self::$instance->get(PostMediaInterface::class);
    }

    public static function postTypes(): PostTypesInterface
    {
        return self::$instance->get(PostTypesInterface::class);
    }

    public static function rest(): RestInterface
    {
        return self::$instance->get(RestInterface::class);
    }

    public static function session(): SessionInterface
    {
        return self::$instance->get(SessionInterface::class);
    }

    public static function task(): TaskInterface
    {
        return self::$instance->get(TaskInterface::class);
    }

    public static function themes(): ThemesInterface
    {
        return self::$instance->get(ThemesInterface::class);
    }

    public static function trackback(): TrackbackInterface
    {
        return self::$instance->get(TrackbackInterface::class);
    }

    public static function url(): UrlInterface
    {
        return self::$instance->get(UrlInterface::class);
    }

    public static function users(): UsersInterface
    {
        return self::$instance->get(UsersInterface::class);
    }

    public static function userPreferences(string $user_id, ?string $workspace = null): UserPreferencesInterface
    {
        return self::$instance->get(UserPreferencesInterface::class, reload: true, user_id: $user_id, workspace: $workspace);
    }

    public static function userWorkspace(): UserWorkspaceInterface
    {
        return self::$instance->get(UserWorkspaceInterface::class);
    }

    public static function version(): VersionInterface
    {
        return self::$instance->get(VersionInterface::class);
    }
}
