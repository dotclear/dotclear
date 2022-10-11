<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcAdminHelper
{
    /**
     * Compose HTML icon markup for favorites, menu, … depending on theme (light, dark)
     *
     * @param mixed     $img        string (default) or array (0 : light, 1 : dark)
     * @param bool      $fallback   use fallback image if none given
     * @param string    $alt        alt attribute
     * @param string    $title      title attribute
     *
     * @return string
     */
    public static function adminIcon($img, bool $fallback = true, string $alt = '', string $title = '', string $class = ''): string
    {
        $unknown_img = 'images/menu/no-icon.svg';
        $dark_img    = '';
        if (is_array($img)) {
            $light_img = $img[0] ?: ($fallback ? $unknown_img : '');   // Fallback to no icon if necessary
            if (isset($img[1]) && $img[1] !== '') {
                $dark_img = $img[1];
            }
        } else {
            $light_img = $img ?: ($fallback ? $unknown_img : '');  // Fallback to no icon if necessary
        }

        $title = $title !== '' ? ' title="' . $title . '"' : '';
        if ($light_img !== '' && $dark_img !== '') {
            $icon = '<img src="' . $light_img .
            '" class="light-only' . ($class !== '' ? ' ' . $class : '') . '" alt="' . $alt . '"' . $title . ' />' .
                '<img src="' . $dark_img .
            '" class="dark-only' . ($class !== '' ? ' ' . $class : '') . '" alt="' . $alt . '"' . $title . ' />';
        } elseif ($light_img !== '') {
            $icon = '<img src="' . $light_img .
            '" class="' . ($class !== '' ? $class : '') . '" alt="' . $alt . '"' . $title . ' />';
        } else {
            $icon = '';
        }

        return $icon;
    }

    /**
     * Loads user locales (English if not defined).
     */
    public static function loadLocales()
    {
        dcCore::app()->lang = dcCore::app()->auth->getInfo('user_lang');
        dcCore::app()->lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', dcCore::app()->lang) ? dcCore::app()->lang : 'en';

        l10n::lang(dcCore::app()->lang);
        if (l10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/date') === false && dcCore::app()->lang != 'en') {
            l10n::set(DC_L10N_ROOT . '/en/date');
        }
        l10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/main');
        l10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/public');
        l10n::set(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/plugins');

        // Set lexical lang
        dcUtils::setlexicalLang('admin', dcCore::app()->lang);
    }

    /**
     * Adds a menu item.
     *
     * @param      string  $section   The section
     * @param      string  $desc      The item description
     * @param      string  $adminurl  The URL scheme
     * @param      mixed   $icon      The icon(s)
     * @param      mixed   $perm      The permission(s)
     * @param      bool    $pinned    Is pinned at begining
     * @param      bool    $strict    Strict URL scheme or allow query string parameters
     */
    public static function addMenuItem(string $section, string $desc, string $adminurl, $icon, $perm, bool $pinned = false, bool $strict = false)
    {
        $url     = dcCore::app()->adminurl->get($adminurl);
        $pattern = '@' . preg_quote($url) . ($strict ? '' : '(\?.*)?') . '$@';
        dcCore::app()->menu[$section]->prependItem(
            $desc,
            $url,
            $icon,
            preg_match($pattern, $_SERVER['REQUEST_URI']),
            $perm,
            null,
            null,
            $pinned
        );
    }
}
