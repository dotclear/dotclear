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

// Set var

$headers = '';
$p_url = 'plugin.php?p=maintenance';
$task = null;
$expired = array();

$code = empty($_POST['code']) ? null : (integer) $_POST['code'];
$tab = empty($_REQUEST['tab']) ? 'maintenance' : $_REQUEST['tab'];

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

// Get expired tasks
$user_options = $core->auth->getOptions();
if (!empty($user_options['user_maintenance_expired'])) {
	$expired = $maintenance->getExpired();
}

// Display page

echo '<html><head>
<title>'.__('Maintenance').'</title>'.
dcPage::jsPageTabs($tab);

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

$msg = $task && !empty($_GET['done']) ? dcPage::success($task->success(),true,true,false) : '';

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
		'<a class="back" href="'.$p_url.'">'.__('Back').'</a>'.
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

				if (array_key_exists($t->id(), $expired)) {
					$res_task .= 
					' <span class="clear form-note warn">'.sprintf(
						__('Last execution of this task was on %s. You should execute it again.'),
						dt::dt2str(__('%Y-%m-%d %H:%M'), $expired[$t->id()])
					).'</span>';
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
			'<p class="form-note info">'.__('This may take a very long time').'.</p>'.
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
}

dcPage::helpBlock('maintenance');

echo '</body></html>';
