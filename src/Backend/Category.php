<?php
/**
 * @since 2.27 Before as admin/category.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Backend;

use dcCategories;
use dcCore;
use dcSettings;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

class Category extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CATEGORIES,
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
                $rs = dcCore::app()->blog->getCategory((int) $_REQUEST['id']);
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
                    $stack[] = new Option(
                        str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . Html::escapeHTML($rs->cat_title),
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
                    $stack[Html::escapeHTML($rs->cat_title)] = $rs->cat_id;
                }
            }
            dcCore::app()->admin->cat_siblings = $stack;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (dcCore::app()->admin->cat_id && isset($_POST['cat_parent'])) {
            // Changing parent
            $new_parent = (int) $_POST['cat_parent'];
            if (dcCore::app()->admin->cat_parent != $new_parent) {
                try {
                    dcCore::app()->blog->setCategoryParent(dcCore::app()->admin->cat_id, $new_parent);
                    Page::addSuccessNotice(__('The category has been successfully moved'));
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
                Page::addSuccessNotice(__('The category has been successfully moved'));
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

                    # --BEHAVIOR-- adminBeforeCategoryUpdate -- Cursor, string|int
                    dcCore::app()->callBehavior('adminBeforeCategoryUpdate', $cur, dcCore::app()->admin->cat_id);

                    dcCore::app()->blog->updCategory($_POST['id'], $cur);

                    # --BEHAVIOR-- adminAfterCategoryUpdate -- Cursor, string|int
                    dcCore::app()->callBehavior('adminAfterCategoryUpdate', $cur, dcCore::app()->admin->cat_id);

                    Page::addSuccessNotice(__('The category has been successfully updated.'));

                    dcCore::app()->adminurl->redirect('admin.category', ['id' => $_POST['id']]);
                } else {
                    // Create category

                    # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                    dcCore::app()->callBehavior('adminBeforeCategoryCreate', $cur);

                    $id = dcCore::app()->blog->addCategory($cur, (int) $_POST['new_cat_parent']);

                    # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string
                    dcCore::app()->callBehavior('adminAfterCategoryCreate', $cur, $id);

                    Page::addSuccessNotice(sprintf(
                        __('The category "%s" has been successfully created.'),
                        Html::escapeHTML($cur->cat_title)
                    ));
                    dcCore::app()->adminurl->redirect('admin.categories');
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        $title = dcCore::app()->admin->cat_id ? Html::escapeHTML(dcCore::app()->admin->cat_title) : __('New category');

        $elements = [
            Html::escapeHTML(dcCore::app()->blog->name) => '',
            __('Categories')                            => dcCore::app()->adminurl->get('admin.categories'),
        ];
        if (dcCore::app()->admin->cat_id) {
            while (dcCore::app()->admin->cat_parents->fetch()) {
                $elements[Html::escapeHTML(dcCore::app()->admin->cat_parents->cat_title)] = dcCore::app()->adminurl->get('admin.category', ['id' => dcCore::app()->admin->cat_parents->cat_id]);
            }
        }
        $elements[$title] = '';

        $category_editor = dcCore::app()->auth->getOption('editor');
        $rte_flag        = true;
        $rte_flags       = @dcCore::app()->auth->user_prefs->interface->rte_flags;
        if (is_array($rte_flags) && in_array('cat_descr', $rte_flags)) {
            $rte_flag = $rte_flags['cat_descr'];
        }

        Page::open(
            $title,
            Page::jsConfirmClose('category-form') .
            Page::jsLoad('js/_category.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            ($rte_flag ? dcCore::app()->callBehavior('adminPostEditor', $category_editor['xhtml'], 'category', ['#cat_desc'], 'xhtml') : ''),
            Page::breadcrumb($elements)
        );

        if (!empty($_GET['upd'])) {
            Page::success(__('Category has been successfully updated.'));
        }

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.category') . '" method="post" id="category-form">' .
        '<h3>' . __('Category information') . '</h3>' .
        '<p><label class="required" for="cat_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label> ' .
        \form::field('cat_title', 40, 255, [
            'default'    => Html::escapeHTML(dcCore::app()->admin->cat_title),
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
                '<option value="' . $rs->cat_id . '" ' . (!empty($_POST['new_cat_parent']) && $_POST['new_cat_parent'] == $rs->cat_id ? 'selected="selected"' : '') . '>' . str_repeat('&nbsp;&nbsp;', $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . Html::escapeHTML($rs->cat_title) . '</option>';
            }
            echo
            '</select></label></p>';
            unset($rs);
        }
        echo
        '<div class="lockable">' .
        '<p><label for="cat_url">' . __('URL:') . '</label> '
        . \form::field('cat_url', 40, 255, Html::escapeHTML(dcCore::app()->admin->cat_url)) .
        '</p>' .
        '<p class="form-note warn" id="note-cat-url">' .
        __('Warning: If you set the URL manually, it may conflict with another category.') . '</p>' .
        '</div>' .

        '<p class="area"><label for="cat_desc">' . __('Description:') . '</label> ' .
        \form::textarea(
            'cat_desc',
            50,
            8,
            [
                'default'    => Html::escapeHTML(dcCore::app()->admin->cat_desc),
                'extra_html' => 'lang="' . dcCore::app()->admin->blog_lang . '" spellcheck="true"',
            ]
        ) .
        '</p>' .

        '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        (dcCore::app()->admin->cat_id ? \form::hidden('id', dcCore::app()->admin->cat_id) : '') .
        dcCore::app()->formNonce() .
        '</p>' .
        '</form>';

        if (dcCore::app()->admin->cat_id) {
            echo
            '<h3 class="border-top">' . __('Move this category') . '</h3>' .
            '<div class="two-cols">';

            echo (new Div())
                ->class('col')
                ->items([
                    (new Form('cat-parent-form'))
                        ->action(dcCore::app()->adminurl->get('admin.category'))
                        ->method('post')
                        ->class('fieldset')
                        ->fields([
                            new Text('h4', __('Category parent')),
                            (new Para())->items([
                                (new Label(__('Parent:')))
                                    ->for('cat_parent')
                                    ->class('classic'),
                                (new Select('cat_parent'))
                                    ->items(dcCore::app()->admin->cat_allowed_parents)
                                    ->default((string) dcCore::app()->admin->cat_parent),
                            ]),
                            (new Para())->items([
                                (new Submit('cat-parent-submit'))
                                    ->accesskey('s')
                                    ->value(__('Save')),
                                new Hidden('id', (string) dcCore::app()->admin->cat_id),
                                dcCore::app()->formNonce(false),
                            ]),
                        ]),
                ])
                ->render();

            if (is_countable(dcCore::app()->admin->cat_siblings) ? count(dcCore::app()->admin->cat_siblings) : 0) {
                echo (new Div())
                    ->class('col')
                    ->items([
                        (new Form('cat-sibling-form'))
                            ->action(dcCore::app()->adminurl->get('admin.category'))
                            ->method('post')
                            ->class('fieldset')
                            ->fields([
                                new Text('h4', __('Category sibling')),
                                (new Para())->items([
                                    (new Label(__('Move current category')))
                                        ->for('cat_sibling')
                                        ->class('classic'),
                                    (new Select('cat_move'))
                                        ->items([
                                            __('before') => 'before',
                                            __('after')  => 'after',
                                        ])
                                        ->title(__('position: ')),
                                    (new Select('cat_sibling'))
                                        ->items(dcCore::app()->admin->cat_siblings),
                                ]),
                                (new Para())->items([
                                    (new Submit('cat-sibling-submit'))
                                        ->accesskey('s')
                                        ->value(__('Save')),
                                    new Hidden('id', (string) dcCore::app()->admin->cat_id),
                                    dcCore::app()->formNonce(false),
                                ]),
                            ]),
                    ])
                    ->render();
            }

            echo '</div>';
        }

        Page::helpBlock('core_category');
        Page::close();
    }
}
