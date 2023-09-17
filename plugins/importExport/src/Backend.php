<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
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
        __('Import / Export') . __('Import and Export your blog');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        My::addBackendMenuItem();

        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_ADMIN,
                    ]),
                ]);
            },
            'importExportModulesV2' => BackendBehaviors::registerIeModules(...),
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
