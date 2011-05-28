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
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('publicHeadContent',array('tplCustomTheme','publicHeadContent'));

class tplCustomTheme
{
	public static function publicHeadContent($core)
	{
		echo
		'<style type="text/css">'."\n".
		'@import url('.$core->blog->settings->system->public_url.'/custom_style.css);'."\n".
		"</style>\n";
	}
}
?>