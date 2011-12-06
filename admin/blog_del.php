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

$blog_id = '';
$blog_name = '';

if (!empty($_POST['blog_id']))
{
	try {
		$rs = $core->getBlog($_POST['blog_id']);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
	
	if ($rs->isEmpty()) {
		$core->error->add(__('No such blog ID'));
	} else {
		$blog_id = $rs->blog_id;
		$blog_name = $rs->blog_name;
	}
}

# Delete the blog
if (!$core->error->flag() && $blog_id && !empty($_POST['del']))
{
	if (!$core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['pwd']))) {
		$core->error->add(__('Password verification failed'));
	} else {
		try {
			$core->delBlog($blog_id);
			http::redirect('blogs.php?del=1');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}

dcPage::open('Delete a blog');

if (!$core->error->flag())
{
	echo
	'<h2 class="page-title">'.__('Delete a blog').'</h2>'.
	'<p class="message">'.__('Warning').'</p>'.
	'<p>'.sprintf(__('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
	'<strong>'.$blog_id.' ('.$blog_name.')</strong>').'</p>'.
	'<p>'.__('Please give your password to confirm the blog deletion.').'</p>';
	
	echo
	'<form action="blog_del.php" method="post">'.
	'<div>'.$core->formNonce().'</div>'.
	'<p><label for="pwd">'.__('Your password:').' '.
	form::password('pwd',20,255).'</label></p>'.
	'<p><input type="submit" class="delete" name="del" value="'.__('Delete this blog').'" />'.
	form::hidden('blog_id',$blog_id).'</p>'.
	'</form>';
}

dcPage::close();
?>