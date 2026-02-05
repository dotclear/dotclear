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
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Timestamp;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Stack\Status;

/**
 * @brief   Users list pager form helper.
 *
 * @since   2.20
 */
class ListingUsers extends Listing
{
    /**
     * Display a user list.
     *
     * @param   int     $page           The page
     * @param   int     $nb_per_page    The number of users per page
     * @param   string  $enclose_block  The enclose block
     * @param   bool    $filter         The filter
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false): void
    {
        if ($this->rs->isEmpty()) {
            echo (new Para())
                ->items([
                    (new Strong($filter ? __('No user matches the filter') : __('No user'))),
                ])
            ->render();

            return;
        }

        $pager = (new Pager($page, (int) $this->rs_count, $nb_per_page, 10))->getLinks();

        $cols = [
            'username' => (new Th())
                ->colspan(2)
                ->scope('col')
                ->class('first')
                ->text(__('Username')),

            'first_name' => (new Th())
                ->scope('col')
                ->text(__('First Name')),

            'last_name' => (new Th())
                ->scope('col')
                ->text(__('Last Name')),

            'display_name' => (new Th())
                ->scope('col')
                ->text(__('Display name')),

            'entries' => (new Th())
                ->scope('col')
                ->class('nowrap')
                ->text(__('No. of entries')),

            'user_creadt' => (new Th())
                ->scope('col')
                ->class('nowrap')
                ->text(__('Creation date')),

            'user_upddt' => (new Th())
                ->scope('col')
                ->class('nowrap')
                ->text(__('Update date')),

            'status' => (new Th())
                ->scope('col')
                ->text(__('Status')),
        ];

        /**
         * @var ArrayObject<string, mixed>
         */
        $cols = new ArrayObject($cols);

        # --BEHAVIOR-- adminUserListHeaderV2 -- MetaRecord, ArrayObject<string, mixed>, bool
        App::behavior()->callBehavior('adminUserListHeaderV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('users', $cols, true);

        // Prepare listing
        $lines = [
            (new Tr())
                ->items($cols),
        ];
        while ($this->rs->fetch()) {
            $lines[] = $this->userLine();
        }

        if ($filter) {
            $caption = sprintf(__('The user matching the filter.', 'List of %s users matching the filter.', $this->rs_count), $this->rs_count);
        } else {
            $stats = [
                (new Text(null, sprintf('%s (%s)', __('Users'), $this->rs_count))),
            ];
            foreach (App::status()->user()->dump(false) as $status) {
                $nb = (int) App::users()->getUsers(['user_status' => $status->level()], true)->f(0);
                if ($nb !== 0) {
                    $stats[] = (new Set())
                        ->separator(' ')
                        ->items([
                            (new Link())
                                ->href(App::backend()->url()->get('admin.users', ['status' => $status->level()]))
                                ->text(__($status->name(), $status->pluralName(), $nb)),
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
            ->class(['table-outer', 'clear'])
            ->items([
                (new Table())
                    ->caption(new Caption($caption))
                    ->items($lines),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(
                            null,
                            __('Legend: ') . (new Set())
                            ->separator(' - ')
                            ->items([
                                self::getRowImage(__('admin'), 'images/admin.svg', 'admin', true),
                                self::getRowImage(__('superadmin'), 'images/superadmin.svg', 'admin', true),
                                ... array_map(fn (Status $k): Img|Set|Text => App::status()->user()->image($k->id(), true), App::status()->user()->dump(false)),
                            ])
                            ->render(),
                        )),
                    ]),
                (new Note())
                    ->class('warning')
                    ->text(__('The “No. of entries” column includes all entry types (articles, pages, …) for all blogs in the installation. The link may not be relevant in some contexts')),
            ])
        ->render();
        if ($enclose_block !== '') {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Get a user line.
     */
    private function userLine(): Tr
    {
        $status = [match ($this->rs->admin()) {
            App::auth()::PERMISSION_SUPERADMIN => self::getRowImage(__('superadmin'), 'images/superadmin.svg', 'admin'),
            App::auth()::PERMISSION_ADMIN      => self::getRowImage(__('admin'), 'images/admin.svg', 'admin'),
            default                            => (new Set()),
        }];

        $cols = [
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Hidden(['nb_post[]'], (string) $this->rs->nb_post)),
                    (new Checkbox(['users[]']))
                        ->value($this->rs->user_id),
                ]),

            'username' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.user', ['id' => $this->rs->user_id]))
                        ->text(Html::escapeHTML($this->rs->user_id)),
                ]),

            'first_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_firstname)),

            'last_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_name)),

            'display_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_displayname)),

            'user_creadt' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Timestamp(Date::str(__('%Y-%m-%d %H:%M'), strtotime($this->rs->user_creadt) + Date::getTimeOffset(App::auth()->getInfo('user_tz')))))
                        ->datetime(Date::iso8601((int) strtotime($this->rs->user_creadt), App::auth()->getInfo('user_tz'))),
                ]),

            'user_upddt' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Timestamp(Date::str(__('%Y-%m-%d %H:%M'), strtotime($this->rs->user_upddt) + Date::getTimeOffset(App::auth()->getInfo('user_tz')))))
                        ->datetime(Date::iso8601((int) strtotime($this->rs->user_upddt), App::auth()->getInfo('user_tz'))),
                ]),

            'entries' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.posts', ['user_id' => $this->rs->user_id]))
                        ->text((string) $this->rs->nb_post),
                ]),

            'status' => (new Td())
                ->class(['nowrap', 'status'])
                ->separator(' ')
                ->items([
                    App::status()->user()->image((int) $this->rs->user_status),
                    ... $status,
                ]),
        ];

        /**
         * @var ArrayObject<string, mixed>
         */
        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminUserListValueV2 -- MetaRecord, ArrayObject<string, mixed>, bool
        App::behavior()->callBehavior('adminUserListValueV2', $this->rs, $cols, true);

        // Cope with optional columns
        $this->userColumns('users', $cols, true);

        return (new Tr())
            ->class('line')
            ->items($cols);
    }
}
