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
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Maintenance') . __('Maintain your installation');

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
        Core::behavior()->addBehaviors([
            'dcMaintenanceInit'                => BackendBehaviors::dcMaintenanceInit(...),
            'adminDashboardFavoritesV2'        => BackendBehaviors::adminDashboardFavorites(...),
            'adminDashboardHeaders'            => BackendBehaviors::adminDashboardHeaders(...),
            'adminDashboardContentsV2'         => BackendBehaviors::adminDashboardItems(...),
            'adminDashboardOptionsFormV2'      => BackendBehaviors::adminDashboardOptionsForm(...),
            'adminAfterDashboardOptionsUpdate' => BackendBehaviors::adminAfterDashboardOptionsUpdate(...),
            'adminPageHelpBlock'               => BackendBehaviors::adminPageHelpBlock(...),
        ]);

        // Rest method
        Core::rest()->addFunction('dcMaintenanceStep', Rest::step(...));
        Core::rest()->addFunction('dcMaintenanceTaskExpired', Rest::countExpired(...));

        return true;
    }
}
