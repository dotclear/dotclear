<?php
/**
 * @brief aboutConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->menu['System']->addItem(
    'about:config',
    dcCore::app()->adminurl->get('admin.plugin.aboutConfig'),
    dcPage::getPF('aboutConfig/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.aboutConfig')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);
