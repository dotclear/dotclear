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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;

/**
 * @brief   Posts list pager form helper.
 *
 * @since   2.20
 */
class ListingPosts extends Listing
{
    /**
     * Display admin post list.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of posts per page
     * @param   string  $enclose_block  The enclose block
     * @param   bool    $filter         The filter
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Text('strong', $filter ? __('No entry matches the filter') : __('No entry'))),
                ])
            ->render();

            return;
        }

        $pager   = (new Pager($page, (int) $this->rs_count, $nb_per_page, 10))->getLinks();
        $entries = [];
        if (isset($_REQUEST['entries'])) {
            foreach ($_REQUEST['entries'] as $v) {
                $entries[(int) $v] = true;
            }
        }

        $cols = [
            'title' => (new Th())
                ->scope('col')
                ->colspan(2)
                ->class('first')
                ->text(__('Title'))
            ->render(),
            'date' => (new Th())
                ->scope('col')
                ->text(__('Date'))
            ->render(),
            'category' => (new Th())
                ->scope('col')
                ->text(__('Category'))
            ->render(),
            'author' => (new Th())
                ->scope('col')
                ->text(__('Author'))
            ->render(),
            'comments' => (new Th())
                ->scope('col')
                ->items([
                    (new Img('images/comments.svg'))
                        ->class('light-only')
                        ->alt(__('Comments')),
                    (new Img('images/comments-dark.svg'))
                        ->class('dark-only')
                        ->alt(__('Comments')),
                    (new Text('span', __('Comments')))
                        ->class('hidden'),
                ])
            ->render(),
            'trackbacks' => (new Th())
                ->scope('col')
                ->items([
                    (new Img('images/trackbacks.svg'))
                        ->class('light-only')
                        ->alt(__('Trackbacks')),
                    (new Img('images/trackbacks-dark.svg'))
                        ->class('dark-only')
                        ->alt(__('Trackbacks')),
                    (new Text('span', __('Trackbacks')))
                        ->class('hidden'),
                ])
            ->render(),
            'status' => (new Th())
                ->scope('col')
                ->text(__('Status'))
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostListHeaderV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('posts', $cols);

        // Prepare listing
        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->postLine(isset($entries[$this->rs->post_id]));
        }

        if ($filter) {
            $caption = sprintf(
                __('List of %s entry matching the filter.', 'List of %s entries matching the filter.', $this->rs_count),
                $this->rs_count
            );
        } else {
            $stats = [
                (new Text(null, sprintf(__('List of entries (%s)'), $this->rs_count))),
            ];
            foreach(App::status()->post()->dump() as $status) {
                $nb = (int) App::blog()->getPosts(['post_status' => $status->level()], true)->f(0);
                if ($nb !== 0) {
                    $stats[] = (new Set())
                        ->separator(' ')
                        ->items([
                            (new Link())
                                ->href(App::backend()->url()->get('admin.posts', ['status' => $status->level()]))
                                ->text(__($status->id() . ' (1)', $status->id() . ' (> 1)', $nb)),
                            (new Text(null, sprintf('(%d)', $nb))),
                        ]);
                }
            }
            $caption = (new Set())
                ->separator(', ')
                ->items($stats)
            ->render();
        }

        $buffer = (new Div())
            ->class('table-outer')
            ->items([
                (new Table())
                    ->class(['maximal', 'dragable'])
                    ->caption(new Caption($caption))
                    ->items([
                        (new Thead())
                            ->rows([
                                (new Tr())
                                    ->items([
                                        (new Text(null, implode('', iterator_to_array($cols)))),
                                    ]),
                            ]),
                        (new Tbody())
                            ->id('pageslist')
                            ->rows($lines),
                    ]),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(null, __('Legend: ') . (new Set())
                            ->separator(' - ')
                            ->items([
                                ... array_map(fn ($k): Img|Text => App::status()->post()->image($k->id(), true), App::status()->post()->dump()),
                                self::getRowImage(__('Protected'), 'images/locker.svg', 'locked', true),
                                self::getRowImage(__('Selected'), 'images/selected.svg', 'selected', true),
                                self::getRowImage(__('Attachments'), 'images/attach.svg', 'attach', true),
                            ])
                            ->render(),
                        )),
                    ]),
            ])
        ->render();
        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Get a line.
     *
     * @param   bool    $checked    The checked flag
     */
    private function postLine(bool $checked): Tr
    {
        $post_classes = ['line'];
        if (App::status()->post()->isRestricted((int) $this->rs->post_status)) {
            $post_classes[] = 'offline';
        }
        $post_classes[] = 'sts-' . App::status()->post()->id((int) $this->rs->post_status); // used ?

        $status = [];
        if ($this->rs->post_password) {
            $status[] = self::getRowImage(__('Protected'), 'images/locker.svg', 'locked');
        }
        if ($this->rs->post_selected) {
            $status[] = self::getRowImage(__('Selected'), 'images/selected.svg', 'selected');
        }
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $status[] = self::getRowImage(sprintf($nb_media == 1 ? __('%d attachment') : __('%d attachments'), $nb_media), 'images/attach.svg', 'attach');
        }

        if ($this->rs->cat_title) {
            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())) {
                $category = (new Link())
                    ->href(App::backend()->url()->get('admin.category', ['id' => $this->rs->cat_id], '&amp;', true))
                    ->text(Html::escapeHTML($this->rs->cat_title));
            } else {
                $category = (new Text(null, Html::escapeHTML($this->rs->cat_title)));
            }
        } else {
            $category = (new Text(null, __('(No cat)')));
        }

        $cols = [
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($this->rs->post_id)
                        ->disabled(!$this->rs->isEditable())
                        ->title(__('Select this post')),
                ])
            ->render(),
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id))
                        ->text(Html::escapeHTML(trim(Html::clean($this->rs->post_title)))),
                ])
            ->render(),
            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Text('time', Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt)))
                        ->extra('datetime="' . Date::iso8601((int) strtotime($this->rs->post_dt), App::auth()->getInfo('user_tz')) . '"'),
                ])
            ->render(),
            'category' => (new Td())
                ->class('nowrap')
                ->items([
                    $category,
                ])
            ->render(),
            'author' => (new Td())
                ->class('nowrap')
                ->text($this->rs->user_id)
            ->render(),
            'comments' => (new Td())
                ->class(['nowrap', 'count'])
                ->text($this->rs->nb_comment)
            ->render(),
            'trackbacks' => (new Td())
                ->class(['nowrap', 'count'])
                ->text($this->rs->nb_trackback)
            ->render(),
            'status' => (new Td())
                ->class(['nowrap', 'status', 'count'])
                ->separator(' ')
                ->items([
                    App::status()->post()->image((int) $this->rs->post_status),
                    ... $status,
                ])
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('posts', $cols);

        return (new Tr())
            ->id('p' . $this->rs->post_id)
            ->class($post_classes)
            ->items([
                (new Text(null, implode('', iterator_to_array($cols)))),
            ]);
    }
}
