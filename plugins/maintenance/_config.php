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
if (!defined('DC_CONTEXT_PLUGIN')) { return; }

$core->blog->settings->addNamespace('maintenance');
$maintenance = new dcMaintenance($core);
$tasks = $maintenance->getTasks();

$combo_ts = array(
	__('Never') 			=> 0,
	__('Every week') 		=> 604800,
	__('Every two weeks') 	=> 1209600,
	__('Every month') 		=> 2592000,
	__('Every two months') 	=> 5184000
);

if (!empty($_POST['save'])) {

	try {
		$core->blog->settings->maintenance->put(
			'plugin_message', 
			!empty($_POST['settings_plugin_message']), 
			'boolean', 
			'Display alert message of late tasks on plugin page', 
			true, 
			true
		);

		foreach($tasks as $t) {
			if (!empty($_POST['settings_recall_type']) && $_POST['settings_recall_type'] == 'all') {
				$ts = $_POST['settings_recall_time'];
			}
			else {
				$ts = empty($_POST['settings_ts_'.$t->id()]) ? 0 : $_POST['settings_ts_'.$t->id()];
			}
			$core->blog->settings->maintenance->put(
				'ts_'.$t->id(), 
				abs((integer) $ts), 
				'integer', 
				sprintf('Recall time for task %s', $t->id()), 
				true, 
				$t->blog()
			);
		}
		
		http::redirect($list->getPageURL('module=maintenance&conf=1&done=1'));
	}
	catch(Exception $e) {
		$core->error->add($e->getMessage());
	}
}

	echo 
	'<p>'.__('Manage alert for maintenance task.').'</p>'.

	'<h4 class="pretty-title">'.__('Activation').'</h4>'.
	'<p><label for="settings_plugin_message" class="classic">'.
	form::checkbox('settings_plugin_message', 1, $core->blog->settings->maintenance->plugin_message).
	__('Display alert messages on late tasks').'</label></p>'.

	'<p class="info">'.sprintf(
		__('You can place list of late tasks on your %s.'),
		'<a href="preferences.php#user-favorites">'.__('Dashboard').'</a>'
	).'</p>'.

	'<h4 class="pretty-title vertical-separator">'.__('Frequency').'</h4>'.

	'<p class="vertical-separator">'.form::radio(array('settings_recall_type', 'settings_recall_all'), 'all').' '.
	'<label class="classic" for="settings_recall_all">'.
	'<strong>'.__('Use one recall time for all tasks').'</strong></label>'.

	'<p class="field wide vertical-separator"><label for="settings_recall_time">'.__('Recall time for all tasks:').'</label>'.
	form::combo('settings_recall_time', $combo_ts, 'seperate', 'recall-for-all').
	'</p>'.

	'<p class="vertical-separator">'.form::radio(array('settings_recall_type', 'settings_recall_separate'), 'separate', 1).' '.
	'<label class="classic" for="settings_recall_separate">'.
	'<strong>'.__('Use one recall time per task').'</strong></label>';

	foreach($tasks as $t)
	{
		echo
		'<div class="two-boxes">'.

		'<p class="field wide"><label for="settings_ts_'.$t->id().'">'.$t->task().'</label>'.
		form::combo('settings_ts_'.$t->id(), $combo_ts, $t->ts(), 'recall-per-task').
		'</p>'.

		'</div>';
	}
