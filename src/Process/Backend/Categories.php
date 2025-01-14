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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @since 2.27 Before as admin/categories.php
 */
class Categories extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CATEGORIES,
        ]));

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_POST['delete'])) {
            // Remove a categories
            $keys   = array_keys($_POST['delete']);
            $cat_id = (int) $keys[0];
            $name   = '';

            // Check if category to delete exists
            $rs = App::blog()->getCategory($cat_id);
            if ($rs->isEmpty()) {
                Notices::addErrorNotice(__('This category does not exist.'));
                App::backend()->url()->redirect('admin.categories');
            } else {
                $name = $rs->cat_title;
            }

            try {
                // Delete category
                App::blog()->delCategory($cat_id);
                Notices::addSuccessNotice(sprintf(
                    __('The category "%s" has been successfully deleted.'),
                    Html::escapeHTML($name)
                ));
                App::backend()->url()->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['mov']) && !empty($_POST['mov_cat'])) {
            // move post into a category
            try {
                // Check if category where to move posts exists
                $keys    = array_keys($_POST['mov']);
                $cat_id  = (int) $keys[0];
                $mov_cat = (int) $_POST['mov_cat'][$cat_id] ?: null;
                $name    = '';

                if ($mov_cat !== null) {
                    $rs = App::blog()->getCategory($mov_cat);
                    if ($rs->isEmpty()) {
                        throw new Exception(__('Category where to move entries does not exist'));
                    }
                    $name = $rs->cat_title;
                }
                // Move posts
                if ($mov_cat != $cat_id) {
                    App::blog()->changePostsCategory($cat_id, $mov_cat);
                }
                Notices::addSuccessNotice(sprintf(
                    __('The entries have been successfully moved to category "%s"'),
                    Html::escapeHTML($name)
                ));
                App::backend()->url()->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['save_order']) && !empty($_POST['categories_order'])) {
            // Update order
            $categories = json_decode((string) $_POST['categories_order'], null, 512, JSON_THROW_ON_ERROR);
            foreach ($categories as $category) {
                if (!empty($category->item_id) && !empty($category->left) && !empty($category->right)) {
                    App::blog()->updCategoryPosition((int) $category->item_id, (int) $category->left, (int) $category->right);
                }
            }
            Notices::addSuccessNotice(__('Categories have been successfully reordered.'));
            App::backend()->url()->redirect('admin.categories');
        }

        if (!empty($_POST['reset'])) {
            // Reset order
            try {
                App::blog()->resetCategoriesOrder();
                Notices::addSuccessNotice(__('Categories order has been successfully reset.'));
                App::backend()->url()->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        $rs = App::blog()->getCategories();

        $starting_script = '';

        if (!App::auth()->prefs()->accessibility->nodragdrop
            && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())
            && $rs->count() > 1) {
            $starting_script .= Page::jsLoad('js/jquery/jquery-ui.custom.js');
            $starting_script .= Page::jsLoad('js/jquery/jquery.ui.touch-punch.js');
            $starting_script .= Page::jsLoad('js/jquery/jquery.mjs.nestedSortable.js');
        }
        $starting_script .= Page::jsConfirmClose('form-categories');
        $starting_script .= Page::jsLoad('js/_categories.js');

        Page::open(
            __('Categories'),
            $starting_script,
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Categories')                      => '',
                ]
            )
        );

        if (!empty($_GET['del'])) {
            Notices::success(__('The category has been successfully removed.'));
        }
        if (!empty($_GET['reord'])) {
            Notices::success(__('Categories have been successfully reordered.'));
        }
        if (!empty($_GET['move'])) {
            Notices::success(__('Entries have been successfully moved to the category you choose.'));
        }

        App::backend()->categories_combo = Combos::getCategoriesCombo($rs);

        echo (new Para())
            ->class('top-add')
            ->items([
                (new Link())
                    ->class(['button', 'add'])
                    ->href(App::backend()->url()->get('admin.category'))
                    ->text(__('New category')),
            ])
            ->render();

        $parts = [];

        if ($rs->isEmpty()) {
            $parts[] = (new Note())
                ->text(__('No category so far.'));
        } else {
            // List of categories
            $list = (new Div('categories'))
                ->items([
                    self::categorieList(1, $rs),
                ]);

            // Actions
            $actions = [];
            $message = (new None());

            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id()) && $rs->count() > 1) {
                if (!App::auth()->prefs()->accessibility->nodragdrop) {
                    $message = (new Note())
                        ->class(['form-note', 'hidden-if-no-js'])
                        ->text(__('To rearrange categories order, move items by drag and drop, then click on “Save categories order” button.'));
                }

                $actions[] = (new Set())
                    ->items([
                        (new Para(null, 'span'))
                            ->class('hidden-if-no-js')
                            ->items([
                                (new Hidden('categories_order', '')),
                                (new Submit(['save_order', 'save-set-order'], __('Save categories order'))),
                            ]),
                    ]);
            }

            $actions[] = (new Set())
                ->items([
                    (new Submit(['reset'], __('Reorder all categories on the top level')))
                        ->class('reset'),
                    (new Hidden(['process'], 'Categories')),
                ]);

            $action = (new Div())
                ->items([
                    $message,
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            ...$actions,
                            App::nonce()->formNonce(),
                        ]),
                ]);

            $parts[] = (new Form('form-categories'))
                ->action(App::backend()->url()->get('admin.categories'))
                ->method('post')
                ->fields([
                    $list,
                    $action,
                ]);
        }

        echo (new Div())
            ->items($parts)
        ->render();

        Page::helpBlock('core_categories');
        Page::close();
    }

    /**
     * Return Ul with current level categories as LI or None if nothing to list
     *
     * @param      int                            $level             The level
     * @param      MetaRecord                     $rs                The recordset
     */
    private static function categorieList(int $level, MetaRecord $rs): Ul|None
    {
        $categories = [];

        if ($rs->isEnd() && $rs->count() === 1) {
            // Only one category
            if ((int) $rs->level >= $level) {
                $categories[] = self::categorieLine($rs);
            }
        } else {
            while (!$rs->isEnd() && $rs->fetch()) {
                if ((int) $rs->level < $level) {
                    // Back to upper level
                    if ($rs->isEnd()) { // @phpstan-ignore-line
                        // Clear end flag of recordset
                        $rs->moveEnd();
                    }
                    $rs->movePrev();

                    break;
                }

                $categories[] = self::categorieLine($rs);
            }
        }

        return count($categories) ?
            (new Ul())->items($categories) :
            (new None());
    }

    /**
     * Return an LI with the category, including sub-categories if any
     *
     * @param      MetaRecord                     $rs                The recordset
     */
    private static function categorieLine(MetaRecord $rs): Li
    {
        // Category info
        $category = (new Set())
            ->items([
                (new Para())
                    ->class(['cat-title', 'form-buttons'])
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.category', ['id' => $rs->cat_id]))
                            ->text(Html::escapeHTML($rs->cat_title)),
                    ]),
                (new Para())
                    ->class(['cat-nb-posts'])
                    ->items([
                        (new Text(null, '(')),
                        (new Link())
                            ->href(App::backend()->url()->get('admin.posts', ['cat_id' => $rs->cat_id]))
                            ->text(sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry')), $rs->nb_post)),
                        (new Text(null, ', ' . __('total:') . ' ' . $rs->nb_total . ')')),
                    ]),
                (new Para())
                    ->class(['cat_url', 'form-buttons'])
                    ->items([
                        (new Text(null, __('URL:'))),
                        (new Text('code', Html::escapeHTML($rs->cat_url))),
                    ]),
            ]);

        // Move entries button
        $move = (new None());
        if ($rs->nb_total > 0) {
            $options = array_filter(App::backend()->categories_combo, fn ($cat): bool => $cat->value !== ((string) $rs->cat_id));
            if (!is_null($options) && count($options)) {
                $move = (new Set())
                    ->items([
                        (new Select(['mov_cat[' . $rs->cat_id . ']', 'mov_cat_' . $rs->cat_id]))
                            ->items($options)
                            ->label(new Label(__('Move entries to'), Label::IL_TF)),
                        (new Submit(['mov[' . $rs->cat_id . ']'], __('Ok'))),
                    ]);
            }
        }

        // Delete button
        $classes = ['delete'];
        $delete  = (new Submit(['delete[' . $rs->cat_id . ']'], __('Delete category')));
        if ($rs->nb_total > 0) {
            $delete
                ->disabled(true);
            $classes[] = 'disabled';
        }
        $delete
            ->class($classes);

        return (new Li('cat_' . $rs->cat_id))
            ->class(['cat-line', 'clearfix'])
            ->items([
                $category,
                (new Para())
                    ->class(['cat-buttons', 'form-buttons'])
                    ->items([
                        $move,
                        $delete,
                    ]),
                self::categorieList((int) $rs->level + 1, $rs),
            ]);
    }
}
