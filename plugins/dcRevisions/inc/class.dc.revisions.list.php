<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of dcRevisions, a plugin for Dotclear.
#
# Copyright (c) 2010 Tomtom and contributors
# http://blog.zenstyle.fr/
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

class dcRevisionsList
{
	protected $rs = null;
	
	public function __construct($rs)
	{
		$this->rs = $rs;
		$this->core = $rs->core;
	}
	
	public function display($url)
	{
		$res = '';
		
		if (!$this->rs->isEmpty()) {
			$html_block =
				'<table id="revisions-list" summary="'.__('Revisions').'" class="clear maximal" style="display: none;">'.
				'<thead>'.
				'<tr>'.
				'<th>'.__('Id').'</th>'.
				'<th class="nowrap">'.__('Author').'</th>'.
				'<th class="nowrap">'.__('Date').'</th>'.
				'<th class="nowrap">'.__('Status').'</th>'.
				'<th class="nowrap">'.__('Actions').'</th>'.
				'</tr>'.
				'</thead>'.
				'<tbody>%s</tbody>'.
				'</table>';
				
			$res .= sprintf($html_block,$this->getLines($url));
		}
		else {
			$res .= '<p style="display:none">'.__('No revision').'</p>';
		}
		
		return $res;
	}
	
	private function getLines($url)
	{
		$res = '';
		$p_img = '<img src="%1$s" alt="%2$s" title="%2$s" />';
		$p_link = '<a href="%1$s" title="%3$s" class="patch"><img src="%2$s" alt="%3$s" /></a>';
		
		while ($this->rs->fetch()) {
			$res .= 
				'<tr class="line wide'.(!$this->rs->canPatch() ? ' offline' : '').'" id="r'.$this->rs->revision_id.'">'."\n".
				'<td class="maximal nowrap rid">'.
					'<strong>'.sprintf(__('Revision #%s'),$this->rs->revision_id).'</strong>'.
				"</td>\n".
				'<td class="minimal nowrap">'.
					$this->rs->getAuthorLink().
				"</td>\n".
				'<td class="minimal nowrap">'.
					$this->rs->getDate().' - '.$this->rs->getTime().
				"</td>\n".
				'<td class="minimal nowrap status">'.
					sprintf(
						$p_img,
						('images/'.($this->rs->canPatch() ? 'check-on.png' : 'locker.png')),
						($this->rs->canPatch() ? __('Revision allowed') : __('Revision blocked'))
					).
				"</td>\n".
				'<td class="minimal nowrap status">'.
					($this->rs->canPatch() ? sprintf($p_link,sprintf($url,$this->rs->revision_id),'index.php?pf=dcRevisions/images/apply.png',__('Apply patch')) : '').
				"</td>\n".
				"</tr>\n";
		}
		
		return $res;
	}
}

?>