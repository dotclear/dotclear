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

dcCore::app()->menu['Plugins']->addItem(
    __('Import/Export'),
    dcCore::app()->adminurl->get('admin.plugin.importExport'),
    [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.importExport')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('admin', dcCore::app()->blog->id)
);

dcCore::app()->addBehavior(
    'adminDashboardFavorites',
    function (dcCore $core, $favs) {
        $favs->register('importExport', [
            'title'       => __('Import/Export'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.importExport'),
            'small-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
            'permissions' => 'admin',
        ]);
    }
);

dcCore::app()->addBehavior(
    'dcMaintenanceInit',
    function ($maintenance) {
        $maintenance
            ->addTask('ieMaintenanceExportblog')
            ->addTask('ieMaintenanceExportfull')
        ;
    }
);
