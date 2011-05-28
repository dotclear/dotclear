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

class dcWidgets
{
	private $__widgets = array();
	
	public static function load($s)
	{
		$o = @unserialize(base64_decode($s));
		
		if ($o instanceof self) {
			return $o;
		} else {
			return self::loadArray($o,$GLOBALS['__widgets']);
		}
	}
	
	public function store()
	{
		$serialized = array();
		foreach ($this->__widgets as $pos=>$w) {
			$serialized[]=($w->serialize($pos));
		}
		return base64_encode(serialize($serialized));
	}
	
	public function create($id,$name,$callback,$append_callback=null)
	{
		$this->__widgets[$id] = new dcWidget($id,$name,$callback);
		$this->__widgets[$id]->append_callback = $append_callback;
	}
	
	public function append($widget)
	{
		if ($widget instanceof dcWidget) {
			if (is_callable($widget->append_callback)) {
				call_user_func($widget->append_callback,$widget);
			}
			$this->__widgets[] = $widget;
		}
	}
	
	public function isEmpty()
	{
		return count($this->__widgets) == 0;
	}
	
	public function elements($sorted=false)
	{
		if ($sorted) {
			uasort($this->__widgets,array('self','sort'));
		}
		return $this->__widgets;
	}
	
	public function __get($id)
	{
		if (!isset($this->__widgets[$id])) {
			return null;
		}
		return $this->__widgets[$id];
	}
	
	public function __wakeup()
	{
		foreach ($this->__widgets as $i => $w)
		{
			if (!($w instanceof dcWidget)) {
				unset($this->__widgets[$i]);
			}
		}
	}
	
	public static function loadArray($A,$widgets)
	{
		if (!($widgets instanceof self)) {
			return false;
		}
		
		uasort($A,array('self','arraySort'));
		
		$result = new self;
		foreach ($A as $v)
		{
			if ($widgets->{$v['id']} != null)
			{
				$w = clone $widgets->{$v['id']};
				
				# Settings
				unset($v['id']);
				unset($v['order']);
				foreach ($v as $sid => $s) {
					$w->{$sid} = $s;
				}
				
				$result->append($w);
			}
		}
		
		return $result;
	}
	
	private static function arraySort($a, $b)
	{
		if ($a['order'] == $b['order']) {
			return 0;
		}
		return $a['order'] > $b['order'] ? 1 : -1;
	}
	
	private static function sort($a,$b)
	{
		$c = $a->name(); 
		$d = $b->name(); 
		if ($c == $d) { 
			return 0; 
		} 
		return ($c < $d) ? -1 : 1; 
	}
}

class dcWidget
{
	private $id;
	private $name;
	private $public_callback = null;
	public $append_callback = null;
	private $settings = array();
	
	public function serialize($order) {
		$values = array();
		foreach ($this->settings as $k=>$v)
			$values[$k]=$v['value'];
		$values['id']=$this->id;
		$values['order']=$order;
		return $values;
	}
	
	public function __construct($id,$name,$callback)
	{
		$this->public_callback = $callback;
		$this->id = $id;
		$this->name = $name;
	}
	
	public function id()
	{
		return $this->id;
	}
	
	public function name()
	{
		return $this->name;
	}
	
	public function getCallback()
	{
		return $this->public_callback;
	}
	
	public function call($i=0)
	{
		if (is_callable($this->public_callback)) {
			return call_user_func($this->public_callback,$this,$i);
		}
		return '<p>Callback not found for widget '.$this->id.'</p>';
	}
	
	/* Widget settings
	--------------------------------------------------- */
	public function __get($n)
	{
		if (isset($this->settings[$n])) {
			return $this->settings[$n]['value'];
		}
		return null;
	}
	
	public function __set($n,$v)
	{
		if (isset($this->settings[$n])) {
			$this->settings[$n]['value'] = $v;
		}
	}
	
	public function setting($name,$title,$value,$type='text')
	{
		if ($type == 'combo') {
			$options = @func_get_arg(4);
			if (!is_array($options)) {
				return false;
			}
		}
		
		$this->settings[$name] = array(
			'title' => $title,
			'type' => $type,
			'value' => $value
		);
		
		if (isset($options)) {
			$this->settings[$name]['options'] = $options;
		}
	}
	
	public function settings()
	{
		return $this->settings;
	}
	
	public function formSettings($pr='',&$i=0)
	{
		$res = '';
		foreach ($this->settings as $id => $s)
		{
			$wfid = "wf-".$i;
			$iname = $pr ? $pr.'['.$id.']' : $id;
			switch ($s['type'])
			{
				case 'text':
					$res .=
					'<p><label for="'.$wfid.'">'.$s['title'].' '.
					form::field(array($iname,$wfid),20,255,html::escapeHTML($s['value']),'maximal').
					'</label></p>';
					break;
				case 'textarea':
					$res .=
					'<p><label for="'.$wfid.'">'.$s['title'].' '.
					form::textarea(array($iname,$wfid),30,5,html::escapeHTML($s['value']),'maximal').
					'</label></p>';
					break;
				case 'check':
					$res .=
					'<p>'.form::hidden(array($iname),'0').
					'<label class="classic" for="'.$wfid.'">'.
					form::checkbox(array($iname,$wfid),'1',$s['value']).' '.$s['title'].
					'</label></p>';
					break;
				case 'combo':
					$res .=
					'<p><label for="'.$wfid.'">'.$s['title'].' '.
					form::combo(array($iname,$wfid),$s['options'],$s['value']).
					'</label></p>';
					break;
			}
			$i++;
		}
		
		return $res;
	}
}
?>