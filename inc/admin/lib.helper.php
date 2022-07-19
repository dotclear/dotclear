<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcAdminHelper
{
    /** @var dcCore dcCore instance */
    /**
     * @deprecated since 2.23
     */
    public static $core;

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
    public static function adminIcon($img, $fallback = true, $alt = '', $title = '', $class = '')
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
     * Loads locales.
     *
     * @param      string  $_lang  The language
     */
    public static function loadLocales(&$_lang)
    {
        $_lang = dcCore::app()->auth->getInfo('user_lang');
        $_lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $_lang) ? $_lang : 'en';

        l10n::lang($_lang);
        if (l10n::set(__DIR__ . '/../../locales/' . $_lang . '/date') === false && $_lang != 'en') {
            l10n::set(__DIR__ . '/../../locales/en/date');
        }
        l10n::set(__DIR__ . '/../../locales/' . $_lang . '/main');
        l10n::set(__DIR__ . '/../../locales/' . $_lang . '/public');
        l10n::set(__DIR__ . '/../../locales/' . $_lang . '/plugins');

        // Set lexical lang
        dcUtils::setlexicalLang('admin', $_lang);
    }

    /**
     * Adds a menu item.
     *
     * @param      string  $section   The section
     * @param      string  $desc      The description
     * @param      string  $adminurl  The adminurl
     * @param      mixed   $icon      The icon(s)
     * @param      mixed   $perm      The permission
     * @param      bool    $pinned    The pinned
     * @param      bool    $strict    The strict
     */
    public static function addMenuItem($section, $desc, $adminurl, $icon, $perm, $pinned = false, $strict = false)
    {
        global $_menu;

        $url     = dcCore::app()->adminurl->get($adminurl);
        $pattern = '@' . preg_quote($url) . ($strict ? '' : '(\?.*)?') . '$@';
        $_menu[$section]->prependItem(
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

/*
 * Store current dcCore instance
 */
dcAdminHelper::$core = dcCore::app();

/**
 * Load locales
 *
 * @deprecated     since 2.21  use dcAdminHelper::loadLocales()
 */
function dc_load_locales()
{
    global $_lang;

    dcAdminHelper::loadLocales($_lang);
}

/**
 * Get icon URL
 *
 * @param      string  $img    The image
 *
 * @deprecated  since 2.21
 *
 * @return     string
 */
function dc_admin_icon_url($img)
{
    return $img;
}

/**
 * Compose HTML icon markup for favorites, menu, … depending on theme (light, dark)
 *
 * @param mixed     $img        string (default) or array (0 : light, 1 : dark)
 * @param bool      $fallback   use fallback image if none given
 * @param string    $alt        alt attribute
 * @param string    $title      title attribute
 *
 * @deprecated  since 2.21  use dcAdminHelper::adminIcon()
 *
 * @return string
 */
function dc_admin_icon_theme($img, $fallback = true, $alt = '', $title = '', $class = '')
{
    return dcAdminHelper::adminIcon($img, $fallback, $alt, $title, $class);
}

/**
 * Adds a menu item.
 *
 * @param      string  $section   The section
 * @param      string  $desc      The description
 * @param      string  $adminurl  The adminurl
 * @param      mixed   $icon      The icon(s)
 * @param      mixed   $perm      The permission
 * @param      bool    $pinned    The pinned
 * @param      bool    $strict    The strict
 *
 * @deprecated  since 2.21  use dcAdminHelper::addMenuItem()
 */
function addMenuItem($section, $desc, $adminurl, $icon, $perm, $pinned = false, $strict = false)
{
    dcAdminHelper::addMenuItem($section, $desc, $adminurl, $icon, $perm, $pinned, $strict);
}
