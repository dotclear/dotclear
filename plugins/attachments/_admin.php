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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior ('adminPostFormSidebar',array('attachmentAdmin','adminPostFormSidebar'));
$core->addBehavior ('adminPostForm',array('attachmentAdmin','adminPostForm'));

class attachmentAdmin
{
	public static function adminPostFormSidebar($post) 
	{
		if ($post !== null)
		{
			$core =& $GLOBALS['core'];
			$post_media = $core->media->getPostMedia($post->post_id);
			echo
			'<h3 class="clear">'.__('Attachments').'</h3>';
			foreach ($post_media as $f)
			{
				$ftitle = $f->media_title;
				if (strlen($ftitle) > 18) {
					$ftitle = substr($ftitle,0,16).'...';
				}
				echo
				'<div class="media-item">'.
				'<a class="media-icon" href="media_item.php?id='.$f->media_id.'">'.
				'<img src="'.$f->media_icon.'" alt="" title="'.$f->basename.'" /></a>'.
				'<ul>'.
				'<li><a class="media-link" href="media_item.php?id='.$f->media_id.'"'.
				'title="'.$f->basename.'">'.$ftitle.'</a></li>'.
				'<li>'.$f->media_dtstr.'</li>'.
				'<li>'.files::size($f->size).' - '.
				'<a href="'.$f->file_url.'">'.__('open').'</a>'.'</li>'.
				
				'<li class="media-action"><a class="attachment-remove" id="attachment-'.$f->media_id.'" '.
				'href="post_media.php?post_id='.$post->post_id.'&amp;media_id='.$f->media_id.'&amp;remove=1">'.
				'<img src="images/check-off.png" alt="'.__('remove').'" /></a>'.
				'</li>'.
				
				'</ul>'.
				'</div>';
			}
			unset($f);
			
			if (empty($post_media)) {
				echo '<p>'.__('No attachment.').'</p>';
			} else {
			}
			echo '<p><a class="button" href="media.php?post_id='.$post->post_id.'">'.__('Add files to this entry').'</a></p>';
		}
	}
	
	public static function adminPostForm($post) {
		if ($post !== null)
		{
			$core =& $GLOBALS['core'];
			echo
				'<form action="post_media.php" id="attachment-remove-hide" method="post">'.
				'<div>'.form::hidden(array('post_id'),$post->post_id).
				form::hidden(array('media_id'),'').
				form::hidden(array('remove'),1).
				$core->formNonce().'</div></form>';
		}
	}
}
?>