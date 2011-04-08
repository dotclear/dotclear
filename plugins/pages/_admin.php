<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminDashboardIcons','pages_dashboard');
function pages_dashboard($core,$icons)
{
	$icons['pages'] = new ArrayObject(array(__('Pages'),'plugin.php?p=pages','index.php?pf=pages/icon-big.png'));
}

$_menu['Blog']->addItem(__('Pages'),'plugin.php?p=pages','index.php?pf=pages/icon.png',
		preg_match('/plugin.php\?p=pages(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('contentadmin,pages',$core->blog->id));

$core->auth->setPermissionType('pages',__('manage pages'));

require dirname(__FILE__).'/_widgets.php';
?>