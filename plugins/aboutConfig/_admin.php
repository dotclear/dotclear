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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$_menu['System']->addItem('about:config',
    $core->adminurl->get('admin.plugin.aboutConfig'),
    dcPage::getPF('aboutConfig/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.aboutConfig')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->isSuperAdmin());
