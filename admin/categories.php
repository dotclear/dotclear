<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('categories');

# Remove a categories
if (!empty($_POST['delete'])) {
    $keys   = array_keys($_POST['delete']);
    $cat_id = (int) $keys[0];

    # Check if category to delete exists
    $c = $core->blog->getCategory((integer) $cat_id);
    if ($c->isEmpty()) {
        dcPage::addErrorNotice(__('This category does not exist.'));
        $core->adminurl->redirect("admin.categories");
    }
    $name = $c->cat_title;
    unset($c);

    try {
        # Delete category
        $core->blog->delCategory($cat_id);
        dcPage::addSuccessNotice(sprintf(__('The category "%s" has been successfully deleted.'), html::escapeHTML($name)));
        $core->adminurl->redirect("admin.categories");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# move post into a category
if (!empty($_POST['mov']) && !empty($_POST['mov_cat'])) {
    try {
        # Check if category where to move posts exists
        $keys    = array_keys($_POST['mov']);
        $cat_id  = (int) $keys[0];
        $mov_cat = (int) $_POST['mov_cat'][$cat_id];

        $mov_cat = $mov_cat ?: null;
        if ($mov_cat !== null) {
            $c = $core->blog->getCategory($mov_cat);
            if ($c->isEmpty()) {
                throw new Exception(__('Category where to move entries does not exist'));
            }
            $name = $c->cat_title;
            unset($c);
        }
        # Move posts
        if ($mov_cat != $cat_id) {
            $core->blog->changePostsCategory($cat_id, $mov_cat);
        }
        dcPage::addSuccessNotice(sprintf(__('The entries have been successfully moved to category "%s"'),
            html::escapeHTML($name)));
        $core->adminurl->redirect("admin.categories");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Update order
if (!empty($_POST['save_order']) && !empty($_POST['categories_order'])) {
    $categories = json_decode($_POST['categories_order']);

    foreach ($categories as $category) {
        if (!empty($category->item_id) && !empty($category->left) && !empty($category->right)) {
            $core->blog->updCategoryPosition($category->item_id, $category->left, $category->right);
        }
    }

    dcPage::addSuccessNotice(__('Categories have been successfully reordered.'));
    $core->adminurl->redirect("admin.categories");
}

# Reset order
if (!empty($_POST['reset'])) {
    try
    {
        $core->blog->resetCategoriesOrder();
        dcPage::addSuccessNotice(__('Categories order has been successfully reset.'));
        $core->adminurl->redirect("admin.categories");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

/* Display
-------------------------------------------------------- */
$rs = $core->blog->getCategories();

$starting_script = "";

$core->auth->user_prefs->addWorkspace('accessibility');
if (!$core->auth->user_prefs->accessibility->nodragdrop
    && $core->auth->check('categories', $core->blog->id)
    && $rs->count() > 1) {
    $starting_script .= dcPage::jsLoad('js/jquery/jquery-ui.custom.js');
    $starting_script .= dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js');
    $starting_script .= dcPage::jsLoad('js/jquery/jquery.mjs.nestedSortable.js');
}
$starting_script .= dcPage::jsConfirmClose('form-categories');
$starting_script .= dcPage::jsLoad('js/_categories.js');

dcPage::open(__('Categories'), $starting_script,
    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Categories')                    => ''
        ))
);

if (!empty($_GET['del'])) {
    dcPage::success(__('The category has been successfully removed.'));
}
if (!empty($_GET['reord'])) {
    dcPage::success(__('Categories have been successfully reordered.'));
}
if (!empty($_GET['move'])) {
    dcPage::success(__('Entries have been successfully moved to the category you choose.'));
}

$categories_combo = dcAdminCombos::getCategoriesCombo($rs);

echo
'<p class="top-add"><a class="button add" href="' . $core->adminurl->get("admin.category") . '">' . __('New category') . '</a></p>';

echo
    '<div class="col">';
if ($rs->isEmpty()) {
    echo '<p>' . __('No category so far.') . '</p>';
} else {
    echo
    '<form action="' . $core->adminurl->get("admin.categories") . '" method="post" id="form-categories">' .
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
        '<p class="cat-title"><label class="classic" for="cat_' . $rs->cat_id . '"><a href="' .
        $core->adminurl->get("admin.category", array('id' => $rs->cat_id)) . '">' . html::escapeHTML($rs->cat_title) .
        '</a></label> </p>' .
        '<p class="cat-nb-posts">(<a href="' . $core->adminurl->get("admin.posts", array('cat_id' => $rs->cat_id)) . '">' .
        sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry')), $rs->nb_post) . '</a>' .
        ', ' . __('total:') . ' ' . $rs->nb_total . ')</p>' .
        '<p class="cat-url">' . __('URL:') . ' <code>' . html::escapeHTML($rs->cat_url) . '</code></p>';

        echo
            '<p class="cat-buttons">';
        if ($rs->nb_total > 0) {
            // remove current category
            echo
            '<label for="mov_cat_' . $rs->cat_id . '">' . __('Move entries to') . '</label> ' .
            form::combo(array('mov_cat[' . $rs->cat_id . ']', 'mov_cat_' . $rs->cat_id), array_filter($categories_combo,
                function ($cat) {return $cat->value != $GLOBALS['rs']->cat_id;}
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

    if ($core->auth->check('categories', $core->blog->id) && $rs->count() > 1) {
        if (!$core->auth->user_prefs->accessibility->nodragdrop) {
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
    $core->formNonce() . '</p>' .
        '</div></form>';
}

echo '</div>';

dcPage::helpBlock('core_categories');
dcPage::close();
