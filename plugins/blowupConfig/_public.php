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
if (!defined('DC_RC_PATH')) { return; }

if ($core->blog->settings->system->theme != 'default') {
	return;
}

require dirname(__FILE__).'/lib/class.blowup.config.php';
$core->addBehavior('publicHeadContent',array('tplBlowupTheme','publicHeadContent'));

class tplBlowUpTheme
{
	public static function publicHeadContent($core)
	{
		$url = blowupConfig::publicCssUrlHelper();
		if ($url) {
			echo '<link rel="stylesheet" href="'.$url.'" type="text/css" />';
		}
	}
}
?>
