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
class dcPagesActions extends dcPostsActions
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

        $this->redirect_fields = [];
        $this->caller_title    = __('Pages');
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
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Pages')                                 => $this->getRedirection(true),
                    __('Pages actions')                         => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Begins a page.
     *
     * @param      string  $breadcrumb  The breadcrumb
     * @param      string  $head        The head
     */
    public function beginPage(string $breadcrumb = '', string $head = '')
    {
        echo '<html><head><title>' . __('Pages') . '</title>' .
        dcPage::jsLoad('js/_posts_actions.js') .
            $head .
            '</script></head><body>' .
            $breadcrumb;
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to pages list') . '</a></p>';
    }

    /**
     * Ends a page.
     */
    public function endPage()
    {
        echo '</body></html>';
    }

    /**
     * Set pages actions
     */
    public function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        dcDefaultPageActions::adminPagesActionsPage($this);
        dcCore::app()->callBehavior('adminPagesActions', $this);
    }

    /**
     * Proceeds action handling, if any
     *
     * This method may issue an exit() if an action is being processed.
     *  If it returns, no action has been performed
     */
    public function process()
    {
        // fake action for pages reordering
        if (!empty($this->from['reorder'])) {
            $this->from['action'] = 'reorder';
        }
        $this->from['post_type'] = 'page';

        return parent::process();
    }
}

class dcDefaultPageActions
{
    /**
     * Set pages actions
     *
     * @param      dcPagesActions  $ap     { parameter_description }
     */
    public static function adminPagesActionsPage(dcPagesActions $ap)
    {
        if (dcCore::app()->auth->check('publish,contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                ['dcDefaultPostActions', 'doChangePostStatus']
            );
        }
        if (dcCore::app()->auth->check('admin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                ['dcDefaultPostActions', 'doChangePostAuthor']
            );
        }
        if (dcCore::app()->auth->check('delete,contentadmin', dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                ['dcDefaultPostActions', 'doDeletePost']
            );
        }
        $ap->addAction(
            [__('Order') => [
                __('Save order') => 'reorder', ]],
            ['dcDefaultPageActions', 'doReorderPages']
        );
    }

    /**
     * Does reorder pages.
     *
     * @param      dcPagesActions  $ap
     * @param      ArrayObject           $post   The post
     *
     * @throws     Exception             If user permission not granted
     */
    public static function doReorderPages(dcPagesActions $ap, ArrayObject $post)
    {
        foreach ($post['order'] as $post_id => $value) {
            if (!dcCore::app()->auth->check('publish,contentadmin', dcCore::app()->blog->id)) {
                throw new Exception(__('You are not allowed to change this entry status'));
            }

            $strReq = "WHERE blog_id = '" . dcCore::app()->con->escape(dcCore::app()->blog->id) . "' " .
            'AND post_id ' . dcCore::app()->con->in($post_id);

            #If user can only publish, we need to check the post's owner
            if (!dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id)) {
                $strReq .= "AND user_id = '" . dcCore::app()->con->escape(dcCore::app()->auth->userID()) . "' ";
            }

            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);

            $cur->post_position = (int) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $cur->update($strReq);
            dcCore::app()->blog->triggerBlog();
        }

        dcPage::addSuccessNotice(__('Selected pages have been successfully reordered.'));
        $ap->redirect(false);
    }
}
