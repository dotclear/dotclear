<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup maintenance
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Maintenance');
        __('Maintain your installation');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Sidebar menu
        My::addBackendMenuItem();

        // Admin behaviors
        App::behavior()->addBehaviors([
            'dcMaintenanceInit'                => BackendBehaviors::dcMaintenanceInit(...),
            'adminDashboardFavoritesV2'        => BackendBehaviors::adminDashboardFavorites(...),
            'adminDashboardHeaders'            => BackendBehaviors::adminDashboardHeaders(...),
            'adminDashboardContentsV2'         => BackendBehaviors::adminDashboardItems(...),
            'adminDashboardOptionsFormV2'      => BackendBehaviors::adminDashboardOptionsForm(...),
            'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
            'adminPageHelpBlock'               => BackendBehaviors::adminPageHelpBlock(...),
        ]);

        // Rest method
        App::rest()->addFunction('dcMaintenanceStep', Rest::step(...));
        App::rest()->addFunction('dcMaintenanceTaskExpired', Rest::countExpired(...));

        return true;
    }
}
