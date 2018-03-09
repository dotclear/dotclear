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

if (!defined('DC_RC_PATH')) {return;}

$__autoload['dcMaintenance']           = dirname(__FILE__) . '/inc/class.dc.maintenance.php';
$__autoload['dcMaintenanceDescriptor'] = dirname(__FILE__) . '/inc/class.dc.maintenance.descriptor.php';
$__autoload['dcMaintenanceTask']       = dirname(__FILE__) . '/inc/class.dc.maintenance.task.php';
$__autoload['dcMaintenanceRest']       = dirname(__FILE__) . '/_services.php';

$__autoload['dcMaintenanceCache']          = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.cache.php';
$__autoload['dcMaintenanceCSP']            = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.csp.php';
$__autoload['dcMaintenanceCountcomments']  = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.countcomments.php';
$__autoload['dcMaintenanceIndexcomments']  = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.indexcomments.php';
$__autoload['dcMaintenanceIndexposts']     = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.indexposts.php';
$__autoload['dcMaintenanceSynchpostsmeta'] = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.synchpostsmeta.php';
$__autoload['dcMaintenanceLogs']           = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.logs.php';
$__autoload['dcMaintenanceVacuum']         = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.vacuum.php';
$__autoload['dcMaintenanceZipmedia']       = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.zipmedia.php';
$__autoload['dcMaintenanceZiptheme']       = dirname(__FILE__) . '/inc/tasks/class.dc.maintenance.ziptheme.php';

$this->core->rest->addFunction('dcMaintenanceStep', array('dcMaintenanceRest', 'step'));
