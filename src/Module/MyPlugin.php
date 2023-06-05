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

use dcAdmin;
use dcCore;
use dcMenu;
use dcModuleDefine;
use dcPage;

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
     * Register backend sidebar menu icon.
     *
     * @param   string                  $menu   The menu (from dcAdmin constant)
     * @param   array<string,string>    $params The URL params
     * @param   string                  $scheme the URL end scheme
     */
    public static function backendSidebarMenuIcon(string $menu = dcAdmin::MENU_PLUGINS, array $params = [], string $scheme = '(&.*)?$'): void
    {
        if (!defined('DC_CONTEXT_ADMIN') || is_null(dcCore::app()->adminurl) || !(dcCore::app()->menu[$menu] instanceof dcMenu)) {
            return;
        }

        dcCore::app()->menu[$menu]->addItem(
            static::name(),
            dcCore::app()->adminurl->get('admin.plugin.' . static::id(), $params),
            static::icons(),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . static::id())) . $scheme . '/', $_SERVER['REQUEST_URI']),
            static::checkContext(static::MENU)
        );
    }

    /**
     * Get modules icon URLs.
     *
     * @return  array<int,string>   The module icons URLs
     */
    public static function icons(): array
    {
        $icons = [];
        if (defined('DC_CONTEXT_ADMIN')) {
            if (file_exists(static::path() . DIRECTORY_SEPARATOR . 'icon.svg')) {
                $icons[] = urldecode(dcPage::getPF(static::id() . '/icon.svg'));
            }
            if (file_exists(static::path() . DIRECTORY_SEPARATOR . 'icon-dark.svg')) {
                $icons[] = urldecode(dcPage::getPF(static::id() . '/icon-dark.svg'));
            }
        }

        return $icons;
    }

    /**
     * Get module backend url.
     *
     * @param   array<string,string|int>    $params     The URL parameters
     *
     * @return  string
     */
    public static function manageUrl(array $params = []): string
    {
        return defined('DC_CONTEXT_ADMIN') && !is_null(dcCore::app()->adminurl) ? dcCore::app()->adminurl->get('admin.plugin.' . static::id(), $params) : '';
    }
}