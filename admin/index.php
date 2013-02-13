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
	require dirname(__FILE__).'/../inc/load_plugin_file.php';
	exit;
}
if (!empty($_GET['tf'])) {
	define('DC_CONTEXT_ADMIN',true);
	require dirname(__FILE__).'/../inc/load_theme_file.php';
	exit;
}

require dirname(__FILE__).'/../inc/admin/prepend.php';

if (!empty($_GET['default_blog'])) {
	try {
		$core->setUserDefaultBlog($core->auth->userID(),$core->blog->id);
		http::redirect('index.php');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

dcPage::check('usage,contentadmin');

# Logout
if (!empty($_GET['logout'])) {
	$core->session->destroy();
	if (isset($_COOKIE['dc_admin'])) {
		unset($_COOKIE['dc_admin']);
		setcookie('dc_admin',false,-600,'','',DC_ADMIN_SSL);
	}
	http::redirect('auth.php');
	exit;
}

# Plugin install
$plugins_install = $core->plugins->installModules();

# Send plugins install messages to templates
if (!empty($plugins_install['success'])) {
	$_ctx->addMessagesList(__('Following plugins have been installed:'),$plugins_install['success']);
}
if (!empty($plugins_install['failure'])) {
	$_ctx->addMessagesList(__('Following plugins have not been installed:'),$plugins_install['failure']);
}

# Send plugins errors messages to templates
$_ctx->modules_errors = $core->auth->isSuperAdmin() ? $core->plugins->getErrors() : array();

# Send Dotclear updates notifications to tempaltes
$_ctx->updater = array();
if ($core->auth->isSuperAdmin() && is_readable(DC_DIGESTS)) {

	$updater = new dcUpdate(DC_UPDATE_URL,'dotclear',DC_UPDATE_VERSION,DC_TPL_CACHE.'/versions');
	$new_v = $updater->check(DC_VERSION);
	$version_info = $new_v ? $updater->getInfoURL() : '';
	
	if ($updater->getNotify() && $new_v) {
		$_ctx->updater = array(
			'new_version'	=> $new_v,
			'version_info'	=> $version_info
		);
	}
}

# Check dashboard module prefs
$ws = $core->auth->user_prefs->addWorkspace('dashboard');

# Doclinks prefs
if (!$core->auth->user_prefs->dashboard->prefExists('doclinks')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('doclinks',true)) {
		$core->auth->user_prefs->dashboard->put('doclinks',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('doclinks',true,'boolean');
}

# Send doclinks to templates
$_ctx->dashboard_doclinks = array();
if ($core->auth->user_prefs->dashboard->doclinks && !empty($__resources['doc'])) {
	$_ctx->dashboard_doclinks = $__resources['doc'];
}

# Dcnews prefs
if (!$core->auth->user_prefs->dashboard->prefExists('dcnews')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('dcnews',true)) {
		$core->auth->user_prefs->dashboard->put('dcnews',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('dcnews',true,'boolean');
}

# Send dcnews to templates
$_ctx->dashboard_dcnews = array();
if ($core->auth->user_prefs->dashboard->dcnews && !empty($__resources['rss_news'])) {
	try
	{
		$feed_reader = new feedReader;
		$feed_reader->setCacheDir(DC_TPL_CACHE);
		$feed_reader->setTimeout(2);
		$feed_reader->setUserAgent('Dotclear - http://www.dotclear.org/');
		$feed = $feed_reader->parse($__resources['rss_news']);
		if ($feed) {
			$items = array();
			$i = 1;
			foreach ($feed->items as $item) {
				$items[] = array(
					'title' => $item->title,
					'link' => isset($item->link) ? $item->link : '',
					'date' => dt::dt2str(__('%d %B %Y'),$item->pubdate,'Europe/Paris'),
					'content' => html::clean($item->content)
				);
				$i++;
				if ($i > 3) { break; }
			}
			$_ctx->dashboard_dcnews = $items;
		}
	}
	catch (Exception $e) {}
}

# Quick entry prefs
if (!$core->auth->user_prefs->dashboard->prefExists('quickentry')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('quickentry',true)) {
		$core->auth->user_prefs->dashboard->put('quickentry',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('quickentry',true,'boolean');
}

# Send quick entry to templates
$_ctx->dashboard_quickentry = false;
if ($core->auth->user_prefs->dashboard->quickentry &&$core->auth->check('usage,contentadmin',$core->blog->id))
{
	$categories_combo = array('&nbsp;' => '');
	try {
		$categories = $core->blog->getCategories(array('post_type'=>'post'));
		while ($categories->fetch()) {
			$categories_combo[$categories->cat_id] = 
				str_repeat('&nbsp;&nbsp;',$categories->level-1).
				($categories->level-1 == 0 ? '' : '&bull; ').
				html::escapeHTML($categories->cat_title);
		}
	} catch (Exception $e) { }
	
	$form = new dcForm($core,array('quickentry','quick-entry'),'post.php');
	$form
		->addField(
			new dcFieldText('post_title','', array(
				'size'		=> 20,
				'required'	=> true,
				'label'		=> __('Title'))))
		->addField(
			new dcFieldTextArea('post_content','', array(
				'required'	=> true,
				'label'		=> __("Content:"))))
		->addField(
			new dcFieldCombo('cat_id','',$categories_combo,array(
				"label" => __('Category:'))))
		->addField(
			new dcFieldSubmit('save',__('Save'),array(
				'action' => 'savePost')))
		->addField(
			new dcFieldHidden ('post_status',-2))
		->addField(
			new dcFieldHidden ('post_format',$core->auth->getOption('post_format')))
		->addField(
			new dcFieldHidden ('post_excerpt',''))
		->addField(
			new dcFieldHidden ('post_lang',$core->auth->getInfo('user_lang')))
		->addField(
			new dcFieldHidden ('post_notes',''))
	;
	if ($core->auth->check('publish',$core->blog->id)) {
		$form->addField(
			new dcFieldHidden ('save-publish',__('Save and publish')));
	}
	
	$_ctx->dashboard_quickentry = true;
}

# Dashboard icons
$__dashboard_icons = new ArrayObject();

# Dashboard favorites
$post_count = $core->blog->getPosts(array(),true)->f(0);
$str_entries = ($post_count > 1) ? __('%d entries') : __('%d entry');

$comment_count = $core->blog->getComments(array(),true)->f(0);
$str_comments = ($comment_count > 1) ? __('%d comments') : __('%d comment');

$ws = $core->auth->user_prefs->addWorkspace('favorites');
$count = 0;
foreach ($ws->dumpPrefs() as $k => $v) {
	// User favorites only
	if (!$v['global']) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			if (dc_valid_fav($fav['url'])) {
				$count++;
				$title = ($fav['name'] == 'posts' ? sprintf($str_entries,$post_count) : 
					($fav['name'] == 'comments' ? sprintf($str_comments,$comment_count) : $fav['title']));
				$__dashboard_icons[$fav['name']] = new ArrayObject(array(__($title),$fav['url'],$fav['large-icon']));

				# Let plugins set their own title for favorite on dashboard
				$core->callBehavior('adminDashboardFavsIcon',$core,$fav['name'],$__dashboard_icons[$fav['name']]);
			}
		}
	}
}	
if (!$count) {
	// Global favorites if any
	foreach ($ws->dumpPrefs() as $k => $v) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			if (dc_valid_fav($fav['url'])) {
				$count++;
				$title = ($fav['name'] == 'posts' ? sprintf($str_entries,$post_count) : 
					($fav['name'] == 'comments' ? sprintf($str_comments,$comment_count) : $fav['title']));
				$__dashboard_icons[$fav['name']] = new ArrayObject(array(__($title),$fav['url'],$fav['large-icon']));

				# Let plugins set their own title for favorite on dashboard
				$core->callBehavior('adminDashboardFavsIcon',$core,$fav['name'],$__dashboard_icons[$fav['name']]);
			}
		}
	}
}
if (!$count) {
	// No user or global favorites, add "user pref" and "new entry" fav
	if ($core->auth->check('usage,contentadmin',$core->blog->id)) {
		$__dashboard_icons['new_post'] = new ArrayObject(array(__('New entry'),'post.php','images/menu/edit-b.png'));
	}
	$__dashboard_icons['prefs'] = new ArrayObject(array(__('My preferences'),'preferences.php','images/menu/user-pref-b.png'));
}

# Send dashboard icons to templates
$icons = array();
foreach ($__dashboard_icons as $i) {
	$icons[] = array(
		'title' 	=> $i[0],
		'url' 	=> $i[1],
		'img' 	=> dc_admin_icon_url($i[2])
	);
}
$_ctx->dashboard_icons = $icons;

# Dashboard items
$__dashboard_items = new ArrayObject(array(new ArrayObject,new ArrayObject));
$core->callBehavior('adminDashboardItems', $core, $__dashboard_items);

# Send dashboard items to templates
$items = array();
foreach ($__dashboard_items as $i) {	
	if ($i->count() > 0) {
		foreach ($i as $v) {
			$items[] = $v;
		}
	}
}
$_ctx->dashboard_items = $items;

# Dashboard content
$__dashboard_contents = new ArrayObject(array(new ArrayObject,new ArrayObject));
$core->callBehavior('adminDashboardContents', $core, $__dashboard_contents);

# Send dashboard contents to templates
$contents = array();
foreach ($__dashboard_contents as $i) {	
	if ($i->count() > 0) {
		foreach ($i as $v) {
			$contents[] = $v;
		}
	}
}
$_ctx->dashboard_contents = $contents;

# Blog status message
if ($core->blog->status == 0) {
	$_ctx->addMessageStatic(__('This blog is offline'));
} elseif ($core->blog->status == -1) {
	$_ctx->addMessageStatic(__('This blog is removed'));
}

# Config errors messages
if (!defined('DC_ADMIN_URL') || !DC_ADMIN_URL) {
	$_ctx->addMessageStatic(
		sprintf(__('%s is not defined, you should edit your configuration file.'),'DC_ADMIN_URL').' '.
		__('See <a href="http://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.')
	);
}
if (!defined('DC_ADMIN_MAILFROM') || !DC_ADMIN_MAILFROM) {
	$_ctx->addMessageStatic(
		sprintf(__('%s is not defined, you should edit your configuration file.'),'DC_ADMIN_MAILFROM').' '.
		__('See <a href="http://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.')
	);
}

$_ctx->fillPageTitle(__('Dashboard'));
$core->tpl->display('index.html.twig');
?>