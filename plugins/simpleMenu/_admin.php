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

$core->addBehavior('adminDashboardIcons','simpleMenu_dashboard');
$core->addBehavior('adminDashboardFavs','simpleMenu_dashboard_favs');
function simpleMenu_dashboard($core,$icons)
{
	$icons['simpleMenu'] = new ArrayObject(array(__('Simple menu'),'plugin.php?p=simpleMenu','index.php?pf=simpleMenu/icon.png'));
}
function simpleMenu_dashboard_favs($core,$favs)
{
	$favs['simpleMenu'] = new ArrayObject(array('simpleMenu','Simple menu','plugin.php?p=simpleMenu',
		'index.php?pf=simpleMenu/icon-small.png','index.php?pf=simpleMenu/icon.png',
		'usage,contentadmin',null,null));
}

$_menu['Plugins']->addItem(__('Simple menu'),'plugin.php?p=simpleMenu','index.php?pf=simpleMenu/icon-small.png',
                preg_match('/plugin.php\?p=simpleMenu(&.*)?$/',$_SERVER['REQUEST_URI']),
                $core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';
?>