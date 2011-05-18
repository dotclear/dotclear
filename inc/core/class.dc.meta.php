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
@brief Dotclear metadata class.

Dotclear metadata class instance is provided by dcCore $meta property.
*/
class dcMeta
{
	private $core;	///< <b>dcCore</b> dcCore instance
	private $con;	///< <b>connection</b>	Database connection object
	private $table;	///< <b>string</b> Media table name
	
	/**
	Object constructor.
	
	@param	core		<b>dcCore</b>		dcCore instance
	*/
	public function __construct($core)
	{
		$this->core =& $core;
		$this->con =& $this->core->con;
		$this->table = $this->core->prefix.'meta';
	}
	
	/**
	Splits up comma-separated values into an array of
	unique, URL-proof metadata values.
	
	@param	str		<b>string</b>		Comma-separated metadata.
	
	@return	<b>Array</b>	The array of sanitized metadata
	*/
	public function splitMetaValues($str)
	{
		$res = array();
		foreach (explode(',',$str) as $i => $tag)
		{
			$tag = trim($tag);
			$tag = self::sanitizeMetaID($tag);
			
			if ($tag != false) {
				$res[$i] = $tag;
			}
		}
		
		return array_unique($res);
	}
	
	/**
	Make a metadata ID URL-proof.
	
	@param	str		<b>string</b>	the metadata ID.
	
	@return	<b>string</b>	The sanitized metadata
	*/
	public static function sanitizeMetaID($str)
	{
		return text::tidyURL($str,false,true);
	}
	
	/**
	Converts serialized metadata (for instance in dc_post post_meta)
	into a meta array.
	
	@param	str		<b>string</b>	the serialized metadata.
	
	@return	<b>Array</b>	the resulting array of post meta
	*/
	public function getMetaArray($str)
	{
		$meta = @unserialize($str);
		
		if (!is_array($meta)) {
			return array();
		}
		
		return $meta;
	}
	
	/**
	Converts serialized metadata (for instance in dc_post post_meta)
	into a comma-separated meta list for a given type.
	
	@param	str		<b>string</b>	the serialized metadata.
	@param	type	<b>string</b>	meta type to retrieve metaIDs from.
	
	@return	<b>string</b>	the comma-separated list of meta
	*/
	public function getMetaStr($str,$type)
	{
		$meta = $this->getMetaArray($str);
		
		if (!isset($meta[$type])) {
			return '';
		}
		
		return implode(', ',$meta[$type]);
	}
	
	/**
	Converts serialized metadata (for instance in dc_post post_meta)
	into a "fetchable" metadata record.
	
	@param	str		<b>string</b>	the serialized metadata.
	@param	type	<b>string</b>	meta type to retrieve metaIDs from.
	
	@return	<b>record</b>	the meta recordset
	*/
	public function getMetaRecordset($str,$type)
	{
		$meta = $this->getMetaArray($str);
		$data = array();
		
		if (isset($meta[$type]))
		{
			foreach ($meta[$type] as $v)
			{
				$data[] = array(
					'meta_id' => $v,
					'meta_type' => $type,
					'meta_id_lower' => mb_strtolower($v),
					'count' => 0,
					'percent' => 0,
					'roundpercent' => 0
				);
			}
		}
		
		return staticRecord::newFromArray($data);
	}
	
	/**
	@deprecated since version 2.2 : $core->meta is always defined
	@see getMetaRecordset
	static version of getMetaRecordset
	*/
	public static function getMetaRecord($core,$str,$type)
	{
		$meta = new self($core);
		return $meta->getMetaRecordset($str,$type);
	}
	
	/**
	Checks whether the current user is allowed to change post meta 
	An exception is thrown if user is not allowed.
	
	@param	post_id	<b>string</b>	the post_id to check.
	*/
	private function checkPermissionsOnPost($post_id)
	{
		$post_id = (integer) $post_id;
		
		if (!$this->core->auth->check('usage,contentadmin',$this->core->blog->id)) {
			throw new Exception(__('You are not allowed to change this entry status'));
		}
		
		#�If user can only publish, we need to check the post's owner
		if (!$this->core->auth->check('contentadmin',$this->core->blog->id))
		{
			$strReq = 'SELECT post_id '.
					'FROM '.$this->core->prefix.'post '.
					'WHERE post_id = '.$post_id.' '.
					"AND user_id = '".$this->con->escape($this->core->auth->userID())."' ";
			
			$rs = $this->con->select($strReq);
			
			if ($rs->isEmpty()) {
				throw new Exception(__('You are not allowed to change this entry status'));
			}
		}
	}
	
	/**
	Updates serialized post_meta information with dc_meta table information.
	
	@param	post_id	<b>string</b>	the post_id to update.
	*/
	private function updatePostMeta($post_id)
	{
		$post_id = (integer) $post_id;
		
		$strReq = 'SELECT meta_id, meta_type '.
				'FROM '.$this->table.' '.
				'WHERE post_id = '.$post_id.' ';
		
		$rs = $this->con->select($strReq);
		
		$meta = array();
		while ($rs->fetch()) {
			$meta[$rs->meta_type][] = $rs->meta_id;
		}
		
		$post_meta = serialize($meta);
		
		$cur = $this->con->openCursor($this->core->prefix.'post');
		$cur->post_meta = $post_meta;
		
		$cur->update('WHERE post_id = '.$post_id);
		$this->core->blog->triggerBlog();
	}
	
	/**
	Retrieves posts corresponding to given meta criteria.
	<b>$params</b> is an array taking the following optional parameters:
	- meta_id : get posts having meta id 
	- meta_type : get posts having meta type
	
	@param	params	<b>array</b>	Parameters
	@param	count_only	<b>boolean</b>		Only counts results
	
	@return	<b>record</b>	the resulting posts record
	*/
	public function getPostsByMeta($params=array(),$count_only=false)
	{
		if (!isset($params['meta_id'])) {
			return null;
		}
		
		$params['from'] = ', '.$this->table.' META ';
		$params['sql'] = 'AND META.post_id = P.post_id ';
		
		$params['sql'] .= "AND META.meta_id = '".$this->con->escape($params['meta_id'])."' ";
		
		if (!empty($params['meta_type'])) {
			$params['sql'] .= "AND META.meta_type = '".$this->con->escape($params['meta_type'])."' ";
			unset($params['meta_type']);
		}
		
		unset($params['meta_id']);
		
		return $this->core->blog->getPosts($params,$count_only);
	}
	
	/**
	Retrieves comments to posts corresponding to given meta criteria.
	<b>$params</b> is an array taking the following optional parameters:
	- meta_id : get comments to posts having meta id 
	- meta_type : get comments to posts having meta type
	
	@param	params	<b>array</b>	Parameters
	@param	count_only	<b>boolean</b>		Only counts results
	
	@return	<b>record</b>	the resulting comments record
	*/
	public function getCommentsByMeta($params=array(),$count_only=false)
	{
		if (!isset($params['meta_id'])) {
			return null;
		}
		
		$params['from'] = ', '.$this->table.' META ';
		$params['sql'] = 'AND META.post_id = P.post_id ';
		$params['sql'] .= "AND META.meta_id = '".$this->con->escape($params['meta_id'])."' ";
		
		if (!empty($params['meta_type'])) {
			$params['sql'] .= "AND META.meta_type = '".$this->con->escape($params['meta_type'])."' ";
			unset($params['meta_type']);
		}
		
		return $this->core->blog->getComments($params,$count_only);
	}
	
	/**
	@deprecated since 2.2. Use getMetadata and computeMetaStats instead.
	Generic-purpose metadata retrieval : gets metadatas according to given
	criteria. Metadata get enriched with stastistics columns (only relevant 
	if limit parameter is not set). Metadata are sorted by post count 
	descending
	
	@param	type	<b>string</b>	if not null, get metas having the given type
	@param	limit	<b>string</b>	if not null, number of max fetched metas
	@param	meta_id	<b>string</b>	if not null, get metas having the given id
	@param	post_id	<b>string</b>	if not null, get metas for the given post id
	
	@return	<b>record</b>	the meta recordset
	*/
	public function getMeta($type=null,$limit=null,$meta_id=null,$post_id=null) {
		$params = array();
		
		if ($type != null)
			$params['meta_type'] = $type;
		if ($limit != null)
			$params['limit'] = $limit;
		if ($meta_id != null)
			$params['meta_id'] = $meta_id;
		if ($meta_id != null)
			$params['post_id'] = $post_id;
		$rs = $this->getMetadata($params, false);
		return $this->computeMetaStats($rs);
	}
	
	/**
	Generic-purpose metadata retrieval : gets metadatas according to given
	criteria. <b>$params</b> is an array taking the following
	optionnal parameters:
	
	- type: get metas having the given type
	- meta_id: if not null, get metas having the given id
	- post_id: get metas for the given post id
	- limit: number of max fetched metas
	- order: results order (default : posts count DESC)
	
	@param	params		<b>array</b>		Parameters
	@param	count_only	<b>boolean</b>		Only counts results
	
	@return	<b>record</b>	the resulting comments record
	*/
	public function getMetadata($params=array(), $count_only=false)
	{
		if ($count_only) {
			$strReq = 'SELECT count(distinct M.meta_id) ';
		} else {
			$strReq = 'SELECT M.meta_id, M.meta_type, COUNT(M.post_id) as count ';
		}
		
		$strReq .=
		'FROM '.$this->table.' M LEFT JOIN '.$this->core->prefix.'post P '.
		'ON M.post_id = P.post_id '.
		"WHERE P.blog_id = '".$this->con->escape($this->core->blog->id)."' ";
		
		if (isset($params['meta_type'])) {
			$strReq .= " AND meta_type = '".$this->con->escape($params['meta_type'])."' ";
		}
		
		if (isset($params['meta_id'])) {
			$strReq .= " AND meta_id = '".$this->con->escape($params['meta_id'])."' ";
		}
		
		if (isset($params['post_id'])) {
			$strReq .= ' AND P.post_id '.$this->con->in($params['post_id']).' ';
		}
		
		if (!$this->core->auth->check('contentadmin',$this->core->blog->id)) {
			$strReq .= 'AND ((post_status = 1 ';
			
			if ($this->core->blog->without_password) {
				$strReq .= 'AND post_password IS NULL ';
			}
			$strReq .= ') ';
			
			if ($this->core->auth->userID()) {
				$strReq .= "OR P.user_id = '".$this->con->escape($this->core->auth->userID())."')";
			} else {
				$strReq .= ') ';
			}
		}
		
		if (!$count_only) {
			if (!isset($params['order'])) {
				$params['order'] = 'count DESC';
			}
			
			$strReq .=
			'GROUP BY meta_id,meta_type,P.blog_id '.
			'ORDER BY '.$params['order'];
			
			if (isset($params['limit'])) {
				$strReq .= $this->con->limit($params['limit']);
			}
		}
		
		$rs = $this->con->select($strReq);
		return $rs;
	}
	
	/**
	Computes statistics from a metadata recordset.
	Each record gets enriched with lowercase name, percent and roundpercent columns
	
	@param	rs	<b>record</b>	recordset to enrich
	
	@return	<b>record</b>	the enriched recordset
	*/
	public function computeMetaStats($rs) {
		$rs_static = $rs->toStatic();
		
		$max = array();
		while ($rs_static->fetch())
		{
			$type = $rs_static->meta_type;
			if (!isset($max[$type])) {
				$max[$type] = $rs_static->count;
			} else {
				if ($rs_static->count > $max[$type]) {
					$max[$type] = $rs_static->count;
				}
			}
		}
		
		while ($rs_static->fetch())
		{
			$rs_static->set('meta_id_lower',mb_strtolower($rs_static->meta_id));
			
			$count = $rs_static->count;
			$percent = ((integer) $rs_static->count) * 100 / $max[$rs_static->meta_type];
			
			$rs_static->set('percent',(integer) round($percent));
			$rs_static->set('roundpercent',round($percent/10)*10);
		}
		
		return $rs_static;
	}
	
	/**
	Adds a metadata to a post.
	
	@param	post_id	<b>integer</b>	the post id
	@param	type	<b>string</b>	meta type
	@param	value	<b>integer</b>	meta value
	*/
	public function setPostMeta($post_id,$type,$value)
	{
		$this->checkPermissionsOnPost($post_id);
		
		$value = trim($value);
		if ($value === false) { return; }
		
		$cur = $this->con->openCursor($this->table);
		
		$cur->post_id = (integer) $post_id;
		$cur->meta_id = (string) $value;
		$cur->meta_type = (string) $type;
		
		$cur->insert();
		$this->updatePostMeta((integer) $post_id);
	}
	
	/**
	Removes metadata from a post.
	
	@param	post_id	<b>integer</b>	the post id
	@param	type	<b>string</b>	meta type (if null, delete all types)
	@param	value	<b>integer</b>	meta value (if null, delete all values)
	*/
	public function delPostMeta($post_id,$type=null,$meta_id=null)
	{
		$post_id = (integer) $post_id;
		
		$this->checkPermissionsOnPost($post_id);
		
		$strReq = 'DELETE FROM '.$this->table.' '.
				'WHERE post_id = '.$post_id;
		
		if ($type !== null) {
			$strReq .= " AND meta_type = '".$this->con->escape($type)."' ";
		}
		
		if ($meta_id !== null) {
			$strReq .= " AND meta_id = '".$this->con->escape($meta_id)."' ";
		}
		
		$this->con->execute($strReq);
		$this->updatePostMeta((integer) $post_id);
	}
	
	/**
	Mass updates metadata for a given post_type.
	
	@param	meta_id		<b>integer</b>	old value
	@param	new_meta	<b>integer</b>	new value
	@param	type	<b>string</b>	meta type (if null, select all types)
	@param	post_type	<b>integer</b>	impacted post_type (if null, select all types)
	@return	<b>boolean</b>	true if at least 1 post has been impacted
	*/
	public function updateMeta($meta_id,$new_meta_id,$type=null,$post_type=null)
	{
		$new_meta_id = self::sanitizeMetaID($new_meta_id);
		
		if ($new_meta_id == $meta_id) {
			return true;
		}
		
		$getReq = 'SELECT M.post_id '.
				'FROM '.$this->table.' M, '.$this->core->prefix.'post P '.
				'WHERE P.post_id = M.post_id '.
				"AND P.blog_id = '".$this->con->escape($this->core->blog->id)."' ".
				"AND meta_id = '%s' ";
		
		if (!$this->core->auth->check('contentadmin',$this->core->blog->id)) {
			$getReq .= "AND P.user_id = '".$this->con->escape($this->core->auth->userID())."' ";
		}
		if ($post_type !== null) {
			$getReq .= "AND P.post_type = '".$this->con->escape($post_type)."' ";
		}
		
		$delReq = 'DELETE FROM '.$this->table.' '.
				'WHERE post_id IN (%s) '.
				"AND meta_id = '%s' ";
		
		$updReq = 'UPDATE '.$this->table.' '.
				"SET meta_id = '%s' ".
				'WHERE post_id IN (%s) '.
				"AND meta_id = '%s' ";
		
		if ($type !== null) {
			$plus = " AND meta_type = '%s' ";
			$getReq .= $plus;
			$delReq .= $plus;
			$updReq .= $plus;
		}
		
		$to_update = $to_remove = array();
		
		$rs = $this->con->select(sprintf($getReq,$this->con->escape($meta_id),
							$this->con->escape($type)));
		
		while ($rs->fetch()) {
			$to_update[] = $rs->post_id;
		}
		
		if (empty($to_update)) {
			return false;
		}
		
		$rs = $this->con->select(sprintf($getReq,$new_meta_id,$type));
		while ($rs->fetch()) {
			if (in_array($rs->post_id,$to_update)) {
				$to_remove[] = $rs->post_id;
				unset($to_update[array_search($rs->post_id,$to_update)]);
			}
		}
		
		# Delete duplicate meta
		if (!empty($to_remove))
		{
			$this->con->execute(sprintf($delReq,implode(',',$to_remove),
							$this->con->escape($meta_id),
							$this->con->escape($type)));
			
			foreach ($to_remove as $post_id) {
				$this->updatePostMeta($post_id);
			}
		}
		
		# Update meta
		if (!empty($to_update))
		{
			$this->con->execute(sprintf($updReq,$this->con->escape($new_meta_id),
							implode(',',$to_update),
							$this->con->escape($meta_id),
							$this->con->escape($type)));
			
			foreach ($to_update as $post_id) {
				$this->updatePostMeta($post_id);
			}
		}
		
		return true;
	}
	
	/**
	Mass delete metadata for a given post_type.
	
	@param	meta_id		<b>integer</b>	meta value
	@param	type	<b>string</b>	meta type (if null, select all types)
	@param	post_type	<b>integer</b>	impacted post_type (if null, select all types)
	@return	<b>Array</b>	the list of impacted post_ids
	*/
	public function delMeta($meta_id,$type=null,$post_type=null)
	{
		$strReq = 'SELECT M.post_id '.
				'FROM '.$this->table.' M, '.$this->core->prefix.'post P '.
				'WHERE P.post_id = M.post_id '.
				"AND P.blog_id = '".$this->con->escape($this->core->blog->id)."' ".
				"AND meta_id = '".$this->con->escape($meta_id)."' ";
		
		if ($type !== null) {
			$strReq .= " AND meta_type = '".$this->con->escape($type)."' ";
		}
		
		if ($post_type !== null) {
			$strReq .= " AND P.post_type = '".$this->con->escape($post_type)."' ";
		}
		
		$rs = $this->con->select($strReq);
		
		if ($rs->isEmpty()) return array();
		
		$ids = array();
		while ($rs->fetch()) {
			$ids[] = $rs->post_id;
		}
		
		$strReq = 'DELETE FROM '.$this->table.' '.
				'WHERE post_id IN ('.implode(',',$ids).') '.
				"AND meta_id = '".$this->con->escape($meta_id)."' ";
		
		if ($type !== null) {
			$strReq .= " AND meta_type = '".$this->con->escape($type)."' ";
		}
		
		$rs = $this->con->execute($strReq);
		
		foreach ($ids as $post_id) {
			$this->updatePostMeta($post_id);
		}
		
		return $ids;
	}
}
?>
