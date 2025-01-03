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
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\L10n;

class Helper
{
    /**
     * Compose HTML icon markup for favorites, menu, … depending on theme (light, dark)
     *
     * @param mixed     $img        string (default) or array (0 : light, 1 : dark)
     * @param bool      $fallback   use fallback image if none given
     * @param string    $alt        alt attribute
     * @param string    $title      title attribute
     * @param string    $class      class attribute
     */
    public static function adminIcon($img, bool $fallback = true, string $alt = '', string $title = '', string $class = ''): string
    {
        $default_img = $fallback ? 'images/menu/no-icon.svg' : '';  // Fallback to no icon if requested

        $dark_img = '';
        if (is_array($img)) {
            $light_img = $img[0] ?: $default_img;
            if (isset($img[1]) && $img[1] !== '') {
                $dark_img = $img[1];
            }
        } else {
            $light_img = $img ?: $default_img;
        }

        if ($title === $alt) {
            // Don't repeat alt in title
            $title = '';
        }

        $icons = [];
        if ($light_img !== '') {
            $icons[0] = (new Img($light_img))
                ->class(array_filter([$dark_img !== '' ? 'light-only' : '', $class]))
                ->alt($alt);
            if ($title !== '') {
                $icons[0]->title($title);
            }
            if ($dark_img !== '') {
                $icons[1] = (new Img($dark_img))
                    ->class(array_filter(['dark-only', $class]))
                    ->alt($alt);
                if ($title !== '') {
                    $icons[1]->title($title);
                }
            }
        }

        return (new Set())
            ->items($icons)
        ->render();
    }

    /**
     * Loads user locales (English if not defined).
     */
    public static function loadLocales(): void
    {
        App::lang()->setLang((string) App::auth()->getInfo('user_lang'));

        if (L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/date') === false && App::lang()->getLang() !== 'en') {
            L10n::set(App::config()->l10nRoot() . '/en/date');
        }
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/main');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/public');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/plugins');

        // Set lexical lang
        App::lexical()->setLexicalLang('admin', App::lang()->getLang());
    }

    /**
     * Adds a menu item.
     *
     * @deprecated since 2.27, use App::backend()->menus()->addItem() instead
     *
     * @param      string  $section   The section
     * @param      string  $desc      The item description
     * @param      string  $adminurl  The URL scheme
     * @param      mixed   $icon      The icon(s)
     * @param      mixed   $perm      The permission(s)
     * @param      bool    $pinned    Is pinned at begining
     * @param      bool    $strict    Strict URL scheme or allow query string parameters
     * @param      string  $id        The menu item id
     */
    public static function addMenuItem(string $section, string $desc, string $adminurl, $icon, $perm, bool $pinned = false, bool $strict = false, ?string $id = null): void
    {
        App::backend()->menus()->addItem($section, $desc, $adminurl, $icon, $perm, $pinned, $strict, $id);
    }
}
