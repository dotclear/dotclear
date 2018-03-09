<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcPostsActionsPage extends dcActionsPage
{
    public function __construct($core, $uri, $redirect_args = array())
    {
        parent::__construct($core, $uri, $redirect_args);
        $this->redirect_fields = array('user_id', 'cat_id', 'status',
            'selected', 'attachment', 'month', 'lang', 'sortby', 'order', 'page', 'nb');
        $this->loadDefaults();
    }

    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action
        // to be setup first
        dcDefaultPostActions::adminPostsActionsPage($this->core, $this);
        $this->core->callBehavior('adminPostsActionsPage', $this->core, $this);

    }

    public function beginPage($breadcrumb = '', $head = '')
    {
        if ($this->in_plugin) {
            echo '<html><head><title>' . __('Entries') . '</title>' .
            dcPage::jsLoad('js/_posts_actions.js') .
                $head .
                '</script></head><body>' .
                $breadcrumb;
        } else {
            dcPage::open(
                __('Entries'),
                dcPage::jsLoad('js/_posts_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to entries list') . '</a></p>';
    }

    public function endPage()
    {
        if ($this->in_plugin) {
            echo '</body></html>';
        } else {
            dcPage::close();
        }
    }

    public function error(Exception $e)
    {
        $this->core->error->add($e->getMessage());
        $this->beginPage(dcPage::breadcrumb(
            array(
                html::escapeHTML($this->core->blog->name) => '',
                $this->getCallerTitle()                   => $this->getRedirection(true),
                __('Entries actions')                     => ''
            ))
        );
        $this->endPage();
    }

    protected function fetchEntries($from)
    {
        $params = array();
        if (!empty($from['entries'])) {
            $entries = $from['entries'];

            foreach ($entries as $k => $v) {
                $entries[$k] = (integer) $v;
            }

            $params['sql'] = 'AND P.post_id IN(' . implode(',', $entries) . ') ';
        } else {
            $params['sql'] = 'AND 1=0 ';
        }

        if (!isset($from['full_content']) || empty($from['full_content'])) {
            $params['no_content'] = true;
        }

        if (isset($from['post_type'])) {
            $params['post_type'] = $from['post_type'];
        }

        $posts = $this->core->blog->getPosts($params);
        while ($posts->fetch()) {
            $this->entries[$posts->post_id] = $posts->post_title;
        }
        $this->rs = $posts;
    }
}

class dcDefaultPostActions
{
    public static function adminPostsActionsPage($core, $ap)
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
        $ap->addAction(
            array(__('Mark') => array(
                __('Mark as selected')   => 'selected',
                __('Mark as unselected') => 'unselected'
            )),
            array('dcDefaultPostActions', 'doUpdateSelectedPost')
        );
        $ap->addAction(
            array(__('Change') => array(
                __('Change category') => 'category'
            )),
            array('dcDefaultPostActions', 'doChangePostCategory')
        );
        $ap->addAction(
            array(__('Change') => array(
                __('Change language') => 'lang'
            )),
            array('dcDefaultPostActions', 'doChangePostLang')
        );
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

    public static function doChangePostStatus($core, dcPostsActionsPage $ap, $post)
    {
        switch ($ap->getAction()) {
            case 'unpublish':$status = 0;
                break;
            case 'schedule':$status = -1;
                break;
            case 'pending':$status = -2;
                break;
            default:$status = 1;
                break;
        }
        $posts_ids = $ap->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }
        $core->blog->updPostsStatus($posts_ids, $status);
        dcPage::addSuccessNotice(sprintf(
            __(
                '%d entry has been successfully updated to status : "%s"',
                '%d entries have been successfully updated to status : "%s"',
                count($posts_ids)
            ),
            count($posts_ids),
            $core->blog->getPostStatus($status))
        );
        $ap->redirect(true);
    }

    public static function doUpdateSelectedPost($core, dcPostsActionsPage $ap, $post)
    {
        $posts_ids = $ap->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }
        $action = $ap->getAction();
        $core->blog->updPostsSelected($posts_ids, $action == 'selected');
        if ($action == 'selected') {
            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d entry has been successfully marked as selected',
                    '%d entries have been successfully marked as selected',
                    count($posts_ids)
                ),
                count($posts_ids))
            );
        } else {
            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d entry has been successfully marked as unselected',
                    '%d entries have been successfully marked as unselected',
                    count($posts_ids)
                ),
                count($posts_ids))
            );
        }
        $ap->redirect(true);
    }

    public static function doDeletePost($core, dcPostsActionsPage $ap, $post)
    {

        $posts_ids = $ap->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }
        // Backward compatibility
        foreach ($posts_ids as $post_id) {
            # --BEHAVIOR-- adminBeforePostDelete
            $core->callBehavior('adminBeforePostDelete', (integer) $post_id);
        }

        # --BEHAVIOR-- adminBeforePostsDelete
        $core->callBehavior('adminBeforePostsDelete', $posts_ids);

        $core->blog->delPosts($posts_ids);
        dcPage::addSuccessNotice(sprintf(
            __(
                '%d entry has been successfully deleted',
                '%d entries have been successfully deleted',
                count($posts_ids)
            ),
            count($posts_ids))
        );

        $ap->redirect(false);
    }

    public static function doChangePostCategory($core, dcPostsActionsPage $ap, $post)
    {
        if (isset($post['new_cat_id'])) {
            $posts_ids = $ap->getIDs();
            if (empty($posts_ids)) {
                throw new Exception(__('No entry selected'));
            }
            $new_cat_id = $post['new_cat_id'];
            if (!empty($post['new_cat_title']) && $core->auth->check('categories', $core->blog->id)) {
                $cur_cat            = $core->con->openCursor($core->prefix . 'category');
                $cur_cat->cat_title = $post['new_cat_title'];
                $cur_cat->cat_url   = '';
                $title              = $cur_cat->cat_title;

                $parent_cat = !empty($post['new_cat_parent']) ? $post['new_cat_parent'] : '';

                # --BEHAVIOR-- adminBeforeCategoryCreate
                $core->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                $new_cat_id = $core->blog->addCategory($cur_cat, (integer) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate
                $core->callBehavior('adminAfterCategoryCreate', $cur_cat, $new_cat_id);
            }

            $core->blog->updPostsCategory($posts_ids, $new_cat_id);
            $title = $core->blog->getCategory($new_cat_id);
            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d entry has been successfully moved to category "%s"',
                    '%d entries have been successfully moved to category "%s"',
                    count($posts_ids)
                ),
                count($posts_ids),
                html::escapeHTML($title->cat_title))
            );

            $ap->redirect(true);
        } else {

            $ap->beginPage(
                dcPage::breadcrumb(
                    array(
                        html::escapeHTML($core->blog->name)      => '',
                        $ap->getCallerTitle()                    => $ap->getRedirection(true),
                        __('Change category for this selection') => ''
                    )));
            # categories list
            # Getting categories
            $categories_combo = dcAdminCombos::getCategoriesCombo(
                $core->blog->getCategories()
            );
            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="new_cat_id" class="classic">' . __('Category:') . '</label> ' .
            form::combo(array('new_cat_id'), $categories_combo);

            if ($core->auth->check('categories', $core->blog->id)) {
                echo
                '<div>' .
                '<p id="new_cat">' . __('Create a new category for the post(s)') . '</p>' .
                '<p><label for="new_cat_title">' . __('Title:') . '</label> ' .
                form::field('new_cat_title', 30, 255) . '</p>' .
                '<p><label for="new_cat_parent">' . __('Parent:') . '</label> ' .
                form::combo('new_cat_parent', $categories_combo) .
                    '</p>' .
                    '</div>';
            }

            echo
            $core->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(array('action'), 'category') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();

        }

    }
    public static function doChangePostAuthor($core, dcPostsActionsPage $ap, $post)
    {
        if (isset($post['new_auth_id']) && $core->auth->check('admin', $core->blog->id)) {
            $new_user_id = $post['new_auth_id'];
            $posts_ids   = $ap->getIDs();
            if (empty($posts_ids)) {
                throw new Exception(__('No entry selected'));
            }
            if ($core->getUser($new_user_id)->isEmpty()) {
                throw new Exception(__('This user does not exist'));
            }

            $cur          = $core->con->openCursor($core->prefix . 'post');
            $cur->user_id = $new_user_id;
            $cur->update('WHERE post_id ' . $core->con->in($posts_ids));
            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d entry has been successfully set to user "%s"',
                    '%d entries have been successfully set to user "%s"',
                    count($posts_ids)
                ),
                count($posts_ids),
                html::escapeHTML($new_user_id))
            );

            $ap->redirect(true);
        } else {
            $usersList = '';
            if ($core->auth->check('admin', $core->blog->id)) {
                $params = array(
                    'limit' => 100,
                    'order' => 'nb_post DESC'
                );
                $rs       = $core->getUsers($params);
                $rsStatic = $rs->toStatic();
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort('user_id');
                while ($rsStatic->fetch()) {
                    $usersList .= ($usersList != '' ? ',' : '') . '"' . $rsStatic->user_id . '"';
                }
            }
            $ap->beginPage(
                dcPage::breadcrumb(
                    array(
                        html::escapeHTML($core->blog->name)    => '',
                        $ap->getCallerTitle()                  => $ap->getRedirection(true),
                        __('Change author for this selection') => '')),
                dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
                '<script type="text/javascript">' . "\n" .
                'usersList = [' . $usersList . ']' . "\n" .
                "</script>\n"
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="new_auth_id" class="classic">' . __('New author (author ID):') . '</label> ' .
            form::field('new_auth_id', 20, 255);

            echo
            $core->formNonce() . $ap->getHiddenFields() .
            form::hidden(array('action'), 'author') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }
    public static function doChangePostLang($core, dcPostsActionsPage $ap, $post)
    {
        $posts_ids = $ap->getIDs();
        if (empty($posts_ids)) {
            throw new Exception(__('No entry selected'));
        }
        if (isset($post['new_lang'])) {
            $new_lang       = $post['new_lang'];
            $cur            = $core->con->openCursor($core->prefix . 'post');
            $cur->post_lang = $new_lang;
            $cur->update('WHERE post_id ' . $core->con->in($posts_ids));
            dcPage::addSuccessNotice(sprintf(
                __(
                    '%d entry has been successfully set to language "%s"',
                    '%d entries have been successfully set to language "%s"',
                    count($posts_ids)
                ),
                count($posts_ids),
                html::escapeHTML(l10n::getLanguageName($new_lang)))
            );
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                dcPage::breadcrumb(
                    array(
                        html::escapeHTML($core->blog->name)      => '',
                        $ap->getCallerTitle()                    => $ap->getRedirection(true),
                        __('Change language for this selection') => ''
                    )));
            # lang list
            # Languages combo
            $rs         = $core->blog->getLangs(array('order' => 'asc'));
            $all_langs  = l10n::getISOcodes(0, 1);
            $lang_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1, 1));
            while ($rs->fetch()) {
                if (isset($all_langs[$rs->post_lang])) {
                    $lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;
                    unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
                } else {
                    $lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
                }
            }
            unset($all_langs);
            unset($rs);

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .

            '<p><label for="new_lang" class="classic">' . __('Entry language:') . '</label> ' .
            form::combo('new_lang', $lang_combo);

            echo
            $core->formNonce() . $ap->getHiddenFields() .
            form::hidden(array('action'), 'lang') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }
}
