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

class dcRevisions
{
	public function __construct($core)
	{
		$this->core = $core;
	}
	
	public function getRevisions($params,$count_only = false)
	{
		if ($count_only) {
			$f = 'COUNT(revision_id)';
		}
		else {
			$f = 'R.revision_id, R.post_id, R.user_id, R.revision_type, '.
			'R.revision_dt, R.revision_tz, R.revision_excerpt_diff, '.
			'R.revision_excerpt_xhtml_diff, R.revision_content_diff, '.
			'R.revision_content_xhtml_diff, U.user_url, U.user_name, '.
			'U.user_firstname, U.user_displayname';
		}
		
		$strReq = 'SELECT '.$f.' FROM '.$this->core->prefix.'revision R '.
		'LEFT JOIN '.$this->core->prefix.'user U ON R.user_id = U.user_id ';
		
		if (!empty($params['from'])) {
			$strReq .= $params['from'].' ';
		}
		
		$strReq .= "WHERE R.blog_id = '".$this->core->con->escape($this->core->blog->id)."' ";
		
		if (!empty($params['post_id'])) {
			if (is_array($params['post_id'])) {
				array_walk($params['post_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			} else {
				$params['post_id'] = array((integer) $params['post_id']);
			}
			$strReq .= 'AND R.post_id '.$this->core->con->in($params['post_id']);
		}
		
		if (!empty($params['revision_id'])) {
			if (is_array($params['revision_id'])) {
				array_walk($params['revision_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			} else {
				$params['revision_id'] = array((integer) $params['revision_id']);
			}
			$strReq .= 'AND R.revision_id '.$this->core->con->in($params['revision_id']);
		}
		
		if (isset($params['post_type'])) {
			if (is_array($params['post_type']) && !empty($params['post_type'])) {
				$strReq .= 'AND R.revision_type '.$this->core->con->in($params['revision_type']);
			} elseif ($params['post_type'] != '') {
				$strReq .= "AND R.revision_type = '".$this->core->con->escape($params['revision_type'])."' ";
			}
		}
		
		if (!empty($params['sql'])) {
			$strReq .= $params['sql'].' ';
		}
		
		if (!$count_only) {
			if (!empty($params['order'])) {
				$strReq .= 'ORDER BY '.$this->core->con->escape($params['order']).' ';
			} else {
				$strReq .= 'ORDER BY revision_dt DESC ';
			}
		}
		
		if (!$count_only && !empty($params['limit'])) {
			$strReq .= $this->core->con->limit($params['limit']);
		}
		
		$rs = $this->core->con->select($strReq);
		$rs->core = $this->core;
		$rs->extend('dcRevisionsExtensions');
		
		return $rs;
	}
	
	public function addRevision($pcur,$post_id)
	{
		$rs = $this->core->con->select(
			'SELECT MAX(revision_id) '.
			'FROM '.$this->core->prefix.'revision' 
		);
		$revision_id = $rs->f(0) + 1; 
		
		$rs = $this->core->blog->getPosts(array('post_id' => $post_id));
		
		$old = array(
			'post_excerpt' => $rs->post_excerpt,
			'post_excerpt_xhtml' => $rs->post_excerpt_xhtml,
			'post_content' => $rs->post_content,
			'post_content_xhtml' => $rs->post_content_xhtml
		);
		$new = array(
			'post_excerpt' => $pcur->post_excerpt,
			'post_excerpt_xhtml' => $pcur->post_excerpt_xhtml,
			'post_content' => $pcur->post_content,
			'post_content_xhtml' => $pcur->post_content_xhtml
		);
		
		$diff = $this->getDiff($new,$old);
		
		$insert = false;
		foreach ($diff as $k => $v) {
			if ($v !== '') {
				$insert = true;
			}
		}
		
		if ($insert) {
			$rcur = $this->core->con->openCursor($this->core->prefix.'revision');
			$rcur->revision_id = $revision_id;
			$rcur->post_id = $post_id;
			$rcur->user_id = $this->core->auth->userID();
			$rcur->blog_id = $this->core->blog->id;
			$rcur->revision_dt = date('Y-m-d H:i:s');
			$rcur->revision_tz = $this->core->auth->getInfo('user_tz');
			$rcur->revision_type = $pcur->post_type;
			$rcur->revision_excerpt_diff = $diff['post_excerpt'];
			$rcur->revision_excerpt_xhtml_diff = $diff['post_excerpt_xhtml'];
			$rcur->revision_content_diff = $diff['post_content'];
			$rcur->revision_content_xhtml_diff = $diff['post_content_xhtml'];
	 
			$this->core->con->writeLock($this->core->prefix.'revision');
			$rcur->insert();
			$this->core->con->unlock();
		}
	}
	
	public function getDiff($n,$o)
	{
		$diff = array(
			'post_excerpt' => '',
			'post_excerpt_xhtml' => '',
			'post_content' => '',
			'post_content_xhtml' => ''
		);
		
		try {
			foreach ($diff as $k => $v) {
				$diff[$k] = diff::uniDiff($n[$k],$o[$k]);
			}
		
			return $diff;
		}
		catch (Exception $e)
		{
			$this->core->error->add($e->getMessage());
		}
	}
	
	public function setPatch($pid,$rid)
	{
		if (!$this->canPatch($rid)) {
			throw new Exception(__('You are not allowed to patch this entry with this revision'));
		}
		
  		try
		{
			$patch = $this->getPatch($pid,$rid);

			$p = $this->core->blog->getPosts(array('post_id' => $pid));

			$cur = $this->core->con->openCursor($this->core->prefix.'post');

			$cur->post_title = $p->post_title;
			$cur->cat_id = $p->cat_id ? $p->cat_id : null;
			$cur->post_dt = $p->post_dt ? date('Y-m-d H:i:00',strtotime($p->post_dt)) : '';
			$cur->post_format = $p->post_format;
			$cur->post_password = $p->post_password;
			$cur->post_lang = $p->post_lang;
			$cur->post_notes = $p->post_notes;
			$cur->post_status = $p->post_status;
			$cur->post_selected = (integer) $p->post_selected;
			$cur->post_open_comment = (integer) $p->post_open_comment;
			$cur->post_open_tb = (integer) $p->post_open_tb;

			$cur->post_excerpt = $patch['post_excerpt'];
			$cur->post_excerpt_xhtml = $patch['post_excerpt_xhtml'];
			$cur->post_content = $patch['post_content'];
			$cur->post_content_xhtml = $patch['post_content_xhtml'];

			# --BEHAVIOR-- adminBeforePostUpdate
			$this->core->callBehavior('adminBeforePostUpdate',$cur,$pid);
			
			$this->core->auth->sudo(array($this->core->blog,'updPost'),$pid,$cur);
			
			# --BEHAVIOR-- adminAfterPostUpdate
			$this->core->callBehavior('adminAfterPostUpdate',$cur,$pid);
			
			http::redirect('post.php?id='.$pid.'&upd=1');
		}
		catch (Exception $e)
		{
			$this->core->error->add($e->getMessage());
		}
	}
	
	public function getPatch($pid,$rid)
	{
		$params = array(
			'post_id' => $pid
		);
		
		$p = $this->core->blog->getPosts($params);
		$r = $this->getRevisions($params);
		
		$patch = array(
			'post_excerpt' => $p->post_excerpt,
			'post_excerpt_xhtml' => $p->post_excerpt_xhtml,
			'post_content' => $p->post_content,
			'post_content_xhtml' => $p->post_content_xhtml
		);

		while ($r->fetch()) {
			foreach ($patch as $k => $v) {
				if ($k === 'post_excerpt') { $f = 'revision_excerpt_diff'; }
				if ($k === 'post_excerpt_xhtml') { $f = 'revision_excerpt_xhtml_diff'; }
				if ($k === 'post_content') { $f = 'revision_content_diff'; }
				if ($k === 'post_content_xhtml') { $f = 'revision_content_xhtml_diff'; }
				
				$patch[$k] = diff::uniPatch($v,$r->{$f});
			}

			if ($r->revision_id === $rid) {
				break;
			}
		}
		
		return $patch;
	}
	
	protected function canPatch($ird)
	{
		$r = $this->getRevisions(array('revision_id' => $rid));
		
		return ($r->canPatch());
	}
}

?>