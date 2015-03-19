<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

$__autoload['dcMaintenance'] = dirname(__FILE__).'/inc/class.dc.maintenance.php';
$__autoload['dcMaintenanceDescriptor'] = dirname(__FILE__).'/inc/class.dc.maintenance.descriptor.php';
$__autoload['dcMaintenanceTask'] = dirname(__FILE__).'/inc/class.dc.maintenance.task.php';
$__autoload['dcMaintenanceRest'] = dirname(__FILE__).'/_services.php';


$__autoload['dcMaintenanceCache'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.cache.php';
$__autoload['dcMaintenanceCountcomments'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.countcomments.php';
$__autoload['dcMaintenanceIndexcomments'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.indexcomments.php';
$__autoload['dcMaintenanceIndexposts'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.indexposts.php';
$__autoload['dcMaintenanceSynchpostsmeta'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.synchpostsmeta.php';
$__autoload['dcMaintenanceLogs'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.logs.php';
$__autoload['dcMaintenanceVacuum'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.vacuum.php';
$__autoload['dcMaintenanceZipmedia'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.zipmedia.php';
$__autoload['dcMaintenanceZiptheme'] = dirname(__FILE__).'/inc/tasks/class.dc.maintenance.ziptheme.php';

$this->core->rest->addFunction('dcMaintenanceStep', array('dcMaintenanceRest', 'step'));
