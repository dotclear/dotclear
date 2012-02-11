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

# Filters
$sortby_combo = array(
__('Last update') => 'blog_upddt',
__('Blog name') => 'UPPER(blog_name)',
__('Blog ID') => 'B.blog_id'
);

$order_combo = array(
__('Descending') => 'desc',
__('Ascending') => 'asc'
);

$q = !empty($_GET['q']) ? $_GET['q'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'blog_upddt';
$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';

$page = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	$nb_per_page = (integer) $_GET['nb'];
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
	}
	
	if ($sortby != 'blog_upddt' || $order != 'desc') {
		$show_filters = true;
	}
}

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);

try {
	$counter = $core->getBlogs($params,1);
	$rs = $core->getBlogs($params);
	$nb_blog = $counter->f(0);
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */
$starting_script = '';
if (!$show_filters) {
	$starting_script .= dcPage::jsLoad('js/filter-controls.js');
}
dcPage::open(__('List of blogs'),$starting_script);

if (!empty($_GET['del'])) {
	echo '<p class="message">'.__('Blog has been successfully deleted.').'</p>';
}

echo '<h2 class="page-title">'.__('List of blogs').'</h2>';

if (!$core->error->flag())
{
	if ($core->auth->isSuperAdmin()) {
		echo '<p class="top-add"><a class="button add" href="blog.php">'.__('Create a new blog').'</a></p>';
	}
	
	if (!$show_filters) {
		echo '<p><a id="filter-control" class="form-control" href="#">'.__('Filters').'</a></p>';
	}
	
	echo
	'<form action="blogs.php" method="get" id="filters-form">'.
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
	__('Blogs per page').'</label> '.
	'<input type="submit" value="'.__('Apply filters').'" /></p>'.
	'</div>'.
	
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
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
		'<table class="clear"><tr>'.
		'<th>'.__('Blog name').'</th>'.
		'<th class="nowrap">'.__('Last update').'</th>'.
		'<th class="nowrap">'.__('Entries').'</th>'.
		'<th class="nowrap">'.__('Blog ID').'</th>'.
		'<th>&nbsp;</th>'.
		'<th class="nowrap">'.__('Status').'</th>'.
		'</tr>';
		
		while ($rs->fetch()) {
			echo blogLine($rs);
		}
		
		echo '</table>';
		
		echo '<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
	}
}

dcPage::close();

function blogLine($rs)
{
	global $core;
	
	$blog_id = html::escapeHTML($rs->blog_id);
	$edit_link = '';
	
	if ($GLOBALS['core']->auth->isSuperAdmin()) {
		$edit_link = 
		'<a href="blog.php?id='.$blog_id.'" '.
		'title="'.sprintf(__('Edit blog %s'),$blog_id).'">'.
		__('edit').'</a>';
	}
	
	$img_status = $rs->blog_status == 1 ? 'check-on' : 'check-off';
	$txt_status = $GLOBALS['core']->getBlogStatus($rs->blog_status);
	$img_status = sprintf('<img src="images/%1$s.png" alt="%2$s" title="%2$s" />',$img_status,$txt_status);
	$offset = dt::getTimeOffset($core->auth->getInfo('user_tz'));
	$blog_upddt = dt::str(__('%Y-%m-%d %H:%M'),strtotime($rs->blog_upddt) + $offset);
	
	return
	'<tr class="line">'.
	'<td class="maximal"><a href="index.php?switchblog='.$rs->blog_id.'" '.
	'title="'.sprintf(__('Switch to blog %s'),$rs->blog_id).'">'.
	html::escapeHTML($rs->blog_name).'</a></td>'.
	'<td class="nowrap">'.$blog_upddt.'</td>'.
	'<td class="nowrap">'.$core->countBlogPosts($rs->blog_id).'</td>'.
	'<td class="nowrap">'.$blog_id.'</td>'.
	'<td>'.$edit_link.'</td>'.
	'<td class="status">'.$img_status.'</td>'.
	'</tr>';
}
?>