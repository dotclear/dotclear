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
# --------------------------------------------------

# -- Repository helper --
$repository = new dcRepository(
	$core->plugins, 
	$core->blog->settings->system->repository_plugin_url
);
$repository->check();

# -- Page helper --
$list = new adminModulesList(
	$core, 
	DC_PLUGINS_ROOT,
	$core->blog->settings->system->plugins_allow_multi_install
);

# -- Execute actions --
if ($core->auth->isSuperAdmin() && $list->isPathWritable()) {

	# Plugin upload
	if ((!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])) ||
		(!empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url'])))
	{
		try
		{
			if (empty($_POST['your_pwd']) || !$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY, $_POST['your_pwd']))) {
				throw new Exception(__('Password verification failed'));
			}
			
			if (!empty($_POST['upload_pkg'])) {
				files::uploadStatus($_FILES['pkg_file']);
				
				$dest = $list->getPath().'/'.$_FILES['pkg_file']['name'];
				if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'], $dest)) {
					throw new Exception(__('Unable to move uploaded file.'));
				}
			}
			else {
				$url = urldecode($_POST['pkg_url']);
				$dest = $list->getPath().'/'.basename($url);
				$repository->download($url, $dest);
			}

			# --BEHAVIOR-- pluginBeforeAdd
			$core->callBehavior('pluginsBeforeAdd', $plugin_id);
						
			$ret_code = $core->plugins->installPackage($dest, $core->plugins);

			# --BEHAVIOR-- pluginAfterAdd
			$core->callBehavior('pluginsAfterAdd', $plugin_id);
			
			http::redirect('plugins.php?msg='.($ret_code == 2 ? 'update' : 'install').'#plugins');
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	elseif (!empty($_POST['module'])) {
		try {
			$list->executeAction('plugins', $core->plugins, $repository);
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
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
	$core->callBehavior('pluginsToolsHeaders', $core),

	dcPage::breadcrumb(
		array(
			__('System') => '',
			'<span class="page-title">'.__('Plugins management').'</span>' => ''
		))
);

# -- Succes messages --
if (!empty($_GET['msg'])) {
	$list->displayMessage($_GET['msg'],__('Plugins'));
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
	$modules = $repository->get(true);
	if (!empty($modules)) {
		echo 
		'<div class="multi-part" id="update" title="'.html::escapeHTML(__('Update plugins')).'">'.
		'<h3>'.html::escapeHTML(__('Update plugins')).'</h3>'.
		'<p>'.sprintf(
			__('There is one plugin to update available from %2$s.', 'There are %s plugins to update available from %s.', count($modules)),
			count($modules),
			'<a title="'.__('Visit Dotaddict').'" href="http://dotaddict.org/dc2/plugins">dotaddict.org</a>'
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

$modules = $core->plugins->getModules();
if (!empty($modules)) {

	echo
	'<h3>'.__('Activated plugins').'</h3>'.
	'<p>'.__('You can manage installed plugins from this list.').'</p>';

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
$modules = $core->plugins->getDisabledModules();
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
	$modules = $search ? $repository->search($search) : $repository->get();

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
	'<div class="info">'.
	'<p>'.sprintf(
		__("Visit %s repository, the resources center for Dotclear."),
		'<a href="http://dotaddict.org/dc2/plugins">Dotaddict</a>'
	).'</p></div>'.
	'</div>';

	# Add a new plugin
	echo
	'<div class="multi-part" id="addplugin" title="'.__('Install or upgrade manually').'">';

	echo '<p>'.__('You can install plugins by uploading or downloading zip files.').'</p>';
	
	# 'Upload plugin' form
	echo
	'<form method="post" action="plugins.php" id="uploadpkg" enctype="multipart/form-data" class="fieldset">'.
	'<h4>'.__('Upload a zip file').'</h4>'.
	'<p class="field"><label for="pkg_file" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Plugin zip file:').'</label> '.
	'<input type="file" id="pkg_file" name="pkg_file" /></p>'.
	'<p class="field"><label for="your_pwd1" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
	form::password(array('your_pwd','your_pwd1'),20,255).'</p>'.
	'<p><input type="submit" name="upload_pkg" value="'.__('Upload plugin').'" />'.
	$core->formNonce().
	'</p>'.
	'</form>';
	
	# 'Fetch plugin' form
	echo
	'<form method="post" action="plugins.php" id="fetchpkg" class="fieldset">'.
	'<h4>'.__('Download a zip file').'</h4>'.
	'<p class="field"><label for="pkg_url" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Plugin zip file URL:').'</label> '.
	form::field(array('pkg_url','pkg_url'),40,255).'</p>'.
	'<p class="field"><label for="your_pwd2" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
	form::password(array('your_pwd','your_pwd2'),20,255).'</p>'.
	'<p><input type="submit" name="fetch_pkg" value="'.__('Download plugin').'" />'.
	$core->formNonce().'</p>'.
	'</form>';

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
?>