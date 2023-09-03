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
use dcPlugins;
use dcThemes;
//
use Dotclear\Core\Frontend\Url;

interface FactoryInterface
{
    public function auth(): AuthInterface;
    public function behavior(): BehaviorInterface;
    public function blog(): BlogInterface;
    public function blogLoader(): BlogLoaderInterface;
    public function blogs(): BlogsInterface;
    public function con(): ConnectionInterface;
    public function error(): ErrorInterface;
    public function filter(): FilterInterface;
    public function formater(): FormaterInterface;
    public function log(): LogInterface;
    public function media(): MediaInterface;
    public function meta(): MetaInterface;
    public function nonce(): NonceInterface;
    public function notice(): NoticeInterface;
    public function plugins(): dcPlugins;
    public function postMedia(): PostMediaInterface;
    public function postTypes(): PostTypesInterface;
    public function rest(): RestInterface;
    public function session(): SessionInterface;
    public function themes(): dcThemes;
    public function url(): Url;
    public function users(): UsersInterface;
    public function version(): VersionInterface;
}
