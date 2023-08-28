<?php
/**
 * Core default factory.
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
use dcMeta;
use dcNotices;
//
use dcCore;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;

class CoreFactory implements CoreFactoryInterface
{
    public function __construct(
        protected Core $core
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

    public function blogs(): Blogs
    {
        return new Blogs(
            con: $this->core->get('con'),
            auth: $this->core->get('auth'),
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
        return new Filter();
    }

    public function formater(): Formater
    {
        return new Formater();
    }

    public function log(): dcLog
    {
        return new dcLog();
    }

    public function meta(): dcMeta
    {
        return new dcMeta();
    }

    public function nonce(): Nonce
    {
        return new Nonce(
            auth: $this->core->get('auth')
        );
    }

    public function notice(): dcNotices
    {
        return new dcNotices();
    }

    public function postTypes(): PostTypes
    {
        return new PostTypes();
    }

    public function session(): Session
    {
        return new Session(
            con: $this->core->get('con'),
            table : $this->core->get('con')->prefix() . Core::SESSION_TABLE_NAME,
            cookie_name: DC_SESSION_NAME,
            cookie_secure: DC_ADMIN_SSL,
            ttl: DC_SESSION_TTL
        );
    }

    public function users(): Users
    {
        return new Users(
            con: $this->core->get('con'),
            auth: $this->core->get('auth'),
            behavior: $this->core->get('behavior')
        );
    }

    public function version(): Version
    {
        return new Version(
            con: $this->core->get('con')
        );
    }
}