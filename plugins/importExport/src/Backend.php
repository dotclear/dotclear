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
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use dcAdmin;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;
use Dotclear\Plugin\maintenance\Maintenance;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('Import/Export'),
            dcCore::app()->adminurl->get('admin.plugin.importExport'),
            [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.importExport')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('importExport', [
                    'title'       => __('Import/Export'),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.importExport'),
                    'small-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
                    'large-icon'  => [dcPage::getPF('importExport/icon.svg'), dcPage::getPF('importExport/icon-dark.svg')],
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            'importExportModulesV2' => [BackendBehaviors::class, 'registerIeModules'],
            'dcMaintenanceInit'     => function (Maintenance $maintenance) {
                $maintenance
                    ->addTask(ExportBlogMaintenanceTask::class)
                    ->addTask(ExportFullMaintenanceTask::class)
                ;
            },
        ]);

        return true;
    }
}
