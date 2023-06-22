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

use dcCore;
use dcFavorites;
use dcNsProcess;
use Dotclear\Plugin\maintenance\Maintenance;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Import / Export') . __('Import and Export your blog');

        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        My::addBackendMenuItem();

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
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
