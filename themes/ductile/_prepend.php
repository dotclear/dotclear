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

# Public and Admin modes :

if (!defined('DC_RC_PATH')) { return; }

# Admin mode only :

# Behaviors
$GLOBALS['core']->addBehavior('adminPageHTMLHead',array('tplDuctileThemeAdmin','adminPageHTMLHead'));

class tplDuctileThemeAdmin
{
	public static function adminPageHTMLHead()
	{
		echo "\n".'<!-- Header directives for Ductile configuration -->'."\n";
		echo dcPage::jsToolMan();
	}
}
?>