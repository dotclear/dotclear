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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$params = array();
$redir = 'comments.php';

if (!empty($_POST['action']) && !empty($_POST['comments']))
{
	$comments = $_POST['comments'];
	$action = $_POST['action'];
	
	if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
	{
		$redir = $_POST['redir'];
	}
	else
	{
		$redir =
		'comments.php?type='.$_POST['type'].
		'&author='.$_POST['author'].
		'&status='.$_POST['status'].
		'&sortby='.$_POST['sortby'].
		'&ip='.$_POST['ip'].
		'&order='.$_POST['order'].
		'&page='.$_POST['page'].
		'&nb='.(integer) $_POST['nb'];
	}
	
	foreach ($comments as $k => $v) {
		$comments[$k] = (integer) $v;
	}
	
	$params['sql'] = 'AND C.comment_id IN('.implode(',',$comments).') ';
	
	if (!isset($_POST['full_content']) || empty($_POST['full_content'])) {
		$params['no_content'] = true;
	}
	
	$co = $core->blog->getComments($params);
	
	# --BEHAVIOR-- adminCommentsActions
	$core->callBehavior('adminCommentsActions',$core,$co,$action,$redir);
	
	if (preg_match('/^(publish|unpublish|pending|junk)$/',$action))
	{
		switch ($action) {
			case 'unpublish' : $status = 0; break;
			case 'pending' : $status = -1; break;
			case 'junk' : $status = -2; break;
			default : $status = 1; break;
		}
		
		while ($co->fetch())
		{
			try {
				$core->blog->updCommentStatus($co->comment_id,$status);
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}
		
		if (!$core->error->flag()) {
			http::redirect($redir);
		}
	}
	elseif ($action == 'delete')
	{
		while ($co->fetch())
		{
			try {
				# --BEHAVIOR-- adminBeforeCommentDelete
				$core->callBehavior('adminBeforeCommentDelete',$co->comment_id);				
				
				$core->blog->delComment($co->comment_id);
			} catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}
		
		if (!$core->error->flag()) {
			http::redirect($redir);
		}
	}
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('Comments'));

if (!isset($action)) {
	dcPage::close();
	exit;
}

$hidden_fields = '';
while ($co->fetch()) {
	$hidden_fields .= form::hidden(array('comments[]'),$co->comment_id);
} 

if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
{
	$hidden_fields .= form::hidden(array('redir'),html::escapeURL($_POST['redir']));
}
else
{
	$hidden_fields .=
	form::hidden(array('type'),$_POST['type']).
	form::hidden(array('author'),$_POST['author']).
	form::hidden(array('status'),$_POST['status']).
	form::hidden(array('sortby'),$_POST['sortby']).
	form::hidden(array('ip'),$_POST['ip']).
	form::hidden(array('order'),$_POST['order']).
	form::hidden(array('page'),$_POST['page']).
	form::hidden(array('nb'),$_POST['nb']);
}

# --BEHAVIOR-- adminCommentsActionsContent
$core->callBehavior('adminCommentsActionsContent',$core,$action,$hidden_fields);

echo '<p><a class="back" href="'.str_replace('&','&amp;',$redir).'">'.__('back').'</a></p>';

dcPage::close();
?>