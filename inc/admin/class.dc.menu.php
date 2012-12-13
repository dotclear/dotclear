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
	private $items;
	private $id;
	public $title;
	public $separator;
	
	public function __construct($id,$title,$separator='')
	{
		$this->id = $id;
		$this->title = $title;
		$this->separator = $separator;
		$this->items = array();
	}
	
	public function getID()
	{
		return $this->id;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function getSeparator()
	{
		return $this->separator;
	}
	
	public function getItems()
	{
		return $this->items;
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
	
	protected function itemDef($title,$url,$img,$active,$id=null,$class=null)
	{
		if (is_array($url)) {
			$link = $url[0];
			$ahtml = (!empty($url[1])) ? ' '.$url[1] : '';
		} else {
			$link = $url;
			$ahtml = '';
		}
		
		return array(
			'title' => $title,
			'link' => $link,
			'ahtml' => $ahtml,
			'img' => dc_admin_icon_url($img),
			'active' => (boolean) $active,
			'id' => $id,
			'class' => $class
		);
	}
	
	/**
	@deprecated Use Template engine instead
	*/
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
			if ($i+1 < count($this->items) && $this->separator != '') {
				$res .= preg_replace('|</li>$|',$this->separator.'</li>',$this->drawItem($this->items[$i]));
				$res .= "\n";
			} else {
				$res .= $this->drawItem($this->items[$i])."\n";
			}
		}
		
		$res .= '</ul></div>'."\n";
		
		return $res;
	}
	
	/**
	@deprecated Use Template engine instead
	*/
	protected function drawItem($item)
	{
		return
		'<li'.(($item['active'] || $item['class']) ? ' class="'.(($item['active']) ? 'active ' : '').(($item['class']) ? $item['class'] : '').'"' : '').
		(($item['id']) ? ' id="'.$item['id'].'"' : '').
		(($item['img']) ? ' style="background-image: url('.$item['img'].');"' : '').
		'>'.
		
		'<a href="'.$item['link'].'"'.$item['ahtml'].'>'.$item['title'].'</a></li>'."\n";
	}
}
?>