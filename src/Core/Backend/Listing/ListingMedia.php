<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use ArrayObject;
use dcMedia;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use form;

class ListingMedia extends Listing
{
    /**
     * Display a media list
     *
     * @param      FilterMedia       $filters        The filters
     * @param      string            $enclose_block  The enclose block
     * @param      bool              $query          The query
     * @param      string            $page_adminurl  The page adminurl
     */
    public function display(FilterMedia $filters, string $enclose_block = '', $query = false, $page_adminurl = 'admin.media')
    {
        $nb_items   = $this->rs_count - ($filters->d ? 1 : 0);
        $nb_folders = $filters->d ? -1 : 0;

        if ($filters->q && !$query) {
            echo '<p><strong>' . __('No file matches the filter') . '</strong></p>';
        } elseif ($nb_items < 1) {
            echo '<p><strong>' . __('No file.') . '</strong></p>';
        }

        if ($this->rs_count && !($filters->q && !$query)) {
            $pager = new Pager($filters->page, $this->rs_count, $filters->nb, 10);

            $items = $this->rs->rows();
            foreach ($items as $item) {
                if (is_array($item)) {
                    // Convert array to object->properties (will then pretend to be like a File object)
                    $item = (object) $item;
                }
                if ($item->d) {
                    $nb_folders++;
                }
            }
            $nb_files = $nb_items - $nb_folders;

            if ($filters->show() && $query) {
                $caption = sprintf(__('%d file matches the filter.', '%d files match the filter.', $nb_items), $nb_items);
            } else {
                $caption = ($nb_files && $nb_folders ?
                    sprintf(__('Nb of items: %d → %d folder(s) + %d file(s)'), $nb_items, $nb_folders, $nb_files) :
                    sprintf(__('Nb of items: %d'), $nb_items));
            }

            $group = ['dirs' => [], 'files' => []];
            for ($index = $pager->index_start, $index_in_page = 0; $index <= $pager->index_end; $index++, $index_in_page++) {
                $item = $items[$index];
                if (is_array($item)) {
                    // Convert array to object->properties (will then pretend to be like a File object)
                    $item = (object) $item;
                }
                $group[$item->d ? 'dirs' : 'files'][] = static::mediaLine($filters, $items[$index], $index_in_page, $query, $page_adminurl);
            }

            if ($filters->file_mode == 'list') {
                $table = sprintf(
                    '<div class="table-outer">' .
                    '<table class="media-items-bloc">' .
                    '<caption>' . $caption . '</caption>' .
                    '<tr>' .
                    '<th colspan="2" class="first">' . __('Name') . '</th>' .
                    '<th scope="col">' . __('Date') . '</th>' .
                    '<th scope="col">' . __('Size') . '</th>' .
                    '</tr>%s%s</table></div>',
                    implode($group['dirs']),
                    implode($group['files'])
                );
                $html_block = sprintf($enclose_block, $table, '');
            } else {
                $html_block = sprintf(
                    '%s%s<div class="media-stats"><p class="form-stats">' . $caption . '</p></div>',
                    !empty($group['dirs']) ? '<div class="folders-group">' . implode($group['dirs']) . '</div>' : '',
                    sprintf($enclose_block, '<div class="media-items-bloc">' . implode($group['files']), '') . '</div>'
                );
            }

            echo $pager->getLinks();

            echo $html_block;

            echo $pager->getLinks();
        }
    }

    /**
     * Display a media item
     *
     * @param      FilterMedia       $filters        The filters
     * @param      File|array        $file           The media file
     * @param      int               $index          Current index in page
     * @param      bool              $query          The query
     * @param      string            $page_adminurl  The page adminurl
     *
     * @return     string            ( description_of_the_return_value )
     */
    public static function mediaLine(FilterMedia $filters, $file, int $index, bool $query = false, string $page_adminurl = 'admin.media'): string
    {
        if (is_array($file)) {
            // Convert array to object->properties (will then pretend to be like a File object)
            $file = (object) $file;
        }

        $display_name = $file->basename;
        $filename     = $query ? $file->relname : $file->basename;

        $class = 'media-item-bloc'; // cope with js message for grid AND list
        $class .= $filters->file_mode == 'list' ? '' : ' media-item media-col-' . ($index % 2);

        if ($file->d) {
            // Folder
            $link = App::backend()->url->get('admin.media', array_merge($filters->values(), ['d' => Html::sanitizeURL($file->relname)]));
            if ($file->parent) {
                $display_name = '..';
                $class .= ' media-folder-up';
            } else {
                $class .= ' media-folder';
            }
        } else {
            // Item
            $params = new ArrayObject(array_merge($filters->values(), ['id' => $file->media_id]));
            unset($params['process']); // move to media item

            # --BEHAVIOR-- adminMediaURLParams -- ArrayObject
            App::behavior()->callBehavior('adminMediaURLParams', $params);

            $link = App::backend()->url->get('admin.media.item', (array) $params);
            if ($file->media_priv) {
                $class .= ' media-private';
            }
        }

        $maxchars = 34; // cope with design
        if (strlen($display_name) > $maxchars) {
            $display_name = substr($display_name, 0, $maxchars - 4) . '...' . ($file->d ? '' : Files::getExtension($display_name));
        }

        $act = '';
        if (!$file->d) {
            if ($filters->select > 0) {
                if ($filters->select == 1) {
                    // Single media selection button
                    $act .= '<a href="' . $link . '"><img src="images/plus.png" alt="' . __('Select this file') . '" ' .
                    'title="' . __('Select this file') . '" /></a> ';
                } else {
                    // Multiple media selection checkbox
                    $act .= form::checkbox(['medias[]', 'media_' . rawurlencode($filename)], $filename);
                }
            } else {
                // Item
                if ($filters->post_id) {
                    // Media attachment button
                    $act .= '<a class="attach-media" title="' . __('Attach this file to entry') . '" href="' .
                    App::backend()->url->get(
                        'admin.post.media',
                        ['media_id' => $file->media_id, 'post_id' => $filters->post_id, 'attach' => 1, 'link_type' => $filters->link_type]
                    ) .
                    '">' .
                    '<img src="images/plus.png" alt="' . __('Attach this file to entry') . '"/>' .
                        '</a>';
                }
                if ($filters->popup) {
                    // Media insertion button
                    $act .= '<a href="' . $link . '"><img src="images/plus.png" alt="' . __('Insert this file into entry') . '" ' .
                    'title="' . __('Insert this file into entry') . '" /></a> ';
                }
            }
        }
        if ($file->del) {
            // Deletion button or checkbox
            if (!$filters->popup && !$file->d) {
                if ($filters->select < 2) {
                    // Already set for multiple media selection
                    $act .= form::checkbox(['medias[]', 'media_' . rawurlencode($filename)], $filename);
                }
            } else {
                $act .= '<a class="media-remove" ' .
                'href="' . App::backend()->url->get($page_adminurl, array_merge($filters->values(), ['remove' => rawurlencode($filename)])) . '">' .
                '<img src="images/trash.png" alt="' . __('Delete') . '" title="' . __('delete') . '" /></a>';
            }
        }

        $file_type  = explode('/', (string) $file->type);
        $class_open = 'class="modal-' . $file_type[0] . '" ';

        // Render markup
        if ($filters->file_mode != 'list') {
            $res = '<div class="' . $class . '"><p><a class="media-icon media-link" href="' . rawurldecode($link) . '">' .
            '<img class="media-icon-square' . (!$file->d && $file->media_preview ? ' media-icon-preview' : '') . '" src="' . $file->media_icon . '" alt="" />' . ($query ? $filename : $display_name) . '</a></p>';

            $lst = '';
            if (!$file->d) {
                $lst .= '<li>' . ($file->media_priv ? '<img class="media-private" src="images/locker.png" alt="' . __('private media') . '">' : '') . $file->media_title . '</li>' .
                '<li>' .
                '<time datetime="' . Date::iso8601(strtotime($file->media_dtstr), App::auth()->getInfo('user_tz')) . '">' .
                $file->media_dtstr .
                '</time>' .
                ' - ' .
                Files::size($file->size) . ' - ' .
                '<a ' . $class_open . 'href="' . $file->file_url . '">' . __('open') . '</a>' .
                    '</li>';
            }
            $lst .= ($act != '' ? '<li class="media-action">&nbsp;' . $act . '</li>' : '');

            // Show player if relevant
            if ($file_type[0] == 'audio') {
                $lst .= '<li>' . dcMedia::audioPlayer($file->type, $file->file_url, null, null, false, false) . '</li>';
            }

            $res .= ($lst != '' ? '<ul>' . $lst . '</ul>' : '');
            $res .= '</div>';
        } else {
            $res = '<tr class="' . $class . '">';
            $res .= '<td class="media-action">' . $act . '</td>';
            $res .= '<td class="maximal" scope="row"><a class="media-flag media-link" href="' . rawurldecode($link) . '">' .
            '<img class="media-icon-square' . (!$file->d && $file->media_preview ? ' media-icon-preview' : '') . '" src="' . $file->media_icon . '" alt="" />' . ($query ? $file : $display_name) . '</a>' .
                '<br />' . ($file->d ? '' : ($file->media_priv ? '<img class="media-private" src="images/locker.png" alt="' . __('private media') . '">' : '') . $file->media_title) . '</td>';
            $res .= '<td class="nowrap count">' . (
                $file->d ? '' :
                '<time datetime="' . Date::iso8601(strtotime($file->media_dtstr), App::auth()->getInfo('user_tz')) . '">' .
                $file->media_dtstr .
                '</time>'
            ) . '</td>';
            $res .= '<td class="nowrap count">' . ($file->d ? '' : Files::size($file->size) . ' - ' .
                '<a ' . $class_open . 'href="' . $file->file_url . '">' . __('open') . '</a>') . '</td>';
            $res .= '</tr>';
        }

        return $res;
    }
}
