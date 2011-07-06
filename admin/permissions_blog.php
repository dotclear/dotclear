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

dcPage::checkSuper();

$q = !empty($_GET['q']) ? $_GET['q'] : '';

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
	$blogs_list = new adminBlogPermissionsList($core);
	
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
		$rs = $core->getBlogs($params);
		$counter = $core->getBlogs($params,1);
		$blogs_list->setItems($rs,$counter->f(0));
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

echo '<h2><a href="users.php">'.__('Users').'</a> &rsaquo; '.__('Choose a blog').'</h2>';

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
	
	'<p><label for="q">'.__('Search:').' '.
	form::field('q',20,255,html::escapeHTML($q)).
	'</label></p>'.
	'<p><input type="submit" value="'.__('Apply filters').'" />'.
	$hidden_fields.'</p>'.
	
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
	echo
	'<p>'.
	sprintf(__('Choose one or more blogs to which you want to give permissions to users %s.'),
	'<strong>'.implode(', ',$users).'</strong>').'</p>';
	
	# Show blogs
	$blogs_list->display('<form action="permissions.php" method="post" id="form-blogs">'.
		'%s'.
		'<p class="checkboxes-helpers"></p>'.
		'<p><input type="submit" value="'.__('set permissions').'" />'.
		$hidden_fields.
		$core->formNonce().'</p>'.
		'</form>'
	);
}

dcPage::close();

?>