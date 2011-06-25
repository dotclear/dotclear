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
if (!defined('DC_RC_PATH')) { return; }

# Attachments
$core->tpl->addBlock('Attachments',array('attachmentTpl','Attachments'));
$core->tpl->addBlock('AttachmentsHeader',array('attachmentTpl','AttachmentsHeader'));
$core->tpl->addBlock('AttachmentsFooter',array('attachmentTpl','AttachmentsFooter'));
$core->tpl->addValue('AttachmentMimeType',array('attachmentTpl','AttachmentMimeType'));
$core->tpl->addValue('AttachmentType',array('attachmentTpl','AttachmentType'));
$core->tpl->addValue('AttachmentFileName',array('attachmentTpl','AttachmentFileName'));
$core->tpl->addValue('AttachmentSize',array('attachmentTpl','AttachmentSize'));
$core->tpl->addValue('AttachmentTitle',array('attachmentTpl','AttachmentTitle'));
$core->tpl->addValue('AttachmentThumbnailURL',array('attachmentTpl','AttachmentThumbnailURL'));
$core->tpl->addValue('AttachmentURL',array('attachmentTpl','AttachmentURL'));
$core->tpl->addValue('MediaURL',array('attachmentTpl','MediaURL'));
$core->tpl->addBlock('AttachmentIf',array('attachmentTpl','AttachmentIf'));

$core->tpl->addValue('EntryAttachmentCount',array('attachmentTpl','EntryAttachmentCount'));

$core->addBehavior('tplIfConditions',array('attachmentBehavior','tplIfConditions'));

class attachmentTpl {
	
	/*dtd
	<!ELEMENT tpl:Attachments - - -- Post Attachments loop -->
	*/
	public static function Attachments($attr,$content)
	{
		$res =
		"<?php\n".
		'if ($_ctx->posts !== null && $core->media) {'."\n".
			'$_ctx->attachments = new ArrayObject($core->media->getPostMedia($_ctx->posts->post_id));'."\n".
		"?>\n".
		
		'<?php foreach ($_ctx->attachments as $attach_i => $attach_f) : '.
		'$GLOBALS[\'attach_i\'] = $attach_i; $GLOBALS[\'attach_f\'] = $attach_f;'.
		'$_ctx->file_url = $attach_f->file_url; ?>'.
		$content.
		'<?php endforeach; $_ctx->attachments = null; unset($attach_i,$attach_f,$_ctx->file_url); ?>'.
		
		"<?php } ?>\n";
		
		return $res;
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentsHeader - - -- First attachments result container -->
	*/
	public static function AttachmentsHeader($attr,$content)
	{
		return
		"<?php if (\$attach_i == 0) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentsFooter - - -- Last attachments result container -->
	*/
	public static function AttachmentsFooter($attr,$content)
	{
		return
		"<?php if (\$attach_i+1 == count(\$_ctx->attachments)) : ?>".
		$content.
		"<?php endif; ?>";
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentsIf - - -- Test on attachment fields -->
	<!ATTLIST tpl:AttachmentIf
	is_image	(0|1)	#IMPLIED	-- test if attachment is an image (value : 1) or not (value : 0)
	has_thumb	(0|1)	#IMPLIED	-- test if attachment has a square thumnail (value : 1) or not (value : 0)
	is_mp3	(0|1)	#IMPLIED	-- test if attachment is a mp3 file (value : 1) or not (value : 0)
	is_flv	(0|1)	#IMPLIED	-- test if attachment is a flv file (value : 1) or not (value : 0)
	>
	*/
	public static function AttachmentIf($attr,$content)
	{
		$if = array();
		
		$operator = isset($attr['operator']) ? $this->getOperator($attr['operator']) : '&&';
		
		if (isset($attr['is_image'])) {
			$sign = (boolean) $attr['is_image'] ? '' : '!';
			$if[] = $sign.'$attach_f->media_image';
		}
		
		if (isset($attr['has_thumb'])) {
			$sign = (boolean) $attr['has_thumb'] ? '' : '!';
			$if[] = $sign.'isset($attach_f->media_thumb[\'sq\'])';
		}
		
		if (isset($attr['is_mp3'])) {
			$sign = (boolean) $attr['is_mp3'] ? '==' : '!=';
			$if[] = '$attach_f->type '.$sign.' "audio/mpeg3"';
		}
		
		if (isset($attr['is_flv'])) {
			$sign = (boolean) $attr['is_flv'] ? '' : '!';
			$if[] = $sign.
				'($attach_f->type == "video/x-flv" || '.
				'$attach_f->type == "video/mp4" || '.
				'$attach_f->type == "video/x-m4v")';
		}
		
		if (!empty($if)) {
			return '<?php if('.implode(' '.$operator.' ', (array) $if).') : ?>'.$content.'<?php endif; ?>';
		} else {
			return $content;
		}
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentMimeType - O -- Attachment MIME Type -->
	*/
	public static function AttachmentMimeType($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$attach_f->type').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentType - O -- Attachment type -->
	*/
	public static function AttachmentType($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$attach_f->media_type').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentFileName - O -- Attachment file name -->
	*/
	public static function AttachmentFileName($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$attach_f->basename').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentSize - O -- Attachment size -->
	<!ATTLIST tpl:AttachmentSize
	full	CDATA	#IMPLIED	-- if set, size is rounded to a human-readable value (in KB, MB, GB, TB)
	>
	*/
	public static function AttachmentSize($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		if (!empty($attr['full'])) {
			return '<?php echo '.sprintf($f,'$attach_f->size').'; ?>';
		}
		return '<?php echo '.sprintf($f,'files::size($attach_f->size)').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentTitle - O -- Attachment title -->
	*/
	public static function AttachmentTitle($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$attach_f->media_title').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentThumbnailURL - O -- Attachment square thumbnail URL -->
	*/
	public static function AttachmentThumbnailURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return
		'<?php '.
		'if (isset($attach_f->media_thumb[\'sq\'])) {'.
			'echo '.sprintf($f,'$attach_f->media_thumb[\'sq\']').';'.
		'}'.
		'?>';
	}
	
	/*dtd
	<!ELEMENT tpl:AttachmentURL - O -- Attachment URL -->
	*/
	public static function AttachmentURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$attach_f->file_url').'; ?>';
	}
	
	public function MediaURL($attr)
	{
		$f = $GLOBALS['core']->tpl->getFilters($attr);
		return '<?php echo '.sprintf($f,'$_ctx->file_url').'; ?>';
	}
	
	/*dtd
	<!ELEMENT tpl:EntryAttachmentCount - O -- Number of attachments for entry -->
	<!ATTLIST tpl:EntryAttachmentCount
	none	CDATA	#IMPLIED	-- text to display for "no attachment" (default: no attachment)
	one	CDATA	#IMPLIED	-- text to display for "one attachment" (default: one attachment)
	more	CDATA	#IMPLIED	-- text to display for "more attachment" (default: %s attachment, %s is replaced by the number of attachments)
	>
	*/
	public function EntryAttachmentCount($attr)
	{
		$none = 'no attachment';
		$one = 'one attachment';
		$more = '%d attachments';
		
		if (isset($attr['none'])) {
			$none = addslashes($attr['none']);
		}
		if (isset($attr['one'])) {
			$one = addslashes($attr['one']);
		}
		if (isset($attr['more'])) {
			$more = addslashes($attr['more']);
		}
		
		return
		"<?php if (\$_ctx->posts->countMedia() == 0) {\n".
		"  printf(__('".$none."'),(integer) \$_ctx->posts->countMedia());\n".
		"} elseif (\$_ctx->posts->countMedia() == 1) {\n".
		"  printf(__('".$one."'),(integer) \$_ctx->posts->countMedia());\n".
		"} else {\n".
		"  printf(__('".$more."'),(integer) \$_ctx->posts->countMedia());\n".
		"} ?>";
	}
}

class attachmentBehavior
{
	public static function tplIfConditions($tag,$attr,$content,$if)
	{
		if ($tag == "EntryIf" && isset($attr['has_attachment'])) {
			$sign = (boolean) $attr['has_attachment'] ? '' : '!';
			$if[] = $sign.'$_ctx->posts->countMedia()';
		}
	}
}
?>