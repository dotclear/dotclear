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
use Dotclear\Core\Core;
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
        if (Core::themes()->isEmpty() && !is_null(Core::blog())) {
            Core::themes()->loadModules(Core::blog()->themes_path);
        }

        return static::getDefineFromNamespace(Core::themes());
    }

    protected static function checkCustomContext(int $context): ?bool
    {
        // themes specific context permissions
        return match ($context) {
            self::BACKEND, self::CONFIG => defined('DC_CONTEXT_ADMIN')
                    // Check specific permission, allowed to blog admin for themes
                    && !is_null(Core::blog())
                    && Core::auth()->check(Core::auth()->makePermissions([
                        Core::auth()::PERMISSION_ADMIN,
                    ]), Core::blog()->id),
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

        if (is_null(Core::blog())) {
            return '';
        }

        $base = preg_match('#^http(s)?://#', (string) Core::blog()->settings->system->themes_url) ?
            Http::concatURL(Core::blog()->settings->system->themes_url, '/' . self::id()) :
            Http::concatURL(Core::blog()->url, Core::blog()->settings->system->themes_url . '/' . self::id());

        return  $base . $resource;
    }
}
