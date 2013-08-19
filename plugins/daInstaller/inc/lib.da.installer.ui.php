<?php
# ***** BEGIN LICENSE BLOCK *****
# This file is part of daInstaller, a plugin for DotClear2.
# Copyright (c) 2008-2011 Tomtom, Pep and contributors, for DotAddict.org.
# All rights reserved.
#
# ***** END LICENSE BLOCK *****

/**
 * Class daModulesList
 */
class daModulesList extends adminGenericList
{
	/**
	 * Display data table for plugins and themes lists
	 *
	 * @param	int		page
	 * @param	int		nb_per_page
	 * @param	string	type
	 * @param	string	url
	 */
	public function display($page,$nb_per_page,$type,$url)
	{
		$search = false;
		if (strpos($type,'search-') === 0) {
			$search = true;
			$type = str_replace('search-','',$type);
		}
		$type = ($type == 'themes') ? 'themes' : 'plugins';
		if ($type == 'themes') {
			$msg_no_entry = __('No theme');
			$msg_th_label = __('Themes');
			$lineMethod   = 'themeLine';
		}
		else {
			$msg_no_entry = __('No extension');
			$msg_th_label = __('Plugins');
			$lineMethod   = 'pluginLine';
		}
		
		if ($this->rs->isEmpty()) {
			echo '<p><strong>'.$msg_no_entry.'</strong></p>';
		}
		else {
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			if (!$search) {
				$pager->base_url = $url.'&amp;tab='.$type.'&amp;page=%s';
			}
			else {
				$pager->var_pager = 'page';
			}
			
			$html_block =
				'<table summary="modules" class="maximal">'.
				'<thead>'.
				'<tr>'.
				'<th>'.$msg_th_label.'</th>'.
				'<th class="nowrap">'.__('Lastest Version').'</th>'.
				'<th>'.__('Quick description').'</th>'.
				'<th>'.__('Actions').'</th>'.
				'</tr>'.
				'</thead>'.
				'<tbody>%s</tbody>'.
				'</table>';
			
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			$blocks = explode('%s',$html_block);
			echo $blocks[0];
			
			$this->rs->index(((integer)$page - 1) * $nb_per_page);
			$iter = 0;
			while ($iter < $nb_per_page) {
				echo $this->{$lineMethod}($url);
				if ($this->rs->isEnd()) {
					break;
				}
				else {
					$this->rs->moveNext();
					$iter++;
				}
			}
			echo $blocks[1];
			echo '<p class="pagination">'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	/**
	 * Return a generic plugin row
	 *
	 * @param	string	url
	 *
	 * @return	string
	 */
	private function pluginLine($url)
	{
		return
			'<tr class="line wide" id="ext_'.$this->rs->id.'">'."\n".
			# Extension
			'<td class="minimal nowrap">'.
				'<strong>'.html::escapeHTML($this->rs->id).'</strong>'.
			"</td>\n".
			# Version
			'<td class="minimal nowrap">'.
				html::escapeHTML($this->rs->version).
			"</td>\n".
			# Quick description
			'<td>'.
				'<p><strong>'.html::escapeHTML($this->rs->label).'</strong><br />'.
				'<em>'.html::escapeHTML($this->rs->desc).'</em></p>'.
				__('by').' '.html::escapeHTML($this->rs->author).'<br />'.
				'( <a href="'.$this->rs->details.'" class="learnmore modal">'.__('More details').'</a> )'.
			"</td>\n".
			# Action
			'<td class="minimal">'.
				'<form action="'.$url.'" method="post">'.
				'<p><input name="package_url" value="'.$this->rs->file.'" type="hidden" />'.
				$this->core->formNonce().
				'<input class="install" name="add_plugin" value="'.
				__('Install').'" type="submit" /></p>'.
				'</form>'.
			"</td>\n".
			'</tr>'."\n";
	}
	
	/**
	 * Return a generic theme row
	 *
	 * @param	string	url
	 *
	 * @return	string
	 */
	private function themeLine($url)
	{
		return
			'<tr class="line wide" id="ext_'.$this->rs->id.'">'."\n".
			# Extension
			'<td class="minimal nowrap">'.
				'<strong>'.html::escapeHTML($this->rs->id).'</strong>'.
				'<p class="sshot"><img src="'.html::escapeHTML($this->rs->sshot).'" alt="" /></p>'.
			"</td>\n".
			# Version
			'<td class="minimal nowrap">'.
				html::escapeHTML($this->rs->version).
			"</td>\n".
			# Quick description
			'<td>'.
				'<p><strong>'.html::escapeHTML($this->rs->label).'</strong><br />'.
				'<em>'.html::escapeHTML($this->rs->desc).'</em></p>'.
				__('by').' '.html::escapeHTML($this->rs->author).'<br />'.
				'( <a href="'.$this->rs->details.'" class="learnmore modal">'.__('More details').'</a> )'.
			"</td>\n".
			# Action
			'<td class="minimal">'.
				'<form action="'.$url.'" method="post">'.
				'<p><input name="package_url" value="'.$this->rs->file.'" type="hidden" />'.
				$this->core->formNonce().
				'<input class="install" name="add_theme" value="'.
				__('Install').'" type="submit" /></p>'.
				'</form>'.
			"</td>\n".
			'</tr>'."\n";
	} 
}

/**
 * Class daModulesUpdateList
 */
class daModulesUpdateList
{
	protected $rs;
	protected $nb;
	
	/**
	 * Class constructor
	 */
	public function __construct($core,$rs,$nb)
	{
		$this->core	= $core;
		$this->rs		=  $rs;
		$this->nb		=  $nb;
		$this->p_link	= '<a href="%s" class="%s">%s</a>';
	}
	
	/**
	 * Display data table for plugins and themes update lists
	 *
	 * @param	string	type
	 * @param	string	url
	 *
	 * @return	string
	 */
	public function display($type,$url)
	{
		$type = ($type == 'themes') ? 'themes' : 'plugins';
		if ($type == 'themes') {
			$msg_th_label = __('Theme');
			$lineMethod   = 'themeLine';
		}
		else {
			$msg_th_label = __('Plugins');
			$lineMethod   = 'pluginLine';
		}
		
		$iter = 0;
		$items = '';
		$html_block =
			'<form action="'.$url.'" method="post">'.
			'<table summary="upd-%1$s" class="maximal">'.
			'<thead>'.
			'<tr>'.
			'<th>'.$msg_th_label.'</th>'.
			'<th class="nowrap">'.__('Lastest Version').'</th>'.
			'<th>'.__('Quick description').'</th>'.
			'</tr>'.
			'</thead>'.
			'<tbody>%2$s</tbody>'.
			'</table>'.
			'<div class="two-cols">'.
			'<p class="col checkboxes-helpers"></p>'.
			'<p class="col right">'.
			$this->core->formNonce().
			'<input type="submit" value="'.__('Update selected modules').'" name="upd_'.$type.'" /></p>'.
			'</div>'.
			'</form>';
			
		while ($iter < $this->rs->count()) {
			$items .= $this->{$lineMethod}($url);
			$this->rs->moveNext();
			$iter++;
		}
		
		echo $this->nb > 0 ? sprintf($html_block,$type,$items) : '';
		
	}

	/**
	 * Return a update plugin row
	 *
	 * @param	string	url
	 *
	 * @return	string
	 */
	private function pluginLine($url)
	{
		$support =
		strlen($this->rs->support) > 0 ?
		sprintf($this->p_link,$this->rs->support,'support modal',__('Support')) :
		'<span class="support">'.__('No support available').'</span>';
		
		return
			'<tr class="line wide" id="ext_'.$this->rs->id.'">'."\n".
			# Extension
			'<td class="minimal nowrap">'.
				form::checkbox(array('plugins_id[]'),$this->rs->id).
				'<strong>'.html::escapeHTML($this->rs->id).'</strong>'.
			"</td>\n".
			# Version
			'<td class="minimal nowrap">'.
				html::escapeHTML($this->rs->version).
			"</td>\n".
			# Quick description
			'<td>'.
				'<p><strong>'.html::escapeHTML($this->rs->label).'</strong><br />'.
				'<em>'.html::escapeHTML($this->rs->desc).'</em></p>'.
				__('by').' '.html::escapeHTML($this->rs->author).'<br />'.
				'( <a href="'.$this->rs->details.'" class="learnmore modal">'.
				__('More details').'</a> - '.$support.'</a> )'.
			"</td>\n".
			'</tr>'."\n";
	}
	
	/**
	 * Return a update theme row
	 *
	 * @param	string	url
	 *
	 * @return	string
	 */
	private function themeLine($url)
	{
		$support =
		strlen($this->rs->support) > 0 ?
		sprintf($this->p_link,$this->rs->support,'support modal',__('Support')) :
		'<span class="support">'.__('No support available').'</span>';
		
		return
			'<tr class="line wide" id="ext_'.$this->rs->id.'">'."\n".
			# Themes
			'<td class="minimal nowrap">'.
				form::checkbox(array('themes_id[]'),$this->rs->id).
				'<strong>'.html::escapeHTML($this->rs->id).'</strong>'.
				'<p class="sshot"><img src="'.html::escapeHTML($this->rs->sshot).'" alt="" /></p>'.
			"</td>\n".
			# Version
			'<td class="minimal nowrap">'.
				html::escapeHTML($this->rs->version).
			"</td>\n".
			# Quick description
			'<td>'.
				'<p><strong>'.html::escapeHTML($this->rs->label).'</strong><br />'.
				'<em>'.html::escapeHTML($this->rs->desc).'</em></p>'.
				__('by').' '.html::escapeHTML($this->rs->author).'<br />'.
				'( <a href="'.$this->rs->details.'" class="learnmore modal">'.
				__('More details').'</a> - '.$support.'</a> )'.
			"</td>\n".
			'</tr>'."\n";
	}
}

?>