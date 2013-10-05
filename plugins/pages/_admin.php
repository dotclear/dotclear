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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminDashboardFavorites',array('pagesDashboard','pagesDashboardFavs'));
$core->addBehavior('adminUsersActionsHeaders','pages_users_actions_headers');

class pagesDashboard
{
	public static function pagesDashboardFavs($core,$favs)
	{
		$favs->register('pages', array(
			'title' => __('Pages'),
			'url' => 'plugin.php?p=pages',
			'small-icon' => 'index.php?pf=pages/icon.png',
			'large-icon' => 'index.php?pf=pages/icon-big.png',
			'permissions' => 'contentadmin,pages',
			'dashboard_cb' => array('pagesDashboard','pagesDashboardCB'),
			'active_cb' => array('pagesDashboard','pagesActiveCB')
		));
		$favs->register('newpage', array(
			'title' => __('New page'),
			'url' => 'plugin.php?p=pages&amp;act=page',
			'small-icon' => 'index.php?pf=pages/icon-np.png',
			'large-icon' => 'index.php?pf=pages/icon-np-big.png',
			'permissions' => 'contentadmin,pages',
			'active_cb' => array('pagesDashboard','newPageActiveCB')
		));
	}

	public static function pagesDashboardCB($core,$v)
	{
		$params = new ArrayObject();
		$params['post_type'] = 'page';
		$page_count = $core->blog->getPosts($params,true)->f(0);
		if ($page_count > 0) {
			$str_pages = ($page_count > 1) ? __('%d pages') : __('%d page');
			$v['title'] = sprintf($str_pages,$page_count);
		}
	}

	public static function pagesActiveCB($request,$params)
	{
		return ($request == "plugin.php") &&
			isset($params['p']) && $params['p'] == 'pages'
			&& !(isset($params['act']) && $params['act']=='page');
	}

	public static function newPageActiveCB($request,$params)
	{
		return ($request == "plugin.php") &&
			isset($params['p']) && $params['p'] == 'pages'
			&& isset($params['act']) && $params['act']=='page';
	}
}


function pages_users_actions_headers()
{
	return dcPage::jsLoad('index.php?pf=pages/_users_actions.js');
}

$_menu['Blog']->addItem(__('Pages'),'plugin.php?p=pages','index.php?pf=pages/icon.png',
		preg_match('/plugin.php(.*)$/',$_SERVER['REQUEST_URI']) && !empty($_REQUEST['p']) && $_REQUEST['p']=='pages',
		$core->auth->check('contentadmin,pages',$core->blog->id));

$core->auth->setPermissionType('pages',__('manage pages'));

require dirname(__FILE__).'/_widgets.php';
?>