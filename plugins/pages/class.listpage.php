<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

/* Pager class
-------------------------------------------------------- */
class adminPagesList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No page') . '</strong></p>';
        } else {
            $pager   = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $entries = array();
            if (isset($_REQUEST['entries'])) {
                foreach ($_REQUEST['entries'] as $v) {
                    $entries[(integer) $v] = true;
                }
            }
            $html_block =
                '<div class="table-outer">' .
                '<table class="maximal dragable"><thead><tr>';

            $cols = array(
                'title'      => '<th colspan="3" scope="col" class="first">' . __('Title') . '</th>',
                'date'       => '<th scope="col">' . __('Date') . '</th>',
                'author'     => '<th scope="col">' . __('Author') . '</th>',
                'comments'   => '<th scope="col"><img src="images/comments.png" alt="" title="' . __('Comments') .
                '" /><span class="hidden">' . __('Comments') . '</span></th>',
                'trackbacks' => '<th scope="col"><img src="images/trackbacks.png" alt="" title="' . __('Trackbacks') .
                '" /><span class="hidden">' . __('Trackbacks') . '</span></th>',
                'status'     => '<th scope="col">' . __('Status') . '</th>'
            );

            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminPagesListHeader', $this->core, $this->rs, $cols);

            // Cope with optional columns
            $this->userColumns('pages', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) .
                '</tr></thead><tbody id="pageslist">%s</tbody></table></div>';

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

            echo $pager->getLinks();
        }
    }

    private function postLine($count, $checked)
    {
        $img       = '<img alt="%1$s" title="%1$s" src="images/%2$s" class="mark mark-%3$s" />';
        $sts_class = '';
        switch ($this->rs->post_status) {
            case 1:
                $img_status = sprintf($img, __('Published'), 'check-on.png', 'published');
                $sts_class  = 'sts-online';
                break;
            case 0:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png', 'unpublished');
                $sts_class  = 'sts-offline';
                break;
            case -1:
                $img_status = sprintf($img, __('Scheduled'), 'scheduled.png', 'scheduled');
                $sts_class  = 'sts-scheduled';
                break;
            case -2:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png', 'pending');
                $sts_class  = 'sts-pending';
                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.png', 'locked');
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

        $res = '<tr class="line ' . ($this->rs->post_status != 1 ? 'offline ' : '') . $sts_class . '"' .
        ' id="p' . $this->rs->post_id . '">';

        $cols = array(
            'position'   => '<td class="nowrap handle minimal">' .
            form::number(array('order[' . $this->rs->post_id . ']'), array(
                'min'        => 1,
                'default'    => $count + 1,
                'class'      => 'position',
                'extra_html' => 'title="' . sprintf(__('position of %s'), html::escapeHTML($this->rs->post_title)) . '"'
            )) .
            '</td>',
            'check'      => '<td class="nowrap">' .
            form::checkbox(array('entries[]'), $this->rs->post_id,
                array(
                    'checked'    => $checked,
                    'disabled'   => !$this->rs->isEditable(),
                    'extra_html' => 'title="' . __('Select this page') . '"'
                )
            ) . '</td>',
            'title'      => '<td class="maximal" scope="row"><a href="' .
            $this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id) . '">' .
            html::escapeHTML($this->rs->post_title) . '</a></td>',
            'date'       => '<td class="nowrap">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>',
            'author'     => '<td class="nowrap">' . $this->rs->user_id . '</td>',
            'comments'   => '<td class="nowrap count">' . $this->rs->nb_comment . '</td>',
            'trackbacks' => '<td class="nowrap count">' . $this->rs->nb_trackback . '</td>',
            'status'     => '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach . '</td>'
        );

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminPagesListValue', $this->core, $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('pages', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}
