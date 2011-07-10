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

$post_id = !empty($_REQUEST['post_id']) ? (integer) $_REQUEST['post_id'] : null;
$media_id = !empty($_REQUEST['media_id']) ? (integer) $_REQUEST['media_id'] : null;

if (!$post_id) {
	exit;
}
$rs = $core->blog->getPosts(array('post_id' => $post_id,'post_type'=>''));
if ($rs->isEmpty()) {
	exit;
}

if ($post_id && $media_id && !empty($_POST['attach']))
{
	$core->media = new dcMedia($core);
	$core->media->addPostMedia($post_id,$media_id);
	http::redirect($core->getPostAdminURL($rs->post_type,$post_id,false));
}

try {
	$core->media = new dcMedia($core);
	$f = $core->media->getPostMedia($post_id,$media_id);
	if (empty($f)) {
		$post_id = $media_id = null;
		throw new Exception(__('This attachment does not exist'));
	}
	$f = $f[0];
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Remove a media from en
if (($post_id && $media_id) || $core->error->flag())
{
	if (!empty($_POST['remove']))
	{
		$core->media->removePostMedia($post_id,$media_id);
		http::redirect($core->getPostAdminURL($rs->post_type,$post_id,false).'&rmattach=1');
	}
	elseif (isset($_POST['post_id'])) {
		http::redirect($core->getPostAdminURL($rs->post_type,$post_id,false));
	}
	
	if (!empty($_GET['remove']))
	{
		dcPage::open(__('Remove attachment'));
		
		echo '<h2>'.__('Attachment').' &rsaquo; <span class="page-title">'.__('confirm removal').'</span></h2>';
		
		echo
		'<form action="post_media.php" method="post">'.
		'<p>'.__('Are you sure you want to remove this attachment?').'</p>'.
		'<p><input type="submit" class="reset" value="'.__('Cancel').'" /> '.
		' &nbsp; <input type="submit" class="delete" name="remove" value="'.__('Yes').'" />'.
		form::hidden('post_id',$post_id).
		form::hidden('media_id',$media_id).
		$core->formNonce().'</p>'.
		'</form>';
		
		dcPage::close();
		exit;
	}
}
?>