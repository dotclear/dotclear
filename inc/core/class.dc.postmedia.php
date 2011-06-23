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

class dcPostMedia
{
	protected $core;		///< <b>dcCore</b> dcCore instance
	protected $con;		///< <b>connection</b> Database connection
	protected $table;		///< <b>string</b> Post-Media table name
	
	/**
	Object constructor.
	
	@param	core		<b>dcCore</b>		dcCore instance
	@param	type		<b>string</b>		Media type filter
	*/
	public function __construct($core,$type='')
	{
		$this->core =& $core;
		$this->con =& $core->con;
		$this->table = $this->core->prefix.'post_media';
	}
	
	/**
	Returns media items attached to a blog post. Result is an array containing
	fileItems objects.
	
	@param	post_id	<b>integer</b>		Post ID
	@param	media_id	<b>integer</b>		Optionnal media ID
	@return	<b>array</b> Array of fileItems
	*/
	public function getPostMedia($params=array())
	{
		$strReq =
		'SELECT M.media_file, M.media_id, M.media_path, M.media_title, M.media_meta, M.media_dt, '.
		'M.media_creadt, M.media_upddt, M.media_private, M.user_id, PM.post_id ';
		
		if (!empty($params['columns']) && is_array($params['columns'])) {
			$strReq .= implode(', ',$params['columns']).', ';
		}
		
		$strReq .=
		'FROM '.$this->core->prefix.'media M '.
		'INNER JOIN '.$this->table.' PM ON (M.media_id = PM.media_id) ';
		
		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}
		
		$where='';
		if (isset($params['post_id'])) {
			$where[]="PM.post_id ".$this->con->in($params['post_id']);
		}
		if (isset($params['media_id'])) {
			$where[]="M.media_id ".$this->con->in($params['media_id']);
		}
		if (isset($params['media_path'])) {
			$where[]="M.media_path ".$this->con->in($params['media_path']);
		}
		if (isset($params['link_type'])) {
			$where[]="PM.link_type ".$this->con->in($params['link_type']);
		} else {
			$where[]="PM.link_type='attachment'";
		}

		$strReq .= 'WHERE '.join('AND ',$where).' ';

		if (isset($params['sql'])) {
			$strReq .= $params['sql'];
		}
	//echo $strReq; exit;
		$rs = $this->con->select($strReq);
		
		return $rs;
	}

	/**
	Attaches a media to a post.
	
	@param	post_id	<b>integer</b>		Post ID
	@param	media_id	<b>integer</b>		Optionnal media ID
	*/
	public function addPostMedia($post_id,$media_id,$link_type='attachment')
	{
		$post_id = (integer) $post_id;
		$media_id = (integer) $media_id;
		
		$f = $this->getPostMedia(array('post_id'=>$post_id,'media_id'=>$media_id,'link_type'=>$link_type));
		
		if (!$f->isEmpty()) {
			return;
		}
		
		$cur = $this->con->openCursor($this->table);
		$cur->post_id = $post_id;
		$cur->media_id = $media_id;
		$cur->link_type = $link_type;
		
		$cur->insert();
		$this->core->blog->triggerBlog();
	}
	
	/**
	Detaches a media from a post.
	
	@param	post_id	<b>integer</b>		Post ID
	@param	media_id	<b>integer</b>		Optionnal media ID
	*/
	public function removePostMedia($post_id,$media_id,$link_type=null)
	{
		$post_id = (integer) $post_id;
		$media_id = (integer) $media_id;
		
		$strReq = 'DELETE FROM '.$this->table.' '.
				'WHERE post_id = '.$post_id.' '.
				'AND media_id = '.$media_id.' ';
		if ($link_type != null) {
			$strReq .= "AND link_type = '".$this->con->escape($link_type)."'";
		}
		$this->con->execute($strReq);
		$this->core->blog->triggerBlog();
	}
	
	/**
	Returns media items attached to a blog post. Result is an array containing
	fileItems objects.
	
	@param	post_id	<b>integer</b>		Post ID
	@param	media_id	<b>integer</b>		Optionnal media ID
	@return	<b>array</b> Array of fileItems
	*/
	public function getLegacyPostMedia($post_id,$media_id=null)
	{
		$post_id = (integer) $post_id;
		
		$strReq =
		'SELECT media_file, M.media_id, media_path, media_title, media_meta, media_dt, '.
		'media_creadt, media_upddt, media_private, user_id '.
		'FROM '.$this->table.' M '.
		'INNER JOIN '.$this->table_ref.' PM ON (M.media_id = PM.media_id) '.
		"WHERE media_path = '".$this->path."' ".
		'AND post_id = '.$post_id.' ';
		
		if ($media_id) {
			$strReq .= 'AND M.media_id = '.(integer) $media_id.' ';
		}
		
		$rs = $this->con->select($strReq);
		
		$res = array();
		
		while ($rs->fetch()) {
			$f = $this->fileRecord($rs);
			if ($f !== null) {
				$res[] = $f;
			}
		}
		
		return $res;
	}
	
}
?>