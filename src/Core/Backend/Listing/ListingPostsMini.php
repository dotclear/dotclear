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
        $post_classes = ['line'];
        if (App::status()->post()->isRestricted((int) $this->rs->post_status)) {
            $post_classes[] = 'offline';
        }
        $post_classes[] = 'sts-' . App::status()->post()->id((int) $this->rs->post_status); // used ?

        $status = [];
        if ($this->rs->post_password) {
            $status[] = self::getRowImage(__('Protected'), 'locker.svg', 'locked');
        }
        if ($this->rs->post_selected) {
            $status[] = self::getRowImage(__('Selected'), 'selected.svg', 'selected');
        }
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $status[] = self::getRowImage(sprintf($nb_media == 1 ? __('%d attachment') : __('%d attachments'), $nb_media), 'attach.svg', 'attach');
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
                ->class(['nowrap', 'status'])
                ->separator(' ')
                ->items([
                    App::status()->post()->image((int) $this->rs->post_status),
                    ... $status,
                ])
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
