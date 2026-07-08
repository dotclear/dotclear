<?php

/**
 * @package     Dotclear
 * @subpackage  Backend
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
use Dotclear\Helper\Html\Form\Component;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Timestamp;
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
                    (new Strong(__('No entry'))),
                ])
            ->render();

            return;
        }

        $pager = (new Pager($page, $this->rs_count, $nb_per_page, 10))->getLinks();

        $cols = [
            'title' => (new Th())
                ->scope('col')
                ->text(__('Title')),

            'date' => (new Th())
                ->scope('col')
                ->text(__('Date')),

            'author' => (new Th())
                ->scope('col')
                ->text(__('Author')),

            'status' => (new Th())
                ->scope('col')
                ->text(__('Status')),
        ];

        /**
         * @var ArrayObject<string, Component>
         */
        $cols = new ArrayObject($cols);

        # --BEHAVIOR-- adminPostMiniListHeaderV2 -- MetaRecord, ArrayObject<string, Component>, bool
        App::behavior()->callBehavior('adminPostMiniListHeaderV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('posts', $cols, true);

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
                                    ->items($cols),
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
        $post_id = $this->rs->intField('post_id');
        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';

        $post_classes = ['line'];
        if (App::status()->post()->isRestricted($this->rs->intField('post_status'))) {
            $post_classes[] = 'offline';
        }
        $post_classes[] = 'sts-' . App::status()->post()->id($this->rs->intField('post_status')); // used ?

        $status = [];
        if ($this->rs->strField('post_password') !== '') {
            $status[] = self::getRowImage(__('Protected'), 'images/locker.svg', 'locked');
        }

        if ($this->rs->boolField('post_selected')) {
            $status[] = self::getRowImage(__('Selected'), 'images/selected.svg', 'selected');
        }

        $nb_media = is_numeric($nb_media = $this->rs->countMedia()) ? (int) $nb_media : 0;
        if ($nb_media > 0) {
            $status[] = self::getRowImage(sprintf($nb_media === 1 ? __('%d attachment') : __('%d attachments'), $nb_media), 'images/attach.svg', 'attach');
        }

        $post_url = is_string($post_url = $this->rs->getURL()) ? $post_url : '';

        $cols = [
            'title' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::postTypes()->get($this->rs->strField('post_type'))->adminUrl($post_id))
                        ->title(Html::escapeHTML($post_url))
                        ->text(Html::escapeHTML(trim(Html::clean($this->rs->strField('post_title'))))),
                ]),

            'date' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Timestamp(Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->strField('post_dt'))))
                        ->datetime(Date::iso8601((int) strtotime($this->rs->strField('post_dt')), $user_tz)),
                ]),

            'author' => (new Td())
                ->class('nowrap')
                ->text($this->rs->strField('user_id')),

            'status' => (new Td())
                ->class(['nowrap', 'status'])
                ->separator(' ')
                ->items([
                    App::status()->post()->image($this->rs->intField('post_status')),
                    ... $status,
                ]),
        ];

        /**
         * @var ArrayObject<string, Component>
         */
        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPostMiniListValueV2 -- MetaRecord, ArrayObject<string, Component>, bool
        App::behavior()->callBehavior('adminPostMiniListValueV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('posts', $cols, true);

        return (new Tr())
            ->id('p' . $post_id)
            ->class($post_classes)
            ->items($cols);
    }
}
