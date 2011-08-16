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
