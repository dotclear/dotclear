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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('admin');

# -- "First time" settings setup --
if ($core->blog->settings->system->plugins_allow_multi_install === null) {
	$core->blog->settings->system->put(
		'plugins_allow_multi_install', false, 'boolean', 'Allow multi-installation for plugins', true, true
	);
}
if ($core->blog->settings->system->repository_plugin_url === null) {
	$core->blog->settings->system->put(
		'repository_plugin_url', 'http://update.dotaddict.org/dc2/plugins.xml', 'string', 'Plugins XML feed location', true, true
	);
}

# -- Page helper --
$list = new adminModulesList(
	$core->plugins, 
	DC_PLUGINS_ROOT, 
	$core->blog->settings->system->repository_plugin_url
);

$list::$allow_multi_install = $core->blog->settings->system->plugins_allow_multi_install;
$list::$distributed_modules = array(
	'aboutConfig',
	'akismet',
	'antispam',
	'attachments',
	'blogroll',
	'blowupConfig',
	'daInstaller',
	'fairTrackbacks',
	'importExport',
	'maintenance',
	'pages',
	'pings',
	'simpleMenu',
	'tags',
	'themeEditor',
	'userPref',
	'widgets'
);

# -- Display module configuration page --
if ($list->setConfigurationFile()) {

	# Get content before page headers
	include $list->getConfigurationFile();

	# Gather content
	$list->setConfigurationContent();

	# Display page
	dcPage::open(__('Plugins management'),

		# --BEHAVIOR-- pluginsToolsHeaders
		$core->callBehavior('pluginsToolsHeaders', $core, true),

		dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				'<a href="'.$list->getPageURL().'">'.__('Plugins management').'</a>' => '',
				'<span class="page-title">'.__('Plugin configuration').'</span>' => ''
			))
	);

	# Message
	if (!empty($_GET['done'])){
		dcPage::success(__('Plugin successfully configured.'));
	}

	# Display previously gathered content
	$list->getConfigurationContent();

	dcPage::close();

	# Stop reading code here
	return;
}

# -- Execute actions --
try {
	$list->doActions('plugins');
}
catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# -- Plugin install --
$plugins_install = null;
if (!$core->error->flag()) {
	$plugins_install = $core->plugins->installModules();
}

# -- Page header --
dcPage::open(__('Plugins management'),
	dcPage::jsLoad('js/_plugins.js').
	dcPage::jsPageTabs().

	# --BEHAVIOR-- pluginsToolsHeaders
	$core->callBehavior('pluginsToolsHeaders', $core, false),

	dcPage::breadcrumb(
		array(
			__('System') => '',
			__('Plugins management') => ''
		))
);

# -- Succes messages --
if (!empty($_GET['msg'])) {
	$list->displayMessage($_GET['msg']);
}

# -- Plugins install messages --
if (!empty($plugins_install['success'])) {
	echo 
	'<div class="static-msg">'.__('Following plugins have been installed:').'<ul>';

	foreach ($plugins_install['success'] as $k => $v) {
		echo 
		'<li>'.$k.'</li>';
	}
	echo 

	'</ul></div>';
}
if (!empty($plugins_install['failure'])) {
	echo 
	'<div class="error">'.__('Following plugins have not been installed:').'<ul>';

	foreach ($plugins_install['failure'] as $k => $v) {
		echo 
		'<li>'.$k.' ('.$v.')</li>';
	}

	echo 
	'</ul></div>';
}

# -- Display modules lists --
if ($core->auth->isSuperAdmin() && $list->isPathWritable()) {

	# Updated modules from repo
	$modules = $list->repository->get(true);
	if (!empty($modules)) {
		echo 
		'<div class="multi-part" id="update" title="'.html::escapeHTML(__('Update plugins')).'">'.
		'<h3>'.html::escapeHTML(__('Update plugins')).'</h3>'.
		'<p>'.sprintf(
			__('There is one plugin to update available from %2$s.', 'There are %s plugins to update available from %s.', count($modules)),
			count($modules),
			'<a href="http://dotaddict.org/dc2/plugins">Dotaddict</a>'
		).'</p>';

		$list
			->newList('plugin-update')
			->setModules($modules)
			->setPageTab('update')
			->displayModulesList(
				/*cols */		array('icon', 'name', 'version', 'current_version', 'desc'),
				/* actions */	array('update')
			);

		echo
		'</div>';
	}
}

# List all active plugins
echo
'<div class="multi-part" id="plugins" title="'.__('Installed plugins').'">';

$modules = $list->modules->getModules();
if (!empty($modules)) {
	echo
	'<h3>'.__('Activated plugins').'</h3>'.
	'<p>'.__('Manage installed plugins from this list.').'</p>';

	$list
		->newList('plugin-activate')
		->setModules($modules)
		->setPageTab('plugins')
		->displayModulesList(
			/* cols */		array('expander', 'icon', 'name', 'config', 'version', 'desc', 'distrib'),
			/* actions */	array('deactivate', 'delete')
		);
}

# Deactivated modules
$modules = $list->modules->getDisabledModules();
if (!empty($modules)) {
	echo
	'<h3>'.__('Deactivated plugins').'</h3>'.
	'<p>'.__('Deactivated plugins are installed but not usable. You can activate them from here.').'</p>';

	$list
		->newList('plugin-deactivate')
		->setModules($modules)
		->setPageTab('plugins')
		->displayModulesList(
			/* cols */		array('icon', 'name', 'distrib'),
			/* actions */	array('activate', 'delete')
		);
}

echo 
'</div>';

if ($core->auth->isSuperAdmin() && $list->isPathWritable()) {

	# New modules from repo
	$search = $list->getSearchQuery();
	$modules = $search ? $list->repository->search($search) : $list->repository->get();

	if (!empty($search) || !empty($modules)) {
		echo
		'<div class="multi-part" id="new" title="'.__('Add plugins from Dotaddict').'">'.
		'<h3>'.__('Add plugins from Dotaddict repository').'</h3>';

		$list
			->newList('plugin-new')
			->setModules($modules)
			->setPageTab('new')
			->displaySearchForm()
			->displayNavMenu()
			->displayModulesList(
				/* cols */		array('expander', 'name', 'version', 'desc'),
				/* actions */	array('install'),
				/* nav limit */	true
			);

		echo
		'<p class="info vertical-separator">'.sprintf(
			__("Visit %s repository, the resources center for Dotclear."),
			'<a href="http://dotaddict.org/dc2/plugins">Dotaddict</a>'
			).
		'</p>'.

		'</div>';
	}

	# Add a new plugin
	echo
	'<div class="multi-part" id="addplugin" title="'.__('Install or upgrade manually').'">'.
	'<p>'.__('You can install plugins by uploading or downloading zip files.').'</p>';

	$list->displayManualForm();

	echo
	'</div>';
}

# --BEHAVIOR-- pluginsToolsTabs
$core->callBehavior('pluginsToolsTabs', $core);

# -- Notice for super admin --
if ($core->auth->isSuperAdmin() && !$list->isPathWritable()) {
	echo 
	'<p class="warning">'.__('Some functions are disabled, please give write access to your plugins directory to enable them.').'</p>';
}

dcPage::close();
