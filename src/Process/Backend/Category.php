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

use Dotclear\App;
use Dotclear\Database\MetaRecord;
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
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/category.php
 */
class Category
{
    use TraitProcess;

    protected static int $cat_id;

    protected static string $cat_title;

    protected static string $cat_url;

    protected static string $cat_desc;

    protected static string $blog_lang;

    protected static MetaRecord $cat_parents;

    protected static int $cat_parent;

    /**
     * @var Option[] $cat_allowed_parents
     */
    protected static array $cat_allowed_parents;

    /**
     * @var Option[] $cat_siblings
     */
    protected static array $cat_siblings;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CATEGORIES,
        ]));

        $blog_settings = App::blogSettings()->createFromBlog(App::blog()->id());

        self::$cat_id    = 0;
        self::$cat_title = '';
        self::$cat_url   = '';
        self::$cat_desc  = '';
        self::$blog_lang = $blog_settings->get('system')->getStr('lang', false) ?: 'en';

        // Getting existing category
        self::$cat_parent          = 0;
        self::$cat_siblings        = [];
        self::$cat_allowed_parents = [];

        if (!empty($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
            $rs = null;

            try {
                // @phpstan-ignore cast.int (false positive, why the previous is_numeric() is not memorized?)
                $rs = App::blog()->getCategory((int) $_REQUEST['id']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            if ($rs instanceof MetaRecord && (!App::error()->flag() && !$rs->isEmpty())) {
                self::$cat_id    = $rs->intField('cat_id');
                self::$cat_title = $rs->strField('cat_title');
                self::$cat_url   = $rs->strField('cat_url');
                self::$cat_desc  = $rs->strField('cat_desc');
            }

            // Getting hierarchy information
            self::$cat_parents = App::blog()->getCategoryParents(self::$cat_id);
            $rs                = App::blog()->getCategoryParent(self::$cat_id);
            self::$cat_parent  = $rs->isEmpty() ? 0 : $rs->intField('cat_id');

            // Allowed parents list
            $children = App::blog()->getCategories(['start' => self::$cat_id]);

            $parents = [];
            while ($children->fetch()) {
                $parents[$children->intField('cat_id')] = 1;
            }

            $stack = [
                new Option(__('Top level'), (string) 0),
            ];
            $rs = App::blog()->getCategories();
            while ($rs->fetch()) {
                if (!isset($parents[$rs->intField('cat_id')])) {
                    $stack[] = new Option(
                        str_repeat('&nbsp;&nbsp;', $rs->intField('level') - 1) . ($rs->intField('level') - 1 === 0 ? '' : '&bull; ') . Html::escapeHTML($rs->strField('cat_title')),
                        (string) $rs->intField('cat_id')
                    );
                }
            }

            self::$cat_allowed_parents = $stack;

            // Allowed siblings list
            $stack = self::$cat_siblings;
            $rs    = App::blog()->getCategoryFirstChildren(self::$cat_parent);
            while ($rs->fetch()) {
                if ($rs->intField('cat_id') !== self::$cat_id) {
                    $stack[] = new Option(
                        Html::escapeHTML($rs->strField('cat_title')),
                        (string) $rs->intField('cat_id')
                    );
                }
            }

            self::$cat_siblings = $stack;
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (self::$cat_id && isset($_POST['cat_parent']) && is_numeric($_POST['cat_parent'])) {
            // Changing parent
            $new_parent = (int) $_POST['cat_parent'];
            if (self::$cat_parent !== $new_parent) {
                try {
                    App::blog()->setCategoryParent(self::$cat_id, $new_parent);
                    App::backend()->notices()->addSuccessNotice(__('The category has been successfully moved.'));
                    App::backend()->url()->redirect('admin.categories');
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        if (self::$cat_id
            && isset($_POST['cat_sibling'])
            && is_numeric($_POST['cat_sibling'])
            && is_string($_POST['cat_move'])
        ) {
            // Changing sibling
            try {
                // @phpstan-ignore cast.int, argument.type (false positive, why the previous is_numeric() is not memorized?)
                App::blog()->setCategoryPosition(self::$cat_id, (int) $_POST['cat_sibling'], $_POST['cat_move']);
                App::backend()->notices()->addSuccessNotice(__('The category has been successfully moved.'));
                App::backend()->url()->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset($_POST['cat_title']) && is_string($_POST['cat_title'])) {
            // Create or update a category
            $cur            = App::blog()->categories()->openCategoryCursor();
            $cur->cat_title = $_POST['cat_title'];
            // @phpstan-ignore assign.propertyType (false positive, why the previous is_string() is not memorized?)
            self::$cat_title = $_POST['cat_title'];
            if (isset($_POST['cat_desc']) && is_string($_POST['cat_desc'])) {
                $cur->cat_desc  = $_POST['cat_desc'];
                self::$cat_desc = $_POST['cat_desc'];
            }

            if (isset($_POST['cat_url']) && is_string($_POST['cat_url'])) {
                $cur->cat_url  = $_POST['cat_url'];
                self::$cat_url = $_POST['cat_url'];
            }

            try {
                if (self::$cat_id !== 0) {
                    // Update category
                    $id = isset($_POST['id']) && is_numeric($id = $_POST['id']) ? (int) $id : 0;
                    if ($id !== 0) {
                        # --BEHAVIOR-- adminBeforeCategoryUpdate -- Cursor, string|int
                        App::behavior()->callBehavior('adminBeforeCategoryUpdate', $cur, self::$cat_id);

                        App::blog()->updCategory($id, $cur);

                        # --BEHAVIOR-- adminAfterCategoryUpdate -- Cursor, string|int
                        App::behavior()->callBehavior('adminAfterCategoryUpdate', $cur, self::$cat_id);

                        App::backend()->notices()->addSuccessNotice(__('The category has been successfully updated.'));
                    }

                    App::backend()->url()->redirect('admin.category', ['id' => $id]);
                } else {
                    // Create category
                    $new_cat_parent = isset($_POST['new_cat_parent']) && is_string($new_cat_parent = $_POST['new_cat_parent']) ? (int) $new_cat_parent : 0;

                    # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur);

                    $id = App::blog()->addCategory($cur, $new_cat_parent);

                    # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterCategoryCreate', $cur, $id);

                    App::backend()->notices()->addSuccessNotice(sprintf(
                        __('The category "%s" has been successfully created.'),
                        // @phpstan-ignore argument.type
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
        $title = self::$cat_id !== 0 ? Html::escapeHTML(self::$cat_title) : __('New category');

        $elements = [
            Html::escapeHTML(App::blog()->name()) => '',
            __('Categories')                      => App::backend()->url()->get('admin.categories'),
        ];
        if (self::$cat_id !== 0) {
            while (self::$cat_parents->fetch()) {
                $elements[Html::escapeHTML(self::$cat_parents->strField('cat_title'))] = App::backend()->url()->get('admin.category', ['id' => self::$cat_parents->intField('cat_id')]);
            }
        }

        $elements[$title] = '';

        $category_editor = App::auth()->prefs()->get('interface')->get('editor');
        $rte_flag        = true;
        $rte_flags       = @App::auth()->prefs()->get('interface')->get('rte_flags');
        if (is_array($rte_flags) && in_array('cat_descr', $rte_flags)) {
            $rte_flag = $rte_flags['cat_descr'];
        }

        App::backend()->page()->open(
            $title,
            App::backend()->page()->jsConfirmClose('category-form') .
            App::backend()->page()->jsLoad('js/_category.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            ($rte_flag && is_array($category_editor) && isset($category_editor['xhtml'])
                ? App::behavior()->callBehavior('adminPostEditor', $category_editor['xhtml'], 'category', ['#cat_desc'], 'xhtml')
                : ''),
            App::backend()->page()->breadcrumb($elements)
        );

        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('Category has been successfully updated.'));
        }

        echo (new Form('category-form'))
            ->action(App::backend()->url()->get('admin.category'))
            ->method('post')
            ->fields([
                (new Text('h3', __('Category information'))),
                (new Note())
                    ->class('form-note')
                    ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                (new Para())
                    ->items([
                        (new Input('cat_title'))
                            ->size(40)
                            ->maxlength(255)
                            ->default(Html::escapeHTML(self::$cat_title))
                            ->required(true)
                            ->placeholder(__('Name'))
                            ->lang(self::$blog_lang)
                            ->spellcheck(true)
                            ->label((new Label((new Span('*'))->render() . __('Name:'), Label::OL_TF))->class('required')),
                    ]),
                self::$cat_id !== 0 ?
                    (new None()) :
                    (new Para())
                        ->items([
                            (new Select('new_cat_parent'))
                                ->items(App::backend()->combos()->getCategoriesCombo(App::blog()->getCategories()))
                                ->default(empty($_POST['new_cat_parent']) || !is_numeric($_POST['new_cat_parent']) ? '' : $_POST['new_cat_parent'])
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
                                    ->default(Html::escapeHTML(self::$cat_url))
                                    ->label(new Label(__('URL:'), Label::OL_TF)),
                            ]),
                        (new Note('note-cat-url'))
                            ->class(['form-note', 'warn'])
                            ->text(__('Warning: If you set the URL manually, it may conflict with another category.')),
                    ]),
                (new Para())
                    ->class('area')
                    ->items([
                        (new Textarea('cat_desc', Html::escapeHTML(self::$cat_desc)))
                            ->cols(50)
                            ->rows(8)
                            ->lang(self::$blog_lang)
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
                        self::$cat_id !== 0 ?
                            new Hidden('id', (string) self::$cat_id) :
                            new None(),
                        App::nonce()->formNonce(),
                    ]),
            ])
        ->render();

        if (self::$cat_id !== 0) {
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
                                        ->items(self::$cat_allowed_parents)
                                        ->default((string) self::$cat_parent),
                                ]),
                            (new Para())
                                ->items([
                                    (new Submit('cat-parent-submit'))
                                        ->accesskey('s')
                                        ->value(__('Save')),
                                    new Hidden('id', (string) self::$cat_id),
                                    App::nonce()->formNonce(),
                                ]),
                        ]),
                ]);

            if (self::$cat_siblings !== []) {
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
                                                new Option(__('before'), 'before'),
                                                new Option(__('after'), 'after'),
                                            ])
                                            ->title(__('position: ')),
                                        (new Select('cat_sibling'))
                                            ->items(self::$cat_siblings),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Submit('cat-sibling-submit'))
                                            ->accesskey('s')
                                            ->value(__('Save')),
                                        new Hidden('id', (string) self::$cat_id),
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

        App::backend()->page()->helpBlock('core_category');
        App::backend()->page()->close();
    }
}
