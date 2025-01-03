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
use Dotclear\Core\Backend\Menu;
use Dotclear\Core\Backend\Menus;
use Dotclear\Helper\Html\Form\Hidden;

/**
 * @brief   Plugin My module class.
 *
 * A plugin My class must extend this class.
 *
 * @since   2.27
 */
abstract class MyPlugin extends MyModule
{
    protected static function define(): ModuleDefine
    {
        return static::getDefineFromNamespace(App::plugins());
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
        if (!App::task()->checkContext('BACKEND') || !(App::backend()->menus()[$menu] instanceof Menu)) {
            return;
        }

        App::backend()->menus()[$menu]->addItem(
            static::name(),
            static::manageUrl($params, '&'),
            static::icons(),
            preg_match('/' . preg_quote(static::manageUrl([], '&')) . $scheme . '/', (string) $_SERVER['REQUEST_URI']), // @phpstan-ignore-line
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
        $check = fn (string $base, string $name): false|string => (file_exists($base . DIRECTORY_SEPARATOR . $name . '.svg') ?
            static::fileURL($name . '.svg') :
            (file_exists($base . DIRECTORY_SEPARATOR . $name . '.png') ?
                static::fileURL($name . '.png') :
                false));

        $icons = [];
        if (App::task()->checkContext('BACKEND')) {
            // Light mode version
            if ($icon = $check(static::path(), 'icon' . ($suffix !== '' ? '-' . $suffix : ''))) {
                $icons[] = $icon;
            }
            // Dark mode version
            if ($icon = $check(static::path(), 'icon-dark' . ($suffix !== '' ? '-' . $suffix : ''))) {
                $icons[] = $icon;
            }
        }
        if ($icons === [] && $suffix) {
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
     * @param   bool                        $parametric Set to true if url will be used as (s)printf() format
     */
    public static function manageUrl(array $params = [], string $separator = '&amp;', bool $parametric = false): string
    {
        return App::task()->checkContext('BACKEND') ? App::backend()->url()->get('admin.plugin.' . static::id(), $params, $separator, $parametric) : '';
    }

    /**
     * Get form hidden fields.
     *
     * @param   array<string,string|int>    $params     The additionnal parameters
     *
     * @return  array<int,Hidden>
     */
    public static function hiddenFields(array $params = []): array
    {
        $fields = [];
        if (App::task()->checkContext('BACKEND')) {
            $params = [
                ...App::backend()->url()->getParams('admin.plugin.' . static::id()),
                ...$params,
            ];
            foreach ($params as $key => $value) {
                $fields[] = new Hidden([$key], (string) $value);
            }
            $fields[] = App::nonce()->formNonce();
        }

        return $fields;
    }

    /**
     * Get rendered form hidden fields.
     *
     * @param   array<string,string|int>    $params     The additionnal parameters
     */
    public static function parsedHiddenFields(array $params = []): string
    {
        $res = '';
        foreach (self::hiddenFields($params) as $field) {
            $res .= $field->render();
        }

        return $res;
    }

    /**
     * Get module backend redirection.
     *
     * @param   array<string,string|int>    $params     The URL parameters
     * @param   string                      $suffix     The URL suffix (#)
     */
    public static function redirect(array $params = [], string $suffix = ''): void
    {
        if (App::task()->checkContext('BACKEND')) {
            App::backend()->url()->redirect('admin.plugin.' . static::id(), $params, $suffix);
        }
    }
}
