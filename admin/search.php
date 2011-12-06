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

$q = !empty($_GET['q']) ? $_GET['q'] : null;
$qtype = !empty($_GET['qtype']) ? $_GET['qtype'] : 'p';
if ($qtype != 'c' && $qtype != 'p') {
	$qtype = 'p';
}

$starting_scripts = '';

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;


if ($q)
{
	$params = array();
	
	# Get posts
	if ($qtype == 'p')
	{
		$starting_scripts .= dcPage::jsLoad('js/_posts_list.js');
		
		$params['search'] = $q;
		$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
		$params['no_content'] = true;
		$params['order'] = 'post_dt DESC';
		
		try {
			$posts = $core->blog->getPosts($params);
			$counter = $core->blog->getPosts($params,true);
			$post_list = new adminPostList($core,$posts,$counter->f(0));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	# Get comments
	elseif ($qtype == 'c')
	{
		$starting_scripts .= dcPage::jsLoad('js/_comments.js');
		
		$params['search'] = $q;
		$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
		$params['no_content'] = true;
		$params['order'] = 'comment_dt DESC';
		
		try {
			$comments = $core->blog->getComments($params);
			$counter = $core->blog->getComments($params,true);
			$comment_list = new adminCommentList($core,$comments,$counter->f(0));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}


dcPage::open(__('Search'),$starting_scripts);

echo
'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Search').'</span></h2>'.
'<form action="search.php" method="get">'.
'<div class="fieldset"><h3>'.__('Search options').'</h3>'.
'<p><label for="q">'.__('Query:').' </label>'.form::field('q',30,255,html::escapeHTML($q)).'</p>'.
'<p><label for="qtype1" class="classic">'.form::radio(array('qtype','qtype1'),'p',$qtype == 'p').' '.__('Search entries').'</label> '.
'<label for="qtype2" class="classic">'.form::radio(array('qtype','qtype2'),'c',$qtype == 'c').' '.__('Search comments').'</label></p>'.
'</p><input type="submit" value="'.__('Search').'" /></p>'.
'</div>'.
'</form>';

if ($q && !$core->error->flag())
{
	$redir = html::escapeHTML($_SERVER['REQUEST_URI']);
	
	# Show posts
	if ($qtype == 'p')
	{
		# Actions combo box
		$combo_action = array();
		if ($core->auth->check('publish,contentadmin',$core->blog->id))
		{
			$combo_action[__('publish')] = 'publish';
			$combo_action[__('unpublish')] = 'unpublish';
			$combo_action[__('schedule')] = 'schedule';
			$combo_action[__('mark as pending')] = 'pending';
		}
		$combo_action[__('change category')] = 'category';
		if ($core->auth->check('admin',$core->blog->id)) {
			$combo_action[__('change author')] = 'author';
		}
		if ($core->auth->check('delete,contentadmin',$core->blog->id))
		{
			$combo_action[__('Delete')] = 'delete';
		}
		
		# --BEHAVIOR-- adminPostsActionsCombo
		$core->callBehavior('adminPostsActionsCombo',array(&$combo_action));
		
		if ($counter->f(0) > 0) {
			printf('<h3>'.
			($counter->f(0) == 1 ? __('%d entry found') : __('%d entries found')).
			'</h3>',$counter->f(0));
		}
		
		$post_list->display($page,$nb_per_page,
		'<form action="posts_actions.php" method="post" id="form-entries">'.
		
		'%s'.
		
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		
		'<p class="col right"><label for="action1" class="classic">'.__('Selected entries action:').'</label> '.
		form::combo(array('action','action1'),$combo_action).
		'<input type="submit" value="'.__('ok').'" /></p>'.
		form::hidden('redir',preg_replace('/%/','%%',$redir)).
		$core->formNonce().
		'</div>'.
		'</form>'
		);
	}
	# Show posts
	elseif ($qtype == 'c')
	{
		# Actions combo box
		$combo_action = array();
		if ($core->auth->check('publish,contentadmin',$core->blog->id))
		{
			$combo_action[__('publish')] = 'publish';
			$combo_action[__('unpublish')] = 'unpublish';
			$combo_action[__('mark as pending')] = 'pending';
			$combo_action[__('mark as junk')] = 'junk';
		}
		if ($core->auth->check('delete,contentadmin',$core->blog->id))
		{
			$combo_action[__('Delete')] = 'delete';
		}
		
		if ($counter->f(0) > 0) {
			printf('<h3>'.
			($counter->f(0) == 1 ? __('%d comment found') : __('%d comments found')).
			'</h3>',$counter->f(0));
		}
		
		$comment_list->display($page,$nb_per_page,
		'<form action="comments_actions.php" method="post" id="form-comments">'.
		
		'%s'.
		
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		
		'<p class="col right"><label for="action2" class="classic">'.__('Selected comments action:').'</label> '.
		form::combo(array('action','action2'),$combo_action).
		'<input type="submit" value="'.__('ok').'" /></p>'.
		form::hidden('redir',preg_replace('/%/','%%',$redir)).
		$core->formNonce().
		'</div>'.
		'</form>'
		);
	}
}


dcPage::close();
?>