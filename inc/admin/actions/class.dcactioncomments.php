<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcCommentsActionsPage extends dcActionsPage
{
    public function __construct($core, $uri, $redirect_args = array())
    {
        parent::__construct($core, $uri, $redirect_args);
        $this->redirect_fields = array('type', 'author', 'status',
            'sortby', 'ip', 'order', 'page', 'nb', 'section');
        $this->field_entries = 'comments';
        $this->title_cb      = __('Comments');
        $this->loadDefaults();
        $core->callBehavior('adminCommentsActionsPage', $core, $this);
    }

    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action
        // to be setup first
        dcDefaultCommentActions::adminCommentsActionsPage($this->core, $this);
    }

    public function beginPage($breadcrumb = '', $head = '')
    {
        if ($this->in_plugin) {
            echo '<html><head><title>' . __('Comments') . '</title>' .
            dcPage::jsLoad('js/_comments_actions.js') .
                $head .
                '</script></head><body>' .
                $breadcrumb;
        } else {
            dcPage::open(
                __('Comments'),
                dcPage::jsLoad('js/_comments_actions.js') .
                $head,
                $breadcrumb
            );

        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to comments list') . '</a></p>';
    }

    public function endPage()
    {
        dcPage::close();
    }

    public function error(Exception $e)
    {
        $this->core->error->add($e->getMessage());
        $this->beginPage(dcPage::breadcrumb(
            array(
                html::escapeHTML($this->core->blog->name) => '',
                __('Comments')                            => $this->core->adminurl->get('admin.comments'),
                __('Comments actions')                    => ''
            ))
        );
        $this->endPage();
    }

    /**
     * getcheckboxes -returns html code for selected entries
     *             as a table containing entries checkboxes
     *
     * @access public
     *
     * @return string the html code for checkboxes
     */
    public function getCheckboxes()
    {
        $ret =
        '<table class="posts-list"><tr>' .
        '<th colspan="2">' . __('Author') . '</th><th>' . __('Title') . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $title) {
            $ret .=
            '<tr><td class="minimal">' .
            form::checkbox(array($this->field_entries . '[]'), $id,
                array(
                    'checked' => true
                )) .
                '</td>' .
                '<td>' . $title['author'] . '</td><td>' . $title['title'] . '</td></tr>';
        }
        $ret .= '</table>';
        return $ret;
    }

    protected function fetchEntries($from)
    {
        $params = array();
        if (!empty($from['comments'])) {
            $comments = $from['comments'];

            foreach ($comments as $k => $v) {
                $comments[$k] = (integer) $v;
            }

            $params['sql'] = 'AND C.comment_id IN(' . implode(',', $comments) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        if (!isset($from['full_content']) || empty($from['full_content'])) {
            $params['no_content'] = true;
        }
        $co = $this->core->blog->getComments($params);
        while ($co->fetch()) {
            $this->entries[$co->comment_id] = array(
                'title'  => $co->post_title,
                'author' => $co->comment_author
            );
        }
        $this->rs = $co;
    }
}

class dcDefaultCommentActions
{
    public static function adminCommentsActionsPage($core, dcCommentsActionsPage $ap)
    {
        if ($core->auth->check('publish,contentadmin', $core->blog->id)) {
            $ap->addAction(
                array(__('Status') => array(
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Mark as pending') => 'pending',
                    __('Mark as junk')    => 'junk'
                )),
                array('dcDefaultCommentActions', 'doChangeCommentStatus')
            );
        }

        if ($core->auth->check('delete,contentadmin', $core->blog->id)) {
            $ap->addAction(
                array(__('Delete') => array(
                    __('Delete') => 'delete')),
                array('dcDefaultCommentActions', 'doDeleteComment')
            );
        }

        $ip_filter_active = true;
        if ($core->blog->settings->antispam->antispam_filters !== null) {
            $filters_opt = $core->blog->settings->antispam->antispam_filters;
            if (is_array($filters_opt)) {
                $ip_filter_active = isset($filters_opt['dcFilterIP']) && is_array($filters_opt['dcFilterIP']) && $filters_opt['dcFilterIP'][0] == 1;
            }
        }

        if ($ip_filter_active) {
            $blacklist_actions = array(__('Blacklist IP') => 'blacklist');
            if ($core->auth->isSuperAdmin()) {
                $blacklist_actions[__('Blacklist IP (global)')] = 'blacklist_global';
            }

            $ap->addAction(
                array(__('IP address') => $blacklist_actions),
                array('dcDefaultCommentActions', 'doBlacklistIP')
            );
        }
    }

    public static function doChangeCommentStatus($core, dcCommentsActionsPage $ap, $post)
    {
        $action = $ap->getAction();
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }
        switch ($action) {
            case 'unpublish':$status = 0;
                break;
            case 'pending':$status = -1;
                break;
            case 'junk':$status = -2;
                break;
            default:$status = 1;
                break;
        }

        $core->blog->updCommentsStatus($co_ids, $status);

        dcPage::addSuccessNotice(__('Selected comments have been successfully updated.'));
        $ap->redirect(true);
    }

    public static function doDeleteComment($core, dcCommentsActionsPage $ap, $post)
    {
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }
        // Backward compatibility
        foreach ($co_ids as $comment_id) {
            # --BEHAVIOR-- adminBeforeCommentDelete
            $core->callBehavior('adminBeforeCommentDelete', $comment_id);
        }

        # --BEHAVIOR-- adminBeforeCommentsDelete
        $core->callBehavior('adminBeforeCommentsDelete', $co_ids);

        $core->blog->delComments($co_ids);
        dcPage::addSuccessNotice(__('Selected comments have been successfully deleted.'));
        $ap->redirect(false);
    }

    public static function doBlacklistIP($core, dcCommentsActionsPage $ap, $post)
    {
        $action = $ap->getAction();
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }

        $global = !empty($action) && $action == 'blacklist_global' && $core->auth->isSuperAdmin();

        $ip_filter = new dcFilterIP($core);
        $rs        = $ap->getRS();
        while ($rs->fetch()) {
            $ip_filter->addIP('black', $rs->comment_ip, $global);
        }

        dcPage::addSuccessNotice(__('IP addresses for selected comments have been blacklisted.'));
        $ap->redirect(true);
    }
}
