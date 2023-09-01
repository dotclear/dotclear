<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Listing;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use form;

class ListingUsers extends Listing
{
    /**
     * Display a user list
     *
     * @param      int     $page           The page
     * @param      int     $nb_per_page    The number of users per page
     * @param      string  $enclose_block  The enclose block
     * @param      bool    $filter         The filter
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No user matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No user') . '</strong></p>';
            }
        } else {
            $pager = new Pager($page, $this->rs_count, $nb_per_page, 10);

            $html_block = '<div class="table-outer clear">' .
                '<table>';

            if ($filter) {
                $html_block .= '<caption>' . sprintf(__('List of %s users match the filter.'), $this->rs_count) . '</caption>';
            } else {
                $html_block .= '<caption class="hidden">' . __('Users list') . '</caption>';
            }

            $cols = [
                'username'     => '<th colspan="2" scope="col" class="first">' . __('Username') . '</th>',
                'first_name'   => '<th scope="col">' . __('First Name') . '</th>',
                'last_name'    => '<th scope="col">' . __('Last Name') . '</th>',
                'display_name' => '<th scope="col">' . __('Display name') . '</th>',
                'entries'      => '<th scope="col" class="nowrap">' . __('Entries (all types)') . '</th>',
            ];

            $cols = new ArrayObject($cols);

            # --BEHAVIOR-- adminUserListHeaderV2 -- MetaRecord, ArrayObject
            App::behavior()->callBehavior('adminUserListHeaderV2', $this->rs, $cols);

            // Cope with optional columns
            $this->userColumns('users', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table>%s</div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->userLine();
            }

            echo $blocks[1];

            $fmt = fn ($title, $image) => sprintf('<img alt="%1$s" title="%1$s" src="images/%2$s" /> %1$s', $title, $image);
            echo '<p class="info">' . __('Legend: ') .
                $fmt(__('admin'), 'admin.png') . ' - ' .
                $fmt(__('superadmin'), 'superadmin.png') .
                '</p>';

            echo $blocks[2];

            echo $pager->getLinks();
        }
    }

    /**
     * Get a user line
     *
     * @return     string
     */
    private function userLine(): string
    {
        $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $img_status = '';

        $p = App::users()->getUserPermissions($this->rs->user_id);

        if (isset($p[App::blog()->id]['p']['admin'])) {
            $img_status = sprintf($img, __('admin'), 'admin.png');
        }
        if ($this->rs->user_super) {
            $img_status = sprintf($img, __('superadmin'), 'superadmin.png');
        }

        $res = '<tr class="line">';

        $cols = [
            'check' => '<td class="nowrap">' . form::hidden(['nb_post[]'], (int) $this->rs->nb_post) .
            form::checkbox(['users[]'], $this->rs->user_id) . '</td>',
            'username' => '<td class="maximal" scope="row"><a href="' .
            App::backend()->url->get('admin.user', ['id' => $this->rs->user_id]) . '">' .
            $this->rs->user_id . '</a>&nbsp;' . $img_status . '</td>',
            'first_name'   => '<td class="nowrap">' . Html::escapeHTML($this->rs->user_firstname) . '</td>',
            'last_name'    => '<td class="nowrap">' . Html::escapeHTML($this->rs->user_name) . '</td>',
            'display_name' => '<td class="nowrap">' . Html::escapeHTML($this->rs->user_displayname) . '</td>',
            'entries'      => '<td class="nowrap count"><a href="' .
            App::backend()->url->get('admin.posts', ['user_id' => $this->rs->user_id]) . '">' .
            $this->rs->nb_post . '</a></td>',
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminUserListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminUserListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('users', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}
