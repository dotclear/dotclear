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
dcCore::app()->addBehaviors([
    'dcMaintenanceInit'                => [dcMaintenanceAdmin::class, 'dcMaintenanceInit'],
    'adminDashboardFavoritesV2'        => [dcMaintenanceAdmin::class, 'adminDashboardFavorites'],
    'adminDashboardContentsV2'         => [dcMaintenanceAdmin::class, 'adminDashboardItems'],
    'adminDashboardOptionsFormV2'      => [dcMaintenanceAdmin::class, 'adminDashboardOptionsForm'],
    'adminAfterDashboardOptionsUpdate' => [dcMaintenanceAdmin::class, 'adminAfterDashboardOptionsUpdate'],
    'adminPageHelpBlock'               => [dcMaintenanceAdmin::class, 'adminPageHelpBlock'],
    'pluginsToolsHeadersV2'            => [dcMaintenanceAdmin::class, 'pluginsToolsHeaders'],
]);
