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

class dcMenu
{
	private $id;
	public $title;
	
	public function __construct($id,$title,$itemSpace='')
	{
		$this->id = $id;
		$this->title = $title;
		$this->itemSpace = $itemSpace;
		$this->items = array();
	}
	
	public function addItem($title,$url,$img,$active,$show=true,$id=null,$class=null)
	{
		if($show) {
			$this->items[] = $this->itemDef($title,$url,$img,$active,$id,$class);
		}
	}
	
	public function prependItem($title,$url,$img,$active,$show=true,$id=null,$class=null)
	{
		if ($show) {
			array_unshift($this->items,$this->itemDef($title,$url,$img,$active,$id,$class));
		}
	}
	
	public function draw()
	{
		if (count($this->items) == 0) {
			return '';
		}
		
		$res =
		'<div id="'.$this->id.'">'.
		($this->title ? '<h3>'.$this->title.'</h3>' : '').
		'<ul>'."\n";
		
		for ($i=0; $i<count($this->items); $i++)
		{
			if ($i+1 < count($this->items) && $this->itemSpace != '') {
				$res .= preg_replace('|</li>$|',$this->itemSpace.'</li>',$this->items[$i]);
				$res .= "\n";
			} else {
				$res .= $this->items[$i]."\n";
			}
		}
		
		$res .= '</ul></div>'."\n";
		
		return $res;
	}
	
	protected function itemDef($title,$url,$img,$active,$id=null,$class=null)
	{
		if (is_array($url)) {
			$link = $url[0];
			$ahtml = (!empty($url[1])) ? ' '.$url[1] : '';
		} else {
			$link = $url;
			$ahtml = '';
		}
		
		$img = dc_admin_icon_url($img);
		
		return
		'<li'.(($active || $class) ? ' class="'.(($active) ? 'active ' : '').(($class) ? $class : '').'"' : '').
		(($id) ? ' id="'.$id.'"' : '').
		(($img) ? ' style="background-image: url('.$img.');"' : '').
		'>'.
		
		'<a href="'.$link.'"'.$ahtml.'>'.$title.'</a></li>'."\n";
	}
}
?>