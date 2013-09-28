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

/**
@ingroup DC_CORE
@brief Rpeository modules feed parser

Provides an object to parse feed of modules from repository.
*/
class dcRepositoryParser
{
	protected $xml;
	protected $items;
	protected static $bloc = 'http://dotaddict.org/da/';

	public function __construct($data)
	{
		if (!is_string($data)) {
			throw new Exception(__('Failed to read data feed'));
		}

		$this->xml = simplexml_load_string($data);
		$this->items = array();

		if ($this->xml === false) {
			throw new Exception(__('Wrong data feed'));
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
			$item['label']		= (string) $i->name; // deprecated
			$item['name']		= (string) $i->name;
			$item['version']	= (string) $i->version;
			$item['author']	= (string) $i->author;
			$item['desc']		= (string) $i->desc;

			# DA specific markers
			$item['dc_min'] 	= (string) $i->children(self::$bloc)->dcmin;
			$item['details'] 	= (string) $i->children(self::$bloc)->details;
			$item['section'] 	= (string) $i->children(self::$bloc)->section;
			$item['support'] 	= (string) $i->children(self::$bloc)->support;
			$item['sshot']		= (string) $i->children(self::$bloc)->sshot;
			
			$tags = array();
			foreach($i->children(self::$bloc)->tags as $t)
			{
				$tags[] = (string) $t->tag;
			}
			$item['tags']		= implode(', ',$tags);
			
			# First filter right now
			if (defined('DC_DEV') && DC_DEV === true || version_compare(DC_VERSION,$item['dc_min'],'>=')) {
				$this->items[$item['id']] = $item;
			}
		}
	}

	public function getModules()
	{
		return $this->items;
	}
}
