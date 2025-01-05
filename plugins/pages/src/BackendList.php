<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module backend pages listing.
 * @ingroup pages
 */
class BackendList extends Listing
{
    /**
     * Display a list of pages.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of per page
     * @param   string  $enclose_block  The enclose block
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Text('strong', __('No page'))),
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
                ->colspan(3)
                ->class('first')
                ->text(__('Title'))
            ->render(),
            'date' => (new Th())
                ->scope('col')
                ->text(__('Date'))
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
        # --BEHAVIOR-- adminPagesListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPagesListHeaderV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('pages', $cols);

        // Prepare listing
        $lines = [];
        $count = 0;
        while ($this->rs->fetch()) {
            $lines[] = $this->postLine($count, isset($entries[$this->rs->post_id]));
            $count++;
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

        $buffer = (new Div())
            ->class('table-outer')
            ->items([
                (new Table())
                    ->class(['maximal', 'dragable'])
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
                            $fmt(__('Hidden'), 'hidden.svg', 'hidden') . ' - ' .
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
     * Return a page line.
     *
     * @param   int     $count      The count
     * @param   bool    $checked    The checked
     */
    private function postLine(int $count, bool $checked): Tr
    {
        $img = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->class(['mark', 'mark-%3$s'])
            ->render();
        $post_classes = ['line'];
        if (App::status()->post()->isLimited((int) $this->rs->post_status)) {
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
            $selected = sprintf($img, __('Hidden'), 'hidden.svg', 'hidden');
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

        $cols = [
            'position' => (new Td())
                ->class($pos_classes)->items([
                    (new Number(['order[' . $this->rs->post_id . ']'], 1))
                        ->value($count + 1)
                        ->class('position')
                        ->title(sprintf(__('position of %s'), Html::escapeHTML($this->rs->post_title))),
                ])
            ->render(),
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($this->rs->post_id)
                        ->disabled(!$this->rs->isEditable())
                        ->title(__('Select this page')),
                ])
            ->render(),
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id))
                        ->text(Html::escapeHTML($this->rs->post_title)),
                ])
            ->render(),
            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Text('time', Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt)))
                        ->extra('datetime="' . Date::iso8601((int) strtotime($this->rs->post_dt), App::auth()->getInfo('user_tz')) . '"'),
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
        # --BEHAVIOR-- adminPagesListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPagesListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('pages', $cols);

        return (new Tr())
            ->id('p' . $this->rs->post_id)
            ->class($post_classes)
            ->items([
                (new Text(null, implode('', iterator_to_array($cols)))),
            ]);
    }
}
