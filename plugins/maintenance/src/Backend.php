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

use dcCore;
use dcNsProcess;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // Sidebar menu
        My::backendSidebarMenuIcon();

        // Admin behaviors
        dcCore::app()->addBehaviors([
            'dcMaintenanceInit'                => [BackendBehaviors::class, 'dcMaintenanceInit'],
            'adminDashboardFavoritesV2'        => [BackendBehaviors::class, 'adminDashboardFavorites'],
            'adminDashboardContentsV2'         => [BackendBehaviors::class, 'adminDashboardItems'],
            'adminDashboardOptionsFormV2'      => [BackendBehaviors::class, 'adminDashboardOptionsForm'],
            'adminAfterDashboardOptionsUpdate' => [BackendBehaviors::class, 'adminAfterDashboardOptionsUpdate'],
            'adminPageHelpBlock'               => [BackendBehaviors::class, 'adminPageHelpBlock'],
        ]);

        // Rest method
        dcCore::app()->rest->addFunction('dcMaintenanceStep', [Rest::class, 'step']);

        return true;
    }
}
