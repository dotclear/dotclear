<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

Clearbricks::lib()->autoload([
    'dcBlogroll'       => __DIR__ . '/inc/blogroll.php',
    'dcImportBlogroll' => __DIR__ . '/inc/importblogroll.php',
    'blogrollWidgets'  => __DIR__ . '/inc/widgets.php',
    'tplBlogroll'      => __DIR__ . '/inc/public.tpl.php',
    'urlBlogroll'      => __DIR__ . '/inc/public.url.php',
]);
