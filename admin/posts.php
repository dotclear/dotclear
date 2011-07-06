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

# Getting categories
try {
	$categories = $core->blog->getCategories(array('post_type'=>'post'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting authors
try {
	$users = $core->blog->getPostsUsers();
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting dates
try {
	$dates = $core->blog->getDates(array('type'=>'month'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Getting langs
try {
	$langs = $core->blog->getLangs();
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Creating filter combo boxes
if (!$core->error->flag())
{
	# Filter form we'll put in html_block
	$users_combo = $categories_combo = array();
	while ($users->fetch())
	{
		$user_cn = dcUtils::getUserCN($users->user_id,$users->user_name,
		$users->user_firstname,$users->user_displayname);
		
		if ($user_cn != $users->user_id) {
			$user_cn .= ' ('.$users->user_id.')';
		}
		
		$users_combo[$user_cn] = $users->user_id; 
	}
	
	$categories_combo[__('None')] = 'NULL';
	while ($categories->fetch()) {
		$categories_combo[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
			html::escapeHTML($categories->cat_title).
			' ('.$categories->nb_post.')'] = $categories->cat_id;
	}
	
	$status_combo = array(
	);
	foreach ($core->blog->getAllPostStatus() as $k => $v) {
		$status_combo[$v] = (string) $k;
	}
	
	$selected_combo = array(
	__('is selected') => '1',
	__('is not selected') => '0'
	);
	
	# Months array
	while ($dates->fetch()) {
		$dt_m_combo[dt::str('%B %Y',$dates->ts())] = $dates->year().$dates->month();
	}
	
	while ($langs->fetch()) {
		$lang_combo[$langs->post_lang] = $langs->post_lang;
	}
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('publish,contentadmin',$core->blog->id))
{
	$combo_action[__('Status')] = array(
		__('Publish') => 'publish',
		__('Unpublish') => 'unpublish',
		__('Schedule') => 'schedule',
		__('Mark as pending') => 'pending'
	);
}
$combo_action[__('Mark')] = array(
	__('Mark as selected') => 'selected',
	__('Mark as unselected') => 'unselected'
);
$combo_action[__('Change')] = array(__('Change category') => 'category');
if ($core->auth->check('admin',$core->blog->id))
{
	$combo_action[__('Change')] = array_merge($combo_action[__('Change')],
		array(__('Change author') => 'author'));
}
if ($core->auth->check('delete,contentadmin',$core->blog->id))
{
	$combo_action[__('Delete')] = array(__('Delete') => 'delete');
}

# --BEHAVIOR-- adminPostsActionsCombo
$core->callBehavior('adminPostsActionsCombo',array(&$combo_action));

/* Get posts
-------------------------------------------------------- */
$post_list = new adminPostList($core);

$params = new ArrayObject();
$params['no_content'] = true;

# - Limit, sortby and order filter
$params = $post_list->applyFilters($params);

$filterSet = new dcFilterSet('posts','posts.php');
class monthComboFilter extends comboFilter {
	public function applyFilter($params) {
		$month=$this->values[0];
		$params['post_month'] = substr($month,4,2);
		$params['post_year'] = substr($month,0,4);
	}
}
$filterSet
	->addFilter(new comboFilter(
		'users',__('Author'), __('Author'), 'user_id', $users_combo))
	->addFilter(new comboFilter(
		'category',__('Category'), __('Category'), 'cat_id', $categories_combo))
	->addFilter(new comboFilter(
		'post_status',__('Status'), __('Status'), 'post_status', $status_combo))
	->addFilter(new booleanFilter(
		'post_selected',__('Selected'), __('The post : '),'post_selected', $selected_combo))
	->addFilter(new comboFilter(
		'lang',__('Lang'), __('Lang'), 'post_lang', $lang_combo))
	->addFilter(new monthComboFilter(
		'month',__('Month'),__('Month'), 'post_month', $dt_m_combo,array('singleval' => 1)));

$core->callBehavior('adminPostsFilters',$filterSet);

$filterSet->setFormValues($_GET);

# Get posts
try {
	$nfparams = $params->getArrayCopy();
	$filtered = $filterSet->applyFilters($params);
	$core->callBehavior('adminPostsParams',$params);
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	if ($filtered) {
		$totalcounter = $core->blog->getPosts($nfparams,true);
		$page_title = sprintf(__('Entries / %s filtered out of %s'),$counter->f(0),$totalcounter->f(0));
	} else {
		$page_title = __('Entries');
	}
	$post_list->setItems($posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

$filterSet->setColumnsForm($post_list->getColumnsForm());

/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_posts_list.js');

$starting_script .= $filterSet->header();

dcPage::open(__('Entries'),$starting_script);

if (!$core->error->flag())
{
	echo 
	'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.$page_title.'</h2>'.
	'<p class="top-add"><a class="button add" href="post.php">'.__('New entry').'</a></p>';

	$filterSet->display();

	# Show posts
	$post_list->display('<form action="posts_actions.php" method="post" id="form-entries">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><span class="filter-title">'.__('Selected entries action:').'</span> '.
	form::combo('action',$combo_action).
	'<input type="submit" value="'.__('ok').'" /></p>'.
	$filterSet->getFormFieldsAsHidden().
	$post_list->getFormFieldsAsHidden().
	$core->formNonce().
	'</div>'.
	'</form>'
	);
}

dcPage::helpBlock('core_posts');
dcPage::close();
?>