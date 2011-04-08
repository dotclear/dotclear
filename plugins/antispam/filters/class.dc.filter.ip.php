<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Antispam, a plugin for Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

class dcFilterIP extends dcSpamFilter
{
	public $name = 'IP Filter';
	public $has_gui = true;
	
	private $style_list = 'height: 200px; overflow: auto; margin-bottom: 1em; ';
	private $style_p = 'margin: 1px 0 0 0; padding: 0.2em 0.5em; ';
	private $style_global = 'background: #ccff99; ';
	
	private $con;
	private $table;
	
	public function __construct($core)
	{
		parent::__construct($core);
		$this->con =& $core->con;
		$this->table = $core->prefix.'spamrule';
	}
	
	protected function setInfo()
	{
		$this->description = __('IP Blacklist / Whitelist Filter');
	}
	
	public function getStatusMessage($status,$comment_id)
	{
		return sprintf(__('Filtered by %1$s with rule %2$s.'),$this->guiLink(),$status);
	}
	
	public function isSpam($type,$author,$email,$site,$ip,$content,$post_id,&$status)
	{
		if (!$ip) {
			return;
		}
		
		# White list check
		if ($this->checkIP($ip,'white') !== false) {
			return false;
		}
		
		# Black list check
		if (($s = $this->checkIP($ip,'black')) !== false) {
			$status = $s;
			return true;
		}
	}
	
	public function gui($url)
	{
		global $default_tab;
		$core =& $this->core;
		
		# Set current type and tab
		$ip_type = 'black';
		if (!empty($_REQUEST['ip_type']) && $_REQUEST['ip_type'] == 'white') {
			$ip_type = 'white';
		}
		$default_tab = 'tab_'.$ip_type;
		
		# Add IP to list
		if (!empty($_POST['addip']))
		{
			try
			{
				$global = !empty($_POST['globalip']) && $core->auth->isSuperAdmin();
				
				$this->addIP($ip_type,$_POST['addip'],$global);
				http::redirect($url.'&added=1&ip_type='.$ip_type);
			}
			catch (Exception $e)
			{
				$core->error->add($e->getMessage());
			}
		}
		
		# Remove IP from list
		if (!empty($_POST['delip']) && is_array($_POST['delip']))
		{
			try {
				$this->removeRule($_POST['delip']);
				http::redirect($url.'&removed=1&ip_type='.$ip_type);
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}
		
		/* DISPLAY
		---------------------------------------------- */
		$res = '';
		
		if (!empty($_GET['added'])) {
			$res .= '<p class="message">'.__('IP address has been successfully added.').'</p>';
		}
		if (!empty($_GET['removed'])) {
			$res .= '<p class="message">'.__('IP addresses have been successfully removed.').'</p>';
		}
		
		$res .=
		$this->displayForms($url,'black',__('Blacklist')).
		$this->displayForms($url,'white',__('Whitelist'));
		
		return $res;
	}
	
	private function displayForms($url,$type,$title)
	{
		$core =& $this->core;
		
		$res =
		'<div class="multi-part" id="tab_'.$type.'" title="'.$title.'">'.
		
		'<form action="'.html::escapeURL($url).'" method="post">'.
		'<fieldset><legend>'.__('Add an IP address').'</legend><p>'.
		form::hidden(array('ip_type'),$type).
		form::field(array('addip'),18,255).' ';
		
		if ($core->auth->isSuperAdmin()) {
			$res .= '<label class="classic">'.form::checkbox(array('globalip'),1).' '.
			__('Global IP').'</label> ';
		}
		
		$res .=
		$core->formNonce().
		'<input type="submit" value="'.__('Add').'"/></p>'.
		'</fieldset></form>';
		
		$rs = $this->getRules($type);
		
		if ($rs->isEmpty())
		{
			$res .= '<p><strong>'.__('No IP address in list.').'</strong></p>';
		}
		else
		{
			$res .=
			'<form action="'.html::escapeURL($url).'" method="post">'.
			'<fieldset><legend>' . __('IP list') . '</legend>'.
			'<div style="'.$this->style_list.'">';
			
			while ($rs->fetch())
			{
				$bits = explode(':',$rs->rule_content);
				$pattern = $bits[0];
				$ip = $bits[1];
				$bitmask = $bits[2];
				
				$disabled_ip = false;
				$p_style = $this->style_p;
				if (!$rs->blog_id) {
					$disabled_ip = !$core->auth->isSuperAdmin();
					$p_style .= $this->style_global;
				}
				
				$res .=
				'<p style="'.$p_style.'"><label class="classic">'.
				form::checkbox(array('delip[]'),$rs->rule_id,false,'','',$disabled_ip).' '.
				html::escapeHTML($pattern).
				'</label></p>';
			}
			$res .=
			'</div>'.
			'<p><input class="submit" type="submit" value="'.__('Delete').'"/>'.
			$core->formNonce().
			form::hidden(array('ip_type'),$type).
			'</p>'.
			'</fieldset></form>';
		}	
		
		$res .= '</div>';
		
		return $res;
	}
	
	private function ipmask($pattern,&$ip,&$mask)
	{
		$bits = explode('/',$pattern);
		
		# Set IP
		$bits[0] .= str_repeat(".0", 3 - substr_count($bits[0], "."));
		$ip = ip2long($bits[0]);
		
		if (!$ip || $ip == -1) {
			throw new Exception('Invalid IP address');
		}
		
		# Set mask
		if (!isset($bits[1])) {
			$mask = -1;
		} elseif (strpos($bits[1],'.')) {
			$mask = ip2long($bits[1]);
			if (!$mask) {
				$mask = -1;
			}
		} else {
			$mask = ~((1 << (32 - $bits[1])) - 1);
		}
	}
	
	private function addIP($type,$pattern,$global)
	{
		$this->ipmask($pattern,$ip,$mask);
		$pattern = long2ip($ip).($mask != -1 ? '/'.long2ip($mask) : '');
		$content = $pattern.':'.$ip.':'.$mask;
		
		$old = $this->getRuleCIDR($type,$global,$ip,$mask);
		$cur = $this->con->openCursor($this->table);
		
		if ($old->isEmpty())
		{
			$id = $this->con->select('SELECT MAX(rule_id) FROM '.$this->table)->f(0) + 1;
			
			$cur->rule_id = $id;
			$cur->rule_type = (string) $type;
			$cur->rule_content = (string) $content;
			
			if ($global && $this->core->auth->isSuperAdmin()) {
				$cur->blog_id = null;
			} else {
				$cur->blog_id = $this->core->blog->id;
			}
			
			$cur->insert();
		}
		else
		{
			$cur->rule_type = (string) $type;
			$cur->rule_content = (string) $content;
			$cur->update('WHERE rule_id = '.(integer) $old->rule_id);
		}
	}
	
	private function getRules($type='all')
	{
		$strReq =
		'SELECT rule_id, rule_type, blog_id, rule_content '.
		'FROM '.$this->table.' '.
		"WHERE rule_type = '".$this->con->escape($type)."' ".
		"AND (blog_id = '".$this->core->blog->id."' OR blog_id IS NULL) ".
		'ORDER BY blog_id ASC, rule_content ASC ';
		
		return $this->con->select($strReq);
	}
	
	private function getRuleCIDR($type,$global,$ip,$mask)
	{
		$strReq =
		'SELECT * FROM '.$this->table.' '.
		"WHERE rule_type = '".$this->con->escape($type)."' ".
		"AND rule_content LIKE '%:".(integer) $ip.":".(integer) $mask."' ".
		'AND blog_id '.($global ? 'IS NULL ' : "= '".$this->core->blog->id."' ");
		
		return $this->con->select($strReq);
	}
	
	private function checkIP($cip,$type)
	{
		$core =& $this->core;
		
		$strReq =
		'SELECT DISTINCT(rule_content) '.
		'FROM '.$this->table.' '.
		"WHERE rule_type = '".$this->con->escape($type)."' ".
		"AND (blog_id = '".$this->core->blog->id."' OR blog_id IS NULL) ".
		'ORDER BY rule_content ASC ';
		
		$rs = $this->con->select($strReq);
		while ($rs->fetch())
		{
			list($pattern,$ip,$mask) = explode(':',$rs->rule_content);
			if ((ip2long($cip) & (integer) $mask) == ((integer) $ip & (integer) $mask)) {
				return $pattern;
			}
		}
		return false;
	}
	
	private function removeRule($ids)
	{
		$strReq = 'DELETE FROM '.$this->table.' ';
		
		if (is_array($ids)) {
			foreach ($ids as $i => $v) {
				$ids[$i] = (integer) $v;
			}
			$strReq .= 'WHERE rule_id IN ('.implode(',',$ids).') ';
		} else {
			$ids = (integer) $ids;
			$strReq .= 'WHERE rule_id = '.$ids.' ';
		}
		
		if (!$this->core->auth->isSuperAdmin()) {
			$strReq .= "AND blog_id = '".$this->core->blog->id."' ";
		}
		
		$this->con->execute($strReq);
	}
}
?>