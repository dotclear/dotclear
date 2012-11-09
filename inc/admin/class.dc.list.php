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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear items pager class.

Dotclear items pager handles pagination for every admin list.
*/
class adminItemsPager extends pager
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
			$htmlEnd = '<a href="'.sprintf($this->page_url,$this->nb_pages).'" class="end">'.
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
@brief Dotclear items column class.

Dotclear items column handles each column object use in adminItemsList class.
*/
class adminItemsColumn
{
	protected $core;		/// <b>object</b> dcCore object
	protected $id;			/// <b>string</b> ID of defined column
	protected $alias;		/// <b>string</b> ID of defined column
	protected $title;		/// <b>string</b> Title of defined column
	protected $callback;	/// <b>array</b> Callback calls to display defined column
	protected $html;		/// <b>array</b> Extra HTML for defined column
	
	/**
	Inits Generic column object
	
	@param	id		<b>string</b>		Column id
	@param	alias	<b>string</b>		Column alias for SQL
	@param	title	<b>string</b>		Column title (for table headers)
	@param	callback	<b>array</b>		Column callback (used for display)
	@param	html		<b>array</b>		Extra html (used for table headers)
	@param	sortable	<b>boolean</b>		Defines if the column can be sorted or not
	@param	listable	<b>boolean</b>		Defines if the column can be listed or not
	@param	can_hide	<b>boolean</b>		Defines if the column can be hidden or not
	*/
	public function __construct($id,$alias,$title,$callback,$html = null,$sortable = true,$listable = true,$can_hide = true)
	{
		if (!is_string($id) || $id === '') {
			throw new Exception(__('Invalid column ID'));
		}
		
		if (!is_string($title)) {
			throw new Exception(__('Invalid column title'));
		}
		
		if (is_null($html) || !is_array($html)) {
			$html = array();
		}
		
		if (!is_bool($sortable)) {
			$sortable = true;
		}
		
		if (!is_bool($listable)) {
			$listable = true;
		}
		
		if (!is_bool($can_hide)) {
			$can_hide = true;
		}
		
		if (!is_string($alias) && $sortable) {
			throw new Exception(__('Invalid column alias'));
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
				if ($p->name == 'adminItemsList') {
					$find_parent = true;
				}
				else {
					$p->getParentClass();
				}
			}
			
			if (!$p || !$f) {
				throw new Exception(__('Callback class should be inherited of adminItemsList class'));
			}
		}
		catch (Exception $e) {
			throw new Exception(sprintf(__('Invalid column callback: %s'),$e->getMessage()));
		}
		
		$this->id = $id;
		$this->alias = $alias;
		$this->title = $title;
		$this->callback = $callback;
		$this->html = $html;
		$this->sortable = $sortable;
		$this->listable = $listable;
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
		if (is_bool($visibility) && $this->can_hide) {
			$this->visibility = $visibility;
		}
		else {
			$this->visibility = true;
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
	Returns if the defined column can be sorted
	
	@return	<b>boolean</b>		true if column is sortable, false otherwise
	*/
	public function isSortable()
	{
		return $this->sortable;
	}
	
	/**
	Returns if the defined column can be listed
	
	@return	<b>boolean</b>		true if column is listable, false otherwise
	*/
	public function isListable()
	{
		return $this->listable;
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
@brief abstract items list class.

Dotclear items list handles administration lists
*/
abstract class adminItemsList implements dcFilterExtraInterface
{
	protected $core;
	protected $rs;
	protected $rs_count;
	protected $columns;
	protected $sortby;
	protected $order;
	protected $nb_per_page;
	protected $page;
	protected $default_sortby;
	protected $default_order;
	
	/*
	Sets columns of defined list
	*/
	abstract function setColumns();
	
	/**
	Inits List object
	
	@param	core		<b>dcCore</b>		dcCore object
	*/
	public function __construct($core)
	{
		
		$this->core = $core;
		$this->context = get_class($this);
		$this->columns = new ArrayObject();
		$this->form_prefix = 'col_';
		
		$this->html_prev = __('prev');
		$this->html_next = __('next');
		$this->html_start = __('start');
		$this->html_end = __('end');
		
		$this->nb_per_page = 10;
		$this->page = 1;
		
		$this->setColumns();
		
		# --BEHAVIOR-- adminItemsListConstruct
		$core->callBehavior('adminItemsListConstruct',$this);
		
	}
	
	public function __clone() {
		$arr = new ArrayObject();
		foreach ($this->columns as $k=>$v) {
			$arr[$k]= clone $v;
		}
		$this->columns = $arr;
	}
	/**
	Apply limit, sortby and order filters to items parameters
	
	@param	params	<b>array</b>	Items parameters
	
	@return	<b>array</b>		Items parameters
	*/
	public function applyFilters($params)
	{
		if (!is_null($this->sortby) && !is_null($this->order)) {
			if (
				isset($this->columns[$this->sortby]) &&
				in_array($this->order,array('asc','desc'))
			) {
				$params['order'] = $this->columns[$this->sortby]->getInfo('alias').' '.$this->order;
			}
		}
		
		$params['limit'] = array((($this->page-1)*$this->nb_per_page),$this->nb_per_page);
		
		return $params;
	}
	
	/**
	Sets items and items counter
	
	@param	rs		<b>recordSet</b>	Items recordSet to display
	@param	rs_count	<b>int</b>		Total items number
	*/
	public function setItems($rs,$rs_count)
	{
		$this->rs = $rs;
		$this->rs_count = $rs_count;
	}
	
	/**
	Returns HTML code form used to choose which column to display
	
	@return	<b>string</b>		HTML code form
	*/
	public function getFormContent()
	{
		$block = 
		'<h3>'.__('Displayed information').'</h3>'.
		'<ul>%s</ul>';
		
		$list = array();
		$sortby_combo = array();
		foreach ($this->columns as $k => $v) {
			$col_id = $this->form_prefix.$k;
			$col_label = sprintf('<label for="%s">%s</label>',$col_id,$v->getInfo('title'));
			$col_html = sprintf('<li class="line">%s</li>',$col_label.form::checkbox($col_id,1,$v->isVisible(),null,null,!$v->canHide()));
			
			if ($v->isListable()) {
				array_push($list,$col_html);
			}
			$sortby_combo[$v->getInfo('title')] = $k;
		}
		$order_combo = array(
				__('Descending') => 'desc',
				__('Ascending') => 'asc'
		);
		
		$nb_per_page = !is_null($this->nb_per_page) ? $this->nb_per_page : 10;
		
		return
		sprintf($block,implode('',$list)).
		'<p><label for="nb_per_page">'.__('Items per page:').
		'</label>&nbsp;'.form::field('nb_per_page',3,3,$nb_per_page).
		'</p>'.
		'<p><label for="sortby">'.__('Sort by:').
		'</label>&nbsp;'.form::combo('sortby',$sortby_combo,$this->sortby).
		'</p>'.
		'<p><label for="order">'.__('Order:').
		'</label>&nbsp;'.form::combo('order',$order_combo,$this->order).
		'</p>';
	}
	
	public function updateRequestParams($params) {
		if (!is_null($this->sortby) && isset($this->columns[$this->sortby])) {
			$params['sortby'] = $this->columns[$this->sortby]->getInfo('alias');
		}
		if (!is_null($this->order)) {
			$params['order'] = $this->order;
		}
		if (!is_null($this->nb_per_page)) {
			$params['nb_per_page'] = $this->nb_per_page;
		}
		if (!is_null($this->page)) {
			$params['page'] = $this->page;
		}
		foreach ($this->columns as $k => $v) {
			if($v->isVisible())
				$params[$this->form_prefix.$k] = 1;
		}

	}
	
	/**
	Returns HTML hidden fields for list options
	
	@return	<b>string</b>		HTML hidden fields
	*/
	public function getFormFieldsAsHidden()
	{
		$res = '';
		
		if (!is_null($this->sortby)) {
			$res .= form::hidden(array('sortby'),$this->sortby);
		}
		if (!is_null($this->order)) {
			$res .= form::hidden(array('order'),$this->order);
		}
		if (!is_null($this->nb_per_page)) {
			$res .= form::hidden(array('nb_per_page'),$this->nb_per_page);
		}
		if (!is_null($this->page)) {
			$res .= form::hidden(array('page'),$this->page);
		}
		
		return $res;
	}
	
	/**
	Returns HTML code list to display
	
	@param	enclose_block	<b>string</b>		HTML wrapper of defined list
	
	@return	<b>string</b>		HTML code list
	*/
	public function display($enclose_block = '')
	{
		if ($this->rs->isEmpty())
		{
			echo '<p><strong>'.__('No item').'</strong></p>';
		}
		else
		{
			$pager = new adminItemsPager($this->page,$this->rs_count,$this->nb_per_page,10);
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->html_start = $this->html_start;
			$pager->html_end = $this->html_end;
			$pager->var_page = 'page';
			
			$html_block =
			'<table class="maximal clear">'.
			$this->getCaption($this->page).
			'<thead><tr>';
			
			foreach ($this->columns as $k => $v) {
				if ($v->isVisible()) {
					$title = $v->getInfo('title');
					if ($v->isSortable()) {
						$title = sprintf('<a href="%2$s">%1$s</a>',$title,$this->getSortLink($v));
					}
					$html_extra = '';
					foreach ($v->getInfo('html') as $i => $j) {
						$html_extra = $i.'="'.implode(' ',$j).'"';
					}
					$html_extra = !empty($html_extra) ? ' '.$html_extra : '';
					$html_block .= sprintf('<th scope="col"%2$s>%1$s</th>',$title,$html_extra);
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
	@param	alias	<b>string</b>		Column alias for SQL
	@param	title	<b>string</b>		Column title (for table headers)
	@param	callback	<b>array</b>		Column callback (used for display)
	@param	html		<b>string</b>		Extra html (used for table headers)
	@param	sortable	<b>boolean</b>		Defines if the column can be sorted or not
	@param	listable	<b>boolean</b>		Defines if the column can be listed or not
	@param	can_hide	<b>boolean</b>		Defines if the column can be hidden or not
	*/
	protected function addColumn($id,$alias,$title,$callback,$html = null,$sortable = true,$listable = true,$can_hide = true)
	{
		try {
			if (is_null($html) || !is_array($html)) {
				$html = array();
			}
			if ($this->sortby === $alias && !is_null($this->order)) {
				if (array_key_exists('class',$html)) {
					array_push($html['class'],$this->order);
				}
				else {
					$html['class'] = array($this->order);
				}
			}
			$c = new adminItemsColumn($id,$alias,$title,$callback,$html,$sortable,$listable,$can_hide);
			$this->columns[$id] = $c;
		}
		catch (Exception $e) {
			if (DC_DEBUG) {
				$this->core->error->add(sprintf('[%s] %s',$id,$e->getMessage()));
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
	Returns default HTML code line
	
	@return	<b>string</b>		Default HTMl code line
	*/
	protected function getDefaultLine()
	{
		return '<tr class="line">%s</tr>';
	}
	
	/**
	Loads list from user settings
	*/
	public function load() {
		$ws = $this->core->auth->user_prefs->addWorkspace('lists');
		$user_pref = !is_null($ws->{$this->context.'_opts'}) ? unserialize($ws->{$this->context.'_opts'}) : array();
		$this->sortby = array_key_exists('sortby',$user_pref) ? $user_pref['sortby'] : $this->default_sortby;
		$this->order = array_key_exists('order',$user_pref) ? $user_pref['order'] : $this->default_order;
		$this->nb_per_page = array_key_exists('nb_per_page',$user_pref) ? $user_pref['nb_per_page'] : 10;
		$user_pref = !is_null($ws->{$this->context.'_col'}) ? unserialize($ws->{$this->context.'_col'}) : array();
		foreach ($this->columns as $k => $v) {
			$visibility =  array_key_exists($k,$user_pref) ? $user_pref[$k] : true;
			$v->setVisibility($visibility);
		}
		if ($this->sortby != null && !isset($this->columns[$this->sortby])) {
			// No alias found
			$this->sortby=$this->default_sortby;
			$this->order=$this->default_order;
		}

	}
	
	/**
	Saves list to user settings
	*/
	public function save() {
		$ws = $this->core->auth->user_prefs->addWorkspace('lists');
		$user_pref = !is_null($ws->{$this->context.'_opts'}) ? unserialize($ws->{$this->context.'_opts'}) : array();
		$user_pref['order'] = $this->order;
		$user_pref['nb_per_page'] = $this->nb_per_page;
		$user_pref['sortby'] = $this->sortby;
		
		$this->core->auth->user_prefs->lists->put($this->context.'_opts',serialize($user_pref),'string');
		
		$user_pref = !is_null($ws->{$this->context.'_col'}) ? unserialize($ws->{$this->context.'_col'}) : array();
		
		foreach ($this->columns as $k => $v) {
			$user_pref[$k] = $v->isVisible();
		}
		$this->core->auth->user_prefs->lists->put($this->context.'_col',serialize($user_pref),'string');
	}
	
	/**
	Set dedicated data from form submission 
	(either $_GET or $_POST depending on the context
	
	@param	data		<b>array</b>		Data to retrieve information from
	*/
	public function initializeFromData ($data)
	{
		$load_from_settings = true;
		foreach ($data as $k=>$v) {
			if (strpos($k,$this->form_prefix) === 0) {
				$load_from_settings = false;
			}
		}
		if ($load_from_settings) {
			$this->load();
		}
		# Sortby
		$this->sortby = array_key_exists('sortby',$data) ? $data['sortby'] : $this->sortby;
		$this->order = array_key_exists('order',$data) ? $data['order'] : $this->order;
		$this->nb_per_page = array_key_exists('nb_per_page',$data) ? (integer) $data['nb_per_page'] : $this->nb_per_page;
		# Page
		$this->page = array_key_exists('page',$data) ? (integer) $data['page'] : 1;
		if ($load_from_settings)
			return;
		foreach ($this->columns as $k => $v) {
			$key = $this->form_prefix.$k;
			$visibility = !array_key_exists($key,$data) ? false : true;
			$v->setVisibility($visibility);
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
	
	/**
	Returns link to sort the defined column
	
	@param	c		<b>adminGenericColumn</b>	Current column
	
	@return	<b>string</b>		HTML link
	*/
	private function getSortLink($c)
	{
		$order = 'desc';
		if (!is_null($this->sortby) && $this->sortby === $c->getInfo('id')) {
			if (!is_null($this->order) && $this->order === $order) {
				$order = 'asc';
			}
		}
		
		$args = $_GET;
		$args['sortby'] = $c->getInfo('id');
		$args['order'] = $order;
		
		array_walk($args,create_function('&$v,$k','$v=$k."=".$v;'));
		
		$url = $_SERVER['PHP_SELF'];
		$url .= '?'.implode('&amp;',$args);
		
		return $url;
	}
}

/**
@ingroup DC_CORE
@nosubgrouping
@brief abstract posts list class.

Handle posts list on admin side
*/
class adminPostList extends adminItemsList
{
	public function setColumns()
	{
		$this->addColumn('title','post_title',__('Title'),array('adminPostList','getTitle'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('date','post_dt',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('datetime','post_dt',__('Date and time'),array('adminPostList','getDateTime'));
		$this->addColumn('category','cat_title',__('Category'),array('adminPostList','getCategory'));
		$this->addColumn('author','user_id',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('comments','nb_comment',__('Comments'),array('adminPostList','getComments'));
		$this->addColumn('trackbacks','nb_trackback',__('Trackbacks'),array('adminPostList','getTrackbacks'));
		$this->addColumn('status','post_status',__('Status'),array('adminPostList','getStatus'));
		$this->default_sortby = 'datetime';
		$this->default_order = 'desc';

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
		$this->addColumn('title','post_title',__('Title'),array('adminPostMiniList','getTitle'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('date','post_dt',__('Date'),array('adminPostList','getDate'));
		$this->addColumn('author','user_id',__('Author'),array('adminPostList','getAuthor'));
		$this->addColumn('status','post_status',__('Status'),array('adminPostList','getStatus'));
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
class adminCommentList extends adminItemsList
{
	public function setColumns()
	{
		$this->addColumn('title','post_title',__('Title'),array('adminCommentList','getTitle'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('date','comment_dt',__('Date'),array('adminCommentList','getDate'));
		$this->addColumn('author','comment_author',__('Author'),array('adminCommentList','getAuthor'));
		$this->addColumn('type','comment_trackback',__('Type'),array('adminCommentList','getType'));
		$this->addColumn('status','comment_status',__('Status'),array('adminCommentList','getStatus'));
		$this->addColumn('edit',null,'',array('adminCommentList','getEdit'),null,false,false,false);
		$this->default_sortby = 'date';
		$this->default_order = 'desc';
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
class adminUserList extends adminItemsList
{
	public function setColumns()
	{
		$this->addColumn('username','U.user_id',__('Username'),array('adminUserList','getUserName'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('firstname','user_firstname',__('First name'),array('adminUserList','getFirstName'),array('class' => array('nowrap')));
		$this->addColumn('lastname','user_name',__('Last name'),array('adminUserList','getLastName'),array('class' => array('nowrap')));
		$this->addColumn('displayname','user_displayname',__('Display name'),array('adminUserList','getDisplayName'),array('class' => array('nowrap')));
		$this->addColumn('entries','nb_post',__('Entries'),array('adminUserList','getEntries'),array('class' => array('nowrap')));
		$this->default_sortby='lastname';
		$this->default_order='asc';
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
class adminBlogList extends adminItemsList
{
	public function setColumns()
	{
		$this->addColumn('blogname','UPPER(blog_name)',__('Blog name'),array('adminBlogList','getBlogName'),array('class' => array('maximal')),true,true,false);
		$this->addColumn('lastupdate','blog_upddt',__('Last update'),array('adminBlogList','getLastUpdate'),array('class' => array('nowrap')));
		$this->addColumn('entries',null,__('Entries'),array('adminBlogList','getEntries'),array('class' => array('nowrap')),false);
		$this->addColumn('blogid','B.blog_id',__('Blog ID'),array('adminBlogList','getBlogId'),array('class' => array('nowrap')));
		$this->addColumn('action',null,'',array('adminBlogList','getAction'),array('class' => array('nowrap')),false);
		$this->addColumn('status','blog_status',__('status'),array('adminBlogList','getStatus'),array('class' => array('nowrap')));
		$this->default_sortby='lastupdate';
		$this->default_order='desc';
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
		$this->addColumn('blogid','B.blog_id',__('Blog ID'),array('adminBlogPermissionsList','getBlogId'),array('class' => array('nowrap')),false,true,false);
		$this->addColumn('blogname','UPPER(blog_name)',__('Blog name'),array('adminBlogPermissionsList','getBlogName'),array('class' => array('maximal')),false);
		$this->addColumn('entries',null,__('Entries'),array('adminBlogList','getEntries'),array('class' => array('nowrap')),false);
		$this->addColumn('status','blog_status',__('status'),array('adminBlogList','getStatus'),array('class' => array('nowrap')),false);
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
