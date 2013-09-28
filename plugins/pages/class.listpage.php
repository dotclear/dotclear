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

/* Pager class
-------------------------------------------------------- */
class adminPageList extends adminGenericList
{
	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No page').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);
			$entries = array();
			if (isset($_REQUEST['entries'])) {
				foreach ($_REQUEST['entries'] as $v) {
					$entries[(integer)$v]=true;
				}
			}			
			$html_block =
			'<div class="table-outer">'.
			'<table class="maximal dragable"><thead><tr>'.
			'<th colspan="3" scope="col" class="first">'.__('Title').'</th>'.
			'<th scope="col">'.__('Date').'</th>'.
			'<th scope="col">'.__('Author').'</th>'.
			'<th scope="col"><img src="images/comments.png" alt="" /><span class="hidden">'.__('Comments').'</span></th>'.
			'<th scope="col"><img src="images/trackbacks.png" alt="" /><span class="hidden">'.__('Trackbacks').'</span></th>'.
			'<th scope="col">'.__('Status').'</th>'.
			'</tr></thead><tbody id="pageslist">%s</tbody></table></div>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo $pager->getLinks();
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			$count = 0;
			while ($this->rs->fetch())
			{
				echo $this->postLine($count,isset($entries[$this->rs->post_id]));
				$count ++;
			}
			
			echo $blocks[1];
			
			echo $pager->getLinks();
		}
	}
	
	private function postLine($count,$checked)
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
			$selected = sprintf($img,__('Hidden'),'hidden.png');
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
		'<td class="nowrap handle minimal">'.form::field(array('order['.$this->rs->post_id.']'),2,3,$count+1,'position','',false,'title="'.sprintf(__('position of %s'),html::escapeHTML($this->rs->post_title)).'"').'</td>'.
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,$checked,'','',!$this->rs->isEditable(),'title="'.__('Select this page').'"').'</td>'.
		'<td class="maximal" scope="row"><a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>'.		
		'<td class="nowrap">'.$this->rs->user_id.'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap count">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>'.
		'</tr>';
		
		return $res;
	}
}
