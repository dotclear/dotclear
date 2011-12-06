<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

class dcFilterLinksLookup extends dcSpamFilter
{
	public $name = 'Links Lookup';
	
	private $server = 'multi.surbl.org';
	
	protected function setInfo()
	{
		$this->description = __('Checks links in comments against surbl.org');
	}
	
	public function getStatusMessage($status,$comment_id)
	{
		return sprintf(__('Filtered by %1$s with server %2$s.'),$this->guiLink(),$status);
	}
	
	public function isSpam($type,$author,$email,$site,$ip,$content,$post_id,&$status)
	{
		if (!$ip || long2ip(ip2long($ip)) != $ip) {
			return;
		}
		
		$urls = $this->getLinks($content);
		array_unshift($urls,$site);
		
		foreach ($urls as $u)
		{
			$b = parse_url($u);
			if (!isset($b['host']) || !$b['host']) {
				continue;
			}
			
			$domain = preg_replace('/^(.*\.)([^.]+\.[^.]+)$/','$2',$b['host']);
			$host = $domain.'.'.$this->server;
			
			if (gethostbyname($host) != $host) {
				$status = substr($domain,0,128);
				return true;
			}
		}
	}
	
	private function getLinks($text)
	{
		$res = array();
		
		# href attribute on "a" tags
		if (preg_match_all('/<a ([^>]+)>/ms', $text, $match, PREG_SET_ORDER))
		{
			for ($i = 0; $i<count($match); $i++)
			{
				if (preg_match('/href="(http:\/\/[^"]+)"/ms', $match[$i][1], $matches)) {
					$res[] = $matches[1];
				}
			}
		}
		
		return $res;
	}
}
?>