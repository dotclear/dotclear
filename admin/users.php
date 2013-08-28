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

dcPage::checkSuper();

# Creating filter combo boxes
$sortby_combo = array(
__('Username') => 'user_id',
__('Last Name') => 'user_name',
__('First Name') => 'user_firstname',
__('Display name') => 'user_displayname',
__('Number of entries') => 'nb_post'
);

$order_combo = array(
__('Descending') => 'desc',
__('Ascending') => 'asc'
);

# Actions combo box
$combo_action = array(
	__('Set permissions') => 'blogs',
	__('Delete') => 'deleteuser'
);

# --BEHAVIOR-- adminUsersActionsCombo
$core->callBehavior('adminUsersActionsCombo',array(&$combo_action));


#?Get users
$page = !empty($_GET['page']) ? $_GET['page'] : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	$nb_per_page = $_GET['nb'];
}

$q = !empty($_GET['q']) ? $_GET['q'] : '';
$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'user_id';
$order = !empty($_GET['order']) ?		$_GET['order'] : 'asc';

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);

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
	} else {
		$order='asc';
	}
	
	if ($sortby != 'user_id' || $order != 'asc') {
		$show_filters = true;
	}
} else {
	$sortby = 'user_id';
	$order = 'asc';
}

# Get users
try {
	$rs = $core->getUsers($params);
	$counter = $core->getUsers($params,1);
	$user_list = new adminUserList($core,$rs,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}


/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_users.js');
if (!$show_filters) {
	$starting_script .= dcPage::jsLoad('js/filter-controls.js');
}

dcPage::open(__('Users'),$starting_script,
	dcPage::breadcrumb(
		array(
			__('System') => '',
			'<span class="page-title">'.__('Users').'</span>' => ''
		))
);

if (!$core->error->flag())
{
	if (!empty($_GET['del'])) {
		dcPage::message(__('User has been successfully removed.'));
	}
	if (!empty($_GET['upd'])) {
		dcPage::message(__('The permissions have been successfully updated.'));
	}
	
	echo
	'<p class="top-add"><strong><a class="button add" href="user.php">'.__('New user').'</a></strong></p>';
	
	if (!$show_filters) {
		echo '<p><a id="filter-control" class="form-control" href="#">'.__('Filter users list').'</a></p>';
	}
	
	echo
	'<form action="users.php" method="get" id="filters-form">'.
	'<h3 class="hidden">'.__('Filter users list').'</h3>'.
	
	'<div class="table">'.
	'<div class="cell">'.
	'<h4>'.__('Filters').'</h4>'.
	'<p><label for="q" class="ib">'.__('Search:').'</label> '.
	form::field('q',20,255,html::escapeHTML($q)).'</p>'.
	'</div>'.

	'<div class="cell filters-options">'.
	'<h4>'.__('Display options').'</h4>'.
	'<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
	form::combo('sortby',$sortby_combo,$sortby).'</p> '.
	'<p><label for="order" class="ib">'.__('Sort:').'</label> '.
	form::combo('order',$order_combo,$order).'</p>'.
	'<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.	
	form::field('nb',3,3,$nb_per_page).' '.__('users per page').'</label></p> '.
	'</div>'.
	'</div>'.

	'<p><input type="submit" value="'.__('Apply filters and display options').'" />'.	
	'<br class="clear" /></p>'. //Opera sucks
	'</form>';
	
	# Show users
	$user_list->display($page,$nb_per_page,
	'<form action="users_actions.php" method="post" id="form-users">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="action" class="classic">'.
	__('Selected users action:').' '.
	form::combo('action',$combo_action).
	'</label> '.
	'<input type="submit" value="'.__('ok').'" />'.
	form::hidden(array('q'),html::escapeHTML($q)).
	form::hidden(array('sortby'),$sortby).
	form::hidden(array('order'),$order).
	form::hidden(array('page'),$page).
	form::hidden(array('nb'),$nb_per_page).
	$core->formNonce().
	'</p>'.
	'</div>'.
	'</form>'
	);
}

dcPage::close();
?>