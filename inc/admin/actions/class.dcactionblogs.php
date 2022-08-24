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

class dcBlogsActionsPage extends dcActionsPage
{
    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        parent::__construct(dcCore::app(), $uri, $redirect_args);
        $this->redirect_fields = ['status', 'sortby', 'order', 'page', 'nb'];
        $this->field_entries   = 'blogs';
        $this->cb_title        = __('Blogs');
        $this->loadDefaults();
        //dcCore::app()->callBehavior('adminBlogsActionsPage', dcCore::app(), $this);
        dcCore::app()->callBehavior('adminBlogsActionsPageV2', $this);
    }

    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action
        // to be setup first
        dcDefaultBlogActions::adminBlogsActionsPage(dcCore::app(), $this);
    }

    public function beginPage($breadcrumb = '', $head = '')
    {
        if ($this->in_plugin) {
            echo '<html><head><title>' . __('Blogs') . '</title>' .
            dcPage::jsLoad('js/_blogs_actions.js') .
                $head .
                '</script></head><body>' .
                $breadcrumb;
        } else {
            dcPage::open(
                __('Blogs'),
                dcPage::jsLoad('js/_blogs_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to blogs list') . '</a></p>';
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
                    __('Blogs')                                 => dcCore::app()->adminurl->get('admin.blogs'),
                    __('Blogs actions')                         => '',
                ]
            )
        );
        $this->endPage();
    }

    public function getCheckboxes()
    {
        $ret = '';
        foreach ($this->entries as $id => $res) {
            $ret .= '<tr>' .
            '<td class="minimal">' . form::checkbox(
                [$this->field_entries . '[]'],
                $id,
                [
                    'checked' => true,
                ]
            ) .
                '</td>' .
                '<td>' . $res['blog'] . '</td>' .
                '<td>' . $res['name'] . '</td>' .
                '</tr>';
        }

        return
        '<table class="blogs-list"><tr>' .
        '<th colspan="2">' . __('Blog id') . '</th><th>' . __('Blog name') . '</th>' .
            '</tr>' . $ret . '</table>';
    }

    protected function fetchEntries($from)
    {
        $params = [];
        if (!empty($from['blogs'])) {
            $params['blog_id'] = $from['blogs'];
        }

        $bl = dcCore::app()->getBlogs($params);
        while ($bl->fetch()) {
            $this->entries[$bl->blog_id] = [
                'blog' => $bl->blog_id,
                'name' => $bl->blog_name,
            ];
        }
        $this->rs = $bl;
    }
}

class dcDefaultBlogActions
{
    public static function adminBlogsActionsPage(dcCore $core, dcBlogsActionsPage $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ap->addAction(
            [__('Status') => [
                __('Set online')     => 'online',
                __('Set offline')    => 'offline',
                __('Set as removed') => 'remove',
            ]],
            ['dcDefaultBlogActions', 'doChangeBlogStatus']
        );
        $ap->addAction(
            [__('Delete') => [
                __('Delete') => 'delete', ]],
            ['dcDefaultBlogActions', 'doDeleteBlog']
        );
    }

    public static function doChangeBlogStatus(dcCore $core, dcBlogsActionsPage $ap, $post)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $action = $ap->getAction();
        $ids    = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }
        switch ($action) {
            case 'online':
                $status = 1;

                break;
            case 'offline':
                $status = 0;

                break;
            case 'remove':
                $status = -1;

                break;
            default:
                $status = 1;

                break;
        }

        $cur              = dcCore::app()->con->openCursor(dcCore::app()->prefix . 'blog');
        $cur->blog_status = $status;
        //$cur->blog_upddt = date('Y-m-d H:i:s');
        $cur->update('WHERE blog_id ' . dcCore::app()->con->in($ids));

        dcPage::addSuccessNotice(__('Selected blogs have been successfully updated.'));
        $ap->redirect(true);
    }

    public static function doDeleteBlog(dcCore $core, dcBlogsActionsPage $ap, $post)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ap_ids = $ap->getIDs();
        if (empty($ap_ids)) {
            throw new Exception(__('No blog selected'));
        }

        if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $ids = [];
        foreach ($ap_ids as $id) {
            if ($id == dcCore::app()->blog->id) {
                dcPage::addWarningNotice(__('The current blog cannot be deleted.'));
            } else {
                $ids[] = $id;
            }
        }

        if (!empty($ids)) {
            # --BEHAVIOR-- adminBeforeBlogsDelete
            dcCore::app()->callBehavior('adminBeforeBlogsDelete', $ids);

            foreach ($ids as $id) {
                dcCore::app()->delBlog($id);
            }

            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d blog has been successfully deleted',
                        '%d blogs have been successfully deleted',
                        count($ids)
                    ),
                    count($ids)
                )
            );
        }
        $ap->redirect(false);
    }
}
