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
		$id = isset($attr['id']) ? trim($attr['id']) : '';
		
		return '<?php echo tplSimpleMenu::displayMenu('.
				"'".addslashes($class)."',".
				"'".addslashes($id)."'".
			'); ?>';
	}
	
	public static function displayMenu($class,$id)
	{
		$ret = '';

		$menu = $GLOBALS['core']->blog->settings->system->get('simpleMenu');
		$menu = @unserialize($menu);

		if (is_array($menu)) 
		{
			// Current relative URL
			$url = $_SERVER['REQUEST_URI'];
			$abs_url = http::getHost().$url;
		
			// Home recognition var
			$home_url = html::stripHostURL($GLOBALS['core']->blog->url);
			$home_directory = dirname($home_url);
			if ($home_directory != '/')
				$home_directory = $home_directory.'/';

			// Menu items loop
			foreach ($menu as $i => $m) {
				# $href = lien de l'item de menu
				$href = $m['url'];
				$href = html::escapeHTML($href);

				# Active item test
				$active = false;
				if (($url == $href) || 
					($abs_url == $href) || 
					($_SERVER['URL_REQUEST_PART'] == $href) || 
					(($_SERVER['URL_REQUEST_PART'] == '') && (($href == $home_url) || ($href == $home_directory)))) {
					$active = true;
				}
				$ret .= '<li class="li'.($i+1).
							($active ? ' active' : '').
							($i == 0 ? ' li-first' : '').
							($i == count($menu)-1 ? ' li-last' : '').
						'">'.
						'<a href="'.$href.'">'.__($m['label']).($m['descr'] ? '<span>'.__($m['descr']).'</span>' : '').'</a>'.
						'</li>';
			}
			
			// Final rendering
			if ($ret) {
				$ret = '<ul '.($id ? 'id="'.$id.'"' : '').' class="simple-menu'.($class ? ' '.$class : '').'">'."\n".$ret."\n".'</ul>';
			}
		}

		return $ret;
	}
}

?>