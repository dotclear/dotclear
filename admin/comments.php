<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_USAGE,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]));

if (!empty($_POST['delete_all_spam'])) {
    try {
        dcCore::app()->blog->delJunkComments();
        $_SESSION['comments_del_spam'] = true;
        dcCore::app()->adminurl->redirect('admin.comments');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

/* Filters
-------------------------------------------------------- */
$comment_filter = new adminCommentFilter();

# get list params
$params = $comment_filter->params();

# lexical sort
$sortby_lex = [
    // key in sorty_combo (see above) => field in SQL request
    'post_title'          => 'post_title',
    'comment_author'      => 'comment_author',
    'comment_spam_filter' => 'comment_spam_filter', ];

# --BEHAVIOR-- adminCommentsSortbyLexCombo
dcCore::app()->callBehavior('adminCommentsSortbyLexCombo', [& $sortby_lex]);

$params['order'] = (array_key_exists($comment_filter->sortby, $sortby_lex) ?
    dcCore::app()->con->lexFields($sortby_lex[$comment_filter->sortby]) :
    $comment_filter->sortby) . ' ' . $comment_filter->order;

# default filter ? do not display spam
if (!$comment_filter->show() && $comment_filter->status == '') {
    $params['comment_status_not'] = -2;
}
$params['no_content'] = true;

/* Actions
-------------------------------------------------------- */
$combo_action = [];
$default      = '';
if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_DELETE,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]), dcCore::app()->blog->id) && $comment_filter->status == -2) {
    $default = 'delete';
}

$comments_actions_page = new dcCommentsActions(dcCore::app()->adminurl->get('admin.comments'));

if ($comments_actions_page->process()) {
    return;
}

/* List
-------------------------------------------------------- */
$comment_list = null;

try {
    $comments     = dcCore::app()->blog->getComments($params);
    $counter      = dcCore::app()->blog->getComments($params, true);
    $comment_list = new adminCommentList($comments, $counter->f(0));
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(
    __('Comments and trackbacks'),
    dcPage::jsLoad('js/_comments.js') . $comment_filter->js(),
    dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            __('Comments and trackbacks')               => '',
        ]
    )
);
if (!empty($_GET['upd'])) {
    dcPage::success(__('Selected comments have been successfully updated.'));
} elseif (!empty($_GET['del'])) {
    dcPage::success(__('Selected comments have been successfully deleted.'));
}

if (!dcCore::app()->error->flag()) {
    if (isset($_SESSION['comments_del_spam'])) {
        dcPage::message(__('Spam comments have been successfully deleted.'));
        unset($_SESSION['comments_del_spam']);
    }

    $spam_count = dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
    if ($spam_count > 0) {
        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.comments') . '" method="post" class="fieldset">';

        if (!$comment_filter->show() || ($comment_filter->status != -2)) {
            if ($spam_count == 1) {
                echo '<p>' . sprintf(__('You have one spam comment.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                '<a href="' . dcCore::app()->adminurl->get('admin.comments', ['status' => -2]) . '">' . __('Show it.') . '</a></p>';
            } elseif ($spam_count > 1) {
                echo '<p>' . sprintf(__('You have %s spam comments.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                '<a href="' . dcCore::app()->adminurl->get('admin.comments', ['status' => -2]) . '">' . __('Show them.') . '</a></p>';
            }
        }

        echo
        '<p>' .
        dcCore::app()->formNonce() .
        '<input name="delete_all_spam" class="delete" type="submit" value="' . __('Delete all spams') . '" /></p>';

        # --BEHAVIOR-- adminCommentsSpamForm
        dcCore::app()->callBehavior('adminCommentsSpamForm');

        echo '</form>';
    }

    $comment_filter->display('admin.comments');

    # Show comments
    $comment_list->display(
        $comment_filter->page,
        $comment_filter->nb,
        '<form action="' . dcCore::app()->adminurl->get('admin.comments') . '" method="post" id="form-comments">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
        form::combo(
            'action',
            $comments_actions_page->getCombo(),
            ['default' => $default, 'extra_html' => 'title="' . __('Actions') . '"']
        ) .
        dcCore::app()->formNonce() .
        '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
        dcCore::app()->adminurl->getHiddenFormFields('admin.comments', $comment_filter->values(true)) .
        '</div>' .

        '</form>',
        $comment_filter->show(),
        ($comment_filter->show() || ($comment_filter->status == -2)),
        dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)
    );
}

dcPage::helpBlock('core_comments');
dcPage::close();
