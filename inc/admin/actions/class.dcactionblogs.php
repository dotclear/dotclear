<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcBlogsActions extends dcActions
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
            'status', 'sortby', 'order', 'page', 'nb',
        ];
        $this->field_entries = 'blogs';
        $this->cb_title      = __('Blogs');

        $this->loadDefaults();
        dcCore::app()->callBehavior('adminBlogsActions', $this);
    }

    /**
     * Loads defaults.
     */
    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        dcDefaultBlogActions::adminBlogsActionsPage($this);
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
     * @param      Exception  $e
     */
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

    /**
     * Returns html code for selected blogs as a table containing blogs checkboxes
     *
     * @return string the html code for checkboxes
     */
    public function getCheckboxes(): string
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

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
    {
        $params = [];
        if (!empty($from['blogs'])) {
            $params['blog_id'] = $from['blogs'];
        }

        $rs = dcCore::app()->getBlogs($params);
        while ($rs->fetch()) {
            $this->entries[$rs->blog_id] = [
                'blog' => $rs->blog_id,
                'name' => $rs->blog_name,
            ];
        }
        $this->rs = $rs;
    }
}

class dcDefaultBlogActions
{
    /**
     * Set blog actions
     *
     * @param      dcBlogsActions  $ap     { parameter_description }
     */
    public static function adminBlogsActionsPage(dcBlogsActions $ap)
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
            [self::class, 'doChangeBlogStatus']
        );
        $ap->addAction(
            [__('Delete') => [
                __('Delete') => 'delete', ]],
            [self::class, 'doDeleteBlog']
        );
    }

    /**
     * Does a change blog status.
     *
     * @param      dcBlogsActions  $ap
     *
     * @throws     Exception             If no blog selected
     */
    public static function doChangeBlogStatus(dcBlogsActions $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }

        switch ($ap->getAction()) {
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

        $cur              = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME);
        $cur->blog_status = $status;
        $cur->update('WHERE blog_id ' . dcCore::app()->con->in($ids));

        dcPage::addSuccessNotice(__('Selected blogs have been successfully updated.'));
        $ap->redirect(true);
    }

    /**
     * Does a delete blog.
     *
     * @param      dcBlogsActions  $ap
     *
     * @throws     Exception             If no blog selected
     */
    public static function doDeleteBlog(dcBlogsActions $ap)
    {
        if (!dcCore::app()->auth->isSuperAdmin()) {
            return;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No blog selected'));
        }

        if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $checked_ids = [];
        foreach ($ids as $id) {
            if ($id === dcCore::app()->blog->id) {
                dcPage::addWarningNotice(__('The current blog cannot be deleted.'));
            } else {
                $checked_ids[] = $id;
            }
        }

        if (!empty($checked_ids)) {
            # --BEHAVIOR-- adminBeforeBlogsDelete
            dcCore::app()->callBehavior('adminBeforeBlogsDelete', $checked_ids);

            foreach ($checked_ids as $id) {
                dcCore::app()->delBlog($id);
            }

            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d blog has been successfully deleted',
                        '%d blogs have been successfully deleted',
                        count($checked_ids)
                    ),
                    count($checked_ids)
                )
            );
        }
        $ap->redirect(false);
    }
}
