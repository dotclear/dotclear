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

$core->addBehavior('adminDashboardFavs','widgets_dashboard_favs');

function widgets_dashboard_favs($core,$favs)
{
	$favs['widgets'] = new ArrayObject(array('widgets','Presentation widgets','plugin.php?p=widgets',
		'index.php?pf=widgets/icon.png','index.php?pf=widgets/icon-big.png',
		'admin',null,null));
}

$_menu['Blog']->addItem(__('Presentation widgets'),'plugin.php?p=widgets','index.php?pf=widgets/icon.png',
		preg_match('/plugin.php\?p=widgets(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));
?>