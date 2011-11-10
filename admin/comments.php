<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

class authorFilter extends textFilter {
	public function header() {
		return 
			dcPage::jqueryUI().
			dcPage::jsLoad('js/author_filter.js')
			;
	}
	public function applyFilter($params) {
		$val = preg_split("/[\s,]+/",$this->values[0]);
		$params[$this->request_param]=$val;
	}
}


# Creating filter combo boxes
# Filter form we'll put in html_block
$status_combo = array();
foreach ($core->blog->getAllCommentStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

$type_combo = array(
__('comment') => '0',
__('trackback') => '1'
);

$comment_list = new adminCommentList($core);

$params = new ArrayObject();
$params['no_content'] = true;

# - Limit, sortby and order filter
$params = $comment_list->applyFilters($params);

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
	$combo_action[__('delete')] = 'delete';
}

# --BEHAVIOR-- adminCommentsActionsCombo
$core->callBehavior('adminCommentsActionsCombo',array(&$combo_action));

$filterSet = new dcFilterSet('comments','comments.php');

$authorFilter = new authorFilter(
		'author',__('Author'), __('Author'),'comment_author',20,255);
$filterSet
	->addFilter(new comboFilter(
		'status',__('Status'), __('Status'), 'comment_status', $status_combo))
	->addFilter(new booleanFilter(
		'type',__('Type'), __('Type'), 'comment_trackback', $type_combo))
	->addFilter($authorFilter)
	->addFilter(new textFilter(
		'ip',__('IP address'), __('IP address'), 'comment_ip',20,39));
		
$core->callBehavior('adminCommentsFilters',$filterSet);

$filterSet->setup($_GET,$_POST);
if (isset($_GET['author'])) {
	$authorFilter->add();
	$authorFilter->setValue($_GET['author']);
}
/* Get comments
-------------------------------------------------------- */
try {
	$nfparams = $params->getArrayCopy();
	$filtered = $filterSet->applyFilters($params);
	$core->callBehavior('adminCommentsParams',$params);
	$comments = $core->blog->getComments($params);
	$counter = $core->blog->getComments($params,true);
	if ($filtered) {
		$totalcounter = $core->blog->getComments($nfparams,true);
		$page_title = sprintf(__('Comments and Trackacks / %s filtered out of %s'),$counter->f(0),$totalcounter->f(0));
	} else {
		$page_title = __('Comments and Trackacks');
	}

	$comment_list->setItems($comments,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}
$filterSet->setExtraData($comment_list->getColumnsForm());

/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_comments.js').$filterSet->header();

# --BEHAVIOR-- adminCommentsHeaders
$starting_script .= $core->callBehavior('adminCommentsHeaders');

dcPage::open(__('Comments and trackbacks'),$starting_script);

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.$page_title.'</h2>';

if (!$core->error->flag())
{
	# Filters
	$filterSet->display();
	
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
	$comment_list->display('<form action="comments_actions.php" method="post" id="form-comments">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.__('Selected comments action:').'</label> '.
	form::combo('action',$combo_action,'','','','','title="'.__('action: ').'"').
	$core->formNonce().
	'<input type="submit" value="'.__('ok').'" /></p>'.
	form::hidden(array('type'),$type).
	form::hidden(array('author'),preg_replace('/%/','%%',$author)).
	form::hidden(array('status'),$status).
	form::hidden(array('ip'),preg_replace('/%/','%%',$ip)).
	$comment_list->getFormFieldsAsHidden().
	'</div>'.
	
	'</form>'
	);
}

dcPage::helpBlock('core_comments');
dcPage::close();
?>