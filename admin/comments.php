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

if (!empty($_POST['delete_all_spam']))
{
	try {
		$core->blog->delJunkComments();
		$_SESSION['comments_del_spam'] = true;
		http::redirect('comments.php');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Creating filter combo boxes
# Filter form we'll put in html_block
$status_combo = array_merge(
	array('-' => ''),
	dcAdminCombos::getCommentStatusescombo()
);


$type_combo = array(
'-' => '',
__('Comment') => 'co',
__('Trackback') => 'tb'
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

$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
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
if ($core->auth->check('delete,contentadmin',$core->blog->id) && $status == -2)
{
	$default = 'delete';
}

$comments_actions_page = new dcCommentsActionsPage($core,'comments.php');

$comments_actions_page->process();

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
$starting_script  = dcPage::jsLoad('js/_comments.js');
$starting_script .= dcPage::jsLoad('js/filter-controls.js');
$starting_script .=
	'<script type="text/javascript">'."\n".
	"//<![CDATA["."\n".
	dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
	dcPage::jsVar('dotclear.msg.filter_posts_list',$form_filter_title)."\n".
	dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel the filter'))."\n".
	"//]]>".
	"</script>";

$form_filter_title = __('Filter comments, trackbacks list and display options');

dcPage::open(__('Comments and trackbacks'),$starting_script,
	dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Comments and trackbacks').'</span>' => ''
		))
);
if (!empty($_GET['upd'])) {
	dcPage::success(__('Selected comments have been successfully updated.'));
} elseif (!empty($_GET['del'])) {
	dcPage::success(__('Selected comments have been successfully deleted.'));
}

if (!$core->error->flag())
{
	if (isset($_SESSION['comments_del_spam'])) {
		dcPage::message(__('Spam comments have been successfully deleted.'));
		unset($_SESSION['comments_del_spam']);
	}
	
	$spam_count = $core->blog->getComments(array('comment_status'=>-2),true)->f(0);
	if ($spam_count > 0) {
		
		echo 
			'<form action="comments.php" method="post" class="fieldset">';

		if (!$with_spam || ($status != -2)) {
			if ($spam_count == 1) {
				echo '<p>'.sprintf(__('You have one spam comment.'),'<strong>'.$spam_count.'</strong>').' '.
				'<a href="comments.php?status=-2">'.__('Show it.').'</a>.</p>';
			} elseif ($spam_count > 1) {
				echo '<p>'.sprintf(__('You have %s spam comments.'),'<strong>'.$spam_count.'</strong>').' '.
				'<a href="comments.php?status=-2">'.__('Show them.').'</a>.</p>';
			}
		}
		
		echo
			'<p class="no-margin">'.
			$core->formNonce().
			'<input name="delete_all_spam" class="delete" type="submit" value="'.__('Delete all spams').'" /></p>';

		# --BEHAVIOR-- adminCommentsSpamForm
		$core->callBehavior('adminCommentsSpamForm',$core);

		echo '</form>';
	}
	
	echo
	'<form action="comments.php" method="get" id="filters-form">'.
	'<h3 class="hidden">'.__('Filter comments and trackbacks list').'</h3>'.
	'<div class="table">'.

	'<div class="cell">'.
	'<h4>'.__('Filters').'</h4>'.
	'<p><label for="type" class="ib">'.__('Type:').'</label> '.
	form::combo('type',$type_combo,$type).'</p> '.
	'<p><label for="status" class="ib">'.__('Status:').'</label> '.
	form::combo('status',$status_combo,$status).'</p>'.
	'</div>'.
	
	'<div class="cell filters-sibling-cell">'.
	'<p><label for="author" class="ib">'.__('Author:').'</label> '.
	form::field('author',20,255,html::escapeHTML($author)).'</p>'.
	'<p><label for="ip" class="ib">'.__('IP address:').'</label> '.
	form::field('ip',20,39,html::escapeHTML($ip)).'</p>'.
	'</div>'.
	
	'<div class="cell filters-options">'.
	'<h4>'.__('Display options').'</h4>'.
	'<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
	form::combo('sortby',$sortby_combo,$sortby).'</p>'.
	'<p><label for="order" class="ib">'.__('Sort:').'</label> '.
	form::combo('order',$order_combo,$order).'</p>'.
	'<p><span class="label ib">Afficher</span> <label for="nb" class="classic">'.
	form::field('nb',3,3,$nb_per_page).' '.
	__('comments per page').'</label></p>'.
	'</div>'.
	
	'</div>'.
	'<p><input type="submit" value="'.__('Apply filters and display options').'" />'.
	'<br class="clear" /></p>'. //Opera sucks
	'</form>';
	
	# Show comments
	$comment_list->display($page,$nb_per_page,
	'<form action="comments.php" method="post" id="form-comments">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected comments action:').'</label> '.
	form::combo('action',$comments_actions_page->getCombo(),$default,'','','','title="'.__('Actions').'"').
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
	
	'</form>',
	$show_filters
	);
}

dcPage::helpBlock('core_comments');
dcPage::close();
?>