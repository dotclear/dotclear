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
	$core->auth->check('admin', $core->blog->id)
);

// Admin behaviors
$core->addBehavior('dcMaintenanceRegister', array('dcMaintenanceAdmin', 'dcMaintenanceRegister'));
$core->addBehavior('adminDashboardFavs', array('dcMaintenanceAdmin', 'adminDashboardFavs'));
$core->addBehavior('adminDashboardFavsIcon', array('dcMaintenanceAdmin', 'adminDashboardFavsIcon'));
$core->addBehavior('adminDashboardItems', array('dcMaintenanceAdmin', 'adminDashboardItems'));
$core->addBehavior('adminDashboardOptionsForm',	array('dcMaintenanceAdmin',	'adminDashboardOptionsForm'));
$core->addBehavior('adminAfterDashboardOptionsUpdate',	array('dcMaintenanceAdmin',	'adminAfterDashboardOptionsUpdate'));

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
	 * @param	$tabs	<b>arrayObject</b>	Array of tabs to register
	 */
	 public static function dcMaintenanceRegister($core, $tasks, $groups, $tabs)
	{
		$tabs['maintenance'] = __('Servicing');
		$tabs['backup'] = __('Backup');

		$groups['optimize'] = __('Optimize');
		$groups['index'] = __('Count and index');
		$groups['purge'] = __('Purge');
		$groups['other'] = __('Other');
		$groups['zipblog'] = __('Compressed file for current blog');
		$groups['zipfull'] = __('Compressed file for all blogs');

		$tasks[] = 'dcMaintenanceCache';
		$tasks[] = 'dcMaintenanceIndexposts';
		$tasks[] = 'dcMaintenanceIndexcomments';
		$tasks[] = 'dcMaintenanceCountcomments';
		$tasks[] = 'dcMaintenanceLogs';
		$tasks[] = 'dcMaintenanceVacuum';
		$tasks[] = 'dcMaintenanceZipmedia';
		$tasks[] = 'dcMaintenanceZiptheme';
	}

	/**
	 * Dashboard favs.
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$favs	<b>arrayObject</b>	Array of favs
	 */
	public static function adminDashboardFavs($core, $favs)
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
	public static function adminDashboardFavsIcon($core, $name, $icon)
	{
		// Check icon
		if ($name !== 'maintenance') {
			return null;
		}

		// Check user option
		$core->auth->user_prefs->addWorkspace('maintenance');
		if (!$core->auth->user_prefs->maintenance->dashboard_icon) {
			return null;
		}

		// Check expired tasks
		$maintenance = new dcMaintenance($core);
		$count = 0;
		foreach($maintenance->getTasks() as $t)
		{
			if ($t->expired() !== false){
				$count++;
			}
		}

		if (!$count) {
			return null;
		}

		$icon[0] .= '<br />'.sprintf(__('One task to execute', '%s tasks to execute', $count), $count);
	}

	/**
	 * Dashboard items stack.
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$items	<b>arrayObject</b>	Dashboard items
	 */
	public static function adminDashboardItems($core, $items)
	{
		$core->auth->user_prefs->addWorkspace('maintenance');
		if (!$core->auth->user_prefs->maintenance->dashboard_item) {
			return null;
		}

		$maintenance = new dcMaintenance($core);

		$lines = array();
		foreach($maintenance->getTasks() as $t)
		{
			$ts = $t->expired();
			if ($ts === false){
				continue;
			}

			$lines[] = 
			'<li title="'.($ts === null ?
				__('This task has never been executed.')
				:
				sprintf(__('Last execution of this task was on %s.'),
					dt::dt2str($core->blog->settings->system->date_format, $ts).' '.
					dt::dt2str($core->blog->settings->system->time_format, $ts)
				)
			).'">'.$t->task().'</li>';
		}

		if (empty($lines)) {
			return null;
		}

		$items[] = new ArrayObject(array(
			'<div id="maintenance-expired">'.
			'<h3><img src="index.php?pf=maintenance/icon.png" alt="" /> '.__('Maintenance').'</h3>'.
			'<p class="warn">'.sprintf(__('There is a task to execute.', 'There are %s tasks to execute.', count($lines)), count($lines)).'</p>'.
			'<ul>'.implode('',$lines).'</ul>'.
			'<p><a href="plugin.php?p=maintenance">'.__('Manage tasks').'</a></p>'.
			'</div>'
			));
	}

	/**
	 * User preferences form.
	 *
	 * This add options for superadmin user 
	 * to show or not expired taks.
	 *
	 * @param	$args	<b>object</b>	dcCore instance or record
	 */
	public static function adminDashboardOptionsForm($core)
	{
		$core->auth->user_prefs->addWorkspace('maintenance');

		echo
		'<div class="fieldset">'.
		'<h4>'.__('Maintenance').'</h4>'.

		'<p><label for="maintenance_dashboard_icon" class="classic">'.
		form::checkbox('maintenance_dashboard_icon', 1, $core->auth->user_prefs->maintenance->dashboard_icon).
		__('Display count of late tasks on maintenance dashboard icon').'</label></p>'.

		'<p><label for="maintenance_dashboard_item" class="classic">'.
		form::checkbox('maintenance_dashboard_item', 1, $core->auth->user_prefs->maintenance->dashboard_item).
		__('Display list of late tasks on dashboard items').'</label></p>'.

		'</div>';
	}

	/**
	 * User preferences update.
	 *
	 * @param	$user_id	<b>string</b>	User ID
	 */
	public static function adminAfterDashboardOptionsUpdate($user_id=null)
	{
		global $core;

		if (is_null($user_id)) {
			return null;
		}

		$core->auth->user_prefs->addWorkspace('maintenance');
		$core->auth->user_prefs->maintenance->put('dashboard_icon', !empty($_POST['maintenance_dashboard_icon']), 'boolean');
		$core->auth->user_prefs->maintenance->put('dashboard_item', !empty($_POST['maintenance_dashboard_item']), 'boolean');
	}
}
