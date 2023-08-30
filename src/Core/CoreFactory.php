<?php
/**
 * Core default factory.
 *
 * Core factory instanciates main Core classes.
 * The factory should use Core container to get classes 
 * required by constructors.
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
use Dotclear\App;
use Dotclear\Core\Frontend\Url;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;

class CoreFactory implements CoreFactoryInterface
{
    public function __construct(
        protected CoreContainer $container
    ) {
    }

    public function auth(): dcAuth
    {
        return dcAuth::init();
    }

    public function behavior(): Behavior
    {
        return new Behavior();
    }

    public function blog(): ?dcBlog
    {
        return $this->container->get('blogLoader')->getBlog();
    }

    public function blogLoader(): BlogLoader
    {
        return new BlogLoader();
    }


    public function blogs(): Blogs
    {
        return new Blogs(
            con: $this->container->get('con'),
            auth: $this->container->get('auth'),
        );
    }

    public function con(): AbstractHandler
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

    public function filter(): Filter
    {
        return new Filter(
            behavior: $this->container->get('behavior'),
            blog_loader: $this->container->get('blogLoader')
        );
    }

    public function formater(): Formater
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

    public function nonce(): Nonce
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

    public function postTypes(): PostTypes
    {
        return new PostTypes();
    }

    public function rest(): dcRestServer
    {
        return new dcRestServer();
    }

    public function session(): Session
    {
        return new Session(
            con: $this->container->get('con'),
            table : $this->container->get('con')->prefix() . App::SESSION_TABLE_NAME,
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

    public function users(): Users
    {
        return new Users(
            con: $this->container->get('con'),
            auth: $this->container->get('auth'),
            behavior: $this->container->get('behavior')
        );
    }

    public function version(): Version
    {
        return new Version(
            con: $this->container->get('con')
        );
    }
}
