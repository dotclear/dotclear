<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

# Local prefs update
if (!empty($_POST['s']) && is_array($_POST['s']))
{
	try
	{
		foreach ($_POST['s'] as $ws => $s)
		{
			$core->auth->user_prefs->addWorkspace($ws);
			
			foreach ($s as $k => $v) 	{
				$core->auth->user_prefs->$ws->put($k,$v);
			}
		}
		
		http::redirect($p_url.'&upd=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Global prefs update
if (!empty($_POST['gs']) && is_array($_POST['gs']))
{
	try
	{
		foreach ($_POST['gs'] as $ws => $s)
		{
			$core->auth->user_prefs->addWorkspace($ws);
			
			foreach ($s as $k => $v) 	{
				$core->auth->user_prefs->$ws->put($k,$v,null,null,true,true);
			}
		}
		
		http::redirect($p_url.'&upd=1&part=global');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

$part = !empty($_GET['part']) && $_GET['part'] == 'global' ? 'global' : 'local';

function prefLine($id,$s,$ws,$field_name,$strong_label)
{
	if ($s['type'] == 'boolean') {
		$field = form::combo(array($field_name.'['.$ws.']['.$id.']',$field_name.'_'.$id),
		array(__('yes') => 1, __('no') => 0),$s['value']);
	} else {
		$field = form::field(array($field_name.'['.$ws.']['.$id.']',$field_name.'_'.$id),40,null,
		html::escapeHTML($s['value']));
	}
	
	$slabel = $strong_label ? '<strong>%s</strong>' : '%s';
	
	return
	'<tr>'.
	'<td><label for="s_'.$id.'">'.sprintf($slabel,html::escapeHTML($id)).'</label></td>'.
	'<td>'.$field.'</td>'.
	'<td>'.$s['type'].'</td>'.
	'<td>'.html::escapeHTML($s['label']).'</td>'.
	'</tr>';
}
?>
<html>
<head>
  <title>user:preferences</title>
  <?php echo dcPage::jsPageTabs($part); ?>
  <style type="text/css">
  .ns-name { background: #ccc; color: #000; padding-top: 0.3em; padding-bottom: 0.3em; font-size: 1.1em; }
  </style>
</head>

<body>
<?php
if (!empty($_GET['upd'])) {
	echo '<p class="message">'.__('Preferences successfully updated').'</p>';
}

if (!empty($_GET['upda'])) {
	echo '<p class="message">'.__('Preferences definition successfully updated').'</p>';
}
?>
<h2><?php echo html::escapeHTML($core->auth->userID()); ?> &rsaquo; user:preferences</h2>

<div id="local" class="multi-part" title="<?php echo __('user preferences'); ?>">
<form action="plugin.php" method="post">
<table>
<tr>
  <th class="nowrap">Pref ID</th>
  <th><?php echo __('Value'); ?></th>
  <th><?php echo __('Type'); ?></th>
  <th class="maximal"><?php echo __('Description'); ?></th>
</tr>
<?php
$prefs = array();

foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
	foreach ($workspace->dumpPrefs() as $k => $v) {
		$prefs[$ws][$k] = $v;
	}
}

ksort($prefs);

foreach ($prefs as $ws => $s)
{
	ksort($s);
	echo '<tr><td colspan="4" class="ws-name">workspace: <strong>'.$ws.'</strong></td></tr>';
	
	foreach ($s as $k => $v)
	{
		echo prefLine($k,$v,$ws,'s',!$v['global']);
	}
}
?>
</table>
<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<div id="global" class="multi-part" title="<?php echo __('global preferences'); ?>">
<form action="plugin.php" method="post">
<table>
<tr>
  <th class="nowrap">Pref ID</th>
  <th><?php echo __('Value'); ?></th>
  <th><?php echo __('Type'); ?></th>
  <th class="maximal"><?php echo __('Description'); ?></th>
</tr>
<?php
$prefs = array();

foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
	foreach ($workspace->dumpGlobalPrefs() as $k => $v) {
		$prefs[$ws][$k] = $v;
	}
}

ksort($prefs);

foreach ($prefs as $ws => $s)
{
	ksort($s);
	echo '<tr><td colspan="4" class="ws-name">workspace: <strong>'.$ws.'</strong></td></tr>';
	
	foreach ($s as $k => $v)
	{
		echo prefLine($k,$v,$ws,'gs',false);
	}
}
?>
</table>
<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

</body>
</html>