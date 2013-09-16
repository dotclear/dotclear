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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

// Sidebar menu
$_menu['Plugins']->addItem(
	__('Maintenance'),
	'plugin.php?p=maintenance',
	'index.php?pf=maintenance/icon.png',
	preg_match('/plugin.php\?p=maintenance(&.*)?$/', $_SERVER['REQUEST_URI']),
	$core->auth->isSuperAdmin()
);

// Admin behaviors
$core->addBehavior('dcMaintenanceRegister', array('dcMaintenanceAdmin', 'register'));
$core->addBehavior('adminDashboardFavs', array('dcMaintenanceAdmin', 'favs'));

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin admin class.

Group of methods used on behaviors.
*/
class dcMaintenanceAdmin
{
	/**
	 * Register default tasks
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$tasks	<b>arrayObject</b>	Array of tasks to register
	 * @param	$groups	<b>arrayObject</b>	Array of groups to register
	 */
	 public static function register($core, $tasks, $groups)
	{
		$groups['optimize'] = __('Optimize');
		$groups['index'] = __('Count and index');
		$groups['purge'] = __('Purge');
		$groups['other'] = __('Other');

		$tasks[] = 'dcMaintenanceCache';
		$tasks[] = 'dcMaintenanceCountcomments';
		$tasks[] = 'dcMaintenanceIndexcomments';
		$tasks[] = 'dcMaintenanceIndexposts';
		$tasks[] = 'dcMaintenanceLogs';
		$tasks[] = 'dcMaintenanceVacuum';
	}

	/**
	 * Dashboard favs
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$favs	<b>arrayObject</b>	Array of favs
	 */
	public static function favs($core, $favs)
	{
		$favs['maintenance'] = new ArrayObject(array(
			'maintenance',
			'Maintenance',
			'plugin.php?p=maintenance',
			'index.php?pf=maintenance/icon.png',
			'index.php?pf=maintenance/icon-big.png',
			null,null,null
		));
	}

	/** @todo Rminder*/
}
