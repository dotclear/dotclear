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

class dcPagesActionsPage extends dcPostsActionsPage
{
    public function __construct($core, $uri, $redirect_args = array())
    {
        parent::__construct($core, $uri, $redirect_args);
        $this->redirect_fields = array();
        $this->caller_title    = __('Pages');

    }

    public function error(Exception $e)
    {
        $this->core->error->add($e->getMessage());
        $this->beginPage(dcPage::breadcrumb(
            array(
                html::escapeHTML($this->core->blog->name) => '',
                __('Pages')                               => $this->getRedirection(true),
                __('Pages actions')                       => ''
            ))
        );
        $this->endPage();
    }

    public function beginPage($breadcrumb = '', $head = '')
    {
        echo '<html><head><title>' . __('Pages') . '</title>' .
        dcPage::jsLoad('js/_posts_actions.js') .
            $head .
            '</script></head><body>' .
            $breadcrumb;
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to pages list') . '</a></p>';

    }

    public function endPage()
    {
        echo '</body></html>';
    }

    public function loadDefaults()
    {
        DefaultPagesActions::adminPagesActionsPage($this->core, $this);
        $this->actions['reorder'] = array('dcPagesActionsPage', 'doReorderPages');
        $this->core->callBehavior('adminPagesActionsPage', $this->core, $this);

    }
    public function process()
    {
        // fake action for pages reordering
        if (!empty($this->from['reorder'])) {
            $this->from['action'] = 'reorder';
        }
        $this->from['post_type'] = 'page';
        return parent::process();
    }

    public static function doReorderPages($core, dcPostsActionsPage $ap, $post)
    {
        foreach ($post['order'] as $post_id => $value) {
            if (!$core->auth->check('publish,contentadmin', $core->blog->id)) {
                throw new Exception(__('You are not allowed to change this entry status'));
            }

            $strReq = "WHERE blog_id = '" . $core->con->escape($core->blog->id) . "' " .
            "AND post_id " . $core->con->in($post_id);

            #If user can only publish, we need to check the post's owner
            if (!$core->auth->check('contentadmin', $core->blog->id)) {
                $strReq .= "AND user_id = '" . $core->con->escape($core->auth->userID()) . "' ";
            }

            $cur = $core->con->openCursor($core->prefix . 'post');

            $cur->post_position = (integer) $value - 1;
            $cur->post_upddt    = date('Y-m-d H:i:s');

            $cur->update($strReq);
            $core->blog->triggerBlog();

        }

        dcPage::addSuccessNotice(__('Selected pages have been successfully reordered.'));
        $ap->redirect(false);
    }
}

class DefaultPagesActions
{
    public static function adminPagesActionsPage($core, $ap)
    {
        if ($core->auth->check('publish,contentadmin', $core->blog->id)) {
            $ap->addAction(
                array(__('Status') => array(
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending'
                )),
                array('dcDefaultPostActions', 'doChangePostStatus')
            );
        }
        if ($core->auth->check('admin', $core->blog->id)) {
            $ap->addAction(
                array(__('Change') => array(
                    __('Change author') => 'author')),
                array('dcDefaultPostActions', 'doChangePostAuthor')
            );
        }
        if ($core->auth->check('delete,contentadmin', $core->blog->id)) {
            $ap->addAction(
                array(__('Delete') => array(
                    __('Delete') => 'delete')),
                array('dcDefaultPostActions', 'doDeletePost')
            );
        }
    }
}
