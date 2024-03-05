<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\Core\Backend\Listing\Pager;
use Dotclear\Core\Backend\Listing\Listing;
use Dotclear\App;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use form;

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
            echo '<p><strong>' . __('No page') . '</strong></p>';
        } else {
            $pager   = new Pager($page, (int) $this->rs_count, $nb_per_page, 10);
            $entries = [];
            if (isset($_REQUEST['entries'])) {
                foreach ($_REQUEST['entries'] as $v) {
                    $entries[(int) $v] = true;
                }
            }
            $html_block = '<div class="table-outer">' .
                '<table class="maximal dragable"><thead><tr>';

            $cols = [
                'title'      => '<th colspan="3" scope="col" class="first">' . __('Title') . '</th>',
                'date'       => '<th scope="col">' . __('Date') . '</th>',
                'author'     => '<th scope="col">' . __('Author') . '</th>',
                'comments'   => '<th scope="col"><img src="images/comments.svg" class="light-only" alt="' . __('Comments') . '"><img src="images/comments-dark.svg" class="dark-only" alt="' . __('Comments') . '"><span class="hidden">' . __('Comments') . '</span></th>',
                'trackbacks' => '<th scope="col"><img src="images/trackbacks.svg" class="light-only" alt="' . __('Trackbacks') . '"><img src="images/trackbacks-dark.svg" class="dark-only" alt="' . __('Trackbacks') . '"><span class="hidden">' . __('Trackbacks') . '</span></th>',
                'status'     => '<th scope="col">' . __('Status') . '</th>',
            ];

            $cols = new ArrayObject($cols);
            # --BEHAVIOR-- adminPagesListHeaderV2 -- MetaRecord, ArrayObject
            App::behavior()->callBehavior('adminPagesListHeaderV2', $this->rs, $cols);

            // Cope with optional columns
            $this->userColumns('pages', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) .
                '</tr></thead><tbody id="pageslist">%s</tbody></table>%s</div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            $count = 0;
            while ($this->rs->fetch()) {
                echo $this->postLine($count, isset($entries[$this->rs->post_id]));
                $count++;
            }

            echo $blocks[1];

            $fmt = fn ($title, $image, $class) => sprintf('<img alt="%1$s" src="images/%2$s" class="mark mark-%3$s"> %1$s', $title, $image, $class);
            echo '<p class="info">' . __('Legend: ') .
                $fmt(__('Published'), 'published.svg', 'published') . ' - ' .
                $fmt(__('Unpublished'), 'check-off.png', 'unpublished') . ' - ' .
                $fmt(__('Scheduled'), 'scheduled.svg', 'scheduled') . ' - ' .
                $fmt(__('Pending'), 'check-wrn.png', 'pending') . ' - ' .
                $fmt(__('Protected'), 'locker.svg', 'locked') . ' - ' .
                $fmt(__('Hidden'), 'hidden.png', 'hidden') . ' - ' .
                $fmt(__('Attachments'), 'attach.png', 'attach') .
                '</p>';

            echo $blocks[2];

            echo $pager->getLinks();
        }
    }

    /**
     * Return a page line.
     *
     * @param   int     $count      The count
     * @param   bool    $checked    The checked
     *
     * @return  string
     */
    private function postLine(int $count, bool $checked): string
    {
        $img        = '<img alt="%1$s" src="images/%2$s" class="mark mark-%3$s">';
        $sts_class  = '';
        $img_status = '';
        switch ($this->rs->post_status) {
            case App::blog()::POST_PUBLISHED:
                $img_status = sprintf($img, __('Published'), 'published.svg', 'published');
                $sts_class  = 'sts-online';

                break;
            case App::blog()::POST_UNPUBLISHED:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png', 'unpublished');
                $sts_class  = 'sts-offline';

                break;
            case App::blog()::POST_SCHEDULED:
                $img_status = sprintf($img, __('Scheduled'), 'scheduled.svg', 'scheduled');
                $sts_class  = 'sts-scheduled';

                break;
            case App::blog()::POST_PENDING:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png', 'pending');
                $sts_class  = 'sts-pending';

                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.svg', 'locked');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('Hidden'), 'hidden.png', 'hidden');
        }

        $attach   = '';
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
            $attach     = sprintf($img, sprintf($attach_str, $nb_media), 'attach.png', 'attach');
        }

        $res = '<tr class="line ' . ($this->rs->post_status != App::blog()::POST_PUBLISHED ? 'offline ' : '') . $sts_class . '"' .
        ' id="p' . $this->rs->post_id . '">';

        $cols = [
            'position' => '<td class="nowrap handle minimal">' .
            form::number(['order[' . $this->rs->post_id . ']'], [
                'min'        => 1,
                'default'    => $count + 1,
                'class'      => 'position',
                'extra_html' => 'title="' . sprintf(__('position of %s'), Html::escapeHTML($this->rs->post_title)) . '"',
            ]) .
            '</td>',
            'check' => '<td class="nowrap">' .
            form::checkbox(
                ['entries[]'],
                $this->rs->post_id,
                [
                    'checked'    => $checked,
                    'disabled'   => !$this->rs->isEditable(),
                    'extra_html' => 'title="' . __('Select this page') . '"',
                ]
            ) . '</td>',
            'title' => '<td class="maximal" scope="row"><a href="' .
            App::postTypes()->get($this->rs->post_type)->adminUrl($this->rs->post_id) . '">' .
            Html::escapeHTML($this->rs->post_title) . '</a></td>',
            'date' => '<td class="nowrap">' .
                '<time datetime="' . Date::iso8601((int) strtotime($this->rs->post_dt), App::auth()->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) .
                '</time>' .
                '</td>',
            'author'     => '<td class="nowrap">' . $this->rs->user_id . '</td>',
            'comments'   => '<td class="nowrap count">' . $this->rs->nb_comment . '</td>',
            'trackbacks' => '<td class="nowrap count">' . $this->rs->nb_trackback . '</td>',
            'status'     => '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach . '</td>',
        ];

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminPagesListValueV2 -- MetaRecord, ArrayObject
        App::behavior()->callBehavior('adminPagesListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('pages', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}
