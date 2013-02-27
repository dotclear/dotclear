<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2010 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****

/**
 * Class daModuleParserException
 */ 
class daModuleParserException extends Exception {}

/**
 * Class daModulesParser
 */
class daModulesParser
{
	protected $xml;
	protected $items;
	
	public function __construct($data)
	{
		if (!is_string($data)) {
			throw new daModuleParserException(__('Impossible to read data feed'));
		}
		
		$this->xml = simplexml_load_string($data);
		$this->items = array();
		
		if ($this->xml === false) {
			throw new daModuleParserException(__('Wrong data feed'));
		}
		
		$this->_parse();
		
		unset($data);
		unset($this->xml);
	}
	
	protected function _parse()
	{
		if (empty($this->xml->module)) {
			return;
		}
		
		foreach ($this->xml->module as $i)
		{
			$attrs = $i->attributes();
			
			$item = array();
			
			# DC/DA shared markers
			$item['id']		= (string) $attrs['id'];
			$item['file']		= (string) $i->file;
			$item['label']		= (string) $i->name;
			$item['version']	= (string) $i->version;
			$item['author']	= (string) $i->author;
			$item['desc']		= (string) $i->desc;
			
			# DA specific markers
			$item['dc_min'] 	= (string) $i->children('http://dotaddict.org/da/')->dcmin;
			$item['details'] 	= (string) $i->children('http://dotaddict.org/da/')->details;
			/*$item['section'] 	= (string) $i->children('http://dotaddict.org/da/')->section;*/
			$item['support'] 	= (string) $i->children('http://dotaddict.org/da/')->support;
			$item['sshot']		= (string) $i->children('http://dotaddict.org/da/')->sshot;
			
			# First filter right now
			if (version_compare(DC_VERSION,$item['dc_min'],'>=')) {
				$this->items[$item['id']] = $item;
			}
		}
	}
	
	public function getModules()
	{
		return $this->items;
	}
}

?>