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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$comment_id = null;
$comment_dt = '';
$comment_author = '';
$comment_email = '';
$comment_site = '';
$comment_content = '';
$comment_ip = '';
$comment_status = '';
$comment_trackback = 0;
$comment_spam_status = '';

# Status combo
foreach ($core->blog->getAllCommentStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

# Adding comment
if (!empty($_POST['add']) && !empty($_POST['post_id']))
{
	try
	{
		$rs = $core->blog->getPosts(array('post_id' => $_POST['post_id'], 'post_type' => ''));
		
		if ($rs->isEmpty()) {
			throw new Exception(__('Entry does not exist.'));
		}
		
		$cur = $core->con->openCursor($core->prefix.'comment');
		
		$cur->comment_author = $_POST['comment_author'];
		$cur->comment_email = html::clean($_POST['comment_email']);
		$cur->comment_site = html::clean($_POST['comment_site']);
		$cur->comment_content = $core->HTMLfilter($_POST['comment_content']);
		$cur->post_id = (integer) $_POST['post_id'];
		
		# --BEHAVIOR-- adminBeforeCommentCreate
		$core->callBehavior('adminBeforeCommentCreate',$cur);
		
		$comment_id = $core->blog->addComment($cur);
		
		# --BEHAVIOR-- adminAfterCommentCreate
		$core->callBehavior('adminAfterCommentCreate',$cur,$comment_id);
		
		http::redirect($core->getPostAdminURL($rs->post_type,$rs->post_id,false).'&co=1&creaco=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

if (!empty($_REQUEST['id']))
{
	$params['comment_id'] = $_REQUEST['id'];
	
	try {
		$rs = $core->blog->getComments($params);
		if (!$rs->isEmpty()) {
			$comment_id = $rs->comment_id;
			$post_id = $rs->post_id;
			$post_type = $rs->post_type;
			$post_title = $rs->post_title;
			$comment_dt = $rs->comment_dt;
			$comment_author = $rs->comment_author;
			$comment_email = $rs->comment_email;
			$comment_site = $rs->comment_site;
			$comment_content = $rs->comment_content;
			$comment_ip = $rs->comment_ip;
			$comment_status = $rs->comment_status;
			$comment_trackback = (boolean) $rs->comment_trackback;
			$comment_spam_status = $rs->comment_spam_status;
		}
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

if (!$comment_id && !$core->error->flag()) {
	$core->error->add(__('No comment'));
}

if (!$core->error->flag() && isset($rs))
{
	$can_edit = $can_delete = $can_publish = $core->auth->check('contentadmin',$core->blog->id);
	
	if (!$core->auth->check('contentadmin',$core->blog->id) && $core->auth->userID() == $rs->user_id) {
		$can_edit = true;
		if ($core->auth->check('delete',$core->blog->id)) {
			$can_delete = true;
		}
		if ($core->auth->check('publish',$core->blog->id)) {
			$can_publish = true;
		}
	}
	
	# update comment
	if (!empty($_POST['update']) && $can_edit)
	{
		$cur = $core->con->openCursor($core->prefix.'comment');
		
		$cur->comment_author = $_POST['comment_author'];
		$cur->comment_email = html::clean($_POST['comment_email']);
		$cur->comment_site = html::clean($_POST['comment_site']);
		$cur->comment_content = $core->HTMLfilter($_POST['comment_content']);
		
		if (isset($_POST['comment_status'])) {
			$cur->comment_status = (integer) $_POST['comment_status'];
		}
		
		try
		{
			# --BEHAVIOR-- adminBeforeCommentUpdate
			$core->callBehavior('adminBeforeCommentUpdate',$cur,$comment_id);
			
			$core->blog->updComment($comment_id,$cur);
			
			# --BEHAVIOR-- adminAfterCommentUpdate
			$core->callBehavior('adminAfterCommentUpdate',$cur,$comment_id);
			
			http::redirect('comment.php?id='.$comment_id.'&upd=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	
	if (!empty($_POST['delete']) && $can_delete)
	{
		try {
			# --BEHAVIOR-- adminBeforeCommentDelete
			$core->callBehavior('adminBeforeCommentDelete',$comment_id);
			
			$core->blog->delComment($comment_id);
			http::redirect($core->getPostAdminURL($rs->post_type,$rs->post_id).'&co=1#c'.$comment_id,false);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	
	if (!$can_edit) {
		$core->error->add(__("You can't edit this comment."));
	}
}

/* DISPLAY
-------------------------------------------------------- */
if ($comment_id) {
	$breadcrumb = dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			html::escapeHTML($post_title) => $core->getPostAdminURL($post_type,$post_id).'&amp;co=1#c'.$comment_id,
			'<span class="page-title">'.__('Edit comment').'</span>' => ''
		));
} else {
	$breadcrumb = dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			html::escapeHTML($post_title) => $core->getPostAdminURL($post_type,$post_id),
			'<span class="page-title">'.__('Edit comment').'</span>' => ''
		));
}

dcPage::open(__('Edit comment'),
	dcPage::jsConfirmClose('comment-form').
	dcPage::jsToolBar().
	dcPage::jsLoad('js/_comment.js').
	# --BEHAVIOR-- adminCommentHeaders
	$core->callBehavior('adminCommentHeaders'),
	$breadcrumb
);

if ($comment_id)
{
	if (!empty($_GET['upd'])) {
		dcPage::success(__('Comment has been successfully updated.'));
	}
	
	$comment_mailto = '';
	if ($comment_email)
	{
		$comment_mailto = '<a href="mailto:'.html::escapeHTML($comment_email)
		.'?subject='.rawurlencode(sprintf(__('Your comment on my blog %s'),$core->blog->name))
		.'&body='
		.rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"),$rs->getPostURL()))
		.'">'.__('Send an e-mail').'</a>';
	}

	echo
	'<form action="comment.php" method="post" id="comment-form">'.
	'<div class="fieldset">'.
	'<h3>'.__('Information collected').'</h3>'.
	'<p>'.__('IP address:').' '.
	'<a href="comments.php?ip='.$comment_ip.'">'.$comment_ip.'</a></p>'.
	
	'<p>'.__('Date:').' '.
	dt::dt2str(__('%Y-%m-%d %H:%M'),$comment_dt).'</p>'.
	'</div>'.

	'<h3>'.__('Comment submitted').'</h3>'.	
	'<p><label for="comment_author" class="required"><abbr title="'.__('Required field').'">*</abbr>'.__('Author:').'</label>'.
	form::field('comment_author',30,255,html::escapeHTML($comment_author)).
	'</p>'.
	
	'<p><label for="comment_email">'.__('Email:').'</label>'.
	form::field('comment_email',30,255,html::escapeHTML($comment_email)).
	'<span>'.$comment_mailto.'</span>'.
	'</p>'.
	
	'<p><label for="comment_site">'.__('Web site:').'</label>'.
	form::field('comment_site',30,255,html::escapeHTML($comment_site)).
	'</p>'.
	
	'<p><label for="comment_status">'.__('Status:').'</label>'.
	form::combo('comment_status',$status_combo,$comment_status,'','',!$can_publish).
	'</p>'.
	
	# --BEHAVIOR-- adminAfterCommentDesc
	$core->callBehavior('adminAfterCommentDesc', $rs).
	
	'<p class="area"><label for="comment_content">'.__('Comment:').'</label> '.
	form::textarea('comment_content',50,10,html::escapeHTML($comment_content)).
	'</p>'.
	
	'<p>'.form::hidden('id',$comment_id).
	$core->formNonce().
	'<input type="submit" accesskey="s" name="update" value="'.__('Save').'" /> ';
	
	if ($can_delete) {
		echo '<input type="submit" class="delete" name="delete" value="'.__('Delete').'" />';
	}
	echo
	'</p>'.
	'</form>';
}

dcPage::helpBlock('core_comments');
dcPage::close();
?>