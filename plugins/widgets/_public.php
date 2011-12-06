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

include dirname(__FILE__).'/_default_widgets.php';
require_once dirname(__FILE__).'/_widgets_functions.php';

$core->tpl->addValue('Widgets',array('publicWidgets','tplWidgets'));
$core->tpl->addBlock('Widget',array('publicWidgets','tplWidget'));

class publicWidgets
{
	public static function tplWidgets($attr)
	{
		$type = isset($attr['type']) ? $attr['type'] : 'nav';
		
		# widgets to disable
		$disable = isset($attr['disable']) ? trim($attr['disable']) : '';
		
		return
		'<?php '.
		"publicWidgets::widgetsHandler('".addslashes($type)."','".addslashes($disable)."'); ".
		' ?>';
	}
	
	public static function widgetsHandler($type,$disable='')
	{
		$wtype = 'widgets_'.$type;
		$GLOBALS['core']->blog->settings->addNameSpace('widgets');
		$widgets = $GLOBALS['core']->blog->settings->widgets->{$wtype};
		
		if (!$widgets) { // If widgets value is empty, get defaults
			$widgets = self::defaultWidgets($type);
		} else { // Otherwise, load widgets
			$widgets = dcWidgets::load($widgets);
		}
		
		if ($widgets->isEmpty()) { // Widgets are empty, don't show anything
			return;
		}
		
		$disable = preg_split('/\s*,\s*/',$disable,-1,PREG_SPLIT_NO_EMPTY);
		$disable = array_flip($disable);
		
		foreach ($widgets->elements() as $k => $w)
		{
			if (isset($disable[$w->id()])) {
				continue;
			}
			echo $w->call($k);
		}
	}
	
	private static function defaultWidgets($type)
	{
		$widgets = new dcWidgets();
		$w = new dcWidgets();
		
		if (isset($GLOBALS['__default_widgets'][$type])) {
			$w = $GLOBALS['__default_widgets'][$type];
		}
		
		return $w;
	}
	
	public static function tplWidget($attr,$content)
	{
		if (!isset($attr['id']) || !($GLOBALS['__widgets']->{$attr['id']} instanceof dcWidget)) {
			return;
		}
		
		# We change tpl:lang syntax, we need it
		$content = preg_replace('/\{\{tpl:lang\s+(.*?)\}\}/msu','{tpl:lang $1}',$content);
		
		# We remove every {{tpl:
		$content = preg_replace('/\{\{tpl:.*?\}\}/msu','',$content);
		
		return
		"<?php publicWidgets::widgetHandler('".addslashes($attr['id'])."','".str_replace("'","\\'",$content)."'); ?>";
	}
	
	public static function widgetHandler($id,$xml)
	{
		$widgets =& $GLOBALS['__widgets'];
		
		if (!($widgets->{$id} instanceof dcWidget)) {
			return;
		}
		
		$xml = '<?xml version="1.0" encoding="utf-8" ?><widget>'.$xml.'</widget>';
		$xml = @simplexml_load_string($xml);
		if (!($xml instanceof SimpleXMLElement)) {
			echo "Invalid widget XML fragment";
			return;
		}
		
		$w = clone $widgets->{$id};
		
		foreach ($xml->setting as $e)
		{
			if (empty($e['name'])) {
				continue;
			}
			
			$setting = (string) $e['name'];
			if (count($e->children())>0) {
				$text = preg_replace('#^<setting[^>]*>(.*)</setting>$#msu','\1', (string)$e->asXML());
			} else {
				$text=$e;
			}
			$w->{$setting} = preg_replace_callback('/\{tpl:lang (.*?)\}/msu',array('self','widgetL10nHandler'),$text);
		}
		
		echo $w->call(0);
	}
	
	private static function widgetL10nHandler($m)
	{
		return __($m[1]);
	}
}
?>