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

require dirname(__FILE__).'/inc/class.dc.ieModule.php';

$modules = new ArrayObject();
$modules['i'] = new ArrayObject(array(
	'dcImportFlat' => dirname(__FILE__).'/inc/class.dc.import.flat.php',
	'dcImportFeed' => dirname(__FILE__).'/inc/class.dc.import.feed.php',
));
$modules['e'] = new ArrayObject(array(
	'dcExportFlat' => dirname(__FILE__).'/inc/class.dc.export.flat.php'
));

if ($core->auth->isSuperAdmin()) {
	$modules['i']['dcImportDC1']  = dirname(__FILE__).'/inc/class.dc.import.dc1.php';
	$modules['i']['dcImportWP']  = dirname(__FILE__).'/inc/class.dc.import.wp.php';
}

# --BEHAVIOR-- importExportModules
$core->callBehavior('importExportModules',$modules);

$type = null;
if (!empty($_REQUEST['t']) && ($_REQUEST['t'] == 'e' || $_REQUEST['t'] == 'i')) {
	$type = $_REQUEST['t'];
}

$current_module = null;
if ($type && !empty($_REQUEST['f'])) {
	if (isset($modules[$type][$_REQUEST['f']])) {
		require_once $modules[$type][$_REQUEST['f']];
		$current_module = new $_REQUEST['f']($core);
		$current_module->init();
	}
}

if ($type && $current_module !== null && !empty($_REQUEST['do']))
{
	try {
		$current_module->process($_REQUEST['do']);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}
?>
<html>
<head>
	<title><?php echo __('Import/Export'); ?></title>
	<style type="text/css">
	dl.modules dt {
		font-weight: bold;
		font-size: 1.1em;
		margin: 1em 0 0 0;
	}
	dl.modules dd {
		margin: 0 0 1.5em 0;
	}
	div.ie-progress {
		background: #eee;
		margin: 1em 0;
	}
	div.ie-progress div {
		height: 10px;
		width: 0;
		font-size: 0.8em;
		line-height: 1em;
		height: 1em;
		padding: 2px 0;
		text-align: right;
		background: green url(index.php?pf=importExport/progress.png) repeat-x top left;
		color: white;
		font-weight: bold;
		-moz-border-radius: 2px;
	}
	</style>
	<script type="text/javascript" src="index.php?pf=importExport/script.js"></script>
	<script type="text/javascript">
	//<![CDATA[
	dotclear.msg.please_wait = '<?php echo html::escapeJS(__("Please wait...")); ?>';
	//]]>
	</script>
</head>
<body>

<?php
if ($type && $current_module !== null)
{
	echo '<h2><a href="'.$p_url.'">'.__('Import/Export').'</a>'.
	' &rsaquo; <span class="page-title">'.html::escapeHTML($current_module->name).'</span></h2>';
	
	echo '<div id="ie-gui">';
	$current_module->gui();
	echo '</div>';
}
else
{
	echo '<h2 class="page-title">'.__('Import/Export').'</h2>';
	echo '<h3>'.__('Import').'</h3>';
	
	echo '<dl class="modules">';
	foreach ($modules['i'] as $k => $v)
	{
		require_once $v;
		$o = new $k($core);
		
		echo 
		'<dt><a href="'.$o->getURL(true).'">'.html::escapeHTML($o->name).'</a></dt>'.
		'<dd>'.html::escapeHTML($o->description).'</dd>';
		
		unset($o);
	}
	echo '</dl>';
	
	echo '<h3>'.__('Export').'</h3>';
	
	echo '<dl class="modules">';
	foreach ($modules['e'] as $k => $v)
	{
		require_once $v;
		$o = new $k($core);
		
		echo 
		'<dt><a href="'.$o->getURL(true).'">'.html::escapeHTML($o->name).'</a></dt>'.
		'<dd>'.html::escapeHTML($o->description).'</dd>';
		
		unset($o);
	}
	echo '</dl>';
}
?>

</body>
</html>