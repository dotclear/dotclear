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
$core->addBehavior('dcMaintenanceInit', array('dcMaintenanceAdmin', 'dcMaintenanceInit'));
$core->addBehavior('adminDashboardFavs', array('dcMaintenanceAdmin', 'adminDashboardFavs'));
$core->addBehavior('adminDashboardFavsIcon', array('dcMaintenanceAdmin', 'adminDashboardFavsIcon'));
$core->addBehavior('adminDashboardContents', array('dcMaintenanceAdmin', 'adminDashboardItems'));
$core->addBehavior('adminDashboardOptionsForm',	array('dcMaintenanceAdmin',	'adminDashboardOptionsForm'));
$core->addBehavior('adminAfterDashboardOptionsUpdate',	array('dcMaintenanceAdmin',	'adminAfterDashboardOptionsUpdate'));
$core->addBehavior('adminPageHelpBlock',	array('dcMaintenanceAdmin',	'adminPageHelpBlock'));
$core->addBehavior('pluginsToolsHeaders',	array('dcMaintenanceAdmin',	'pluginsToolsHeaders'));

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
	 * @param	$maintenance	<b>dcMaintenance</b>	dcMaintenance instance
	 */
	 public static function dcMaintenanceInit($maintenance)
	{
		$maintenance
		->addTab('maintenance', __('Servicing'), array('summary' => __('Tools to maintain the performance of your blogs.')))
		->addTab('backup', __('Backup'), array('summary' => __('Tools to back up your content.')))
		->addTab('dev', __('Development'), array('summary' => __('Tools to assist in development of plugins, themes and core.')))

		->addGroup('optimize', __('Optimize'))
		->addGroup('index', __('Count and index'))
		->addGroup('purge', __('Purge'))
		->addGroup('other', __('Other'))
		->addGroup('zipblog', __('Current blog'))
		->addGroup('zipfull', __('All blogs'))

		->addGroup('l10n', __('Translations'), array('summary' => __('Maintain translations')))

		->addTask('dcMaintenanceCache')
		->addTask('dcMaintenanceIndexposts')
		->addTask('dcMaintenanceIndexcomments')
		->addTask('dcMaintenanceCountcomments')
		->addTask('dcMaintenanceSynchpostsmeta')
		->addTask('dcMaintenanceLogs')
		->addTask('dcMaintenanceVacuum')
		->addTask('dcMaintenanceZipmedia')
		->addTask('dcMaintenanceZiptheme')
		;
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
			'<div id="maintenance-expired" class="box small">'.
			'<h3><img src="index.php?pf=maintenance/icon-small.png" alt="" /> '.__('Maintenance').'</h3>'.
			'<p class="warning no-margin">'.sprintf(__('There is a task to execute.', 'There are %s tasks to execute.', count($lines)), count($lines)).'</p>'.
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

	/**
	 * Build a well sorted help for tasks.
	 *
	 * This method is not so good if used with lot of tranlsations 
	 * as it grows memory usage and translations files size, 
	 * it is better to use help ressource files 
	 * but keep it for exemple of how to use behavior adminPageHelpBlock.
	 * Cheers, JC
	 *
	 * @param	$block	<b>arrayObject</b>	Called helpblocks
	 */
	public static function adminPageHelpBlock($blocks)
	{
		$found = false;
		foreach($blocks as $block) {
			if ($block == 'maintenancetasks') {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return null;
		}

		$maintenance = new dcMaintenance($GLOBALS['core']);

		$res_tab = '';
		foreach($maintenance->getTabs() as $tab_obj)
		{
			$res_group = '';
			foreach($maintenance->getGroups() as $group_obj)
			{
				$res_task = '';
				foreach($maintenance->getTasks() as $t)
				{
					if ($t->group() != $group_obj->id() 
					 || $t->tab() != $tab_obj->id()) {
						continue;
					}
					if (($desc = $t->description()) != '') {
						$res_task .= 
						'<dt>'.$t->task().'</dt>'.
						'<dd>'.$desc.'</dd>';
					}
				}
				if (!empty($res_task)) {
					$desc = $group_obj->description ? $group_obj->description : $group_obj->summary;

					$res_group .= 
					'<h5>'.$group_obj->name().'</h5>'.
					($desc ? '<p>'.$desc.'</p>' : '').
					'<dl>'.$res_task.'</dl>';
				}
			}
			if (!empty($res_group)) {
				$desc = $tab_obj->description ? $tab_obj->description : $tab_obj->summary;

				$res_tab .= 
				'<h4>'.$tab_obj->name().'</h4>'.
				($desc ? '<p>'.$desc.'</p>' : '').
				$res_group;
			}
		}
		if (!empty($res_tab)) {
			$res = new ArrayObject();
			$res->content = $res_tab;
			$blocks[] = $res;
		}
	}

	/**
	 * Add javascript for plugin configuration.
	 *
	 * @param	$core	<b>dcCore</b>	dcCore instance
	 * @param	$module	<b>mixed</b>	Module ID or false if none
	 * @return	<b>string</b>	Header code for js inclusion
	 */
	public static function pluginsToolsHeaders($core, $module)
	{
		if ($module == 'maintenance') {
			return dcPage::jsLoad('index.php?pf=maintenance/js/settings.js');
		}
	}
}
