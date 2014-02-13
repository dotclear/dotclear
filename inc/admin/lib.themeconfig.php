<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_ADMIN_CONTEXT')) { return; }

/**
 * @ingroup DC_CORE
 * @brief Helper for theme configurators.
 * @since 2.7
 *
 * Provides helper tools for theme configurators.
 */
class dcThemeConfig
{
	// Utilities

	public static function computeContrastRatio($color,$background)
	{
		// Compute contrast ratio between two colors

		$color = adjustColor($color);
		if (($color == '') || (strlen($color) != 7)) return 0;
		$background = adjustColor($background);
		if (($background == '') || (strlen($background) != 7)) return 0;

		$l1 = (0.2126 * pow(hexdec(substr($color,1,2))/255,2.2)) +
			(0.7152 * pow(hexdec(substr($color,3,2))/255,2.2)) +
			(0.0722 * pow(hexdec(substr($color,5,2))/255,2.2));

		$l2 = (0.2126 * pow(hexdec(substr($background,1,2))/255,2.2)) +
		 	(0.7152 * pow(hexdec(substr($background,3,2))/255,2.2)) +
			(0.0722 * pow(hexdec(substr($background,5,2))/255,2.2));

		if ($l1 > $l2) {
			$ratio = ($l1 + 0.05) / ($l2 + 0.05);
		} else {
			$ratio = ($l2 + 0.05) / ($l1 + 0.05);
		}
		return $ratio;
	}

	public static function contrastRatioLevel($ratio,$size,$bold=false)
	{
		if ($size == '') {
			return '';
		}

		// Eval font size in em (assume base font size in pixels equal to 16)
		if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex)?$/',$size,$m)) {
			if (empty($m[2])) {
				$m[2] = 'em';
			}
		} else {
			return '';
		}
		switch ($m[2]) {
			case '%':
				$s = (float) $m[1] / 100;
				break;
			case 'pt':
				$s = (float) $m[1] / 12;
				break;
			case 'px':
				$s = (float) $m[1] / 16;
				break;
			case 'em':
				$s = (float) $m[1];
				break;
			case 'ex':
				$s = (float) $m[1] / 2;
				break;
			default:
				return '';
		}

		$large = ((($s > 1.5) && ($bold == false)) || (($s > 1.2) && ($bold == true)));

		// Check ratio
		if ($ratio > 7) {
			return 'AAA';
		} elseif (($ratio > 4.5) && $large) {
			return 'AAA';
		} elseif ($ratio > 4.5) {
			return 'AA';
		} elseif (($ratio > 3) && $large) {
			return 'AA';
		}
		return '';
	}

	public static function contrastRatio($color,$background,$size='',$bold=false)
	{
		if (($color != '') && ($background != '')) {
			$ratio = computeContrastRatio($color,$background);
			$level = contrastRatioLevel($ratio,$size,$bold);
			return
				sprintf(__('ratio %.1f'),$ratio).
				($level != '' ? ' '.sprintf(__('(%s)'),$level) : '');
		}
		return '';
	}

	public static function adjustFontSize($s)
	{
		if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex)?$/',$s,$m)) {
			if (empty($m[2])) {
				$m[2] = 'em';
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

	// CSS file management
	public static function cleanCSS($css)
	{
		// TODO ?
		return $css;
	}

	public static function cssPath($folder)
	{
		global $core;
		return path::real($core->blog->public_path).'/'.$folder;
	}

	public static function cssURL($folder)
	{
		global $core;
		return $core->blog->settings->system->public_url.'/'.$folder;
	}

	public static function canWriteCss($folder,$create=false)
	{
		global $core;

		$public = path::real($core->blog->public_path);
		$css = self::cssPath($folder);

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
			$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public/'.$folder));
			return false;
		}

		return true;
	}

	public static function prop(&$css,$selector,$prop,$value)
	{
		if ($value) {
			$css[$selector][$prop] = $value;
		}
	}

	public static function backgroundImg($folder,&$css,$selector,$value,$image)
	{
		$file = self::imagesPath($folder).'/'.$image;
		if ($value && file_exists($file)){
			$css[$selector]['background-image'] = 'url('.self::imagesURL($folder).'/'.$image.')';
		}
	}

	public static function writeCss($folder,$theme,$css)
	{
		file_put_contents(self::cssPath($folder).'/'.$theme.'.css', $css);
	}

	public static function dropCss($folder,$theme)
	{
		$file = path::real(self::cssPath($folder).'/'.$theme.'.css');
		if (is_writable(dirname($file))) {
			@unlink($file);
		}
	}

	public static function publicCssUrlHelper($folder)
	{
		$theme = $GLOBALS['core']->blog->settings->system->theme;
		$url = self::cssURL($folder);
		$path = self::cssPath($folder);

		if (file_exists($path.'/'.$theme.'.css')) {
			return $url.'/'.$theme.'.css';
		}

		return null;
	}

	public static function imagesPath($folder)
	{
		global $core;
		return path::real($core->blog->public_path).'/'.$folder;
	}

	public static function imagesURL($folder)
	{
		global $core;
		return $core->blog->settings->system->public_url.'/'.$folder;
	}

	public static function canWriteImages($folder,$create=false)
	{
		global $core;

		$public = path::real($core->blog->public_path);
		$imgs = self::imagesPath($folder);

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
			$core->error->add(sprintf(__('The \'%s\' directory cannot be modified.'),'public/'.$folder));
			return false;
		}

		return true;
	}

	public static function uploadImage($folder,$f)
	{
		if (!self::canWriteImages($folder,true)) {
			throw new Exception(__('Unable to create images.'));
		}

		$name = $f['name'];
		$type = files::getMimeType($name);

		if ($type != 'image/jpeg' && $type != 'image/png') {
			throw new Exception(__('Invalid file type.'));
		}

		$dest = self::imagesPath($folder).'/uploaded'.($type == 'image/png' ? '.png' : '.jpg');

		if (@move_uploaded_file($f['tmp_name'],$dest) === false) {
			throw new Exception(__('An error occurred while writing the file.'));
		}

		$s = getimagesize($dest);
		if ($s[0] != 800) {
			throw new Exception(__('Uploaded image is not 800 pixels wide.'));
		}

		return $dest;
	}

	public static function dropImage($folder,$img)
	{
		$img = path::real(self::imagesPath($folder).'/'.$img);
		if (is_writable(dirname($img))) {
			@unlink($img);
			// Following lines should be replaced by a media thumbnails removal tool
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_m.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_s.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_t.jpg');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.png');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_m.png');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_s.png');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_sq.png');
			@unlink(dirname($img).'/.'.basename($img,'.png').'_t.png');
		}
	}

}
