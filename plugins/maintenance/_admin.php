<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
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

// Sidebar menu
dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
    __('Maintenance'),
    dcCore::app()->adminurl->get('admin.plugin.maintenance'),
    dcPage::getPF('maintenance/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.maintenance')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_ADMIN,
    ]), dcCore::app()->blog->id)
);

// Admin behaviors
dcCore::app()->addBehavior('dcMaintenanceInit', [dcMaintenanceAdmin::class, 'dcMaintenanceInit']);
dcCore::app()->addBehavior('adminDashboardFavoritesV2', [dcMaintenanceAdmin::class, 'adminDashboardFavorites']);
dcCore::app()->addBehavior('adminDashboardContentsV2', [dcMaintenanceAdmin::class, 'adminDashboardItems']);
dcCore::app()->addBehavior('adminDashboardOptionsFormV2', [dcMaintenanceAdmin::class, 'adminDashboardOptionsForm']);
dcCore::app()->addBehavior('adminAfterDashboardOptionsUpdate', [dcMaintenanceAdmin::class, 'adminAfterDashboardOptionsUpdate']);
dcCore::app()->addBehavior('adminPageHelpBlock', [dcMaintenanceAdmin::class, 'adminPageHelpBlock']);
dcCore::app()->addBehavior('pluginsToolsHeadersV2', [dcMaintenanceAdmin::class, 'pluginsToolsHeaders']);
