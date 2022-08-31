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
    'dcPagesActions'       => __DIR__ . '/class.actionpage.php',
    'dcDefaultPageActions' => __DIR__ . '/class.actionpage.php',
    'adminPagesList'       => __DIR__ . '/class.listpage.php',
]);

dcCore::app()->url->register('pages', 'pages', '^pages/(.+)$', ['urlPages', 'pages']);
dcCore::app()->url->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', ['urlPages', 'pagespreview']);

dcCore::app()->setPostType('page', 'plugin.php?p=pages&act=page&id=%d', dcCore::app()->url->getURLFor('pages', '%s'), 'Pages');
