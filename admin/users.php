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

$show_filters = false;

#?Get users
$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
$nb_per_page =  30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	if ($nb_per_page != $_GET['nb']) {
		$show_filters = true;
	}
	$nb_per_page = $_GET['nb'];
}

$q = !empty($_GET['q']) ? $_GET['q'] : '';
$sortby = !empty($_GET['sortby']) ?	$_GET['sortby'] : 'user_id';
$order = !empty($_GET['order']) ?		$_GET['order'] : 'asc';

$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);


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

$form_filter_title = __('Show filters and display options');
$starting_script  = dcPage::jsLoad('js/_users.js');
$starting_script .= dcPage::jsLoad('js/filter-controls.js');
$starting_script .=
	'<script type="text/javascript">'."\n".
	"//<![CDATA["."\n".
	dcPage::jsVar('dotclear.msg.show_filters', $show_filters ? 'true':'false')."\n".
	dcPage::jsVar('dotclear.msg.filter_posts_list',$form_filter_title)."\n".
	dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'))."\n".
	"//]]>".
	"</script>";

dcPage::open(__('Users'),$starting_script,
	dcPage::breadcrumb(
		array(
			__('System') => '',
			__('Users') => ''
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
	'<p class="top-add"><strong><a class="button add" href="user.php">'.__('New user').'</a></strong></p>'.
	'<form action="users.php" method="get" id="filters-form">'.
	'<h3 class="out-of-screen-if-js">'.$form_filter_title.'</h3>'.
	
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
	'</form>',
	$show_filters
	);
}

dcPage::close();
?>