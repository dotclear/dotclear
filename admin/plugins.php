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

dcPage::checkSuper();

$default_tab = !empty($_REQUEST['tab']) ? html::escapeHTML($_REQUEST['tab']) : 'plugins';

$p_paths = explode(PATH_SEPARATOR, DC_PLUGINS_ROOT);
$p_path = array_pop($p_paths);
unset($p_paths);

$is_writable = false;
if (is_dir($p_path) && is_writeable($p_path)) {
	$is_writable = true;
	$p_path_pat = preg_quote($p_path,'!');
}

$plugin_id = !empty($_POST['plugin_id']) ? $_POST['plugin_id'] : null;

if ($is_writable)
{
	# Delete plugin
	if ($plugin_id && !empty($_POST['delete']))
	{
		try
		{
			if (empty($_POST['deactivated']))
			{
				if (!$core->plugins->moduleExists($plugin_id)) {
					throw new Exception(__('No such plugin.'));
				}
				
				$plugin = $core->plugins->getModules($plugin_id);
				$plugin['id'] = $plugin_id;
				
				if (!preg_match('!^'.$p_path_pat.'!', $plugin['root'])) {
					throw new Exception(__('You don\'t have permissions to delete this plugin.'));
				}
				
				# --BEHAVIOR-- pluginBeforeDelete
				$core->callBehavior('pluginsBeforeDelete', $plugin);
				
				$core->plugins->deleteModule($plugin_id);
				
				# --BEHAVIOR-- pluginAfterDelete
				$core->callBehavior('pluginsAfterDelete', $plugin);
			}
			else
			{
				$core->plugins->deleteModule($plugin_id,true);
			}
			
			http::redirect('plugins.php?removed=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	# Deactivate plugin
	elseif ($plugin_id && !empty($_POST['deactivate']))
	{
		try
		{
			if (!$core->plugins->moduleExists($plugin_id)) {
				throw new Exception(__('No such plugin.'));
			}
			
			$plugin = $core->plugins->getModules($plugin_id);
			$plugin['id'] = $plugin_id;
			
			if (!$plugin['root_writable']) {
				throw new Exception(__('You don\'t have permissions to deactivate this plugin.'));
			}
			
			# --BEHAVIOR-- pluginBeforeDeactivate
			$core->callBehavior('pluginsBeforeDeactivate', $plugin);
				
			$core->plugins->deactivateModule($plugin_id);

			# --BEHAVIOR-- pluginAfterDeactivate
			$core->callBehavior('pluginsAfterDeactivate', $plugin);
				
			http::redirect('plugins.php');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	# Activate plugin
	elseif ($plugin_id && !empty($_POST['activate']))
	{
		try
		{
			$p = $core->plugins->getDisabledModules();
			if (!isset($p[$plugin_id])) {
				throw new Exception(__('No such plugin.'));
			}

			# --BEHAVIOR-- pluginBeforeActivate
			$core->callBehavior('pluginsBeforeActivate', $plugin_id);
			
			$core->plugins->activateModule($plugin_id);

			# --BEHAVIOR-- pluginAfterActivate
			$core->callBehavior('pluginsAfterActivate', $plugin_id);
			
			http::redirect('plugins.php');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	# Plugin upload
	elseif ((!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])) ||
		(!empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url'])))
	{
		try
		{
			if (empty($_POST['your_pwd']) || !$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['your_pwd']))) {
				throw new Exception(__('Password verification failed'));
			}
			
			if (!empty($_POST['upload_pkg']))
			{
				files::uploadStatus($_FILES['pkg_file']);
				
				$dest = $p_path.'/'.$_FILES['pkg_file']['name'];
				if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'],$dest)) {
					throw new Exception(__('Unable to move uploaded file.'));
				}
			}
			else
			{
				$url = urldecode($_POST['pkg_url']);
				$dest = $p_path.'/'.basename($url);
				
				try
				{
					$client = netHttp::initClient($url,$path);
					$client->setUserAgent('Dotclear - http://www.dotclear.org/');
					$client->useGzip(false);
					$client->setPersistReferers(false);
					$client->setOutput($dest);
					$client->get($path);
				}
				catch( Exception $e)
				{
					throw new Exception(__('An error occurred while downloading the file.'));
				}
				
				unset($client);
			}

			# --BEHAVIOR-- pluginBeforeAdd
			$core->callBehavior('pluginsBeforeAdd', $plugin_id);
						
			$ret_code = $core->plugins->installPackage($dest,$core->plugins);

			# --BEHAVIOR-- pluginAfterAdd
			$core->callBehavior('pluginsAfterAdd', $plugin_id);
			
			http::redirect('plugins.php?added='.$ret_code);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
			$default_tab = 'addplugin';
		}
	}
}

# Plugin install
$plugins_install = $core->plugins->installModules();

/* DISPLAY Main page
-------------------------------------------------------- */
dcPage::open(__('Plugins management'),
	dcPage::jsLoad('js/_plugins.js').
	dcPage::jsPageTabs($default_tab),
	dcPage::breadcrumb(
		array(
			__('System') => '',
			'<span class="page-title">'.__('Plugins management').'</span>' => ''
		))
);

if (!empty($_GET['removed'])) {
	dcPage::success(__('Plugin has been successfully deleted.'));
}
if (!empty($_GET['added'])) {
	dcPage::success(($_GET['added'] == 2 ? __('Plugin has been successfully upgraded') : __('Plugin has been successfully installed.')));
}

# Plugins install messages
if (!empty($plugins_install['success']))
{
	echo '<div class="static-msg">'.__('Following plugins have been installed:').'<ul>';
	foreach ($plugins_install['success'] as $k => $v) {
		echo '<li>'.$k.'</li>';
	}
	echo '</ul></div>';
}
if (!empty($plugins_install['failure']))
{
	echo '<div class="error">'.__('Following plugins have not been installed:').'<ul>';
	foreach ($plugins_install['failure'] as $k => $v) {
		echo '<li>'.$k.' ('.$v.')</li>';
	}
	echo '</ul></div>';
}

# List all active plugins
echo '<p>'.__('Plugins add new functionalities to Dotclear. '.
'Here you can activate or deactivate installed plugins.').'</p>';

echo (!$core->plugins->moduleExists('daInstaller') ?
	sprintf('<p><strong>'.__('You can find additional plugins for your blog on %s.').'</strong></p>',
		'<a href="http://plugins.dotaddict.org/dc2/">Dotaddict</a>') :
	sprintf('<p><strong>'.__('You can find additional plugins for your blog on %s or using the %s.').'</strong></p>',
		'<a href="http://plugins.dotaddict.org/dc2/">Dotaddict</a>',
		'<a href="plugin.php?p=daInstaller">'.__('DotAddict.org Installer').'</a>'));

if ($is_writable) {
	echo '<p>'.__('To install or upgrade a plugin you generally just need to upload it '.
	'in "Install or upgrade a plugin" section.');
} else {
	echo '<p>'.__('To install or upgrade a plugin you just need to extract it in your plugins directory.');
}
echo '</p>';

echo
'<div class="multi-part" id="plugins" title="'.__('Plugins').'">';

$p_available = $core->plugins->getModules();
uasort($p_available,create_function('$a,$b','return strcasecmp($a["name"],$b["name"]);'));
if (!empty($p_available)) 
{
	echo
	'<h3>'.__('Activated plugins').'</h3>'.
	'<table class="table-outer clear">'.
	'<table class="plugins"><tr>'.
	'<th>'.__('Plugin').'</th>'.
	'<th class="nowrap">'.__('Version').'</th>'.
	'<th class="nowrap">'.__('Details').'</th>'.
	'<th class="nowrap">'.__('Action').'</th>'.
	'</tr>';
	
	$distrib_plugins = array('aboutConfig','akismet','antispam','attachments','blogroll','blowupConfig','daInstaller',
		'fairTrackbacks','importExport','maintenance','pages','pings','simpleMenu','tags','themeEditor','userPref','widgets');
	$distrib_img = '<img src="images/dotclear_pw.png"'.
		' alt="'.__('Plugin from official distribution').'" title="'.__('Plugin from official distribution').'" />';

	foreach ($p_available as $k => $v)
	{
		$is_deletable = $is_writable && preg_match('!^'.$p_path_pat.'!',$v['root']);
		$is_deactivable = $v['root_writable'];
		$is_distrib = in_array($k, $distrib_plugins);
		
		echo
		'<tr class="line wide">'.
		'<td class="minimal nowrap"><strong>'.html::escapeHTML($k).'</strong></td>'.
		'<td class="minimal">'.html::escapeHTML($v['version']).'</td>'.
		'<td class="maximal'.($is_distrib ? ' distrib' : '').'"><strong>'.html::escapeHTML(__($v['name'])).'</strong> '.
		'<br />'.html::escapeHTML(__($v['desc'])).($is_distrib ? ' '.$distrib_img : '').'</td>'.
		'<td class="nowrap action">';
		
		if ($is_deletable || $is_deactivable)
		{
			echo
			'<form action="plugins.php" method="post">'.
			'<div>'.
			$core->formNonce().
			form::hidden(array('plugin_id'),html::escapeHTML($k)).
			($is_deactivable ? '<input type="submit" name="deactivate" value="'.__('Deactivate').'" /> ' : '').
			($is_deletable ? '<input type="submit" class="delete" name="delete" value="'.__('Delete').'" /> ' : '').
			'</div>'.
			'</form>';
		}
		
		echo
		'</td>'.
		'</tr>';
	}
	echo
	'</table></div>';
}

$p_disabled = $core->plugins->getDisabledModules();
uksort($p_disabled,create_function('$a,$b','return strcasecmp($a,$b);'));
if (!empty($p_disabled))
{
	echo
	'<h3>'.__('Deactivated plugins').'</h3>'.
	'<table class="clear table-outer">'.
	'<table class="plugins"><tr>'.
	'<th>'.__('Plugin').'</th>'.
	'<th class="nowrap">'.__('Action').'</th>'.
	'</tr>';
	
	foreach ($p_disabled as $k => $v)
	{
		$is_deletable = $is_writable && preg_match('!^'.$p_path_pat.'!',$v['root']);
		$is_activable = $v['root_writable'];
		
		echo
		'<tr class="line wide">'.
		'<td class="maximal nowrap"><strong>'.html::escapeHTML($k).'</strong></td>'.
		'<td class="nowrap action">';
		
		if ($is_deletable || $is_activable)
		{
			echo
			'<form action="plugins.php" method="post">'.
			'<div>'.
			$core->formNonce().
			form::hidden(array('plugin_id'),html::escapeHTML($k)).
			form::hidden(array('deactivated'),1).
			($is_activable ? '<input type="submit" name="activate" value="'.__('Activate').'" /> ' : '').
			($is_deletable ? '<input type="submit" class="delete" name="delete" value="'.__('Delete').'" /> ' : '').
			'</div>'.
			'</form>';
		}
		
		echo
		'</td>'.
		'</tr>';
	}
	echo
	'</table></div>';
}

echo '</div>';

# Add a new plugin
echo
'<div class="multi-part" id="addplugin" title="'.__('Install or upgrade a plugin').'">';

if ($is_writable)
{
	echo '<p>'.__('You can install plugins by uploading or downloading zip files.').'</p>';
	
	# 'Upload plugin' form
	echo
	'<form method="post" action="plugins.php" id="uploadpkg" enctype="multipart/form-data" class="fieldset">'.
	'<h3>'.__('Upload a zip file').'</h3>'.
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
	'<h3>'.__('Download a zip file').'</h3>'.
	'<p class="field"><label for="pkg_url" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Plugin zip file URL:').'</label> '.
	form::field(array('pkg_url','pkg_url'),40,255).'</p>'.
	'<p class="field"><label for="your_pwd2" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').'</label> '.
	form::password(array('your_pwd','your_pwd2'),20,255).'</p>'.
	'<p><input type="submit" name="fetch_pkg" value="'.__('Download plugin').'" />'.
	$core->formNonce().'</p>'.
	'</form>';
}
else
{
	echo
	'<p class="static-msg">'.
	__('To enable this function, please give write access to your plugins directory.').
	'</p>';
}
echo '</div>';

# --BEHAVIOR-- pluginsToolsTabs
$core->callBehavior('pluginsToolsTabs',$core);

dcPage::close();
?>