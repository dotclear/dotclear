<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Core\Backend\Combos;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

class dcPostsActions extends dcActions
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
            'user_id', 'cat_id', 'status', 'selected', 'attachment', 'month', 'lang', 'sortby', 'order', 'page', 'nb',
        ];

        $this->loadDefaults();
    }

    /**
     * Set posts actions
     */
    protected function loadDefaults()
    {
        // We could have added a behavior here, but we want default action to be setup first
        dcDefaultPostActions::adminPostsActionsPage($this);
        # --BEHAVIOR-- adminPostsActions -- dcActions
        dcCore::app()->callBehavior('adminPostsActions', $this);
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
            dcPage::openModule(
                __('Posts'),
                dcPage::jsLoad('js/_posts_actions.js') .
                $head
            );
            echo $breadcrumb;
        } else {
            dcPage::open(
                __('Posts'),
                dcPage::jsLoad('js/_posts_actions.js') .
                $head,
                $breadcrumb
            );
        }
        echo '<p><a class="back" href="' . $this->getRedirection(true) . '">' . __('Back to entries list') . '</a></p>';
    }

    /**
     * Ends a page.
     */
    public function endPage()
    {
        if ($this->in_plugin) {
            dcPage::closeModule();
        } else {
            dcPage::close();
        }
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
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    $this->getCallerTitle()                     => $this->getRedirection(true),
                    __('Posts actions')                         => '',
                ]
            )
        );
        $this->endPage();
    }

    /**
     * Fetches entries.
     *
     * @param      ArrayObject  $from   The parameters ($_POST)
     */
    protected function fetchEntries(ArrayObject $from)
    {
        $params = [];
        if (!empty($from['entries'])) {
            $entries = $from['entries'];

            foreach ($entries as $k => $v) {
                $entries[$k] = (int) $v;
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

        $rs = dcCore::app()->blog->getPosts($params);
        while ($rs->fetch()) {
            $this->entries[$rs->post_id] = $rs->post_title;
        }
        $this->rs = $rs;
    }
}

class dcDefaultPostActions
{
    /**
     * Set posts actions
     *
     * @param      dcPostsActions  $ap
     */
    public static function adminPostsActionsPage(dcPostsActions $ap)
    {
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                [self::class, 'doChangePostStatus']
            );
        }
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('First publication') => [
                    __('Never published')   => 'never',
                    __('Already published') => 'already',
                ]],
                [self::class, 'doChangePostFirstPub']
            );
        }
        $ap->addAction(
            [__('Mark') => [
                __('Mark as selected')   => 'selected',
                __('Mark as unselected') => 'unselected',
            ]],
            [self::class, 'doUpdateSelectedPost']
        );
        $ap->addAction(
            [__('Change') => [
                __('Change category') => 'category',
            ]],
            [self::class, 'doChangePostCategory']
        );
        $ap->addAction(
            [__('Change') => [
                __('Change language') => 'lang',
            ]],
            [self::class, 'doChangePostLang']
        );
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                [self::class, 'doChangePostAuthor']
            );
        }
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_DELETE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                [self::class, 'doDeletePost']
            );
        }
    }

    /**
     * Does a change post status.
     *
     * @param      dcPostsActions  $ap
     *
     * @throws     Exception             (description)
     */
    public static function doChangePostStatus(dcPostsActions $ap)
    {
        switch ($ap->getAction()) {
            case 'unpublish':
                $status = dcBlog::POST_UNPUBLISHED;

                break;
            case 'schedule':
                $status = dcBlog::POST_SCHEDULED;

                break;
            case 'pending':
                $status = dcBlog::POST_PENDING;

                break;
            default:
                $status = dcBlog::POST_PUBLISHED;

                break;
        }

        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No entry selected'));
        }

        // Do not switch to scheduled already published entries
        if ($status === dcBlog::POST_SCHEDULED) {
            $rs           = $ap->getRS();
            $excluded_ids = [];
            if ($rs->rows()) {
                while ($rs->fetch()) {
                    if ((int) $rs->post_status === dcBlog::POST_PUBLISHED) {
                        $excluded_ids[] = (int) $rs->post_id;
                    }
                }
            }
            if (count($excluded_ids)) {
                $ids = array_diff($ids, $excluded_ids);
            }
        }
        if (count($ids) === 0) {
            throw new Exception(__('Published entries cannot be set to scheduled'));
        }

        // Set status of remaining entries
        dcCore::app()->blog->updPostsStatus($ids, $status);

        dcPage::addSuccessNotice(
            sprintf(
                __(
                    '%d entry has been successfully updated to status : "%s"',
                    '%d entries have been successfully updated to status : "%s"',
                    count($ids)
                ),
                count($ids),
                dcCore::app()->blog->getPostStatus($status)
            )
        );
        $ap->redirect(true);
    }

    /**
     * Does a change post status.
     *
     * @param      dcPostsActions  $ap
     *
     * @throws     Exception             (description)
     */
    public static function doChangePostFirstPub(dcPostsActions $ap)
    {
        $status = null;
        switch ($ap->getAction()) {
            case 'never':
                $status = 0;

                break;
            case 'already':
                $status = 1;
        }

        if (!is_null($status)) {
            $ids = $ap->getIDs();
            if (empty($ids)) {
                throw new Exception(__('No entry selected'));
            }

            // Set first publication flag of entries
            dcCore::app()->blog->updPostsFirstPub($ids, $status);

            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully updated as: "%s"',
                        '%d entries have been successfully updated as: "%s"',
                        count($ids)
                    ),
                    count($ids),
                    $status ? __('Already published') : __('Never published')
                )
            );
        }
        $ap->redirect(true);
    }

    /**
     * Does an update selected post.
     *
     * @param      dcPostsActions  $ap
     *
     * @throws     Exception
     */
    public static function doUpdateSelectedPost(dcPostsActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No entry selected'));
        }

        $action = $ap->getAction();
        dcCore::app()->blog->updPostsSelected($ids, $action === 'selected');
        if ($action == 'selected') {
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully marked as selected',
                        '%d entries have been successfully marked as selected',
                        count($ids)
                    ),
                    count($ids)
                )
            );
        } else {
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully marked as unselected',
                        '%d entries have been successfully marked as unselected',
                        count($ids)
                    ),
                    count($ids)
                )
            );
        }
        $ap->redirect(true);
    }

    /**
     * Does a delete post.
     *
     * @param      dcPostsActions  $ap
     *
     * @throws     Exception
     */
    public static function doDeletePost(dcPostsActions $ap)
    {
        $ids = $ap->getIDs();
        if (empty($ids)) {
            throw new Exception(__('No entry selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforePostDelete -- int
            dcCore::app()->callBehavior('adminBeforePostDelete', (int) $id);
        }

        # --BEHAVIOR-- adminBeforePostsDelete -- array<int,string>
        dcCore::app()->callBehavior('adminBeforePostsDelete', $ids);

        dcCore::app()->blog->delPosts($ids);
        dcPage::addSuccessNotice(
            sprintf(
                __(
                    '%d entry has been successfully deleted',
                    '%d entries have been successfully deleted',
                    count($ids)
                ),
                count($ids)
            )
        );

        $ap->redirect(false);
    }

    /**
     * Does a change post category.
     *
     * @param      dcPostsActions       $ap
     * @param      ArrayObject          $post   The parameters ($_POST)
     *
     * @throws     Exception             If no entry selected
     */
    public static function doChangePostCategory(dcPostsActions $ap, ArrayObject $post)
    {
        if (isset($post['new_cat_id'])) {
            $ids = $ap->getIDs();
            if (empty($ids)) {
                throw new Exception(__('No entry selected'));
            }
            $new_cat_id = $post['new_cat_id'];
            if (!empty($post['new_cat_title']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CATEGORIES,
            ]), dcCore::app()->blog->id)) {
                $cur_cat            = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME);
                $cur_cat->cat_title = $post['new_cat_title'];
                $cur_cat->cat_url   = '';
                $title              = $cur_cat->cat_title;

                $parent_cat = !empty($post['new_cat_parent']) ? $post['new_cat_parent'] : '';

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                dcCore::app()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                $new_cat_id = dcCore::app()->blog->addCategory($cur_cat, (int) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string
                dcCore::app()->callBehavior('adminAfterCategoryCreate', $cur_cat, $new_cat_id);
            }

            dcCore::app()->blog->updPostsCategory($ids, $new_cat_id);
            $title = __('(No cat)');
            if ($new_cat_id) {
                $title = dcCore::app()->blog->getCategory($new_cat_id)->cat_title;
            }
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully moved to category "%s"',
                        '%d entries have been successfully moved to category "%s"',
                        count($ids)
                    ),
                    count($ids),
                    Html::escapeHTML($title)
                )
            );

            $ap->redirect(true);
        } else {
            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        Html::escapeHTML(dcCore::app()->blog->name) => '',
                        $ap->getCallerTitle()                       => $ap->getRedirection(true),
                        __('Change category for this selection')    => '',
                    ]
                )
            );
            # categories list
            # Getting categories
            $categories_combo = Combos::getCategoriesCombo(
                dcCore::app()->blog->getCategories()
            );
            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="new_cat_id" class="classic">' . __('Category:') . '</label> ' .
            form::combo(['new_cat_id'], $categories_combo);

            if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CATEGORIES,
            ]), dcCore::app()->blog->id)) {
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
            dcCore::app()->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'category') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }

    /**
     * Does a change post author.
     *
     * @param      dcPostsActions  $ap
     * @param      ArrayObject           $post   The parameters ($_POST)
     *
     * @throws     Exception             If no entry selected
     */
    public static function doChangePostAuthor(dcPostsActions $ap, ArrayObject $post)
    {
        if (isset($post['new_auth_id']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]), dcCore::app()->blog->id)) {
            $new_user_id = $post['new_auth_id'];
            $ids         = $ap->getIDs();
            if (empty($ids)) {
                throw new Exception(__('No entry selected'));
            }
            if (dcCore::app()->getUser($new_user_id)->isEmpty()) {
                throw new Exception(__('This user does not exist'));
            }

            $cur          = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);
            $cur->user_id = $new_user_id;
            $cur->update('WHERE post_id ' . dcCore::app()->con->in($ids));
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully set to user "%s"',
                        '%d entries have been successfully set to user "%s"',
                        count($ids)
                    ),
                    count($ids),
                    Html::escapeHTML($new_user_id)
                )
            );

            $ap->redirect(true);
        } else {
            $usersList = [];
            if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog->id)) {
                $params = [
                    'limit' => 100,
                    'order' => 'nb_post DESC',
                ];
                $rs       = dcCore::app()->getUsers($params);
                $rsStatic = $rs->toStatic();
                $rsStatic->extend('rsExtUser');
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort('user_id');
                while ($rsStatic->fetch()) {
                    $usersList[] = $rsStatic->user_id;
                }
            }
            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        Html::escapeHTML(dcCore::app()->blog->name) => '',
                        $ap->getCallerTitle()                       => $ap->getRedirection(true),
                        __('Change author for this selection')      => '', ]
                ),
                dcPage::jsLoad('js/jquery/jquery.autocomplete.js') .
                dcPage::jsJson('users_list', $usersList)
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="new_auth_id" class="classic">' . __('New author (author ID):') . '</label> ' .
            form::field('new_auth_id', 20, 255);

            echo
            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'author') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }

    /**
     * Does a change post language.
     *
     * @param      dcPostsActions  $ap
     * @param      ArrayObject           $post   The parameters ($_POST)
     *
     * @throws     Exception             If no entry selected
     */
    public static function doChangePostLang(dcPostsActions $ap, ArrayObject $post)
    {
        $post_ids = $ap->getIDs();
        if (empty($post_ids)) {
            throw new Exception(__('No entry selected'));
        }
        if (isset($post['new_lang'])) {
            $new_lang       = $post['new_lang'];
            $cur            = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);
            $cur->post_lang = $new_lang;
            $cur->update('WHERE post_id ' . dcCore::app()->con->in($post_ids));
            dcPage::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully set to language "%s"',
                        '%d entries have been successfully set to language "%s"',
                        count($post_ids)
                    ),
                    count($post_ids),
                    Html::escapeHTML(L10n::getLanguageName($new_lang))
                )
            );
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        Html::escapeHTML(dcCore::app()->blog->name) => '',
                        $ap->getCallerTitle()                       => $ap->getRedirection(true),
                        __('Change language for this selection')    => '',
                    ]
                )
            );
            # lang list
            # Languages combo
            $rs         = dcCore::app()->blog->getLangs(['order' => 'asc']);
            $all_langs  = L10n::getISOcodes(false, true);
            $lang_combo = ['' => '', __('Most used') => [], __('Available') => L10n::getISOcodes(true, true)];
            while ($rs->fetch()) {
                if (isset($all_langs[$rs->post_lang])) {
                    $lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;
                    unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
                } else {
                    $lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
                }
            }
            unset($all_langs, $rs);

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .

            '<p><label for="new_lang" class="classic">' . __('Entry language:') . '</label> ' .
            form::combo('new_lang', $lang_combo);

            echo
            dcCore::app()->formNonce() . $ap->getHiddenFields() .
            form::hidden(['action'], 'lang') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
                '</form>';
            $ap->endPage();
        }
    }
}
