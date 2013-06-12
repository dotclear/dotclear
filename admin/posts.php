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
global $_ctx;
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
	

# Getting categories
$categories_combo = array();
try {
	$categories = $core->blog->getCategories(array('post_type'=>'post'));
	while ($categories->fetch()) {
		$categories_combo[$categories->cat_id] = 
			str_repeat('&nbsp;&nbsp;',$categories->level-1).
			($categories->level-1 == 0 ? '' : '&bull; ').
			html::escapeHTML($categories->cat_title);
	}
} catch (Exception $e) { }
	$status_combo = array(
	);
	foreach ($core->blog->getAllPostStatus() as $k => $v) {
		$status_combo[(string) $k] = (string)$v;
	}
	
	$selected_combo = array(
	'1' => __('is selected'),
	'0' => __('is not selected')
	);
	
	# Months array
	while ($dates->fetch()) {
		$dt_m_combo[$dates->year().$dates->month()] = dt::str('%B %Y',$dates->ts());
	}
	
	while ($langs->fetch()) {
		$lang_combo[$langs->post_lang] = $langs->post_lang;
	}
}
$form = new dcForm($core,'post','post.php');


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
$combo_action[__('Change')] = array(
	__('Change category') => 'category',
	__('Change language') => 'lang');
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



class monthdcFilterCombo extends dcFilterCombo {
	public function applyFilter($params) {
		$month=$this->avalues['values'][0];
		$params['post_month'] = substr($month,4,2);
		$params['post_year'] = substr($month,0,4);
	}
}

class PostsFetcher extends dcListFetcher {

	public function getEntries($params,$offset,$limit) {
		$params['limit'] = array($offset,$limit);
		return $this->core->blog->getPosts($params);
	}

	public function getEntriesCount($params) {
		$count = $this->core->blog->getPosts($params,true);
		return $count->f(0);
	}
}

/* DISPLAY
-------------------------------------------------------- */
$filterSet = new dcFilterSet($core,'fposts','posts.php');

$filterSet
	->addFilter(new dcFilterRichCombo(
		'users',__('Author'), __('Author'), 'user_id', $users_combo,array(
			'multiple' => true)))
	->addFilter(new dcFilterRichCombo(
		'category',__('Category'), __('Category'), 'cat_id', $categories_combo))
	->addFilter(new dcFilterRichCombo(
		'post_status',__('Status'), __('Status'), 'post_status', $status_combo))
	->addFilter(new dcFilterRichCombo(
		'lang',__('Lang'), __('Lang'), 'post_lang', $lang_combo))
	->addFilter(new dcFilterCombo(
		'selected',__('Selected'), __('The post : '),'post_selected', $selected_combo))
	->addFilter(new monthdcFilterCombo(
		'month',__('Month'),__('Month'), 'post_month', $dt_m_combo,array('singleval' => 1)))
	->addFilter(new dcFilterText(
		'search',__('Contains'),__('The entry contains'), 'search',20,255));


$lfetcher = new PostsFetcher($core);
$lposts = new dcItemList ($core,array('lposts','form-entries'),$filterSet,$lfetcher,'posts_actions.php');
$lposts->addTemplate('posts_cols.html.twig');

$lposts
	->addColumn(new dcColumn('title',__('Title'),'post_title'))
	->addColumn(new dcColumn('cat',__('Category'),'cat_title'))
	->addColumn(new dcColumn('date',__('Date'),'post_date'))
	->addColumn(new dcColumn('datetime',__('Date and Time'),'post_dt'))
	->addColumn(new dcColumn('author',__('Author'),'user_id'))
	->addColumn(new dcColumn('status',__('Status'),'post_status'));


$lposts->setup();

$_ctx
	->fillPageTitle(__('Entries'),'posts.php');


$core->tpl->display('posts.html.twig');


?>