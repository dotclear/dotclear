<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\importExport;

use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Process;
use Dotclear\Plugin\maintenance\Maintenance;

/**
 * @brief   The module backend process.
 * @ingroup importExport
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Import / Export');
        __('Import and Export your blog');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]),
                ]);

                return '';
            },
            'importExportModulesV2' => BackendBehaviors::registerIeModules(...),
            'dcMaintenanceInit'     => function (Maintenance $maintenance): string {
                $maintenance
                    ->addTask(ExportBlogMaintenanceTask::class)
                    ->addTask(ExportFullMaintenanceTask::class)
                ;

                return '';
            },
        ]);

        return true;
    }
}
