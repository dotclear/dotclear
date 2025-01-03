<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Dotclear\Helper\L10n;
use Dotclear\Interface\Core\BlogWorkspaceInterface;
use Dotclear\Interface\Core\UserWorkspaceInterface;
use Dotclear\Interface\Module\ModulesInterface;
use Exception;

/**
 * @brief   Generic My module class.
 *
 * This class is an helper to have short access to module properties and common requirements.
 *
 * A module My class must not extend this class but MUST extend MyPlugin or MyTheme class.
 *
 * @since   2.27
 */
abstract class MyModule
{
    /**
     * Install context.
     *
     * @var     int     INSTALL
     */
    public const INSTALL = 0;

    /**
     * Prepend context.
     *
     * @var     int     PREPEND
     */
    public const PREPEND = 1;

    /**
     * Frontend context.
     *
     * @var     int     FRONTEND
     */
    public const FRONTEND = 2;

    /**
     * Backend context (usually when the connected user may access at least one functionnality of this module).
     *
     * @var     int     BACKEND
     */
    public const BACKEND = 3;

    /**
     * Manage context (main page of module).
     *
     * @var     int     MANAGE
     */
    public const MANAGE = 4;

    /**
     * Config context (config page of module).
     *
     * @var     int     CONFIG
     */
    public const CONFIG = 5;

    /**
     * Menu context (adding a admin menu item).
     *
     * @var     int     MENU
     */
    public const MENU = 6;

    /**
     * Widgets context (managing blog's widgets).
     *
     * @var     int     WIDGETS
     */
    public const WIDGETS = 7;

    /**
     * Uninstall context.
     *
     * @since   2.28
     * @var     int     UNINSTALL
     */
    public const UNINSTALL = 8;

    /**
     * Global module context.
     *
     * @since   2.28
     * @var     int     MODULE
     */
    public const MODULE = 10;

    /**
     * The know modules defines.
     *
     * @var     array<string,ModuleDefine>  $defines
     */
    protected static $defines = [];

    /**
     * Load (once) the module define.
     *
     * This method is defined in MyPlugin or MyTheme.
     *
     * @return  ModuleDefine  The module define
     */
    abstract protected static function define(): ModuleDefine;

    /**
     * Check context permission.
     *
     * Module My class could implement this method
     * to check specific context permissions,
     * and else return null for classic context permissions.
     *
     * @param   int     $context     context
     *
     * @return  null|bool    true if allowed, false if not, null to let MyModule do check
     */
    protected static function checkCustomContext(int $context): ?bool
    {
        return null;
    }

    /**
     * Check permission depending on given context.
     *
     * @param   int     $context     context
     *
     * @return  bool    true if allowed, else false
     */
    final public static function checkContext(int $context): bool
    {
        // module contextual permissions
        $check = static::checkCustomContext($context);
        if (!is_null($check)) {
            return $check;
        }

        // else default permissions, we always check for whole module perms first
        return  static::checkCustomContext(self::MODULE) !== false && match ($context) {
            // Global module context (Beware this can be check in BACKEND, FRONTEND, INSTALL,...)
            self::MODULE => App::config()->configPath() !== '',

            // Installation of module
            self::INSTALL => App::task()->checkContext('BACKEND')
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin()
                    // And only if new version of module
                    && App::version()->newerVersion(self::id(), (string) App::plugins()->getDefine(self::id())->get('version')),

            // Uninstallation of module
            self::UNINSTALL => App::config()->configPath() !== ''
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin(),

            // Prepend and Frontend context
            self::PREPEND,
            self::FRONTEND => App::config()->configPath() !== '',

            // Backend context
            self::BACKEND => App::task()->checkContext('BACKEND')
                    // Check specific permission
                    && App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_USAGE,
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                    ]), App::blog()->id()),

            // Main page of module, Admin menu, Blog widgets
            self::MANAGE,
            self::MENU,
            self::WIDGETS => App::task()->checkContext('BACKEND')
                    // Check specific permission
                    && App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,  // Admin+
                    ]), App::blog()->id()),

            // Config page of module
            self::CONFIG => App::task()->checkContext('BACKEND')
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin(),

            default => false,
        };
    }

    /**
     * Get the module path.
     */
    final public static function path(): string
    {
        $root = static::define()->get('root');

        return is_string($root) ? $root : '';
    }

    /**
     * Get the module ID.
     */
    final public static function id(): string
    {
        return static::define()->getId();
    }

    /**
     * Get the module translated name.
     */
    final public static function name(): string
    {
        $name = static::define()->get('name');

        return is_string($name) ? __($name) : __(static::id());
    }

    /**
     * The module settings instance.
     *
     * @throws  Exception   Since 2.28 if blog is not defined
     */
    final public static function settings(): BlogWorkspaceInterface
    {
        return App::blog()->settings()->get(static::id());
    }

    /**
     * The module preferences instance.
     */
    final public static function prefs(): UserWorkspaceInterface
    {
        return App::auth()->prefs()->get(static::id());
    }

    /**
     * Set module locales.
     *
     * @param   string  $process    The locales process
     */
    final public static function l10n(string $process): void
    {
        L10n::set(implode(DIRECTORY_SEPARATOR, [static::path(), 'locales', App::lang()->getLang(), $process]));
    }

    /**
     * Returns URL of a module file.
     *
     * In frontend it returns public URL,
     * In backend it returns admin URL (or public with $frontend=true)
     *
     * @param   string  $resource   The resource file
     * @param   bool    $frontend   Force to get frontend (public) URL even in backend
     */
    public static function fileURL(string $resource, bool $frontend = false): string
    {
        if ($resource !== '' && !str_starts_with($resource, '/')) {
            $resource = '/' . $resource;
        }
        if (App::task()->checkContext('BACKEND') && !$frontend) {
            return urldecode(App::backend()->url()->get('load.plugin.file', ['pf' => self::id() . $resource], '&'));
        }

        return App::blog()->isDefined() ? urldecode(App::blog()->getQmarkURL() . 'pf=' . self::id() . $resource) : '';
    }

    /**
     * Return a HTML CSS resource load (usually in HTML head).
     *
     * Resource MUST be in 'css' module subfolder.
     * or put a / at the beginning to use another path, ex: My::cssLoad('/lib/external');
     *
     * @param   string          $resource   The resource
     * @param   string          $media      The media
     * @param   null|string     $version    The version
     */
    public static function cssLoad(string $resource, string $media = 'screen', ?string $version = ''): string
    {
        $base = str_starts_with($resource, '/') ? '' : 'css/';
        $ext  = str_contains($resource, '.css') ? '' : '.css';

        if (is_null($version) || $version === '') {
            $version = App::version()->getVersion(self::id());
        }

        return App::plugins()->cssLoad(static::fileURL($base . $resource . $ext), $media, $version);
    }

    /**
     * Return a HTML JS resource load (usually in HTML head).
     *
     * Resource MUST be in 'js' module subfolder
     * or put a / at the beginning to use another path, ex: My::jsLoad('/lib/external');
     *
     * @param   string          $resource   The resource
     * @param   null|string     $version    The version
     * @param   bool            $module     Load source as JS module
     */
    public static function jsLoad(string $resource, ?string $version = '', bool $module = false): string
    {
        $base = str_starts_with($resource, '/') ? '' : 'js/';
        $ext  = str_contains($resource, '.js') ? '' : '.js';
        if ($module && !str_contains($resource, '.js')) {
            $ext = str_contains($resource, '.mjs') ? '' : '.mjs';
        }

        if (is_null($version) || $version === '') {
            $version = App::version()->getVersion(self::id());
        }

        return App::plugins()->jsLoad(static::fileURL($base . $resource . $ext), $version, $module);
    }

    /**
     * Get module define from its namespace.
     *
     * This method is used to load module define.
     * see MyPlugin::define() and MyTheme::define()
     *
     * @param   null|ModulesInterface   $modules    The modules instance (Themes or Plugins)
     */
    final protected static function getDefineFromNamespace(?ModulesInterface $modules): ModuleDefine
    {
        // take into account modules not loaded
        if (!$modules instanceof ModulesInterface) {
            static::exception('Failed to load modules for ' . static::class);
        }

        // check if Define is already known
        if (!isset(static::$defines[static::class])) {
            // note: namespace from Modules start with a backslash
            $find = $modules->getDefines([
                'namespace' => '\\' . (new \ReflectionClass(static::class))->getNamespaceName(),
            ]);
            if (count($find) != 1) {
                static::exception('Failed to find namespace from ' . static::class);
            }

            static::$defines[static::class] = $find[0];
        }

        return static::$defines[static::class];
    }

    /**
     * Throw exception on breaking script error.
     *
     * @return never
     */
    final protected static function exception(string $msg = '')
    {
        $msg = App::config()->devMode() && $msg !== '' ? ': ' . $msg : '';

        throw new Exception('Invalid module structure' . $msg);
    }
}
