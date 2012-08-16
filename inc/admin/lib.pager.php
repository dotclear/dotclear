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

class adminGenericList
{
	protected $core;
	protected $items;
	protected $item_count;
	
	public function __construct($core,$items,$item_count)
	{
		$this->core =& $core;
		$this->items =& $items;
		$this->item_count = $item_count;
		$this->html_prev = __('&#171;prev.');
		$this->html_next = __('next&#187;');
	}
}

class adminPostList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if (count($this->items) == 0)
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->item_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><tr>'.
			'<th colspan="2">'.__('Title').'</th>'.
			'<th>'.__('Date').'</th>'.
			'<th>'.__('Author').'</th>'.
			'<th>'.__('Status').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			foreach ($this->items as $item)
			{
				echo $this->postLine($item);
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function postLine($item)
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($item->post_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
		}
		
		$selected = '';
		if ($item->post_selected) {
			$selected = sprintf($img,__('selected'),'selected.png');
		}
		
		
		$res = '<tr class="line'.($item->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$item->post_id.'">';
		
		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$item->post_id,'','','',!$item->isEditable()).'</td>'.
		'<td class="maximal"><a href="'.$this->core->getPostAdminURL($item->post_type,$item->post_id).'">'.
		html::escapeHTML($item->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$item->post_dt).'</td>'.
		'<td class="nowrap">'.$item->user_id.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.'</td>'.
		'</tr>';
		
		return $res;
	}
}

class adminPostMiniList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if (count($this->items) == 0)
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->item_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><tr>'.
			'<th>'.__('Title').'</th>'.
			'<th>'.__('Date').'</th>'.
			'<th>'.__('Author').'</th>'.
			'<th>'.__('Status').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			foreach ($this->items as $item)
			{
				echo $this->postLine($item);
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function postLine($item)
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($item->post_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
		}
		
		$selected = '';
		if ($item->post_selected) {
			$selected = sprintf($img,__('selected'),'selected.png');
		}
		
		
		$res = '<tr class="line'.($item->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$item->post_id.'">';
		
		$res .=
		'<td class="maximal"><a href="'.$this->core->getPostAdminURL($item->post_type,$item->post_id).'" '.
		'title="'.html::escapeHTML($item->getURL()).'">'.
		html::escapeHTML($item->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$item->post_dt).'</td>'.
		'<td class="nowrap">'.$item->user_id.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.'</td>'.
		'</tr>';
		
		return $res;
	}
}

class adminUserList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if (count($this->items) == 0)
		{
			echo '<p><strong>'.__('No user').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->item_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><tr>'.
			'<th colspan="2">'.__('Username').'</th>'.
			'<th>'.__('First Name').'</th>'.
			'<th>'.__('Last Name').'</th>'.
			'<th>'.__('Display name').'</th>'.
			'<th class="nowrap">'.__('Entries').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			foreach ($this->items as $item)
			{
				echo $this->userLine($item);
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function userLine($item)
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		$img_status = '';
		
		$p = $this->core->getUserPermissions($item->user_id);
		
		if (isset($p[$this->core->blog->id]['p']['admin'])) {
			$img_status = sprintf($img,__('admin'),'admin.png');
		}
		if ($item->user_super) {
			$img_status = sprintf($img,__('superadmin'),'superadmin.png');
		}
		return
		'<tr class="line">'.
		'<td class="nowrap">'.form::hidden(array('nb_post[]'),(integer) $item->nb_post).
		form::checkbox(array('user_id[]'),$item->user_id).'</td>'.
		'<td class="maximal"><a href="user.php?id='.$item->user_id.'">'.
		$item->user_id.'</a>&nbsp;'.$img_status.'</td>'.
		'<td class="nowrap">'.$item->user_firstname.'</td>'.
		'<td class="nowrap">'.$item->user_name.'</td>'.
		'<td class="nowrap">'.$item->user_displayname.'</td>'.
		'<td class="nowrap"><a href="posts.php?user_id='.$item->user_id.'">'.
		$item->nb_post.'</a></td>'.
		'</tr>';
	}
}
?>