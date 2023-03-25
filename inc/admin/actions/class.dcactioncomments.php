<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\antispam\Filters\Ip as dcFilterIP;
use Dotclear\Plugin\antispam\Filters\IpV6 as dcFilterIPv6;

class dcCommentsActions extends dcActions
{
    /**
     * Constructs a new instance.
     *
     * @param      null|string  $uri            The uri
     * @param      array        $redirect_args  The redirect arguments
     */
    public function __construct(?string $uri, array $redirect_args = [])
    {
        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = [
            'type', 'author', 'status', 'sortby', 'ip', 'order', 'page', 'nb', 'section',
        ];
        $this->field_entries = 'comments';
        $this->cb_title      = __('Comments');

        $this->loadDefaults();
        dcCore::app()->callBehavior('adminCommentsActions', $this);
    }

    /**
     * Loads comments actions.
     */
    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        dcDefaultCommentActions::adminCommentsActionsPage($this);
    }

    /**
     * Begins a page.
     *
     * @param      string  $breadcrumb  The breadcrumb
     * @param      string  $head        The head
     */
    public function beginPage(string $breadcrumb = '', string $head = '')
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

    /**
     * Ends a page.
     */
    public function endPage()
    {
        dcPage::close();
    }

    /**
     * Display error page
     *
     * @param      Exception  $e      { parameter_description }
     */
    public function error(Exception $e)
    {
        dcCore::app()->error->add($e->getMessage());
        $this->beginPage(
            dcPage::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Comments')                              => dcCore::app()->adminurl->get('admin.comments'),
                    __('Comments actions')                      => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Returns HTML code for selected entries as a table containing entries checkboxes
     *
     * @return string the HTML code for checkboxes
     */
    public function getCheckboxes(): string
    {
        $ret = '<table class="posts-list"><tr>' .
        '<th colspan="2">' . __('Author') . '</th><th>' . __('Title') . '</th>' .
            '</tr>';
        foreach ($this->entries as $id => $description) {
            $ret .= '<tr><td class="minimal">' .
            (new Checkbox([$this->field_entries . '[]'], true))->value($id)->render() .
                '</td>' .
                '<td>' . $description['author'] . '</td><td>' . $description['title'] . '</td></tr>';
        }
        $ret .= '</table>';

        return $ret;
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
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
        $rs = dcCore::app()->blog->getComments($params);
        while ($rs->fetch()) {
            $this->entries[$rs->comment_id] = [
                'title'  => $rs->post_title,
                'author' => $rs->comment_author,
            ];
        }
        $this->rs = $rs;
    }
}

class dcDefaultCommentActions
{
    /**
     * Set comments actions
     *
     * @param      dcCommentsActions  $ap
     */
    public static function adminCommentsActionsPage(dcCommentsActions $ap)
    {
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Mark as pending') => 'pending',
                    __('Mark as junk')    => 'junk',
                ]],
                [self::class, 'doChangeCommentStatus']
            );
        }

        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                [self::class, 'doDeleteComment']
            );
        }

        $ip_filter_active = false;
        if (dcCore::app()->blog->settings->antispam->antispam_filters !== null) {
            $filters_opt = dcCore::app()->blog->settings->antispam->antispam_filters;
            if (is_array($filters_opt)) {
                $filterActive     = fn ($name) => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
                $ip_filter_active = $filterActive('dcFilterIP') || $filterActive('dcFilterIPv6');
            }
        }

        if ($ip_filter_active) {
            $blocklist_actions = [__('Blocklist IP') => 'blocklist'];
            if (dcCore::app()->auth->isSuperAdmin()) {
                $blocklist_actions[__('Blocklist IP (global)')] = 'blocklist_global';
            }

            $ap->addAction(
                [__('IP address') => $blocklist_actions],
                [self::class, 'doBlocklistIP']
            );
        }
    }

    /**
     * Does a change comment status.
     *
     * @param      dcCommentsActions  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doChangeCommentStatus(dcCommentsActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }

        switch ($ap->getAction()) {
            case 'unpublish':
                $status = dcBlog::COMMENT_UNPUBLISHED;

                break;
            case 'pending':
                $status = dcBlog::COMMENT_PENDING;

                break;
            case 'junk':
                $status = dcBlog::COMMENT_JUNK;

                break;
            default:
                $status = dcBlog::COMMENT_PUBLISHED;

                break;
        }

        dcCore::app()->blog->updCommentsStatus($ids, $status);

        dcPage::addSuccessNotice(__('Selected comments have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete comment.
     *
     * @param      dcCommentsActions  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doDeleteComment(dcCommentsActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforeCommentDelete
            dcCore::app()->callBehavior('adminBeforeCommentDelete', $id);
        }

        # --BEHAVIOR-- adminBeforeCommentsDelete
        dcCore::app()->callBehavior('adminBeforeCommentsDelete', $ids);

        dcCore::app()->blog->delComments($ids);

        dcPage::addSuccessNotice(__('Selected comments have been successfully deleted.'));
        $ap->redirect(false);
    }

    /**
     * Add comments IP in an antispam blacklist.
     *
     * @param      dcCommentsActions  $ap
     *
     * @throws     Exception                If no comment selected
     */
    public static function doBlocklistIP(dcCommentsActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No comment selected'));
        }

        $action = $ap->getAction();
        $global = !empty($action) && $action == 'blocklist_global' && dcCore::app()->auth->isSuperAdmin();

        $filters_opt  = dcCore::app()->blog->settings->antispam->antispam_filters;
        $filterActive = fn ($name) => isset($filters_opt[$name]) && is_array($filters_opt[$name]) && $filters_opt[$name][0] == 1;
        $filters      = [
            'v4' => $filterActive('dcFilterIP'),
            'v6' => $filterActive('dcFilterIPv6'),
        ];

        $count = 0;

        if (is_array($filters_opt)) {
            $rs = $ap->getRS();
            while ($rs->fetch()) {
                if (filter_var($rs->comment_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                    // IP is an IPv6
                    if ($filters['v6']) {
                        (new dcFilterIPv6())->addIP('blackv6', $rs->comment_ip, $global);
                        $count++;
                    }
                } else {
                    // Assume that IP is IPv4
                    if ($filters['v4']) {
                        (new dcFilterIP())->addIP('black', $rs->comment_ip, $global);
                        $count++;
                    }
                }
            }

            if ($count) {
                dcPage::addSuccessNotice(__('IP addresses for selected comments have been blocklisted.'));
            }
        }
        $ap->redirect(true);
    }
}
