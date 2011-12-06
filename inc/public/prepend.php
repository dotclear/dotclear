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

if (!empty($_GET['pf'])) {
	require dirname(__FILE__).'/../load_plugin_file.php';
	exit;
}

if (!isset($_SERVER['PATH_INFO'])) {
	$_SERVER['PATH_INFO'] = '';
}

require_once dirname(__FILE__).'/../prepend.php';
require_once dirname(__FILE__).'/rs.extension.php';

# Loading blog
if (defined('DC_BLOG_ID')) {
	try {
		$core->setBlog(DC_BLOG_ID);
	} catch (Exception $e) {
		init_prepend_l10n();
		__error(__('Database problem')
			,DC_DEBUG ?
				__('The following error was encountered while trying to read the database:').'</p><ul><li>'.$e->getMessage().'</li></ul>' :
				__('Something went wrong while trying to read the database.')
			,20);
	}
}

if ($core->blog->id == null) {
	__error(__('Blog is not defined.')
		,__('Did you change your Blog ID?')
		,30);
}

if ((boolean)!$core->blog->status) {
	$core->unsetBlog();
	__error(__('Blog is offline.')
		,__('This blog is offline. Please try again later.')
		,70);
}

# Loading media
try {
	$core->media = new dcMedia($core);
} catch (Exception $e) {}

# Creating template context
$_ctx = new context();
try {
	$core->tpl = new dcTemplate(DC_TPL_CACHE,'$core->tpl',$core);
} catch (Exception $e) {
	__error(__('Can\'t create template files.')
		,$e->getMessage()
		,40);
}

# Loading locales
$_lang = $core->blog->settings->system->lang;
$_lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$_lang) ? $_lang : 'en';

if (l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/date') === false && $_lang != 'en') {
	l10n::set(dirname(__FILE__).'/../../locales/en/date');
}
l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/public');
l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/plugins');

# Loading plugins
try {
	$core->plugins->loadModules(DC_PLUGINS_ROOT,'public',$_lang);
} catch (Exception $e) {}

# Loading themes
$core->themes = new dcThemes($core);
$core->themes->loadModules($core->blog->themes_path);

# Defining theme if not defined
if (!isset($__theme)) {
	$__theme = $core->blog->settings->system->theme;
}

if (!$core->themes->moduleExists($__theme)) {
	$__theme = $core->blog->settings->system->theme = 'default';
}

$__parent_theme = $core->themes->moduleInfo($__theme,'parent');
if ($__parent_theme) {
	if (!$core->themes->moduleExists($__parent_theme)) {
		$__theme = $core->blog->settings->system->theme = 'default';
		$__parent_theme = null;
	}
}
	
# If theme doesn't exist, stop everything
if (!$core->themes->moduleExists($__theme)) {
	__error(__('Default theme not found.')
		,__('This either means you removed your default theme or set a wrong theme '.
		'path in your blog configuration. Please check theme_path value in '.
		'about:config module or reinstall default theme. ('.$__theme.')')
		,50);
}

# Loading _public.php file for selected theme
$core->themes->loadNsFile($__theme,'public');

# Loading translations for selected theme
if ($__parent_theme) {
	$core->themes->loadModuleL10N($__parent_theme,$_lang,'main');
}
$core->themes->loadModuleL10N($__theme,$_lang,'main');

# --BEHAVIOR-- publicPrepend
$core->callBehavior('publicPrepend',$core);

# Prepare the HTTP cache thing
$mod_files = get_included_files();
$mod_ts = array();
$mod_ts[] = $core->blog->upddt;

$__theme_tpl_path = array(
	$core->blog->themes_path.'/'.$__theme.'/tpl'
);
if ($__parent_theme) {
	$__theme_tpl_path[] = $core->blog->themes_path.'/'.$__parent_theme.'/tpl';
}

$core->tpl->setPath(
	$__theme_tpl_path,
	dirname(__FILE__).'/default-templates',
	$core->tpl->getPath());

$core->url->mode = $core->blog->settings->system->url_scan;

try {
	# --BEHAVIOR-- publicBeforeDocument
	$core->callBehavior('publicBeforeDocument',$core);
	
	$core->url->getDocument();
	
	# --BEHAVIOR-- publicAfterDocument
	$core->callBehavior('publicAfterDocument',$core);
} catch (Exception $e) {
	__error($e->getMessage()
		,__('Something went wrong while loading template file for your blog.')
		,60);
}
?>