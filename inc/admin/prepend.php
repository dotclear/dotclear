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

define('DC_CONTEXT_ADMIN',true);

require_once dirname(__FILE__).'/../prepend.php';

// HTTP/1.1
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

// HTTP/1.0
header("Pragma: no-cache");

function dc_load_locales() {
	global $_lang, $core;
	
	$_lang = $core->auth->getInfo('user_lang');
	$_lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$_lang) ? $_lang : 'en';
	
	l10n::lang($_lang);
	if (l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/date') === false && $_lang != 'en') {
		l10n::set(dirname(__FILE__).'/../../locales/en/date');
	}
	l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/main');
	l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/public');
	l10n::set(dirname(__FILE__).'/../../locales/'.$_lang.'/plugins');
}

function dc_admin_icon_url($img)
{
	global $core;
	
	$core->auth->user_prefs->addWorkspace('interface');
	$user_ui_iconset = @$core->auth->user_prefs->interface->iconset;
	if (($user_ui_iconset) && ($img)) {
		$icon = false;
		if ((preg_match('/^images\/menu\/(.+)$/',$img,$m)) || 
			(preg_match('/^index\.php\?pf=(.+)$/',$img,$m))) {
			if ($m[1]) {
				$icon = path::real(dirname(__FILE__).'/../../admin/images/iconset/'.$user_ui_iconset.'/'.$m[1],false);
				if ($icon !== false) {
					$allow_types = array('png','jpg','jpeg','gif');
					if (is_file($icon) && is_readable($icon) && in_array(files::getExtension($icon),$allow_types)) {
						return DC_ADMIN_URL.'images/iconset/'.$user_ui_iconset.'/'.$m[1];
					}
				}
			}
		}
	}
	return $img;
}

if (defined('DC_AUTH_SESS_ID') && defined('DC_AUTH_SESS_UID'))
{
	# We have session information in constants
	$_COOKIE[DC_SESSION_NAME] = DC_AUTH_SESS_ID;
	
	if (!$core->auth->checkSession(DC_AUTH_SESS_UID)) {
		throw new Exception('Invalid session data.');
	}
	
	# Check nonce from POST requests
	if (!empty($_POST))
	{
		if (empty($_POST['xd_check']) || !$core->checkNonce($_POST['xd_check'])) {
			throw new Exception('Precondition Failed.');
		}
	}
	
	if (empty($_SESSION['sess_blog_id'])) {
		throw new Exception('Permission denied.');
	}
	
	# Loading locales
	dc_load_locales();
	
	$core->setBlog($_SESSION['sess_blog_id']);
	if (!$core->blog->id) {
		throw new Exception('Permission denied.');
	}
}
elseif ($core->auth->sessionExists())
{
	# If we have a session we launch it now
	try {
		if (!$core->auth->checkSession())
		{
			# Avoid loop caused by old cookie
			$p = $core->session->getCookieParameters(false,-600);
			$p[3] = '/';
			call_user_func_array('setcookie',$p);
			
			http::redirect('auth.php');
		}
	} catch (Exception $e) {
		__error(__('Database error')
			,__('There seems to be no Session table in your database. Is Dotclear completly installed?')
			,20);
	}
	
	# Check nonce from POST requests
	if (!empty($_POST))
	{
		if (empty($_POST['xd_check']) || !$core->checkNonce($_POST['xd_check'])) {
			http::head(412);
			header('Content-Type: text/plain');
			echo 'Precondition Failed';
			exit;
		}
	}
	
	
	if (!empty($_REQUEST['switchblog'])
	&& $core->auth->getPermissions($_REQUEST['switchblog']) !== false)
	{
		$_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];
		if (isset($_SESSION['media_manager_dir'])) {
			unset($_SESSION['media_manager_dir']);
		}
		if (isset($_SESSION['media_manager_page'])) {
			unset($_SESSION['media_manager_page']);
		}
		
		# Removing switchblog from URL
		$redir = $_SERVER['REQUEST_URI'];
		$redir = preg_replace('/switchblog=(.*?)(&|$)/','',$redir);
		$redir = preg_replace('/\?$/','',$redir);
		http::redirect($redir);
		exit;
	}
	
	# Check blog to use and log out if no result
	if (isset($_SESSION['sess_blog_id']))
	{
		if ($core->auth->getPermissions($_SESSION['sess_blog_id']) === false) {
			unset($_SESSION['sess_blog_id']);
		}
	}
	else
	{
		if (($b = $core->auth->findUserBlog($core->auth->getInfo('user_default_blog'))) !== false) {
			$_SESSION['sess_blog_id'] = $b;
			unset($b);
		}
	}
	
	# Loading locales
	dc_load_locales();
	
	if (isset($_SESSION['sess_blog_id'])) {
		$core->setBlog($_SESSION['sess_blog_id']);
	} else {
		$core->session->destroy();
		http::redirect('auth.php');
	}

/*	
	# Check add to my fav fired
	if (!empty($_REQUEST['add-favorite'])) {
		$redir = $_SERVER['REQUEST_URI'];
		# Extract admin page from URI
		# TO BE COMPLETED
	}
*/
}

if ($core->auth->userID() && $core->blog !== null)
{
	# Loading resources and help files
	$locales_root = dirname(__FILE__).'/../../locales/';
	require $locales_root.'/en/resources.php';
	if (($f = l10n::getFilePath($locales_root,'resources.php',$_lang))) {
		require $f;
	}
	unset($f);
	
	if (($hfiles = @scandir($locales_root.$_lang.'/help')) !== false)
	{
		foreach ($hfiles as $hfile) {
			if (preg_match('/^(.*)\.html$/',$hfile,$m)) {
				$GLOBALS['__resources']['help'][$m[1]] = $locales_root.$_lang.'/help/'.$hfile;
			}
		}
	}
	unset($hfiles,$locales_root);
	// Contextual help flag
	$GLOBALS['__resources']['ctxhelp'] = false;

	$core->auth->user_prefs->addWorkspace('interface');
	$user_ui_nofavmenu = $core->auth->user_prefs->interface->nofavmenu;

	$core->favs = new dcFavorites($core);


	# [] : Title, URL, small icon, large icon, permissions, id, class
	# NB : '*' in permissions means any, null means super admin only
	
	
	# Menus creation
	$_menu = new ArrayObject();
	$_menu['Dashboard'] = new dcMenu('dashboard-menu',null);
	if (!$user_ui_nofavmenu) {
		$core->favs->appendMenuTitle($_menu);
	}
	$_menu['Blog'] = new dcMenu('blog-menu','Blog');
	$_menu['System'] = new dcMenu('system-menu','System');
	$_menu['Plugins'] = new dcMenu('plugins-menu','Plugins');
	# Loading plugins
	$core->plugins->loadModules(DC_PLUGINS_ROOT,'admin',$_lang);
	$core->favs->setup();
	
	if (!$user_ui_nofavmenu) {
		$core->favs->appendMenu($_menu);
	}

	
	# Set menu titles
	
	$_menu['System']->title = __('System settings');
	$_menu['Blog']->title = __('Blog');
	$_menu['Plugins']->title = __('Plugins');


	$_menu['Blog']->prependItem(__('Blog appearance'),'blog_theme.php','images/menu/themes.png',
		preg_match('/blog_theme.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));
	$_menu['Blog']->prependItem(__('Blog settings'),'blog_pref.php','images/menu/blog-pref.png',
		preg_match('/blog_pref.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));
	$_menu['Blog']->prependItem(__('Media manager'),'media.php','images/menu/media.png',
		preg_match('/media(_item)?.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('media,media_admin',$core->blog->id));
	$_menu['Blog']->prependItem(__('Categories'),'categories.php','images/menu/categories.png',
		preg_match('/categories.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('categories',$core->blog->id));
	$_menu['Blog']->prependItem(__('Search'),'search.php','images/menu/search.png',
		preg_match('/search.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));
	$_menu['Blog']->prependItem(__('Comments'),'comments.php','images/menu/comments.png',
		preg_match('/comments.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));
	$_menu['Blog']->prependItem(__('Entries'),'posts.php','images/menu/entries.png',
		preg_match('/posts.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));
	$_menu['Blog']->prependItem(__('New entry'),'post.php','images/menu/edit.png',
		preg_match('/post.php$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('usage,contentadmin',$core->blog->id));
	
	$_menu['System']->prependItem(__('Update'),'update.php','images/menu/update.png',
		preg_match('/update.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin() && is_readable(DC_DIGESTS));
	$_menu['System']->prependItem(__('Languages'),'langs.php','images/menu/langs.png',
		preg_match('/langs.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
	$_menu['System']->prependItem(__('Plugins management'),'plugins.php','images/menu/plugins.png',
		preg_match('/plugins.php(\?.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
	$_menu['System']->prependItem(__('Users'),'users.php','images/menu/users.png',
		preg_match('/users.php$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin());
	$_menu['System']->prependItem(__('Blogs'),'blogs.php','images/menu/blogs.png',
		preg_match('/blogs.php$/',$_SERVER['REQUEST_URI']),
		$core->auth->isSuperAdmin() ||
		$core->auth->check('usage,contentadmin',$core->blog->id) && $core->auth->getBlogCount() > 1);
	
	if (empty($core->blog->settings->system->jquery_migrate_mute)) {
		$core->blog->settings->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
	}
}
?>