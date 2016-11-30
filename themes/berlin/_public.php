<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
# This file is part of Berlin, a theme for Dotclear
#
# Copyright (c) Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

l10n::set(dirname(__FILE__).'/locales/'.$_lang.'/main');
//__('Show menu').__('Hide menu').__('Navigation');

$core->addBehavior('publicHeadContent',array('behaviorBerlinTheme','publicHeadContent'));

class behaviorBerlinTheme
{
	public static function publicHeadContent()
	{
//		global $core,$_ctx;

		echo dcUtils::jsVars(array(
			'dotclear_berlin_show_menu' => __('Show menu'),
			'dotclear_berlin_hide_menu' => __('Hide menu'),
			'dotclear_berlin_navigation' => __('Navigation')
			));
	}
}
