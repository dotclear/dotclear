<?php
/**
 * @brief Generic My module class.
 *
 * This class is an helper to have short access to
 * module properties and common requiremets.
 *
 * A module My class must not extend this class
 * but must extend MyPlugin or MyTheme class.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.27
 */
declare(strict_types=1);

namespace Dotclear\Module;

use dcModules;
use dcModuleDefine;
use dcNamespace;
use dcUtils;
use dcWorkspace;
use Dotclear\App;
use Dotclear\Helper\L10n;
use Exception;

/**
 * Module helper.
 *
 * Module My class MUST NOT extend this class
 * but MyPlugin or MyTheme.
 */
abstract class MyModule
{
    /** @var    int     Install context */
    public const INSTALL = 0;

    /** @var    int     Prepend context */
    public const PREPEND = 1;

    /** @var    int     Frontend context */
    public const FRONTEND = 2;

    /** @var    int     Backend context (usually when the connected user may access at least one functionnality of this module) */
    public const BACKEND = 3;

    /** @var    int     Manage context (main page of module) */
    public const MANAGE = 4;

    /** @var    int     Config context (config page of module) */
    public const CONFIG = 5;

    /** @var    int     Menu context (adding a admin menu item) */
    public const MENU = 6;

    /** @var    int     Widgets context (managing blog's widgets) */
    public const WIDGETS = 7;

    /** @var    int     Uninstall context */
    public const UNINSTALL = 8;

    /** @var    array<string,dcModuleDefine>    The know modules defines */
    protected static $defines = [];

    /**
     * Load (once) the module define.
     *
     * This method is defined in MyPlugin or MyTheme.
     *
     * @return  dcModuleDefine  The module define
     */
    abstract protected static function define(): dcModuleDefine;

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

        // else default permissions
        return match ($context) {
            // Installation of module
            self::INSTALL => defined('DC_CONTEXT_ADMIN')
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin()
                    // And only if new version of module
                    && App::version()->newerVersion(self::id(), (string) App::plugins()->getDefine(self::id())->get('version')),

            // Uninstallation of module
            self::UNINSTALL => defined('DC_RC_PATH')
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin(),

            // Prepend and Frontend context
            self::PREPEND,
            self::FRONTEND => defined('DC_RC_PATH'),

            // Backend context
            self::BACKEND => defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_USAGE,
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                    ]), App::blog()->id()),

            // Main page of module, Admin menu, Blog widgets
            self::MANAGE,
            self::MENU,
            self::WIDGETS => defined('DC_CONTEXT_ADMIN')
                    // Check specific permission
                    && App::blog()->isDefined()
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,  // Admin+
                    ]), App::blog()->id()),

            // Config page of module
            self::CONFIG => defined('DC_CONTEXT_ADMIN')
                    // Manageable only by super-admin
                    && App::auth()->isSuperAdmin(),

            default => false,
        };
    }

    /**
     * Get the module path.
     *
     * @return  string  The module path
     */
    final public static function path(): string
    {
        $root = static::define()->get('root');

        return is_string($root) ? $root : '';
    }

    /**
     * The module ID.
     *
     * @return  string The module ID
     */
    final public static function id(): string
    {
        return static::define()->getId();
    }

    /**
     * The module name.
     *
     * @return  string The module translated name
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
     *
     * @return  dcNamespace    The module settings instance
     */
    final public static function settings(): dcNamespace
    {
        return App::blog()->settings()->get(static::id());
    }

    /**
     * The module preferences instance.
     *
     * @return  null|dcWorkspace    The module preferences instance
     */
    final public static function prefs(): ?dcWorkspace
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
        L10n::set(implode(DIRECTORY_SEPARATOR, [static::path(), 'locales', App::lang(), $process]));
    }

    /**
     * Returns URL of a module file.
     *
     * In frontend it returns public URL,
     * In backend it returns admin URL (or public with $frontend=true)
     *
     * @param   string  $resource   The resource file
     * @param   bool    $frontend   Force to get frontend (public) URL even in backend
     *
     * @return  string
     */
    public static function fileURL(string $resource, bool $frontend = false): string
    {
        if (!empty($resource) && substr($resource, 0, 1) !== '/') {
            $resource = '/' . $resource;
        }
        if (defined('DC_CONTEXT_ADMIN') && DC_CONTEXT_ADMIN && !$frontend) {
            return urldecode(App::backend()->url->get('load.plugin.file', ['pf' => self::id() . $resource], '&'));
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
     *
     * @return  string
     */
    public static function cssLoad(string $resource, string $media = 'screen', ?string $version = ''): string
    {
        $base = substr($resource, 0, 1)   === '/' ? '' : 'css/';
        $ext  = strpos($resource, '.css') === false ? '.css' : '';

        if (is_null($version) || $version === '') {
            $version = App::version()->getVersion(self::id());
        }

        return dcUtils::cssLoad(static::fileURL($base . $resource . $ext), $media, $version);
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
     *
     * @return  string
     */
    public static function jsLoad(string $resource, ?string $version = '', bool $module = false): string
    {
        $base = substr($resource, 0, 1)  === '/' ? '' : 'js/';
        $ext  = strpos($resource, '.js') === false ? '.js' : '';
        if ($module && strpos($resource, '.js') === false) {
            $ext = strpos($resource, '.mjs') === false ? '.mjs' : '';
        }

        if (is_null($version) || $version === '') {
            $version = App::version()->getVersion(self::id());
        }

        return dcUtils::jsLoad(static::fileURL($base . $resource . $ext), $version, $module);
    }

    /**
     * Get module define from its namespace.
     *
     * This method is used to load module define.
     * see MyPlugin::define() and MyTheme::define()
     *
     * @param   null|dcModules  $modules    The modules instance (Themes or Plugins)
     *
     * @return  dcModuleDefine  The module define
     */
    final protected static function getDefineFromNamespace(?dcModules $modules): dcModuleDefine
    {
        // take into account modules not loaded
        if (null === $modules) {
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
        $msg = defined('DC_DEV') && DC_DEV && !empty($msg) ? ': ' . $msg : '';

        throw new Exception('Invalid module structure' . $msg);
    }
}
