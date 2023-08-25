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

use dcCore;
use dcModuleDefine;
use dcThemes;
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
        if (is_null(dcCore::app()->themes)) {   // @phpstan-ignore-line
            dcCore::app()->themes = new dcThemes();
            if (!is_null(dcCore::app()->blog)) {
                dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path);
            }
        }

        return static::getDefineFromNamespace(dcCore::app()->themes);
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        // themes specific context permissions
        return match ($context) {
            self::BACKEND, self::CONFIG => defined('DC_CONTEXT_ADMIN')
                    // Check specific permission, allowed to blog admin for themes
                    && !is_null(dcCore::app()->blog)
                    && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,
                    ]), dcCore::app()->blog->id),
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

        if (is_null(dcCore::app()->blog)) {
            return '';
        }

        $base = preg_match('#^http(s)?://#', (string) dcCore::app()->blog->settings->system->themes_url) ?
            Http::concatURL(dcCore::app()->blog->settings->system->themes_url, '/' . self::id()) :
            Http::concatURL(dcCore::app()->blog->url, dcCore::app()->blog->settings->system->themes_url . '/' . self::id());

        return  $base . $resource;
    }
}
