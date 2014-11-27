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

$core->addBehavior('adminDashboardFavorites','widgets_dashboard_favorites');

function widgets_dashboard_favorites($core,$favs)
{
	$favs->register('widgets', array(
		'title' => __('Presentation widgets'),
		'url' => $core->adminurl->get('admin.plugin.widgets'),
		'small-icon' => $core->adminurl->decode('load.plugin.file',array('pf' => 'widgets/icon.png')),
		'large-icon' => $core->adminurl->decode('load.plugin.file',array('pf' => 'widgets/icon-big.png')),
	));
}

$_menu['Blog']->addItem(__('Presentation widgets'),
		$core->adminurl->get('admin.plugin.widgets'),
		$core->adminurl->decode('load.plugin.file',array('pf' => 'widgets/icon.png')),
		preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.widgets')).'(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));
