<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

//use Dotclear\Core\Container;
use Dotclear\Interface\Module\ModulesInterface;

/**
 * Core factory interface.
 *
 * Core factory interface protect Core compatibility.
 */
interface FactoryInterface
{
    //public function __construct(Container $container);
    public function auth(): AuthInterface;
    public function behavior(): BehaviorInterface;
    public function blog(): BlogInterface;
    public function blogSettings(?string $blog_id): BlogSettingsInterface;
    public function blogLoader(): BlogLoaderInterface;
    public function blogWorkspace(): BlogWorkspaceInterface;
    public function blogs(): BlogsInterface;
    public function con(): ConnectionInterface;
    public function cache(): CacheInterface;
    public function categories(): CategoriesInterface;
    public function deprecated(): DeprecatedInterface;
    public function error(): ErrorInterface;
    public function filter(): FilterInterface;
    public function formater(): FormaterInterface;
    public function log(): LogInterface;
    public function lexical(): LexicalInterface;
    public function media(): MediaInterface;
    public function meta(): MetaInterface;
    public function nonce(): NonceInterface;
    public function notice(): NoticeInterface;
    public function plugins(): ModulesInterface;
    public function postMedia(): PostMediaInterface;
    public function postTypes(): PostTypesInterface;
    public function rest(): RestInterface;
    public function session(): SessionInterface;
    public function themes(): ModulesInterface;
    public function trackback(): TrackbackInterface;
    public function url(): UrlInterface;
    public function users(): UsersInterface;
    public function userPreferences(string $user_id, ?string $workspace = null): UserPreferencesInterface;
    public function userWorkspace(): UserWorkspaceInterface;
    public function version(): VersionInterface;
}
