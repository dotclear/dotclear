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

$core->addBehavior('publicHeadContent',array('tplDuctileTheme','publicHeadContent'));

class tplDuctileTheme
{
	public static function publicHeadContent($core)
	{
		echo 
			'<style type="text/css">'."\n".
			'/* Additionnal style directives */'."\n".
			self::ductileStyleHelper().
			"</style>\n";
	}
	
	public static function ductileStyleHelper()
	{
		$s = $GLOBALS['core']->blog->settings->themes->ductile_style;

		if ($s === null) {
			return;
		}

		$s = @unserialize($s);
		if (!is_array($s)) {
			return;
		}

		$css = array();

		# Properties

		self::prop($css,'a','color',$s['body_link_c']);
		self::prop($css,'a:visited','color',$s['body_link_v_c']);
		self::prop($css,'a:hover, a:focus, a:active','color',$s['body_link_f_c']);

		$res = '';
		foreach ($css as $selector => $values) {
			$res .= $selector." {\n";
			foreach ($values as $k => $v) {
				$res .= $k.':'.$v.";\n";
			}
			$res .= "}\n";
		}

		return $res;
	}

	protected static function prop(&$css,$selector,$prop,$value)
	{
		if ($value) {
			$css[$selector][$prop] = $value;
		}
	}
}
?>