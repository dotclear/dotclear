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

# Delete users
if (!empty($delete_users))
{
	foreach ($delete_users as $u)
	{
		try
		{
			# --BEHAVIOR-- adminBeforeUserDelete
			$core->callBehavior('adminBeforeUserDelete',$u);
			if ($u != $core->auth->userID()) {
				$core->delUser($u);
			}
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	if (!$core->error->flag()) {
		http::redirect('users.php?del=1');
	}
}


# Creating filter combo boxes
$sortby_combo = array(
__('Username') => 'U.user_id',
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
	__('Set permissions') => 'setpermissions',
	__('Delete') => 'deleteuser'
);

# --BEHAVIOR-- adminUsersActionsCombo
$core->callBehavior('adminUsersActionsCombo',array(&$combo_action));


# Get users
$user_list = new adminUserList($core);

$q = !empty($_GET['q']) ? $_GET['q'] : '';

$show_filters = false;

$params = new ArrayObject();

# - Limit, sortby and order filter
$params = $user_list->applyFilters($params);

# - Search filter
if ($q) {
	$params['q'] = $q;
	$show_filters = true;
}

try {
	$rs = $core->getUsers($params);
	$counter = $core->getUsers($params,1);
	$user_list->setItems($rs,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */
$starting_script = dcPage::jsLoad('js/_users.js');
if (!$show_filters) {
	$starting_script .= dcPage::jsLoad('js/filter-controls.js');
}

dcPage::open(__('users'),$starting_script);

if (!$core->error->flag())
{
	if (!empty($_GET['del'])) {
		echo '<p class="message">'.__('User has been successfully removed.').'</p>';
	}
	
	echo 
	'<h2 class="post-title">'.__('Users').'</h2>'.
	'<p class="top-add"><strong><a class="button add" href="user.php">'.__('Create a new user').'</a></strong></p>';
	
	if (!$show_filters) {
		echo '<p><a id="filter-control" class="form-control" href="#">'.__('Filters').'</a></p>';
	}
	
	echo
	'<form action="users.php" method="get" id="filters-form">'.
	'<fieldset><legend>'.__('Filters').'</legend>'.
	
	'<p><label for="q">'.__('Search:').' '.
	form::field('q',20,255,html::escapeHTML($q)).
	'</label></p>'.
	'<p><input type="submit" value="'.__('Apply filters').'" /></p>'.
	
	'<br class="clear" />'. //Opera sucks
	'</fieldset>'.
	'</form>';
	
	# Show users
	$user_list->display('<form action="dispatcher.php" method="get" id="form-users">'.
	
	'%s'.
	
	'<div class="two-cols">'.
	'<p class="col checkboxes-helpers"></p>'.
	
	'<p class="col right"><label for="dispatch_action" class="classic">'.
	__('Selected users action:').' '.
	form::combo('dispatch_action',$combo_action).
	'</label> '.
	'<input type="submit" value="'.__('ok').'" />'.
	'</p>'.
	$user_list->getFormFieldsAsHidden().
	'</div>'.
	'</form>'
	);
}

dcPage::close();
?>