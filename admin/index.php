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

$favs = $core->favs->getUserFavorites();
$core->favs->appendDashboardIcons($__dashboard_icons);


# Check plugins and themes update from repository
function dc_check_store_update($mod, $url, $img, $icon)
{
	$repo = new dcStore($mod, $url);
	$upd = $repo->get(true);
	if (!empty($upd)) {
		$icon[0] .= '<br />'.sprintf(__('An update is available', '%s updates are available.', count($upd)),count($upd));
		$icon[1] .= '#update';
		$icon[2] = 'images/menu/'.$img.'-b-update.png';
	}
}
if (isset($__dashboard_icons['plugins'])) {
	dc_check_store_update($core->plugins, $core->blog->settings->system->store_plugin_url, 'plugins', $__dashboard_icons['plugins']);
}
if (isset($__dashboard_icons['blog_theme'])) {
	$themes = new dcThemes($core);
	$themes->loadModules($core->blog->themes_path, null);
	dc_check_store_update($themes, $core->blog->settings->system->store_theme_url, 'blog-theme', $__dashboard_icons['blog_theme']);
}

# Latest news for dashboard
$__dashboard_items = new ArrayObject(array(new ArrayObject(),new ArrayObject()));

$dashboardItem = 0;


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
			$latest_news = '<div class="box medium dc-box"><h3>'.__('Dotclear news').'</h3><dl id="news">';
			$i = 1;
			foreach ($feed->items as $item)
			{
				$dt = isset($item->link) ? '<a href="'.$item->link.'" class="outgoing" title="'.$item->title.'">'.
					$item->title.' <img src="images/outgoing-blue.png" alt="" /></a>' : $item->title;

				if ($i < 3) {
					$latest_news .=
					'<dt>'.$dt.'</dt>'.
					'<dd><p><strong>'.dt::dt2str(__('%d %B %Y:'),$item->pubdate,'Europe/Paris').'</strong> '.
					'<em>'.text::cutString(html::clean($item->content),120).'...</em></p></dd>';
				} else {
					$latest_news .=
					'<dt>'.$dt.'</dt>'.
					'<dd>'.dt::dt2str(__('%d %B %Y:'),$item->pubdate,'Europe/Paris').'</dd>';
				}
				$i++;
				if ($i > 2) { break; }
			}
			$latest_news .= '</dl></div>';
			$__dashboard_items[$dashboardItem][] = $latest_news;
			$dashboardItem++;
		}
	}
	catch (Exception $e) {}
}

# Documentation links
if ($core->auth->user_prefs->dashboard->doclinks) {
	if (!empty($__resources['doc']))
	{
		$doc_links = '<div class="box small dc-box"><h3>'.__('Documentation and support').'</h3><ul>';

		foreach ($__resources['doc'] as $k => $v) {
			$doc_links .= '<li><a class="outgoing" href="'.$v.'" title="'.$k.'">'.$k.
			' <img src="images/outgoing-blue.png" alt="" /></a></li>';
		}

		$doc_links .= '</ul></div>';
		$__dashboard_items[$dashboardItem][] = $doc_links;
		$dashboardItem++;
	}
}

$core->callBehavior('adminDashboardItems', $core, $__dashboard_items);

# Dashboard content
$dashboardContents = '';
$__dashboard_contents = new ArrayObject(array(new ArrayObject,new ArrayObject));
$core->callBehavior('adminDashboardContents', $core, $__dashboard_contents);

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
$_ctx->dashboard_icons = $__dashboard_icons;
//print_r($__dashboard_icons);exit;
$_ctx->setBreadCrumb(__('Dashboard').' : '.html::escapeHTML($core->blog->name), false);
$core->tpl->display('index.html.twig');
?>
