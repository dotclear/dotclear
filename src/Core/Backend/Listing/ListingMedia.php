<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\FilterMedia;
use Dotclear\Helper\Date;
use Dotclear\Helper\File\File;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;

/**
 * @brief   Media list pager form helper.
 *
 * @since   2.20
 */
class ListingMedia extends Listing
{
    /**
     * Display a media list.
     *
     * @param   FilterMedia     $filters        The filters
     * @param   string          $enclose_block  The enclose block
     * @param   bool            $query          The query
     * @param   string          $page_adminurl  The page adminurl
     */
    public function display(FilterMedia $filters, string $enclose_block = '', $query = false, $page_adminurl = 'admin.media'): void
    {
        $nb_items   = $this->rs_count - ($filters->d ? 1 : 0);
        $nb_folders = $filters->d ? -1 : 0;

        if ($filters->q && !$query) {
            echo (new Para())
                ->items([
                    (new Text('strong', __('No file matches the filter'))),
                ])
            ->render();

            return;
        }

        if ($nb_items < 1) {
            echo (new Para())
                ->items([
                    (new Text('strong', __('No file.'))),
                ])
            ->render();

            return;
        }

        $pager = new Pager($filters->page, (int) $this->rs_count, $filters->nb, 10);

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

        $dirs  = [];
        $files = [];
        for ($index = $pager->index_start, $index_in_page = 0; $index <= $pager->index_end; $index++, $index_in_page++) {
            $item = $items[$index];
            if (is_array($item)) {
                // Convert array to object->properties (will then pretend to be like a File object)
                $item = (object) $item;
            }
            if ($item->d) {
                $dirs[] = self::mediaItem($filters, $items[$index], $index_in_page, $query, $page_adminurl);
            } else {
                $files[] = self::mediaItem($filters, $items[$index], $index_in_page, $query, $page_adminurl);
            }
        }

        if ($filters->file_mode === FilterMedia::MODE_LIST) {
            $buffer = (new Div())
                ->class('table-outer')
                ->items([
                    (new Table())
                        ->class('media-item-bloc')
                        ->caption(new Caption($caption))
                        ->items([
                            (new Tr())
                                ->items([
                                    (new Th())
                                        ->colspan(2)
                                        ->class('first')
                                        ->text(__('Name')),
                                    (new Th())
                                        ->scope('col')
                                        ->text(__('Date')),
                                    (new Th())
                                        ->scope('col')
                                        ->text(__('Size')),
                                ]),
                            ...$dirs,
                            ...$files,
                        ]),
                ])
            ->render();
        } else {
            $buffer_files = (new Div())
                ->class('media-items-bloc')
                ->items($files)
            ->render();
            if ($enclose_block) {
                $buffer_files = sprintf($enclose_block, $buffer_files, '');
            }
            $buffer = (new Set())
                ->items([
                    empty($dirs) ?
                        (new None()) :
                        (new Div())
                            ->class('folders-group')
                            ->items($dirs),
                    (new Text(null, $buffer_files)),
                    (new Div())
                        ->class('media-stats')
                        ->items([
                            (new Note())
                                ->class('form-stats')
                                ->text($caption),
                        ]),
                ])
            ->render();
        }

        echo $pager->getLinks() . $buffer . $pager->getLinks();
    }

    /**
     * Display a media item.
     *
     * @param   FilterMedia                 $filters        The filters
     * @param   File|array<string, mixed>   $file           The media file
     * @param   int                         $index          Current index in page
     * @param   bool                        $query          The query
     * @param   string                      $page_adminurl  The page adminurl
     *
     * @return  Tr|Div
     */
    private static function mediaItem(
        FilterMedia $filters,
        $file,
        int $index,
        bool $query = false,
        string $page_adminurl = 'admin.media'
    ): Tr|Div {
        if (is_array($file)) {
            // Convert array to object->properties (will then pretend to be like a File object)
            $file = (object) $file;
        }

        $mode = $filters->file_mode === FilterMedia::MODE_LIST ? FilterMedia::MODE_LIST : FilterMedia::MODE_GRID;

        // Function to get image alternate text
        $getImageAlt = function ($file): string {
            if (!$file) {
                return '';
            }

            if ($file->media_title && $file->media_title !== '') {
                return $file->media_title;
            }

            if (is_countable($file->media_meta) && count($file->media_meta) && is_iterable($file->media_meta)) {
                foreach ($file->media_meta as $k => $v) {
                    if ((string) $v && ($k == 'AltText')) {
                        return (string) $v;
                    }
                }
            }

            return '';
        };

        $display_name = (string) $file->basename;
        $filename     = $query ? $file->relname : $file->basename;

        $classes   = [];
        $classes[] = 'media-item-bloc'; // cope with js message for grid AND list
        if ($mode === FilterMedia::MODE_GRID) {
            $classes[] = 'media-item media-col-' . ($index % 2);
        }

        if ($file->d) {
            // Folder
            $link = App::backend()->url()->get(
                'admin.media',
                [
                    ...$filters->values(),
                    'd' => Html::sanitizeURL($file->relname),
                ]
            );
            if ($file->parent) {
                $display_name = '..';
                $classes[]    = 'media-folder-up';
            } else {
                $classes[] = 'media-folder';
            }
        } else {
            // Item
            $params = new ArrayObject([...$filters->values(), 'id' => $file->media_id]);
            unset($params['process']); // move to media item

            # --BEHAVIOR-- adminMediaURLParams -- ArrayObject
            App::behavior()->callBehavior('adminMediaURLParams', $params);

            $link = App::backend()->url()->get('admin.media.item', (array) $params);
            if ($file->media_priv) {
                $classes[] = 'media-private';
            }
        }

        $maxchars = 34; // cope with design
        if (strlen($display_name) > $maxchars) {
            $display_name = substr($display_name, 0, $maxchars - 4) . '...' . ($file->d ? '' : Files::getExtension($display_name));
        }

        $actions = [];
        if (!$file->d) {
            if ($filters->select > 0) {
                if ($filters->select == 1) {
                    // Single media selection button
                    $actions[] = (new Link())
                        ->class('insert-media')
                        ->href($link)
                        ->title(__('Select this file'))
                        ->items([
                            (new Img('images/plus.svg'))
                                ->alt(__('Select this file')),
                        ]);
                } else {
                    // Multiple media selection checkbox
                    $actions[] = (new Checkbox(['medias[]', 'media_' . rawurlencode($filename)]))
                        ->value($filename);
                }
            } else {
                // Item
                if ($filters->post_id) {
                    // Media attachment button
                    $link_attach = App::backend()->url()->get(
                        'admin.post.media',
                        [
                            'media_id'  => $file->media_id,
                            'post_id'   => $filters->post_id,
                            'attach'    => 1,
                            'link_type' => $filters->link_type,
                        ]
                    );
                    $actions[] = (new Link())
                        ->class('attach-media')
                        ->href($link_attach)
                        ->title(__('Attach this file to entry'))
                        ->items([
                            (new Img('images/plus.svg'))
                                ->alt(__('Attach this file to entry')),
                        ]);
                }
                if ($filters->popup) {
                    // Media insertion button
                    $actions[] = (new Link())
                        ->class('insert-media-media')
                        ->href($link)
                        ->title(__('Insert this file into entry'))
                        ->items([
                            (new Img('images/plus.svg'))
                                ->alt(__('Insert this file into entry')),
                        ]);
                }
            }
        }
        if ($file->del) {
            // Deletion button or checkbox
            if (!$filters->popup && !$file->d) {
                if ($filters->select < 2) {
                    // Already set for multiple media selection
                    $actions[] = (new Checkbox(['medias[]', 'media_' . rawurlencode($filename)]))
                        ->value($filename);
                }
            } else {
                $link_remove = App::backend()->url()->get(
                    $page_adminurl,
                    [
                        ...$filters->values(),
                        'remove' => rawurlencode($filename),
                    ]
                );
                $actions[] = (new Link())
                    ->class('media-remove')
                    ->href($link_remove)
                    ->title(__('Delete'))
                    ->items([
                        (new Img('images/trash.svg'))
                            ->alt(__('Delete')),
                    ]);
            }
        }

        $file_type     = explode('/', (string) $file->type);
        $class_open    = 'class="modal-' . $file_type[0] . '" ';
        $class_preview = !$file->d && $file->media_preview ? 'media-icon-preview' : '';

        if ($mode === FilterMedia::MODE_LIST) {
            return (new Tr())
                ->class($classes)
                ->items([
                    (new Td())
                        ->class('media-action')
                        ->items($actions),
                    (new Td())
                        ->class('maximal')
                        ->items([
                            (new Link())
                                ->class(['media-flag', 'media-link'])
                                ->href(rawurldecode($link))
                                ->items([
                                    (new Img($file->media_icon))
                                        ->class(array_filter(['media-icon-square', $class_preview]))
                                        ->alt(''),
                                    (new Text(null, $query ? $filename : $display_name)),
                                ]),
                            (new Single('br')),
                            (new Set())
                                ->items([
                                    $file->d ?
                                        (new None()) :
                                        (new Set())
                                            ->items([
                                                $file->media_priv ?
                                                    (new img('images/locker.svg'))
                                                        ->class(['media-private', 'mark', 'mark-locked'])
                                                        ->alt(__('private media')) :
                                                    (new None()),
                                                (new Text(null, $getImageAlt($file))),
                                            ]),
                                ]),
                        ]),
                    (new Td())
                        ->class(['nowrap', 'count'])
                        ->items([
                            $file->d ?
                                (new None()) :
                                (new Text('time', $file->media_dtstr))
                                    ->extra('datetime="' . Date::iso8601((int) strtotime((string) $file->media_dtstr), App::auth()->getInfo('user_tz')) . '"'),
                        ]),
                    (new Td())
                        ->class(['nowrap', 'count'])
                        ->items([
                            $file->d ?
                                (new None()) :
                                (new Set())
                                    ->separator(' - ')
                                    ->items([
                                        (new Text(null, Files::size((int) $file->size))),
                                        (new Link())
                                            ->class($class_open)
                                            ->href($file->file_url)
                                            ->text(__('open')),
                                    ]),
                        ]),
                ]);
        }

        $list = [];
        if (!$file->d) {
            $list[] = (new Li())
                ->items([
                    $file->media_priv ?
                        (new img('images/locker.svg'))
                            ->class(['media-private', 'mark', 'mark-locked'])
                            ->alt(__('private media')) :
                        (new None()),
                    (new Text(null, $getImageAlt($file))),
                ]);
            $list[] = (new Li())
                ->separator(' - ')
                ->items([
                    (new Text('time', $file->media_dtstr))
                        ->extra('datetime="' . Date::iso8601((int) strtotime((string) $file->media_dtstr), App::auth()->getInfo('user_tz')) . '"'),
                    (new Text(null, Files::size((int) $file->size))),
                    (new Link())
                        ->class($class_open)
                        ->href($file->file_url)
                        ->text(__('open')),
                ]);
        }
        if (count($actions)) {
            $list[] = (new Li())
                ->class('media-action')
                ->items($actions);
        }

        // Show player if relevant
        if ($file_type[0] === 'audio') {
            $list[] = (new Li())
                ->text(App::media()::audioPlayer($file->type, $file->file_url, null, null, false, false));
        }

        return (new Div())
            ->class($classes)
            ->items([
                (new Para())
                    ->items([
                        (new Link())
                            ->class(['media-icon', 'media-link'])
                            ->href(rawurldecode($link))
                            ->items([
                                (new Img($file->media_icon))
                                    ->class(array_filter(['media-icon-square', $class_preview]))
                                    ->alt(''),
                                (new Text(null, $query ? $filename : $display_name)),
                            ]),
                    ]),
                count($list) ?
                    (new Ul())
                        ->items($list) :
                    (new None()),
            ]);
    }

    /**
     * Display a media item.
     *
     * @param   FilterMedia                 $filters        The filters
     * @param   File|array<string, mixed>   $file           The media file
     * @param   int                         $index          Current index in page
     * @param   bool                        $query          The query
     * @param   string                      $page_adminurl  The page adminurl
     *
     * @return  string
     */
    public static function mediaLine(FilterMedia $filters, $file, int $index, bool $query = false, string $page_adminurl = 'admin.media'): string
    {
        return self::mediaItem($filters, $file, $index, $query, $page_adminurl)->render();
    }
}
