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
$core->addBehavior('dcMaintenanceRegister', array('dcMaintenanceAdmin', 'dcMaintenanceRegister'));
$core->addBehavior('adminPreferencesHeaders', array('dcMaintenanceAdmin', 'adminPreferencesHeaders'));
$core->addBehavior('adminDashboardFavs', array('dcMaintenanceAdmin', 'adminDashboardFavs'));
$core->addBehavior('adminDashboardFavsIcon', array('dcMaintenanceAdmin', 'adminDashboardFavsIcon'));
$core->addBehavior('adminDashboardItems', array('dcMaintenanceAdmin', 'adminDashboardItems'));
$core->addBehavior('adminPreferencesForm',	array('dcMaintenanceAdmin',	'adminPreferencesForm'));
$core->addBehavior('adminBeforeUserOptionsUpdate',	array('dcMaintenanceAdmin',	'adminBeforeUserOptionsUpdate'));

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
		$tasks[] = 'dcMaintenanceCountcomments';
		$tasks[] = 'dcMaintenanceIndexcomments';
		$tasks[] = 'dcMaintenanceIndexposts';
		$tasks[] = 'dcMaintenanceLogs';
		$tasks[] = 'dcMaintenanceVacuum';
		$tasks[] = 'dcMaintenanceZipmedia';
		$tasks[] = 'dcMaintenanceZiptheme';
	}

	/**
	 * Dashboard headers.
	 *
	 * Add ajavascript to toggle tasks list.
	 */
	public static function adminPreferencesHeaders()
	{
		return dcPage::jsLoad('index.php?pf=maintenance/js/preferences.js');
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
		$expired = $maintenance->getExpired();
		$expired = count($expired);
		if (!$expired) {
			return null;
		}

		$icon[0] .= '<br />'.sprintf(__('One task to update', '%s tasks to update', $expired), $expired);
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
		$tasks = $maintenance->getExpired();
		if (empty($tasks)) {
			return null;
		}

		$lines = array();
		foreach($tasks as $id => $ts) {
			$lines[$ts] = 
			'<li title="'.sprintf(__('Last updated on %s'),
				dt::dt2str($core->blog->settings->system->date_format, $ts).' '.
				dt::dt2str($core->blog->settings->system->time_format, $ts)
			).'">'.$maintenance->getTask($id)->task().'</li>';
		}
		ksort($lines);

		$items[] = new ArrayObject(array(
			'<div id="maintenance-expired">'.
			'<h3><img src="index.php?pf=maintenance/icon.png" alt="" /> '.__('Maintenance').'</h3>'.
			'<p>'.sprintf(__('There is a task to update.', 'There are %s tasks to update.', count($tasks)), count($tasks)).'</p>'.
			'<ul>'.implode('',$lines).'</ul>'.
			'<p><a href="plugin.php?p=maintenance">'.__('Manage task').'</a></p>'.
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
	public static function adminPreferencesForm($core)
	{
		$core->auth->user_prefs->addWorkspace('maintenance');
		$maintenance = new dcMaintenance($core);

		$tasks = $maintenance->getTasks();
		if (empty($tasks)) {
			return null;
		}

		$full_combo_ts = array_merge(array(
			__('Use different periods for each task') => 'seperate'), 
			self::comboTs()
		);

		$task_combo_ts = array_merge(array(
			__('Never') => 0), 
			self::comboTs()
		);

		echo
		'<div class="fieldset">'.
		'<h4>'.__('Maintenance').'</h4>'.

		'<div class="two-boxes">'.

		'<p><label for="maintenance_dashboard_icon" class="classic">'.
		form::checkbox('maintenance_dashboard_icon', 1, $core->auth->user_prefs->maintenance->dashboard_icon).
		__('Display count of expired tasks on maintenance dashboard icon').'</label></p>'.

		'<p><label for="maintenance_dashboard_item" class="classic">'.
		form::checkbox('maintenance_dashboard_item', 1, $core->auth->user_prefs->maintenance->dashboard_item).
		__('Display list of expired tasks on dashboard contents').'</label></p>'.

		'<p><label for="maintenance_plugin_message" class="classic">'.
		form::checkbox('maintenance_plugin_message', 1, $core->auth->user_prefs->maintenance->plugin_message).
		__('Display alert message of expired tasks on plugin page').'</label></p>'.

		'</div>'.

		'<div class="two-boxes">'.

		'<p><label for="maintenance_recall_time">'.__('Recall time for all tasks').'</label>'.
		form::combo('maintenance_recall_time', $full_combo_ts, 'seperate', 'recall-for-all').
		'</p>'.

		'</div>'.

		'<div id="maintenance-recall-time">'.
		'<h5>'.__('Recall time per task').'</h5>';

		foreach($tasks as $task) {
			echo
			'<div class="two-boxes">'.

			'<p><label for="maintenance_ts_'.$task->id().'">'.$task->task().'</label>'.
			form::combo('maintenance_ts_'.$task->id(), $task_combo_ts, $task->ts(), 'recall-per-task').
			'</p>'.

			'</div>';
		}

		echo 
		'</div>'.
		'</div>';
	}

	/**
	 * User preferences update.
	 *
	 * @param	$cur	<b>cursor</b>	Cursor of user options
	 * @param	$user_id	<b>string</b>	User ID
	 */
	public static function adminBeforeUserOptionsUpdate($cur, $user_id=null)
	{
		global $core;

		if (is_null($user_id)) {
			return null;
		}

		$maintenance = new dcMaintenance($core);
		$tasks = $maintenance->getTasks();
		if (empty($tasks)) {
			return null;
		}

		$core->auth->user_prefs->addWorkspace('maintenance');
		$core->auth->user_prefs->maintenance->put('dashboard_icon', !empty($_POST['maintenance_dashboard_icon']), 'boolean');
		$core->auth->user_prefs->maintenance->put('dashboard_item', !empty($_POST['maintenance_dashboard_item']), 'boolean');
		$core->auth->user_prefs->maintenance->put('plugin_message', !empty($_POST['maintenance_plugin_message']), 'boolean');

		foreach($tasks as $task) {
			if ($_POST['maintenance_recall_time'] == 'seperate') {
				$ts = empty($_POST['maintenance_ts_'.$task->id()]) ? 0 : $_POST['maintenance_ts_'.$task->id()];
			}
			else {
				$ts = $_POST['maintenance_recall_time'];
			}
			$core->auth->user_prefs->maintenance->put('ts_'.$task->id(), abs((integer) $ts), 'integer');
		}
	}

	/* @ignore */
	public static function comboTs()
	{
		return array(
			__('Every week') 		=> 604800,
			__('Every two weeks') 	=> 1209600,
			__('Every month') 		=> 2592000,
			__('Every two months') 	=> 5184000
		);
	}
}
