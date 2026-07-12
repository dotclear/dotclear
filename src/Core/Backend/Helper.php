<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\App;

class Helper
{
    /**
     * Compose HTML icon markup for favorites, menu, … depending on theme (light, dark)
     *
     * @param null|string|string[]  $img        string (default) or array (0 : light, 1 : dark)
     * @param bool                  $fallback   use fallback image if none given
     * @param string                $alt        alt attribute
     * @param string                $title      title attribute
     * @param string                $class      class attribute
     */
    public static function adminIcon(null|string|array $img, bool $fallback = true, string $alt = '', string $title = '', string $class = ''): string
    {
        $default_img = $fallback ? 'images/menu/no-icon.svg' : '';  // Fallback to no icon if requested

        $dark_img = '';
        if (is_array($img)) {
            $light_img = $img[0] ?: '';
            if (isset($img[1]) && $img[1] !== '') {
                $dark_img = $img[1];
            }
        } else {
            $light_img = $img ?: '';
        }

        return (new Icon($light_img, $dark_img, $alt, $title, $class))
            ->getComponent($default_img)->render();
    }

    /**
     * Loads user locales (English if not defined).
     */
    public static function loadLocales(): void
    {
        $user_lang = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : 'en';
        App::lang()->setLang($user_lang);

        if (App::lang()->set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/date') === false && App::lang()->getLang() !== 'en') {
            App::lang()->set(App::config()->l10nRoot() . '/en/date');
        }

        App::lang()->set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/main');
        App::lang()->set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/public');
        App::lang()->set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/plugins');

        // Set lexical lang
        App::lexical()->setLexicalLang('admin', App::lang()->getLang());
    }

    /**
     * Adds a menu item.
     *
     * @deprecated since 2.27, use App::backend()->menus()->addItem() instead
     *
     * @param      string                               $section   The section
     * @param      string                               $desc      The item description
     * @param      string                               $adminurl  The URL scheme
     * @param      string|array{0?: string, 1?: string} $icon      The icon(s)
     * @param      bool                                 $perm      The permission(s)
     * @param      bool                                 $pinned    Is pinned at begining
     * @param      bool                                 $strict    Strict URL scheme or allow query string parameters
     * @param      string                               $id        The menu item id
     */
    public static function addMenuItem(string $section, string $desc, string $adminurl, string|array $icon, bool $perm, bool $pinned = false, bool $strict = false, ?string $id = null): void
    {
        App::backend()->menus()->addItem($section, $desc, $adminurl, $icon, $perm, $pinned, $strict, $id);
    }
}
