<?php
/**
 * @brief Widgets, a plugin for Dotclear 2
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
    'dcWidgets'      => __DIR__ . '/inc/widgets.php',
    'defaultWidgets' => __DIR__ . '/inc/default.widgets.php',
    'publicWidgets'  => __DIR__ . '/inc/public.tpl.php',
]);
