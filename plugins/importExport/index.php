<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of importExport, a plugin for DotClear2.
#
# Copyright (c) 2003-2012 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

function listImportExportModules($core,$modules)
{
	$res = '';
	foreach ($modules as $id)
	{
		$o = new $id($core);
		
		$res .= 
		'<dt><a href="'.$o->getURL(true).'">'.html::escapeHTML($o->name).'</a></dt>'.
		'<dd>'.html::escapeHTML($o->description).'</dd>';
		
		unset($o);
	}
	return '<dl class="modules">'.$res.'</dl>';
}

$modules = new ArrayObject(array('import' => array(),'export' => array()));

# --BEHAVIOR-- importExportModules
$core->callBehavior('importExportModules', $modules, $core);

$type = null;
if (!empty($_REQUEST['type'])  && in_array($_REQUEST['type'],array('export','import'))) {
	$type = $_REQUEST['type'];
}

$module = null;
if ($type && !empty($_REQUEST['module'])) {

	if (isset($modules[$type]) && in_array($_REQUEST['module'],$modules[$type])) {
	
		$module = new $_REQUEST['module']($core);
		$module->init();
	}
}

if ($type && $module !== null && !empty($_REQUEST['do']))
{
	try {
		$module->process($_REQUEST['do']);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

$title = __('Import/Export');

echo '
<html>
<head>
	<title>'.$title.'</title>
	<link rel="stylesheet" type="text/css" href="index.php?pf=importExport/style.css" />
	'.dcPage::jsLoad('index.php?pf=importExport/js/script.js').'
	<script type="text/javascript">
	//<![CDATA[
	'.dcPage::jsVar('dotclear.msg.please_wait',__('Please wait...')).'
	//]]>
	</script>
</head>
<body>';

if ($type && $module !== null) {
	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			$title => $p_url,
			html::escapeHTML($module->name) => ''
		)).
		dcPage::notices();

	echo
	'<div id="ie-gui">';
	
	$module->gui();
	
	echo '</div>';
}
else {
	echo dcPage::breadcrumb(
		array(
			__('Plugins') => '',
			$title => ''
		)).
		dcPage::notices();

	echo '<h3>'.__('Import').'</h3>'.listImportExportModules($core,$modules['import']);
}

echo
'<h3>'.__('Export').'</h3>'.
'<p class="info">'.sprintf(
	__('Export functions are in the page %s.'),
	'<a href="plugin.php?p=maintenance&amp;tab=backup#backup">'.__('Maintenance').'</a>'
).'</p>';

dcPage::helpBlock('import');

echo '</body></html>';
