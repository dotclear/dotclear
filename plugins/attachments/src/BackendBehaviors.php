<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\File\Files;
use form;

if (!App::task()->checkContext('BACKEND')) {
    return false;
}

/**
 * @brief   The module backend behaviors.
 * @ingroup attachments
 */
class BackendBehaviors
{
    /**
     * Add an attachments help ID if necessary.
     *
     * @param   ArrayObject<string, mixed>     $blocks     The blocks
     */
    public static function adminPageHelpBlock(ArrayObject $blocks): void
    {
        if (array_search('core_post', $blocks->getArrayCopy(), true) !== false) {
            $blocks->append('attachments');
        }
    }

    /**
     * Add attachment fieldset in entry sidebar.
     *
     * @param   ArrayObject<string, mixed>      $main       The main part of the entry form
     * @param   ArrayObject<string, mixed>      $sidebar    The sidebar part of the entry form
     * @param   MetaRecord                      $post       The post
     */
    public static function adminPostFormItems(ArrayObject $main, ArrayObject $sidebar, ?MetaRecord $post): void
    {
        if ($post !== null) {
            $post_media = App::media()->getPostMedia((int) $post->post_id, null, 'attachment');
            $nb_media   = is_countable($post_media) ? count($post_media) : 0;   // @phpstan-ignore-line
            $title      = !$nb_media ? __('Attachments') : sprintf(__('Attachments (%d)'), $nb_media);
            $item       = '<h5 class="clear s-attachments">' . $title . '</h5>';
            foreach ($post_media as $file) {
                $ftitle = $file->media_title;
                if (strlen($ftitle) > 18) {
                    $ftitle = substr($ftitle, 0, 16) . '...';
                }
                $item .= '<div class="media-item s-attachments">' .
                '<a class="media-icon" href="' . App::backend()->url->get('admin.media.item', ['id' => $file->media_id]) . '">' .
                '<img src="' . $file->media_icon . '" alt="" title="' . $file->basename . '" /></a>' .
                '<ul>' .
                '<li><a class="media-link" href="' . App::backend()->url->get('admin.media.item', ['id' => $file->media_id]) . '" ' .
                'title="' . $file->basename . '">' . $ftitle . '</a></li>' .
                '<li>' . $file->media_dtstr . '</li>' .
                '<li>' . Files::size($file->size) . ' - ' .
                '<a href="' . $file->file_url . '">' . __('open') . '</a>' . '</li>' .

                '<li class="media-action"><a class="attachment-remove" id="attachment-' . $file->media_id . '" ' .
                'href="' . App::backend()->url->get('admin.post.media', [
                    'post_id'   => $post->post_id,
                    'media_id'  => $file->media_id,
                    'link_type' => 'attachment',
                    'remove'    => '1',
                ]) . '">' .
                '<img src="images/trash.png" alt="' . __('remove') . '" /></a>' .
                    '</li>' .

                    '</ul>' .
                    '</div>';
            }

            if (empty($post_media)) {
                $item .= '<p class="form-note s-attachments">' . __('No attachment.') . '</p>';
            }
            $item .= '<p class="s-attachments"><a class="button" href="' . App::backend()->url->get('admin.media', ['post_id' => $post->post_id, 'link_type' => 'attachment']) . '">' .
            __('Add files to this entry') . '</a></p>';
            $sidebar['metas-box']['items']['attachments'] = $item;
        }
    }

    /**
     * Add attchment remove form template.
     *
     * @param   MetaRecord  $post   The post
     */
    public static function adminPostAfterForm(?MetaRecord $post): void
    {
        if ($post !== null) {
            echo
            '<form action="' . App::backend()->url->get('admin.post.media') . '" ' .
            'id="attachment-remove-hide" method="post">' .
            '<div>' .
            form::hidden(['post_id'], $post->post_id) .
            form::hidden(['media_id'], '') .
            form::hidden(['link_type'], 'attachment') .
            form::hidden(['remove'], 1) .
            App::nonce()->getFormNonce() .
            '</div>' .
            '</form>';
        }
    }
}
