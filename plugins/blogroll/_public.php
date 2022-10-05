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

require __DIR__ . '/_widgets.php';

// Blogroll template functions
dcCore::app()->tpl->addValue('Blogroll', [tplBlogroll::class, 'blogroll']);
dcCore::app()->tpl->addValue('BlogrollXbelLink', [tplBlogroll::class, 'blogrollXbelLink']);

dcCore::app()->url->register('xbel', 'xbel', '^xbel(?:\/?)$', [urlBlogroll::class, 'xbel']);
