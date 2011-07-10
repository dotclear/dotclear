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

dcPage::checkSuper();

# Filters
$sortby_combo = array(
__('Blog ID') => 'B.blog_id',
__('Blog name') => 'blog_name'
);

$order_combo = array(
__('Descending') => 'desc',
__('Ascending') => 'asc'
);

$q = !empty($_GET['q']) ? $_GET['q'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'blog_id';
$order = !empty($_GET['order']) ? $_GET['order'] : 'asc';


# Check users
if (!empty($_REQUEST['user_id']) && is_array($_REQUEST['user_id']))
{
	foreach ($_REQUEST['user_id'] as $u)
	{
		if ($core->userExists($u)) {
			$users[] = $u;
		}
	}
}

if (empty($users))
{
	$core->error->add(__('No blog or user given.'));
}
else
{
	$page = !empty($_GET['page']) ? $_GET['page'] : 1;
	$nb_per_page =  30;
	
	if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
		$nb_per_page = $_GET['nb'];
	}
	
	$show_filters = false;
	
	# - Search filter
	if ($q) {
		$params['q'] = $q;
		$show_filters = true;
	}
	
	# - Sortby and order filter
	if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
		if ($order !== '' && in_array($order,$order_combo)) {
			$params['order'] = $sortby.' '.$order;
			$show_filters = true;
		}
	}
	
	$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
	
	try {
		$rs = $core->getBlogs($params);
		$counter = $core->getBlogs($params,1);
		$nb_blog = $counter->f(0);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_permissions_blog.js');
if (!$show_filters) {
	$starting_script .= dcPage::jsLoad('js/filter-controls.js');
}
dcPage::open(__('choose a blog'),$starting_script);

echo '<h2><a href="users.php">'.__('Users').'</a> &rsaquo; <span class="page-title">'.__('Choose a blog').'</span></h2>';

if (!$core->error->flag())
{
	$hidden_fields = '';
	foreach ($users as $u) {
		$hidden_fields .= form::hidden(array('user_id[]'),$u);
	}
	
	if (!$show_filters) {
		echo '<p><a id="filter-control" class="form-control" href="#">'.__('Filters').'</a></p>';
	}
	
	echo
	'<form action="permissions_blog.php" method="get" id="filters-form">'.
	'<fieldset class="two-cols"><legend>'.__('Filters').'</legend>'.
	
	'<div class="col">'.
	'<p><label for="sortby">'.__('Order by:').' '.
	form::combo('sortby',$sortby_combo,html::escapeHTML($sortby)).
	'</label> '.
	'<label for="order">'.__('Sort:').' '.
	form::combo('order',$order_combo,html::escapeHTML($order)).
	'</label></p>'.
	'</div>'.
	
	'<div class="col">'.
	'<p><label for="q">'.__('Search:').' '.
	form::field('q',20,255,html::escapeHTML($q)).
	'</label></p>'.
	'<p><label for="nb" class="classic">'.	form::field('nb',3,3,$nb_per_page).' '.
	__('Entries per page').'</label> '.
	'<input type="submit" value="'.__('Apply filters').'" />'.
	$hidden_fields.'</p>'.
	'</div>'.
	
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
	echo
	'<p>'.
	sprintf(__('Choose one or more blogs to which you want to give permissions to users %s.'),
	'<strong>'.implode(', ',$users).'</strong>').'</p>';
	
	# Show blogs
	if ($nb_blog == 0)
	{
		echo '<p><strong>'.__('No blog').'</strong></p>';
	}
	else
	{
		$pager = new pager($page,$nb_blog,$nb_per_page,10);
		$pager->var_page = 'page';
		
		echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
		
		echo
		'<form action="permissions.php" method="post" id="form-blogs">'.
		'<table class="clear"><tr>'.
		'<th colspan="2">'.__('Blog ID').'</th>'.
		'<th>'.__('Blog name').'</th>'.
		'<th class="nowrap">'.__('Entries').'</th>'.
		'<th class="nowrap">'.__('Status').'</th>'.
		'</tr>';
		
		while ($rs->fetch()) {
			echo blogLine($rs);
		}
		
		echo
		'</table>'.
		
		'<p class="checkboxes-helpers"></p>'.
		
		'<p><input type="submit" value="'.__('Set permissions').'" />'.
		$hidden_fields.
		$core->formNonce().'</p>'.
		'</form>';
		
		echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
	}
}

dcPage::close();

function blogLine($rs)
{
	global $core;
	
	$img_status = $rs->blog_status == 1 ? 'check-on' : 'check-off';
	$txt_status = $GLOBALS['core']->getBlogStatus($rs->blog_status);
	$img_status = sprintf('<img src="images/%1$s.png" alt="%2$s" title="%2$s" />',$img_status,$txt_status);
	
	return
	'<tr class="line">'.
	'<td class="nowrap">'.
	form::checkbox(array('blog_id[]'),$rs->blog_id,'','','',false,'title="'.__('select').' '.$rs->blog_id.'"').'</td>'.
	'<td class="nowrap">'.$rs->blog_id.'</td>'.
	'<td class="maximal">'.html::escapeHTML($rs->blog_name).'</td>'.
	'<td class="nowrap">'.$core->countBlogPosts($rs->blog_id).'</td>'.
	'<td class="status">'.$img_status.'</td>'.
	'</tr>';
}
?>