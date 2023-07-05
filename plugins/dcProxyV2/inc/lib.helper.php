<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Core\Backend\Helper;

/**
 * Load locales
 *
 * @deprecated     since 2.21  use \Dotclear\Core\Backend\Helper::loadLocales()
 */
function dc_load_locales()
{
    Helper::loadLocales();
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
function dc_admin_icon_url(string $img): string
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
 * @deprecated  since 2.21  use \Dotclear\Core\Backend\Helper::adminIcon()
 *
 * @return string
 */
function dc_admin_icon_theme($img, bool $fallback = true, string $alt = '', string $title = '', string $class = ''): string
{
    return Helper::adminIcon($img, $fallback, $alt, $title, $class);
}

/**
 * Adds a menu item.
 *
 * @deprecated  since 2.21  use dcCore::app()->admin->menu->addItem() instead
 *
 * @param      string  $section   The section
 * @param      string  $desc      The description
 * @param      string  $adminurl  The adminurl
 * @param      mixed   $icon      The icon(s)
 * @param      mixed   $perm      The permission
 * @param      bool    $pinned    The pinned
 * @param      bool    $strict    The strict
 */
function addMenuItem(string $section, string $desc, string $adminurl, $icon, $perm, bool $pinned = false, bool $strict = false): void
{
    dcCore::app()->admin->menu->addItem($section, $desc, $adminurl, $icon, $perm, $pinned, $strict);
}
