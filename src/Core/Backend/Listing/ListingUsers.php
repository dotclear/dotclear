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
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Html;
use Dotclear\Interface\Core\AuthInterface;

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
                    (new Text('strong', $filter ? __('No user matches the filter') : __('No user'))),
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
                ->text(__('Username'))
            ->render(),

            'first_name' => (new Th())
                ->scope('col')
                ->text(__('First Name'))
            ->render(),

            'last_name' => (new Th())
                ->scope('col')
                ->text(__('Last Name'))
            ->render(),

            'display_name' => (new Th())
                ->scope('col')
                ->text(__('Display name'))
            ->render(),

            'entries' => (new Th())
                ->scope('col')
                ->class('nowrap')
                ->text(__('No. of entries'))
            ->render(),
        ];

        $cols = new ArrayObject($cols);

        # --BEHAVIOR-- adminUserListHeaderV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminUserListHeaderV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('users', $cols);

        $fmt = fn ($title, $image) => sprintf(
            (new Img('images/%2$s'))
                    ->alt('%1$s')
                    ->class(['mark', 'mark-admin'])
                    ->render() . ' %1$s',
            $title,
            $image
        );

        // Prepare listing
        $lines = [
            (new Tr())
                ->items([
                    (new Text(null, implode('', iterator_to_array($cols)))),
                ]),
        ];
        while ($this->rs->fetch()) {
            $lines[] = $this->userLine();
        }

        if ($filter) {
            $caption = (new Caption(sprintf(__('List of %s users match the filter.'), $this->rs_count)));
        } else {
            $caption = (new Caption(__('Users list')))
                ->class('hidden');
        }

        $buffer = (new Div())
            ->class(['table-outer', 'clear'])
            ->items([
                (new Table())
                    ->caption($caption)
                    ->items($lines),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(null, __('Legend: '))),
                        (new Text(null, $fmt(__('admin'), 'admin.svg') . ' - ')),
                        (new Text(null, $fmt(__('superadmin'), 'superadmin.svg'))),
                    ]),
                (new Note())
                    ->class('warning')
                    ->text(__('The “No. of entries” column includes all entry types (articles, pages, …) for all blogs in the installation. The link may not be relevant in some contexts')),
            ])
        ->render();
        if ($enclose_block) {
            $buffer = sprintf($enclose_block, $buffer);
        }

        echo $pager . $buffer . $pager;
    }

    /**
     * Get a user line.
     *
     * @return  Tr
     */
    private function userLine(): Tr
    {
        $img = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->class(['mark', 'mark-admin'])
            ->render();

        $img_status = match ($this->rs->admin()) {
            AuthInterface::PERMISSION_SUPERADMIN => sprintf($img, __('superadmin'), 'superadmin.svg'),
            AuthInterface::PERMISSION_ADMIN      => sprintf($img, __('admin'), 'admin.svg'),
            default                              => '',
        };

        $cols = [
            'check' => (new Td())
                ->class('nowrap')
                ->items([
                    (new Hidden(['nb_post[]'], (string) $this->rs->nb_post)),
                    (new Checkbox(['users[]']))
                        ->value($this->rs->user_id),
                ])
            ->render(),

            'username' => (new Td())
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.user', ['id' => $this->rs->user_id]))
                        ->text(Html::escapeHTML($this->rs->user_id)),
                    (new Text(null, $img_status)),
                ])
            ->render(),

            'first_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_firstname))
            ->render(),

            'last_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_name))
            ->render(),

            'display_name' => (new Td())
                ->class('nowrap')
                ->text(Html::escapeHTML($this->rs->user_displayname))
            ->render(),

            'entries' => (new Td())
                ->class(['nowrap', 'count'])
                ->items([
                    (new Link())
                        ->href(App::backend()->url()->get('admin.posts', ['user_id' => $this->rs->user_id]))
                        ->text((string) $this->rs->nb_post),
                ])
            ->render(),
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminUserListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminUserListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('users', $cols);

        return (new Tr())
            ->class('line')
            ->items([
                (new Text(null, implode('', iterator_to_array($cols)))),
            ]);
    }
}
