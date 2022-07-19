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

$__autoload['dcMaintenance']           = __DIR__ . '/inc/class.dc.maintenance.php';
$__autoload['dcMaintenanceDescriptor'] = __DIR__ . '/inc/class.dc.maintenance.descriptor.php';
$__autoload['dcMaintenanceTask']       = __DIR__ . '/inc/class.dc.maintenance.task.php';
$__autoload['dcMaintenanceRest']       = __DIR__ . '/_services.php';

$__autoload['dcMaintenanceCache']          = __DIR__ . '/inc/tasks/class.dc.maintenance.cache.php';
$__autoload['dcMaintenanceCSP']            = __DIR__ . '/inc/tasks/class.dc.maintenance.csp.php';
$__autoload['dcMaintenanceCountcomments']  = __DIR__ . '/inc/tasks/class.dc.maintenance.countcomments.php';
$__autoload['dcMaintenanceIndexcomments']  = __DIR__ . '/inc/tasks/class.dc.maintenance.indexcomments.php';
$__autoload['dcMaintenanceIndexposts']     = __DIR__ . '/inc/tasks/class.dc.maintenance.indexposts.php';
$__autoload['dcMaintenanceSynchpostsmeta'] = __DIR__ . '/inc/tasks/class.dc.maintenance.synchpostsmeta.php';
$__autoload['dcMaintenanceLogs']           = __DIR__ . '/inc/tasks/class.dc.maintenance.logs.php';
$__autoload['dcMaintenanceVacuum']         = __DIR__ . '/inc/tasks/class.dc.maintenance.vacuum.php';
$__autoload['dcMaintenanceZipmedia']       = __DIR__ . '/inc/tasks/class.dc.maintenance.zipmedia.php';
$__autoload['dcMaintenanceZiptheme']       = __DIR__ . '/inc/tasks/class.dc.maintenance.ziptheme.php';

dcCore::app()->rest->addFunction('dcMaintenanceStep', ['dcMaintenanceRest', 'step']);
