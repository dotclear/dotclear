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
use dcAntispam;
use dcBlog;
use dcCore;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use form;

class ListingComments extends Listing
{
    /**
     * Display a comment list
     *
     * @param      int     $page           The page
     * @param      int     $nb_per_page    The number of comments per page
     * @param      string  $enclose_block  The enclose block
     * @param      bool    $filter         The spam filter
     * @param      bool    $spam           Show spam
     * @param      bool    $show_ip        Show ip
     */
    public function display(int $page, int $nb_per_page, string $enclose_block = '', bool $filter = false, bool $spam = false, bool $show_ip = true)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No comments or trackbacks matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No comments') . '</strong></p>';
            }
        } else {
            // Get antispam filters' name
            $filters = [];
            if ($spam) {
                if (class_exists('dcAntispam')) {
                    dcAntispam::initFilters();
                    $fs = dcAntispam::$filters->getFilters();
                    foreach ($fs as $fid => $f) {
                        $filters[$fid] = $f->name;
                    }
                }
            }

            $pager = new Pager($page, $this->rs_count, $nb_per_page, 10);

            $comments = [];
            if (isset($_REQUEST['comments'])) {
                foreach ($_REQUEST['comments'] as $v) {
                    $comments[(int) $v] = true;
                }
            }
            $html_block = '<div class="table-outer">' .
                '<table>';

            if ($filter) {
                $html_block .= '<caption>' .
                sprintf(__(
                    'Comment or trackback matching the filter.',
                    'List of %s comments or trackbacks matching the filter.',
                    $this->rs_count
                ), $this->rs_count) .
                    '</caption>';
            } else {
                $nb_published   = (int) dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_PUBLISHED], true)->f(0);
                $nb_spam        = (int) dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
                $nb_pending     = (int) dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_PENDING], true)->f(0);
                $nb_unpublished = (int) dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_UNPUBLISHED], true)->f(0);
                $html_block .= '<caption>' .
                sprintf(__('List of comments and trackbacks (%s)'), $this->rs_count) .
                    ($nb_published ?
                    sprintf(
                        __(', <a href="%s">published</a> (1)', ', <a href="%s">published</a> (%s)', $nb_published),
                        dcCore::app()->adminurl->get('admin.comments', ['status' => dcBlog::COMMENT_PUBLISHED]),
                        $nb_published
                    ) : '') .
                    ($nb_spam ?
                    sprintf(
                        __(', <a href="%s">spam</a> (1)', ', <a href="%s">spam</a> (%s)', $nb_spam),
                        dcCore::app()->adminurl->get('admin.comments', ['status' => dcBlog::COMMENT_JUNK]),
                        $nb_spam
                    ) : '') .
                    ($nb_pending ?
                    sprintf(
                        __(', <a href="%s">pending</a> (1)', ', <a href="%s">pending</a> (%s)', $nb_pending),
                        dcCore::app()->adminurl->get('admin.comments', ['status' => dcBlog::COMMENT_PENDING]),
                        $nb_pending
                    ) : '') .
                    ($nb_unpublished ?
                    sprintf(
                        __(', <a href="%s">unpublished</a> (1)', ', <a href="%s">unpublished</a> (%s)', $nb_unpublished),
                        dcCore::app()->adminurl->get('admin.comments', ['status' => dcBlog::COMMENT_UNPUBLISHED]),
                        $nb_unpublished
                    ) : '') .
                    '</caption>';
            }

            $cols = [
                'type'   => '<th colspan="2" scope="col" abbr="comm" class="first">' . __('Type') . '</th>',
                'author' => '<th scope="col">' . __('Author') . '</th>',
                'date'   => '<th scope="col">' . __('Date') . '</th>',
                'status' => '<th scope="col" class="txt-center">' . __('Status') . '</th>',
            ];
            if ($spam) {
                if ($show_ip) {
                    $cols['ip'] = '<th scope="col">' . __('IP') . '</th>';
                }
                $cols['spam_filter'] = '<th scope="col">' . __('Spam filter') . '</th>';
            }
            $cols['entry'] = '<th scope="col" abbr="entry">' . __('Entry') . '</th>';

            $cols = new ArrayObject($cols);
            # --BEHAVIOR-- adminCommentListHeaderV2 -- MetaRecord, ArrayObject
            dcCore::app()->callBehavior('adminCommentListHeaderV2', $this->rs, $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table>%s</div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->commentLine(isset($comments[$this->rs->comment_id]), $spam, $filters, $show_ip);
            }

            echo $blocks[1];

            $fmt = fn ($title, $image) => sprintf('<img alt="%1$s" title="%1$s" src="images/%2$s" /> %1$s', $title, $image);
            echo '<p class="info">' . __('Legend: ') .
                $fmt(__('Published'), 'check-on.png') . ' - ' .
                $fmt(__('Unpublished'), 'check-off.png') . ' - ' .
                $fmt(__('Pending'), 'check-wrn.png') . ' - ' .
                $fmt(__('Junk'), 'junk.png') .
                '</p>';

            echo $blocks[2];

            echo $pager->getLinks();
        }
    }

    /**
     * Get a comment line
     *
     * @param      bool    $checked  The checked flag
     * @param      bool    $spam     The spam flag
     * @param      array   $filters  The filters
     *
     * @return     string
     */
    private function commentLine(bool $checked = false, bool $spam = false, array $filters = [], bool $show_ip = true): string
    {
        $author_url = dcCore::app()->adminurl->get('admin.comments', [
            'author' => $this->rs->comment_author,
        ]);

        $post_url = dcCore::app()->getPostAdminURL($this->rs->post_type, $this->rs->post_id);

        $comment_url = dcCore::app()->adminurl->get('admin.comment', ['id' => $this->rs->comment_id]);

        $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $img_status = '';
        $sts_class  = '';
        switch ($this->rs->comment_status) {
            case dcBlog::COMMENT_PUBLISHED:
                $img_status = sprintf($img, __('Published'), 'check-on.png');
                $sts_class  = 'sts-online';

                break;
            case dcBlog::COMMENT_UNPUBLISHED:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                $sts_class  = 'sts-offline';

                break;
            case dcBlog::COMMENT_PENDING:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                $sts_class  = 'sts-pending';

                break;
            case dcBlog::COMMENT_JUNK:
                $img_status = sprintf($img, __('Junk'), 'junk.png');
                $sts_class  = 'sts-junk';

                break;
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

        $res = '<tr class="line ' . ($this->rs->comment_status != dcBlog::COMMENT_PUBLISHED ? 'offline ' : '') . $sts_class . '"' .
        ' id="c' . $this->rs->comment_id . '">';

        $cols = [
            'check' => '<td class="nowrap">' .
            form::checkbox(['comments[]'], $this->rs->comment_id, $checked) .
            '</td>',
            'type' => '<td class="nowrap" abbr="' . __('Type and author') . '" scope="row">' .
            '<a href="' . $comment_url . '" title="' . $comment_title . '">' .
            '<img src="images/edit-mini.png" alt="' . __('Edit') . '"/> ' .
            ($this->rs->comment_trackback ? __('trackback') : __('comment')) . ' ' . '</a></td>',
            'author' => '<td class="nowrap maximal"><a href="' . $author_url . '">' .
            Html::escapeHTML($this->rs->comment_author) . '</a></td>',
            'date' => '<td class="nowrap count">' .
                '<time datetime="' . Date::iso8601(strtotime($this->rs->comment_dt), dcCore::app()->auth->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->comment_dt) .
                '</time>' .
                '</td>',
            'status' => '<td class="nowrap status txt-center">' . $img_status . '</td>',
        ];

        if ($spam) {
            $filter_name = '';
            if ($this->rs->comment_spam_filter) {
                if (isset($filters[$this->rs->comment_spam_filter])) {
                    $filter_name = $filters[$this->rs->comment_spam_filter];
                } else {
                    $filter_name = $this->rs->comment_spam_filter;
                }
            }
            if ($show_ip) {
                $cols['ip'] = '<td class="nowrap"><a href="' .
                    dcCore::app()->adminurl->get('admin.comments', ['ip' => $this->rs->comment_ip]) . '">' .
                    $this->rs->comment_ip . '</a></td>';
            }
            $cols['spam_filter'] = '<td class="nowrap">' . $filter_name . '</td>';
        }
        $cols['entry'] = '<td class="nowrap discrete"><a href="' . $post_url . '">' . $post_title . '</a>' .
            ($this->rs->post_type != 'post' ? ' (' . Html::escapeHTML($this->rs->post_type) . ')' : '') . '</td>';

        $cols = new ArrayObject($cols);
        # --BEHAVIOR-- adminCommentListValueV2 -- MetaRecord, ArrayObject
        dcCore::app()->callBehavior('adminCommentListValueV2', $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('comments', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}
