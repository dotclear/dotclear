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
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
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
 * @brief   Posts mini list pager form helper.
 *
 * @since   2.20
 */
class ListingPostsMini extends Listing
{
    /**
     * Display a mini post list.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of posts per page
     * @param   string  $enclose_block  The enclose block
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Text('strong', __('No entry'))),
                ])
            ->render();

            return;
        }

        $pager = (new Pager($page, (int) $this->rs_count, $nb_per_page, 10))->getLinks();

        $cols = [
            'title' => (new Th())
                ->scope('col')
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
            'status' => (new Th())
                ->scope('col')
                ->text(__('Status'))
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostMiniListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostMiniListHeaderV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('posts', $cols);

        // Prepare listing
        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->postLine();
        }

        $buffer = (new Div())
            ->class(['table-outer', 'clear'])
            ->items([
                (new Table())
                    ->class(['maximal', 'dragable'])
                    ->caption((new Caption(__('Entries list')))
                        ->class('hidden'))
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
            ])
        ->render();
        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Get a line.
     */
    private function postLine(): Tr
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

        $cols = [
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id))
                        ->title(Html::escapeHTML($this->rs->getURL()))
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
            'author' => (new Td())
                ->class('nowrap')
                ->text($this->rs->user_id)
            ->render(),
            'status' => (new Td())
                ->class(['nowrap', 'count'])
                ->text($img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach)
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostMiniListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPostMiniListValueV2', $this->rs, $cols);

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
