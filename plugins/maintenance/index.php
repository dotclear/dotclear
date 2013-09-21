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

dcPage::checkSuper();

// main class

$maintenance = new dcMaintenance($core);
$core->blog->settings->addNamespace('maintenance');

// Set var

$msg = '';
$headers = '';
$p_url = 'plugin.php?p=maintenance';
$task = null;
$expired = array();

$code = empty($_POST['code']) ? null : (integer) $_POST['code'];
$tab = empty($_REQUEST['tab']) ? 'maintenance' : $_REQUEST['tab'];

// Save settings

if (!empty($_POST['settings'])) {

	try {
		$core->blog->settings->maintenance->put(
			'plugin_message', 
			!empty($_POST['settings_plugin_message']), 
			'boolean', 
			'Display alert message of expired tasks on plugin page', 
			true, 
			true
		);

		foreach($maintenance->getTasks() as $t) {
			if (!empty($_POST['settings_recall_time']) && $_POST['settings_recall_time'] == 'seperate') {
				$ts = empty($_POST['settings_ts_'.$t->id()]) ? 0 : $_POST['settings_ts_'.$t->id()];
			}
			else {
				$ts = $_POST['settings_recall_time'];
			}
			$core->blog->settings->maintenance->put(
				'ts_'.$t->id(), 
				abs((integer) $ts), 
				'integer', 
				sprintf('Recall time for task %s', $t->id()), 
				true, 
				true
			);
		}
		
		http::redirect($p_url.'&done=1&tab='.$tab);
	}
	catch(Exception $e) {
		$core->error->add($e->getMessage());
	}
}

// Get task object

if (!empty($_REQUEST['task'])) {
	$task = $maintenance->getTask($_REQUEST['task']);

	if ($task === null) {
		$core->error->add('Unknow task ID');
	}

	$task->code($code);
}

// Execute task

if ($task && !empty($_POST['task']) && $task->id() == $_POST['task']) {
	try {
		$code = $task->execute();
		if (false === $code) {
			throw new Exception($task->error());
		}
		if (true === $code) {
			$maintenance->setLog($task->id());
			http::redirect($p_url.'&task='.$task->id().'&done=1&tab='.$tab);
		}
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

// Combos

$combo_ts = array(
	__('Every week') 		=> 604800,
	__('Every two weeks') 	=> 1209600,
	__('Every month') 		=> 2592000,
	__('Every two months') 	=> 5184000
);

$full_combo_ts = array_merge(array(
	__('Use different periods for each task') => 'seperate'), 
	$combo_ts
);

$task_combo_ts = array_merge(array(
	__('Never') => 0), 
	$combo_ts
);

// Display page

echo '<html><head>
<title>'.__('Maintenance').'</title>'.
dcPage::jsPageTabs($tab).
dcPage::jsLoad('index.php?pf=maintenance/js/settings.js');;

if ($task && $task->ajax()) {
	echo 
	'<script type="text/javascript">'."\n".
	"//<![CDATA[\n".
	dcPage::jsVar('dotclear.msg.wait', __('Please wait...')).
	"//]]>\n".
	'</script>'.
	dcPage::jsLoad('index.php?pf=maintenance/js/dc.maintenance.js');
}

echo 
$maintenance->getHeaders().'
</head>
<body>';

// Success message

if (!empty($_GET['done']) && $tab == 'settings') {
	$msg = dcPage::success(__('Settings successfully updated'), true, true, false);
}
elseif (!empty($_GET['done']) && $task) {
	$msg = dcPage::success($task->success(), true, true, false);
}

if ($task && ($res = $task->step()) !== null) {

	// Page title

	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			'<a href="'.$p_url.'">'.__('Maintenance').'</a>' => '',
			'<span class="page-title">'.html::escapeHTML($task->name()).'</span>' => ''
		)
	);

	echo $msg;

	// Intermediate task (task required several steps)

	echo 
	'<div class="step-box" id="'.$task->id().'">'.
	'<h3>'.html::escapeHTML($task->name()).'</h3>'.
	'<form action="'.$p_url.'" method="post">'.
	'<p class="step-msg">'.
		$res.
	'</p>'.
	'<p class="step-submit">'.
		'<input type="submit" value="'.$task->task().'" /> '.
		form::hidden(array('task'), $task->id()).
		form::hidden(array('code'), (integer) $code).
		$core->formNonce().
	'</p>'.
	'</form>'.
	'<p class="step-back">'.
		'<a class="back" href="'.$p_url.'&tab='.$task->tab().'">'.__('Back').'</a>'.
	'</p>'.
	'</div>';
}
else {

	// Page title

	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			'<span class="page-title">'.__('Maintenance').'</span>' => ''
		)
	);

	echo $msg;

	// Simple task (with only a button to start it)

	foreach($maintenance->getTabs() as $tab_id => $tab_name)
	{
		$res_group = '';
		foreach($maintenance->getGroups($core) as $group_id => $group_name)
		{
			$res_task = '';
			foreach($maintenance->getTasks($core) as $t)
			{
				if ($t->group() != $group_id || $t->tab() != $tab_id) {
					continue;
				}

				$res_task .=  
				'<p>'.form::radio(array('task', $t->id()), $t->id()).' '.
				'<label class="classic" for="'.$t->id().'">'.
				html::escapeHTML($t->task()).'</label>';

				// Expired task alert message
				$ts = $t->expired();
				if ($core->blog->settings->maintenance->plugin_message && $ts !== false) {
					if ($ts === null) {
						$res_task .= 
						'<br /> <span class="warn">'.
						__('This task has never been executed.').' '.
						__('You should execute it now.').'</span>';
					}
					else {
						$res_task .= 
						'<br /> <span class="warn">'.sprintf(
							__('Last execution of this task was on %s.'),
							dt::str($core->blog->settings->system->date_format, $ts).' '.
							dt::str($core->blog->settings->system->time_format, $ts)
						).' '.
						__('You should execute it now.').'</span>';
					}
				}

				$res_task .= '</p>';
			}

			if (!empty($res_task)) {
				$res_group .= 
				'<div class="fieldset">'.
				'<h4 id="'.$group_id.'">'.$group_name.'</h4>'.
				$res_task.
				'</div>';
			}
		}

		if (!empty($res_group)) {
			echo 
			'<div id="'.$tab_id.'" class="multi-part" title="'.$tab_name.'">'.
			'<h3>'.$tab_name.'</h3>'.
			'<form action="'.$p_url.'" method="post">'.
			$res_group.
			'<p><input type="submit" value="'.__('Execute task').'" /> '.
			form::hidden(array('tab'), $tab_id).
			$core->formNonce().'</p>'.
			'<p class="form-note info">'.__('This may take a very long time.').'.</p>'.
			'</form>'.
			'</div>';
		}
	}

	// Advanced tasks (that required a tab)

	foreach($maintenance->getTasks($core) as $t)
	{
		if ($t->group() !== null) {
			continue;
		}

		echo 
		'<div id="'.$t->id().'" class="multi-part" title="'.$t->name().'">'.
		'<h3>'.$t->name().'</h3>'.
		'<form action="'.$p_url.'" method="post">'.
		$t->content().
		'<p><input type="submit" value="'.__('Execute task').'" /> '.
		form::hidden(array('task'), $t->id()).
		form::hidden(array('tab'), $t->id()).
		$core->formNonce().'</p>'.
		'</form>'.
		'</div>';
	}

	// Settings

	echo 
	'<div id="settings" class="multi-part" title="'.__('Settings').'">'.
	'<h3>'.__('Settings').'</h3>'.
	'<form action="'.$p_url.'" method="post">'.

	'<p><label for="settings_plugin_message" class="classic">'.
	form::checkbox('settings_plugin_message', 1, $core->blog->settings->maintenance->plugin_message).
	__('Display alert messages on expired tasks').'</label></p>'.

	'<p><label for="settings_recall_time">'.__('Recall time for all tasks:').'</label>'.
	form::combo('settings_recall_time', $full_combo_ts, 'seperate', 'recall-for-all').
	'</p>'.

	'<p>'.__('Recall time per task:').'</p>';

	foreach($maintenance->getTasks($core) as $t)
	{
		echo
		'<div class="two-boxes">'.

		'<p><label for="settings_ts_'.$t->id().'">'.$t->task().'</label>'.
		form::combo('settings_ts_'.$t->id(), $task_combo_ts, $t->ts(), 'recall-per-task').
		'</p>'.

		'</div>';
	}

	echo 
	'<p><input type="submit" value="'.__('Save').'" /> '.
	form::hidden(array('tab'), 'settings').
	form::hidden(array('settings'), 1).
	$core->formNonce().'</p>'.
	'</form>'.
	'<p class="info">'.sprintf(
		__('You can place list of expired tasks on your %s.'),
		'<a href="preferences.php#user-favorites">'.__('Dashboard').'</a>'
	).'</a></p>'.
	'</div>';
}

dcPage::helpBlock('maintenance');

echo '</body></html>';
