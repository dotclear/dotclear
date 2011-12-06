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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminDashboardIcons','pages_dashboard');
$core->addBehavior('adminDashboardFavs','pages_dashboard_favs');
$core->addBehavior('adminDashboardFavsIcon','pages_dashboard_favs_icon');
function pages_dashboard($core,$icons)
{
	$icons['pages'] = new ArrayObject(array(__('Pages'),'plugin.php?p=pages','index.php?pf=pages/icon-big.png'));
}
function pages_dashboard_favs($core,$favs)
{
	$favs['pages'] = new ArrayObject(array('pages','Pages','plugin.php?p=pages',
		'index.php?pf=pages/icon.png','index.php?pf=pages/icon-big.png',
		'contentadmin,pages',null,null));
	$favs['newpage'] = new ArrayObject(array('newpage','New page','plugin.php?p=pages&amp;act=page',
		'index.php?pf=pages/icon-np.png','index.php?pf=pages/icon-np-big.png',
		'contentadmin,pages',null,null));
}
function pages_dashboard_favs_icon($core,$name,$icon)
{
	// Check if it is one of my own favs
	if ($name == 'pages') {
		$params = new ArrayObject();
		$params['post_type'] = 'page';
		$page_count = $core->blog->getPosts($params,true)->f(0);
		if ($page_count > 0) {
			$str_pages = ($page_count > 1) ? __('%d pages') : __('%d page');
			$icon[0] = sprintf($str_pages,$page_count);
		}
	}
}

$_menu['Blog']->addItem(__('Pages'),'plugin.php?p=pages','index.php?pf=pages/icon.png',
		preg_match('/plugin.php\?p=pages(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('contentadmin,pages',$core->blog->id));

$core->auth->setPermissionType('pages',__('manage pages'));

require dirname(__FILE__).'/_widgets.php';
?>