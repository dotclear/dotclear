<?php
/**
 * Core default factory.
 *
 * Core factory instanciates main Core classes.
 * The factory should use Core container to get classes
 * required by constructors.
 *
 * Default factory uses Dotclear\Database clases for
 * database connection handler and session handler.
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
use dcBlog;
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

use Dotclear\Interface\Core\BehaviorInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;
use Dotclear\Interface\Core\BlogsInterface;
use Dotclear\Interface\Core\ConnectionInterface;
use Dotclear\Interface\Core\FactoryInterface;
use Dotclear\Interface\Core\FilterInterface;
use Dotclear\Interface\Core\FormaterInterface;
use Dotclear\Interface\Core\NonceInterface;
use Dotclear\Interface\Core\PostTypesInterface;
use Dotclear\Interface\Core\SessionInterface;
use Dotclear\Interface\Core\UsersInterface;
use Dotclear\Interface\Core\VersionInterface;

class Factory implements FactoryInterface
{
    /**
     * Constructor takes Container instance.
     *
     * @param   Container   $container The core container
     */
    public function __construct(
        protected Container $container
    ) {
    }

    public function auth(): dcAuth
    {
        return dcAuth::init();
    }

    public function behavior(): BehaviorInterface
    {
        return new Behavior();
    }

    public function blog(): ?dcBlog
    {
        return $this->container->get('blogLoader')->getBlog();
    }

    public function blogLoader(): BlogLoaderInterface
    {
        return new BlogLoader();
    }

    public function blogs(): BlogsInterface
    {
        return new Blogs(
            con: $this->container->get('con'),
            auth: $this->container->get('auth'),
        );
    }

    public function con(): ConnectionInterface
    {
        return AbstractHandler::init(
            driver: DC_DBDRIVER,
            host: DC_DBHOST,
            database: DC_DBNAME,
            user: DC_DBUSER,
            password: DC_DBPASSWORD,
            persistent: DC_DBPERSIST,
            prefix: DC_DBPREFIX
        );
    }

    public function error(): dcError
    {
        return new dcError();
    }

    public function filter(): FilterInterface
    {
        return new Filter(
            behavior: $this->container->get('behavior'),
            blog_loader: $this->container->get('blogLoader')
        );
    }

    public function formater(): FormaterInterface
    {
        return new Formater(
            plugins: $this->container->get('plugins')
        );
    }

    public function log(): dcLog
    {
        return new dcLog();
    }

    public function media(): dcMedia
    {
        return new dcMedia();
    }

    public function meta(): dcMeta
    {
        return new dcMeta();
    }

    public function nonce(): NonceInterface
    {
        return new Nonce(
            auth: $this->container->get('auth')
        );
    }

    public function notice(): dcNotices
    {
        return new dcNotices();
    }

    public function plugins(): dcPlugins
    {
        return new dcPlugins();
    }

    public function postMedia(): dcPostMedia
    {
        return new dcPostMedia();
    }

    public function postTypes(): PostTypesInterface
    {
        return new PostTypes();
    }

    public function rest(): dcRestServer
    {
        return new dcRestServer();
    }

    public function session(): SessionInterface
    {
        return new Session(
            con: $this->container->get('con'),
            table : $this->container->get('con')->prefix() . Session::SESSION_TABLE_NAME,
            cookie_name: DC_SESSION_NAME,
            cookie_secure: DC_ADMIN_SSL,
            ttl: DC_SESSION_TTL
        );
    }

    public function themes(): dcThemes
    {
        return new dcThemes();
    }

    public function url(): Url
    {
        return new Url();
    }

    public function users(): UsersInterface
    {
        return new Users(
            con: $this->container->get('con'),
            auth: $this->container->get('auth'),
            behavior: $this->container->get('behavior')
        );
    }

    public function version(): VersionInterface
    {
        return new Version(
            con: $this->container->get('con')
        );
    }
}
