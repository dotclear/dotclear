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
		echo '<style type="text/css">'."\n".self::blowUpStyleHelper()."\n</style>\n";
	}

	public static function blowUpStyleHelper()
	{
		$s = $GLOBALS['core']->blog->settings->themes->blowup_style;

		if ($s === null) {
			return;
		}

		$s = @unserialize($s);
		if (!is_array($s)) {
			return;
		}

		$css = array();

		/* Sidebar position
		---------------------------------------------- */
		if ($s['sidebar_position'] == 'left') {
			$css['#wrapper']['background-position'] = '-300px 0';
			$css['#main']['float'] = 'right';
			$css['#sidebar']['float'] = 'left';
		}

		/* Properties
		---------------------------------------------- */
		self::prop($css,'body','background-color',$s['body_bg_c']);

		self::prop($css,'body','color',$s['body_txt_c']);
		self::prop($css,'.post-tags li a:link, .post-tags li a:visited, .post-info-co a:link, .post-info-co a:visited','color',$s['body_txt_c']);
		self::prop($css,'#page','font-size',$s['body_txt_s']);
		self::prop($css,'body','font-family',blowupConfig::fontDef($s['body_txt_f']));

		self::prop($css,'.post-content, .post-excerpt, #comments dd, #pings dd, dd.comment-preview','line-height',$s['body_line_height']);

		if (!$s['blog_title_hide'])
		{
			self::prop($css,'#top h1 a','color',$s['blog_title_c']);
			self::prop($css,'#top h1','font-size',$s['blog_title_s']);
			self::prop($css,'#top h1','font-family',blowupConfig::fontDef($s['blog_title_f']));

			if ($s['blog_title_a'] == 'right' || $s['blog_title_a'] == 'left') {
				$css['#top h1'][$s['blog_title_a']] = '0px';
				$css['#top h1']['width'] = 'auto';
			}

			if ($s['blog_title_p'])
			{
				$_p = explode(':',$s['blog_title_p']);
				$css['#top h1']['top'] = $_p[1].'px';
				if ($s['blog_title_a'] != 'center') {
					$_a = $s['blog_title_a'] == 'right' ? 'right' : 'left';
					$css['#top h1'][$_a] = $_p[0].'px';
				}
			}
		}
		else
		{
			self::prop($css,'#top h1 span','text-indent','-5000px');
			self::prop($css,'#top h1','top','0px');
			$css['#top h1 a'] = array(
				'display' => 'block',
				'height' => $s['top_height'] ? ($s['top_height']-10).'px' : '120px',
				'width' => '800px'
			);
		}
		self::prop($css,'#top','height',$s['top_height']);

		self::prop($css,'.day-date','color',$s['date_title_c']);
		self::prop($css,'.day-date','font-family',blowupConfig::fontDef($s['date_title_f']));
		self::prop($css,'.day-date','font-size',$s['date_title_s']);

		self::prop($css,'a','color',$s['body_link_c']);
		self::prop($css,'a:visited','color',$s['body_link_v_c']);
		self::prop($css,'a:hover, a:focus, a:active','color',$s['body_link_f_c']);

		self::prop($css,'#comment-form input, #comment-form textarea','color',$s['body_link_c']);
		self::prop($css,'#comment-form input.preview','color',$s['body_link_c']);
		self::prop($css,'#comment-form input.preview:hover','background',$s['body_link_f_c']);
		self::prop($css,'#comment-form input.preview:hover','border-color',$s['body_link_f_c']);
		self::prop($css,'#comment-form input.submit','color',$s['body_link_c']);
		self::prop($css,'#comment-form input.submit:hover','background',$s['body_link_f_c']);
		self::prop($css,'#comment-form input.submit:hover','border-color',$s['body_link_f_c']);

		self::prop($css,'#sidebar','font-family',blowupConfig::fontDef($s['sidebar_text_f']));
		self::prop($css,'#sidebar','font-size',$s['sidebar_text_s']);
		self::prop($css,'#sidebar','color',$s['sidebar_text_c']);

		self::prop($css,'#sidebar h2','font-family',blowupConfig::fontDef($s['sidebar_title_f']));
		self::prop($css,'#sidebar h2','font-size',$s['sidebar_title_s']);
		self::prop($css,'#sidebar h2','color',$s['sidebar_title_c']);

		self::prop($css,'#sidebar h3','font-family',blowupConfig::fontDef($s['sidebar_title2_f']));
		self::prop($css,'#sidebar h3','font-size',$s['sidebar_title2_s']);
		self::prop($css,'#sidebar h3','color',$s['sidebar_title2_c']);

		self::prop($css,'#sidebar ul','border-top-color',$s['sidebar_line_c']);
		self::prop($css,'#sidebar li','border-bottom-color',$s['sidebar_line_c']);
		self::prop($css,'#topnav ul','border-bottom-color',$s['sidebar_line_c']);

		self::prop($css,'#sidebar li a','color',$s['sidebar_link_c']);
		self::prop($css,'#sidebar li a:visited','color',$s['sidebar_link_v_c']);
		self::prop($css,'#sidebar li a:hover, #sidebar li a:focus, #sidebar li a:active','color',$s['sidebar_link_f_c']);
		self::prop($css,'#search input','color',$s['sidebar_link_c']);
		self::prop($css,'#search .submit','color',$s['sidebar_link_c']);
		self::prop($css,'#search .submit:hover','background',$s['sidebar_link_f_c']);
		self::prop($css,'#search .submit:hover','border-color',$s['sidebar_link_f_c']);

		self::prop($css,'.post-title','color',$s['post_title_c']);
		self::prop($css,'.post-title a, .post-title a:visited','color',$s['post_title_c']);
		self::prop($css,'.post-title','font-family',blowupConfig::fontDef($s['post_title_f']));
		self::prop($css,'.post-title','font-size',$s['post_title_s']);

		self::prop($css,'#comments dd','background-color',$s['post_comment_bg_c']);
		self::prop($css,'#comments dd','color',$s['post_comment_c']);
		self::prop($css,'#comments dd.me','background-color',$s['post_commentmy_bg_c']);
		self::prop($css,'#comments dd.me','color',$s['post_commentmy_c']);

		self::prop($css,'#prelude, #prelude a','color',$s['prelude_c']);

		self::prop($css,'#footer p','background-color',$s['footer_bg_c']);
		self::prop($css,'#footer p','color',$s['footer_c']);
		self::prop($css,'#footer p','font-size',$s['footer_s']);
		self::prop($css,'#footer p','font-family',blowupConfig::fontDef($s['footer_f']));
		self::prop($css,'#footer p a','color',$s['footer_l_c']);

		/* Images
		------------------------------------------------------ */
		self::backgroundImg($css,'body',$s['body_bg_c'],'body-bg.png');
		self::backgroundImg($css,'body',$s['body_bg_g'] != 'light','body-bg.png');
		self::backgroundImg($css,'body',$s['prelude_c'],'body-bg.png');
		self::backgroundImg($css,'#top',$s['body_bg_c'],'page-t.png');
		self::backgroundImg($css,'#top',$s['body_bg_g'] != 'light','page-t.png');
		self::backgroundImg($css,'#top',$s['uploaded'] || $s['top_image'],'page-t.png');
		self::backgroundImg($css,'#footer',$s['body_bg_c'],'page-b.png');
		self::backgroundImg($css,'#comments dt',$s['post_comment_bg_c'],'comment-t.png');
		self::backgroundImg($css,'#comments dd',$s['post_comment_bg_c'],'comment-b.png');
		self::backgroundImg($css,'#comments dt.me',$s['post_commentmy_bg_c'],'commentmy-t.png');
		self::backgroundImg($css,'#comments dd.me',$s['post_commentmy_bg_c'],'commentmy-b.png');

		$res = '';
		foreach ($css as $selector => $values) {
			$res .= $selector." {\n";
			foreach ($values as $k => $v) {
				$res .= $k.':'.$v.";\n";
			}
			$res .= "}\n";
		}

		$res .= $s['extra_css'];

		return $res;
	}

	protected static function prop(&$css,$selector,$prop,$value)
	{
		if ($value) {
			$css[$selector][$prop] = $value;
		}
	}

	protected static function backgroundImg(&$css,$selector,$value,$image)
	{
		$file = blowupConfig::imagesPath().'/'.$image;
		if ($value && file_exists($file)){
			$css[$selector]['background-image'] = 'url('.blowupConfig::imagesURL().'/'.$image.')';
		}
	}
}
?>
