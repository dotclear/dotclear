<?php
/**
 * @brief pages, a plugin for Dotclear 2
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
    'dcPagesActions'       => __DIR__ . '/inc/pages.actions.php',
    'dcDefaultPageActions' => __DIR__ . '/inc/pages.actions.php',
    'adminPagesList'       => __DIR__ . '/inc/admin.pages.list.php',
    'pagesDashboard'       => __DIR__ . '/inc/admin.behaviors.php',
    'urlPages'             => __DIR__ . '/inc/public.url.php',
    'tplPages'             => __DIR__ . '/inc/public.tpl.php',
    'publicPages'          => __DIR__ . '/inc/public.behaviors.php',
    'pagesWidgets'         => __DIR__ . '/inc/widgets.php',
]);

dcCore::app()->url->register('pages', 'pages', '^pages/(.+)$', [urlPages::class, 'pages']);
dcCore::app()->url->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', [urlPages::class, 'pagespreview']);

dcCore::app()->setPostType('page', 'plugin.php?p=pages&act=page&id=%d', dcCore::app()->url->getURLFor('pages', '%s'), 'Pages');
