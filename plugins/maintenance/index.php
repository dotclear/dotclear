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

// Set env

$core->blog->settings->addNamespace('maintenance');

$maintenance = new dcMaintenance($core);
$tasks = $maintenance->getTasks();

$msg = '';
$headers = '';
$p_url = 'plugin.php?p=maintenance';
$task = null;
$expired = array();

$code = empty($_POST['code']) ? null : (integer) $_POST['code'];
$tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

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
			http::redirect($p_url.'&task='.$task->id().'&done=1&tab='.$tab.'#'.$tab);
		}
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

// Combos

$combo_ts = array(
	__('Never') 			=> 0,
	__('Every week') 		=> 604800,
	__('Every two weeks') 	=> 1209600,
	__('Every month') 		=> 2592000,
	__('Every two months') 	=> 5184000
);

// Display page

echo '<html><head>
<title>'.__('Maintenance').'</title>'.
dcPage::jsPageTabs($tab).
dcPage::jsLoad('index.php?pf=maintenance/js/settings.js');

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

// Check if there is somthing to display according to user permissions
if (empty($tasks)) {
	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			__('Maintenance') => ''
		)
	).
	'<p class="warn">'.__('You have not sufficient permissions to view this page.').'</p>'.
	'</body></html>';

	return null;
}

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
			html::escapeHTML($task->name())=> ''
		)
	);

	echo $msg;

	// Intermediate task (task required several steps)

	echo 
	'<div class="step-box" id="'.$task->id().'">'.
	'<p class="step-back">'.
		'<a class="back" href="'.$p_url.'&tab='.$task->tab().'#'.$task->tab().'">'.__('Back').'</a>'.
	'</p>'.
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
	'</div>';
}
else {

	// Page title

	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			__('Maintenance') => ''
		)
	);

	echo $msg;

	// Simple task (with only a button to start it)

	foreach($maintenance->getTabs() as $tab_obj)
	{
		$res_group = '';
		foreach($maintenance->getGroups() as $group_obj)
		{
			$res_task = '';
			foreach($tasks as $t)
			{
				if ($t->group() != $group_obj->id() 
				 || $t->tab() != $tab_obj->id()) {
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
				'<h4 id="'.$group_obj->id().'">'.$group_obj->name().'</h4>'.
				$res_task.
				'</div>';
			}
		}

		if (!empty($res_group)) {
			echo 
			'<div id="'.$tab_obj->id().'" class="multi-part" title="'.$tab_obj->name().'">'.
			'<h3>'.$tab_obj->name().'</h3>'.
			// ($tab_obj->option('summary') ? '<p>'.$tab_obj->option('summary').'</p>' : '').
			'<form action="'.$p_url.'" method="post">'.
			$res_group.
			'<p><input type="submit" value="'.__('Execute task').'" /> '.
			form::hidden(array('tab'), $tab_obj->id()).
			$core->formNonce().'</p>'.
			'<p class="form-note info">'.__('This may take a very long time.').'</p>'.
			'</form>'.
			'</div>';
		}
	}

	// Advanced tasks (that required a tab)

	foreach($tasks as $t)
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
}

dcPage::helpBlock('maintenance', 'maintenancetasks');

echo '</body></html>';
