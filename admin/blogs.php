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

echo '<h2>'.__('List of blogs').'</h2>';

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
	$blogs_list = new adminBlogList($core,$rs,$nb_blog);
	$blogs_list->display($page,$nb_per_page);
}

dcPage::close();

?>