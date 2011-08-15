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

# Check dashboard module prefs
$ws = $core->auth->user_prefs->addWorkspace('dashboard');
if (!$core->auth->user_prefs->dashboard->prefExists('doclinks')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('doclinks',true)) {
		$core->auth->user_prefs->dashboard->put('doclinks',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('doclinks',true,'boolean');
}
if (!$core->auth->user_prefs->dashboard->prefExists('dcnews')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('dcnews',true)) {
		$core->auth->user_prefs->dashboard->put('dcnews',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('dcnews',true,'boolean');
}
if (!$core->auth->user_prefs->dashboard->prefExists('quickentry')) {
	if (!$core->auth->user_prefs->dashboard->prefExists('quickentry',true)) {
		$core->auth->user_prefs->dashboard->put('quickentry',true,'boolean','',null,true);
	}
	$core->auth->user_prefs->dashboard->put('quickentry',true,'boolean');
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
			$count++;
			$title = ($fav['name'] == 'posts' ? sprintf($str_entries,$post_count) : 
				($fav['name'] == 'comments' ? sprintf($str_comments,$comment_count) : $fav['title']));
			$__dashboard_icons[$fav['name']] = new ArrayObject(array(__($title),$fav['url'],$fav['large-icon']));

			# Let plugins set their own title for favorite on dashboard
			$core->callBehavior('adminDashboardFavsIcon',$core,$fav['name'],$__dashboard_icons[$fav['name']]);
		}
	}
}	
if (!$count) {
	// Global favorites if any
	foreach ($ws->dumpPrefs() as $k => $v) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			$count++;
			$title = ($fav['name'] == 'posts' ? sprintf($str_entries,$post_count) : 
				($fav['name'] == 'comments' ? sprintf($str_comments,$comment_count) : $fav['title']));
			$__dashboard_icons[$fav['name']] = new ArrayObject(array(__($title),$fav['url'],$fav['large-icon']));

			# Let plugins set their own title for favorite on dashboard
			$core->callBehavior('adminDashboardFavsIcon',$core,$fav['name'],$__dashboard_icons[$fav['name']]);
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

# Latest news for dashboard
$__dashboard_items = new ArrayObject(array(new ArrayObject,new ArrayObject));

# Documentation links
$dashboardItem = 0;
if ($core->auth->user_prefs->dashboard->doclinks) {
	if (!empty($__resources['doc']))
	{
		$doc_links = '<h3>'.__('Documentation and support').'</h3><ul>';
	
		foreach ($__resources['doc'] as $k => $v) {
			$doc_links .= '<li><a href="'.$v.'">'.$k.'</a></li>';
		}
	
		$doc_links .= '</ul>';
		$__dashboard_items[$dashboardItem][] = $doc_links;
		$dashboardItem++;
	}
}

if ($core->auth->user_prefs->dashboard->dcnews) {
	try
	{
		if (empty($__resources['rss_news'])) {
			throw new Exception();
		}
	
		$feed_reader = new feedReader;
		$feed_reader->setCacheDir(DC_TPL_CACHE);
		$feed_reader->setTimeout(2);
		$feed_reader->setUserAgent('Dotclear - http://www.dotclear.org/');
		$feed = $feed_reader->parse($__resources['rss_news']);
		if ($feed)
		{
			$latest_news = '<h3>'.__('Latest news').'</h3><dl id="news">';
			$i = 1;
			foreach ($feed->items as $item)
			{
				$dt = isset($item->link) ? '<a href="'.$item->link.'">'.$item->title.'</a>' : $item->title;
			
				if ($i < 3) {
					$latest_news .=
					'<dt>'.$dt.'</dt>'.
					'<dd><p><strong>'.dt::dt2str('%d %B %Y',$item->pubdate,'Europe/Paris').'</strong>: '.
					'<em>'.text::cutString(html::clean($item->content),120).'...</em></p></dd>';
				} else {
					$latest_news .=
					'<dt>'.$dt.'</dt>'.
					'<dd>'.dt::dt2str('%d %B %Y',$item->pubdate,'Europe/Paris').'</dd>';
				}
				$i++;
				if ($i > 3) { break; }
			}
			$latest_news .= '</dl>';
			$__dashboard_items[$dashboardItem][] = $latest_news;
			$dashboardItem++;
		}
	}
	catch (Exception $e) {}
}

$core->callBehavior('adminDashboardItems', $core, $__dashboard_items);

# Dashboard content
$dashboardContents = '';
$__dashboard_contents = new ArrayObject(array(new ArrayObject,new ArrayObject));
$core->callBehavior('adminDashboardContents', $core, $__dashboard_contents);

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('Dashboard'),
	dcPage::jsToolBar().
	dcPage::jsLoad('js/_index.js').
	# --BEHAVIOR-- adminDashboardHeaders
	$core->callBehavior('adminDashboardHeaders')
);

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Dashboard').'</span></h2>';

if ($core->auth->getInfo('user_default_blog') != $core->blog->id && $core->auth->blog_count > 1) {
	echo
	'<p><a href="index.php?default_blog=1" class="button">'.__('Make this blog my default blog').'</a></p>';
}

if ($core->blog->status == 0) {
	echo '<p class="static-msg">'.__('This blog is offline').'</p>';
} elseif ($core->blog->status == -1) {
	echo '<p class="static-msg">'.__('This blog is removed').'</p>';
}

if (!defined('DC_ADMIN_URL') || !DC_ADMIN_URL) {
	echo
	'<p class="static-msg">'.
	'DC_ADMIN_URL '.__('is not defined, you should edit your configuration file.').
	'</p>';
}

if (!defined('DC_ADMIN_MAILFROM') || !DC_ADMIN_MAILFROM) {
	echo
	'<p class="static-msg">'.
	'DC_ADMIN_MAILFROM '.__('is not defined, you should edit your configuration file.').
	'</p>';
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

# Dashboard columns (processed first, as we need to know the result before displaying the icons.)
$dashboardItems = '';

# Dotclear updates notifications
if ($core->auth->isSuperAdmin() && is_readable(DC_DIGESTS))
{
	$updater = new dcUpdate(DC_UPDATE_URL,'dotclear',DC_UPDATE_VERSION,DC_TPL_CACHE.'/versions');
	$new_v = $updater->check(DC_VERSION);
	$version_info = $new_v ? $updater->getInfoURL() : '';
	
	if ($updater->getNotify() && $new_v) {
		$dashboardItems .=
		'<div id="upg-notify" class="static-msg"><p>'.sprintf(__('Dotclear %s is available!'),$new_v).'</p> '.
		'<ul><li><strong><a href="update.php">'.sprintf(__('Upgrade now'),$new_v).'</a></strong>'.
		'</li><li><a href="update.php?hide_msg=1">'.__('Remind me later').'</a>'.
		($version_info ? ' </li><li><a href="'.$version_info.'">'.__('information about this version').'</a>' : '').
		'</li></ul></div>';
	}
}

# Errors modules notifications
if ($core->auth->isSuperAdmin())
{
	$list = array();
	foreach ($core->plugins->getErrors() as $k => $error) {
		$list[] = '<li>'.$error.'</li>';
	}
	
	if (count($list) > 0) {
		$dashboardItems .=
		'<div id="module-errors" class="error"><p>'.__('Some plugins are installed twice:').'</p> '.
		'<ul>'.implode("\n",$list).'</ul></div>';
	}
	
}

foreach ($__dashboard_items as $i)
{	
	if ($i->count() > 0)
	{
		$dashboardItems .= '<div>';
		foreach ($i as $v) {
			$dashboardItems .= $v;
		}
		$dashboardItems .= '</div>';
	}
}

# Dashboard icons
echo '<div id="dashboard-main"'.($dashboardItems ? '' : ' class="fullwidth"').'><div id="icons">';
foreach ($__dashboard_icons as $i)
{
	echo
	'<p><a href="'.$i[1].'"><img src="'.dc_admin_icon_url($i[2]).'" alt="" />'.
	'<br /><span>'.$i[0].'</span></a></p>';
}
echo '</div>';

if ($core->auth->user_prefs->dashboard->quickentry) {
	if ($core->auth->check('usage,contentadmin',$core->blog->id))
	{
		$categories_combo = array('&nbsp;' => '');
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
			while ($categories->fetch()) {
				$categories_combo[] = new formSelectOption(
					str_repeat('&nbsp;&nbsp;',$categories->level-1).
					($categories->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($categories->cat_title),
					$categories->cat_id
				);
			}
		} catch (Exception $e) { }
	
		echo
		'<div id="quick">'.
		'<h3>'.__('Quick entry').'</h3>'.
		'<form id="quick-entry" action="post.php" method="post">'.
		'<fieldset><legend>'.__('New entry').'</legend>'.
		'<p class="col"><label for="post_title" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').
		form::field('post_title',20,255,'','maximal').
		'</label></p>'.
		'<p class="area"><label class="required" '.
		'for="post_content"><abbr title="'.__('Required field').'">*</abbr> '.__('Content:').'</label> '.
		form::textarea('post_content',50,7).
		'</p>'.
		'<p><label for="cat_id" class="classic">'.__('Category:').' '.
		form::combo('cat_id',$categories_combo).'</label></p>'.
		'<p><input type="submit" value="'.__('Save').'" name="save" /> '.
		($core->auth->check('publish',$core->blog->id)
			? '<input type="hidden" value="'.__('Save and publish').'" name="save-publish" />'
			: '').
		$core->formNonce().
		form::hidden('post_status',-2).
		form::hidden('post_format',$core->auth->getOption('post_format')).
		form::hidden('post_excerpt','').
		form::hidden('post_lang',$core->auth->getInfo('user_lang')).
		form::hidden('post_notes','').
		'</p>'.
		'</fieldset>'.
		'</form>'.
		'</div>';
	}
}

foreach ($__dashboard_contents as $i)
{	
	if ($i->count() > 0)
	{
		$dashboardContents .= '<div>';
		foreach ($i as $v) {
			$dashboardContents .= $v;
		}
		$dashboardContents .= '</div>';
	}
}
echo ($dashboardContents ? '<div id="dashboard-contents">'.$dashboardContents.'</div>' : '');

echo '</div>';

echo ($dashboardItems ? '<div id="dashboard-items">'.$dashboardItems.'</div>' : '');

dcPage::close();
?>