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

$q = !empty($_GET['q']) ? $_GET['q'] : '';

$blogs_list = new adminBlogList($core);

$params = new ArrayObject();

# - Limit, sortby and order filter
$params = $blogs_list->applyFilters($params);

$show_filters = false;
	
# - Search filter
if ($q) {
	$params['q'] = $q;
	$show_filters = true;
}

try {
	$counter = $core->getBlogs($params,1);
	$rs = $core->getBlogs($params);
	$blogs_list->setItems($rs,$counter->f(0));
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
	'<fieldset><legend>'.__('Filters').'</legend>'.
	
	'<p><label for="q">'.__('Search:').' '.
	form::field('q',20,255,html::escapeHTML($q)).
	'</label></p>'.
	'<p><input type="submit" value="'.__('Apply filters').'" /></p>'.
	
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
	# Show blogs
	$blogs_list->display();
}

dcPage::close();

?>