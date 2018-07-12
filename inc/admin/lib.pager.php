<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcPager extends pager
{
    protected $form_action;
    protected $form_hidden;

    protected function getLink($li_class, $href, $img_src, $img_src_nolink, $img_alt, $enable_link)
    {
        if ($enable_link) {
            $formatter = '<li class="%s btn"><a href="%s"><img src="%s" alt="%s"/></a><span class="hidden">%s</span></li>';
            return sprintf($formatter,
                $li_class, $href, $img_src, $img_alt, $img_alt);
        } else {
            $formatter = '<li class="%s no-link btn"><img src="%s" alt="%s"/></li>';
            return sprintf($formatter,
                $li_class, $img_src_nolink, $img_alt, $img_alt);
        }
    }
    public function setURL()
    {
        parent::setURL();
        $url = parse_url($_SERVER['REQUEST_URI']);
        if (isset($url['query'])) {
            parse_str($url['query'], $args);
        } else {
            $args = array();
        }
        # Removing session information
        if (session_id()) {
            if (isset($args[session_name()])) {
                unset($args[session_name()]);
            }

        }
        if (isset($args[$this->var_page])) {
            unset($args[$this->var_page]);
        }
        if (isset($args['ok'])) {
            unset($args['ok']);
        }

        $this->form_hidden = '';
        foreach ($args as $k => $v) {
            // Check parameter key (will prevent some forms of XSS)
            if ($k === preg_replace('`[^A-Za-z0-9_-]`', '', $k)) {
                if (is_array($v)) {
                    foreach ($v as $k2 => $v2) {
                        $this->form_hidden .= form::hidden(array($k . '[]'), html::escapeHTML($v2));
                    }
                } else {
                    $this->form_hidden .= form::hidden(array($k), html::escapeHTML($v));
                }
            }
        }
        $this->form_action = $url['path'];
    }

    /**
     * Pager Links
     *
     * Returns pager links
     *
     * @return string
     */
    public function getLinks()
    {
        $this->setURL();
        $htmlFirst = $this->getLink(
            "first",
            sprintf($this->page_url, 1),
            "images/pagination/first.png",
            "images/pagination/no-first.png",
            __('First page'),
            ($this->env > 1)
        );
        $htmlPrev = $this->getLink(
            "prev",
            sprintf($this->page_url, $this->env - 1),
            "images/pagination/previous.png",
            "images/pagination/no-previous.png",
            __('Previous page'),
            ($this->env > 1)
        );
        $htmlNext = $this->getLink(
            "next",
            sprintf($this->page_url, $this->env + 1),
            "images/pagination/next.png",
            "images/pagination/no-next.png",
            __('Next page'),
            ($this->env < $this->nb_pages)
        );
        $htmlLast = $this->getLink(
            "last",
            sprintf($this->page_url, $this->nb_pages),
            "images/pagination/last.png",
            "images/pagination/no-last.png",
            __('Last page'),
            ($this->env < $this->nb_pages)
        );
        $htmlCurrent =
        '<li class="active"><strong>' .
        sprintf(__('Page %s / %s'), $this->env, $this->nb_pages) .
            '</strong></li>';

        $htmlDirect =
            ($this->nb_pages > 1 ?
            sprintf('<li class="direct-access">' . __('Direct access page %s'),
                form::number(array($this->var_page), 1, $this->nb_pages)) .
            '<input type="submit" value="' . __('ok') . '" class="reset" ' .
            'name="ok" />' . $this->form_hidden . '</li>' : '');

        $res =
        '<form action="' . $this->form_action . '" method="get">' .
            '<div class="pager"><ul>' .
            $htmlFirst .
            $htmlPrev .
            $htmlCurrent .
            $htmlNext .
            $htmlLast .
            $htmlDirect .
            '</ul>' .
            '</div>' .
            '</form>'
        ;

        return $this->nb_elements > 0 ? $res : '';
    }
}

class adminGenericList
{
    protected $core;
    protected $rs;
    protected $rs_count;

    public function __construct($core, $rs, $rs_count)
    {
        $this->core      = &$core;
        $this->rs        = &$rs;
        $this->rs_count  = $rs_count;
        $this->html_prev = __('&#171; prev.');
        $this->html_next = __('next &#187;');
    }

    public function userColumns($type, $cols)
    {
        $cols_user = @$this->core->auth->user_prefs->interface->cols;
        if (is_array($cols_user)) {
            if (isset($cols_user[$type])) {
                foreach ($cols_user[$type] as $cn => $cd) {
                    if (!$cd && isset($cols[$cn])) {
                        unset($cols[$cn]);
                    }
                }
            }
        }
    }
}

class adminPostList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No entry matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No entry') . '</strong></p>';
            }
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
                '<table>';

            if ($filter) {
                $html_block .= '<caption>' . sprintf(__('List of %s entries matching the filter.'), $this->rs_count) . '</caption>';
            } else {
                $nb_published   = $this->core->blog->getPosts(array('post_status' => 1), true)->f(0);
                $nb_pending     = $this->core->blog->getPosts(array('post_status' => -2), true)->f(0);
                $nb_programmed  = $this->core->blog->getPosts(array('post_status' => -1), true)->f(0);
                $nb_unpublished = $this->core->blog->getPosts(array('post_status' => 0), true)->f(0);
                $html_block .= '<caption>' .
                sprintf(__('List of entries (%s)'), $this->rs_count) .
                    ($nb_published ?
                    sprintf(
                        __(', <a href="%s">published</a> (1)', ', <a href="%s">published</a> (%s)', $nb_published),
                        $this->core->adminurl->get('admin.posts', array('status' => 1)),
                        $nb_published) : '') .
                    ($nb_pending ?
                    sprintf(
                        __(', <a href="%s">pending</a> (1)', ', <a href="%s">pending</a> (%s)', $nb_pending),
                        $this->core->adminurl->get('admin.posts', array('status' => -2)),
                        $nb_pending) : '') .
                    ($nb_programmed ?
                    sprintf(__(', <a href="%s">programmed</a> (1)', ', <a href="%s">programmed</a> (%s)', $nb_programmed),
                        $this->core->adminurl->get('admin.posts', array('status' => -1)),
                        $nb_programmed) : '') .
                    ($nb_unpublished ?
                    sprintf(__(', <a href="%s">unpublished</a> (1)', ', <a href="%s">unpublished</a> (%s)', $nb_unpublished),
                        $this->core->adminurl->get('admin.posts', array('status' => 0)),
                        $nb_unpublished) : '') .
                    '</caption>';
            }

            $cols = array(
                'title'      => '<th colspan="2" class="first">' . __('Title') . '</th>',
                'date'       => '<th scope="col">' . __('Date') . '</th>',
                'category'   => '<th scope="col">' . __('Category') . '</th>',
                'author'     => '<th scope="col">' . __('Author') . '</th>',
                'comments'   => '<th scope="col"><img src="images/comments.png" alt="" title="' . __('Comments') .
                '" /><span class="hidden">' . __('Comments') . '</span></th>',
                'trackbacks' => '<th scope="col"><img src="images/trackbacks.png" alt="" title="' . __('Trackbacks') .
                '" /><span class="hidden">' . __('Trackbacks') . '</span></th>',
                'status'     => '<th scope="col">' . __('Status') . '</th>'
            );
            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminPostListHeader', $this->core, $this->rs, $cols);

            // Cope with optional columns
            $this->userColumns('posts', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->postLine(isset($entries[$this->rs->post_id]));
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function postLine($checked)
    {
        if ($this->core->auth->check('categories', $this->core->blog->id)) {
            $cat_link = '<a href="' . $this->core->adminurl->get('admin.category', array('id' => '%s'), '&amp;', true) . '">%s</a>';
        } else {
            $cat_link = '%2$s';
        }

        if ($this->rs->cat_title) {
            $cat_title = sprintf($cat_link, $this->rs->cat_id,
                html::escapeHTML($this->rs->cat_title));
        } else {
            $cat_title = __('(No cat)');
        }

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
            $selected = sprintf($img, __('Selected'), 'selected.png', 'selected');
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
            'check'      => '<td class="nowrap">' .
            form::checkbox(array('entries[]'), $this->rs->post_id,
                array(
                    'checked'  => $checked,
                    'disabled' => !$this->rs->isEditable()
                )) .
            '</td>',
            'title'      => '<td class="maximal" scope="row"><a href="' .
            $this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id) . '">' .
            html::escapeHTML($this->rs->post_title) . '</a></td>',
            'date'       => '<td class="nowrap count">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>',
            'category'   => '<td class="nowrap">' . $cat_title . '</td>',
            'author'     => '<td class="nowrap">' . html::escapeHTML($this->rs->user_id) . '</td>',
            'comments'   => '<td class="nowrap count">' . $this->rs->nb_comment . '</td>',
            'trackbacks' => '<td class="nowrap count">' . $this->rs->nb_trackback . '</td>',
            'status'     => '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach . '</td>'
        );
        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminPostListValue', $this->core, $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('posts', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}

class adminPostMiniList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '')
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . __('No entry') . '</strong></p>';
        } else {
            $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);

            $html_block =
            '<div class="table-outer clear">' .
            '<table><caption class="hidden">' . __('Entries list') . '</caption><tr>';

            $cols = array(
                'title'  => '<th scope="col">' . __('Title') . '</th>',
                'date'   => '<th scope="col">' . __('Date') . '</th>',
                'author' => '<th scope="col">' . __('Author') . '</th>',
                'status' => '<th scope="col">' . __('Status') . '</th>'
            );

            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminPostMiniListHeader', $this->core, $this->rs, $cols);

            // Cope with optional columns
            $this->userColumns('posts', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->postLine();
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function postLine()
    {
        $img       = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $sts_class = '';
        switch ($this->rs->post_status) {
            case 1:
                $img_status = sprintf($img, __('Published'), 'check-on.png');
                $sts_class  = 'sts-online';
                break;
            case 0:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                $sts_class  = 'sts-offline';
                break;
            case -1:
                $img_status = sprintf($img, __('Scheduled'), 'scheduled.png');
                $sts_class  = 'sts-scheduled';
                break;
            case -2:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                $sts_class  = 'sts-pending';
                break;
        }

        $protected = '';
        if ($this->rs->post_password) {
            $protected = sprintf($img, __('Protected'), 'locker.png');
        }

        $selected = '';
        if ($this->rs->post_selected) {
            $selected = sprintf($img, __('Selected'), 'selected.png');
        }

        $attach   = '';
        $nb_media = $this->rs->countMedia();
        if ($nb_media > 0) {
            $attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
            $attach     = sprintf($img, sprintf($attach_str, $nb_media), 'attach.png');
        }

        $res = '<tr class="line ' . ($this->rs->post_status != 1 ? 'offline ' : '') . $sts_class . '"' .
        ' id="p' . $this->rs->post_id . '">';

        $cols = array(
            'title'  => '<td scope="row" class="maximal"><a href="' .
            $this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id) . '" ' .
            'title="' . html::escapeHTML($this->rs->getURL()) . '">' .
            html::escapeHTML($this->rs->post_title) . '</a></td>',
            'date'   => '<td class="nowrap count">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>',
            'author' => '<td class="nowrap">' . html::escapeHTML($this->rs->user_id) . '</td>',
            'status' => '<td class="nowrap status">' . $img_status . ' ' . $selected . ' ' . $protected . ' ' . $attach . '</td>'
        );

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminPostMiniListValue', $this->core, $this->rs, $cols);

        // Cope with optional columns
        $this->userColumns('posts', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}

class adminCommentList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '', $filter = false, $spam = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No comments or trackbacks matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No comments') . '</strong></p>';
            }
        } else {
            // Get antispam filters' name
            $filters = array();
            if ($spam) {
                if (class_exists('dcAntispam')) {
                    dcAntispam::initFilters();
                    $fs = dcAntispam::$filters->getFilters();
                    foreach ($fs as $fid => $f) {
                        $filters[$fid] = $f->name;
                    }
                }
            }

            $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);

            $comments = array();
            if (isset($_REQUEST['comments'])) {
                foreach ($_REQUEST['comments'] as $v) {
                    $comments[(integer) $v] = true;
                }
            }
            $html_block =
                '<div class="table-outer">' .
                '<table>';

            if ($filter) {
                $html_block .= '<caption>' .
                sprintf(__(
                    'Comment or trackback matching the filter.',
                    'List of %s comments or trackbacks matching the filter.',
                    $this->rs_count), $this->rs_count) .
                    '</caption>';
            } else {
                $nb_published   = $this->core->blog->getComments(array('comment_status' => 1), true)->f(0);
                $nb_spam        = $this->core->blog->getComments(array('comment_status' => -2), true)->f(0);
                $nb_pending     = $this->core->blog->getComments(array('comment_status' => -1), true)->f(0);
                $nb_unpublished = $this->core->blog->getComments(array('comment_status' => 0), true)->f(0);
                $html_block .= '<caption>' .
                sprintf(__('List of comments and trackbacks (%s)'), $this->rs_count) .
                    ($nb_published ?
                    sprintf(
                        __(', <a href="%s">published</a> (1)', ', <a href="%s">published</a> (%s)', $nb_published),
                        $this->core->adminurl->get('admin.comments', array('status' => 1)),
                        $nb_published) : '') .
                    ($nb_spam ?
                    sprintf(
                        __(', <a href="%s">spam</a> (1)', ', <a href="%s">spam</a> (%s)', $nb_spam),
                        $this->core->adminurl->get('admin.comments', array('status' => -2)),
                        $nb_spam) : '') .
                    ($nb_pending ?
                    sprintf(__(', <a href="%s">pending</a> (1)', ', <a href="%s">pending</a> (%s)', $nb_pending),
                        $this->core->adminurl->get('admin.comments', array('status' => -1)),
                        $nb_pending) : '') .
                    ($nb_unpublished ?
                    sprintf(__(', <a href="%s">unpublished</a> (1)', ', <a href="%s">unpublished</a> (%s)', $nb_unpublished),
                        $this->core->adminurl->get('admin.comments', array('status' => 0)),
                        $nb_unpublished) : '') .
                    '</caption>';
            }

            $cols = array(
                'type'   => '<th colspan="2" scope="col" abbr="comm" class="first">' . __('Type') . '</th>',
                'author' => '<th scope="col">' . __('Author') . '</th>',
                'date'   => '<th scope="col">' . __('Date') . '</th>',
                'status' => '<th scope="col" class="txt-center">' . __('Status') . '</th>'
            );
            if ($spam) {
                $cols['ip']          = '<th scope="col">' . __('IP') . '</th>';
                $cols['spam_filter'] = '<th scope="col">' . __('Spam filter') . '</th>';
            }
            $cols['entry'] = '<th scope="col" abbr="entry">' . __('Entry') . '</th>';

            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminCommentListHeader', $this->core, $this->rs, $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->commentLine(isset($comments[$this->rs->comment_id]), $spam, $filters);
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function commentLine($checked = false, $spam = false, $filters = array())
    {
        global $core, $author, $status, $sortby, $order, $nb_per_page;

        $author_url =
        $this->core->adminurl->get('admin.comments', array(
            'n'      => $nb_per_page,
            'status' => $status,
            'sortby' => $sortby,
            'order'  => $order,
            'author' => $this->rs->comment_author
        ));

        $post_url = $this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id);

        $comment_url = $this->core->adminurl->get('admin.comment', array('id' => $this->rs->comment_id));

        $comment_dt =
        dt::dt2str($this->core->blog->settings->system->date_format . ' - ' .
            $this->core->blog->settings->system->time_format, $this->rs->comment_dt);

        $img       = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $sts_class = '';
        switch ($this->rs->comment_status) {
            case 1:
                $img_status = sprintf($img, __('Published'), 'check-on.png');
                $sts_class  = 'sts-online';
                break;
            case 0:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                $sts_class  = 'sts-offline';
                break;
            case -1:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                $sts_class  = 'sts-pending';
                break;
            case -2:
                $img_status = sprintf($img, __('Junk'), 'junk.png');
                $sts_class  = 'sts-junk';
                break;
        }

        $post_title = html::escapeHTML($this->rs->post_title);
        if (mb_strlen($post_title) > 70) {
            $post_title = mb_strcut($post_title, 0, 67) . '...';
        }
        $comment_title = sprintf(__('Edit the %1$s from %2$s'),
            $this->rs->comment_trackback ? __('trackback') : __('comment'),
            html::escapeHTML($this->rs->comment_author));

        $res = '<tr class="line ' . ($this->rs->comment_status != 1 ? 'offline ' : '') . $sts_class . '"' .
        ' id="c' . $this->rs->comment_id . '">';

        $cols = array(
            'check'  => '<td class="nowrap">' .
            form::checkbox(array('comments[]'), $this->rs->comment_id, $checked) .
            '</td>',
            'type'   => '<td class="nowrap" abbr="' . __('Type and author') . '" scope="row">' .
            '<a href="' . $comment_url . '" title="' . $comment_title . '">' .
            '<img src="images/edit-mini.png" alt="' . __('Edit') . '"/> ' .
            ($this->rs->comment_trackback ? __('trackback') : __('comment')) . ' ' . '</a></td>',
            'author' => '<td class="nowrap maximal"><a href="' . $author_url . '">' .
            html::escapeHTML($this->rs->comment_author) . '</a></td>',
            'date'   => '<td class="nowrap count">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->comment_dt) . '</td>',
            'status' => '<td class="nowrap status txt-center">' . $img_status . '</td>'
        );

        if ($spam) {
            $filter_name = '';
            if ($this->rs->comment_spam_filter) {
                if (isset($filters[$this->rs->comment_spam_filter])) {
                    $filter_name = $filters[$this->rs->comment_spam_filter];
                } else {
                    $filter_name = $this->rs->comment_spam_filter;
                }
            }
            $cols['ip'] = '<td class="nowrap"><a href="' .
            $core->adminurl->get("admin.comments", array('ip' => $this->rs->comment_ip)) . '">' .
            $this->rs->comment_ip . '</a></td>';
            $cols['spam_filter'] = '<td class="nowrap">' . $filter_name . '</td>';
        }
        $cols['entry'] = '<td class="nowrap discrete"><a href="' . $post_url . '">' . $post_title . '</a>' .
            ($this->rs->post_type != 'post' ? ' (' . html::escapeHTML($this->rs->post_type) . ')' : '') . '</td>';

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminCommentListValue', $this->core, $this->rs, $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}

class adminBlogList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No blog matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No blog') . '</strong></p>';
            }
        } else {
            $blogs = array();
            if (isset($_REQUEST['blogs'])) {
                foreach ($_REQUEST['blogs'] as $v) {
                    $blogs[$v] = true;
                }
            }

            $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);

            $cols = array(
                'blog'   => '<th' .
                ($this->core->auth->isSuperAdmin() ? ' colspan="2"' : '') .
                ' scope="col" abbr="comm" class="first nowrap">' . __('Blog id') . '</th>',
                'name'   => '<th scope="col" abbr="name">' . __('Blog name') . '</th>',
                'url'    => '<th scope="col" class="nowrap">' . __('URL') . '</th>',
                'posts'  => '<th scope="col" class="nowrap">' . __('Entries (all types)') . '</th>',
                'upddt'  => '<th scope="col" class="nowrap">' . __('Last update') . '</th>',
                'status' => '<th scope="col" class="txt-center">' . __('Status') . '</th>'
            );

            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminBlogListHeader', $this->core, $this->rs, $cols);

            $html_block =
            '<div class="table-outer"><table>' .
            ($filter ?
                '<caption>' .
                sprintf(__('%d blog matches the filter.', '%d blogs match the filter.', $this->rs_count), $this->rs_count) .
                '</caption>'
                :
                '<caption class="hidden">' . __('Blogs list') . '</caption>'
            ) .
            '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';

            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            $blocks = explode('%s', $html_block);

            echo $pager->getLinks();

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->blogLine(isset($blogs[$this->rs->blog_id]));
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function blogLine($checked = false)
    {
        $blog_id = html::escapeHTML($this->rs->blog_id);

        $cols = array(
            'check'  =>
            ($this->core->auth->isSuperAdmin() ?
                '<td class="nowrap">' .
                form::checkbox(array('blogs[]'), $this->rs->blog_id, $checked) .
                '</td>' : ''),
            'blog'   =>
            '<td class="nowrap">' .
            ($this->core->auth->isSuperAdmin() ?
                '<a href="' . $this->core->adminurl->get("admin.blog", array('id' => $blog_id)) . '"  ' .
                'title="' . sprintf(__('Edit blog settings for %s'), $blog_id) . '">' .
                '<img src="images/edit-mini.png" alt="' . __('Edit blog settings') . '" /> ' . $blog_id . '</a> ' :
                $blog_id . ' ') .
            '</td>',
            'name'   =>
            '<td class="maximal">' .
            '<a href="' . $this->core->adminurl->get("admin.home", array('switchblog' => $this->rs->blog_id)) . '" ' .
            'title="' . sprintf(__('Switch to blog %s'), $this->rs->blog_id) . '">' .
            html::escapeHTML($this->rs->blog_name) . '</a>' .
            '</td>',
            'url'    =>
            '<td class="nowrap">' .
            '<a class="outgoing" href="' .
            html::escapeHTML($this->rs->blog_url) . '">' . html::escapeHTML($this->rs->blog_url) .
            ' <img src="images/outgoing-link.svg" alt="" /></a></td>',
            'posts'  =>
            '<td class="nowrap count">' .
            $this->core->countBlogPosts($this->rs->blog_id) .
            '</td>',
            'upddt'  =>
            '<td class="nowrap count">' .
            dt::str(__('%Y-%m-%d %H:%M'), strtotime($this->rs->blog_upddt) + dt::getTimeOffset($this->core->auth->getInfo('user_tz'))) .
            '</td>',
            'status' =>
            '<td class="nowrap status txt-center">' .
            sprintf(
                '<img src="images/%1$s.png" alt="%2$s" title="%2$s" />',
                ($this->rs->blog_status == 1 ? 'check-on' : ($this->rs->blog_status == 0 ? 'check-off' : 'check-wrn')),
                $this->core->getBlogStatus($this->rs->blog_status)
            ) .
            '</td>'
        );

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminBlogListValue', $this->core, $this->rs, $cols);

        return
        '<tr class="line" id="b' . $blog_id . '">' .
        implode(iterator_to_array($cols)) .
            '</tr>';
    }
}

class adminUserList extends adminGenericList
{
    public function display($page, $nb_per_page, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No user matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No user') . '</strong></p>';
            }
        } else {
            $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);

            $html_block =
                '<div class="table-outer clear">' .
                '<table>';

            if ($filter) {
                $html_block .= '<caption>' . sprintf(__('List of %s users match the filter.'), $this->rs_count) . '</caption>';
            } else {
                $html_block .= '<caption class="hidden">' . __('Users list') . '</caption>';
            }

            $cols = array(
                'username'     => '<th colspan="2" scope="col" class="first">' . __('Username') . '</th>',
                'first_name'   => '<th scope="col">' . __('First Name') . '</th>',
                'last_name'    => '<th scope="col">' . __('Last Name') . '</th>',
                'display_name' => '<th scope="col">' . __('Display name') . '</th>',
                'entries'      => '<th scope="col" class="nowrap">' . __('Entries (all types)') . '</th>'
            );

            $cols = new ArrayObject($cols);
            $this->core->callBehavior('adminUserListHeader', $this->core, $this->rs, $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';
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

            echo $pager->getLinks();
        }
    }

    private function userLine()
    {
        $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $img_status = '';

        $p = $this->core->getUserPermissions($this->rs->user_id);

        if (isset($p[$this->core->blog->id]['p']['admin'])) {
            $img_status = sprintf($img, __('admin'), 'admin.png');
        }
        if ($this->rs->user_super) {
            $img_status = sprintf($img, __('superadmin'), 'superadmin.png');
        }

        $res = '<tr class="line">';

        $cols = array(
            'check'        => '<td class="nowrap">' . form::hidden(array('nb_post[]'), (integer) $this->rs->nb_post) .
            form::checkbox(array('users[]'), $this->rs->user_id) . '</td>',
            'username'     => '<td class="maximal" scope="row"><a href="' .
            $this->core->adminurl->get('admin.user', array('id' => $this->rs->user_id)) . '">' .
            $this->rs->user_id . '</a>&nbsp;' . $img_status . '</td>',
            'first_name'   => '<td class="nowrap">' . html::escapeHTML($this->rs->user_firstname) . '</td>',
            'last_name'    => '<td class="nowrap">' . html::escapeHTML($this->rs->user_name) . '</td>',
            'display_name' => '<td class="nowrap">' . html::escapeHTML($this->rs->user_displayname) . '</td>',
            'entries'      => '<td class="nowrap count"><a href="' .
            $this->core->adminurl->get('admin.posts', array('user_id' => $this->rs->user_id)) . '">' .
            $this->rs->nb_post . '</a></td>'
        );

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminUserListValue', $this->core, $this->rs, $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}
