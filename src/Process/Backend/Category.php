<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @since 2.27 Before as admin/category.php
 */
class Category extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CATEGORIES,
        ]));

        $blog_settings = App::blogSettings()->createFromBlog(App::blog()->id());

        App::backend()->cat_id    = '';
        App::backend()->cat_title = '';
        App::backend()->cat_url   = '';
        App::backend()->cat_desc  = '';
        App::backend()->blog_lang = $blog_settings->system->lang;

        // Getting existing category
        App::backend()->cat_parents         = null;
        App::backend()->cat_parent          = 0;
        App::backend()->cat_siblings        = [];
        App::backend()->cat_allowed_parents = [];

        if (!empty($_REQUEST['id'])) {
            $rs = null;

            try {
                $rs = App::blog()->getCategory((int) $_REQUEST['id']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if ($rs) {
                if (!App::error()->flag() && !$rs->isEmpty()) {
                    App::backend()->cat_id    = (int) $rs->cat_id;
                    App::backend()->cat_title = $rs->cat_title;
                    App::backend()->cat_url   = $rs->cat_url;
                    App::backend()->cat_desc  = $rs->cat_desc;
                }
                unset($rs);
            }

            // Getting hierarchy information
            App::backend()->cat_parents = App::blog()->getCategoryParents((int) App::backend()->cat_id);
            $rs                         = App::blog()->getCategoryParent((int) App::backend()->cat_id);
            App::backend()->cat_parent  = $rs->isEmpty() ? 0 : (int) $rs->cat_id;

            // Allowed parents list
            $children = App::blog()->getCategories(['start' => App::backend()->cat_id]);

            App::backend()->cat_allowed_parents = [__('Top level') => 0];

            $parents = [];
            while ($children->fetch()) {
                $parents[$children->cat_id] = 1;
            }

            $stack = App::backend()->cat_allowed_parents;
            $rs    = App::blog()->getCategories();
            while ($rs->fetch()) {
                if (!isset($parents[$rs->cat_id])) {
                    $stack[] = new Option(
                        str_repeat('&nbsp;&nbsp;', (int) $rs->level - 1) . ($rs->level - 1 == 0 ? '' : '&bull; ') . Html::escapeHTML($rs->cat_title),
                        $rs->cat_id
                    );
                }
            }
            App::backend()->cat_allowed_parents = $stack;

            // Allowed siblings list
            $stack = App::backend()->cat_siblings;
            $rs    = App::blog()->getCategoryFirstChildren(App::backend()->cat_parent);
            while ($rs->fetch()) {
                if ($rs->cat_id != App::backend()->cat_id) {
                    $stack[Html::escapeHTML($rs->cat_title)] = $rs->cat_id;
                }
            }
            App::backend()->cat_siblings = $stack;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (App::backend()->cat_id && isset($_POST['cat_parent'])) {
            // Changing parent
            $new_parent = (int) $_POST['cat_parent'];
            if (App::backend()->cat_parent != $new_parent) {
                try {
                    App::blog()->setCategoryParent(App::backend()->cat_id, $new_parent);
                    Notices::addSuccessNotice(__('The category has been successfully moved'));
                    App::backend()->url()->redirect('admin.categories');
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        if (App::backend()->cat_id && isset($_POST['cat_sibling'])) {
            // Changing sibling
            try {
                App::blog()->setCategoryPosition(App::backend()->cat_id, (int) $_POST['cat_sibling'], $_POST['cat_move']);
                Notices::addSuccessNotice(__('The category has been successfully moved'));
                App::backend()->url()->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset($_POST['cat_title'])) {
            // Create or update a category
            $cur = App::blog()->categories()->openCategoryCursor();

            $cur->cat_title = App::backend()->cat_title = $_POST['cat_title'];
            if (isset($_POST['cat_desc'])) {
                $cur->cat_desc = App::backend()->cat_desc = $_POST['cat_desc'];
            }
            if (isset($_POST['cat_url'])) {
                $cur->cat_url = App::backend()->cat_url = $_POST['cat_url'];
            } else {
                $cur->cat_url = App::backend()->cat_url;
            }

            try {
                if (App::backend()->cat_id) {
                    // Update category

                    # --BEHAVIOR-- adminBeforeCategoryUpdate -- Cursor, string|int
                    App::behavior()->callBehavior('adminBeforeCategoryUpdate', $cur, App::backend()->cat_id);

                    App::blog()->updCategory((int) $_POST['id'], $cur);

                    # --BEHAVIOR-- adminAfterCategoryUpdate -- Cursor, string|int
                    App::behavior()->callBehavior('adminAfterCategoryUpdate', $cur, App::backend()->cat_id);

                    Notices::addSuccessNotice(__('The category has been successfully updated.'));

                    App::backend()->url()->redirect('admin.category', ['id' => $_POST['id']]);
                } else {
                    // Create category

                    # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur);

                    $id = App::blog()->addCategory($cur, (int) $_POST['new_cat_parent']);

                    # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterCategoryCreate', $cur, $id);

                    Notices::addSuccessNotice(sprintf(
                        __('The category "%s" has been successfully created.'),
                        Html::escapeHTML($cur->cat_title)
                    ));
                    App::backend()->url()->redirect('admin.categories');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        $title = App::backend()->cat_id ? Html::escapeHTML(App::backend()->cat_title) : __('New category');

        $elements = [
            Html::escapeHTML(App::blog()->name()) => '',
            __('Categories')                      => App::backend()->url()->get('admin.categories'),
        ];
        if (App::backend()->cat_id) {
            while (App::backend()->cat_parents->fetch()) {
                $elements[Html::escapeHTML(App::backend()->cat_parents->cat_title)] = App::backend()->url()->get('admin.category', ['id' => App::backend()->cat_parents->cat_id]);
            }
        }
        $elements[$title] = '';

        $category_editor = App::auth()->getOption('editor');
        $rte_flag        = true;
        $rte_flags       = @App::auth()->prefs()->interface->rte_flags;
        if (is_array($rte_flags) && in_array('cat_descr', $rte_flags)) {
            $rte_flag = $rte_flags['cat_descr'];
        }

        Page::open(
            $title,
            Page::jsConfirmClose('category-form') .
            Page::jsLoad('js/_category.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            ($rte_flag ? App::behavior()->callBehavior('adminPostEditor', $category_editor['xhtml'], 'category', ['#cat_desc'], 'xhtml') : ''),
            Page::breadcrumb($elements)
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('Category has been successfully updated.'));
        }

        if (!App::backend()->cat_id) {
            $options = Combos::getCategoriesCombo(App::blog()->getCategories());
            $parent  = !empty($_POST['new_cat_parent']) && $_POST['new_cat_parent'] ? $_POST['new_cat_parent'] : '';
        }

        echo (new Form('category-form'))
            ->action(App::backend()->url()->get('admin.category'))
            ->method('post')
            ->fields([
                (new Text('h3', __('Category information'))),
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                (new Para())
                    ->items([
                        (new Input('cat_title'))
                            ->size(40)
                            ->maxlength(255)
                            ->default(Html::escapeHTML(App::backend()->cat_title))
                            ->required(true)
                            ->placeholder(__('Name'))
                            ->lang(App::backend()->blog_lang)
                            ->spellcheck(true)
                            ->label((new Label((new Text('span', '*'))->render() . __('Name:'), Label::OL_TF))->class('required')),
                    ]),
                App::backend()->cat_id ?
                    (new None()) :
                    (new Para())
                        ->items([
                            (new Select('new_cat_parent'))
                                ->items($options)
                                ->default($parent)
                                ->label(new Label(__('Parent:'), Label::IL_TF)),
                        ]),
                (new Div())
                    ->class('lockable')
                    ->items([
                        (new Para())
                            ->items([
                                (new Input('cat_url'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default(Html::escapeHTML(App::backend()->cat_url))
                                    ->label(new Label(__('URL:'), Label::OL_TF)),
                            ]),
                        (new Note('note-cat-url'))
                            ->class(['form-note', 'warn'])
                            ->text(__('Warning: If you set the URL manually, it may conflict with another category.')),
                    ]),
                (new Para())
                    ->class('area')
                    ->items([
                        (new Textarea('cat_desc', Html::escapeHTML(App::backend()->cat_desc)))
                            ->cols(50)
                            ->rows(8)
                            ->lang(App::backend()->blog_lang)
                            ->spellcheck(true)
                            ->label(new Label(__('Description:'), Label::OL_TF)),
                    ]),
                (new Para())
                    ->class('form-buttons')
                    ->items([
                        (new Submit('new_cat_submit', __('Save')))
                            ->accesskey('s'),
                        (new Button(['cancel']))
                            ->value(__('Back'))
                            ->class(['go-back', 'reset', 'hidden-if-no-js']),
                        App::nonce()->formNonce(),
                    ]),
            ])
        ->render();

        if (App::backend()->cat_id) {
            $cols = [];

            $cols[] = (new Div())
                ->class('col')
                ->items([
                    (new Form('cat-parent-form'))
                        ->action(App::backend()->url()->get('admin.category'))
                        ->method('post')
                        ->class('fieldset')
                        ->fields([
                            new Text('h4', __('Category parent')),
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    (new Label(__('Parent:')))
                                        ->for('cat_parent')
                                        ->class('classic'),
                                    (new Select('cat_parent'))
                                        ->items(App::backend()->cat_allowed_parents)
                                        ->default((string) App::backend()->cat_parent),
                                ]),
                            (new Para())
                                ->items([
                                    (new Submit('cat-parent-submit'))
                                        ->accesskey('s')
                                        ->value(__('Save')),
                                    new Hidden('id', (string) App::backend()->cat_id),
                                    App::nonce()->formNonce(),
                                ]),
                        ]),
                ]);

            if (is_countable(App::backend()->cat_siblings) ? count(App::backend()->cat_siblings) : 0) {
                $cols[] = (new Div())
                    ->class('col')
                    ->items([
                        (new Form('cat-sibling-form'))
                            ->action(App::backend()->url()->get('admin.category'))
                            ->method('post')
                            ->class('fieldset')
                            ->fields([
                                new Text('h4', __('Category sibling')),
                                (new Para())
                                    ->class('form-buttons')
                                    ->items([
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
                                            ->items(App::backend()->cat_siblings),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Submit('cat-sibling-submit'))
                                            ->accesskey('s')
                                            ->value(__('Save')),
                                        new Hidden('id', (string) App::backend()->cat_id),
                                        App::nonce()->formNonce(),
                                    ]),
                            ]),
                    ]);
            }

            echo (new Fieldset())
                ->legend(new Legend(__('Move this category')))
                ->fields([
                    (new Div())
                        ->class('two-cols')
                        ->items($cols),
                ])
            ->render();
        }

        Page::helpBlock('core_category');
        Page::close();
    }
}
