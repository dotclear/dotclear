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

#if (isset($_GET['dcxd'])) {
#	$_COOKIE['dcxd'] = $_GET['dcxd'];
#}

require dirname(__FILE__).'/../inc/admin/prepend.php';

$core->rest->addFunction('getPostById',array('dcRestMethods','getPostById'));
$core->rest->addFunction('getCommentById',array('dcRestMethods','getCommentById'));
$core->rest->addFunction('quickPost',array('dcRestMethods','quickPost'));
$core->rest->addFunction('validatePostMarkup',array('dcRestMethods','validatePostMarkup'));
$core->rest->addFunction('getZipMediaContent',array('dcRestMethods','getZipMediaContent'));
$core->rest->addFunction('getMeta',array('dcRestMethods','getMeta'));
$core->rest->addFunction('delMeta',array('dcRestMethods','delMeta'));
$core->rest->addFunction('setPostMeta',array('dcRestMethods','setPostMeta'));
$core->rest->addFunction('searchMeta',array('dcRestMethods','searchMeta'));

$core->rest->serve();

/* Common REST methods */
class dcRestMethods
{
	public static function getPostById($core,$get)
	{
		if (empty($get['id'])) {
			throw new Exception('No post ID');
		}
		
		$params = array('post_id' => (integer) $get['id']);
		
		if (isset($get['post_type'])) {
			$params['post_type'] = $get['post_type'];
		}
		
		$rs = $core->blog->getPosts($params);
		
		if ($rs->isEmpty()) {
			throw new Exception('No post for this ID');
		}
		
		$rsp = new xmlTag('post');
		$rsp->id = $rs->post_id;
		
		$rsp->blog_id($rs->blog_id);
		$rsp->user_id($rs->user_id);
		$rsp->cat_id($rs->cat_id);
		$rsp->post_dt($rs->post_dt);
		$rsp->post_creadt($rs->post_creadt);
		$rsp->post_upddt($rs->post_upddt);
		$rsp->post_format($rs->post_format);
		$rsp->post_url($rs->post_url);
		$rsp->post_lang($rs->post_lang);
		$rsp->post_title($rs->post_title);
		$rsp->post_excerpt($rs->post_excerpt);
		$rsp->post_excerpt_xhtml($rs->post_excerpt_xhtml);
		$rsp->post_content($rs->post_content);
		$rsp->post_content_xhtml($rs->post_content_xhtml);
		$rsp->post_notes($rs->post_notes);
		$rsp->post_status($rs->post_status);
		$rsp->post_selected($rs->post_selected);
		$rsp->post_open_comment($rs->post_open_comment);
		$rsp->post_open_tb($rs->post_open_tb);
		$rsp->nb_comment($rs->nb_comment);
		$rsp->nb_trackback($rs->nb_trackback);
		$rsp->user_name($rs->user_name);
		$rsp->user_firstname($rs->user_firstname);
		$rsp->user_displayname($rs->user_displayname);
		$rsp->user_email($rs->user_email);
		$rsp->user_url($rs->user_url);
		$rsp->cat_title($rs->cat_title);
		$rsp->cat_url($rs->cat_url);
		
		$rsp->post_display_content($rs->getContent(true));
		$rsp->post_display_excerpt($rs->getExcerpt(true));
		
		$metaTag = new xmlTag('meta');
		if (($meta = @unserialize($rs->post_meta)) !== false)
		{
			foreach ($meta as $K => $V)
			{
				foreach ($V as $v) {
					$metaTag->$K($v);
				}
			}
		}
		$rsp->post_meta($metaTag);
		
		return $rsp;
	}
	
	public static function getCommentById($core,$get)
	{
		if (empty($get['id'])) {
			throw new Exception('No comment ID');
		}
		
		$rs = $core->blog->getComments(array('comment_id' => (integer) $get['id']));
		
		if ($rs->isEmpty()) {
			throw new Exception('No comment for this ID');
		}
		
		$rsp = new xmlTag('post');
		$rsp->id = $rs->comment_id;
		
		$rsp->comment_dt($rs->comment_dt);
		$rsp->comment_upddt($rs->comment_upddt);
		$rsp->comment_author($rs->comment_author);
		$rsp->comment_site($rs->comment_site);
		$rsp->comment_content($rs->comment_content);
		$rsp->comment_trackback($rs->comment_trackback);
		$rsp->comment_status($rs->comment_status);
		$rsp->post_title($rs->post_title);
		$rsp->post_url($rs->post_url);
		$rsp->post_id($rs->post_id);
		$rsp->post_dt($rs->post_dt);
		$rsp->user_id($rs->user_id);
		
		$rsp->comment_display_content($rs->getContent(true));
		
		if ($core->auth->userID()) {
			$rsp->comment_ip($rs->comment_ip);
			$rsp->comment_email($rs->comment_email);
			# --BEHAVIOR-- adminAfterCommentDesc
			$rsp->comment_spam_disp($core->callBehavior('adminAfterCommentDesc', $rs));
		}
		
		return $rsp;
	}
	
	public static function quickPost($core,$get,$post)
	{
		$cur = $core->con->openCursor($core->prefix.'post');
		
		$cur->post_title = !empty($post['post_title']) ? $post['post_title'] : '';
		$cur->user_id = $core->auth->userID();
		$cur->post_content = !empty($post['post_content']) ? $post['post_content'] : '';
		$cur->cat_id = !empty($post['cat_id']) ? (integer) $post['cat_id'] : null;
		$cur->post_format = !empty($post['post_format']) ? $post['post_format'] : 'xhtml';
		$cur->post_lang = !empty($post['post_lang']) ? $post['post_lang'] : '';
		$cur->post_status = !empty($post['post_status']) ? (integer) $post['post_status'] : 0;
		$cur->post_open_comment = (integer) $core->blog->settings->system->allow_comments;
		$cur->post_open_tb = (integer) $core->blog->settings->system->allow_trackbacks;
		
		# --BEHAVIOR-- adminBeforePostCreate
		$core->callBehavior('adminBeforePostCreate',$cur);
		
		$return_id = $core->blog->addPost($cur);
		
		# --BEHAVIOR-- adminAfterPostCreate
		$core->callBehavior('adminAfterPostCreate',$cur,$return_id);
		
		$rsp = new xmlTag('post');
		$rsp->id = $return_id;
		
		$post = $core->blog->getPosts(array('post_id' => $return_id));
		
		$rsp->post_status = $post->post_status;
		$rsp->post_url = $post->getURL();
		return $rsp;
	}
	
	public static function validatePostMarkup($core,$get,$post)
	{
		if (!isset($post['excerpt'])) {
			throw new Exception('No entry excerpt');
		}
		
		if (!isset($post['content'])) {
			throw new Exception('No entry content');
		}
		
		if (empty($post['format'])) {
			throw new Exception('No entry format');
		}
		
		if (!isset($post['lang'])) {
			throw new Exception('No entry lang');
		}
		
		$excerpt = $post['excerpt'];
		$excerpt_xhtml = '';
		$content = $post['content'];
		$content_xhtml = '';
		$format = $post['format'];
		$lang = $post['lang'];
		
		$core->blog->setPostContent(0,$format,$lang,$excerpt,$excerpt_xhtml,$content,$content_xhtml);
		
		$rsp = new xmlTag('result');
		
		$v = htmlValidator::validate($excerpt_xhtml.$content_xhtml);
		
		$rsp->valid($v['valid']);
		$rsp->errors($v['errors']);
		
		return $rsp;
	}
	
	public static function getZipMediaContent($core,$get,$post)
	{
		if (empty($get['id'])) {
			throw new Exception('No media ID');
		}
		
		$id = (integer) $get['id'];
		
		if (!$core->auth->check('media,media_admin',$core->blog)) {
			throw new Exception('Permission denied');
		}
		
		$core->media = new dcMedia($core);
		$file = $core->media->getFile($id);
		
		if ($file === null || $file->type != 'application/zip' || !$file->editable) {
			throw new Exception('Not a valid file');
		}
		
		$rsp = new xmlTag('result');
		$content = $core->media->getZipContent($file);
		
		foreach ($content as $k => $v) {
			$rsp->file($k);
		}
		
		return $rsp;
	}
	
	public static function getMeta($core,$get)
	{
		$postid = !empty($get['postId']) ? $get['postId'] : null;
		$limit = !empty($get['limit']) ? $get['limit'] : null;
		$metaId = !empty($get['metaId']) ? $get['metaId'] : null;
		$metaType = !empty($get['metaType']) ? $get['metaType'] : null;
		
		$sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';
		
		$rs = $core->meta->getMetadata(array(
			'meta_type' => $metaType,
			'limit' => $limit,
			'meta_id' => $metaId,
			'post_id' => $postid));
		$rs = $core->meta->computeMetaStats($rs);
		
		$sortby = explode(',',$sortby);
		$sort = $sortby[0];
		$order = isset($sortby[1]) ? $sortby[1] : 'asc';
		
		switch ($sort) {
			case 'metaId':
				$sort = 'meta_id_lower';
				break;
			case 'count':
				$sort = 'count';
				break;
			case 'metaType':
				$sort = 'meta_type';
				break;
			default:
				$sort = 'meta_type';
		}
		
		$rs->sort($sort,$order);
		
		$rsp = new xmlTag();
		
		while ($rs->fetch())
		{
			$metaTag = new xmlTag('meta');
			$metaTag->type = $rs->meta_type;
			$metaTag->uri = rawurlencode($rs->meta_id);
			$metaTag->count = $rs->count;
			$metaTag->percent = $rs->percent;
			$metaTag->roundpercent = $rs->roundpercent;
			$metaTag->CDATA($rs->meta_id);
			
			$rsp->insertNode($metaTag);
		}
		
		return $rsp;
	}
	
	public static function setPostMeta($core,$get,$post)
	{
		if (empty($post['postId'])) {
			throw new Exception('No post ID');
		}
		
		if (empty($post['meta']) && $post['meta'] != '0') {
			throw new Exception('No meta');
		}
		
		if (empty($post['metaType'])) {
			throw new Exception('No meta type');
		}
		
		# Get previous meta for post
		$post_meta = $core->meta->getMetadata(array(
			'meta_type' => $post['metaType'],
			'post_id' => $post['postId']));
		$pm = array();
		while ($post_meta->fetch()) {
			$pm[] = $post_meta->meta_id;
		}
		
		foreach ($core->meta->splitMetaValues($post['meta']) as $m)
		{
			if (!in_array($m,$pm)) {
				$core->meta->setPostMeta($post['postId'],$post['metaType'],$m);
			}
		}
		
		return true;
	}
	
	public static function delMeta($core,$get,$post)
	{
		if (empty($post['postId'])) {
			throw new Exception('No post ID');
		}
		
		if (empty($post['metaId']) && $post['metaId'] != '0') {
			throw new Exception('No meta ID');
		}
		
		if (empty($post['metaType'])) {
			throw new Exception('No meta type');
		}
		
		$core->meta->delPostMeta($post['postId'],$post['metaType'],$post['metaId']);
		
		return true;
	}
	
	public static function searchMeta($core,$get)
	{
		$q = !empty($get['q']) ? $get['q'] : null;
		$metaType = !empty($get['metaType']) ? $get['metaType'] : null;
		
		$sortby = !empty($get['sortby']) ? $get['sortby'] : 'meta_type,asc';
		
		$rs = $core->meta->getMetadata(array('meta_type' => $metaType));
		$rs = $core->meta->computeMetaStats($rs);
		
		$sortby = explode(',',$sortby);
		$sort = $sortby[0];
		$order = isset($sortby[1]) ? $sortby[1] : 'asc';
		
		switch ($sort) {
			case 'metaId':
				$sort = 'meta_id_lower';
				break;
			case 'count':
				$sort = 'count';
				break;
			case 'metaType':
				$sort = 'meta_type';
				break;
			default:
				$sort = 'meta_type';
		}
		
		$rs->sort($sort,$order);
		
		$rsp = new xmlTag();
		
		while ($rs->fetch())
		{
			if (preg_match('/'.$q.'/i',$rs->meta_id)) {
				$metaTag = new xmlTag('meta');
				$metaTag->type = $rs->meta_type;
				$metaTag->uri = rawurlencode($rs->meta_id);
				$metaTag->count = $rs->count;
				$metaTag->percent = $rs->percent;
				$metaTag->roundpercent = $rs->roundpercent;
				$metaTag->CDATA($rs->meta_id);
				
				$rsp->insertNode($metaTag);
			}
		}
		
		return $rsp;
	}
}
?>