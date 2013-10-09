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

/**
@ingroup DC_CORE
@nosubgrouping
@brief Dotclear blog class.

Dotclear blog class instance is provided by dcCore $blog property.
*/
class dcBlog
{
	/** @var dcCore dcCore instance */
	protected $core;
	/** @var connection Database connection object */
	public $con;
	/** @var string Database table prefix */
	public $prefix;
	
	/** @var string Blog ID */
	public $id;
	/** @var string Blog unique ID */
	public $uid;
	/** @var string Blog name */
	public $name;
	/** @var string Blog description */
	public $desc;
	/** @var string Blog URL */
	public $url;
	/** @var string Blog host */
	public $host;
	/** @var string Blog creation date */
	public $creadt;
	/** @var string Blog last update date */
	public $upddt;
	/** @var string Blog status */
	public $status;
	
	/** @var dcSettings dcSettings object */
	public $settings;
	/** @var string Blog theme path */
	public $themes_path;
	/** @var string Blog public path */
	public $public_path;
	
	private $post_status = array();
	private $comment_status = array();
	
	private $categories;
	
	/** @var boolean Disallow entries password protection */
	public $without_password = true;
	
	/**
	Inits dcBlog object
	
	@param	core		<b>dcCore</b>		Dotclear core reference
	@param	id		<b>string</b>		Blog ID
	*/
	public function __construct($core, $id)
	{
		$this->con =& $core->con;
		$this->prefix = $core->prefix;
		$this->core =& $core;
		
		if (($b = $this->core->getBlog($id)) !== false)
		{
			$this->id = $id;
			$this->uid = $b->blog_uid;
			$this->name = $b->blog_name;
			$this->desc = $b->blog_desc;
			$this->url = $b->blog_url;
			$this->host = http::getHostFromURL($this->url);
			$this->creadt = strtotime($b->blog_creadt);
			$this->upddt = strtotime($b->blog_upddt);
			$this->status = $b->blog_status;
			
			$this->settings = new dcSettings($this->core,$this->id);
			
			$this->themes_path = path::fullFromRoot($this->settings->system->themes_path,DC_ROOT);
			$this->public_path = path::fullFromRoot($this->settings->system->public_path,DC_ROOT);
			
			$this->post_status['-2'] = __('Pending');
			$this->post_status['-1'] = __('Scheduled');
			$this->post_status['0'] = __('Unpublished');
			$this->post_status['1'] = __('Published');
			
			$this->comment_status['-2'] = __('Junk');
			$this->comment_status['-1'] = __('Pending');
			$this->comment_status['0'] = __('Unpublished');
			$this->comment_status['1'] = __('Published');
			
			# --BEHAVIOR-- coreBlogConstruct
			$this->core->callBehavior('coreBlogConstruct',$this);
		}
	}
	
	/// @name Common public methods
	//@{
	/**
	Returns blog URL ending with a question mark.
	*/
	public function getQmarkURL()
	{
		if (substr($this->url,-1) != '?') {
			return $this->url.'?';
		}
		
		return $this->url;
	}
	
	/**
	Returns an entry status name given to a code. Status are translated, never
	use it for tests. If status code does not exist, returns <i>unpublished</i>.
	
	@param	s	<b>integer</b> Status code
	@return	<b>string</b> Blog status name
	*/
	public function getPostStatus($s)
	{
		if (isset($this->post_status[$s])) {
			return $this->post_status[$s];
		}
		return $this->post_status['0'];
	}
	
	/**
	Returns an array of available entry status codes and names.
	
	@return	<b>array</b> Simple array with codes in keys and names in value
	*/
	public function getAllPostStatus()
	{
		return $this->post_status;
	}
	
	/**
	Returns an array of available comment status codes and names.
	
	@return	<b>array</b> Simple array with codes in keys and names in value
	*/
	public function getAllCommentStatus()
	{
		return $this->comment_status;
	}
	
	/**
	Disallows entries password protection. You need to set it to
	<var>false</var> while serving a public blog.
	
	@param	v		<b>boolean</b>
	*/
	public function withoutPassword($v)
	{
		$this->without_password = (boolean) $v;
	}
	//@}
	
	/// @name Triggers methods
	//@{
	/**
	Updates blog last update date. Should be called every time you change
	an element related to the blog.
	*/
	public function triggerBlog()
	{
		$cur = $this->con->openCursor($this->prefix.'blog');
		
		$cur->blog_upddt = date('Y-m-d H:i:s');
		
		$cur->update("WHERE blog_id = '".$this->con->escape($this->id)."' ");
		
		# --BEHAVIOR-- coreBlogAfterTriggerBlog
		$this->core->callBehavior('coreBlogAfterTriggerBlog',$cur);
	}
	
	/**
	Updates comment and trackback counters in post table. Should be called
	every time a comment or trackback is added, removed or changed its status.
	
	@param	id		<b>integer</b>		Comment ID
	@param	del		<b>boolean</b>		If comment is delete, set this to true
	*/
	public function triggerComment($id,$del=false)
	{
		$this->triggerComments($id,$del);
	}
	
	/**
	Updates comments and trackbacks counters in post table. Should be called
	every time comments or trackbacks are added, removed or changed their status.
	
	@param	ids		<b>mixed</b>		Comment(s) ID(s)
	@param	del		<b>boolean</b>		If comment is delete, set this to true
	@param	affected_posts		<b>mixed</b>		Posts(s) ID(s)
	*/
	public function triggerComments($ids, $del=false, $affected_posts=null)
	{
		$comments_ids = dcUtils::cleanIds($ids);
		
		# Get posts affected by comments edition
		if (empty($affected_posts)) {
			$strReq = 
				'SELECT post_id '.
				'FROM '.$this->prefix.'comment '.
				'WHERE comment_id'.$this->con->in($comments_ids).
				'GROUP BY post_id';
			
			$rs = $this->con->select($strReq);
			
			$affected_posts = array();
			while ($rs->fetch()) {
				$affected_posts[] = (integer) $rs->post_id;
			}
		}
		
		if (!is_array($affected_posts) || empty($affected_posts)) {
			return;
		}
		
		# Count number of comments if exists for affected posts
		$strReq = 
			'SELECT post_id, COUNT(post_id) AS nb_comment, comment_trackback '.
			'FROM '.$this->prefix.'comment '.
			'WHERE comment_status = 1 '.
			'AND post_id'.$this->con->in($affected_posts).
			'GROUP BY post_id,comment_trackback';
		
		$rs = $this->con->select($strReq);
		
		$posts = array();
		while ($rs->fetch()) {
			if ($rs->comment_trackback) {
				$posts[$rs->post_id]['trackback'] = $rs->nb_comment;
			} else {
				$posts[$rs->post_id]['comment'] = $rs->nb_comment;
			}
		}
		
		# Update number of comments on affected posts
		$cur = $this->con->openCursor($this->prefix.'post');
		foreach($affected_posts as $post_id)
		{
			$cur->clean();
			
			if (!array_key_exists($post_id,$posts)) {
				$cur->nb_trackback = 0;
				$cur->nb_comment = 0;
			} else {
				$cur->nb_trackback = empty($posts[$post_id]['trackback']) ? 0 : $posts[$post_id]['trackback'];
				$cur->nb_comment = empty($posts[$post_id]['comment']) ? 0 : $posts[$post_id]['comment'];
			}
			
			$cur->update('WHERE post_id = '.$post_id);
		}
	}
	//@}
	
	/// @name Categories management methods
	//@{
	public function categories()
	{
		if (!($this->categories instanceof dcCategories)) {
			$this->categories = new dcCategories($this->core);
		}
		
		return $this->categories;
	}
	
	/**
	Retrieves categories. <var>$params</var> is an associative array which can
	take the following parameters:
	
	- post_type: Get only entries with given type (default "post")
	- cat_url: filter on cat_url field
	- cat_id: filter on cat_id field
	- start: start with a given category
	- level: categories level to retrieve
	
	@param	params	<b>array</b>		Parameters
	@return	<b>record</b>
	*/
	public function getCategories($params=array())
	{
		$c_params = array();
		if (isset($params['post_type'])) {
			$c_params['post_type'] = $params['post_type'];
			unset($params['post_type']);
		}
		$counter = $this->getCategoriesCounter($c_params);
		
		if (isset($params['without_empty']) && ($params['without_empty'] == false)) {
			$without_empty = false;
		} else {
			$without_empty = $this->core->auth->userID() == false; # Get all categories if in admin display
		}
		
		$start = isset($params['start']) ? (integer) $params['start'] : 0;
		$l = isset($params['level']) ? (integer) $params['level'] : 0;
		
		$rs = $this->categories()->getChildren($start,null,'desc');
		
		# Get each categories total posts count
		$data = array();
		$stack = array();
		$level = 0;
		$cols = $rs->columns();
		while ($rs->fetch())
		{
			$nb_post = isset($counter[$rs->cat_id]) ? (integer) $counter[$rs->cat_id] : 0;
			
			if ($rs->level > $level) {
				$nb_total = $nb_post;
				$stack[$rs->level] = (integer) $nb_post;
			} elseif ($rs->level == $level) {
				$nb_total = $nb_post;
				$stack[$rs->level] += $nb_post;
			} else {
				$nb_total = $stack[$rs->level+1] + $nb_post;
				if (isset($stack[$rs->level])) {
					$stack[$rs->level] += $nb_total;
				} else {
					$stack[$rs->level] = $nb_total;
				}
				unset($stack[$rs->level+1]);
			}
			
			if ($nb_total == 0 && $without_empty) {
				continue;
			}
			
			$level = $rs->level;
			
			$t = array();
			foreach ($cols as $c) {
				$t[$c] = $rs->f($c);
			}
			$t['nb_post'] = $nb_post;
			$t['nb_total'] = $nb_total;
			
			if ($l == 0 || ($l > 0 && $l == $rs->level)) {
				array_unshift($data,$t);
			}
		}
		
		# We need to apply filter after counting
		if (isset($params['cat_id']) && $params['cat_id'] !== '')
		{
			$found = false;
			foreach ($data as $v) {
				if ($v['cat_id'] == $params['cat_id']) {
					$found = true;
					$data = array($v);
					break;
				}
			}
			if (!$found) {
				$data = array();
			}
		}
		
		if (isset($params['cat_url']) && ($params['cat_url'] !== '') 
			&& !isset($params['cat_id']))
		{
			$found = false;
			foreach ($data as $v) {
				if ($v['cat_url'] == $params['cat_url']) {
					$found = true;
					$data = array($v);
					break;
				}
			}
			if (!$found) {
				$data = array();
			}
		}
		
		return staticRecord::newFromArray($data);
	}
	
	/**
	Retrieves a category by its ID.
	
	@param	id		<b>integer</b>		Category ID
	@return	<b>record</b>
	*/
	public function getCategory($id)
	{
		return $this->getCategories(array('cat_id' => $id));
	}
	
	/**
	Retrieves parents of a given category.
	
	@param	id		<b>integer</b>		Category ID
	@return	<b>record</b>
	*/
	public function getCategoryParents($id)
	{
		return $this->categories()->getParents($id);
	}
	
	/**
	Retrieves first parent of a given category.
	
	@param	id		<b>integer</b>		Category ID
	@return	<b>record</b>
	*/
	public function getCategoryParent($id)
	{
		return $this->categories()->getParent($id);
	}
	
	/**
	Retrieves all category's first children
	
	@param	id		<b>integer</b>		Category ID
	@return	<b>record</b>
	*/
	public function getCategoryFirstChildren($id)
	{
		return $this->getCategories(array('start' => $id,'level' => $id == 0 ? 1 : 2));
	}
	
	private function getCategoriesCounter($params=array())
	{
		$strReq =
		'SELECT  C.cat_id, COUNT(P.post_id) AS nb_post '.
		'FROM '.$this->prefix.'category AS C '.
		'JOIN '.$this->prefix."post P ON (C.cat_id = P.cat_id AND P.blog_id = '".$this->con->escape($this->id)."' ) ".
		"WHERE C.blog_id = '".$this->con->escape($this->id)."' ";
		
		if (!$this->core->auth->userID()) {
			$strReq .= 'AND P.post_status = 1 ';
		}
		
		if (!empty($params['post_type'])) {
			$strReq .= 'AND P.post_type '.$this->con->in($params['post_type']);
		}
		
		$strReq .= 'GROUP BY C.cat_id ';
		
		$rs = $this->con->select($strReq);
		$counters = array();
		while ($rs->fetch()) {
			$counters[$rs->cat_id] = $rs->nb_post;
		}
		
		return $counters;
	}
	
	/**
	Creates a new category. Takes a cursor as input and returns the new category
	ID.
	
	@param	cur		<b>cursor</b>		Category cursor
	@return	<b>integer</b>		New category ID
	*/
	public function addCategory($cur,$parent=0)
	{
		if (!$this->core->auth->check('categories',$this->id)) {
			throw new Exception(__('You are not allowed to add categories'));
		}
		
		$url = array();
		if ($parent != 0)
		{
			$rs = $this->getCategory($parent);
			if ($rs->isEmpty()) {
				$url = array();
			} else {
				$url[] = $rs->cat_url;
			}
		}
		
		if ($cur->cat_url == '') {
			$url[] = text::tidyURL($cur->cat_title,false);
		} else {
			$url[] = $cur->cat_url;
		}
		
		$cur->cat_url = implode('/',$url);
		
		$this->getCategoryCursor($cur);
		$cur->blog_id = (string) $this->id;
		
		# --BEHAVIOR-- coreBeforeCategoryCreate
		$this->core->callBehavior('coreBeforeCategoryCreate',$this,$cur);
		
		$id = $this->categories()->addNode($cur,$parent);
		# Update category's cursor
		$rs = $this->getCategory($id);
		if (!$rs->isEmpty()) {
			$cur->cat_lft = $rs->cat_lft;
			$cur->cat_rgt = $rs->cat_rgt;
		}
		
		# --BEHAVIOR-- coreAfterCategoryCreate
		$this->core->callBehavior('coreAfterCategoryCreate',$this,$cur);
		$this->triggerBlog();
		
		return $cur->cat_id;
	}
	
	/**
	Updates an existing category.
	
	@param	id		<b>integer</b>		Category ID
	@param	cur		<b>cursor</b>		Category cursor
	*/
	public function updCategory($id,$cur)
	{
		if (!$this->core->auth->check('categories',$this->id)) {
			throw new Exception(__('You are not allowed to update categories'));
		}
		
		if ($cur->cat_url == '')
		{
			$url = array();
			$rs = $this->categories()->getParents($id);
			while ($rs->fetch()) {
				if ($rs->index() == $rs->count()-1) {
					$url[] = $rs->cat_url;
				}
			}
			
			
			$url[] = text::tidyURL($cur->cat_title,false);
			$cur->cat_url = implode('/',$url);
		}
		
		$this->getCategoryCursor($cur,$id);
		
		# --BEHAVIOR-- coreBeforeCategoryUpdate
		$this->core->callBehavior('coreBeforeCategoryUpdate',$this,$cur);
		
		$cur->update(
		'WHERE cat_id = '.(integer) $id.' '.
		"AND blog_id = '".$this->con->escape($this->id)."' ");
		
		# --BEHAVIOR-- coreAfterCategoryUpdate
		$this->core->callBehavior('coreAfterCategoryUpdate',$this,$cur);
		
		$this->triggerBlog();
	}

        /**
        Set category position

        @param  id              <b>integer</b>          Category ID
        @param  left            <b>integer</b>          Category ID before
        @param  right           <b>integer</b>          Category ID after
        */
        public function updCategoryPosition($id,$left,$right)
        {
                $this->categories()->updatePosition($id,$left,$right);
                $this->triggerBlog();
        }
	
	/**
	DEPRECATED METHOD. Use dcBlog::setCategoryParent and dcBlog::moveCategory
	instead.
	
	@param	id		<b>integer</b>		Category ID
	@param	order	<b>integer</b>		Category position
	*/
	public function updCategoryOrder($id,$order)
	{
		return;
	}
	
	/**
	Set a category parent
	
	@param	id		<b>integer</b>		Category ID
	@param	parent	<b>integer</b>		Parent Category ID
	*/
	public function setCategoryParent($id,$parent)
	{
		$this->categories()->setNodeParent($id,$parent);
		$this->triggerBlog();
	}
	
	/**
	Set category position
	
	@param	id		<b>integer</b>		Category ID
	@param	sibling	<b>integer</b>		Sibling Category ID
	@param	move		<b>integer</b>		Order (before|after)
	*/
	public function setCategoryPosition($id,$sibling,$move)
	{
		$this->categories()->setNodePosition($id,$sibling,$move);
		$this->triggerBlog();
	}
	
	/**
	Deletes a category.
	
	@param	id		<b>integer</b>		Category ID
	*/
	public function delCategory($id)
	{
		if (!$this->core->auth->check('categories',$this->id)) {
			throw new Exception(__('You are not allowed to delete categories'));
		}
		
		$strReq = 'SELECT COUNT(post_id) AS nb_post '.
				'FROM '.$this->prefix.'post '.
				'WHERE cat_id = '.(integer) $id.' '.
				"AND blog_id = '".$this->con->escape($this->id)."' ";
		
		$rs = $this->con->select($strReq);
		
		if ($rs->nb_post > 0) {
			throw new Exception(__('This category is not empty.'));
		}
		
		$this->categories()->deleteNode($id,true);
		$this->triggerBlog();
	}
	
	/**
	Reset categories order and relocate them to first level
	*/
	public function resetCategoriesOrder()
	{
		if (!$this->core->auth->check('categories',$this->id)) {
			throw new Exception(__('You are not allowed to reset categories order'));
		}
		
		$this->categories()->resetOrder();
		$this->triggerBlog();
	}
	
	private function checkCategory($title,$url,$id=null)
	{
		# Let's check if URL is taken...
		$strReq = 
			'SELECT cat_url FROM '.$this->prefix.'category '.
			"WHERE cat_url = '".$this->con->escape($url)."' ".
			($id ? 'AND cat_id <> '.(integer) $id. ' ' : '').
			"AND blog_id = '".$this->con->escape($this->id)."' ".
			'ORDER BY cat_url DESC';
		
		$rs = $this->con->select($strReq);
		
		if (!$rs->isEmpty())
		{
			if ($this->con->driver() == 'mysql' || $this->con->driver() == 'mysqli') {
				$clause = "REGEXP '^".$this->con->escape($url)."[0-9]+$'";
			} elseif ($this->con->driver() == 'pgsql') {
				$clause = "~ '^".$this->con->escape($url)."[0-9]+$'";
			} else {
				$clause = "LIKE '".$this->con->escape($url)."%'";
			}
			$strReq = 
				'SELECT cat_url FROM '.$this->prefix.'category '.
				"WHERE cat_url ".$clause.' '.
				($id ? 'AND cat_id <> '.(integer) $id. ' ' : '').
				"AND blog_id = '".$this->con->escape($this->id)."' ".
				'ORDER BY cat_url DESC ';
			
			$rs = $this->con->select($strReq);
			$a = array();
			while ($rs->fetch()) {
				$a[] = $rs->cat_url;
			}
			
			natsort($a);
			$t_url = end($a);
			
			if (preg_match('/(.*?)([0-9]+)$/',$t_url,$m)) {
				$i = (integer) $m[2];
				$url = $m[1];
			} else {
				$i = 1;
			}
			
			return $url.($i+1);
		}
		
		# URL is empty?
		if ($url == '') {
			throw new Exception(__('Empty category URL'));
		}
		
		return $url;
	}
	
	private function getCategoryCursor($cur,$id=null)
	{
		if ($cur->cat_title == '') {
			throw new Exception(__('You must provide a category title'));
		}
		
		# If we don't have any cat_url, let's do one
		if ($cur->cat_url == '') {
			$cur->cat_url = text::tidyURL($cur->cat_title,false);
		}
		
		# Still empty ?
		if ($cur->cat_url == '') {
			throw new Exception(__('You must provide a category URL'));
		} else {
			$cur->cat_url = text::tidyURL($cur->cat_url,true);
		}
		
		# Check if title or url are unique
		$cur->cat_url = $this->checkCategory($cur->cat_title,$cur->cat_url,$id);
		
		if ($cur->cat_desc !== null) {
			$cur->cat_desc = $this->core->HTMLfilter($cur->cat_desc);
		}
	}
	//@}
	
	/// @name Entries management methods
	//@{
	/**
	Retrieves entries. <b>$params</b> is an array taking the following
	optionnal parameters:
	
	- no_content: Don't retrieve entry content (excerpt and content)
	- post_type: Get only entries with given type (default "post", array for many types and '' for no type)
	- post_id: (integer or array) Get entry with given post_id
	- post_url: Get entry with given post_url field
	- user_id: (integer) Get entries belonging to given user ID
	- cat_id: (string or array) Get entries belonging to given category ID
	- cat_id_not: deprecated (use cat_id with "id ?not" instead)
	- cat_url: (string or array) Get entries belonging to given category URL
	- cat_url_not: deprecated (use cat_url with "url ?not" instead)
	- post_status: (integer) Get entries with given post_status
	- post_selected: (boolean) Get select flaged entries
	- post_year: (integer) Get entries with given year
	- post_month: (integer) Get entries with given month
	- post_day: (integer) Get entries with given day
	- post_lang: Get entries with given language code
	- search: Get entries corresponding of the following search string
	- columns: (array) More columns to retrieve
	- sql: Append SQL string at the end of the query
	- from: Append SQL string after "FROM" statement in query
	- order: Order of results (default "ORDER BY post_dt DES")
	- limit: Limit parameter
	- sql_only : return the sql request instead of results. Only ids are selected
	- exclude_post_id : (integer or array) Exclude entries with given post_id
	
	Please note that on every cat_id or cat_url, you can add ?not to exclude
	the category and ?sub to get subcategories.
	
	@param	params		<b>array</b>		Parameters
	@param	count_only	<b>boolean</b>		Only counts results
	@return	<b>record</b>	A record with some more capabilities or the SQL request
	*/
	public function getPosts($params=array(),$count_only=false)
	{
		# --BEHAVIOR-- coreBlogBeforeGetPosts
		$params = new ArrayObject($params);
		$this->core->callBehavior('coreBlogBeforeGetPosts',$params);

		if ($count_only)
		{
			$strReq = 'SELECT count(DISTINCT P.post_id) ';
		}
		elseif (!empty($params['sql_only'])) 
		{
			$strReq = 'SELECT P.post_id ';
		}
		else
		{
			if (!empty($params['no_content'])) {
				$content_req = '';
			} else {
				$content_req =
				'post_excerpt, post_excerpt_xhtml, '.
				'post_content, post_content_xhtml, post_notes, ';
			}
			
			if (!empty($params['columns']) && is_array($params['columns'])) {
				$content_req .= implode(', ',$params['columns']).', ';
			}
			
			if ($this->core->con->driver() == 'pgsql' && isset($params['media'])) {
				$strReq = 'SELECT DISTINCT ON (P.post_id) ';
			} else {
				$strReq = 'SELECT ';
			}
			$strReq .=
			'P.post_id, P.blog_id, P.user_id, P.cat_id, post_dt, '.
			'post_tz, post_creadt, post_upddt, post_format, post_password, '.
			'post_url, post_lang, post_title, '.$content_req.
			'post_type, post_meta, post_status, post_selected, post_position, '.
			'post_open_comment, post_open_tb, nb_comment, nb_trackback, '.
			'U.user_name, U.user_firstname, U.user_displayname, U.user_email, '.
			'U.user_url, '.
			'C.cat_title, C.cat_url, C.cat_desc ';
		}
		
		$strReq .=
		'FROM '.$this->prefix.'post P '.
		'INNER JOIN '.$this->prefix.'user U ON U.user_id = P.user_id '.
		'LEFT OUTER JOIN '.$this->prefix.'category C ON P.cat_id = C.cat_id ';

		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}
		
		$strReq .=
		"WHERE P.blog_id = '".$this->con->escape($this->id)."' ";
		
		if (!$this->core->auth->check('contentadmin',$this->id)) {
			$strReq .= 'AND ((post_status = 1 ';
			
			if ($this->without_password) {
				$strReq .= 'AND post_password IS NULL ';
			}
			$strReq .= ') ';
			
			if ($this->core->auth->userID()) {
				$strReq .= "OR P.user_id = '".$this->con->escape($this->core->auth->userID())."')";
			} else {
				$strReq .= ') ';
			}
		}
		
		#Adding parameters
		if (isset($params['post_type']))
		{
			if (is_array($params['post_type']) || $params['post_type'] != '') {
				$strReq .= 'AND post_type '.$this->con->in($params['post_type']);
			}
		}
		else
		{
			$strReq .= "AND post_type = 'post' ";
		}
		
		if (isset($params['post_id']) && $params['post_id'] !== '') {
			if (is_array($params['post_id'])) {
				array_walk($params['post_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			} else {
				$params['post_id'] = array((integer) $params['post_id']);
			}
			$strReq .= 'AND P.post_id '.$this->con->in($params['post_id']);
		}
		
		if (isset($params['exclude_post_id']) && $params['exclude_post_id'] !== '') {
			if (is_array($params['exclude_post_id'])) {
				array_walk($params['exclude_post_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			} else {
				$params['exclude_post_id'] = array((integer) $params['exclude_post_id']);
			}
			$strReq .= 'AND P.post_id NOT '.$this->con->in($params['exclude_post_id']);
		}
		
		if (isset($params['post_url']) && $params['post_url'] !== '') {
			$strReq .= "AND post_url = '".$this->con->escape($params['post_url'])."' ";
		}
		
		if (!empty($params['user_id'])) {
			$strReq .= "AND U.user_id = '".$this->con->escape($params['user_id'])."' ";
		}
		
		if (isset($params['cat_id']) && $params['cat_id'] !== '')
		{
			if (!is_array($params['cat_id'])) {
				$params['cat_id'] = array($params['cat_id']);
			}
			if (!empty($params['cat_id_not'])) {
				array_walk($params['cat_id'],create_function('&$v,$k','$v=$v." ?not";'));
			}
			$strReq .= 'AND '.$this->getPostsCategoryFilter($params['cat_id'],'cat_id').' ';
		}
		elseif (isset($params['cat_url']) && $params['cat_url'] !== '')
		{
			if (!is_array($params['cat_url'])) {
				$params['cat_url'] = array($params['cat_url']);
			}
			if (!empty($params['cat_url_not'])) {
				array_walk($params['cat_url'],create_function('&$v,$k','$v=$v." ?not";'));
			}
			$strReq .= 'AND '.$this->getPostsCategoryFilter($params['cat_url'],'cat_url').' ';
		}
		
		/* Other filters */
		if (isset($params['post_status'])) {
			$strReq .= 'AND post_status = '.(integer) $params['post_status'].' ';
		}
		
		if (isset($params['post_selected'])) {
			$strReq .= 'AND post_selected = '.(integer) $params['post_selected'].' ';
		}
		
		if (!empty($params['post_year'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%Y').' = '.
			"'".sprintf('%04d',$params['post_year'])."' ";
		}
		
		if (!empty($params['post_month'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%m').' = '.
			"'".sprintf('%02d',$params['post_month'])."' ";
		}
		
		if (!empty($params['post_day'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%d').' = '.
			"'".sprintf('%02d',$params['post_day'])."' ";
		}
		
		if (!empty($params['post_lang'])) {
			$strReq .= "AND P.post_lang = '".$this->con->escape($params['post_lang'])."' ";
		}
		
		if (!empty($params['search']))
		{
			$words = text::splitWords($params['search']);
			
			if (!empty($words))
			{
				# --BEHAVIOR-- corePostSearch
				if ($this->core->hasBehavior('corePostSearch')) {
					$this->core->callBehavior('corePostSearch',$this->core,array(&$words,&$strReq,&$params));
				}
				
				if ($words)
				{
					foreach ($words as $i => $w) {
						$words[$i] = "post_words LIKE '%".$this->con->escape($w)."%'";
					}
					$strReq .= 'AND '.implode(' AND ',$words).' ';
				}
			}
		}
		
		if (isset($params['media'])) {
			$strReq .= 'AND P.post_id ';
			if ($params['media'] == '0') {
				$strReq .= 'NOT ';
			}
			$strReq .= 'IN (SELECT M.post_id FROM '.$this->prefix.'post_media M ';
			if (isset($params['link_type'])) {
				$strReq .= " WHERE M.link_type ".$this->con->in($params['link_type'])." ";
			}
			$strReq .= ")";
		}

		if (!empty($params['sql'])) {
			$strReq .= $params['sql'].' ';
		}

		if (!$count_only)
		{
			if (!empty($params['order'])) {
				$strReq .= 'ORDER BY '.$this->con->escape($params['order']).' ';
			} else {
				$strReq .= 'ORDER BY post_dt DESC ';
			}
		}
		
		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $this->con->limit($params['limit']);
		}
		
		if (!empty($params['sql_only'])) {
			return $strReq;
		}

		$rs = $this->con->select($strReq);
		$rs->core = $this->core;
		$rs->_nb_media = array();
		$rs->extend('rsExtPost');
		
		# --BEHAVIOR-- coreBlogGetPosts
		$this->core->callBehavior('coreBlogGetPosts',$rs);
		
		return $rs;
	}
	
	/**
	Returns a record with post id, title and date for next or previous post
	according to the post ID.
	$dir could be 1 (next post) or -1 (previous post).
	
	@param	post_id				<b>integer</b>		Post ID
	@param	dir					<b>integer</b>		Search direction
	@param	restrict_to_category	<b>boolean</b>		Restrict to post with same category
	@param	restrict_to_lang		<b>boolean</b>		Restrict to post with same lang
	@return	record
	*/
	public function getNextPost($post,$dir,$restrict_to_category=false, $restrict_to_lang=false)
	{
		$dt = $post->post_dt;
		$post_id = (integer) $post->post_id;
		
		if($dir > 0) {
			$sign = '>';
			$order = 'ASC';
		}
		else {
			$sign = '<';
			$order = 'DESC';
		}
		
		$params['post_type'] = $post->post_type;
		$params['limit'] = 1;
		$params['order'] = 'post_dt '.$order.', P.post_id '.$order;
		$params['sql'] =
		'AND ( '.
		"	(post_dt = '".$this->con->escape($dt)."' AND P.post_id ".$sign." ".$post_id.") ".
		"	OR post_dt ".$sign." '".$this->con->escape($dt)."' ".
		') ';
		
		if ($restrict_to_category) {
			$params['sql'] .= $post->cat_id ? 'AND P.cat_id = '.(integer) $post->cat_id.' ' : 'AND P.cat_id IS NULL ';
		}
		
		if ($restrict_to_lang) {
			$params['sql'] .= $post->post_lang ? 'AND P.post_lang = \''. $this->con->escape($post->post_lang) .'\' ': 'AND P.post_lang IS NULL ';
		}
		
		$rs = $this->getPosts($params);
		
		if ($rs->isEmpty()) {
			return null;
		}
		
		return $rs;
	}
	
	/**
	Retrieves different languages and post count on blog, based on post_lang
	field. <var>$params</var> is an array taking the following optionnal
	parameters:
	
	- post_type: Get only entries with given type (default "post", '' for no type)
	- lang: retrieve post count for selected lang
	- order: order statement (default post_lang DESC)
	
	@param	params	<b>array</b>		Parameters
	@return	record
	*/
	public function getLangs($params=array())
	{
		$strReq = 'SELECT COUNT(post_id) as nb_post, post_lang '.
				'FROM '.$this->prefix.'post '.
				"WHERE blog_id = '".$this->con->escape($this->id)."' ".
				"AND post_lang <> '' ".
				"AND post_lang IS NOT NULL ";
		
		if (!$this->core->auth->check('contentadmin',$this->id)) {
			$strReq .= 'AND ((post_status = 1 ';
			
			if ($this->without_password) {
				$strReq .= 'AND post_password IS NULL ';
			}
			$strReq .= ') ';
			
			if ($this->core->auth->userID()) {
				$strReq .= "OR user_id = '".$this->con->escape($this->core->auth->userID())."')";
			} else {
				$strReq .= ') ';
			}
		}
		
		if (isset($params['post_type'])) {
			if ($params['post_type'] != '') {
				$strReq .= "AND post_type = '".$this->con->escape($params['post_type'])."' ";
			}
		} else {
			$strReq .= "AND post_type = 'post' ";
		}
		
		if (isset($params['lang'])) {
			$strReq .= "AND post_lang = '".$this->con->escape($params['lang'])."' ";
		}
		
		$strReq .= 'GROUP BY post_lang ';
		
		$order = 'desc';
		if (!empty($params['order']) && preg_match('/^(desc|asc)$/i',$params['order'])) {
			$order = $params['order'];
		}
		$strReq .= 'ORDER BY post_lang '.$order.' ';
		
		return $this->con->select($strReq);
	}
	
	/**
	Returns a record with all distinct blog dates and post count.
	<var>$params</var> is an array taking the following optionnal parameters:
	
	- type: (day|month|year) Get days, months or years
	- year: (integer) Get dates for given year
	- month: (integer) Get dates for given month
	- day: (integer) Get dates for given day
	- cat_id: (integer) Category ID filter
	- cat_url: Category URL filter
	- post_lang: lang of the posts
	- next: Get date following match
	- previous: Get date before match
	- order: Sort by date "ASC" or "DESC"
	
	@param	params	<b>array</b>		Parameters array
	@return	record
	*/
	public function getDates($params=array())
	{
		$dt_f = '%Y-%m-%d';
		$dt_fc = '%Y%m%d';
		if (isset($params['type'])) {
			if ($params['type'] == 'year') {
				$dt_f = '%Y-01-01';
				$dt_fc = '%Y0101';
			} elseif ($params['type'] == 'month') {
				$dt_f = '%Y-%m-01';
				$dt_fc = '%Y%m01';
			}
		}
		$dt_f .= ' 00:00:00';
		$dt_fc .= '000000';
		
		$cat_field = $catReq = $limit = '';
		
		if (isset($params['cat_id']) && $params['cat_id'] !== '') {
			$catReq = 'AND P.cat_id = '.(integer) $params['cat_id'].' ';
			$cat_field = ', C.cat_url ';
		} elseif (isset($params['cat_url']) && $params['cat_url'] !== '') {
			$catReq = "AND C.cat_url = '".$this->con->escape($params['cat_url'])."' ";
			$cat_field = ', C.cat_url ';
		}
		if (!empty($params['post_lang'])) {
			$catReq = 'AND P.post_lang = \''. $params['post_lang'].'\' ';
		}
		
		$strReq = 'SELECT DISTINCT('.$this->con->dateFormat('post_dt',$dt_f).') AS dt '.
				$cat_field.
				',COUNT(P.post_id) AS nb_post '.
				'FROM '.$this->prefix.'post P LEFT JOIN '.$this->prefix.'category C '.
				'ON P.cat_id = C.cat_id '.
				"WHERE P.blog_id = '".$this->con->escape($this->id)."' ".
				$catReq;
		
		if (!$this->core->auth->check('contentadmin',$this->id)) {
			$strReq .= 'AND ((post_status = 1 ';
			
			if ($this->without_password) {
				$strReq .= 'AND post_password IS NULL ';
			}
			$strReq .= ') ';
			
			if ($this->core->auth->userID()) {
				$strReq .= "OR P.user_id = '".$this->con->escape($this->core->auth->userID())."')";
			} else {
				$strReq .= ') ';
			}
		}
		
		if (!empty($params['post_type'])) {
			$strReq .= "AND post_type ".$this->con->in($params['post_type'])." ";
		} else {
			$strReq .= "AND post_type = 'post' ";
		}
		
		if (!empty($params['year'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%Y')." = '".sprintf('%04d',$params['year'])."' ";
		}
		
		if (!empty($params['month'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%m')." = '".sprintf('%02d',$params['month'])."' ";
		}
		
		if (!empty($params['day'])) {
			$strReq .= 'AND '.$this->con->dateFormat('post_dt','%d')." = '".sprintf('%02d',$params['day'])."' ";
		}
		
		# Get next or previous date
		if (!empty($params['next']) || !empty($params['previous']))
		{
			if (!empty($params['next'])) {
				$pdir = ' > ';
				$params['order'] = 'asc';
				$dt = $params['next'];
			} else {
				$pdir = ' < ';
				$params['order'] = 'desc';
				$dt = $params['previous'];
			}
			
			$dt = date('YmdHis',strtotime($dt));
			
			$strReq .= 'AND '.$this->con->dateFormat('post_dt',$dt_fc).$pdir."'".$dt."' ";
			$limit = $this->con->limit(1);
		}
		
		$strReq .= 'GROUP BY dt '.$cat_field;
		
		$order = 'desc';
		if (!empty($params['order']) && preg_match('/^(desc|asc)$/i',$params['order'])) {
			$order = $params['order'];
		}
		
		$strReq .=
		'ORDER BY dt '.$order.' '.
		$limit;
		
		$rs = $this->con->select($strReq);
		$rs->extend('rsExtDates');
		return $rs;
	}
	
	/**
	Creates a new entry. Takes a cursor as input and returns the new entry
	ID.
	
	@param	cur		<b>cursor</b>		Post cursor
	@return	<b>integer</b>		New post ID
	*/
	public function addPost($cur)
	{
		if (!$this->core->auth->check('usage,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to create an entry'));
		}
		
		$this->con->writeLock($this->prefix.'post');
		try
		{
			# Get ID
			$rs = $this->con->select(
				'SELECT MAX(post_id) '.
				'FROM '.$this->prefix.'post ' 
				);
			
			$cur->post_id = (integer) $rs->f(0) + 1;
			$cur->blog_id = (string) $this->id;
			$cur->post_creadt = date('Y-m-d H:i:s');
			$cur->post_upddt = date('Y-m-d H:i:s');
			$cur->post_tz = $this->core->auth->getInfo('user_tz');
			
			# Post excerpt and content
			$this->getPostContent($cur,$cur->post_id);
			
			$this->getPostCursor($cur);
			
			$cur->post_url = $this->getPostURL($cur->post_url,$cur->post_dt,$cur->post_title,$cur->post_id);
			
			if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
				$cur->post_status = -2;
			}
			
			# --BEHAVIOR-- coreBeforePostCreate
			$this->core->callBehavior('coreBeforePostCreate',$this,$cur);
			
			$cur->insert();
			$this->con->unlock();
		}
		catch (Exception $e)
		{
			$this->con->unlock();
			throw $e;
		}
		
		# --BEHAVIOR-- coreAfterPostCreate
		$this->core->callBehavior('coreAfterPostCreate',$this,$cur);
		
		$this->triggerBlog();
		
		return $cur->post_id;
	}
	
	/**
	Updates an existing post.
	
	@param	id		<b>integer</b>		Post ID
	@param	cur		<b>cursor</b>		Post cursor
	*/
	public function updPost($id,$cur)
	{
		if (!$this->core->auth->check('usage,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to update entries'));
		}
		
		$id = (integer) $id;
		
		if (empty($id)) {
			throw new Exception(__('No such entry ID'));
		}
		
		# Post excerpt and content
		$this->getPostContent($cur,$id);
		
		$this->getPostCursor($cur);
		
		if ($cur->post_url !== null) {
			$cur->post_url = $this->getPostURL($cur->post_url,$cur->post_dt,$cur->post_title,$id);
		}
		
		if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
			$cur->unsetField('post_status');
		}
		
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		#If user is only "usage", we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq = 'SELECT post_id '.
					'FROM '.$this->prefix.'post '.
					'WHERE post_id = '.$id.' '.
					"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
			
			$rs = $this->con->select($strReq);
			
			if ($rs->isEmpty()) {
				throw new Exception(__('You are not allowed to edit this entry'));
			}
		}
		
		# --BEHAVIOR-- coreBeforePostUpdate
		$this->core->callBehavior('coreBeforePostUpdate',$this,$cur);
		
		$cur->update('WHERE post_id = '.$id.' ');
		
		# --BEHAVIOR-- coreAfterPostUpdate
		$this->core->callBehavior('coreAfterPostUpdate',$this,$cur);
		
		$this->triggerBlog();
	}
	
	/**
	Updates post status.
	
	@param	id		<b>integer</b>		Post ID
	@param	status	<b>integer</b>		Post status
	*/
	public function updPostStatus($id,$status)
	{
		$this->updPostsStatus($id,$status);
	}
	
	/**
	Updates posts status.
	
	@param	ids		<b>mixed</b>		Post(s) ID(s)
	@param	status	<b>integer</b>		Post status
	*/
	public function updPostsStatus($ids,$status)
	{
		if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to change this entry status'));
		}
		
		$posts_ids = dcUtils::cleanIds($ids);
		$status = (integer) $status;
		
		$strReq = "WHERE blog_id = '".$this->con->escape($this->id)."' ".
				"AND post_id ".$this->con->in($posts_ids);
		
		#If user can only publish, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= "AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->post_status = $status;
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update($strReq);
		$this->triggerBlog();
	}
	
	/**
	Updates post selection.
	
	@param	id		<b>integer</b>		Post ID
	@param	selected	<b>integer</b>		Is selected post
	*/
	public function updPostSelected($id,$selected)
	{
		$this->updPostsSelected($id,$selected);
	}
	
	/**
	Updates posts selection.
	
	@param	ids		<b>mixed</b>		Post(s) ID(s)
	@param	selected	<b>integer</b>		Is selected post(s)
	*/
	public function updPostsSelected($ids,$selected)
	{
		if (!$this->core->auth->check('usage,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to change this entry category'));
		}
		
		$posts_ids = dcUtils::cleanIds($ids);
		$selected = (boolean) $selected;
		
		$strReq = "WHERE blog_id = '".$this->con->escape($this->id)."' ".
				"AND post_id ".$this->con->in($posts_ids);
		
		# If user is only usage, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= "AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->post_selected = (integer) $selected;
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update($strReq);
		$this->triggerBlog();
	}
	
	/**
	Updates post category. <var>$cat_id</var> can be null.
	
	@param	id		<b>integer</b>		Post ID
	@param	cat_id	<b>integer</b>		Category ID
	*/
	public function updPostCategory($id,$cat_id)
	{
		$this->updPostsCategory($id,$cat_id);
	}
	
	/**
	Updates posts category. <var>$cat_id</var> can be null.
	
	@param	ids		<b>mixed</b>		Post(s) ID(s)
	@param	cat_id	<b>integer</b>		Category ID
	*/
	public function updPostsCategory($ids,$cat_id)
	{
		if (!$this->core->auth->check('usage,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to change this entry category'));
		}
		
		$posts_ids = dcUtils::cleanIds($ids);
		$cat_id = (integer) $cat_id;
		
		$strReq = "WHERE blog_id = '".$this->con->escape($this->id)."' ".
				"AND post_id ".$this->con->in($posts_ids);
		
		# If user is only usage, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= "AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->cat_id = ($cat_id ? $cat_id : null);
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update($strReq);
		$this->triggerBlog();
	}
	
	/**
	Updates posts category. <var>$new_cat_id</var> can be null.
	
	@param	old_cat_id	<b>integer</b>		Old category ID
	@param	new_cat_id	<b>integer</b>		New category ID
	*/
	public function changePostsCategory($old_cat_id,$new_cat_id)
	{
		if (!$this->core->auth->check('contentadmin,categories',$this->id)) {
			throw new Exception(__('You are not allowed to change entries category'));
		}
		
		$old_cat_id = (integer) $old_cat_id;
		$new_cat_id = (integer) $new_cat_id;
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->cat_id = ($new_cat_id ? $new_cat_id : null);
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update(
			'WHERE cat_id = '.$old_cat_id.' '.
			"AND blog_id = '".$this->con->escape($this->id)."' "
		);
		$this->triggerBlog();
	}
	
	/**
	Deletes a post.
	
	@param	id		<b>integer</b>		Post ID
	*/
	public function delPost($id)
	{
		$this->delPosts($id);
	}
	
	/**
	Deletes multiple posts.
	
	@param	ids		<b>mixed</b>		Post(s) ID(s)
	*/
	public function delPosts($ids)
	{
		if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete entries'));
		}
		
		$posts_ids = dcUtils::cleanIds($ids);
		
		if (empty($posts_ids)) {
			throw new Exception(__('No such entry ID'));
		}
		
		$strReq = 'DELETE FROM '.$this->prefix.'post '.
				"WHERE blog_id = '".$this->con->escape($this->id)."' ".
				"AND post_id ".$this->con->in($posts_ids);
		
		#If user can only delete, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= "AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$this->con->execute($strReq);
		$this->triggerBlog();
	}
	
	/**
	Publishes all entries flaged as "scheduled".
	*/
	public function publishScheduledEntries()
	{
		$strReq = 'SELECT post_id, post_dt, post_tz '.
				'FROM '.$this->prefix.'post '.
				'WHERE post_status = -1 '.
				"AND blog_id = '".$this->con->escape($this->id)."' ";
		
		$rs = $this->con->select($strReq);
		
		$now = dt::toUTC(time());
		$to_change = new ArrayObject();

		if ($rs->isEmpty()) {
			return;
		}
		
		while ($rs->fetch())
		{
			# Now timestamp with post timezone
			$now_tz = $now + dt::getTimeOffset($rs->post_tz,$now);
			
			# Post timestamp
			$post_ts = strtotime($rs->post_dt);
			
			# If now_tz >= post_ts, we publish the entry
			if ($now_tz >= $post_ts) {
				$to_change[] = (integer) $rs->post_id;
			}
		}
		if (count($to_change))
		{
			# --BEHAVIOR-- coreBeforeScheduledEntriesPublish
			$this->core->callBehavior('coreBeforeScheduledEntriesPublish',$this,$to_change);

			$strReq =
			'UPDATE '.$this->prefix.'post SET '.
			'post_status = 1 '.
			"WHERE blog_id = '".$this->con->escape($this->id)."' ".
			'AND post_id '.$this->con->in((array)$to_change).' ';
			$this->con->execute($strReq);
			$this->triggerBlog();

			# --BEHAVIOR-- coreAfterScheduledEntriesPublish
			$this->core->callBehavior('coreAfterScheduledEntriesPublish',$this,$to_change);
		}
		
	}
	
	/**
	Retrieves all users having posts on current blog.
	
	@param	post_type		<b>string</b>		post_type filter (post)
	@return	record
	*/
	public function getPostsUsers($post_type='post')
	{
		$strReq = 'SELECT P.user_id, user_name, user_firstname, '.
				'user_displayname, user_email '.
				'FROM '.$this->prefix.'post P, '.$this->prefix.'user U '.
				'WHERE P.user_id = U.user_id '.
				"AND blog_id = '".$this->con->escape($this->id)."' ";
		
		if ($post_type) {
			$strReq .= "AND post_type = '".$this->con->escape($post_type)."' ";
		}
		
		$strReq .= 'GROUP BY P.user_id, user_name, user_firstname, user_displayname, user_email ';
		
		return $this->con->select($strReq);
	}
	
	private function getPostsCategoryFilter($arr,$field='cat_id')
	{
		$field = $field == 'cat_id' ? 'cat_id' : 'cat_url';
		
		$sub = array();
		$not = array();
		$queries = array();
		
		foreach ($arr as $v)
		{
			$v = trim($v);
			$args = preg_split('/\s*[?]\s*/',$v,-1,PREG_SPLIT_NO_EMPTY);
			$id = array_shift($args);
			$args = array_flip($args);
			
			if (isset($args['not'])) { $not[$id] = 1; }
			if (isset($args['sub'])) { $sub[$id] = 1; }
			if ($field == 'cat_id') {
				if (preg_match('/^null$/i',$id)) {
					$queries[$id] = 'P.cat_id IS NULL';
				}
				else {
					$queries[$id] = 'P.cat_id = '.(integer) $id;
				}
			} else {
				$queries[$id] = "C.cat_url = '".$this->con->escape($id)."' ";
			}
		}
		
		if (!empty($sub)) {
			$rs = $this->con->select(
				'SELECT cat_id, cat_url, cat_lft, cat_rgt FROM '.$this->prefix.'category '.
				"WHERE blog_id = '".$this->con->escape($this->id)."' ".
				'AND '.$field.' '.$this->con->in(array_keys($sub))
			);
			
			while ($rs->fetch()) {
				$queries[$rs->f($field)] = '(C.cat_lft BETWEEN '.$rs->cat_lft.' AND '.$rs->cat_rgt.')';
			}
		}
		
		# Create queries
		$sql = array(
			0 => array(), # wanted categories
			1 => array()  # excluded categories
		);
		
		foreach ($queries as $id => $q) {
			$sql[(integer) isset($not[$id])][] = $q;
		}
		
		$sql[0] = implode(' OR ',$sql[0]);
		$sql[1] = implode(' OR ',$sql[1]);
		
		if ($sql[0]) {
			$sql[0] = '('.$sql[0].')';
		} else {
			unset($sql[0]);
		}
		
		if ($sql[1]) {
			$sql[1] = '(P.cat_id IS NULL OR NOT('.$sql[1].'))';
		} else {
			unset($sql[1]);
		}
		
		return implode(' AND ',$sql);
	}
	
	private function getPostCursor($cur,$post_id=null)
	{
		if ($cur->post_title == '') {
			throw new Exception(__('No entry title'));
		}
		
		if ($cur->post_content == '') {
			throw new Exception(__('No entry content'));
		}
		
		if ($cur->post_password === '') {
			$cur->post_password = null;
		}
		
		if ($cur->post_dt == '') {
			$offset = dt::getTimeOffset($this->core->auth->getInfo('user_tz'));
			$now = time() + $offset;
			$cur->post_dt = date('Y-m-d H:i:00',$now);
		}
		
		$post_id = is_int($post_id) ? $post_id : $cur->post_id;
		
		if ($cur->post_content_xhtml == '') {
			throw new Exception(__('No entry content'));
		}
		
		# Words list
		if ($cur->post_title !== null && $cur->post_excerpt_xhtml !== null
		&& $cur->post_content_xhtml !== null)
		{
			$words =
			$cur->post_title.' '.
			$cur->post_excerpt_xhtml.' '.
			$cur->post_content_xhtml;
			
			$cur->post_words = implode(' ',text::splitWords($words));
		}
	}
	
	private function getPostContent($cur,$post_id)
	{
		$post_excerpt = $cur->post_excerpt;
		$post_excerpt_xhtml = $cur->post_excerpt_xhtml;
		$post_content = $cur->post_content;
		$post_content_xhtml = $cur->post_content_xhtml;
		
		$this->setPostContent(
			$post_id,$cur->post_format,$cur->post_lang,
			$post_excerpt,$post_excerpt_xhtml,
			$post_content,$post_content_xhtml
		);
		
		$cur->post_excerpt = $post_excerpt;
		$cur->post_excerpt_xhtml = $post_excerpt_xhtml;
		$cur->post_content = $post_content;
		$cur->post_content_xhtml = $post_content_xhtml;
	}
	
	/**
	Creates post HTML content, taking format and lang into account.
	
	@param		post_id		<b>integer</b>		Post ID
	@param		format		<b>string</b>		Post format
	@param		lang			<b>string</b>		Post lang
	@param		excerpt		<b>string</b>		Post excerpt
	@param[out]	excerpt_xhtml	<b>string</b>		Post excerpt HTML
	@param		content		<b>string</b>		Post content
	@param[out]	content_xhtml	<b>string</b>		Post content HTML
	*/
	public function setPostContent($post_id,$format,$lang,&$excerpt,&$excerpt_xhtml,&$content,&$content_xhtml)
	{
		if ($format == 'wiki')
		{
			$this->core->initWikiPost();
			$this->core->wiki2xhtml->setOpt('note_prefix','pnote-'.$post_id);
			switch ($this->settings->system->note_title_tag) {
				case 1:
					$tag = 'h3';
					break;
				case 2:
					$tag = 'p';
					break;
				default:
					$tag = 'h4';
					break;
			}
			$this->core->wiki2xhtml->setOpt('note_str','<div class="footnotes"><'.$tag.' class="footnotes-title">'.
				__('Notes').'</'.$tag.'>%s</div>');
			$this->core->wiki2xhtml->setOpt('note_str_single','<div class="footnotes"><'.$tag.' class="footnotes-title">'.
				__('Note').'</'.$tag.'>%s</div>');
			if (strpos($lang,'fr') === 0) {
				$this->core->wiki2xhtml->setOpt('active_fr_syntax',1);
			}
		}
		
		if ($excerpt) {
			$excerpt_xhtml = $this->core->callFormater($format,$excerpt);
			$excerpt_xhtml = $this->core->HTMLfilter($excerpt_xhtml);
		} else {
			$excerpt_xhtml = '';
		}
		
		if ($content) {
			$content_xhtml = $this->core->callFormater($format,$content);
			$content_xhtml = $this->core->HTMLfilter($content_xhtml);
		} else {
			$content_xhtml = '';
		}
		
		# --BEHAVIOR-- coreAfterPostContentFormat
		$this->core->callBehavior('coreAfterPostContentFormat',array(
			'excerpt' => &$excerpt,
			'content' => &$content,
			'excerpt_xhtml' => &$excerpt_xhtml,
			'content_xhtml' => &$content_xhtml
		));
	}
	
	/**
	Returns URL for a post according to blog setting <var>post_url_format</var>.
	It will try to guess URL and append some figures if needed.
	
	@param	url			<b>string</b>		Origin URL, could be empty
	@param	post_dt		<b>string</b>		Post date (in YYYY-MM-DD HH:mm:ss)
	@param	post_title	<b>string</b>		Post title
	@param	post_id		<b>integer</b>		Post ID
	@return	<b>string</b>	result URL
	*/
	public function getPostURL($url,$post_dt,$post_title,$post_id)
	{
		$url = trim($url);
		
		$url_patterns = array(
		'{y}' => date('Y',strtotime($post_dt)),
		'{m}' => date('m',strtotime($post_dt)),
		'{d}' => date('d',strtotime($post_dt)),
		'{t}' => text::tidyURL($post_title),
		'{id}' => (integer) $post_id
		);
		
		# If URL is empty, we create a new one
		if ($url == '')
		{
			# Transform with format
			$url = str_replace(
				array_keys($url_patterns),
				array_values($url_patterns),
				$this->settings->system->post_url_format
			);
		}
		else
		{
			$url = text::tidyURL($url);
		}
		
		# Let's check if URL is taken...
		$strReq = 'SELECT post_url FROM '.$this->prefix.'post '.
				"WHERE post_url = '".$this->con->escape($url)."' ".
				'AND post_id <> '.(integer) $post_id. ' '.
				"AND blog_id = '".$this->con->escape($this->id)."' ".
				'ORDER BY post_url DESC';
		
		$rs = $this->con->select($strReq);
		
		if (!$rs->isEmpty())
		{
			if ($this->con->driver() == 'mysql' || $this->con->driver() == 'mysqli') {
				$clause = "REGEXP '^".$this->con->escape($url)."[0-9]+$'";
			} elseif ($this->con->driver() == 'pgsql') {
				$clause = "~ '^".$this->con->escape($url)."[0-9]+$'";
			} else {
				$clause = "LIKE '".$this->con->escape($url)."%'";
			}
			$strReq = 'SELECT post_url FROM '.$this->prefix.'post '.
					"WHERE post_url ".$clause.' '.
					'AND post_id <> '.(integer) $post_id.' '.
					"AND blog_id = '".$this->con->escape($this->id)."' ".
					'ORDER BY post_url DESC ';
			
			$rs = $this->con->select($strReq);
			$a = array();
			while ($rs->fetch()) {
				$a[] = $rs->post_url;
			}
			
			natsort($a);
			$t_url = end($a);
			
			if (preg_match('/(.*?)([0-9]+)$/',$t_url,$m)) {
				$i = (integer) $m[2];
				$url = $m[1];
			} else {
				$i = 1;
			}
			
			return $url.($i+1);
		}
		
		# URL is empty?
		if ($url == '') {
			throw new Exception(__('Empty entry URL'));
		}
		
		return $url;
	}
	//@}
	
	/// @name Comments management methods
	//@{
	/**
	Retrieves comments. <b>$params</b> is an array taking the following
	optionnal parameters:
	
	- no_content: Don't retrieve comment content
	- post_type: Get only entries with given type (default no type, array for many types) 
	- post_id: (integer) Get comments belonging to given post_id
	- cat_id: (integer or array) Get comments belonging to entries of given category ID
	- comment_id: (integer) Get comment with given ID
	- comment_site: (string) Get comments with given comment_site
	- comment_status: (integer) Get comments with given comment_status
	- comment_trackback: (integer) Get only comments (0) or trackbacks (1)
	- comment_ip: (string) Get comments with given IP address
	- post_url: Get entry with given post_url field
	- user_id: (integer) Get entries belonging to given user ID
	- q_author: Search comments by author
	- sql: Append SQL string at the end of the query
	- from: Append SQL string after "FROM" statement in query
	- order: Order of results (default "ORDER BY comment_dt DES")
	- limit: Limit parameter
	- sql_only : return the sql request instead of results. Only ids are selected
	
	@param	params		<b>array</b>		Parameters
	@param	count_only	<b>boolean</b>		Only counts results
	@return	<b>record</b>	A record with some more capabilities
	*/
	public function getComments($params=array(),$count_only=false)
	{
		if ($count_only)
		{
			$strReq = 'SELECT count(comment_id) ';
		}
		elseif (!empty($params['sql_only'])) 
		{
			$strReq = 'SELECT P.post_id ';
		}
		else
		{
			if (!empty($params['no_content'])) {
				$content_req = '';
			} else {
				$content_req = 'comment_content, ';
			}
			
			if (!empty($params['columns']) && is_array($params['columns'])) {
				$content_req .= implode(', ',$params['columns']).', ';
			}
			
			$strReq =
			'SELECT C.comment_id, comment_dt, comment_tz, comment_upddt, '.
			'comment_author, comment_email, comment_site, '.
			$content_req.' comment_trackback, comment_status, '.
			'comment_spam_status, comment_spam_filter, comment_ip, '.
			'P.post_title, P.post_url, P.post_id, P.post_password, P.post_type, '.
			'P.post_dt, P.user_id, U.user_email, U.user_url ';
		}
		
		$strReq .=
		'FROM '.$this->prefix.'comment C '.
		'INNER JOIN '.$this->prefix.'post P ON C.post_id = P.post_id '.
		'INNER JOIN '.$this->prefix.'user U ON P.user_id = U.user_id ';
		
		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}
		
		$strReq .=
		"WHERE P.blog_id = '".$this->con->escape($this->id)."' ";
		
		if (!$this->core->auth->check('contentadmin',$this->id)) {
			$strReq .= 'AND ((comment_status = 1 AND P.post_status = 1 ';
			
			if ($this->without_password) {
				$strReq .= 'AND post_password IS NULL ';
			}
			$strReq .= ') ';
			
			if ($this->core->auth->userID()) {
				$strReq .= "OR P.user_id = '".$this->con->escape($this->core->auth->userID())."')";
			} else {
				$strReq .= ') ';
			}
		}
		
		if (!empty($params['post_type']))
		{
			$strReq .= 'AND post_type '.$this->con->in($params['post_type']);
		}
		
		if (isset($params['post_id']) && $params['post_id'] !== '') {
			$strReq .= 'AND P.post_id = '.(integer) $params['post_id'].' ';
		}
		
		if (isset($params['cat_id']) && $params['cat_id'] !== '') {
			$strReq .= 'AND P.cat_id = '.(integer) $params['cat_id'].' ';
		}
		
		if (isset($params['comment_id']) && $params['comment_id'] !== '') {
			$strReq .= 'AND comment_id = '.(integer) $params['comment_id'].' ';
		}
		
		if (isset($params['comment_site'])) {
			$comment_site = $this->con->escape(str_replace('*','%',$params['comment_site']));
			$strReq .= "AND comment_site LIKE '".$comment_site."' ";
		}
		
		if (isset($params['comment_status'])) {
			$strReq .= 'AND comment_status = '.(integer) $params['comment_status'].' ';
		}
		
		if (!empty($params['comment_status_not']))
		{
			$strReq .= 'AND comment_status <> '.(integer) $params['comment_status_not'].' ';
		}
		
		if (isset($params['comment_trackback'])) {
			$strReq .= 'AND comment_trackback = '.(integer) (boolean) $params['comment_trackback'].' ';
		}
		
		if (isset($params['comment_ip'])) {
			$comment_ip = $this->con->escape(str_replace('*','%',$params['comment_ip']));
			$strReq .= "AND comment_ip LIKE '".$comment_ip."' ";
		}
		
		if (isset($params['q_author'])) {
			$q_author = $this->con->escape(str_replace('*','%',strtolower($params['q_author'])));
			$strReq .= "AND LOWER(comment_author) LIKE '".$q_author."' ";
		}
		
		if (!empty($params['search']))
		{
			$words = text::splitWords($params['search']);
			
			if (!empty($words))
			{
				# --BEHAVIOR coreCommentSearch
				if ($this->core->hasBehavior('coreCommentSearch')) {
					$this->core->callBehavior('coreCommentSearch',$this->core,array(&$words,&$strReq,&$params));
				}
				
				if ($words)
				{
					foreach ($words as $i => $w) {
						$words[$i] = "comment_words LIKE '%".$this->con->escape($w)."%'";
					}
					$strReq .= 'AND '.implode(' AND ',$words).' ';
				}
			}
		}
		
		if (!empty($params['sql'])) {
			$strReq .= $params['sql'].' ';
		}
		
		if (!$count_only)
		{
			if (!empty($params['order'])) {
				$strReq .= 'ORDER BY '.$this->con->escape($params['order']).' ';
			} else {
				$strReq .= 'ORDER BY comment_dt DESC ';
			}
		}
		
		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $this->con->limit($params['limit']);
		}

		if (!empty($params['sql_only'])) {
			return $strReq;
		}
		
		$rs = $this->con->select($strReq);
		$rs->core = $this->core;
		$rs->extend('rsExtComment');
		
		# --BEHAVIOR-- coreBlogGetComments
		$this->core->callBehavior('coreBlogGetComments',$rs);
		
		return $rs;
	}
	
	/**
	Creates a new comment. Takes a cursor as input and returns the new comment
	ID.
	
	@param	cur		<b>cursor</b>		Comment cursor
	@return	<b>integer</b>		New comment ID
	*/
	public function addComment($cur)
	{
		$this->con->writeLock($this->prefix.'comment');
		try
		{
			# Get ID
			$rs = $this->con->select(
				'SELECT MAX(comment_id) '.
				'FROM '.$this->prefix.'comment ' 
			);
			
			$cur->comment_id = (integer) $rs->f(0) + 1;
			$cur->comment_upddt = date('Y-m-d H:i:s');
			
			$offset = dt::getTimeOffset($this->settings->system->blog_timezone);
			$cur->comment_dt = date('Y-m-d H:i:s',time() + $offset);
			$cur->comment_tz = $this->settings->system->blog_timezone;
			
			$this->getCommentCursor($cur);
			
			if ($cur->comment_ip === null) {
				$cur->comment_ip = http::realIP();
			}
			
			# --BEHAVIOR-- coreBeforeCommentCreate
			$this->core->callBehavior('coreBeforeCommentCreate',$this,$cur);
			
			$cur->insert();
			$this->con->unlock();
		}
		catch (Exception $e)
		{
			$this->con->unlock();
			throw $e;
		}
		
		# --BEHAVIOR-- coreAfterCommentCreate
		$this->core->callBehavior('coreAfterCommentCreate',$this,$cur);
		
		$this->triggerComment($cur->comment_id);
		if ($cur->comment_status != -2) {
			$this->triggerBlog();
		}	
		return $cur->comment_id;
	}
	
	/**
	Updates an existing comment.
	
	@param	id		<b>integer</b>		Comment ID
	@param	cur		<b>cursor</b>		Comment cursor
	*/
	public function updComment($id,$cur)
	{
		if (!$this->core->auth->check('usage,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to update comments'));
		}
		
		$id = (integer) $id;
		
		if (empty($id)) {
			throw new Exception(__('No such comment ID'));
		}
		
		$rs = $this->getComments(array('comment_id' => $id));
		
		if ($rs->isEmpty()) {
			throw new Exception(__('No such comment ID'));
		}
		
		#If user is only usage, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			if ($rs->user_id != $this->core->auth->userID()) {
				throw new Exception(__('You are not allowed to update this comment'));
			}
		}
		
		$this->getCommentCursor($cur);
		
		$cur->comment_upddt = date('Y-m-d H:i:s');
		
		if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
			$cur->unsetField('comment_status');
		}
		
		# --BEHAVIOR-- coreBeforeCommentUpdate
		$this->core->callBehavior('coreBeforeCommentUpdate',$this,$cur,$rs);
		
		$cur->update('WHERE comment_id = '.$id.' ');
		
		# --BEHAVIOR-- coreAfterCommentUpdate
		$this->core->callBehavior('coreAfterCommentUpdate',$this,$cur,$rs);
		
		$this->triggerComment($id);
		$this->triggerBlog();
	}
	
	/**
	Updates comment status.
	
	@param	id		<b>integer</b>		Comment ID
	@param	status	<b>integer</b>		Comment status
	*/
	public function updCommentStatus($id,$status)
	{
		$this->updCommentsStatus($id,$status);
	}
	
	/**
	Updates comments status.
	
	@param	ids		<b>mixed</b>		Comment(s) ID(s)
	@param	status	<b>integer</b>		Comment status
	*/
	public function updCommentsStatus($ids,$status)
	{
		if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
			throw new Exception(__("You are not allowed to change this comment's status"));
		}
		
		$co_ids = dcUtils::cleanIds($ids);
		$status = (integer) $status;
		
		$strReq = 
			'UPDATE '.$this->prefix.'comment tc ';
		
		# mySQL uses "JOIN" synthax
		if ($this->con->driver() == 'mysql' || $this->con->driver() == 'mysqli') {
			$strReq .= 
				'JOIN '.$this->prefix.'post tp ON tc.post_id = tp.post_id ';
		}
		
		$strReq .= 
			'SET comment_status = '.$status.' ';
		
		# pgSQL uses "FROM" synthax
		if ($this->con->driver() != 'mysql' && $this->con->driver() != 'mysqli') {
			$strReq .= 
				'FROM '.$this->prefix.'post tp ';
		}
		
		$strReq .=
			"WHERE blog_id = '".$this->con->escape($this->id)."' ".
			'AND comment_id'.$this->con->in($co_ids);
		
		# add pgSQL "WHERE" clause
		if ($this->con->driver() != 'mysql' && $this->con->driver() != 'mysqli') {
			$strReq .= 
				'AND tc.post_id = tp.post_id ';
		}
		
		#If user is only usage, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= 
				"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$this->con->execute($strReq);
		$this->triggerComments($co_ids);
		$this->triggerBlog();
	}
	
	/**
	Delete a comment
	
	@param	id		<b>integer</b>		Comment ID
	*/
	public function delComment($id)
	{
		$this->delComments($id);
	}
	
	/**
	Delete comments
	
	@param	ids		<b>mixed</b>		Comment(s) ID(s)
	*/
	public function delComments($ids)
	{
		if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete comments'));
		}
		
		$co_ids = dcUtils::cleanIds($ids);
		
		if (empty($co_ids)) {
			throw new Exception(__('No such comment ID'));
		}
		
		# Retrieve posts affected by comments edition
		$affected_posts = array();
		$strReq =
			'SELECT post_id '.
			'FROM '.$this->prefix.'comment '.
			'WHERE comment_id'.$this->con->in($co_ids).
			'GROUP BY post_id';
		
		$rs = $this->con->select($strReq);
		
		while ($rs->fetch()) {
			$affected_posts[] = (integer) $rs->post_id;
		}
		
		# mySQL uses "INNER JOIN" synthax
		if ($this->con->driver() == 'mysql' || $this->con->driver() == 'mysqli') {
			$strReq = 
				'DELETE FROM tc '.
				'USING '.$this->prefix.'comment tc '.
				'INNER JOIN '.$this->prefix.'post tp ';
		}
		# pgSQL uses nothing special
		else {
			$strReq = 
				'DELETE FROM '.$this->prefix.'comment tc '.
				'USING '.$this->prefix.'post tp ';
		}
		
		$strReq .= 
			'WHERE tc.post_id = tp.post_id '.
			"AND tp.blog_id = '".$this->con->escape($this->id)."' ".
			'AND comment_id'.$this->con->in($co_ids);
		
		#If user can only delete, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= 
				"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$this->con->execute($strReq);
		$this->triggerComments($co_ids, true, $affected_posts);
		$this->triggerBlog();
	}

	public function delJunkComments()
	{
		if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete comments'));
		}
		
		# mySQL uses "INNER JOIN" synthax
		if ($this->con->driver() == 'mysql' || $this->con->driver() == 'mysqli') {
			$strReq = 
				'DELETE FROM tc '.
				'USING '.$this->prefix.'comment tc '.
				'INNER JOIN '.$this->prefix.'post tp ';
		}
		# pgSQL uses nothing special
		else {
			$strReq = 
				'DELETE FROM '.$this->prefix.'comment tc '.
				'USING '.$this->prefix.'post tp ';
		}
		
		$strReq .= 
			'WHERE tc.post_id = tp.post_id '.
			"AND tp.blog_id = '".$this->con->escape($this->id)."' ".
			'AND comment_status = -2';
		
		#If user can only delete, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq .= 
				"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		
		$this->con->execute($strReq);
		$this->triggerBlog();
	}
	
	private function getCommentCursor($cur)
	{
		if ($cur->comment_content !== null && $cur->comment_content == '') {
			throw new Exception(__('You must provide a comment'));
		}
		
		if ($cur->comment_author !== null && $cur->comment_author == '') {
			throw new Exception(__('You must provide an author name'));
		}
		
		if ($cur->comment_email != '' && !text::isEmail($cur->comment_email)) {
			throw new Exception(__('Email address is not valid.'));
		}
		
		if ($cur->comment_site !== null && $cur->comment_site != '') {
			if (!preg_match('|^http(s?)://|i',$cur->comment_site, $matches)) {
				$cur->comment_site = 'http://'.$cur->comment_site;
			}else{
				$cur->comment_site = strtolower($matches[0]).substr($cur->comment_site, strlen($matches[0]));
			}
		}
		
		if ($cur->comment_status === null) {
			$cur->comment_status = (integer) $this->settings->system->comments_pub;
		}
		
		# Words list
		if ($cur->comment_content !== null)
		{
			$cur->comment_words = implode(' ',text::splitWords($cur->comment_content));
		}
	}
	//@}
}
?>
