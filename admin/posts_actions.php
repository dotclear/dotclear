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

/* Actions
-------------------------------------------------------- */
if (!empty($_POST['action']) && !empty($_POST['entries']))
{
	$entries = $_POST['entries'];
	$action = $_POST['action'];
	
	if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
	{
		$redir = $_POST['redir'];
	}
	else
	{
		$redir =
		'posts.php?user_id='.$_POST['user_id'].
		'&cat_id='.$_POST['cat_id'].
		'&status='.$_POST['status'].
		'&selected='.$_POST['selected'].
		'&month='.$_POST['month'].
		'&lang='.$_POST['lang'].
		'&sortby='.$_POST['sortby'].
		'&order='.$_POST['order'].
		'&page='.$_POST['page'].
		'&nb='.$_POST['nb'];
	}
	
	foreach ($entries as $k => $v) {
		$entries[$k] = (integer) $v;
	}
	
	$params['sql'] = 'AND P.post_id IN('.implode(',',$entries).') ';
	
	if (!isset($_POST['full_content']) || empty($_POST['full_content'])) {
		$params['no_content'] = true;
	}
	
	if (isset($_POST['post_type'])) {
		$params['post_type'] = $_POST['post_type'];
	}
	
	$posts = $core->blog->getPosts($params);
	
	# --BEHAVIOR-- adminPostsActions
	$core->callBehavior('adminPostsActions',$core,$posts,$action,$redir);
	
	if (preg_match('/^(publish|unpublish|schedule|pending)$/',$action))
	{
		switch ($action) {
			case 'unpublish' : $status = 0; break;
			case 'schedule' : $status = -1; break;
			case 'pending' : $status = -2; break;
			default : $status = 1; break;
		}
		
		try
		{
			while ($posts->fetch()) {
				$core->blog->updPostStatus($posts->post_id,$status);
			}
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'selected' || $action == 'unselected')
	{
		try
		{
			while ($posts->fetch()) {
				$core->blog->updPostSelected($posts->post_id,$action == 'selected');
			}
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'delete')
	{
		try
		{
			while ($posts->fetch()) {
				# --BEHAVIOR-- adminBeforePostDelete
				$core->callBehavior('adminBeforePostDelete',$posts->post_id);				
				$core->blog->delPost($posts->post_id);
			}
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
		
	}
	elseif ($action == 'category' && isset($_POST['new_cat_id']))
	{
		try
		{
			while ($posts->fetch())
			{
				$new_cat_id = (integer) $_POST['new_cat_id'];
				$core->blog->updPostCategory($posts->post_id,$new_cat_id);
			}
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	elseif ($action == 'author' && isset($_POST['new_auth_id'])
	&& $core->auth->check('admin',$core->blog->id))
	{
		$new_user_id = $_POST['new_auth_id'];
		
		try
		{
			if ($core->getUser($new_user_id)->isEmpty()) {
				throw new Exception(__('This user does not exist'));
			}
			
			while ($posts->fetch())
			{
				$cur = $core->con->openCursor($core->prefix.'post');
				$cur->user_id = $new_user_id;
				$cur->update('WHERE post_id = '.(integer) $posts->post_id);
			}
			
			http::redirect($redir);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(
	__('Entries'),
	dcPage::jsMetaEditor().
	# --BEHAVIOR-- adminBeforePostDelete
	$core->callBehavior('adminPostsActionsHeaders')
);

if (!isset($action)) {
	dcPage::close();
	exit;
}

$hidden_fields = '';
while ($posts->fetch()) {
	$hidden_fields .= form::hidden(array('entries[]'),$posts->post_id);
}

if (isset($_POST['redir']) && strpos($_POST['redir'],'://') === false)
{
	$hidden_fields .= form::hidden(array('redir'),html::escapeURL($_POST['redir']));
}
else
{
	$hidden_fields .=
	form::hidden(array('user_id'),$_POST['user_id']).
	form::hidden(array('cat_id'),$_POST['cat_id']).
	form::hidden(array('status'),$_POST['status']).
	form::hidden(array('selected'),$_POST['selected']).
	form::hidden(array('month'),$_POST['month']).
	form::hidden(array('lang'),$_POST['lang']).
	form::hidden(array('sortby'),$_POST['sortby']).
	form::hidden(array('order'),$_POST['order']).
	form::hidden(array('page'),$_POST['page']).
	form::hidden(array('nb'),$_POST['nb']);
}

if (isset($_POST['post_type'])) {
	$hidden_fields .= form::hidden(array('post_type'),$_POST['post_type']);
}

# --BEHAVIOR-- adminPostsActionsContent
$core->callBehavior('adminPostsActionsContent',$core,$action,$hidden_fields);

if ($action == 'category')
{
	echo '<h2 class="page-title">'.__('Change category for entries').'</h2>';
	
	# categories list
	# Getting categories
	$categories_combo = array('&nbsp;' => '');
	try {
		$categories = $core->blog->getCategories(array('post_type'=>'post'));
		while ($categories->fetch()) {
			$categories_combo[] = new formSelectOption(
				str_repeat('&nbsp;&nbsp;',$categories->level-1).
				($categories->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($categories->cat_title),
				$categories->cat_id
			);
		}
	} catch (Exception $e) { }
	
	echo
	'<form action="posts_actions.php" method="post">'.
	'<p><label for="new_cat_id" class="classic">'.__('Category:').' '.
	form::combo('new_cat_id',$categories_combo,'').
	'</label> ';
	
	echo
	$hidden_fields.
	$core->formNonce().
	form::hidden(array('action'),'category').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';
}
elseif ($action == 'author' && $core->auth->check('admin',$core->blog->id))
{
	echo '<h2 class="page-title">'.__('Change author for entries').'</h2>';
	
	echo
	'<form action="posts_actions.php" method="post">'.
	'<p><label for="new_auth_id" class="classic">'.__('Author ID:').' '.
	form::field('new_auth_id',20,255).
	'</label> ';
	
	echo
	$hidden_fields.
	$core->formNonce().
	form::hidden(array('action'),'author').
	'<input type="submit" value="'.__('Save').'" /></p>'.
	'</form>';
}

echo '<p><a class="back" href="'.html::escapeURL($redir).'">'.__('back').'</a></p>';

dcPage::close();
?>