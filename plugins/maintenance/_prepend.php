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
    'dcMaintenance'               => __DIR__ . '/inc/maintenance.php',
    'dcMaintenanceDescriptor'     => __DIR__ . '/inc/maintenance.descriptor.php',
    'dcMaintenanceTask'           => __DIR__ . '/inc/maintenance.task.php',
    'dcMaintenanceAdmin'          => __DIR__ . '/inc/admin.behaviors.php',
    'dcMaintenanceRest'           => __DIR__ . '/_services.php',
    'dcMaintenanceCache'          => __DIR__ . '/inc/tasks/maintenance.cache.php',
    'dcMaintenanceCSP'            => __DIR__ . '/inc/tasks/maintenance.csp.php',
    'dcMaintenanceCountcomments'  => __DIR__ . '/inc/tasks/maintenance.countcomments.php',
    'dcMaintenanceIndexcomments'  => __DIR__ . '/inc/tasks/maintenance.indexcomments.php',
    'dcMaintenanceIndexposts'     => __DIR__ . '/inc/tasks/maintenance.indexposts.php',
    'dcMaintenanceLogs'           => __DIR__ . '/inc/tasks/maintenance.logs.php',
    'dcMaintenanceSynchpostsmeta' => __DIR__ . '/inc/tasks/maintenance.synchpostsmeta.php',
    'dcMaintenanceVacuum'         => __DIR__ . '/inc/tasks/maintenance.vacuum.php',
    'dcMaintenanceZipmedia'       => __DIR__ . '/inc/tasks/maintenance.zipmedia.php',
    'dcMaintenanceZiptheme'       => __DIR__ . '/inc/tasks/maintenance.ziptheme.php',
]);

dcCore::app()->rest->addFunction('dcMaintenanceStep', [dcMaintenanceRest::class, 'step']);
