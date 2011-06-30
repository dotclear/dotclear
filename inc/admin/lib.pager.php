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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear Pager class.

Dotclear Pager handles pagination for every admin list.
*/
class dcPager extends pager
{
	public function getLinks()
	{
		$htmlText = '';
		$htmlStart = '';
		$htmlEnd = '';
		$htmlPrev = '';
		$htmlNext = '';
		$htmlDirectAccess = '';
		$htmlHidden = '';
		
		$this->setURL();
		
		# Page text
		$htmlText = '<span>'.sprintf(__('Page %s over %s'),$this->env,$this->nb_pages).'</span>';
		
		# Previous page
		if($this->env != 1) {
			$htmlPrev = '<a href="'.sprintf($this->page_url,$this->env-1).'" class="prev">'.
			$htmlPrev .= $this->html_prev.'</a>';
		}
		
		# Next page
		if($this->env != $this->nb_pages) {
			$htmlNext = '<a href="'.sprintf($this->page_url,$this->env+1).'" class="next">';
			$htmlNext .= $this->html_next.'</a>';
		}
		
		# Start
		if($this->env != 1) {
			$htmlStart = '<a href="'.sprintf($this->page_url,1).'" class="start">'.
			$htmlStart .= $this->html_start.'</a>';
		}
		
		# End
		if($this->env != $this->nb_pages) {
			$htmlEnd = '<a href="'.sprintf($this->page_url,$this->nb_elements).'" class="end">'.
			$htmlEnd .= $this->html_end.'</a>';
		}
		
		# Direct acces
		$htmlDirectAccess = 
			'<span>'.__('Direct access to page').'&nbsp;'.
			form::field(array('page'),3,3,$this->env).'&nbsp;'.
			'<input type="submit" value="'.__('ok').'" />'.
			'</span>';
			
		# Hidden fields
		foreach ($_GET as $k => $v) {
			if ($k != $this->var_page) {
				$htmlHidden .= form::hidden(array($k),$v);
			}
		}
		
		$res =
			'<form method="get" action="'.$this->base_url.'"><p>'.
			$htmlStart.
			$htmlPrev.
			$htmlText.
			$htmlNext.
			$htmlEnd.
			$htmlDirectAccess.
			$htmlHidden.
			'</p></form>';
		
		return $this->nb_elements > 0 ? $res : '';
	}
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear Generic column class.

Dotclear Generic column handles each column object use in adminGenericList class.
*/
class adminGenericColumn
{
	protected $core;		/// <b>object</b> dcCore object
	protected $id;			/// <b>string</b> ID of defined column
	protected $title;		/// <b>string</b> Title of defined column
	protected $callback;	/// <b>array</b> Callback calls to display defined column
	protected $html;		/// <b>string</b> Extra HTML for defined column
	protected $visibility;	/// <b>boolean</b> Visibility of defined column
	
	/**
	Inits Generic column object
	
	@param	id		<b>string</b>		Column id
	@param	title	<b>string</b>		Column title (for table headers)
	@param	callback	<b>array</b>		Column callback (used for display)
	@param	html		<b>string</b>		Extra html (used for table headers)
	@param	can_hide	<b>boolean</b>		Defines if the column can be hidden or not
	*/
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
			$find_parent = false;
			
			while (!$p) {
				if ($p->name == 'adminGenericList') {
					$find_parent = true;
				}
				else {
					$p->getParentClass();
				}
			}
			
			if (!$p || !$f) {
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
	
	/**
	Gets information of defined column
	
	@param	info		<b>string</b>		Column info to retrive
	
	@return	<b>mixed</b>	The information requested, null otherwise
	*/
	public function getInfo($info)
	{
		return property_exists(get_class($this),$info) ? $this->{$info} : null;
	}
	
	/**
	Sets visibility of defined column
	
	@param	visibility	<b>boolean</b>		Column visibility
	*/
	public function setVisibility($visibility)
	{
		if (is_bool($visibility)) {
			$this->visibility = $visibility;
		}
	}
	
	/**
	Returns visibility status of defined column
	
	@return	<b>boolean</b>		true if column is visible, false otherwise
	*/
	public function isVisible()
	{
		return $this->visibility;
	}
	
	/**
	Returns if the defined column can be hidden
	
	@return	<b>boolean</b>	true if column can be hidden, false otherwise
	*/
	public function canHide()
	{
		return $this->can_hide;
	}
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract Generic list class.

Dotclear Generic list handles administration lists
*/
abstract class adminGenericList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	protected $columns;
	
	/*
	Sets columns of defined list
	*/
	abstract function setColumns();
	
	/**
	Inits List object
	
	@param	core		<b>dcCore</b>		dcCore object
	@param	rs		<b>recordSet</b>	Items recordSet to display
	@param	rs_count	<b>int</b>		Total items number
	*/
	public function __construct($core,$rs,$rs_count)
	{
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->context = get_class($this);
		$this->columns = array();
		$this->form_prefix = 'col_%s';
		$this->form_trigger = 'add_filter';
		
		$this->html_prev = __('prev');
		$this->html_next = __('next');
		$this->html_start = __('start');
		$this->html_end = __('end');
		
		$this->setColumns();
		
		$core->callBehavior('adminListConstruct',$this);
		
		$this->setColumnsVisibility();
	}
	
	/**
	Returns HTML code form used to choose which column to display
	
	@return	<b>string</b>		HTML code form
	*/
	public function getColumnsForm()
	{
		$block = 
		'<h3>'.__('Displayed information').'</h3>'.
		'<ul>%s</ul>';
		
		$list = array();
		
		foreach ($this->columns as $k => $v) {
			$col_id = sprintf($this->form_prefix,$k);
			$col_label = sprintf('<label for="%s">%s</label>',$col_id,$v->getInfo('title'));
			$col_html = sprintf('<li class="line">%s</li>',$col_label.form::checkbox($col_id,1,$v->isVisible(),null,null,!$v->canHide()));
			
			array_push($list,$col_html);
		}
		
		$nb_per_page = isset($_GET['nb']) ? $_GET['nb'] : 10;
		
		return
		sprintf($block,implode('',$list)).
		'<p><label for="nb">'.__('Items per page:').
		'</label>&nbsp;'.form::field('nb',3,3,$nb_per_page).
		'</p>';
	}
	
	/**
	Returns HTML code list to display
	
	@param	page			<b>string|int</b>	Current page
	@param	nb_per_page	<b>string|int</b>	Number of items to display in each page
	@param	enclose_block	<b>string</b>		HTML wrapper of defined list
	
	@return	<b>string</b>		HTML code list
	*/
	public function display($page,$nb_per_page,$enclose_block = '')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No entry').'</strong></p>';
		}
		else
		{
			$pager = new dcPager($page,$this->rs_count,$nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->html_start = $this->html_start;
			$pager->html_end = $this->html_end;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="maximal clear">'.
			$this->getCaption($page).
			'<thead><tr>';
			
			foreach ($this->columns as $k => $v) {
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
			
			echo '<div class="pagination">'.$pager->getLinks().'</div>';
			
			$blocks = explode('%s',$html_block);
			
			echo $blocks[0];
			
			while ($this->rs->fetch())
			{
				echo $this->displayLine();
			}
			
			echo $blocks[1];
			
			echo '<div class="pagination">'.$pager->getLinks().'</div>';
		}
	}
	
	/**
	Adds column to defined list
	
	@param	id		<b>string</b>		Column id
	@param	title	<b>string</b>		Column title (for table headers)
	@param	callback	<b>array</b>		Column callback (used for display)
	@param	html		<b>string</b>		Extra html (used for table headers)
	@param	can_hide	<b>boolean</b>		Defines if the column can be hidden or not
	*/
	protected function addColumn($id,$title,$callback,$html = null,$can_hide = true)
	{
		try {
			$c = new adminGenericColumn($id,$title,$callback,$html,$can_hide);
			$this->columns[$id] = $c;
		}
		catch (Exception $e) {
			if (DC_DEBUG) {
				$this->core->error->add($e->getMessage());
			}
		}
	}
	
	/**
	Returns default caption text
	
	@return	<b>string</b>		Default caption
	*/
	protected function getDefaultCaption()
	{
		return;
	}
	
	/**
	Returns default HTMl code line
	
	@return	<b>string</b>		Default HTMl code line
	*/
	protected function getDefaultLine()
	{
		return '<tr class="line">%s</tr>';
	}
	
	/**
	Sets columns visibility of defined list
	*/
	private function setColumnsVisibility()
	{
		$ws = $this->core->auth->user_prefs->addWorkspace('lists');
		
		$user_pref = !is_null($ws->{$this->context}) ? unserialize($ws->{$this->context}) : array();
		
		foreach ($this->columns as $k => $v) {
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
	
	/**
	Returns HTML code for each line of defined list
	
	@return	<b>string</b>		HTML code line
	*/
	private function displayLine()
	{
		$res = '';
		
		foreach ($this->columns as $k => $v) {
			if ($v->isVisible()) {
				$c = $v->getInfo('callback');
				$res .= $this->{$c[1]}();
			}
		}
		
		return sprintf($this->getDefaultLine(),$res);
	}
	
	/**
	Returns caption of defined list
	
	@param	page			<b>string|int</b>	Current page
	
	@return	<b>string</b>		HTML caption tag
	*/
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
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract posts list class.

Handle posts list on admin side
*/
class adminPostList extends adminGenericList
{
	public function setColumns()
	{
		$this->addColumn('title',__('Title'),array('adminPostList','getTitle'),' class="maximal"',false);
		$this->addColumn('date',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('datetime',__('Date and time'),array('adminPostList','getDateTime'));
		$this->addColumn('category',__('Category'),array('adminPostList','getCategory'));
		$this->addColumn('author',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('comment',__('Comments'),array('adminPostList','getComments'));
		$this->addColumn('trackback',__('Trackbacks'),array('adminPostList','getTrackbacks'));
		$this->addColumn('status',__('Status'),array('adminPostList','getStatus'));
	}
	
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

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract mini posts list class.

Handle mini posts list on admin side (used for link popup)
*/
class adminPostMiniList extends adminPostList
{
	public function setColumns()
	{
		$this->addColumn('title',__('Title'),array('adminPostMiniList','getTitle'),' class="maximal"',false);
		$this->addColumn('date',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('author',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('status',__('Status'),array('adminPostList','getStatus'));
	}
	
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

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract comments list class.

Handle comments list on admin side
*/
class adminCommentList extends adminGenericList
{
	public function setColumns()
	{
		$this->addColumn('title',__('Title'),array('adminCommentList','getTitle'),' class="maximal"',false);
		$this->addColumn('date',__('Date'),array('adminCommentList','getDate'));
		$this->addColumn('author',__('Author'),array('adminCommentList','getAuthor'));
		$this->addColumn('type',__('Type'),array('adminCommentList','getType'));
		$this->addColumn('status',__('Status'),array('adminCommentList','getStatus'));
		$this->addColumn('edit','',array('adminCommentList','getEdit'));
	}
	
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

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract users list class.

Handle users list on admin side
*/
class adminUserList extends adminGenericList
{
	public function setColumns()
	{
		$this->addColumn('username',__('Username'),array('adminUserList','getUserName'),'class="maximal"',false);
		$this->addColumn('firstname',__('First name'),array('adminUserList','getFirstName'),'class="nowrap"');
		$this->addColumn('lastname',__('Last name'),array('adminUserList','getLastName'),'class="nowrap"');
		$this->addColumn('displayname',__('Display name'),array('adminUserList','getDisplayName'),'class="nowrap"');
		$this->addColumn('entries',__('Entries'),array('adminUserList','getEntries'),'class="nowrap"');
	}
	
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

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract blogs list class.

Handle blogs list on admin side
*/
class adminBlogList extends adminGenericList
{
	public function setColumns()
	{
		$this->addColumn('blogname',__('Blog name'),array('adminBlogList','getBlogName'),'class="maximal"',false);
		$this->addColumn('lastupdate',__('Last update'),array('adminBlogList','getLastUpdate'),'class="nowrap"');
		$this->addColumn('entries',__('Entries'),array('adminBlogList','getEntries'),'class="nowrap"');
		$this->addColumn('blogid',__('Blog ID'),array('adminBlogList','getBlogId'),'class="nowrap"');
		$this->addColumn('action','',array('adminBlogList','getAction'),'class="nowrap"');
		$this->addColumn('status',__('status'),array('adminBlogList','getStatus'),'class="nowrap"');
	}
	
	protected function getDefaultCaption()
	{
		return __('Blogs list');
	}
	
	protected function getBlogName()
	{
		return
		'<th scope="row" class="maximal"><a href="index.php?switchblog='.$this->rs->blog_id.'" '.
		'title="'.sprintf(__('Switch to blog %s'),$this->rs->blog_id).'">'.
		html::escapeHTML($this->rs->blog_name).'</a></th>';
	}
	
	protected function getLastUpdate()
	{
		$offset = dt::getTimeOffset($this->core->auth->getInfo('user_tz'));
		$blog_upddt = dt::str(__('%Y-%m-%d %H:%M'),strtotime($this->rs->blog_upddt) + $offset);
	
		return '<td class="nowrap">'.$blog_upddt.'</td>';
	}
	
	protected function getEntries()
	{
		return '<td class="nowrap">'.$this->core->countBlogPosts($this->rs->blog_id).'</td>';
	}
	
	protected function getBlogId()
	{
		return '<td class="nowrap">'.html::escapeHTML($this->rs->blog_id).'</td>';
	}
	
	protected function getAction()
	{
		$edit_link = '';
		$blog_id = html::escapeHTML($this->rs->blog_id);
	
		if ($GLOBALS['core']->auth->isSuperAdmin()) {
			$edit_link = 
			'<a href="blog.php?id='.$blog_id.'" '.
			'title="'.sprintf(__('Edit blog %s'),$blog_id).'">'.
			__('edit').'</a>';
		}
		
		return '<td class="nowrap">'.$edit_link.'</td>';
	}
	
	protected function getStatus()
	{
		$img_status = $this->rs->blog_status == 1 ? 'check-on' : 'check-off';
		$txt_status = $GLOBALS['core']->getBlogStatus($this->rs->blog_status);
		$img_status = sprintf('<img src="images/%1$s.png" alt="%2$s" title="%2$s" />',$img_status,$txt_status);
		
		return '<td class="status">'.$img_status.'</td>';
	}
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract blogs permissions list class.

Handle blogs permissions list on admin side
*/
class adminBlogPermissionsList extends adminBlogList
{
	public function setColumns()
	{
		$this->addColumn('blogid',__('Blog ID'),array('adminBlogPermissionsList','getBlogId'),'class="nowrap"',false);
		$this->addColumn('blogname',__('Blog name'),array('adminBlogPermissionsList','getBlogName'),'class="maximal"');
		$this->addColumn('entries',__('Entries'),array('adminBlogList','getEntries'),'class="nowrap"');
		$this->addColumn('status',__('status'),array('adminBlogList','getStatus'),'class="nowrap"');
	}
	
	protected function getBlogId()
	{
		return
		'<th scope="row" class="nowrap">'.
		form::checkbox(array('blog_id[]'),$this->rs->blog_id,'','','',false,'title="'.__('select').' '.$this->rs->blog_id.'"').
		$this->rs->blog_id.'</th>';
	}
	
	protected function getBlogName()
	{
		return '<td class="maximal">'.html::escapeHTML($this->rs->blog_name).'</td>';
	}
}

?>