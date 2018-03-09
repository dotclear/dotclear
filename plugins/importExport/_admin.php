<?php
/**
 * @brief importExport, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$_menu['Plugins']->addItem(
    __('Import/Export'),
    $core->adminurl->get('admin.plugin.importExport'),
    dcPage::getPF('importExport/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.importExport')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id)
);

$core->addBehavior('adminDashboardFavorites', 'importExportDashboardFavorites');

function importExportDashboardFavorites($core, $favs)
{
    $favs->register('importExport', array(
        'title'       => __('Import/Export'),
        'url'         => $core->adminurl->get('admin.plugin.importExport'),
        'small-icon'  => dcPage::getPF('importExport/icon.png'),
        'large-icon'  => dcPage::getPF('importExport/icon-big.png'),
        'permissions' => 'admin'
    ));
}

$core->addBehavior('dcMaintenanceInit', 'ieMaintenanceInit');

function ieMaintenanceInit($maintenance)
{
    $maintenance
        ->addTask('ieMaintenanceExportblog')
        ->addTask('ieMaintenanceExportfull')
    ;
}
