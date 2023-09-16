<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Core\Frontend\Url;
use Dotclear\Database\AbstractHandler;
use Dotclear\Module\Plugins;
use Dotclear\Module\Themes;
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
use Dotclear\Interface\Core\FactoryInterface;
use Dotclear\Interface\Core\FilterInterface;
use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\LexicalInterface;
use Dotclear\Interface\Core\LogInterface;
use Dotclear\Interface\Core\MediaInterface;
use Dotclear\Interface\Core\MetaInterface;
use Dotclear\Interface\Core\NonceInterface;
use Dotclear\Interface\Core\NoticeInterface;
use Dotclear\Interface\Core\PostMediaInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\RestInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\TaskInterface;
use Dotclear\Interface\Core\TrackbackInterface;
use Dotclear\Interface\Core\UrlInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Interface\Core\UserPreferencesInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Interface\Core\VersionInterface;
use Dotclear\Interface\Module\ModulesInterface;

/**
 * @brief   Core default factory.
 *
 * Core factory instanciates main Core classes.
 * The factory should use Core container to get classes
 * required by constructors.
 *
 * Default factory uses Dotclear\Database clases for
 * database connection handler and session handler.
 */
class Factory implements FactoryInterface
{
    /**
     * Constructor takes Container instance.
     *
     * @param   App  $container The core container
     */
    public function __construct(
        protected App $container
    ) {
    }

    public function auth(): AuthInterface
    {
        return Auth::init();
    }

    public function backend(): Backend
    {
        return new Backend();
    }

    public function behavior(): BehaviorInterface
    {
        return new Behavior();
    }

    public function blog(): BlogInterface
    {
        return $this->container->blogLoader()->getBlog();
    }

    public function blogSettings(?string $blog_id): BlogSettingsInterface
    {
        return new BlogSettings(
            blog_id: $blog_id
        );
    }

    public function blogLoader(): BlogLoaderInterface
    {
        return new BlogLoader();
    }

    public function blogs(): BlogsInterface
    {
        return new Blogs();
    }

    public function blogWorkspace(): BlogWorkspaceInterface
    {
        return new BlogWorkspace();
    }

    public function cache(): CacheInterface
    {
        return new Cache(
            cache_dir: $this->container->config()->cacheRoot()
        );
    }

    public function categories(): CategoriesInterface
    {
        return new Categories();
    }

    public function con(): ConnectionInterface
    {
        return AbstractHandler::init(
            driver: $this->container->config()->dbDriver(),
            host: $this->container->config()->dbHost(),
            database: $this->container->config()->dbName(),
            user: $this->container->config()->dbUser(),
            password: $this->container->config()->dbPassword(),
            persistent: $this->container->config()->dbPersist(),
            prefix: $this->container->config()->dbPrefix()
        );
    }

    public function error(): ErrorInterface
    {
        return new Error();
    }

    public function deprecated(): DeprecatedInterface
    {
        return new Deprecated();
    }

    public function filter(): FilterInterface
    {
        return new Filter();
    }

    public function formater(): FormaterInterface
    {
        return new Formater();
    }

    public function frontend(): Frontend
    {
        return new Frontend();
    }

    public function lexical(): LexicalInterface
    {
        return new Lexical();
    }

    public function log(): LogInterface
    {
        return new Log();
    }

    public function media(): MediaInterface
    {
        return new Media();
    }

    public function meta(): MetaInterface
    {
        return new Meta();
    }

    public function nonce(): NonceInterface
    {
        return new Nonce();
    }

    public function notice(): NoticeInterface
    {
        return new Notice();
    }

    public function plugins(): ModulesInterface
    {
        return new Plugins();
    }

    public function postMedia(): PostMediaInterface
    {
        return new PostMedia();
    }

    public function postTypes(): PostTypesInterface
    {
        return new PostTypes();
    }

    public function rest(): RestInterface
    {
        return new Rest();
    }

    public function session(): SessionInterface
    {
        return new Session(
            con: $this->container->con(),
            table : $this->container->con()->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: $this->container->config()->sessionName(),
            cookie_secure: $this->container->config()->adminSsl(),
            ttl: $this->container->config()->sessionTtl()
        );
    }

    public function task(): TaskInterface
    {
        return new Task();
    }

    public function themes(): ModulesInterface
    {
        return new Themes();
    }

    public function trackback(): TrackbackInterface
    {
        return new Trackback();
    }

    public function url(): UrlInterface
    {
        return new Url();
    }

    public function users(): UsersInterface
    {
        return new Users();
    }

    public function userPreferences(string $user_id, ?string $workspace = null): UserPreferencesInterface
    {
        return new UserPreferences(
            user_id: $user_id,
            workspace: $workspace
        );
    }

    public function userWorkspace(): UserWorkspaceInterface
    {
        return new UserWorkspace();
    }

    public function version(): VersionInterface
    {
        return new Version();
    }
}
