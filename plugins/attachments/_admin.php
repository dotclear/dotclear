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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminPostFormItems',array('attachmentAdmin','adminPostFormItems'));
$core->addBehavior('adminPostAfterForm',array('attachmentAdmin','adminPostAfterForm'));
$core->addBehavior('adminPostHeaders',array('attachmentAdmin','postHeaders'));
$core->addBehavior('adminPageFormItems',array('attachmentAdmin','adminPostFormItems'));
$core->addBehavior('adminPageAfterForm',array('attachmentAdmin','adminPostAfterForm'));
$core->addBehavior('adminPageHeaders',array('attachmentAdmin','postHeaders'));
$core->addBehavior('adminPageHelpBlock',array('attachmentAdmin','adminPageHelpBlock'));

class attachmentAdmin
{
	function adminPageHelpBlock($blocks)
	{
		$found = false;
		foreach($blocks as $block) {
			if ($block == 'core_post') {
				$found = true;
				break;
			}
		}
		if (!$found) {
			return null;
		}
		$blocks[] = 'attachments';
	}
	public static function postHeaders()
	{
		return 
		'<script type="text/javascript" src="index.php?pf=attachments/js/post.js"></script>';
	}
	public static function adminPostFormItems($main,$sidebar,$post) 
	{
		if ($post !== null)
		{
			$core =& $GLOBALS['core'];
			$post_media = $core->media->getPostMedia($post->post_id);
			$nb_media = count($post_media);
			$title = !$nb_media ? __('Attachments') : sprintf(__('Attachments (%d)'),$nb_media);
			$item = '<h5 class="clear s-attachments">'.$title.'</h5>';
			foreach ($post_media as $f)
			{
				$ftitle = $f->media_title;
				if (strlen($ftitle) > 18) {
					$ftitle = substr($ftitle,0,16).'...';
				}
				$item .=
				'<div class="media-item s-attachments">'.
				'<a class="media-icon" href="media_item.php?id='.$f->media_id.'">'.
				'<img src="'.$f->media_icon.'" alt="" title="'.$f->basename.'" /></a>'.
				'<ul>'.
				'<li><a class="media-link" href="media_item.php?id='.$f->media_id.'" '.
				'title="'.$f->basename.'">'.$ftitle.'</a></li>'.
				'<li>'.$f->media_dtstr.'</li>'.
				'<li>'.files::size($f->size).' - '.
				'<a href="'.$f->file_url.'">'.__('open').'</a>'.'</li>'.
				
				'<li class="media-action"><a class="attachment-remove" id="attachment-'.$f->media_id.'" '.
				'href="post_media.php?post_id='.$post->post_id.'&amp;media_id='.$f->media_id.'&amp;remove=1">'.
				'<img src="images/trash.png" alt="'.__('remove').'" /></a>'.
				'</li>'.
				
				'</ul>'.
				'</div>';
			}
			unset($f);
			
			if (empty($post_media)) {
				$item .= '<p class="form-note s-attachments">'.__('No attachment.').'</p>';
			} 
			$item .= '<p class="s-attachments"><a class="button" href="media.php?post_id='.$post->post_id.'">'.__('Add files to this entry').'</a></p>';
			$sidebar['metas-box']['items']['attachments']= $item;
		}
	}
	
	public static function adminPostAfterForm($post) {
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
