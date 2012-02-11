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

# Creating filter combo boxes
# Filter form we'll put in html_block
$status_combo = array(
'-' => ''
);
foreach ($core->blog->getAllCommentStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

$type_combo = array(
'-' => '',
__('comment') => 'co',
__('trackback') => 'tb'
);

$sortby_combo = array(
__('Date') => 'comment_dt',
__('Entry title') => 'post_title',
__('Author') => 'comment_author',
__('Status') => 'comment_status'
);

$order_combo = array(
__('Descending') => 'desc',
__('Ascending') => 'asc'
);


/* Get comments
-------------------------------------------------------- */
$author = isset($_GET['author']) ?	$_GET['author'] : '';
$status = isset($_GET['status']) ?		$_GET['status'] : '';
$type = !empty($_GET['type']) ?		$_GET['type'] : '';
$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'comment_dt';
$order = !empty($_GET['order']) ?		$_GET['order'] : 'desc';
$ip = !empty($_GET['ip']) ?			$_GET['ip'] : '';

$with_spam = $author || $status || $type || $sortby != 'comment_dt' || $order != 'desc' || $ip;

$show_filters = false;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	if ($nb_per_page != $_GET['nb']) {
		$show_filters = true;
	}
	$nb_per_page = (integer) $_GET['nb'];
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;

# Author filter
if ($author !== '') {
	$params['q_author'] = $author;
	$show_filters = true;
} else {
	$author='';
}

# - Type filter
if ($type == 'tb' || $type == 'co') {
	$params['comment_trackback'] = ($type == 'tb');
	$show_filters = true;
} else {
	$type='';
}

# - Status filter
if ($status !== '' && in_array($status,$status_combo)) {
	$params['comment_status'] = $status;
	$show_filters = true;
} elseif (!$with_spam) {
	$params['comment_status_not'] = -2;
	$status='';
} else {
	$status='';
}

# - IP filter
if ($ip) {
	$params['comment_ip'] = $ip;
	$show_filters = true;
}

# Sortby and order filter
if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
	if ($order !== '' && in_array($order,$order_combo)) {
		$params['order'] = $sortby.' '.$order;
	} else {
		$order = 'desc';
	}
	
	if ($sortby != 'comment_dt' || $order != 'desc') {
		$show_filters = true;
	}
} else {
	$sortby = 'comment_dt';
	$order = 'desc';
}

# Actions combo box
$combo_action = array();
$default = '';
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
	if ($status == -2) {
		$default = 'delete';
	}
}

# --BEHAVIOR-- adminCommentsActionsCombo
$core->callBehavior('adminCommentsActionsCombo',array(&$combo_action));

/* Get comments
-------------------------------------------------------- */
try {
	$comments = $core->blog->getComments($params);
	$counter = $core->blog->getComments($params,true);
	$comment_list = new adminCommentList($core,$comments,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_comments.js');
if (!$show_filters) {
	$starting_script .= dcPage::jsLoad('js/filter-controls.js');
}
# --BEHAVIOR-- adminCommentsHeaders
$starting_script .= $core->callBehavior('adminCommentsHeaders');

dcPage::open(__('Comments and trackbacks'),$starting_script);

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Comments and trackbacks').'</span></h2>';

if (!$core->error->flag())
{
	# Filters
	if (!$show_filters) {
		echo '<p><a id="filter-control" class="form-control" href="#">'.
		__('Filters').'</a></p>';
	}
	
	echo
	'<form action="comments.php" method="get" id="filters-form">'.
	'<fieldset><legend>'.__('Filters').'</legend>'.
	'<div class="three-cols">'.
	'<div class="col">'.
	'<label for="type">'.__('Type:').' '.
	form::combo('type',$type_combo,$type).
	'</label> '.
	'<label for="status">'.__('Status:').' '.
	form::combo('status',$status_combo,$status).
	'</label>'.
	'</div>'.
	
	'<div class="col">'.
	'<p><label for="sortby">'.__('Order by:').' '.
	form::combo('sortby',$sortby_combo,$sortby).
	'</label> '.
	'<label for="order">'.__('Sort:').' '.
	form::combo('order',$order_combo,$order).
	'</label></p>'.
	'<p><label for="nb" class="classic">'.	form::field('nb',3,3,$nb_per_page).' '.
	__('Comments per page').'</label></p>'.
	'</div>'.
	
	'<div class="col">'.
	'<p><label for="author">'.__('Comment author:').' '.
	form::field('author',20,255,html::escapeHTML($author)).
	'</label>'.
	'<label for="ip">'.__('IP address:').' '.
	form::field('ip',20,39,html::escapeHTML($ip)).
	'</label></p>'.
	'<p><input type="submit" value="'.__('Apply filters').'" /></p>'.
	'</div>'.
	
	'</div>'.
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
	if (!$with_spam) {
		$spam_count = $core->blog->getComments(array('comment_status'=>-2),true)->f(0);
		if ($spam_count == 1) {
			echo '<p>'.sprintf(__('You have one spam comments.'),'<strong>'.$spam_count.'</strong>').' '.
			'<a href="comments.php?status=-2">'.__('Show it.').'</a></p>';
		} elseif ($spam_count > 1) {
			echo '<p>'.sprintf(__('You have %s spam comments.'),'<strong>'.$spam_count.'</strong>').' '.
			'<a href="comments.php?status=-2">'.__('Show them.').'</a></p>';
		}
	}
	
	# Show comments
	$comment_list->display($page,$nb_per_page,
	'<form action="comments_actions.php" method="post" id="form-comments">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected comments action:').'</label> '.
	form::combo('action',$combo_action,$default,'','','','title="'.__('action: ').'"').
	$core->formNonce().
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('type'),$type).
	form::hidden(array('sortby'),$sortby).
	form::hidden(array('order'),$order).
	form::hidden(array('author'),preg_replace('/%/','%%',$author)).
	form::hidden(array('status'),$status).
	form::hidden(array('ip'),preg_replace('/%/','%%',$ip)).
	form::hidden(array('page'),$page).
	form::hidden(array('nb'),$nb_per_page).
	'</div>'.
	
	'</form>'
	);
}

dcPage::helpBlock('core_comments');
dcPage::close();
?>
