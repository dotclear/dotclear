<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class dcCommentsActionsPage extends dcActionsPage
{
    public function __construct($uri, $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);
        $this->redirect_fields = ['type', 'author', 'status',
            'sortby', 'ip', 'order', 'page', 'nb', 'section', ];
        $this->field_entries = 'comments';
        $this->cb_title      = __('Comments');
        $this->loadDefaults();
        dcCore::app()->callBehavior('adminCommentsActionsPageV2', $this);
    }

    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action
        // to be setup first
        dcDefaultCommentActions::adminCommentsActionsPage(dcCore::app(), $this);
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
        dcCore::app()->error->add($e->getMessage());
        $this->beginPage(
            dcPage::breadcrumb(
                [
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Comments')                              => dcCore::app()->adminurl->get('admin.comments'),
                    __('Comments actions')                      => '',
                ]
            )
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
        $ret = '<table class="posts-list"><tr>' .
        '<th colspan="2">' . __('Author') . '</th><th>' . __('Title') . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $title) {
            $ret .= '<tr><td class="minimal">' .
            form::checkbox(
                [$this->field_entries . '[]'],
                $id,
                [
                    'checked' => true,
                ]
            ) .
                '</td>' .
                '<td>' . $title['author'] . '</td><td>' . $title['title'] . '</td></tr>';
        }
        $ret .= '</table>';

        return $ret;
    }

    protected function fetchEntries($from)
    {
        $params = [];
        if (!empty($from['comments'])) {
            $comments = $from['comments'];

            foreach ($comments as $k => $v) {
                $comments[$k] = (int) $v;
            }

            $params['sql'] = 'AND C.comment_id IN(' . implode(',', $comments) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        if (!isset($from['full_content']) || empty($from['full_content'])) {
            $params['no_content'] = true;
        }
        $co = dcCore::app()->blog->getComments($params);
        while ($co->fetch()) {
            $this->entries[$co->comment_id] = [
                'title'  => $co->post_title,
                'author' => $co->comment_author,
            ];
        }
        $this->rs = $co;
    }
}

class dcDefaultCommentActions
{
    public static function adminCommentsActionsPage(dcCore $core, dcCommentsActionsPage $ap)
    {
        if (dcCore::app()->auth->check('publish,contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Mark as pending') => 'pending',
                    __('Mark as junk')    => 'junk',
                ]],
                ['dcDefaultCommentActions', 'doChangeCommentStatus']
            );
        }

        if (dcCore::app()->auth->check('delete,contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                ['dcDefaultCommentActions', 'doDeleteComment']
            );
        }

        $ip_filter_active = true;
        if (dcCore::app()->blog->settings->antispam->antispam_filters !== null) {
            $filters_opt = dcCore::app()->blog->settings->antispam->antispam_filters;
            if (is_array($filters_opt)) {
                $ip_filter_active = isset($filters_opt['dcFilterIP']) && is_array($filters_opt['dcFilterIP']) && $filters_opt['dcFilterIP'][0] == 1;
            }
        }

        if ($ip_filter_active) {
            $blocklist_actions = [__('Blocklist IP') => 'blocklist'];
            if (dcCore::app()->auth->isSuperAdmin()) {
                $blocklist_actions[__('Blocklist IP (global)')] = 'blocklist_global';
            }

            $ap->addAction(
                [__('IP address') => $blocklist_actions],
                ['dcDefaultCommentActions', 'doBlocklistIP']
            );
        }
    }

    public static function doChangeCommentStatus(dcCore $core, dcCommentsActionsPage $ap, $post)
    {
        $action = $ap->getAction();
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }
        switch ($action) {
            case 'unpublish':
                $status = 0;

                break;
            case 'pending':
                $status = -1;

                break;
            case 'junk':
                $status = -2;

                break;
            default:
                $status = 1;

                break;
        }

        dcCore::app()->blog->updCommentsStatus($co_ids, $status);

        dcPage::addSuccessNotice(__('Selected comments have been successfully updated.'));
        $ap->redirect(true);
    }

    public static function doDeleteComment(dcCore $core, dcCommentsActionsPage $ap, $post)
    {
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }
        // Backward compatibility
        foreach ($co_ids as $comment_id) {
            # --BEHAVIOR-- adminBeforeCommentDelete
            dcCore::app()->callBehavior('adminBeforeCommentDelete', $comment_id);
        }

        # --BEHAVIOR-- adminBeforeCommentsDelete
        dcCore::app()->callBehavior('adminBeforeCommentsDelete', $co_ids);

        dcCore::app()->blog->delComments($co_ids);
        dcPage::addSuccessNotice(__('Selected comments have been successfully deleted.'));
        $ap->redirect(false);
    }

    public static function doBlocklistIP(dcCore $core, dcCommentsActionsPage $ap, $post)
    {
        $action = $ap->getAction();
        $co_ids = $ap->getIDs();
        if (empty($co_ids)) {
            throw new Exception(__('No comment selected'));
        }

        $global = !empty($action) && $action == 'blocklist_global' && dcCore::app()->auth->isSuperAdmin();

        $rs = $ap->getRS();

        while ($rs->fetch()) {
            if (filter_var($rs->comment_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                // IP is an IPv6
                (new dcFilterIPv6())->addIP('blackv6', $rs->comment_ip, $global);
            } else {
                // Assume that IP is IPv4
                (new dcFilterIP())->addIP('black', $rs->comment_ip, $global);
            }
        }

        dcPage::addSuccessNotice(__('IP addresses for selected comments have been blocklisted.'));
        $ap->redirect(true);
    }
}
