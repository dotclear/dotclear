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
			$this->host = preg_replace('|^([a-z]{3,}://)(.*?)/.*$|','$1$2',$this->url);
			$this->creadt = strtotime($b->blog_creadt);
			$this->upddt = strtotime($b->blog_upddt);
			$this->status = $b->blog_status;
			
			$this->settings = new dcSettings($this->core,$this->id);
			
			$this->themes_path = path::fullFromRoot($this->settings->system->themes_path,DC_ROOT);
			$this->public_path = path::fullFromRoot($this->settings->system->public_path,DC_ROOT);
			
			$this->post_status['-2'] = __('pending');
			$this->post_status['-1'] = __('scheduled');
			$this->post_status['0'] = __('unpublished');
			$this->post_status['1'] = __('published');
						
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
	
	/// @name Entries management methods
	//@{
	/**
	Retrieves entries. <b>$params</b> is an array taking the following
	optionnal parameters:
	
	- no_content: Don't retrieve entry content (excerpt and content)
	- post_type: Get only entries with given type (default "post", array for many types and '' for no type)
	- post_id: (integer) Get entry with given post_id
	- post_url: Get entry with given post_url field
	- user_id: (integer) Get entries belonging to given user ID
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
			$strReq = 'SELECT count(P.post_id) ';
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
			
			$strReq =
			'SELECT P.post_id, P.blog_id, P.user_id, post_dt, '.
			'post_tz, post_creadt, post_upddt, post_format, post_password, '.
			'post_url, post_lang, post_title, '.$content_req.
			'post_type, post_meta, post_status, post_selected, post_position, '.
			'U.user_name, U.user_firstname, U.user_displayname, U.user_email, '.
			'U.user_url ';
		}
		
		$strReq .=
		'FROM '.$this->prefix.'post P '.
		'INNER JOIN '.$this->prefix.'user U ON U.user_id = P.user_id ';
		
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
		
		if (isset($params['post_url']) && $params['post_url'] !== '') {
			$strReq .= "AND post_url = '".$this->con->escape($params['post_url'])."' ";
		}
		
		if (!empty($params['user_id'])) {
			$strReq .= "AND U.user_id = '".$this->con->escape($params['user_id'])."' ";
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
	@param	restrict_to_lang		<b>boolean</b>		Restrict to post with same lang
	@return	record
	*/
	public function getNextPost($post, $dir, $restrict_to_lang=false)
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
		
		if (!empty($params['post_lang'])) {
			$catReq = 'AND P.post_lang = \''. $params['post_lang'].'\' ';
		}
		
		$strReq = 'SELECT DISTINCT('.$this->con->dateFormat('post_dt',$dt_f).') AS dt '.
				',COUNT(P.post_id) AS nb_post '.
				'FROM '.$this->prefix.'post P '.
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
		if (!$this->core->auth->check('publish,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to change this entry status'));
		}
		
		$id = (integer) $id;
		$status = (integer) $status;
		
		#If user can only publish, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq = 'SELECT post_id '.
					'FROM '.$this->prefix.'post '.
					'WHERE post_id = '.$id.' '.
					"AND blog_id = '".$this->con->escape($this->id)."' ".
					"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
			
			$rs = $this->con->select($strReq);
			
			if ($rs->isEmpty()) {
				throw new Exception(__('You are not allowed to change this entry status'));
			}
		}
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->post_status = $status;
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update(
			'WHERE post_id = '.$id.' '.
			"AND blog_id = '".$this->con->escape($this->id)."' "
			);
		$this->triggerBlog();
	}
	
	public function updPostSelected($id,$selected)
	{
		$id = (integer) $id;
		$selected = (boolean) $selected;
		
		# If user is only usage, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq = 'SELECT post_id '.
					'FROM '.$this->prefix.'post '.
					'WHERE post_id = '.$id.' '.
					"AND blog_id = '".$this->con->escape($this->id)."' ".
					"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
			
			$rs = $this->con->select($strReq);
			
			if ($rs->isEmpty()) {
				throw new Exception(__('You are not allowed to mark this entry as selected'));
			}
		}
		
		$cur = $this->con->openCursor($this->prefix.'post');
		
		$cur->post_selected = (integer) $selected;
		$cur->post_upddt = date('Y-m-d H:i:s');
		
		$cur->update(
			'WHERE post_id = '.$id.' '.
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
		if (!$this->core->auth->check('delete,contentadmin',$this->id)) {
			throw new Exception(__('You are not allowed to delete entries'));
		}
		
		$id = (integer) $id;
		
		if (empty($id)) {
			throw new Exception(__('No such entry ID'));
		}
		
		#If user can only delete, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->id))
		{
			$strReq = 'SELECT post_id '.
					'FROM '.$this->prefix.'post '.
					'WHERE post_id = '.$id.' '.
					"AND blog_id = '".$this->con->escape($this->id)."' ".
					"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
			
			$rs = $this->con->select($strReq);
			
			if ($rs->isEmpty()) {
				throw new Exception(__('You are not allowed to delete this entry'));
			}
		}
		
		
		$strReq = 'DELETE FROM '.$this->prefix.'post '.
				'WHERE post_id = '.$id.' '.
				"AND blog_id = '".$this->con->escape($this->id)."' ";
		
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
			if ($this->con->driver() == 'mysql') {
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
}
?>