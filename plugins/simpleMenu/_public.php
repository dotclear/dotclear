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

require dirname(__FILE__).'/_widgets.php';

# Simple menu template functions
$core->tpl->addValue('SimpleMenu',array('tplSimpleMenu','simpleMenu'));

class tplSimpleMenu
{
	# Template function
	public static function simpleMenu($attr)
	{
		global $core;

		if (!(boolean) $core->blog->settings->system->simpleMenu_active)
			return '';

		$class = isset($attr['class']) ? trim($attr['class']) : '';
		$id = isset($attr['id']) ? trim($attr['id']) : '';
		$description = isset($attr['description']) ? trim($attr['description']) : '';

		if (!preg_match('#^(title|span)$#',$description)) {
			$description = '';
		}

		return '<?php echo tplSimpleMenu::displayMenu('.
				"'".addslashes($class)."',".
				"'".addslashes($id)."',".
				"'".addslashes($description)."'".
			'); ?>';
	}

	# Widget function
	public static function simpleMenuWidget($w)
	{
		global $core, $_ctx;

		if (!(boolean) $core->blog->settings->system->simpleMenu_active)
			return;

		if (($w->homeonly == 1 && $core->url->type != 'default') ||
			($w->homeonly == 2 && $core->url->type == 'default')) {
			return;
		}

		$menu = tplSimpleMenu::displayMenu('','','title');
		if ($menu == '') {
			return;
		}

		return $w->renderDiv($w->content_only,'simple-menu '.$w->class,'',
			($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '').$menu);
	}

	public static function displayMenu($class='',$id='',$description='')
	{
		global $core;

		$ret = '';

		if (!(boolean) $core->blog->settings->system->simpleMenu_active)
			return $ret;

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
				$title = $span = '';
				if ($m['descr']) {
					if ($description == 'title') {
						$title = ' title="'.html::escapeHTML(__($m['descr'])).'"';
					} else {
						$span = ' <span>'.html::escapeHTML(__($m['descr'])).'</span>';
					}
				}
				$label = html::escapeHTML(__($m['label']));

				$item = new ArrayObject(array(
					'url' => $href,					// URL
					'label' => $label,				// <a> link label
					'title' => $title,				// <a> link title (optional)
					'span' => $span,				// description (will be displayed after <a> link)
					'active' => $active,			// status (true/false)
					'class' => ''					// additional <li> class (optional)
					));

				# --BEHAVIOR-- publicSimpleMenuItem
				$core->callBehavior('publicSimpleMenuItem',$i,$item);

				$ret .= '<li class="li'.($i+1).
							($item['active'] ? ' active' : '').
							($i == 0 ? ' li-first' : '').
							($i == count($menu)-1 ? ' li-last' : '').
							($item['class'] ? $item['class'] : '').
						'">'.
						'<a href="'.$href.'"'.$item['title'].'>'.$item['label'].$item['span'].'</a>'.
						'</li>';
			}

			// Final rendering
			if ($ret) {
				$ret = '<ul '.($id ? 'id="'.$id.'"' : '').' class="simple-menu'.($class ? ' '.$class : '').'" role="navigation">'."\n".$ret."\n".'</ul>';
			}
		}

		return $ret;
	}
}
