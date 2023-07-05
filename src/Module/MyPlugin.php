<?php
/**
 * @brief Plugin My module class.
 *
 * A plugin My class must extend this class.
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
use Dotclear\Core\Backend\Menu;
use Dotclear\Core\Backend\Menus;

/**
 * Plugin module helper.
 *
 * My class of module of type "plugin" SHOULD extends this class.
 */
abstract class MyPlugin extends MyModule
{
    protected static function define(): dcModuleDefine
    {
        return static::getDefineFromNamespace(dcCore::app()->plugins);
    }

    /**
     * Register backend sidebar menu item.
     *
     * @param   string                  $menu   The menu (from Utility constant)
     * @param   array<string,string>    $params The URL params
     * @param   string                  $scheme The URL end scheme
     * @param   string                  $id     The id (if not provided a standard one will be set), will be prefixed by 'plugin-'
     */
    public static function addBackendMenuItem(string $menu = Menus::MENU_PLUGINS, array $params = [], string $scheme = '(&.*)?$', ?string $id = null): void
    {
        if (!defined('DC_CONTEXT_ADMIN') || !(dcCore::app()->admin->menu[$menu] instanceof Menu)) {
            return;
        }

        dcCore::app()->admin->menu[$menu]->addItem(
            static::name(),
            static::manageUrl($params, '&'),
            static::icons(),
            preg_match('/' . preg_quote(static::manageUrl([], '&')) . $scheme . '/', (string) $_SERVER['REQUEST_URI']),
            static::checkContext(static::MENU),
            'plugin-' . ($id ?? static::id())
        );
    }

    /**
     * Get modules icon URLs.
     *
     * Will use SVG format is exist, else PNG
     *
     * @param   string    $suffix   Optionnal suffix (will be prefixed by - if any)
     *
     * @return  array<int,string>   The module icons URLs
     */
    public static function icons(string $suffix = ''): array
    {
        $check = fn (string $base, string $name) => (file_exists($base . DIRECTORY_SEPARATOR . $name . '.svg') ?
            static::fileURL($name . '.svg') :
            (file_exists($base . DIRECTORY_SEPARATOR . $name . '.png') ?
                static::fileURL($name . '.png') :
                false));

        $icons = [];
        if (defined('DC_CONTEXT_ADMIN')) {
            // Light mode version
            if ($icon = $check(static::path(), 'icon' . ($suffix !== '' ? '-' . $suffix : ''))) {
                $icons[] = $icon;
            }
            // Dark mode version
            if ($icon = $check(static::path(), 'icon-dark' . ($suffix !== '' ? '-' . $suffix : ''))) {
                $icons[] = $icon;
            }
        }
        if (!count($icons) && $suffix) {
            // Suffixed icons not found, try without
            return static::icons();
        }

        return $icons;
    }

    /**
     * Get module backend url.
     *
     * @param   array<string,string|int>    $params     The URL parameters
     * @param   string                      $separator  The query string separator
     *
     * @return  string
     */
    public static function manageUrl(array $params = [], string $separator = '&amp;'): string
    {
        return defined('DC_CONTEXT_ADMIN') ? dcCore::app()->admin->url->get('admin.plugin.' . static::id(), $params, $separator) : '';
    }

    /**
     * Get module backend redirection.
     *
     * @param   array<string,string|int>    $params     The URL parameters
     * @param   string                      $suffix     The URL suffix (#)
     *
     * @return  void
     */
    public static function redirect(array $params = [], string $suffix = ''): void
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcCore::app()->admin->url->redirect('admin.plugin.' . static::id(), $params, $suffix);
        }
    }
}
