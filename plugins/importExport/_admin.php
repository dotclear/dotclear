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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Import/Export'),
    dcCore::app()->adminurl->get('admin.plugin.importExport'),
    [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.importExport')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
    ]), dcCore::app()->blog->id)
);

dcCore::app()->addBehavior(
    'adminDashboardFavoritesV2',
    function (dcFavorites $favs) {
        $favs->register('importExport', [
            'title'       => __('Import/Export'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.importExport'),
            'small-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]),
        ]);
    }
);

dcCore::app()->addBehavior(
    'importExportModulesV2',
    [importExportBehaviors::class, 'registerIeModules']
);

dcCore::app()->addBehavior(
    'dcMaintenanceInit',
    function (dcMaintenance $maintenance) {
        $maintenance
            ->addTask('ieMaintenanceExportblog')
            ->addTask('ieMaintenanceExportfull')
        ;
    }
);
