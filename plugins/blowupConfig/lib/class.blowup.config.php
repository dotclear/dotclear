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

class blowupConfig
{
	protected static $fonts = array(
		'sans-serif' => array(
			'ss1' => 'Arial, Helvetica, sans-serif',
			'ss2' => 'Verdana,Geneva, Arial, Helvetica, sans-serif',
			'ss3' => '"Lucida Grande", "Lucida Sans Unicode", sans-serif',
			'ss4' => '"Trebuchet MS", Helvetica, sans-serif',
			'ss5' => 'Impact, Charcoal, sans-serif'
		),

		'serif' => array(
			's1' => 'Times, "Times New Roman", serif',
			's2' => 'Georgia, serif',
			's3' => 'Baskerville, "Palatino Linotype", serif'
		),

		'monospace' => array(
			'm1' => '"Andale Mono", "Courier New", monospace',
			'm2' => '"Courier New", Courier, mono, monospace'
		)
	);

	protected static $fonts_combo = array();
	protected static $fonts_list = array();

	public static $top_images = array(
		'default' => 'Default',
		'blank' => 'Blank',
		'light-trails-1' => 'Light Trails 1',
		'light-trails-2' => 'Light Trails 2',
		'light-trails-3' => 'Light Trails 3',
		'light-trails-4' => 'Light Trails 4',
		'butterflies' => 'Butterflies',
		'flourish-1' => 'Flourished 1',
		'flourish-2' => 'Flourished 2',
		'animals' => 'Animals',
		'plumetis' => 'Plumetis',
		'flamingo' => 'Flamingo',
		'rabbit' => 'Rabbit',
		'roadrunner-1' => 'Road Runner 1',
		'roadrunner-2' => 'Road Runner 2',
		'typo' => 'Typo'
	);

	public static function fontsList()
	{
		if (empty(self::$fonts_combo))
		{
			self::$fonts_combo[__('default')] = '';
			foreach (self::$fonts as $family => $g)
			{
				$fonts = array();
				foreach ($g as $code => $font) {
					$fonts[str_replace('"','',$font)] = $code;
				}
				self::$fonts_combo[$family] = $fonts;
			}
		}

		return self::$fonts_combo;
	}

	public static function fontDef($c)
	{
		if (empty(self::$fonts_list))
		{
			foreach (self::$fonts as $family => $g)
			{
				foreach ($g as $code => $font) {
					self::$fonts_list[$code] = $font;
				}
			}
		}

		return isset(self::$fonts_list[$c]) ? self::$fonts_list[$c] : null;
	}

	public static function adjustFontSize($s)
	{
		if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex)?$/',$s,$m)) {
			if (empty($m[2])) {
				$m[2] = 'px';
			}
			return $m[1].$m[2];
		}

		return null;
	}

	public static function adjustPosition($p)
	{
		if (!preg_match('/^[0-9]+(:[0-9]+)?$/',$p)) {
			return null;
		}

		$p = explode(':',$p);

		return $p[0].(count($p) == 1 ? ':0' : ':'.$p[1]);
	}

	public static function adjustColor($c)
	{
		if ($c === '') {
			return '';
		}

		$c = strtoupper($c);

		if (preg_match('/^[A-F0-9]{3,6}$/',$c)) {
			$c = '#'.$c;
		}

		if (preg_match('/^#[A-F0-9]{6}$/',$c)) {
			return $c;
		}

		if (preg_match('/^#[A-F0-9]{3,}$/',$c)) {
			return '#'.substr($c,1,1).substr($c,1,1).substr($c,2,1).substr($c,2,1).substr($c,3,1).substr($c,3,1);
		}

		return '';
	}

	public static function cleanCSS($css)
	{
		// TODO ?
		return $css;
	}

	public static function cssPath()
	{
		global $core;
		return path::real($core->blog->public_path).'/blowup-css';
	}

	public static function cssURL()
	{
		global $core;
		return $core->blog->settings->system->public_url.'/blowup-css';
	}

	public static function canWriteCss($create=false)
	{
		global $core;

		$public = path::real($core->blog->public_path);
		$css = self::cssPath();

		if (!is_dir($public)) {
			$core->error->add(__('The \'public\' directory does not exist.'));
			return false;
		}

		if (!is_dir($css)) {
			if (!is_writable($public)) {
				$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public'));
				return false;
			}
			if ($create) {
				files::makeDir($css);
			}
			return true;
		}

		if (!is_writable($css)) {
			$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public/blowup-css'));
			return false;
		}

		return true;
	}

	public static function createCss($s)
	{
		global $core;
		
		if ($s === null) {
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
		self::prop($css,'body','font-family',self::fontDef($s['body_txt_f']));

		self::prop($css,'.post-content, .post-excerpt, #comments dd, #pings dd, dd.comment-preview','line-height',$s['body_line_height']);

		if (!$s['blog_title_hide'])
		{
			self::prop($css,'#top h1 a','color',$s['blog_title_c']);
			self::prop($css,'#top h1','font-size',$s['blog_title_s']);
			self::prop($css,'#top h1','font-family',self::fontDef($s['blog_title_f']));

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
		self::prop($css,'.day-date','font-family',self::fontDef($s['date_title_f']));
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

		self::prop($css,'#sidebar','font-family',self::fontDef($s['sidebar_text_f']));
		self::prop($css,'#sidebar','font-size',$s['sidebar_text_s']);
		self::prop($css,'#sidebar','color',$s['sidebar_text_c']);

		self::prop($css,'#sidebar h2','font-family',self::fontDef($s['sidebar_title_f']));
		self::prop($css,'#sidebar h2','font-size',$s['sidebar_title_s']);
		self::prop($css,'#sidebar h2','color',$s['sidebar_title_c']);

		self::prop($css,'#sidebar h3','font-family',self::fontDef($s['sidebar_title2_f']));
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
		self::prop($css,'.post-title','font-family',self::fontDef($s['post_title_f']));
		self::prop($css,'.post-title','font-size',$s['post_title_s']);

		self::prop($css,'#comments dd','background-color',$s['post_comment_bg_c']);
		self::prop($css,'#comments dd','color',$s['post_comment_c']);
		self::prop($css,'#comments dd.me','background-color',$s['post_commentmy_bg_c']);
		self::prop($css,'#comments dd.me','color',$s['post_commentmy_c']);

		self::prop($css,'#prelude, #prelude a','color',$s['prelude_c']);

		self::prop($css,'#footer p','background-color',$s['footer_bg_c']);
		self::prop($css,'#footer p','color',$s['footer_c']);
		self::prop($css,'#footer p','font-size',$s['footer_s']);
		self::prop($css,'#footer p','font-family',self::fontDef($s['footer_f']));
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

		if (!self::canWriteCss(true)) {
			throw new Exception(__('Unable to create css file.'));
		}

		# erase old css file
		self::dropCss($core->blog->settings->system->theme);

		# create new css file into public blowup-css subdirectory
		self::writeCss($core->blog->settings->system->theme, $res);

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
		$file = self::imagesPath().'/'.$image;
		if ($value && file_exists($file)){
			$css[$selector]['background-image'] = 'url('.self::imagesURL().'/'.$image.')';
		}
	}

	private static function writeCss($theme,$css)
	{
		file_put_contents(self::cssPath().'/'.$theme.'.css', $css);
	}
	
	public static function dropCss($theme)
	{
		$file = path::real(self::cssPath().'/'.$theme.'.css');
		if (is_writable(dirname($file))) {
			@unlink($file);
		}
	}

	public static function publicCssUrlHelper()
	{
		$theme = $GLOBALS['core']->blog->settings->system->theme;
		$url = blowupConfig::cssURL();
		$path = blowupConfig::cssPath();

		if (file_exists($path.'/'.$theme.'.css')) {
			return $url.'/'.$theme.'.css';
		}

		return null;
	}

	public static function imagesPath()
	{
		global $core;
		return path::real($core->blog->public_path).'/blowup-images';
	}

	public static function imagesURL()
	{
		global $core;
		return $core->blog->settings->system->public_url.'/blowup-images';
	}

	public static function canWriteImages($create=false)
	{
		global $core;

		$public = path::real($core->blog->public_path);
		$imgs = self::imagesPath();

		if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng') || !function_exists('imagecreatefrompng')) {
			$core->error->add(__('At least one of the following functions is not available: '.
				'imagecreatetruecolor, imagepng & imagecreatefrompng.'));
			return false;
		}

		if (!is_dir($public)) {
			$core->error->add(__('The \'public\' directory does not exist.'));
			return false;
		}

		if (!is_dir($imgs)) {
			if (!is_writable($public)) {
				$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public'));
				return false;
			}
			if ($create) {
				files::makeDir($imgs);
			}
			return true;
		}

		if (!is_writable($imgs)) {
			$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public/blowup-images'));
			return false;
		}

		return true;
	}

	public static function uploadImage($f)
	{
		if (!self::canWriteImages(true)) {
			throw new Exception(__('Unable to create images.'));
		}

		$name = $f['name'];
		$type = files::getMimeType($name);

		if ($type != 'image/jpeg' && $type != 'image/png') {
			throw new Exception(__('Invalid file type.'));
		}

		$dest = self::imagesPath().'/uploaded'.($type == 'image/png' ? '.png' : '.jpg');

		if (@move_uploaded_file($f['tmp_name'],$dest) === false) {
			throw new Exception(__('An error occurred while writing the file.'));
		}

		$s = getimagesize($dest);
		if ($s[0] != 800) {
			throw new Exception(__('Uploaded image is not 800 pixels wide.'));
		}

		return $dest;
	}

	public static function createImages(&$config,$uploaded)
	{
		$body_color = $config['body_bg_c'];
		$prelude_color = $config['prelude_c'];
		$gradient = $config['body_bg_g'];
		$comment_color = $config['post_comment_bg_c'];
		$comment_color_my = $config['post_commentmy_bg_c'];
		$top_image = $config['top_image'];

		$config['top_height'] = null;

		if ($top_image != 'custom' && !isset(self::$top_images[$top_image])) {
			$top_image = 'default';
		}
		if ($uploaded && !is_file($uploaded)) {
			$uploaded = null;
		}

		if (!self::canWriteImages(true)) {
			throw new Exception(__('Unable to create images.'));
		}

		$body_fill = array(
			'light' => dirname(__FILE__).'/../alpha-img/gradient-l.png',
			'medium' => dirname(__FILE__).'/../alpha-img/gradient-m.png',
			'dark' => dirname(__FILE__).'/../alpha-img/gradient-d.png'
		);

		$body_g = isset($body_fill[$gradient]) ? $body_fill[$gradient] : false;

		if ($top_image == 'custom' && $uploaded) {
			$page_t = $uploaded;
		} else {
			$page_t = dirname(__FILE__).'/../alpha-img/page-t/'.$top_image.'.png';
		}

		$body_bg = dirname(__FILE__).'/../alpha-img/body-bg.png';
		$page_t_mask = dirname(__FILE__).'/../alpha-img/page-t/image-mask.png';
		$page_b = dirname(__FILE__).'/../alpha-img/page-b.png';
		$comment_t = dirname(__FILE__).'/../alpha-img/comment-t.png';
		$comment_b = dirname(__FILE__).'/../alpha-img/comment-b.png';
		$default_bg = '#e0e0e0';
		$default_prelude = '#ededed';

		self::dropImage(basename($body_bg));
		self::dropImage('page-t.png');
		self::dropImage(basename($page_b));
		self::dropImage(basename($comment_t));
		self::dropImage(basename($comment_b));

		$body_color = self::adjustColor($body_color);
		$prelude_color = self::adjustColor($prelude_color);
		$comment_color = self::adjustColor($comment_color);

		if ($top_image || $body_color || $gradient != 'light' || $prelude_color || $uploaded)
		{
			if (!$body_color) {
				$body_color = $default_bg;
			}
			$body_color = sscanf($body_color,'#%2X%2X%2X');

			# Create body gradient with color
			$d_body_bg = imagecreatetruecolor(50,180);
			$fill = imagecolorallocate($d_body_bg,$body_color[0],$body_color[1],$body_color[2]);
			imagefill($d_body_bg,0,0,$fill);

			# User choosed a gradient
			if ($body_g) {
				$s_body_bg = imagecreatefrompng($body_g);
				imagealphablending($s_body_bg,true);
				imagecopy($d_body_bg,$s_body_bg,0,0,0,0,50,180);
				imagedestroy($s_body_bg);
			}

			if (!$prelude_color) {
				$prelude_color = $default_prelude;
			}
			$prelude_color = sscanf($prelude_color,'#%2X%2X%2X');

			$s_prelude = imagecreatetruecolor(50,30);
			$fill = imagecolorallocate($s_prelude,$prelude_color[0],$prelude_color[1],$prelude_color[2]);
			imagefill($s_prelude,0,0,$fill);
			imagecopy($d_body_bg,$s_prelude,0,0,0,0,50,30);

			imagepng($d_body_bg,self::imagesPath().'/'.basename($body_bg));
		}

		if ($top_image || $body_color || $gradient != 'light')
		{
			# Create top image from uploaded image
			$size = getimagesize($page_t);
			$size = $size[1];
			$type = files::getMimeType($page_t);

			$d_page_t = imagecreatetruecolor(800,$size);

			if ($type == 'image/png') {
				$s_page_t = @imagecreatefrompng($page_t);
			} else {
				$s_page_t = @imagecreatefromjpeg($page_t);
			}

			if (!$s_page_t) {
				throw new exception(__('Unable to open image.'));
			}

			$fill = imagecolorallocate($d_page_t,$body_color[0],$body_color[1],$body_color[2]);
			imagefill($d_page_t,0,0,$fill);

			if ($type == 'image/png')
			{
				# PNG, we only add body gradient and image
				imagealphablending($s_page_t,true);
				imagecopyresized($d_page_t,$d_body_bg,0,0,0,50,800,130,50,130);
				imagecopy($d_page_t,$s_page_t,0,0,0,0,800,$size);
			}
			else
			{
				# JPEG, we add image and a frame with rounded corners
				imagecopy($d_page_t,$s_page_t,0,0,0,0,800,$size);

				imagecopy($d_page_t,$d_body_bg,0,0,0,50,8,4);
				imagecopy($d_page_t,$d_body_bg,0,4,0,54,4,4);
				imagecopy($d_page_t,$d_body_bg,792,0,0,50,8,4);
				imagecopy($d_page_t,$d_body_bg,796,4,0,54,4,4);

				$mask = imagecreatefrompng($page_t_mask);
				imagealphablending($mask,true);
				imagecopy($d_page_t,$mask,0,0,0,0,800,11);
				imagedestroy($mask);

				$fill = imagecolorallocate($d_page_t,255,255,255);
				imagefilledrectangle($d_page_t,0,11,3,$size-1,$fill);
				imagefilledrectangle($d_page_t,796,11,799,$size-1,$fill);
				imagefilledrectangle($d_page_t,0,$size-9,799,$size-1,$fill);
			}

			$config['top_height'] = ($size).'px';

			imagepng($d_page_t,self::imagesPath().'/page-t.png');

			imagedestroy($d_body_bg);
			imagedestroy($d_page_t);
			imagedestroy($s_page_t);

			# Create bottom image with color
			$d_page_b = imagecreatetruecolor(800,8);
			$fill = imagecolorallocate($d_page_b,$body_color[0],$body_color[1],$body_color[2]);
			imagefill($d_page_b,0,0,$fill);

			$s_page_b = imagecreatefrompng($page_b);
			imagealphablending($s_page_b,true);
			imagecopy($d_page_b,$s_page_b,0,0,0,0,800,160);

			imagepng($d_page_b,self::imagesPath().'/'.basename($page_b));

			imagedestroy($d_page_b);
			imagedestroy($s_page_b);
		}

		if ($comment_color) {
			self::commentImages($comment_color,$comment_t,$comment_b,basename($comment_t),basename($comment_b));
		}
		if ($comment_color_my) {
			self::commentImages($comment_color_my,$comment_t,$comment_b,'commentmy-t.png','commentmy-b.png');
		}
	}

	protected static function commentImages($comment_color,$comment_t,$comment_b,$dest_t,$dest_b)
	{
		$comment_color = sscanf($comment_color,'#%2X%2X%2X');

		$d_comment_t = imagecreatetruecolor(500,25);
		$fill = imagecolorallocate($d_comment_t,$comment_color[0],$comment_color[1],$comment_color[2]);
		imagefill($d_comment_t,0,0,$fill);

		$s_comment_t = imagecreatefrompng($comment_t);
		imagealphablending($s_comment_t,true);
		imagecopy($d_comment_t,$s_comment_t,0,0,0,0,500,25);

		imagepng($d_comment_t,self::imagesPath().'/'.$dest_t);
		imagedestroy($d_comment_t);
		imagedestroy($s_comment_t);

		$d_comment_b = imagecreatetruecolor(500,7);
		$fill = imagecolorallocate($d_comment_b,$comment_color[0],$comment_color[1],$comment_color[2]);
		imagefill($d_comment_b,0,0,$fill);

		$s_comment_b = imagecreatefrompng($comment_b);
		imagealphablending($s_comment_b,true);
		imagecopy($d_comment_b,$s_comment_b,0,0,0,0,500,7);

		imagepng($d_comment_b,self::imagesPath().'/'.$dest_b);
		imagedestroy($d_comment_b);
		imagedestroy($s_comment_b);
	}

	public static function dropImage($img)
	{
		$img = path::real(self::imagesPath().'/'.$img);
		if (is_writable(dirname($img))) {
			@unlink($img);
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_m.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_s.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_t.jpg');
		}
	}
}
?>
