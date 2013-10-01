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

# --------------------------------------------------
# @todo Add settings to Dotclear update features
if ($core->blog->settings->system->repository_theme_url === null) {
	$core->blog->settings->system->put(
		'repository_theme_url', 'http://update.dotaddict.org/dc2/themes.xml', 'string', 'Themes XML feed location', true, true
	);
}
# --------------------------------------------------

# -- Loading themes --
$core->themes = new dcThemes($core);
$core->themes->loadModules($core->blog->themes_path, null);

# -- Repository helper --
$repository = new dcRepository(
	$core->themes, 
	$core->blog->settings->system->repository_theme_url
);
$repository->check();

# -- Page helper --
$list = new adminThemesList(
	$core, 
	$core->blog->themes_path,
	false
);

# -- Theme screenshot --
if (!empty($_GET['shot']) && $core->themes->moduleExists($_GET['shot']))
{
	if (empty($_GET['src'])) {
		$f = $core->blog->themes_path.'/'.$_GET['shot'].'/screenshot.jpg';
	} else {
		$f = $core->blog->themes_path.'/'.$_GET['shot'].'/'.path::clean($_GET['src']);
	}
	
	$f = path::real($f);
	
	if (!file_exists($f)) {
		$f = dirname(__FILE__).'/images/noscreenshot.png';
	}
	
	http::cache(array_merge(array($f),get_included_files()));
	
	header('Content-Type: '.files::getMimeType($f));
	header('Content-Length: '.filesize($f));
	readfile($f);
	
	exit;
}

# -- Display module configuration page --
if ($list->setConfigurationFile($core->themes, $core->blog->settings->system->theme)) {

	# Get content before page headers
	include $list->getConfigurationFile();

	# Gather content
	$list->setConfigurationContent();

	# Display page
	dcPage::open(__('Blog appearance'),
		dcPage::jsPageTabs().
		dcPage::jsColorPicker().

		# --BEHAVIOR-- themesToolsHeaders
		$core->callBehavior('themesToolsHeaders', $core, true),

		dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				__('Blog appearance') => 'blog_theme.php',
				'<span class="page-title">'.__('Theme configuration').'</span>' => ''
			))
	);

	if (!empty($_GET['done'])){
		dcPage::success(__('Theme successfully configured.'));
	}

	# Display previously gathered content
	$list->getConfigurationContent();

	dcPage::close();

	# Stop reading code here
	return;
}

# -- Execute actions --
if (!empty($_POST) && empty($_REQUEST['conf']) && $core->auth->isSuperAdmin() && $list->isPathWritable()) {
	try {
		$list->executeAction('themes', $core->themes, $repository);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# -- Page header --
dcPage::open(__('Themes management'),
	dcPage::jsLoad('js/_blog_theme.js').
	dcPage::jsPageTabs().
	dcPage::jsColorPicker(),

	# --BEHAVIOR-- themesToolsHeaders
	$core->callBehavior('themesToolsHeaders', $core, false),

	dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Blog appearance').'</span>' => ''
		))
);

# -- Succes messages --
if (!empty($_GET['msg'])) {
	$list->displayMessage($_GET['msg']);
}

# -- Display modules lists --
if ($core->auth->isSuperAdmin() && $list->isPathWritable()) {

	# Updated modules from repo
	$modules = $repository->get(true);
	if (!empty($modules)) {
		echo 
		'<div class="multi-part" id="update" title="'.html::escapeHTML(__('Update themes')).'">'.
		'<h3>'.html::escapeHTML(__('Update themes')).'</h3>'.
		'<p>'.sprintf(
			__('There is one theme to update available from %2$s.', 'There are %s themes to update available from %s.', count($modules)),
			count($modules),
			'<a href="http://dotaddict.org/dc2/themes">Dotaddict</a>'
		).'</p>';

		$list
			->newList('theme-update')
			->setModules($modules)
			->setPageTab('update')
			->displayModulesList(
				/*cols */		array('sshot', 'name', 'desc', 'author', 'version', 'current_version', 'parent'),
				/* actions */	array('update')
			);

		echo
		'</div>';
	}
}

# List all active plugins
echo
'<div class="multi-part" id="themes" title="'.__('Installed themes').'">';

$modules = $core->themes->getModules();
if (!empty($modules)) {

	echo
	'<h3>'.__('Activated themes').'</h3>'.
	'<p>'.__('Manage installed themes from this list.').'</p>';

	$list
		->newList('theme-activate')
		->setModules($modules)
		->setPageTab('themes')
		->displayModulesList(
			/* cols */		array('sshot', 'name', 'config', 'desc', 'author', 'version', 'parent'),
			/* actions */	array('select', 'deactivate', 'delete')
		);
}

$modules = $core->themes->getDisabledModules();
if (!empty($modules)) {

	echo
	'<h3>'.__('Deactivated themes').'</h3>'.
	'<p>'.__('Deactivated themes are installed but not usable. You can activate them from here.').'</p>';

	$list
		->newList('theme-deactivate')
		->setModules($modules)
		->setPageTab('themes')
		->displayModulesList(
			/* cols */		array('name', 'distrib'),
			/* actions */	array('activate', 'delete')
		);
}

echo 
'</div>';

if ($core->auth->isSuperAdmin() && $list->isPathWritable()) {

	# New modules from repo
	$search = $list->getSearchQuery();
	$modules = $search ? $repository->search($search) : $repository->get();

	echo
	'<div class="multi-part" id="new" title="'.__('Add themes from Dotaddict').'">'.
	'<h3>'.__('Add themes from Dotaddict repository').'</h3>';

	$list
		->newList('theme-new')
		->setModules($modules)
		->setPageTab('new')
		->displaySearchForm()
		->displayNavMenu()
		->displayModulesList(
			/* cols */		array('expander', 'sshot', 'name', 'config', 'desc', 'author', 'version', 'parent', 'distrib'),
			/* actions */	array('install'),
			/* nav limit */	true
		);

	echo
	'<p class="info vertical-separator">'.sprintf(
		__("Visit %s repository, the resources center for Dotclear."),
		'<a href="http://dotaddict.org/dc2/themes">Dotaddict</a>'
		).
	'</p>'.

	'</div>';

	# Add a new plugin
	echo
	'<div class="multi-part" id="addtheme" title="'.__('Install or upgrade manually').'">';

	echo '<p>'.__('You can install themes by uploading or downloading zip files.').'</p>';
	
	$list->displayManualForm();

	echo
	'</div>';
}

dcPage::close();
?>