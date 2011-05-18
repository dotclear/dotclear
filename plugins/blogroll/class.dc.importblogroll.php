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

class linksImporter
{
	protected $entries = null;
		
	public function parse($data)
	{
		if (preg_match('!<xbel(\s+version)?!', $data)) {
			$this->_parseXBEL($data);
		}
		elseif (preg_match('!<opml(\s+version)?!', $data)) {
			$this->_parseOPML($data);
		}
		else {
			throw new Exception(__('You need to provide a XBEL or OPML file.'));
		}
	}
	
	
	protected function _parseOPML($data)
	{
		$xml = @simplexml_load_string($data);
		if (!$xml) throw new Exception(__('File is not in XML format.'));
		
		$outlines = $xml->xpath("//outline");
		
		$this->entries = array();
		foreach ($outlines as $outline) {
			if (isset($outline['htmlUrl'])) {
				$link = $outline['htmlUrl'];
			}
			elseif (isset($outline['url'])) {
				$link = $outline['url'];
			}
			else continue;
			$entry = new StdClass();
			$entry->link = $link;
			$entry->title = (!empty($outline['title']))?$outline['title']:'';
			if (empty($entry->title)) {
				$entry->title = (!empty($outline['text']))?$outline['text']:$entry->link;
			}
			$entry->desc = (!empty($outline['description']))?$outline['description']:'';
			$this->entries[] = $entry;
		}
	}
	
	protected function _parseXBEL($data)
	{
		$xml = @simplexml_load_string($data);
		if (!$xml) throw new Exception(__('File is not in XML format.'));
		
		$outlines = $xml->xpath("//bookmark");
		
		$this->entries = array();
		foreach ($outlines as $outline) {
			if (!isset($outline['href'])) continue;
			$entry = new StdClass();
			$entry->link = $outline['href'];
			$entry->title = (!empty($outline->title))?$outline->title:'';
			if (empty($entry->title)) {
				$entry->title = $entry->link;
			}
			$entry->desc = (!empty($outline->desc))?$outline->desc:'';
			$this->entries[] = $entry;
		}
	}
	
	
	public function getAll()
	{
		if (!$this->entries) return null;
		return $this->entries;
	}
		
}

class dcImportBlogroll {
	
	public static function loadFile($file)
	{
		if (file_exists($file) && is_readable($file)) {
			$importer = new linksImporter();
			$importer->parse(file_get_contents($file));		
			return $importer->getAll();
		}
		return false;
	}
}
?>