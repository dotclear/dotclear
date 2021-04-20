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
$core->addBehavior('dcMaintenanceInit', ['dcBuildTools', 'maintenanceAdmin']);

class dcBuildTools
{
    public static function maintenanceAdmin($maintenance)
    {
        $maintenance->addTask('dcMaintenanceBuildtools');
    }
}
