<?php
/**
 * @brief buildtools, a plugin for Dotclear 2
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

class dcBuildTools
{
    /**
     * Add l10n faker maintenance task
     *
     * @param      dcMaintenance  $maintenance  The maintenance object
     */
    public static function maintenanceAdmin(dcMaintenance $maintenance)
    {
        $maintenance->addTask(dcMaintenanceBuildtools::class);
    }
}
