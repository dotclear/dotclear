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

class pingsAPI extends xmlrpcClient
{
	public static function doPings($srv_uri,$site_name,$site_url)
	{
		$o = new self($srv_uri);
		$o->timeout = 3;
		
		$rsp = $o->query('weblogUpdates.ping',$site_name,$site_url);
		
		if (isset($rsp['flerror']) && $rsp['flerror']) {
			throw new Exception($rsp['message']);
		}
		
		return true;
	}
}

class pingsBehaviors
{
	public static function pingJS()
	{
		$res =
		"<script type=\"text/javascript\">\n//<![CDATA[\n".
		dcPage::jsVar('dotclear.msg.check_all',__('Check all'))."\n".
		"</script>\n".dcPage::jsLoad('index.php?pf=pings/post.js');
		
		return $res;
	}
	
	public static function pingsForm($post)
	{
		$core =& $GLOBALS['core'];
		if (!$core->blog->settings->pings->pings_active) {
			return;
		}
		
		$pings_uris = @unserialize($core->blog->settings->pings->pings_uris);
		if (empty($pings_uris) || !is_array($pings_uris)) {
			return;
		}
		
		if (!empty($_POST['pings_do']) && is_array($_POST['pings_do'])) {
			$pings_do = $_POST['pings_do'];
		} else {
			$pings_do = array();
		}
		
		echo '<h3 class="ping-services">'.__('Pings:').'</h3>';
		$i = 0;
		foreach ($pings_uris as $k => $v)
		{
			echo
			'<p class="ping-services"><label for="pings_do-'.$i.'" class="classic">'.
			form::checkbox(array('pings_do[]','pings_do-'.$i),html::escapeHTML($v),in_array($v,$pings_do)).' '.
			html::escapeHTML($k).'</label></p>';
			$i++;
		}
	}
	
	public static function doPings($cur,$post_id)
	{
		if (empty($_POST['pings_do']) || !is_array($_POST['pings_do'])) {
			return;
		}
		
		$core =& $GLOBALS['core'];
		if (!$core->blog->settings->pings->pings_active) {
			return;
		}
		
		$pings_uris = @unserialize($core->blog->settings->pings->pings_uris);
		if (empty($pings_uris) || !is_array($pings_uris)) {
			return;
		}
		
		foreach ($_POST['pings_do'] as $uri)
		{
			if (in_array($uri,$pings_uris)) {
				try {
					pingsAPI::doPings($uri,$core->blog->name,$core->blog->url);
				} catch (Exception $e) {}
			}
		}
	}
}
?>