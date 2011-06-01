<?php

class dcToolbar
{
	protected $core;
	protected $toolbars;
	
	public function __construct($core)
	{
		$this->core = $core;
		$this->toolbars = array();
	}
	
	public function addFormatter($formatter)
	{
		if (!is_string($formatter) || empty($formatter)) {
			return;
		}
		
		$toolbar = array(
			'settings' => array(),
			'plugins' => array(),
			'buttons' => array(
				1 => array(),
				2 => array(),
				3 => array(),
				4 => array()
			)
		);
		
		$this->toolbars[$formatter] = array_key_exists($formatter,$this->toolbars) ? $this->toolbars[$formatter] : $toolbar;
	}
	
	public function addSettings($formatter,$settings)
	{
		
		if (!array_key_exists($formatter,$this->toolbars)) {
			return;
		}
		if (!is_array($settings)) {
			return;
		}
		
		$this->toolbars[$formatter]['settings'] = array_merge($this->toolbars[$formatter]['settings'],$settings);
	}
	
	public function addPlugins($formatter,$plugins)
	{
		if (!array_key_exists($formatter,$this->toolbars)) {
			return;
		}
		if (!is_array($plugins)) {
			return;
		}
		
		$this->toolbars[$formatter]['plugins'] = array_merge($this->toolbars[$formatter]['plugins'],$plugins);
	}
	
	public function addButtons($formatter,$buttons)
	{
		if (!array_key_exists($formatter,$this->toolbars)) {
			return;
		}
		if (!is_array($buttons)) {
			return;
		}
		
		foreach($buttons as $level => $ids) {
			$level = !preg_match('#^[1-4]$#',$level) ? 1 : $level;
			$this->toolbars[$formatter]['buttons'][$level] = array_merge($this->toolbars[$formatter]['buttons'][$level],$ids);
		}
	}
	
	public function getJS()
	{
		$res = "dcToolBar = new dcToolBar();\n";
		
		foreach ($this->toolbars as $formatter => $options) {
			$s = $options['settings'];
			
			// Add plugins
			array_walk($options['plugins'],create_function('&$v,$k','$v=!$v ? "-".$k : $k;'));
			$s['plugins'] = implode(',',$options['plugins']);
			
			// Add buttons
			foreach ($options['buttons'] as $level => $buttons) {
				$s[sprintf('theme_advanced_buttons%d',$level)] = implode(',',$buttons);
			}
			
			// Add configuration
			array_walk($s,create_function('&$v,$k','$v=sprintf("%s: %s",$k,(!preg_match("#^(true|false|{.*})$#",$v)?"\'".$v."\'":$v));'));
			$res .= sprintf("dcToolBar.setConfig('%s',{%s});\n",$formatter,implode(",\n",$s));
		}
		
		return $res;
	}
}

?>
