<?php
/**
 * @brief attachments, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

# Attachments
dcCore::app()->tpl->addBlock('Attachments', ['attachmentTpl', 'Attachments']);
dcCore::app()->tpl->addBlock('AttachmentsHeader', ['attachmentTpl', 'AttachmentsHeader']);
dcCore::app()->tpl->addBlock('AttachmentsFooter', ['attachmentTpl', 'AttachmentsFooter']);
dcCore::app()->tpl->addValue('AttachmentMimeType', ['attachmentTpl', 'AttachmentMimeType']);
dcCore::app()->tpl->addValue('AttachmentType', ['attachmentTpl', 'AttachmentType']);
dcCore::app()->tpl->addValue('AttachmentFileName', ['attachmentTpl', 'AttachmentFileName']);
dcCore::app()->tpl->addValue('AttachmentSize', ['attachmentTpl', 'AttachmentSize']);
dcCore::app()->tpl->addValue('AttachmentTitle', ['attachmentTpl', 'AttachmentTitle']);
dcCore::app()->tpl->addValue('AttachmentThumbnailURL', ['attachmentTpl', 'AttachmentThumbnailURL']);
dcCore::app()->tpl->addValue('AttachmentURL', ['attachmentTpl', 'AttachmentURL']);
dcCore::app()->tpl->addValue('MediaURL', ['attachmentTpl', 'MediaURL']);
dcCore::app()->tpl->addBlock('AttachmentIf', ['attachmentTpl', 'AttachmentIf']);

dcCore::app()->tpl->addValue('EntryAttachmentCount', ['attachmentTpl', 'EntryAttachmentCount']);

dcCore::app()->addBehavior('tplIfConditions', ['attachmentBehavior', 'tplIfConditions']);

class attachmentTpl
{
    /*dtd
    <!ELEMENT tpl:Attachments - - -- Post Attachments loop -->
     */
    public static function Attachments($attr, $content)
    {
        $res = "<?php\n" .
            'if ($_ctx->posts !== null && dcCore::app()->media) {' . "\n" .
            '$_ctx->attachments = new ArrayObject(dcCore::app()->media->getPostMedia($_ctx->posts->post_id,null,"attachment"));' . "\n" .
            "?>\n" .

            '<?php foreach ($_ctx->attachments as $attach_i => $attach_f) : ' .
            '$GLOBALS[\'attach_i\'] = $attach_i; $GLOBALS[\'attach_f\'] = $attach_f;' .
            '$_ctx->file_url = $attach_f->file_url; ?>' .
            $content .
            '<?php endforeach; $_ctx->attachments = null; unset($attach_i,$attach_f,$_ctx->file_url); ?>' .

            "<?php } ?>\n";

        return $res;
    }

    /*dtd
    <!ELEMENT tpl:AttachmentsHeader - - -- First attachments result container -->
     */
    public static function AttachmentsHeader($attr, $content)
    {
        return
            '<?php if ($attach_i == 0) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentsFooter - - -- Last attachments result container -->
     */
    public static function AttachmentsFooter($attr, $content)
    {
        return
            '<?php if ($attach_i+1 == count($_ctx->attachments)) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentsIf - - -- Test on attachment fields -->
    <!ATTLIST tpl:AttachmentIf
    is_image    (0|1)    #IMPLIED    -- test if attachment is an image (value : 1) or not (value : 0)
    has_thumb    (0|1)    #IMPLIED    -- test if attachment has a square thumnail (value : 1) or not (value : 0)
    is_mp3    (0|1)    #IMPLIED    -- test if attachment is a mp3 file (value : 1) or not (value : 0)
    is_flv    (0|1)    #IMPLIED    -- test if attachment is a flv file (value : 1) or not (value : 0)
    is_audio    (0|1)    #IMPLIED    -- test if attachment is an audio file (value : 1) or not (value : 0)
    is_video    (0|1)    #IMPLIED    -- test if attachment is a video file (value : 1) or not (value : 0)
    >
     */
    public static function AttachmentIf($attr, $content)
    {
        $if = [];

        $operator = isset($attr['operator']) ? dcTemplate::getOperator($attr['operator']) : '&&';

        if (isset($attr['is_image'])) {
            $sign = (bool) $attr['is_image'] ? '' : '!';
            $if[] = $sign . '$attach_f->media_image';
        }

        if (isset($attr['has_thumb'])) {
            $sign = (bool) $attr['has_thumb'] ? '' : '!';
            $if[] = $sign . 'isset($attach_f->media_thumb[\'sq\'])';
        }

        if (isset($attr['is_mp3'])) {
            $sign = (bool) $attr['is_mp3'] ? '==' : '!=';
            $if[] = '$attach_f->type ' . $sign . ' "audio/mpeg3"';
        }

        if (isset($attr['is_flv'])) {
            $sign = (bool) $attr['is_flv'] ? '==' : '!=';
            $if[] = '$attach_f->type ' . $sign . ' "video/x-flv"';
        }

        if (isset($attr['is_audio'])) {
            $sign = (bool) $attr['is_audio'] ? '==' : '!=';
            $if[] = '$attach_f->type_prefix ' . $sign . ' "audio"';
        }

        if (isset($attr['is_video'])) {
            // Since 2.15 .flv media are no more considered as video (Flash is obsolete)
            $sign = (bool) $attr['is_video'] ? '==' : '!=';
            $test = '$attach_f->type_prefix ' . $sign . ' "video"';
            if ($sign == '==') {
                $test .= ' && $attach_f->type != "video/x-flv"';
            } else {
                $test .= ' || $attach_f->type == "video/x-flv"';
            }
            $if[] = $test;
        }

        if (count($if) != 0) {
            return '<?php if(' . implode(' ' . $operator . ' ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /*dtd
    <!ELEMENT tpl:AttachmentMimeType - O -- Attachment MIME Type -->
     */
    public static function AttachmentMimeType($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, '$attach_f->type') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentType - O -- Attachment type -->
     */
    public static function AttachmentType($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, '$attach_f->media_type') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentFileName - O -- Attachment file name -->
     */
    public static function AttachmentFileName($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, '$attach_f->basename') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentSize - O -- Attachment size -->
    <!ATTLIST tpl:AttachmentSize
    full    CDATA    #IMPLIED    -- if set, size is rounded to a human-readable value (in KB, MB, GB, TB)
    >
     */
    public static function AttachmentSize($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);
        if (!empty($attr['full'])) {
            return '<?php echo ' . sprintf($f, '$attach_f->size') . '; ?>';
        }

        return '<?php echo ' . sprintf($f, 'files::size($attach_f->size)') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentTitle - O -- Attachment title -->
     */
    public static function AttachmentTitle($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return '<?php echo ' . sprintf($f, '$attach_f->media_title') . '; ?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentThumbnailURL - O -- Attachment square thumbnail URL -->
     */
    public static function AttachmentThumbnailURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return
        '<?php ' . "\n" .
        'if (isset($attach_f->media_thumb[\'sq\'])) {' . "\n" .
        '   $url = $attach_f->media_thumb[\'sq\']);' . "\n" .
        '   if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '       $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '   }' . "\n" .
        '   echo ' . sprintf($f, '$url') . ';' . "\n" .
        '}' .
        '?>';
    }

    /*dtd
    <!ELEMENT tpl:AttachmentURL - O -- Attachment URL -->
     */
    public static function AttachmentURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return
        '<?php ' . "\n" .
        '$url = $attach_f->file_url;' . "\n" .
        'if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '   $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '}' . "\n" .
        'echo ' . sprintf($f, '$url') . ';' . "\n" .
        '?>';
    }

    public static function MediaURL($attr)
    {
        $f = dcCore::app()->tpl->getFilters($attr);

        return
        '<?php ' . "\n" .
        '$url = $_ctx->file_url;' . "\n" .
        'if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '   $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '}' . "\n" .
        'echo ' . sprintf($f, '$url') . ';' . "\n" .
        '?>';
    }

    /*dtd
    <!ELEMENT tpl:EntryAttachmentCount - O -- Number of attachments for entry -->
    <!ATTLIST tpl:EntryAttachmentCount
    none    CDATA    #IMPLIED    -- text to display for "no attachments" (default: no attachments)
    one    CDATA    #IMPLIED    -- text to display for "one attachment" (default: one attachment)
    more    CDATA    #IMPLIED    -- text to display for "more attachment" (default: %s attachment, %s is replaced by the number of attachments)
    >
     */
    public static function EntryAttachmentCount($attr)
    {
        return dcCore::app()->tpl->displayCounter(
            '$_ctx->posts->countMedia(\'attachment\')',
            [
                'none' => 'no attachments',
                'one'  => 'one attachment',
                'more' => '%d attachments',
            ],
            $attr,
            false
        );
    }
}

class attachmentBehavior
{
    public static function tplIfConditions($tag, $attr, $content, $if)
    {
        if ($tag == 'EntryIf' && isset($attr['has_attachment'])) {
            $sign = (bool) $attr['has_attachment'] ? '' : '!';
            $if[] = $sign . '$_ctx->posts->countMedia(\'attachment\')';
        }
    }
}
