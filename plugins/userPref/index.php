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

# Local navigation
if (!empty($_POST['gp_nav'])) {
	http::redirect($p_url.$_POST['gp_nav']);
	exit;
}
if (!empty($_POST['lp_nav'])) {
	http::redirect($p_url.$_POST['lp_nav']);
	exit;
}

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
		array(__('yes') => 1, __('no') => 0),$s['value'] ? 1 : 0);
	} else {
		$field = form::field(array($field_name.'['.$ws.']['.$id.']',$field_name.'_'.$id),40,null,
		html::escapeHTML($s['value']));
	}
	
	$slabel = $strong_label ? '<strong>%s</strong>' : '%s';
	
	return
	'<tr class="line">'.
	'<td scope="row"><label for="s_'.$id.'">'.sprintf($slabel,html::escapeHTML($id)).'</label></td>'.
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
	<script type="text/javascript">
	//<![CDATA[
	$(function() {
		$("#gp_submit").hide();
		$("#lp_submit").hide();
		$("#gp_nav").change(function() {
			window.location = $("#gp_nav option:selected").val();
		})
		$("#lp_nav").change(function() {
			window.location = $("#lp_nav option:selected").val();
		})
	});
	//]]>
	</script>
</head>

<body>
<?php
echo dcPage::breadcrumb(
	array(
		__('System') => '',
		html::escapeHTML($core->auth->userID()) => '',
		'<span class="page-title">'.__('user:preferences').'</span>' => ''
	));

if (!empty($_GET['upd'])) {
	dcPage::success(__('Preferences successfully updated'));
}

if (!empty($_GET['upda'])) {
	dcPage::success(__('Preferences definition successfully updated'));
}
?>

<div id="local" class="multi-part" title="<?php echo __('User preferences'); ?>">
<h3 class="out-of-screen-if-js"><?php echo __('User preferences'); ?></h3>

<?php 
$table_header = '<div class="table-outer"><table class="prefs" id="%s"><caption class="as_h3">%s</caption>'.
'<thead>'.
'<tr>'."\n".
'  <th class="nowrap">Setting ID</th>'."\n".
'  <th>'.__('Value').'</th>'."\n".
'  <th>'.__('Type').'</th>'."\n".
'  <th class="maximalx">'.__('Description').'</th>'."\n".
'</tr>'."\n".
'</thead>'."\n".
'<tbody>';
$table_footer = '</tbody></table></div>';

$prefs = array();
foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
	foreach ($workspace->dumpPrefs() as $k => $v) {
		$prefs[$ws][$k] = $v;
	}
}
ksort($prefs);
if (count($prefs) > 0) {
	$ws_combo = array();
	foreach ($prefs as $ws => $s) {
		$ws_combo[$ws] = '#l_'.$ws;
	}
	echo 
		'<form action="plugin.php" method="post">'.
		'<p class="anchor-nav">'.
		'<label for="lp_nav" class="classic">'.__('Goto:').'</label> '.form::combo('lp_nav',$ws_combo).
		' <input type="submit" value="'.__('Ok').'" id="lp_submit" />'.
		'<input type="hidden" name="p" value="aboutConfig" />'.
		$core->formNonce().'</p></form>';
}
?>

<form action="plugin.php" method="post">

<?php
foreach ($prefs as $ws => $s)
{
	ksort($s);
	echo sprintf($table_header,'l_'.$ws,$ws);
	foreach ($s as $k => $v)
	{
		echo prefLine($k,$v,$ws,'s',!$v['global']);
	}
	echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

<div id="global" class="multi-part" title="<?php echo __('Global preferences'); ?>">
<h3 class="out-of-screen-if-js"><?php echo __('Global preferences'); ?></h3>

<?php
$prefs = array();

foreach ($core->auth->user_prefs->dumpWorkspaces() as $ws => $workspace) {
	foreach ($workspace->dumpGlobalPrefs() as $k => $v) {
		$prefs[$ws][$k] = $v;
	}
}

ksort($prefs);

if (count($prefs) > 0) {
	$ws_combo = array();
	foreach ($prefs as $ws => $s) {
		$ws_combo[$ws] = '#g_'.$ws;
	}
	echo 
		'<form action="plugin.php" method="post">'.
		'<p class="anchor-nav">'.
		'<label for="gp_nav" class="classic">'.__('Goto:').'</label> '.form::combo('gp_nav',$ws_combo).
		' <input type="submit" value="'.__('Ok').'" id="gp_submit" />'.
		'<input type="hidden" name="p" value="aboutConfig" />'.
		$core->formNonce().'</p></form>';
}
?>

<form action="plugin.php" method="post">

<?php
foreach ($prefs as $ws => $s)
{
	ksort($s);
	echo sprintf($table_header,'g_'.$ws,$ws);
	foreach ($s as $k => $v)
	{
		echo prefLine($k,$v,$ws,'gs',false);
	}
	echo $table_footer;
}
?>

<p><input type="submit" value="<?php echo __('Save'); ?>" />
<input type="hidden" name="p" value="userPref" />
<?php echo $core->formNonce(); ?></p>
</form>
</div>

</body>
</html>
