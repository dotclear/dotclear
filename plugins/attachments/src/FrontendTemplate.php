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
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use ArrayObject;
use dcCore;
use Dotclear\Core\Frontend\Tpl;
use Dotclear\Helper\File\Files;

class FrontendTemplate
{
    /**
     * tpl:Attachments [attributes] : Post Attachments loop (tpl block)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function Attachments(ArrayObject $attr, string $content): string
    {
        return
            '<?php' . "\n" .
            'if (dcCore::app()->ctx->posts !== null && dcCore::app()->media) {' . "\n" .
            '    dcCore::app()->ctx->attachments = new ArrayObject(dcCore::app()->media->getPostMedia(dcCore::app()->ctx->posts->post_id,null,"attachment"));' . "\n" .
            '    foreach (dcCore::app()->ctx->attachments as $attach_i => $attach_f) : ' .
            '        dcCore::app()->ctx->file_url = $attach_f->file_url;' . "\n" .
            '?>' . "\n" .
            $content .
            '<?php' . "\n" .
            '    endforeach;' . "\n" .
            '    dcCore::app()->ctx->attachments = null;' . "\n" .
            '    unset($attach_i,$attach_f,dcCore::app()->ctx->file_url);' . "\n" .
            '}' . "\n" .
            '?>' . "\n";
    }

    /**
     * tpl:AttachmentsHeader : First attachments result container (tpl block)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function AttachmentsHeader(ArrayObject $attr, string $content): string
    {
        return
            '<?php if ($attach_i == 0) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /**
     * tpl:AttachmentsFooter : Last attachments result container (tpl block)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function AttachmentsFooter(ArrayObject $attr, string $content): string
    {
        return
            '<?php if ($attach_i+1 == count(dcCore::app()->ctx->attachments)) : ?>' .
            $content .
            '<?php endif; ?>';
    }

    /**
     * tpl:AttachmentIf [attributes] : Include content if attachment tests is true (tpl block)
     *
     * attributes:
     *
     *      - is_image        (0|1)   Attachment is an image (if 1), or not (if 0)
     *      - has_thumb       (0|1)   Attachment has a square thumbnail (if 1), or not (if 0)
     *      - is_mp3          (0|1)   Attachment is a mp3 audio file (if 1), or not (if 0)
     *      - is_flv          (0|1)   Attachment is a flv file (if 1), or not (if 0)
     *      - is_audio        (0|1)   Attachment is an audio file (if 1), or not (if 0)
     *      - is_video        (0|1)   Attachment is a video file (if 1), or not (if 0)
     *
     * @param      ArrayObject    $attr     The attributes
     * @param      string         $content  The content
     *
     * @return     string
     */
    public static function AttachmentIf(ArrayObject $attr, string $content): string
    {
        $if = [];

        $operator = isset($attr['operator']) ? Tpl::getOperator($attr['operator']) : '&&';

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

        if (count($if)) {
            return '<?php if(' . implode(' ' . $operator . ' ', (array) $if) . ') : ?>' . $content . '<?php endif; ?>';
        }

        return $content;
    }

    /**
     * tpl:AttachmentMimeType [attributes] : Attachment MIME type (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentMimeType(ArrayObject $attr): string
    {
        return '<?php echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$attach_f->type') . '; ?>';
    }

    /**
     * tpl:AttachmentType [attributes] : Attachment type (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentType(ArrayObject $attr): string
    {
        return '<?php echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$attach_f->media_type') . '; ?>';
    }

    /**
     * tpl:AttachmentFileName [attributes] : Attachment file name (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentFileName(ArrayObject $attr): string
    {
        return '<?php echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$attach_f->basename') . '; ?>';
    }

    /**
     * tpl:AttachmentSize [attributes] : Attachment size (tpl value)
     *
     * attributes:
     *
     *      - full            (0|1)   Display size rounded to a human-readable value (in KB, MB, GB, TB)
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentSize(ArrayObject $attr): string
    {
        $f = dcCore::app()->public->tpl->getFilters($attr);
        if (!empty($attr['full'])) {
            return '<?php echo ' . sprintf($f, '$attach_f->size') . '; ?>';
        }

        return '<?php echo ' . sprintf($f, Files::class . '::size($attach_f->size)') . '; ?>';
    }

    /**
     * tpl:AttachmentTitle [attributes] : Attachment title (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentTitle(ArrayObject $attr): string
    {
        return '<?php echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$attach_f->media_title') . '; ?>';
    }

    /**
     * tpl:AttachmentThumbnailURL [attributes] : Attachment square thumbnail URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentThumbnailURL(ArrayObject $attr): string
    {
        return
        '<?php ' . "\n" .
        'if (isset($attach_f->media_thumb[\'sq\'])) {' . "\n" .
        '    $url = $attach_f->media_thumb[\'sq\'];' . "\n" .
        '    if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '        $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '    }' . "\n" .
        '    echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$url') . ';' . "\n" .
        '}' . "\n" .
        '?>';
    }

    /**
     * tpl:AttachmentURL [attributes] : Attachment URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function AttachmentURL(ArrayObject $attr): string
    {
        return
        '<?php ' . "\n" .
        '$url = $attach_f->file_url;' . "\n" .
        'if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '    $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '}' . "\n" .
        'echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$url') . ';' . "\n" .
        '?>';
    }

    /**
     * tpl:MediaURL [attributes] : Context file URL (tpl value)
     *
     * attributes:
     *
     *      - any filters     See Tpl::getFilters()
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function MediaURL(ArrayObject $attr): string
    {
        return
        '<?php ' . "\n" .
        '$url = dcCore::app()->ctx->file_url;' . "\n" .
        'if (substr($url, 0, strlen(dcCore::app()->blog->host)) === dcCore::app()->blog->host) {' . "\n" .
        '    $url = substr($url, strlen(dcCore::app()->blog->host));' . "\n" .
        '}' . "\n" .
        'echo ' . sprintf(dcCore::app()->public->tpl->getFilters($attr), '$url') . ';' . "\n" .
        '?>';
    }

    /**
     * tpl:EntryAttachmentCount [attributes] : Number of attachments for entry (tpl value)
     *
     * attributes:
     *
     *      - none        string      Text to display for "no attachments" (default: no attachments)
     *      - one         string      Text to display for "one attachment" (default: one attachment)
     *      - more        string      Text to display for "more attachments" (default: %s attachments, see note 1)
     *
     *      Notes:
     *
     *      1) %s will be replaced by the number of attachments
     *
     * @param      ArrayObject    $attr     The attributes
     *
     * @return     string
     */
    public static function EntryAttachmentCount(ArrayObject $attr): string
    {
        return dcCore::app()->public->tpl->displayCounter(
            'dcCore::app()->ctx->posts->countMedia(\'attachment\')',
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
