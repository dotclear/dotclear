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

		# Main font
		$main_font_selectors = 'body, #supranav li a span, #comments.me, a.comment-number';
		self::prop($css,$main_font_selectors,'font-family',self::fontDef($s['body_font']));

		# Alternate font
		$alternate_font_selectors = '#blogdesc, #supranav li, #content-info, #subcategories, #comments-feed, #sidebar h2, #sidebar h3, #footer p';
		self::prop($css,$alternate_font_selectors,'font-family',self::fontDef($s['alternate_font']));

		# Link colors
		self::prop($css,'a','color',$s['body_link_c']);
		self::prop($css,'a:visited','color',$s['body_link_v_c']);
		self::prop($css,'a:hover, a:focus, a:active','color',$s['body_link_f_c']);

		# Style directives
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

	protected static $fonts = array(
		'Ductile body' => '"Century Schoolbook", "Century Schoolbook L", Georgia, serif',
		'Ductile alternate' => '"Franklin gothic medium", "arial narrow", "DejaVu Sans Condensed", "helvetica neue", helvetica, sans-serif',
		'Times New Roman' => 'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
		'Georgia' => 'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif," "Bitstream Vera Serif", "Liberation Serif", Georgia, serif',
		'Garamond' => '"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',
		'Helvetica/Arial' => 'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
		'Verdana' => 'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
		'Trebuchet MS' => '"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',
		'Impact' => 'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',
		'Monospace' => 'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace'
	);

	protected static function fontDef($c)
	{
		return isset(self::$fonts[$c]) ? self::$fonts[$c] : null;
	}

	protected static function prop(&$css,$selector,$prop,$value)
	{
		if ($value) {
			$css[$selector][$prop] = $value;
		}
	}
}
?>