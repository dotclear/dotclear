<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
if (!defined('DC_RC_PATH')) { return; }

class adminGenericColumn
{
	protected $core;
	protected $rs;
	protected $id;
	protected $title;
	protected $callback;
	protected $html;
	protected $visibility;
	
	public function __construct($id,$title,$callback,$html = null,$can_hide = true)
	{
		if (!is_string($id) || $id === '') {
			throw new Exception(__('Invalid column ID'));
		}
		
		if (!is_string($title)) {
			throw new Exception(__('Invalid column title'));
		}
		
		if (is_null($html) || !is_string($html)) {
			$html = '';
		}
		if (!empty($html)) {
			$html = ' '.$html;
		}
		
		if (!is_bool($can_hide)) {
			$can_hide = true;
		}
		
		try {
			if (!is_array($callback) || count($callback) < 2) {
				throw new Exception(__('Callback parameter should be an array'));
			}
			$r = new ReflectionClass($callback[0]);
			$f = $r->getMethod($callback[1]);
			$p = $r->getParentClass();
			if (!$p || $p->name != 'adminGenericList') {
				throw new Exception(__('Callback class should be inherited of adminGenericList class'));
			}
		}
		catch (Exception $e) {
			throw new Exception(sprintf(__('Invalid column callback: %s'),$e->getMessage()));
		}
		
		$this->id = $id;
		$this->title = $title;
		$this->callback = $callback;
		$this->html = $html;
		$this->can_hide = $can_hide;
		$this->visibility = true;
	}
	
	public function getInfo($k)
	{
		return property_exists(get_class($this),$k) ? $this->{$k} : null;
	}
	
	public function setVisibility($visibility)
	{
		if (is_bool($visibility)) {
			$this->visibility = $visibility;
		}
	}
	
	public function isVisible()
	{
		return $this->visibility;
	}
	
	public function canHide()
	{
		return $this->can_hide;
	}
}

class adminGenericList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	protected $columns;
	
	public function __construct($core,$rs,$rs_count)
	{
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->context = get_class($this);
		$this->columns = array();
		$this->form_prefix = 'col_%s';
		$this->form_trigger = 'add_filter';
		
		$this->html_prev = __('&#171;prev.');
		$this->html_next = __('next&#187;');
		
		# Post columns
		$this->addColumn('adminPostList','title',__('Title'),array('adminPostList','getTitle'),' class="maximal"',false);
		$this->addColumn('adminPostList','date',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('adminPostList','datetime',__('Date and time'),array('adminPostList','getDateTime'));
		$this->addColumn('adminPostList','category',__('Category'),array('adminPostList','getCategory'));
		$this->addColumn('adminPostList','author',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('adminPostList','comment',__('Comments'),array('adminPostList','getComments'));
		$this->addColumn('adminPostList','trackback',__('Trackbacks'),array('adminPostList','getTrackbacks'));
		$this->addColumn('adminPostList','status',__('Status'),array('adminPostList','getStatus'));
		
		# Post (mini list) columns
		$this->addColumn('adminPostMiniList','title',__('Title'),array('adminPostList','getTitle'),' class="maximal"',false);
		$this->addColumn('adminPostMiniList','date',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('adminPostMiniList','author',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('adminPostMiniList','status',__('Status'),array('adminPostList','getStatus'));
		
		# Comment columns
		$this->addColumn('adminCommentList','title',__('Title'),array('adminCommentList','getTitle'),' class="maximal"',false);
		$this->addColumn('adminCommentList','date',__('Date'),array('adminCommentList','getDate'));
		$this->addColumn('adminCommentList','author',__('Author'),array('adminCommentList','getAuthor'));
		$this->addColumn('adminCommentList','type',__('Type'),array('adminCommentList','getType'));
		$this->addColumn('adminCommentList','status',__('Status'),array('adminCommentList','getStatus'));
		$this->addColumn('adminCommentList','edit','',array('adminCommentList','getEdit'));
		
		# User columns
		$this->addColumn('adminUserList','username',__('Username'),array('adminUserList','getUserName'),' class="maximal"',false);
		$this->addColumn('adminUserList','firstname',__('First name'),array('adminUserList','getFirstName'));
		$this->addColumn('adminUserList','lastname',__('Last name'),array('adminUserList','getLastName'));
		$this->addColumn('adminUserList','displayname',__('Display name'),array('adminUserList','getDisplayName'));
		$this->addColumn('adminUserList','entries',__('Entries'),array('adminUserList','getEntries'));
		
		$core->callBehavior('adminGenericListConstruct',$this);
		
		$this->setColumnsVisibility();
	}
	
	public function addColumn($context,$id,$title,$callback,$html = null,$can_hide = true)
	{
		try {
			if (!array_key_exists($context,$this->columns)) {
				$this->columns[$context] = array();
			}
			
			$c = new adminGenericColumn($id,$title,$callback,$html,$can_hide);
			$this->columns[$context][$c->getInfo('id')] = $c;
		}
		catch (Exception $e) {
			if (DC_DEBUG) {
				$this->core->error->add($e->getMessage());
			}
		}
	}
	
	public function setColumnsVisibility()
	{
		$ws = $this->core->auth->user_prefs->addWorkspace('lists');
		
		$user_pref = !is_null($ws->{$this->context}) ? unserialize($ws->{$this->context}) : array();
		
		foreach ($this->columns[$this->context] as $k => $v) {
			$visibility =  array_key_exists($k,$user_pref) ? $user_pref[$k] : true;
			if (array_key_exists($this->form_trigger,$_REQUEST)) {
				$key = sprintf($this->form_prefix,$k);
				$visibility = !array_key_exists($key,$_REQUEST) ? false : true;
			}
			if (!$v->canHide()) {
				$visibility = true;
			}
			$v->setVisibility($visibility);
			$user_pref[$k] = $visibility;
		}
		
		if (array_key_exists($this->form_trigger,$_REQUEST)) {
			$this->core->auth->user_prefs->lists->put($this->context,serialize($user_pref),'string');
		}
	}
	
	public function getColumnsForm()
	{
		$block = 
		'<h3>'.__('Displayed information').'</h3>'.
		'<ul>%s</ul>';
		
		$list = array();
		
		foreach ($this->columns[$this->context] as $k => $v) {
			$col_id = sprintf($this->form_prefix,$k);
			$col_label = sprintf('<label for="%s">%s</label>',$col_id,$v->getInfo('title'));
			$col_html = sprintf('<li class="line">%s</li>',$col_label.form::checkbox($col_id,1,$v->isVisible(),null,null,!$v->canHide()));
			
			array_push($list,$col_html);
		}
		
		$nb_per_page = isset($_GET['nb']) ? $_GET['nb'] : 10;
		
		array_push($list,'<label for="nb" class="classic">'.__('Items per page:').'</label>&nbsp;'.form::field('nb',3,3,$nb_per_page));
		
		return sprintf($block,implode('',$list));
	}
	
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
			'<table class="maximal clear">'.
			$this->getCaption($page).
			'<thead><tr>';
			
			foreach ($this->columns[$this->context] as $k => $v) {
				if ($v->isVisible()) {
					$html_extra = $v->getInfo('html') != '' ? ' '.$v->getInfo('html') : '';
					$html_block .= sprintf('<th scope="col"%s>%s</th>',$html_extra,$v->getInfo('title'));
				}
			}
			
			$html_block .=
			'</tr></thead>'.
			'<tbody>%s</tbody>'.
			'</table>';
			
			if ($enclose_block) {
				$html_block = sprintf($enclose_block,$html_block);
			}
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->displayLine();
			}
			
			echo $blocks[1];
			
			echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		}
	}
	
	private function displayLine()
	{
		$res = '';
		
		foreach ($this->columns[$this->context] as $k => $v) {
			if ($v->isVisible()) {
				$c = $v->getInfo('callback');
				$func = $c[1];
				$res .= $this->{$c[1]}();
			}
		}
		
		return sprintf($this->getDefaultLine(),$res);
	}
	
	private function getCaption($page)
	{
		$caption = $this->getDefaultCaption();
		
		if (!empty($caption)) {
			$caption = sprintf(
				'<caption>%s - %s</caption>',
				$caption,sprintf(__('Page %s'),$page)
			);
		}
		
		return $caption;
	}
	
	protected function getDefaultCaption()
	{
		return;
	}
	
	protected function getDefaultLine()
	{
		return '<tr class="line">%s</tr>';
	}
}

class adminPostList extends adminGenericList
{
	protected function getDefaultCaption()
	{
		return __('Entries list');
	}
	
	protected function getDefaultLine()
	{
		return
		'<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">%s</tr>';
	}
	
	protected function getTitle()
	{
		return
		'<th scope="row" class="maximal">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()).'&nbsp;'.
		'<a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.
		html::escapeHTML($this->rs->post_title).'</a></th>';
	}
	
	protected function getDate()
	{
		return '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d'),$this->rs->post_dt).'</td>';
	}
	
	protected function getDateTime()
	{
		return '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt).'</td>';
	}
	
	protected function getCategory()
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
			$cat_title = __('None');
		}
		
		return '<td class="nowrap">'.$cat_title.'</td>';
	}
	
	protected function getAuthor()
	{
		return '<td class="nowrap">'.$this->rs->user_id.'</td>';
	}
	
	protected function getComments()
	{
		return '<td class="nowrap">'.$this->rs->nb_comment.'</td>';
	}
	
	protected function getTrackbacks()
	{
		return '<td class="nowrap">'.$this->rs->nb_trackback.'</td>';
	}
	
	protected function getStatus()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
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
		
		$protected = '';
		if ($this->rs->post_password) {
			$protected = sprintf($img,__('protected'),'locker.png');
		}
		
		$selected = '';
		if ($this->rs->post_selected) {
			$selected = sprintf($img,__('selected'),'selected.png');
		}
		
		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		return '<td class="nowrap status">'.$img_status.' '.$selected.' '.$protected.' '.$attach.'</td>';
	}
}

class adminPostMiniList extends adminPostList
{
	protected function getTitle() 
	{
		return
		'<th scope="row" class="maximal">'.
		form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()).'&nbsp;'.
		'<a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'" '.
		'title="'.html::escapeHTML($this->rs->getURL()).'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>';
	}
}

class adminCommentList extends adminGenericList
{
	protected function getDefaultCaption()
	{
		return __('Comments list');
	}
	
	protected function getDefaultLine()
	{
		return
		'<tr class="line'.($this->rs->comment_status != 1 ? ' offline' : '').'"'.
		' id="c'.$this->rs->comment_id.'">%s</tr>';
	}
	
	protected function getTitle()
	{
		$post_url = $this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id);
		
		return
		'<th scope="row" class="maximal">'.
		form::checkbox(array('comments[]'),$this->rs->comment_id,'','','',0).'&nbsp;'.
		'<a href="'.$post_url.'">'.
		html::escapeHTML($this->rs->post_title).'</a>'.
		($this->rs->post_type != 'post' ? ' ('.html::escapeHTML($this->rs->post_type).')' : '').'</th>';
	}
	
	protected function getDate()
	{
		return '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->comment_dt).'</td>';
	}
	
	protected function getAuthor()
	{
		global $author, $status, $sortby, $order, $nb_per_page;
		
		$author_url =
		'comments.php?n='.$nb_per_page.
		'&amp;status='.$status.
		'&amp;sortby='.$sortby.
		'&amp;order='.$order.
		'&amp;author='.rawurlencode($this->rs->comment_author);
		
		$comment_author = html::escapeHTML($this->rs->comment_author);
		if (mb_strlen($comment_author) > 20) {
			$comment_author = mb_strcut($comment_author,0,17).'...';
		}
		
		return '<td class="nowrap"><a href="'.$author_url.'">'.$comment_author.'</a></td>';
	}
	
	protected function getType()
	{
		return '<td class="nowrap">'.($this->rs->comment_trackback ? __('trackback') : __('comment')).'</td>';
	}
	
	protected function getStatus()
	{
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->comment_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
			case -2:
				$img_status = sprintf($img,__('junk'),'junk.png');
				break;
		}
		
		return '<td class="nowrap status">'.$img_status.'</td>';
	}
	
	protected function getEdit()
	{
		$comment_url = 'comment.php?id='.$this->rs->comment_id;
		
		return
		'<td class="nowrap status"><a href="'.$comment_url.'">'.
		'<img src="images/edit-mini.png" alt="" title="'.__('Edit this comment').'" /></a></td>';
	}
}

class adminUserList extends adminGenericList
{
	protected function getDefaultCaption()
	{
		return __('Users list');
	}
	
	protected function getUserName()
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
		'<th scope="row" class="maximal">'.form::hidden(array('nb_post[]'),(integer) $this->rs->nb_post).
		form::checkbox(array('user_id[]'),$this->rs->user_id).'&nbsp;'.
		'<a href="user.php?id='.$this->rs->user_id.'">'.
		$this->rs->user_id.'</a>&nbsp;'.$img_status.'</th>';
	}
	
	protected function getFirstName()
	{
		return '<td class="nowrap">'.$this->rs->user_firstname.'</td>';
	}
	
	protected function getLastName()
	{
		return '<td class="nowrap">'.$this->rs->user_name.'</td>';
	}
	
	protected function getDisplayName()
	{
		return '<td class="nowrap">'.$this->rs->user_displayname.'</td>';
	}
	
	protected function getEntries()
	{
		return
		'<td class="nowrap"><a href="posts.php?user_id='.$this->rs->user_id.'">'.
		$this->rs->nb_post.'</a></td>';
	}
}

?>