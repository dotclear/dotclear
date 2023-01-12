<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminCategory
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CATEGORIES,
        ]));

        $blog_settings = new dcSettings(dcCore::app()->blog->id);

        dcCore::app()->admin->cat_id    = '';
        dcCore::app()->admin->cat_title = '';
        dcCore::app()->admin->cat_url   = '';
        dcCore::app()->admin->cat_desc  = '';
        dcCore::app()->admin->blog_lang = $blog_settings->system->lang;

        // Getting existing category
        dcCore::app()->admin->cat_parents         = null;
        dcCore::app()->admin->cat_parent          = 0;
        dcCore::app()->admin->cat_siblings        = [];
        dcCore::app()->admin->cat_allowed_parents = [];

        if (!empty($_REQUEST['id'])) {
            $rs = null;

            try {
                $rs = dcCore::app()->blog->getCategory($_REQUEST['id']);
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            if (!dcCore::app()->error->flag() && !$rs->isEmpty()) {
                dcCore::app()->admin->cat_id    = (int) $rs->cat_id;
                dcCore::app()->admin->cat_title = $rs->cat_title;
                dcCore::app()->admin->cat_url   = $rs->cat_url;
                dcCore::app()->admin->cat_desc  = $rs->cat_desc;
            }
            unset($rs);

            // Getting hierarchy information
            dcCore::app()->admin->cat_parents = dcCore::app()->blog->getCategoryParents(dcCore::app()->admin->cat_id);
            $rs                               = dcCore::app()->blog->getCategoryParent(dcCore::app()->admin->cat_id);
            dcCore::app()->admin->cat_parent  = $rs->isEmpty() ? 0 : (int) $rs->cat_id;

            // Allowed parents list
            $children = dcCore::app()->blog->getCategories(['start' => dcCore::app()->admin->cat_id]);

            dcCore::app()->admin->cat_allowed_parents = [__('Top level') => 0];

            $parents = [];
            while ($children->fetch()) {
                $parents[$children->cat_id] = 1;
            }

            $stack = dcCore::app()->admin->cat_allowed_parents;
            $rs    = dcCore::app()->blog->getCategories();
            while ($rs->fetch()) {
                if (!isset($parents[$rs->cat_id])) {
                    $stack[] = new formOption(
                        str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . html::escapeHTML($rs->cat_title),
                        $rs->cat_id
                    );
                }
            }
            dcCore::app()->admin->cat_allowed_parents = $stack;

            // Allowed siblings list
            $stack = dcCore::app()->admin->cat_siblings;
            $rs    = dcCore::app()->blog->getCategoryFirstChildren(dcCore::app()->admin->cat_parent);
            while ($rs->fetch()) {
                if ($rs->cat_id != dcCore::app()->admin->cat_id) {
                    $stack[html::escapeHTML($rs->cat_title)] = $rs->cat_id;
                }
            }
            dcCore::app()->admin->cat_siblings = $stack;
        }
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        if (dcCore::app()->admin->cat_id && isset($_POST['cat_parent'])) {
            // Changing parent
            $new_parent = (int) $_POST['cat_parent'];
            if (dcCore::app()->admin->cat_parent != $new_parent) {
                try {
                    dcCore::app()->blog->setCategoryParent(dcCore::app()->admin->cat_id, $new_parent);
                    dcPage::addSuccessNotice(__('The category has been successfully moved'));
                    dcCore::app()->adminurl->redirect('admin.categories');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        if (dcCore::app()->admin->cat_id && isset($_POST['cat_sibling'])) {
            // Changing sibling
            try {
                dcCore::app()->blog->setCategoryPosition(dcCore::app()->admin->cat_id, (int) $_POST['cat_sibling'], $_POST['cat_move']);
                dcPage::addSuccessNotice(__('The category has been successfully moved'));
                dcCore::app()->adminurl->redirect('admin.categories');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (isset($_POST['cat_title'])) {
            // Create or update a category
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME);

            $cur->cat_title = dcCore::app()->admin->cat_title = $_POST['cat_title'];
            if (isset($_POST['cat_desc'])) {
                $cur->cat_desc = dcCore::app()->admin->cat_desc = $_POST['cat_desc'];
            }
            if (isset($_POST['cat_url'])) {
                $cur->cat_url = dcCore::app()->admin->cat_url = $_POST['cat_url'];
            } else {
                $cur->cat_url = dcCore::app()->admin->cat_url;
            }

            try {
                if (dcCore::app()->admin->cat_id) {
                    // Update category

                    # --BEHAVIOR-- adminBeforeCategoryUpdate
                    dcCore::app()->callBehavior('adminBeforeCategoryUpdate', $cur, dcCore::app()->admin->cat_id);

                    dcCore::app()->blog->updCategory($_POST['id'], $cur);

                    # --BEHAVIOR-- adminAfterCategoryUpdate
                    dcCore::app()->callBehavior('adminAfterCategoryUpdate', $cur, dcCore::app()->admin->cat_id);

                    dcPage::addSuccessNotice(__('The category has been successfully updated.'));

                    dcCore::app()->adminurl->redirect('admin.category', ['id' => $_POST['id']]);
                } else {
                    // Create category

                    # --BEHAVIOR-- adminBeforeCategoryCreate
                    dcCore::app()->callBehavior('adminBeforeCategoryCreate', $cur);

                    $id = dcCore::app()->blog->addCategory($cur, (int) $_POST['new_cat_parent']);

                    # --BEHAVIOR-- adminAfterCategoryCreate
                    dcCore::app()->callBehavior('adminAfterCategoryCreate', $cur, $id);

                    dcPage::addSuccessNotice(sprintf(
                        __('The category "%s" has been successfully created.'),
                        html::escapeHTML($cur->cat_title)
                    ));
                    dcCore::app()->adminurl->redirect('admin.categories');
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        $title = dcCore::app()->admin->cat_id ? html::escapeHTML(dcCore::app()->admin->cat_title) : __('New category');

        $elements = [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            __('Categories')                            => dcCore::app()->adminurl->get('admin.categories'),
        ];
        if (dcCore::app()->admin->cat_id) {
            while (dcCore::app()->admin->cat_parents->fetch()) {
                $elements[html::escapeHTML(dcCore::app()->admin->cat_parents->cat_title)] = dcCore::app()->adminurl->get('admin.category', ['id' => dcCore::app()->admin->cat_parents->cat_id]);
            }
        }
        $elements[$title] = '';

        $category_editor = dcCore::app()->auth->getOption('editor');
        $rte_flag        = true;
        $rte_flags       = @dcCore::app()->auth->user_prefs->interface->rte_flags;
        if (is_array($rte_flags) && in_array('cat_descr', $rte_flags)) {
            $rte_flag = $rte_flags['cat_descr'];
        }

        dcPage::open(
            $title,
            dcPage::jsConfirmClose('category-form') .
            dcPage::jsLoad('js/_category.js') .
            ($rte_flag ? dcCore::app()->callBehavior('adminPostEditor', $category_editor['xhtml'], 'category', ['#cat_desc'], 'xhtml') : ''),
            dcPage::breadcrumb($elements)
        );

        if (!empty($_GET['upd'])) {
            dcPage::success(__('Category has been successfully updated.'));
        }

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.category') . '" method="post" id="category-form">' .
        '<h3>' . __('Category information') . '</h3>' .
        '<p><label class="required" for="cat_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label> ' .
        form::field('cat_title', 40, 255, [
            'default'    => html::escapeHTML(dcCore::app()->admin->cat_title),
            'extra_html' => 'required placeholder="' . __('Name') . '" lang="' . dcCore::app()->admin->blog_lang . '" spellcheck="true"',
        ]) .
        '</p>';

        if (!dcCore::app()->admin->cat_id) {
            $rs = dcCore::app()->blog->getCategories();
            echo
            '<p><label for="new_cat_parent">' . __('Parent:') . ' ' .
            '<select id="new_cat_parent" name="new_cat_parent" >' .
            '<option value="0">' . __('(none)') . '</option>';
            while ($rs->fetch()) {
                echo
                '<option value="' . $rs->cat_id . '" ' . (!empty($_POST['new_cat_parent']) && $_POST['new_cat_parent'] == $rs->cat_id ? 'selected="selected"' : '') . '>' . str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . html::escapeHTML($rs->cat_title) . '</option>';
            }
            echo
            '</select></label></p>';
            unset($rs);
        }
        echo
        '<div class="lockable">' .
        '<p><label for="cat_url">' . __('URL:') . '</label> '
        . form::field('cat_url', 40, 255, html::escapeHTML(dcCore::app()->admin->cat_url)) .
        '</p>' .
        '<p class="form-note warn" id="note-cat-url">' .
        __('Warning: If you set the URL manually, it may conflict with another category.') . '</p>' .
        '</div>' .

        '<p class="area"><label for="cat_desc">' . __('Description:') . '</label> ' .
        form::textarea(
            'cat_desc',
            50,
            8,
            [
                'default'    => html::escapeHTML(dcCore::app()->admin->cat_desc),
                'extra_html' => 'lang="' . dcCore::app()->admin->blog_lang . '" spellcheck="true"',
            ]
        ) .
        '</p>' .

        '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        (dcCore::app()->admin->cat_id ? form::hidden('id', dcCore::app()->admin->cat_id) : '') .
        dcCore::app()->formNonce() .
        '</p>' .
        '</form>';

        if (dcCore::app()->admin->cat_id) {
            echo
            '<h3 class="border-top">' . __('Move this category') . '</h3>' .
            '<div class="two-cols">';

            echo (new formDiv())
                ->class('col')
                ->items([
                    (new formForm('cat-parent-form'))
                        ->action(dcCore::app()->adminurl->get('admin.category'))
                        ->method('post')
                        ->class('fieldset')
                        ->fields([
                            new formText('h4', __('Category parent')),
                            (new formPara())->items([
                                (new formLabel(__('Parent:')))
                                    ->for('cat_parent')
                                    ->class('classic'),
                                (new formSelect('cat_parent'))
                                    ->items(dcCore::app()->admin->cat_allowed_parents)
                                    ->default((string) dcCore::app()->admin->cat_parent),
                            ]),
                            (new formPara())->items([
                                (new formSubmit('cat-parent-submit'))
                                    ->accesskey('s')
                                    ->value(__('Save')),
                                new formHidden('id', dcCore::app()->admin->cat_id),
                                dcCore::app()->formNonce(false),
                            ]),
                        ]),
                ])
                ->render();

            if (is_countable(dcCore::app()->admin->cat_siblings) ? count(dcCore::app()->admin->cat_siblings) : 0) {
                echo (new formDiv())
                    ->class('col')
                    ->items([
                        (new formForm('cat-sibling-form'))
                            ->action(dcCore::app()->adminurl->get('admin.category'))
                            ->method('post')
                            ->class('fieldset')
                            ->fields([
                                new formText('h4', __('Category sibling')),
                                (new formPara())->items([
                                    (new formLabel(__('Move current category')))
                                        ->for('cat_sibling')
                                        ->class('classic'),
                                    (new formSelect('cat_move'))
                                        ->items([
                                            __('before') => 'before',
                                            __('after')  => 'after',
                                        ])
                                        ->title(__('position: ')),
                                    (new formSelect('cat_sibling'))
                                        ->items(dcCore::app()->admin->cat_siblings),
                                ]),
                                (new formPara())->items([
                                    (new formSubmit('cat-sibling-submit'))
                                        ->accesskey('s')
                                        ->value(__('Save')),
                                    new formHidden('id', dcCore::app()->admin->cat_id),
                                    dcCore::app()->formNonce(false),
                                ]),
                            ]),
                    ])
                    ->render();
            }

            echo '</div>';
        }

        dcPage::helpBlock('core_category');
        dcPage::close();
    }
}

adminCategory::init();
adminCategory::process();
adminCategory::render();
