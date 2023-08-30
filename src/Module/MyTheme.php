<?php
/**
 * @brief Theme My module class.
 *
 * A theme My class must extend this class.
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

use dcModuleDefine;
use Dotclear\App;
use Dotclear\Helper\Network\Http;

/**
 * Theme module helper.
 *
 * My class of module of type "theme" SHOULD extends this class.
 */
abstract class MyTheme extends MyModule
{
    protected static function define(): dcModuleDefine
    {
        // load once themes
        if (App::themes()->isEmpty() && !is_null(App::blog())) {
            App::themes()->loadModules(App::blog()->themes_path);
        }

        return static::getDefineFromNamespace(App::themes());
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        // themes specific context permissions
        return match ($context) {
            self::BACKEND, self::CONFIG => defined('DC_CONTEXT_ADMIN')
                    // Check specific permission, allowed to blog admin for themes
                    && !is_null(App::blog())
                    && App::auth()->check(App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]), App::blog()->id),
            default => null,
        };
    }

    /**
     * Returns URL of a theme file.
     *
     * Always returns frontend (public) URL
     *
     * @param   string  $resource   The resource file
     * @param   bool    $frontend   (not used for themes)
     *
     * @return  string
     */
    public static function fileURL(string $resource, bool $frontend = false): string
    {
        if (!empty($resource) && substr($resource, 0, 1) !== '/') {
            $resource = '/' . $resource;
        }

        if (is_null(App::blog())) {
            return '';
        }

        $base = preg_match('#^http(s)?://#', (string) App::blog()->settings->system->themes_url) ?
            Http::concatURL(App::blog()->settings->system->themes_url, '/' . self::id()) :
            Http::concatURL(App::blog()->url, App::blog()->settings->system->themes_url . '/' . self::id());

        return  $base . $resource;
    }
}
