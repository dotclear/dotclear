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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('admin');

# Loading themes
$core->themes = new dcThemes($core);
$core->themes->loadModules($core->blog->themes_path,null);

# Theme screenshot
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

$can_install = $core->auth->isSuperAdmin();
$is_writable = is_dir($core->blog->themes_path) && is_writable($core->blog->themes_path);
$default_tab = 'themes-list';

# Selecting theme
if (!empty($_POST['theme']) && !empty($_POST['select']) && empty($_REQUEST['conf']))
{
	$core->blog->settings->addNamespace('system');
	$core->blog->settings->system->put('theme',$_POST['theme']);
	$core->blog->triggerBlog();
	http::redirect('blog_theme.php?upd=1');
}

if ($can_install && !empty($_POST['theme']) && !empty($_POST['remove']) && empty($_REQUEST['conf']))
{
	try
	{
		if ($_POST['theme'] == 'default') {
			throw new Exception(__('You can\'t remove default theme.'));
		}
		
		if (!$core->themes->moduleExists($_POST['theme'])) {
			throw new Exception(__('Theme does not exist.'));
		}
		
		$theme = $core->themes->getModules($_POST['theme']);
		
		# --BEHAVIOR-- themeBeforeDelete
		$core->callBehavior('themeBeforeDelete',$theme);
		
		$core->themes->deleteModule($_POST['theme']);
		
		# --BEHAVIOR-- themeAfterDelete
		$core->callBehavior('themeAfterDelete',$theme);
		
		http::redirect('blog_theme.php?del=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Theme upload
if ($can_install && $is_writable && ((!empty($_POST['upload_pkg']) && !empty($_FILES['pkg_file'])) ||
	(!empty($_POST['fetch_pkg']) && !empty($_POST['pkg_url']))))
{
	try
	{
		if (empty($_POST['your_pwd']) || !$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['your_pwd']))) {
			throw new Exception(__('Password verification failed'));
		}
		
		if (!empty($_POST['upload_pkg']))
		{
			files::uploadStatus($_FILES['pkg_file']);
			
			$dest = $core->blog->themes_path.'/'.$_FILES['pkg_file']['name'];
			if (!move_uploaded_file($_FILES['pkg_file']['tmp_name'],$dest)) {
				throw new Exception(__('Unable to move uploaded file.'));
			}
		}
		else
		{
			$url = urldecode($_POST['pkg_url']);
			$dest = $core->blog->themes_path.'/'.basename($url);
			
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
		
		$ret_code = dcModules::installPackage($dest,$core->themes);
		http::redirect('blog_theme.php?added='.$ret_code);
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
		$default_tab = 'add-theme';
	}
}

$theme_conf_mode = false;
if (!empty($_REQUEST['conf']))
{
	$theme_conf_file = path::real($core->blog->themes_path.'/'.$core->blog->settings->system->theme).'/_config.php';
	if (file_exists($theme_conf_file)) {
		$theme_conf_mode = true;
	}
}

function display_theme_details($id,$details,$current)
{
	global $core;
	
	$screenshot = 'images/noscreenshot.png';
	if (file_exists($core->blog->themes_path.'/'.$id.'/screenshot.jpg')) {
		$screenshot = 'blog_theme.php?shot='.rawurlencode($id);
	}
	
	$radio_id = 'theme_'.html::escapeHTML($id);
	if (preg_match('#^http(s)?://#',$core->blog->settings->system->themes_url)) {
		$theme_url = http::concatURL($core->blog->settings->system->themes_url,'/'.$id);
	} else {
		$theme_url = http::concatURL($core->blog->url,$core->blog->settings->system->themes_url.'/'.$id);
	}
	$has_conf = file_exists(path::real($core->blog->themes_path.'/'.$id).'/_config.php');
	$has_css = file_exists(path::real($core->blog->themes_path.'/'.$id).'/style.css');
	$parent = $core->themes->moduleInfo($id,'parent');
	$has_parent = (boolean)$parent;
	if ($has_parent) {
		$is_parent_present = $core->themes->moduleExists($parent);
	}
	
	$res =
	'<div class="theme-details'.($current ? ' current-theme' : '').'">'.
	'<div class="theme-shot"><img src="'.$screenshot.'" alt="" /></div>'.
	'<div class="theme-info">'.
		'<h3>'.form::radio(array('theme',$radio_id),html::escapeHTML($id),$current,'','',($has_parent && !$is_parent_present)).' '.
		'<label class="classic" for="'.$radio_id.'">'.
		html::escapeHTML($details['name']).'</label></h3>'.
		'<p><span class="theme-desc">'.html::escapeHTML($details['desc']).'</span> '.
		'<span class="theme-author">'.sprintf(__('by %s'),html::escapeHTML($details['author'])).'</span> '.
		'<span class="theme-version">'.sprintf(__('version %s'),html::escapeHTML($details['version'])).'</span> ';
		if ($has_parent) {
			if ($is_parent_present) {
				$res .= '<span class="theme-parent-ok">'.sprintf(__('(built on "%s")'),html::escapeHTML($parent)).'</span> ';
			} else {
				$res .= '<span class="theme-parent-missing">'.sprintf(__('(requires "%s")'),html::escapeHTML($parent)).'</span> ';
			}
		}
		if ($has_css) {
			$res .= '<a class="theme-css" href="'.$theme_url.'/style.css">'.__('Stylesheet').'</a>';
		}
		$res .= '</p>';
	$res .=
	'</div>'.
	'<div class="theme-actions">';
		if ($current && $has_conf) {
			$res .= '<p><a href="blog_theme.php?conf=1" class="button">'.__('Configure theme').'</a></p>';
		}
		if ($current) {
			# --BEHAVIOR-- adminCurrentThemeDetails
			$res .= $core->callBehavior('adminCurrentThemeDetails',$core,$id,$details);
		}
	$res .=
	'</div>'.
	'</div>';
	
	return $res;
}

dcPage::open(__('Blog appearance'),
	(!$theme_conf_mode ? dcPage::jsLoad('js/_blog_theme.js') : '').
	dcPage::jsPageTabs($default_tab).
	dcPage::jsColorPicker()
);

if (!$theme_conf_mode)
{
	echo
	'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Blog appearance').'</span></h2>';
	
	if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Theme has been successfully changed.').'</p>';
	}
	
	if (!empty($_GET['added'])) {
		echo '<p class="message">'.
		($_GET['added'] == 2 ? __('Theme has been successfully upgraded') : __('Theme has been successfully installed.')).
		'</p>';
	}
	
	if (!empty($_GET['del'])) {
		echo '<p class="message">'.__('Theme has been successfully deleted.').'</p>';
	}
	
	if ($can_install) {
		echo
		'<p><strong>'.sprintf(__('You can find additional themes for your blog on %s.'),
		'<a href="http://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>').'</strong> '.
		__('To install or upgrade a theme you generally just need to upload it '.
		'in "Install or upgrade a theme" section.').'</p>';
	}
	
	# Themes list
	echo '<div class="multi-part" id="themes-list" title="'.__('Themes').'">';
	
	$themes = $core->themes->getModules();
	if (isset($themes[$core->blog->settings->system->theme])) {
		echo '<h3>'.sprintf(__('You are currently using "%s"'),$themes[$core->blog->settings->system->theme]['name']).'</h3>';
	}
	
	echo
	'<form action="blog_theme.php" method="post" id="themes-form">'.
	'<div id="themes">';
	
	if (isset($themes[$core->blog->settings->system->theme])) {
		echo display_theme_details($core->blog->settings->system->theme,$themes[$core->blog->settings->system->theme],true);
	}
	
	foreach ($themes as $k => $v)
	{
		if ($core->blog->settings->system->theme == $k) { // Current theme
			continue;
		}
		echo display_theme_details($k,$v,false);
	}
	
	echo '</div>';
	
	echo
	'<div class="two-cols clear" id="themes-actions">'.
	$core->formNonce().
	'<p class="col"><input type="submit" name="select" value="'.__('Use selected theme').'" /></p>';
	
	if ($can_install) {
		echo '<p class="col right"><input type="submit" class="delete" name="remove" value="'.__('Delete selected theme').'" /></p>';
	}
	
	echo
	'</div>'.
	'</form>'.
	'</div>';
	
	# Add a new theme
	if ($can_install)
	{
		echo
		'<div class="multi-part clear" id="add-theme" title="'.__('Install or upgrade a theme').'">';
		
		if ($is_writable)
		{
			echo '<p>'.__('You can install themes by uploading or downloading zip files.').'</p>';
			
			# 'Upload theme' form
			echo
			'<form method="post" action="blog_theme.php" id="uploadpkg" enctype="multipart/form-data">'.
			'<fieldset>'.
			'<legend>'.__('Upload a zip file').'</legend>'.
			'<p class="field"><label for="pkg_file" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Theme zip file:').' '.
			'<input type="file" name="pkg_file" id="pkg_file" /></label></p>'.
			'<p class="field"><label for="your_pwd1" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').' '.
			form::password(array('your_pwd','your_pwd1'),20,255).'</label></p>'.
			'<input type="submit" name="upload_pkg" value="'.__('Upload theme').'" />'.
			$core->formNonce().
			'</fieldset>'.
			'</form>';
			
			# 'Fetch theme' form
			echo
			'<form method="post" action="blog_theme.php" id="fetchpkg">'.
			'<fieldset>'.
			'<legend>'.__('Download a zip file').'</legend>'.
			'<p class="field"><label for="pkg_url" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Theme zip file URL:').' '.
			form::field(array('pkg_url','pkg_url'),40,255).'</label></p>'.
			'<p class="field"><label for="your_pwd2" class="classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Your password:').' '.
			form::password(array('your_pwd','your_pwd2'),20,255).'</label></p>'.
			'<input type="submit" name="fetch_pkg" value="'.__('Download theme').'" />'.
			$core->formNonce().
			'</fieldset>'.
			'</form>';
		}
		else
		{
			echo
			'<p class="static-msg">'.
			__('To enable this function, please give write access to your themes directory.').
			'</p>';
		}
		echo '</div>';
	}
}
else
{
	$theme_name = $core->themes->moduleInfo($core->blog->settings->system->theme,'name');
	$core->themes->loadModuleL10Nresources($core->blog->settings->system->theme,$_lang);
	echo
	'<h2>'.html::escapeHTML($core->blog->name).
	' &rsaquo; <a href="blog_theme.php">'.__('Blog appearance').'</a> &rsaquo; <span class="page-title">'.__('Theme configuration').'<span class="page-title"></h2>'.
	'<p><a class="back" href="blog_theme.php">'.__('back').'</a></p>';
	
	try
	{
		# Let theme configuration set their own form(s) if required
		$standalone_config = (boolean) $core->themes->moduleInfo($core->blog->settings->system->theme,'standalone_config');

		if (!$standalone_config)
			echo '<form id="theme_config" action="blog_theme.php?conf=1" method="post" enctype="multipart/form-data">';

		include $theme_conf_file;

		if (!$standalone_config)
			echo
			'<p class="clear"><input type="submit" value="'.__('Save').'" />'.
			$core->formNonce().'</p>'.
			'</form>';

	}
	catch (Exception $e)
	{
		echo '<div class="error"><p>'.$e->getMessage().'</p></div>';
	}
}

dcPage::close();
?>