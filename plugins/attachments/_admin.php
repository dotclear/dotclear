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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$core->addBehavior('adminPostFormItems', array('attachmentAdmin', 'adminPostFormItems'));
$core->addBehavior('adminPostAfterForm', array('attachmentAdmin', 'adminPostAfterForm'));
$core->addBehavior('adminPostHeaders', array('attachmentAdmin', 'postHeaders'));
$core->addBehavior('adminPageFormItems', array('attachmentAdmin', 'adminPostFormItems'));
$core->addBehavior('adminPageAfterForm', array('attachmentAdmin', 'adminPostAfterForm'));
$core->addBehavior('adminPageHeaders', array('attachmentAdmin', 'postHeaders'));
$core->addBehavior('adminPageHelpBlock', array('attachmentAdmin', 'adminPageHelpBlock'));

class attachmentAdmin
{
    public static function adminPageHelpBlock($blocks)
    {
        $found = false;
        foreach ($blocks as $block) {
            if ($block == 'core_post') {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return;
        }
        $blocks[] = 'attachments';
    }
    public static function postHeaders()
    {
        $core = &$GLOBALS['core'];
        return dcPage::jsLoad(dcPage::getPF('attachments/js/post.js'));
    }
    public static function adminPostFormItems($main, $sidebar, $post)
    {
        if ($post !== null) {
            $core       = &$GLOBALS['core'];
            $post_media = $core->media->getPostMedia($post->post_id, null, 'attachment');
            $nb_media   = count($post_media);
            $title      = !$nb_media ? __('Attachments') : sprintf(__('Attachments (%d)'), $nb_media);
            $item       = '<h5 class="clear s-attachments">' . $title . '</h5>';
            foreach ($post_media as $f) {
                $ftitle = $f->media_title;
                if (strlen($ftitle) > 18) {
                    $ftitle = substr($ftitle, 0, 16) . '...';
                }
                $item .=
                '<div class="media-item s-attachments">' .
                '<a class="media-icon" href="' . $core->adminurl->get('admin.media.item', array('id' => $f->media_id)) . '">' .
                '<img src="' . $f->media_icon . '" alt="" title="' . $f->basename . '" /></a>' .
                '<ul>' .
                '<li><a class="media-link" href="' . $core->adminurl->get('admin.media.item', array('id' => $f->media_id)) . '" ' .
                'title="' . $f->basename . '">' . $ftitle . '</a></li>' .
                '<li>' . $f->media_dtstr . '</li>' .
                '<li>' . files::size($f->size) . ' - ' .
                '<a href="' . $f->file_url . '">' . __('open') . '</a>' . '</li>' .

                '<li class="media-action"><a class="attachment-remove" id="attachment-' . $f->media_id . '" ' .
                'href="' . $core->adminurl->get('admin.post.media', array(
                    'post_id'   => $post->post_id,
                    'media_id'  => $f->media_id,
                    'link_type' => 'attachment',
                    'remove'    => '1'
                )) . '">' .
                '<img src="images/trash.png" alt="' . __('remove') . '" /></a>' .
                    '</li>' .

                    '</ul>' .
                    '</div>';
            }
            unset($f);

            if (empty($post_media)) {
                $item .= '<p class="form-note s-attachments">' . __('No attachment.') . '</p>';
            }
            $item .=
            '<p class="s-attachments"><a class="button" href="' . $core->adminurl->get('admin.media', array('post_id' => $post->post_id, 'link_type' => 'attachment')) . '">' .
            __('Add files to this entry') . '</a></p>';
            $sidebar['metas-box']['items']['attachments'] = $item;
        }
    }

    public static function adminPostAfterForm($post)
    {
        if ($post !== null) {
            $core = &$GLOBALS['core'];
            echo
            '<form action="' . $core->adminurl->get('admin.post.media') . '" id="attachment-remove-hide" method="post">' .
            '<div>' . form::hidden(array('post_id'), $post->post_id) .
            form::hidden(array('media_id'), '') .
            form::hidden(array('link_type'), 'attachment') .
            form::hidden(array('remove'), 1) .
            $core->formNonce() . '</div></form>';
        }
    }
}
