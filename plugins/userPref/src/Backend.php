<?php
/**
 * @brief userPref, a plugin for Dotclear 2
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

dcCore::app()->menu[dcAdmin::MENU_SYSTEM]->addItem(
    'user:preferences',
    dcCore::app()->adminurl->get('admin.plugin.userPref'),
    dcPage::getPF('userPref/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.userPref')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);
