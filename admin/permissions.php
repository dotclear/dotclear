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

$changes = false;
$blogs = array();
$users = array();

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

# Check blogs
if (!empty($_REQUEST['blog_id']) && is_array($_REQUEST['blog_id']))
{
	foreach ($_REQUEST['blog_id'] as $b)
	{
		if ($core->blogExists($b)) {
			$blogs[] = $b;
		}
	}
}

# Update permissions
if (!empty($_POST['upd_perm']) && !empty($users) && !empty($blogs))
{
	$redir = 'permissions.php?upd=1';
	
	try
	{
		if (empty($_POST['your_pwd']) || !$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['your_pwd']))) {
			throw new Exception(__('Password verification failed'));
		}
		
		foreach ($users as $u)
		{
			foreach ($blogs as $b)
			{
				$set_perms = array();
				
				if (!empty($_POST['perm'][$b]))
				{
					foreach ($_POST['perm'][$b] as $perm_id => $v)
					{
						if ($v) {
							$set_perms[$perm_id] = true;
						}
					}
				}
				
				$core->setUserBlogPermissions($u, $b, $set_perms, true);
			}
			
			$redir .= '&user_id[]='.$u;
		}
		
		foreach ($blogs as $b) {
			$redir .= '&blog_id[]='.$b;
		}
		http::redirect($redir);
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}



if (empty($blogs) || empty($users)) {
	$core->error->add(__('No blog or user given.'));
}


/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('permissions'),
	dcPage::jsLoad('js/_permissions.js')
);

echo '<h2><a href="users.php">'.__('Users').'</a> &rsaquo; <span class="page-title">'.__('Permissions').'</span></h2>';

if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('The permissions have been successfully updated.').'</p>';
}

if (!empty($blogs) && !empty($users))
{
	$perm_form = '';
	
	if (count($users) == 1) {
			$user_perm = $core->getUserPermissions($users[0]);	
	}
	
	foreach ($users as $u) {
		$user_list[] = '<a href="user.php?id='.$u.'">'.$u.'</a>';
	}
	
	echo '<p>'.sprintf(__('You are about to change permissions on the following blogs for users %s.'),
	implode(', ',$user_list));
	
	echo '<form id="permissions-form" action="permissions.php" method="post">';
	
	foreach ($blogs as $b)
	{
		echo '<h3><a href="blog.php?id='.html::escapeHTML($b).'">'.html::escapeHTML($b).'</a>'.
		form::hidden(array('blog_id[]'),$b).'</h3>';
		
		foreach ($core->auth->getPermissionsTypes() as $perm_id => $perm)
		{
			$checked = false;
			
			if (count($users) == 1) {
				$checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
			}
			
			echo
			'<p><label for="perm'.html::escapeHTML($b).html::escapeHTML($perm_id).'" class="classic">'.
			form::checkbox(array('perm['.html::escapeHTML($b).']['.html::escapeHTML($perm_id).']','perm'.html::escapeHTML($b).html::escapeHTML($perm_id)),
			1,$checked).' '.
			__($perm).'</label></p>';
		}
	}
	
	echo
	'<fieldset><legend>'.__('Validate permissions').'</legend>'.
	'<p><label for="your_pwd">'.__('Your password:').
	form::password('your_pwd',20,255).'</label></p>'.
	'</fieldset>'.
	'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
	$core->formNonce();
	
	foreach ($users as $u) {
		echo form::hidden(array('user_id[]'),$u);
	}
	
	echo	form::hidden('upd_perm',1).'</p></form>';
}

dcPage::close();
?>