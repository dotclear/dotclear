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
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\antispam\Antispam;

/**
 * @brief   Comments list pager form helper.
 *
 * @since   2.20
 */
class ListingComments extends Listing
{
    /**
     * Display a comment list.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of comments per page
     * @param   string  $enclose_block  The enclose block
     * @param   bool    $filter         The spam filter
     * @param   bool    $spam           Show spam
     * @param   bool    $show_ip        Show ip
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false, bool $spam = false, bool $show_ip = true): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Text('strong', $filter ? __('No comments or trackbacks matches the filter') : __('No comments'))),
                ])
            ->render();

            return;
        }

        // At least one comment+trackback to render

        // Get antispam filters' name
        $filters = [];
        if ($spam) {
            if (class_exists(Antispam::class)) {
                Antispam::initFilters();
                $fs = Antispam::$filters->getFilters();
                foreach ($fs as $fid => $f) {
                    $filters[$fid] = $f->name;
                }
            }
        }

        $pager = (new Pager($page, (int) $this->rs_count, $nb_per_page, 10))->getLinks();

        $comments = [];
        if (isset($_REQUEST['comments'])) {
            foreach ($_REQUEST['comments'] as $v) {
                $comments[(int) $v] = true;
            }
        }

        if ($filter) {
            $caption = sprintf(__(
                'Comment or trackback matching the filter.',
                'List of %s comments or trackbacks matching the filter.',
                $this->rs_count
            ), $this->rs_count);
        } else {
            $nb_published   = (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_PUBLISHED], true)->f(0);
            $nb_spam        = (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_JUNK], true)->f(0);
            $nb_pending     = (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_PENDING], true)->f(0);
            $nb_unpublished = (int) App::blog()->getComments(['comment_status' => App::blog()::COMMENT_UNPUBLISHED], true)->f(0);

            $caption = (new Set())
                ->separator(', ')
                ->items([
                    (new Text(null, sprintf(__('List of comments and trackbacks (%s)'), $this->rs_count))),
                    $nb_published > 0 ?
                        (new Set())
                            ->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => App::blog()::COMMENT_PUBLISHED]))
                                    ->text(__('published (1)', 'published (> 1)', $nb_published)),
                                (new Text(null, sprintf(' (%d)', $nb_published))),
                            ]) :
                        (new None()),
                    $nb_spam > 0 ?
                        (new Set())
                            ->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => App::blog()::COMMENT_JUNK]))
                                    ->text(__('spam (1)', 'spam (> 1)', $nb_spam)),
                                (new Text(null, sprintf(' (%d)', $nb_spam))),
                            ]) :
                        (new None()),
                    $nb_pending > 0 ?
                        (new Set())
                            ->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => App::blog()::COMMENT_PENDING]))
                                    ->text(__('pending (1)', 'pending (> 1)', $nb_pending)),
                                (new Text(null, sprintf(' (%d)', $nb_pending))),
                            ]) :
                        (new None()),
                    $nb_unpublished > 0 ?
                        (new Set())
                            ->items([
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => App::blog()::COMMENT_UNPUBLISHED]))
                                    ->text(__('unpublished (1)', 'unpublished (> 1)', $nb_unpublished)),
                                (new Text(null, sprintf(' (%d)', $nb_unpublished))),
                            ]) :
                        (new None()),
                ])
            ->render();
        }

        $cols = [
            'type' => (new Th())
                ->colspan(2)
                ->scope('col')
                ->class('first')
                ->text(__('Type'))
            ->render(),

            'author' => (new Th())
                ->scope('col')
                ->text(__('Author'))
            ->render(),

            'date' => (new Th())
                ->scope('col')
                ->text(__('Date'))
            ->render(),

            'status' => (new Th())
                ->scope('col')
                ->text(__('Status'))
                ->class('txt-center')
            ->render(),
        ];
        if ($show_ip) {
            $cols['ip'] = (new Th())
                ->scope('col')
                ->text(__('IP'))
            ->render();
        }
        if ($spam) {
            $cols['spam_filter'] = (new Th())
                ->scope('col')
                ->text(__('Spam filter'))
            ->render();
        }
        $cols['entry'] = (new Th())
            ->scope('col')
            ->text(__('Entry'))
        ->render();

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminCommentListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminCommentListHeaderV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('comments', $cols);

        // Prepare listing
        $lines = [
            (new Tr())
                ->items([
                    (new Text(null, implode('', iterator_to_array($cols)))),
                ]),
        ];
        while ($this->rs->fetch()) {
            $lines[] = $this->commentLine(isset($comments[$this->rs->comment_id]), $spam, $filters, $show_ip);
        }

        $fmt = fn ($title, $image, $class, $dark = false) => (new Img('images/' . $image . '.svg'))
            ->class(array_filter(['mark', 'mark-' . $class, $dark ? 'light-only' : '']))
            ->alt($title)
        ->render() . ($dark ? (new Img('images/' . $image . '-dark.svg'))
            ->class(['mark', 'mark-' . $class, 'dark-only'])
            ->alt($title)
        ->render() : '') . ' ' . $title;

        $buffer = (new Div())
            ->class('table-outer')
            ->items([
                (new Table())
                    ->caption(new Caption($caption))
                    ->items($lines),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(null, __('Legend: '))),
                        (new Text(null, $fmt(__('Published'), 'published', 'published') . ' - ')),
                        (new Text(null, $fmt(__('Unpublished'), 'unpublished', 'unpublished') . ' - ')),
                        (new Text(null, $fmt(__('Pending'), 'pending', 'pending') . ' - ')),
                        (new Text(null, $fmt(__('Junk'), 'junk', 'junk', true))),
                    ]),
            ])
        ->render();
        if ($enclose_block) {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Get a comment line.
     *
     * @param   bool                    $checked    The checked flag
     * @param   bool                    $spam       The spam flag
     * @param   array<string, string>   $filters    The filters
     *
     * @return  Tr
     */
    private function commentLine(bool $checked = false, bool $spam = false, array $filters = [], bool $show_ip = true): Tr
    {
        $author_url  = App::backend()->url()->get('admin.comments', ['author' => $this->rs->comment_author]);
        $post_url    = App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id);
        $comment_url = App::backend()->url()->get('admin.comment', ['id' => $this->rs->comment_id]);

        [
            $img_status_alt,
            $img_status_case,
            $img_status_dark,
            $sts_class
        ] = match ((int) $this->rs->comment_status) {
            App::blog()::COMMENT_PUBLISHED => [
                __('Published'),
                'published',
                false,
                'sts-online',
            ],
            App::blog()::COMMENT_UNPUBLISHED => [
                __('Unpublished'),
                'unpublished',
                false,
                'sts-offline',
            ],
            App::blog()::COMMENT_PENDING => [
                __('Pending'),
                'pending',
                false,
                'sts-pending',
            ],
            App::blog()::COMMENT_JUNK => [
                __('Junk'),
                'junk',
                true,
                'sts-junk',
            ],
            default => [
                __('Unknown'),
                'unknown',
                false,
                'sts-unknown',
            ]
        };

        $img_status = [
            (new Img('images/' . $img_status_case . '.svg'))
                ->class(array_filter(['mark', 'mark-' . $img_status_case, $img_status_dark ? 'light-only' : '']))
                ->alt($img_status_alt),
        ];
        if ($img_status_dark) {
            $img_status[] = (new Img('images/' . $img_status_case . '-dark.svg'))
                    ->class(['mark', 'mark-' . $img_status_case, 'dark-only'])
                    ->alt($img_status_alt)
            ;
        }

        $post_title = Html::escapeHTML(trim(Html::clean($this->rs->post_title)));
        if (mb_strlen($post_title) > 70) {
            $post_title = mb_strcut($post_title, 0, 67) . '...';
        }
        $comment_title = sprintf(
            __('Edit the %1$s from %2$s'),
            $this->rs->comment_trackback ? __('trackback') : __('comment'),
            Html::escapeHTML($this->rs->comment_author)
        );

        $cols = [
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Checkbox(['comments[]'], $checked))
                        ->value($this->rs->comment_id),
                ])
            ->render(),

            'type' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Link())
                        ->href($comment_url)
                        ->title($comment_title)
                        ->items([
                            (new Img('images/edit.svg'))
                                ->class(['mark', 'mark-edit', 'light-only'])
                                ->alt(__('Edit')),
                            (new Img('images/edit-dark.svg'))
                                ->class(['mark', 'mark-edit', 'dark-only'])
                                ->alt(__('Edit')),
                            (new Text(null, $this->rs->comment_trackback ? __('trackback') : __('comment'))),
                        ]),
                ])
            ->render(),

            'author' => (new Td())
                ->class(['nowrap', 'maximal'])
                ->items([
                    (new Link())
                        ->href($author_url)
                        ->text(Html::escapeHTML($this->rs->comment_author)),
                ])
            ->render(),

            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Text('time', Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->comment_dt)))
                        ->extra('datetime="' . Date::iso8601((int) strtotime($this->rs->comment_dt), App::auth()->getInfo('user_tz')) . '"'),
                ])
            ->render(),

            'status' => (new Td())
                ->class(['nowrap', 'status', 'txt-center'])
                ->items($img_status)
            ->render(),
        ];

        if ($show_ip) {
            $cols['ip'] = (new Td())
                ->class('nowrap')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.comments', ['ip' => $this->rs->comment_ip]))
                        ->text($this->rs->comment_ip),
                ])
            ->render();
        }
        if ($spam) {
            $filter_name = '';
            if ($this->rs->comment_spam_filter) {
                if (isset($filters[$this->rs->comment_spam_filter])) {
                    $filter_name = $filters[$this->rs->comment_spam_filter];
                } else {
                    $filter_name = $this->rs->comment_spam_filter;
                }
            }
            $cols['spam_filter'] = (new Td())
                ->class('nowrap')
                ->text($filter_name)
            ->render();
        }

        $cols['entry'] = (new Td())
            ->class(['nowrap', 'discrete'])
            ->separator(' ')
            ->items([
                (new Link())
                    ->href($post_url)
                    ->text($post_title),
                (new Text(null, $this->rs->post_type !== 'post' ? '(' . Html::escapeHTML($this->rs->post_type) . ')' : '')),
            ])
        ->render();

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminCommentListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminCommentListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('comments', $cols);

        return (new Tr())
            ->id('c' . (string) $this->rs->comment_id)
            ->class(array_filter([
                'line',
                $this->rs->comment_status != App::blog()::COMMENT_PUBLISHED ? 'offline' : '',
                $sts_class,
            ]))
            ->items([
                (new Text(null, implode('', iterator_to_array($cols)))),
            ]);
    }
}
