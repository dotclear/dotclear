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

class adminGenericList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	
	public function __construct($core,$rs,$rs_count)
	{
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->html_prev = __('&#171; prev.');
		$this->html_next = __('next &#187;');
	}
}

class adminPostList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			$entries = array();
			if (isset($_REQUEST['entries'])) {
				foreach ($_REQUEST['entries'] as $v) {
					$entries[(integer)$v]=true;
				}
			}
			$html_block =
			'<table class="clear"><caption class="hidden">'.__('Entries list').'</caption><tr>'.
			'<th colspan="2" class="first">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Category').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col">'.__('Comments').'</th>'.
			'<th scope="col">'.__('Trackbacks').'</th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->postLine(isset($entries[$this->rs->post_id]));
			}
			
			echo $blocks[1];
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function postLine($checked)
	{
		if ($this->core->auth->check('categories',$this->core->blog->id)) {
			$cat_link = '<a href="category.php?id=%s">%s</a>';
		} else {
			$cat_link = '%2$s';
		}
		
		if ($this->rs->cat_title) {
			$cat_title = sprintf($cat_link,$this->rs->cat_id,
			html::escapeHTML($this->rs->cat_title));
		} else {
			$cat_title = __('(No cat)');
		}
		
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}
		
		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
		
		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,$checked,'','',!$this->rs->isEditable()).'</td>'.
		'<td class="maximal" scope="row"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$cat_title.'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_id).'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';
		
		return $res;
	}
}

class adminPostMiniList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><caption class="hidden">'.__('Entries list').'</caption><tr>'.
			'<th scope="col">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->postLine();
			}
			
			echo $blocks[1];
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function postLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Scheduled'),'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
		}
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('Protected'),'locker.png');
		}
		
		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('Selected'),'selected.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		$res = '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">';
		
		$res .=
		'<td scope="row" class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'" '.
		'title="'.html::escapeHTML($this->rs->getURL()).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_id).'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';
		
		return $res;
	}
}

class adminCommentList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No comment').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			$comments = array();
			if (isset($_REQUEST['comments'])) {
				foreach ($_REQUEST['comments'] as $v) {
					$comments[(integer)$v]=true;
				}
			}			
			$html_block =
			'<table><caption class="hidden">'.__('Comments and trackbacks list').'</caption><tr>'.
			'<th colspan="2" scope="col" abbr="comm" class="first">'.__('Type').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col" class="txt-center">'.__('Status').'</th>'.
			'<th scope="col" abbr="entry">'.__('Entry title').'</th>'.
			'</tr>%s</table>';

			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->commentLine(isset($comments[$this->rs->comment_id]));
			}
			
			echo $blocks[1];
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function commentLine($checked)
	{
		global $author, $status, $sortby, $order, $nb_per_page;
		
		$author_url =
		'comments.php?n='.$nb_per_page.
		'&amp;status='.$status.
		'&amp;sortby='.$sortby.
		'&amp;order='.$order.
		'&amp;author='.rawurlencode($this->rs->comment_author);
		
		$post_url = $this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id);
		
		$comment_url = 'comment.php?id='.$this->rs->comment_id;
		
		$comment_dt =
		dt::dt2str($this->core->blog->settings->system->date_format.' - '.
		$this->core->blog->settings->system->time_format,$this->rs->comment_dt);
		
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->comment_status) {
			case 1:
				$img_status = sprintf($img,__('Published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('Unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('Pending'),'check-wrn.png');
				break;
			case -2:
				$img_status = sprintf($img,__('Junk'),'junk.png');
				break;
		}
		
		$post_title = html::escapeHTML($this->rs->post_title);
		if (mb_strlen($post_title) > 60) {
			$post_title = mb_strcut($post_title,0,57).'...';
		}
		$comment_title = sprintf(__('Edit the %1$s from %2$s'),
			$this->rs->comment_trackback ? __('trackback') : __('comment'),
			html::escapeHTML($this->rs->comment_author));
		
		$res = '<tr class="line'.($this->rs->comment_status != 1 ? ' offline' : '').'"'.
		' id="c'.$this->rs->comment_id.'">';
		
		$res .=
		'<td class="nowrap">'.
		form::checkbox(array('comments[]'),$this->rs->comment_id,$checked,'','',0).'</td>'.
		'<td class="nowrap" abbr="'.__('Type and author').'" scope="raw">'.
			'<a href="'.$comment_url.'" title="'.$comment_title.'">'.
			'<img src="images/edit-mini.png" alt="'.__('Edit').'"/> '.
			($this->rs->comment_trackback ? __('trackback') : __('comment')).' '.'</a></td>'.
		'<td class="nowrap maximal"><a href="'.$author_url.'">'.html::escapeHTML($this->rs->comment_author).'</a></td>'.
		'<td class="nowrap count">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->comment_dt).'</td>'.
		'<td class="nowrap status txt-center">'.$img_status.'</td>'.
		'<td class="nowrap"><a href="'.$post_url.'">'.
		html::escapeHTML($post_title).'</a>'.
		($this->rs->post_type != 'post' ? ' ('.html::escapeHTML($this->rs->post_type).')' : '').'</td>';
		
		$res .= '</tr>';
		
		return $res;
	}
}

class adminUserList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No user').'</strong></p>';
		}
		else
		{
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="clear"><caption class="hidden">'.__('Users list').'</caption><tr>'.
			'<th colspan="2" scope="col" class="first">'.__('Username').'</th>'.
			'<th scope="col">'.__('First Name').'</th>'.
			'<th scope="col">'.__('Last Name').'</th>'.
			'<th scope="col">'.__('Display name').'</th>'.
			'<th scope="col" class="nowrap">'.__('Entries (all types)').'</th>'.
			'</tr>%s</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->userLine();
			}
			
			echo $blocks[1];
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function userLine()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		$img_status = '';
		
		$p = $this->core->getUserPermissions($this->rs->user_id);
		
		if (isset($p[$this->core->blog->id]['p']['admin'])) {
			$img_status = sprintf($img,__('admin'),'admin.png');
		}
		if ($this->rs->user_super) {
			$img_status = sprintf($img,__('superadmin'),'superadmin.png');
		}
		return
		'<tr class="line">'.
		'<td class="nowrap">'.form::hidden(array('nb_post[]'),(integer) $this->rs->nb_post).
		form::checkbox(array('users[]'),$this->rs->user_id).'</td>'.
		'<td class="maximal" scope="row"><a href="user.php?id='.$this->rs->user_id.'">'.
		$this->rs->user_id.'</a>&nbsp;'.$img_status.'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_firstname).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_name).'</td>'.
		'<td class="nowrap">'.html::escapeHTML($this->rs->user_displayname).'</td>'.
		'<td class="nowrap count"><a href="posts.php?user_id='.$this->rs->user_id.'">'.
		$this->rs->nb_post.'</a></td>'.
		'</tr>';
	}
}
?>