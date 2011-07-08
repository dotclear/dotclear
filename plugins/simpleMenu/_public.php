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

# Simple menu template functions
$core->tpl->addValue('SimpleMenu',array('tplSimpleMenu','simpleMenu'));

class tplSimpleMenu
{
	public static function simpleMenu($attr)
	{
		$class = isset($attr['class']) ? trim($attr['class']) : '';
		
		return '<?php echo tplSimpleMenu::displayMenu('.
				"'".addslashes($class)."'".
			'); ?>';
	}
	
	public static function displayMenu($class)
	{
		$ret = '';
		
		# Current relative URL
		$url = $_SERVER['REQUEST_URI'];
		$abs_url = http::getHost().$url;
		
		# For detect if home
		$home_url = html::stripHostURL($GLOBALS['core']->blog->url);
		$home_directory = dirname($home_url);
		if ($home_directory != '/')
			$home_directory = $home_directory.'/';
		
		# $href = lien de l'item de menu
		$href = 'archive';
		$href = html::escapeHTML($href);

		# Active item test
		$active = false;
		if (($url == $href) || 
			($abs_url == $href) || 
			($_SERVER['URL_REQUEST_PART'] == $href) || 
			(($_SERVER['URL_REQUEST_PART'] == '') && (($href == $home_url) || ($href == $home_directory)))) {
			$active = true;
		}
		
		$ret = '<p>Archives ? '.($active ? 'Oui' : 'Non').'</p>';
		$ret .= '<p>$class ? '.($class ? $class : '').'</p>';
		/*
		$ret .= '<p>'.'$_SERVER[\'REQUEST_URI\']'.' = '.$_SERVER['REQUEST_URI'].'</p>';
		$ret .= '<p>'.'$_SERVER[\'URL_REQUEST_PART\']'.' = '.$_SERVER['URL_REQUEST_PART'].'</p>';
		$ret .= '<p>'.'$url'.' = '.$url.'</p>';
		$ret .= '<p>'.'$abs_url'.' = '.$abs_url.'</p>';
		$ret .= '<p>'.'$home_url'.' = '.$home_url.'</p>';
		$ret .= '<p>'.'$home_directory'.' = '.$home_directory.'</p>';
		$ret .= '<p>'.'$href'.' = '.$href.'</p>';
		*/
		return $ret;
	}
}

?>