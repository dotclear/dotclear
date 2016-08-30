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

$core->addBehavior('adminDashboardFavorites',array('widgetsBehaviors','widgets_dashboard_favorites'));
$core->addBehavior('adminRteFlags',array('widgetsBehaviors','adminRteFlags'));

$_menu['Blog']->addItem(__('Presentation widgets'),
		$core->adminurl->get('admin.plugin.widgets'),
		dcPage::getPF('widgets/icon.png'),
		preg_match('/'.preg_quote($core->adminurl->get('admin.plugin.widgets')).'(&.*)?$/',$_SERVER['REQUEST_URI']),
		$core->auth->check('admin',$core->blog->id));

class widgetsBehaviors
{
	public static function widgets_dashboard_favorites($core,$favs)
	{
		$favs->register('widgets', array(
			'title' => __('Presentation widgets'),
			'url' => $core->adminurl->get('admin.plugin.widgets'),
			'small-icon' => dcPage::getPF('widgets/icon.png'),
			'large-icon' => dcPage::getPF('widgets/icon-big.png'),
		));
	}

	public static function adminRteFlags($core,$rte)
	{
		$rte['widgets_text'] = array(true,__('Widget\'s textareas'));
	}
}
