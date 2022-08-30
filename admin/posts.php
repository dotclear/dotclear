<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

/* Actions
-------------------------------------------------------- */
$posts_actions_page = new dcPostsActionsPage(dcCore::app(), dcCore::app()->adminurl->get('admin.posts'));

if ($posts_actions_page->process()) {
    return;
}

/* Filters
-------------------------------------------------------- */
$post_filter = new adminPostFilter();

# get list params
$params = $post_filter->params();

# lexical sort
$sortby_lex = [
    // key in sorty_combo (see above) => field in SQL request
    'post_title' => 'post_title',
    'cat_title'  => 'cat_title',
    'user_id'    => 'P.user_id', ];

# --BEHAVIOR-- adminPostsSortbyLexCombo
dcCore::app()->callBehavior('adminPostsSortbyLexCombo', [& $sortby_lex]);

$params['order'] = (array_key_exists($post_filter->sortby, $sortby_lex) ?
    dcCore::app()->con->lexFields($sortby_lex[$post_filter->sortby]) :
    $post_filter->sortby) . ' ' . $post_filter->order;

$params['no_content'] = true;

/* List
-------------------------------------------------------- */
$post_list = null;

try {
    $posts     = dcCore::app()->blog->getPosts($params);
    $counter   = dcCore::app()->blog->getPosts($params, true);
    $post_list = new adminPostList(dcCore::app(), $posts, $counter->f(0));
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(
    __('Posts'),
    dcPage::jsLoad('js/_posts_list.js') . $post_filter->js(),
    dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            __('Posts')                                 => '',
        ]
    )
);
if (!empty($_GET['upd'])) {
    dcPage::success(__('Selected entries have been successfully updated.'));
} elseif (!empty($_GET['del'])) {
    dcPage::success(__('Selected entries have been successfully deleted.'));
}
if (!dcCore::app()->error->flag()) {
    echo '<p class="top-add"><a class="button add" href="' . dcCore::app()->adminurl->get('admin.post') . '">' . __('New post') . '</a></p>';

    # filters
    $post_filter->display('admin.posts');

    # Show posts
    $post_list->display(
        $post_filter->page,
        $post_filter->nb,
        '<form action="' . dcCore::app()->adminurl->get('admin.posts') . '" method="post" id="form-entries">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
        form::combo('action', $posts_actions_page->getCombo()) .
        '<input id="do-action" type="submit" value="' . __('ok') . '" disabled /></p>' .
        dcCore::app()->adminurl->getHiddenFormFields('admin.posts', $post_filter->values()) .
        dcCore::app()->formNonce() .
        '</div>' .
        '</form>',
        $post_filter->show()
    );
}

dcPage::helpBlock('core_posts');
dcPage::close();
