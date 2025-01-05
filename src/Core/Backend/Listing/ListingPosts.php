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

        $fmt = fn ($title, $image, $class): string => sprintf(
            (new Img('images/%2$s'))
                    ->alt('%1$s')
                    ->class(['mark', 'mark-%3$s'])
                    ->render() . ' %1$s',
            $title,
            $image,
            $class
        );

        if ($filter) {
            $caption = sprintf(
                __('List of %s entry matching the filter.', 'List of %s entries matching the filter.', $this->rs_count),
                $this->rs_count
            );
        } else {
            $nb_published   = (int) App::blog()->getPosts(['post_status' => App::status()->post()->level('published')], true)->f(0);
            $nb_pending     = (int) App::blog()->getPosts(['post_status' => App::status()->post()->level('pending')], true)->f(0);
            $nb_scheduled   = (int) App::blog()->getPosts(['post_status' => App::status()->post()->level('scheduled')], true)->f(0);
            $nb_unpublished = (int) App::blog()->getPosts(['post_status' => App::status()->post()->level('unpublished')], true)->f(0);
            $stats          = [
                (new Text(null, sprintf(__('List of entries (%s)'), $this->rs_count))),
            ];
            if ($nb_published !== 0) {
                $stats[] = (new Set())
                    ->separator(' ')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()->level('published')]))
                            ->text(__('published (1)', 'published (> 1)', $nb_published)),
                        (new Text(null, sprintf('(%d)', $nb_published))),
                    ]);
            }
            if ($nb_pending !== 0) {
                $stats[] = (new Set())
                    ->separator(' ')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()->level('pending')]))
                            ->text(__('pending (1)', 'pending (> 1)', $nb_published)),
                        (new Text(null, sprintf('(%d)', $nb_pending))),
                    ]);
            }
            if ($nb_scheduled !== 0) {
                $stats[] = (new Set())
                    ->separator(' ')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()->level('scheduled')]))
                            ->text(__('scheduled (1)', 'scheduled (> 1)', $nb_scheduled)),
                        (new Text(null, sprintf('(%d)', $nb_scheduled))),
                    ]);
            }
            if ($nb_unpublished !== 0) {
                $stats[] = (new Set())
                    ->separator(' ')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.posts', ['status' => App::status()->post()->level('unpublished')]))
                            ->text(__('unpublished (1)', 'unpublished (> 1)', $nb_unpublished)),
                        (new Text(null, sprintf('(%d)', $nb_unpublished))),
                    ]);
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
                        (new Text(
                            null,
                            __('Legend: ') .
                            $fmt(__('Published'), 'published.svg', 'published') . ' - ' .
                            $fmt(__('Unpublished'), 'unpublished.svg', 'unpublished') . ' - ' .
                            $fmt(__('Scheduled'), 'scheduled.svg', 'scheduled') . ' - ' .
                            $fmt(__('Pending'), 'pending.svg', 'pending') . ' - ' .
                            $fmt(__('Protected'), 'locker.svg', 'locked') . ' - ' .
                            $fmt(__('Selected'), 'selected.svg', 'selected') . ' - ' .
                            $fmt(__('Attachments'), 'attach.svg', 'attach')
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
        $img = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->class(['mark', 'mark-%3$s'])
            ->render();
        $post_classes = ['line'];
        if ((int) $this->rs->post_status <= App::status()->post()->level('unpublished')) {
            $post_classes[] = 'offline';
        }
        $img_status = '';
        switch ((int) $this->rs->post_status) {
            case App::status()->post()->level('published'):
                $img_status     = sprintf($img, __('Published'), 'published.svg', 'published');
                $post_classes[] = 'sts-online';

                break;
            case App::status()->post()->level('unpublished'):
                $img_status     = sprintf($img, __('Unpublished'), 'unpublished.svg', 'unpublished');
                $post_classes[] = 'sts-offline';

                break;
            case App::status()->post()->level('scheduled'):
                $img_status     = sprintf($img, __('Scheduled'), 'scheduled.svg', 'scheduled');
                $post_classes[] = 'sts-scheduled';

                break;
            case App::status()->post()->level('pending'):
                $img_status     = sprintf($img, __('Pending'), 'pending.svg', 'pending');
                $post_classes[] = 'sts-pending';

                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.svg', 'locked');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('Selected'), 'selected.svg', 'selected');
        }

        $attach   = '';
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
            $attach     = sprintf($img, sprintf($attach_str, $nb_media), 'attach.svg', 'attach');
        }

        $pos_classes = ['nowrap', 'minimal'];
        if (!App::auth()->prefs()->accessibility->nodragdrop) {
            $pos_classes[] = 'handle';
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
                ->class(['nowrap', 'count'])
                ->text($img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach)
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
