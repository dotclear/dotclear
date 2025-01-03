<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend\Action;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Schema\Extension\User;
use Exception;

/**
 * @brief   Handler for default actions on posts.
 */
class ActionsPostsDefault
{
    /**
     * Set posts actions.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     */
    public static function adminPostsActionsPage(ActionsPosts $ap): void
    {
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Status') => [
                    __('Publish')         => 'publish',
                    __('Unpublish')       => 'unpublish',
                    __('Schedule')        => 'schedule',
                    __('Mark as pending') => 'pending',
                ]],
                self::doChangePostStatus(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('First publication') => [
                    __('Never published')   => 'never',
                    __('Already published') => 'already',
                ]],
                self::doChangePostFirstPub(...)
            );
        }
        $ap->addAction(
            [__('Mark') => [
                __('Mark as selected')   => 'selected',
                __('Mark as unselected') => 'unselected',
            ]],
            self::doUpdateSelectedPost(...)
        );
        $ap->addAction(
            [__('Change') => [
                __('Change category') => 'category',
            ]],
            self::doChangePostCategory(...)
        );
        $ap->addAction(
            [__('Change') => [
                __('Change language') => 'lang',
            ]],
            self::doChangePostLang(...)
        );
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Change') => [
                    __('Change author') => 'author', ]],
                self::doChangePostAuthor(...)
            );
        }
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())) {
            $ap->addAction(
                [__('Delete') => [
                    __('Delete') => 'delete', ]],
                self::doDeletePost(...)
            );
        }
    }

    /**
     * Does a change post status.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     *
     * @throws  Exception
     */
    public static function doChangePostStatus(ActionsPosts $ap): void
    {
        $status = match ($ap->getAction()) {
            'unpublish' => App::blog()::POST_UNPUBLISHED,
            'schedule'  => App::blog()::POST_SCHEDULED,
            'pending'   => App::blog()::POST_PENDING,
            default     => App::blog()::POST_PUBLISHED,
        };

        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }

        // Do not switch to scheduled already published entries
        if ($status === App::blog()::POST_SCHEDULED) {
            $rs           = $ap->getRS();
            $excluded_ids = [];
            if ($rs->rows()) {
                while ($rs->fetch()) {
                    if ((int) $rs->post_status === App::blog()::POST_PUBLISHED) {
                        $excluded_ids[] = (int) $rs->post_id;
                    }
                }
            }
            if ($excluded_ids !== []) {
                $ids = array_diff($ids, $excluded_ids);
            }
        }
        if ($ids === []) {
            throw new Exception(__('Published entries cannot be set to scheduled'));
        }

        // Set status of remaining entries
        App::blog()->updPostsStatus($ids, $status);

        Notices::addSuccessNotice(
            sprintf(
                __(
                    '%d entry has been successfully updated to status : "%s"',
                    '%d entries have been successfully updated to status : "%s"',
                    count($ids)
                ),
                count($ids),
                App::blog()->getPostStatus($status)
            )
        );
        $ap->redirect(true);
    }

    /**
     * Does a change post status.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     *
     * @throws  Exception
     */
    public static function doChangePostFirstPub(ActionsPosts $ap): void
    {
        $status = match ($ap->getAction()) {
            'never'   => 0,
            'already' => 1,
            default   => null,
        };

        if (!is_null($status)) {
            $ids = $ap->getIDs();
            if ($ids === []) {
                throw new Exception(__('No entry selected'));
            }

            // Set first publication flag of entries
            App::blog()->updPostsFirstPub($ids, $status);

            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully updated as: "%s"',
                        '%d entries have been successfully updated as: "%s"',
                        count($ids)
                    ),
                    count($ids),
                    $status !== 0 ? __('Already published') : __('Never published')
                )
            );
        }
        $ap->redirect(true);
    }

    /**
     * Does an update selected post.
     *
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     *
     * @throws  Exception
     */
    public static function doUpdateSelectedPost(ActionsPosts $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }

        $action = $ap->getAction();
        App::blog()->updPostsSelected($ids, $action === 'selected');
        if ($action == 'selected') {
            Notices::addSuccessNotice(
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
            Notices::addSuccessNotice(
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
     * @param   ActionsPosts    $ap     The ActionsPosts instance
     *
     * @throws  Exception
     */
    public static function doDeletePost(ActionsPosts $ap): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }
        // Backward compatibility
        foreach ($ids as $id) {
            # --BEHAVIOR-- adminBeforePostDelete -- int
            App::behavior()->callBehavior('adminBeforePostDelete', (int) $id);
        }

        # --BEHAVIOR-- adminBeforePostsDelete -- array<int,string>
        App::behavior()->callBehavior('adminBeforePostsDelete', $ids);

        App::blog()->delPosts($ids);
        Notices::addSuccessNotice(
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
     * @param   ActionsPosts                $ap     The ActionsPosts instance
     * @param   ArrayObject<string, mixed>  $post   The parameters ($_POST)
     *
     * @throws  Exception   If no entry selected
     */
    public static function doChangePostCategory(ActionsPosts $ap, ArrayObject $post): void
    {
        if (isset($post['new_cat_id'])) {
            $ids = $ap->getIDs();
            if ($ids === []) {
                throw new Exception(__('No entry selected'));
            }
            $new_cat_id = (int) $post['new_cat_id'];
            if (!empty($post['new_cat_title']) && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())) {
                $cur_cat            = App::blog()->categories()->openCategoryCursor();
                $cur_cat->cat_title = $post['new_cat_title'];
                $cur_cat->cat_url   = '';

                $parent_cat = empty($post['new_cat_parent']) ? '' : $post['new_cat_parent'];

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                $new_cat_id = App::blog()->addCategory($cur_cat, (int) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string
                App::behavior()->callBehavior('adminAfterCategoryCreate', $cur_cat, $new_cat_id);
            }

            App::blog()->updPostsCategory($ids, $new_cat_id);
            $title = __('(No cat)');
            if ($new_cat_id !== 0) {
                $title = App::blog()->getCategory($new_cat_id)->cat_title;
            }
            Notices::addSuccessNotice(
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
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())    => '',
                        $ap->getCallerTitle()                    => $ap->getRedirection(true),
                        __('Change category for this selection') => '',
                    ]
                )
            );
            # categories list
            # Getting categories
            $categories_combo = Combos::getCategoriesCombo(
                App::blog()->getCategories()
            );

            $items = [
                $ap->checkboxes(),
                (new Para())
                    ->items([
                        (new Label(__('Category:'), Label::OUTSIDE_LABEL_BEFORE))
                            ->for('new_cat_id'),
                        (new Select('new_cat_id'))
                            ->items($categories_combo)
                            ->default(''),
                    ]),
            ];

            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())) {
                $items[] = (new Div())
                    ->items([
                        (new Text('p', __('Create a new category for the post(s)')))
                            ->id('new_cat'),
                        (new Para())
                            ->items([
                                (new Label(__('Title:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('new_cat_title'),
                                (new Input('new_cat_title'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(''),
                            ]),
                        (new Para())
                            ->items([
                                (new Label(__('Parent:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('new_cat_parent'),
                                (new Select('new_cat_parent'))
                                    ->items($categories_combo)
                                    ->default(''),
                            ]),
                    ]);
            }

            $items[] = (new Para())
                ->items([
                    App::nonce()->formNonce(),
                    ...$ap->hiddenFields(),
                    (new Hidden('action', 'category')),
                    (new Submit('save'))
                        ->value(__('Save')),
                ]);

            echo (new Form('dochangepostcategory'))
                ->method('post')
                ->action($ap->getURI())
                ->fields($items)
                ->render();

            $ap->endPage();
        }
    }

    /**
     * Does a change post author.
     *
     * @param   ActionsPosts                    $ap     The ActionsPosts instance
     * @param   ArrayObject<string, mixed>      $post   The parameters ($_POST)
     *
     * @throws  Exception   If no entry selected
     */
    public static function doChangePostAuthor(ActionsPosts $ap, ArrayObject $post): void
    {
        if (isset($post['new_auth_id']) && App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]), App::blog()->id())) {
            $new_user_id = $post['new_auth_id'];
            $ids         = $ap->getIDs();
            if ($ids === []) {
                throw new Exception(__('No entry selected'));
            }
            if (App::users()->getUser($new_user_id)->isEmpty()) {
                throw new Exception(__('This user does not exist'));
            }

            $cur          = App::blog()->openPostCursor();
            $cur->user_id = $new_user_id;

            $sql = new UpdateStatement();
            $sql
                ->where('post_id ' . $sql->in($ids))
                ->update($cur);

            Notices::addSuccessNotice(
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
            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id())) {
                $params = [
                    'limit' => 100,
                    'order' => 'nb_post DESC',
                ];
                $rs       = App::users()->getUsers($params);
                $rsStatic = $rs->toStatic();
                $rsStatic->extend(User::class);
                $rsStatic = $rsStatic->toExtStatic();
                $rsStatic->lexicalSort('user_id');
                while ($rsStatic->fetch()) {
                    $usersList[] = $rsStatic->user_id;
                }
            }
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())  => '',
                        $ap->getCallerTitle()                  => $ap->getRedirection(true),
                        __('Change author for this selection') => '', ]
                ),
                Page::jsLoad('js/jquery/jquery.autocomplete.js') .
                Page::jsJson('users_list', $usersList)
            );

            echo (new Form('dochangepostauthor'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    $ap->checkboxes(),
                    (new Para())
                        ->items([
                            (new Label(__('New author (author ID):'), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('new_auth_id'),
                            (new Input('new_auth_id'))
                                ->size(20)
                                ->maxlength(255)
                                ->value(''),
                        ]),
                    (new Para())
                        ->items([
                            App::nonce()->formNonce(),
                            ...$ap->hiddenFields(),
                            (new Hidden('action', 'author')),
                            (new Submit('save'))
                                ->value(__('Save')),

                        ]),
                ])
                ->render();

            $ap->endPage();
        }
    }

    /**
     * Does a change post language.
     *
     * @param   ActionsPosts                    $ap     The ActionsPosts instance
     * @param   ArrayObject<string, mixed>      $post   The parameters ($_POST)
     *
     * @throws  Exception   If no entry selected
     */
    public static function doChangePostLang(ActionsPosts $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();
        if ($ids === []) {
            throw new Exception(__('No entry selected'));
        }
        if (isset($post['new_lang'])) {
            $new_lang       = $post['new_lang'];
            $cur            = App::blog()->openPostCursor();
            $cur->post_lang = $new_lang;

            $sql = new UpdateStatement();
            $sql
                ->where('post_id ' . $sql->in($ids))
                ->update($cur);

            Notices::addSuccessNotice(
                sprintf(
                    __(
                        '%d entry has been successfully set to language "%s"',
                        '%d entries have been successfully set to language "%s"',
                        count($ids)
                    ),
                    count($ids),
                    Html::escapeHTML(L10n::getLanguageName($new_lang))
                )
            );
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())    => '',
                        $ap->getCallerTitle()                    => $ap->getRedirection(true),
                        __('Change language for this selection') => '',
                    ]
                )
            );
            # lang list
            # Languages combo
            $rs         = App::blog()->getLangs(['order' => 'asc']);
            $all_langs  = L10n::getISOcodes(false, true);
            $lang_combo = ['' => '', __('Most used') => [], __('Available') => L10n::getISOcodes(true, true)];
            while ($rs->fetch()) {
                if (isset($all_langs[$rs->post_lang])) {
                    $lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;  // @phpstan-ignore-line
                    unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
                } else {
                    $lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
                }
            }
            unset($all_langs, $rs);

            echo (new Form('dochangepostlang'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    $ap->checkboxes(),
                    (new Para())
                        ->items([
                            (new Label(__('Entry language:'), Label::OUTSIDE_LABEL_BEFORE))
                                ->for('new_lang'),
                            (new Select('new_lang'))
                                ->items($lang_combo)
                                ->default(''),
                        ]),
                    (new Para())
                        ->items([
                            App::nonce()->formNonce(),
                            ...$ap->hiddenFields(),
                            (new Hidden('action', 'lang')),
                            (new Submit('save'))
                                ->value(__('Save')),

                        ]),
                ])
                ->render();

            $ap->endPage();
        }
    }
}
