<?php
/**
 * Core factory interface.
 *
 * Core factory interface protect Core compatibility.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

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

interface FactoryInterface
{
    public function auth(): dcAuth;
    public function behavior(): BehaviorInterface;
    public function blog(): ?dcBlog;
    public function blogLoader(): BlogLoaderInterface;
    public function blogs(): BlogsInterface;
    public function con(): ConnectionInterface;
    public function error(): dcError;
    public function filter(): FilterInterface;
    public function formater(): FormaterInterface;
    public function log(): dcLog;
    public function meta(): dcMeta;
    public function media(): dcMedia;
    public function nonce(): NonceInterface;
    public function notice(): dcNotices;
    public function plugins(): dcPlugins;
    public function postMedia(): dcPostMedia;
    public function postTypes(): PostTypesInterface;
    public function rest(): dcRestServer;
    public function session(): SessionInterface;
    public function themes(): dcThemes;
    public function url(): Url;
    public function users(): UsersInterface;
    public function version(): VersionInterface;
}
