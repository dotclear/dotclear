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

$__autoload['dcPagesActionsPage'] = __DIR__ . '/class.actionpage.php';
$__autoload['adminPagesList']     = __DIR__ . '/class.listpage.php';

dcCore::app()->url->register('pages', 'pages', '^pages/(.+)$', ['urlPages', 'pages']);
dcCore::app()->url->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', ['urlPages', 'pagespreview']);

dcCore::app()->setPostType('page', 'plugin.php?p=pages&act=page&id=%d', dcCore::app()->url->getURLFor('pages', '%s'), 'Pages');

# We should put this as settings later
$GLOBALS['page_url_format'] = '{t}';
