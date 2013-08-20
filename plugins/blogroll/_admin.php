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

$core->addBehavior('adminDashboardIcons','blogroll_dashboard');
$core->addBehavior('adminDashboardFavs','blogroll_dashboard_favs');
$core->addBehavior('adminUsersActionsHeaders','blogroll_users_actions_headers');

function blogroll_dashboard($core,$icons)
{
	$icons['blogroll'] = new ArrayObject(array(__('Blogroll'),'plugin.php?p=blogroll','index.php?pf=blogroll/icon.png'));
}
function blogroll_dashboard_favs($core,$favs)
{
	$favs['blogroll'] = new ArrayObject(array('blogroll','Blogroll','plugin.php?p=blogroll',
		'index.php?pf=blogroll/icon-small.png','index.php?pf=blogroll/icon.png',
		'usage,contentadmin',null,null));
}
function blogroll_users_actions_headers()
{
	return dcPage::jsLoad('index.php?pf=blogroll/_users_actions.js');
}

$_menu['Blog']->addItem(__('Blogroll'),'plugin.php?p=blogroll','index.php?pf=blogroll/icon-small.png',
                preg_match('/plugin.php\?p=blogroll(&.*)?$/',$_SERVER['REQUEST_URI']),
                $core->auth->check('usage,contentadmin',$core->blog->id));

$core->auth->setPermissionType('blogroll',__('manage blogroll'));

require dirname(__FILE__).'/_widgets.php';
?>