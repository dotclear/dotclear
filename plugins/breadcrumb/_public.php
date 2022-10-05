<?php
/**
 * @brief breadcrumb, a plugin for Dotclear 2
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

# Breadcrumb template functions
dcCore::app()->tpl->addValue('Breadcrumb', [tplBreadcrumb::class, 'breadcrumb']);
