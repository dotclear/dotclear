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

if (!defined('DC_RC_PATH')) {return;}

$__autoload['dcPagesActionsPage'] = dirname(__FILE__) . '/class.actionpage.php';
$__autoload['adminPagesList']     = dirname(__FILE__) . '/class.listpage.php';

$core->url->register('pages', 'pages', '^pages/(.+)$', array('urlPages', 'pages'));
$core->url->register('pagespreview', 'pagespreview', '^pagespreview/(.+)$', array('urlPages', 'pagespreview'));

$core->setPostType('page', 'plugin.php?p=pages&act=page&id=%d', $core->url->getURLFor('pages', '%s'), 'Pages');

# We should put this as settings later
$GLOBALS['page_url_format'] = '{t}';
