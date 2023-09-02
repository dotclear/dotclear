<?php
/**
 * @since 2.27 Before as admin/cateogires.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

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
            $rs = App::blog()->getCategory((int) $cat_id);
            if ($rs->isEmpty()) {
                Notices::addErrorNotice(__('This category does not exist.'));
                App::backend()->url->redirect('admin.categories');
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
                App::backend()->url->redirect('admin.categories');
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
                App::backend()->url->redirect('admin.categories');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['save_order']) && !empty($_POST['categories_order'])) {
            // Update order
            $categories = json_decode($_POST['categories_order'], null, 512, JSON_THROW_ON_ERROR);
            foreach ($categories as $category) {
                if (!empty($category->item_id) && !empty($category->left) && !empty($category->right)) {
                    App::blog()->updCategoryPosition((int) $category->item_id, (int) $category->left, (int) $category->right);
                }
            }
            Notices::addSuccessNotice(__('Categories have been successfully reordered.'));
            App::backend()->url->redirect('admin.categories');
        }

        if (!empty($_POST['reset'])) {
            // Reset order
            try {
                App::blog()->resetCategoriesOrder();
                Notices::addSuccessNotice(__('Categories order has been successfully reset.'));
                App::backend()->url->redirect('admin.categories');
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
            ]), App::blog()->id)
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
                    Html::escapeHTML(App::blog()->name) => '',
                    __('Categories')                    => '',
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

        $categories_combo = Combos::getCategoriesCombo($rs);

        echo
        '<p class="top-add"><a class="button add" href="' . App::backend()->url->get('admin.category') . '">' . __('New category') . '</a></p>';

        echo
        '<div class="col">';
        if ($rs->isEmpty()) {
            echo '<p>' . __('No category so far.') . '</p>';
        } else {
            echo
            '<form action="' . App::backend()->url->get('admin.categories') . '" method="post" id="form-categories">' .
            '<div id="categories">';

            $ref_level = $level = $rs->level - 1;
            while ($rs->fetch()) {
                $attr = 'id="cat_' . $rs->cat_id . '" class="cat-line clearfix"';

                if ($rs->level > $level) {
                    echo str_repeat('<ul><li ' . $attr . '>', $rs->level - $level);
                } elseif ($rs->level < $level) {
                    echo str_repeat('</li></ul>', -($rs->level - $level));
                }

                if ($rs->level <= $level) {
                    echo '</li><li ' . $attr . '>';
                }

                echo
                '<p class="cat-title"><label class="classic" for="cat_' . $rs->cat_id . '"><a href="' . App::backend()->url->get('admin.category', ['id' => $rs->cat_id]) . '">' . Html::escapeHTML($rs->cat_title) . '</a></label> </p>' .
                '<p class="cat-nb-posts">(<a href="' . App::backend()->url->get('admin.posts', ['cat_id' => $rs->cat_id]) . '">' . sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry')), $rs->nb_post) . '</a>' . ', ' . __('total:') . ' ' . $rs->nb_total . ')</p>' .
                '<p class="cat-url">' . __('URL:') . ' <code>' . Html::escapeHTML($rs->cat_url) . '</code></p>';

                echo
                '<p class="cat-buttons">';
                if ($rs->nb_total > 0) {
                    // remove current category
                    echo
                    '<label for="mov_cat_' . $rs->cat_id . '">' . __('Move entries to') . '</label> ' .
                    form::combo(['mov_cat[' . $rs->cat_id . ']', 'mov_cat_' . $rs->cat_id], array_filter(
                        $categories_combo,
                        fn ($cat) => $cat->value != ($rs->cat_id ?? '0')
                    ), '', '') .
                    ' <input type="submit" class="reset" name="mov[' . $rs->cat_id . ']" value="' . __('OK') . '"/>';

                    $attr_disabled = ' disabled="disabled"';
                    $input_class   = 'disabled ';
                } else {
                    $attr_disabled = '';
                    $input_class   = '';
                }
                echo
                ' <input type="submit"' . $attr_disabled . ' class="' . $input_class . 'delete" name="delete[' . $rs->cat_id . ']" value="' . __('Delete category') . '"/>' .
                '</p>';

                $level = $rs->level;
            }

            if ($ref_level - $level < 0) {
                echo str_repeat('</li></ul>', -($ref_level - $level));
            }
            echo
            '</div>';

            echo '<div class="clear">';

            if (App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id) && $rs->count() > 1) {
                if (!App::auth()->prefs()->accessibility->nodragdrop) {
                    echo '<p class="form-note hidden-if-no-js">' . __('To rearrange categories order, move items by drag and drop, then click on “Save categories order” button.') . '</p>';
                }
                echo
                '<p><span class="hidden-if-no-js">' .
                '<input type="hidden" id="categories_order" name="categories_order" value=""/>' .
                '<input type="submit" name="save_order" id="save-set-order" value="' . __('Save categories order') . '" />' .
                '</span> ';
            } else {
                echo '<p>';
            }

            echo
            '<input type="submit" class="reset" name="reset" value="' . __('Reorder all categories on the top level') . '" />' .
            '<input type="hidden" name="process" value="Categories"/>' .
            App::nonce()->getFormNonce() .
            '</p>' .
            '</div></form>';
        }

        echo '</div>';

        Page::helpBlock('core_categories');
        Page::close();
    }
}
