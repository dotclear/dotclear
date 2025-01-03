<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\attachments;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;

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
        if (in_array('core_post', $blocks->getArrayCopy(), true)) {
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
        if ($post instanceof MetaRecord) {
            // Entry saved at least once
            $post_media = App::media()->getPostMedia((int) $post->post_id, null, 'attachment');
            $nb_media   = count($post_media);

            $rows = [];
            foreach ($post_media as $file) {
                $ftitle = $file->media_title;
                if (strlen($ftitle) > 18) {
                    $ftitle = substr($ftitle, 0, 16) . '...';
                }
                $rows[] = (new Div())->class(['media-item', 's-attachments'])->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.media.item', ['id' => $file->media_id]))
                        ->title($file->basename)
                        ->items([
                            (new Img($file->media_icon)),
                        ]),
                    (new Ul())->items([
                        (new Li())->items([
                            (new Link())
                                ->class('media-link')
                                ->href(App::backend()->url()->get('admin.media.item', ['id' => $file->media_id]))
                                ->title($file->basename)
                                ->text($ftitle),
                        ]),
                        (new Li())->text($file->media_dtstr),
                        (new Li())->items([
                            (new Text(null, Files::size($file->size) . ' - ')),
                            (new Link())->href($file->file_url)->text(__('open')),
                        ]),
                        (new Li())->class('media-action')->items([
                            (new Link('attachment-' . $file->media_id))
                                ->class('attachment-remove')
                                ->href(App::backend()->url()->get('admin.post.media', [
                                    'post_id'   => $post->post_id,
                                    'media_id'  => $file->media_id,
                                    'link_type' => 'attachment',
                                    'remove'    => '1',
                                ]))
                                ->items([
                                    (new Img('images/trash.svg'))->alt(__('remove')),
                                ]),
                        ]),
                    ]),
                ]);
            }

            if ($rows === []) {
                $rows = [
                    (new Para())->class(['form-note', 's-attachments'])->items([
                        (new Text(null, __('No attachment.'))),
                    ]),
                ];
            }

            $title = $nb_media === 0 ? __('Attachments') : sprintf(__('Attachments (%d)'), $nb_media);

            $item = (new Set())->items([
                (new Text('h5', $title))->class(['clear', 's-attachments']),
                ...$rows,
                (new Para())->class('s-attachments')->items([
                    (new Link())
                        ->class('button')
                        ->href(App::backend()->url()->get('admin.media', ['post_id' => $post->post_id, 'link_type' => 'attachment']))
                        ->text(__('Add files to this entry')),
                ]),
            ]);
        } else {
            // Entry still not saved
            $item = (new Set())->items([
                (new Text('h5', __('Attachments')))->class(['clear', 's-attachments']),

                (new Para())->class(['form-note', 's-attachments'])->items([
                    (new Text(null, __('You must save the entry before adding an attachment.'))),
                ]),
            ]);
        }

        $sidebar['metas-box']['items']['attachments'] = $item->render();
    }

    /**
     * Add attchment remove form template.
     *
     * @param   MetaRecord  $post   The post
     */
    public static function adminPostAfterForm(?MetaRecord $post): void
    {
        if ($post instanceof MetaRecord) {
            echo (new Form('attachment-remove-hide'))
                ->action(App::backend()->url()->get('admin.post.media'))
                ->method('post')
                ->fields([
                    (new Div())->items([
                        (new Hidden(['post_id'], (string) $post->post_id)),
                        (new Hidden(['media_id'], '')),
                        (new Hidden(['link_type'], 'attachment')),
                        (new Hidden(['remove'], '1')),
                        App::nonce()->formNonce(),
                    ]),
                ])
            ->render();
        }
    }
}
