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
            'dcMaintenanceInit'                => [BackendBehaviors::class, 'dcMaintenanceInit'],
            'adminDashboardFavoritesV2'        => [BackendBehaviors::class, 'adminDashboardFavorites'],
            'adminDashboardHeaders'            => [BackendBehaviors::class, 'adminDashboardHeaders'],
            'adminDashboardContentsV2'         => [BackendBehaviors::class, 'adminDashboardItems'],
            'adminDashboardOptionsFormV2'      => [BackendBehaviors::class, 'adminDashboardOptionsForm'],
            'adminAfterDashboardOptionsUpdate' => [BackendBehaviors::class, 'adminAfterDashboardOptionsUpdate'],
            'adminPageHelpBlock'               => [BackendBehaviors::class, 'adminPageHelpBlock'],
        ]);

        // Rest method
        Core::rest()->addFunction('dcMaintenanceStep', [Rest::class, 'step']);
        Core::rest()->addFunction('dcMaintenanceTaskExpired', [Rest::class, 'countExpired']);

        return true;
    }
}
