<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcBlogsActionsPage extends dcActionsPage
{
    public function __construct($core, $uri, $redirect_args = array())
    {
        parent::__construct($core, $uri, $redirect_args);
        $this->redirect_fields = array('status', 'sortby', 'order', 'page', 'nb');
        $this->field_entries   = 'blogs';
        $this->title_cb        = __('Blogs');
        $this->loadDefaults();
        $core->callBehavior('adminBlogsActionsPage', $core, $this);
    }

    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action
        // to be setup first
        dcDefaultBlogActions::adminBlogsActionsPage($this->core, $this);
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
        $this->core->error->add($e->getMessage());
        $this->beginPage(dcPage::breadcrumb(
            array(
                html::escapeHTML($this->core->blog->name) => '',
                __('Blogs')                               => $this->core->adminurl->get('admin.blogs'),
                __('Blogs actions')                       => ''
            ))
        );
        $this->endPage();
    }

    public function getCheckboxes()
    {
        $ret = '';
        foreach ($this->entries as $id => $res) {
            $ret .=
            '<tr>' .
            '<td class="minimal">' . form::checkbox(array($this->field_entries . '[]'), $id,
                array(
                    'checked' => true
                )) .
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
        $params = array();
        if (!empty($from['blogs'])) {
            $params['blog_id'] = $from['blogs'];
        }

        $bl = $this->core->getBlogs($params);
        while ($bl->fetch()) {
            $this->entries[$bl->blog_id] = array(
                'blog' => $bl->blog_id,
                'name' => $bl->blog_name
            );
        }
        $this->rs = $bl;
    }
}

class dcDefaultBlogActions
{
    public static function adminBlogsActionsPage($core, dcBlogsActionsPage $ap)
    {
        if (!$core->auth->isSuperAdmin()) {
            return;
        }

        $ap->addAction(
            array(__('Status') => array(
                __('Set online')     => 'online',
                __('Set offline')    => 'offline',
                __('Set as removed') => 'remove'
            )),
            array('dcDefaultBlogActions', 'doChangeBlogStatus')
        );
        $ap->addAction(
            array(__('Delete') => array(
                __('Delete') => 'delete')),
            array('dcDefaultBlogActions', 'doDeleteBlog')
        );
    }

    public static function doChangeBlogStatus($core, dcBlogsActionsPage $ap, $post)
    {
        if (!$core->auth->isSuperAdmin()) {
            return;
        }

        $action = $ap->getAction();
        $ids    = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }
        switch ($action) {
            case 'online':$status = 1;
                break;
            case 'offline':$status = 0;
                break;
            case 'remove':$status = -1;
                break;
            default:$status = 1;
                break;
        }

        $cur              = $core->con->openCursor($core->prefix . 'blog');
        $cur->blog_status = $status;
        //$cur->blog_upddt = date('Y-m-d H:i:s');
        $cur->update('WHERE blog_id ' . $core->con->in($ids));

        dcPage::addSuccessNotice(__('Selected blogs have been successfully updated.'));
        $ap->redirect(true);
    }

    public static function doDeleteBlog($core, dcBlogsActionsPage $ap, $post)
    {
        if (!$core->auth->isSuperAdmin()) {
            return;
        }

        $ap_ids = $ap->getIDs();
        if (empty($ap_ids)) {
            throw new Exception(__('No blog selected'));
        }

        if (!$core->auth->checkPassword($_POST['pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $ids = array();
        foreach ($ap_ids as $id) {
            if ($id == $core->blog->id) {
                dcPage::addWarningNotice(__('The current blog cannot be deleted.'));
            } else {
                $ids[] = $id;
            }
        }

        if (!empty($ids)) {
            # --BEHAVIOR-- adminBeforeBlogsDelete
            $core->callBehavior('adminBeforeBlogsDelete', $ids);

            foreach ($ids as $id) {
                $core->delBlog($id);
            }

            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d blog has been successfully deleted',
                    '%d blogs have been successfully deleted',
                    count($ids)
                ),
                count($ids))
            );
        }
        $ap->redirect(false);
    }
}
