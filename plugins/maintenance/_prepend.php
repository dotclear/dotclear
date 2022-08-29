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
if (!defined('DC_RC_PATH')) {
    return;
}

Clearbricks::lib()->autoload([
    'dcMaintenance'           => __DIR__ . '/inc/class.dc.maintenance.php',
    'dcMaintenanceDescriptor' => __DIR__ . '/inc/class.dc.maintenance.descriptor.php',
    'dcMaintenanceTask'       => __DIR__ . '/inc/class.dc.maintenance.task.php',
    'dcMaintenanceRest'       => __DIR__ . '/_services.php',

    'dcMaintenanceCache'          => __DIR__ . '/inc/tasks/class.dc.maintenance.cache.php',
    'dcMaintenanceCSP'            => __DIR__ . '/inc/tasks/class.dc.maintenance.csp.php',
    'dcMaintenanceCountcomments'  => __DIR__ . '/inc/tasks/class.dc.maintenance.countcomments.php',
    'dcMaintenanceIndexcomments'  => __DIR__ . '/inc/tasks/class.dc.maintenance.indexcomments.php',
    'dcMaintenanceIndexposts'     => __DIR__ . '/inc/tasks/class.dc.maintenance.indexposts.php',
    'dcMaintenanceSynchpostsmeta' => __DIR__ . '/inc/tasks/class.dc.maintenance.synchpostsmeta.php',
    'dcMaintenanceLogs'           => __DIR__ . '/inc/tasks/class.dc.maintenance.logs.php',
    'dcMaintenanceVacuum'         => __DIR__ . '/inc/tasks/class.dc.maintenance.vacuum.php',
    'dcMaintenanceZipmedia'       => __DIR__ . '/inc/tasks/class.dc.maintenance.zipmedia.php',
    'dcMaintenanceZiptheme'       => __DIR__ . '/inc/tasks/class.dc.maintenance.ziptheme.php',
]);

dcCore::app()->rest->addFunction('dcMaintenanceStep', ['dcMaintenanceRest', 'step']);
