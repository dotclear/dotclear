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
$core->addBehavior('adminDashboardFavsIcon', array('dcMaintenanceAdmin', 'favsicon'));
$core->addBehavior('adminPreferencesForm',	array('dcMaintenanceAdmin',	'prefform'));
$core->addBehavior('adminBeforeUserOptionsUpdate',	array('dcMaintenanceAdmin',	'userupd'));

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin admin class.

Group of methods used on behaviors.
*/
class dcMaintenanceAdmin
{
	/**
	 * Register default tasks.
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
	 * Dashboard favs.
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

	/**
	 * Dashboard favs icon.
	 *
	 * This updates maintenance fav icon text 
	 * if there are tasks required maintenance.
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$name	<b>string</b>	Current fav name
	 * @param	$icon	<b>arrayObject</b>	Current fav attributes
	 */
	public static function favsicon($core, $name, $icon)
	{
		// Check icon
		if ($name !== 'maintenance') {
			return null;
		}

		// Check user option
		$user_options = $core->auth->getOptions();
		if (empty($user_options['user_maintenance_expired'])) {
			return null;
		}

		// Check expired tasks
		$maintenance = new dcMaintenance($core);
		$expired = $maintenance->getExpired();
		$expired = count($expired);
		if (!$expired) {
			return null;
		}

		$icon[0] .= '<br />'.sprintf(__('One task to update', '%s tasks to update', $expired), $expired);
	}

	/**
	 * User preferences form.
	 *
	 * This add options for superadmin user 
	 * to show or not expired taks.
	 *
	 * @param	$args	<b>object</b>	dcCore instance or record
	 */
	public static function prefform($args)
	{
		$opts = array();
		if ($args instanceof dcCore) {
			$opts = $args->auth->getOptions();
			$core = $args;
		}
		elseif ($args instanceof record) {
			$opts = $args->options();
			$core = $args->core;
		}

		echo 
		'<p><label for="user_maintenance_expired" class="classic">'.
		form::checkbox('user_maintenance_expired', 1, !empty($opts['user_maintenance_expired'])).' '.
		__('Show maintenance tasks to update.').'</label></p>';
	}

	/**
	 * User preferences update.
	 *
	 * @param	$cur	<b>cursor</b>	Cursor of user options
	 * @param	$user_id	<b>string</b>	User ID
	 */
	public static function userupd($cur, $user_id=null)
	{
		if (!is_null($user_id)) {
			$cur->user_options['user_maintenance_expired'] = !empty($_POST['user_maintenance_expired']);
		}
	}
}
