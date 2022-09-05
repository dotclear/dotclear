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

$show_ip = dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id);

$comment_id          = null;
$comment_dt          = '';
$comment_author      = '';
$comment_email       = '';
$comment_site        = '';
$comment_content     = '';
$comment_ip          = '';
$comment_status      = '';
$comment_trackback   = 0;
$comment_spam_status = '';

$comment_editor = dcCore::app()->auth->getOption('editor');

# Status combo
$status_combo = dcAdminCombos::getCommentStatusesCombo();

# Adding comment (comming from post form, comments tab)
if (!empty($_POST['add']) && !empty($_POST['post_id'])) {
    try {
        $rs = dcCore::app()->blog->getPosts(['post_id' => $_POST['post_id'], 'post_type' => '']);

        if ($rs->isEmpty()) {
            throw new Exception(__('Entry does not exist.'));
        }

        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

        $cur->comment_author  = $_POST['comment_author'];
        $cur->comment_email   = html::clean($_POST['comment_email']);
        $cur->comment_site    = html::clean($_POST['comment_site']);
        $cur->comment_content = dcCore::app()->HTMLfilter($_POST['comment_content']);
        $cur->post_id         = (int) $_POST['post_id'];

        # --BEHAVIOR-- adminBeforeCommentCreate
        dcCore::app()->callBehavior('adminBeforeCommentCreate', $cur);

        $comment_id = dcCore::app()->blog->addComment($cur);

        # --BEHAVIOR-- adminAfterCommentCreate
        dcCore::app()->callBehavior('adminAfterCommentCreate', $cur, $comment_id);

        dcPage::addSuccessNotice(__('Comment has been successfully created.'));
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
    http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, $rs->post_id, false) . '&co=1');
}

$rs         = null;
$post_id    = '';
$post_type  = '';
$post_title = '';

if (!empty($_REQUEST['id'])) {
    $params['comment_id'] = $_REQUEST['id'];

    try {
        $rs = dcCore::app()->blog->getComments($params);
        if (!$rs->isEmpty()) {
            $comment_id          = $rs->comment_id;
            $post_id             = $rs->post_id;
            $post_type           = $rs->post_type;
            $post_title          = $rs->post_title;
            $comment_dt          = $rs->comment_dt;
            $comment_author      = $rs->comment_author;
            $comment_email       = $rs->comment_email;
            $comment_site        = $rs->comment_site;
            $comment_content     = $rs->comment_content;
            $comment_ip          = $rs->comment_ip;
            $comment_status      = $rs->comment_status;
            $comment_trackback   = (bool) $rs->comment_trackback;
            $comment_spam_status = $rs->comment_spam_status;
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

if (!$comment_id && !dcCore::app()->error->flag()) {
    dcCore::app()->error->add(__('No comments'));
}

$can_edit = $can_delete = $can_publish = false;
if (!dcCore::app()->error->flag() && isset($rs)) {
    $can_edit = $can_delete = $can_publish = dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id);

    if (!dcCore::app()->auth->check('contentadmin', dcCore::app()->blog->id) && dcCore::app()->auth->userID() == $rs->user_id) {
        $can_edit = true;
        if (dcCore::app()->auth->check('delete', dcCore::app()->blog->id)) {
            $can_delete = true;
        }
        if (dcCore::app()->auth->check('publish', dcCore::app()->blog->id)) {
            $can_publish = true;
        }
    }

    # update comment
    if (!empty($_POST['update']) && $can_edit) {
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

        $cur->comment_author  = $_POST['comment_author'];
        $cur->comment_email   = html::clean($_POST['comment_email']);
        $cur->comment_site    = html::clean($_POST['comment_site']);
        $cur->comment_content = dcCore::app()->HTMLfilter($_POST['comment_content']);

        if (isset($_POST['comment_status'])) {
            $cur->comment_status = (int) $_POST['comment_status'];
        }

        try {
            # --BEHAVIOR-- adminBeforeCommentUpdate
            dcCore::app()->callBehavior('adminBeforeCommentUpdate', $cur, $comment_id);

            dcCore::app()->blog->updComment($comment_id, $cur);

            # --BEHAVIOR-- adminAfterCommentUpdate
            dcCore::app()->callBehavior('adminAfterCommentUpdate', $cur, $comment_id);

            dcPage::addSuccessNotice(__('Comment has been successfully updated.'));
            dcCore::app()->adminurl->redirect('admin.comment', ['id' => $comment_id]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    if (!empty($_POST['delete']) && $can_delete) {
        try {
            # --BEHAVIOR-- adminBeforeCommentDelete
            dcCore::app()->callBehavior('adminBeforeCommentDelete', $comment_id);

            dcCore::app()->blog->delComment($comment_id);

            dcPage::addSuccessNotice(__('Comment has been successfully deleted.'));
            http::redirect(dcCore::app()->getPostAdminURL($rs->post_type, $rs->post_id) . '&co=1');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    if (!$can_edit) {
        dcCore::app()->error->add(__("You can't edit this comment."));
    }
}

/* DISPLAY
-------------------------------------------------------- */
if ($comment_id) {
    $breadcrumb = dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            html::escapeHTML($post_title)               => dcCore::app()->getPostAdminURL($post_type, $post_id) . '&amp;co=1#c' . $comment_id,
            __('Edit comment')                          => '',
        ]
    );
} else {
    $breadcrumb = dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            html::escapeHTML($post_title)               => dcCore::app()->getPostAdminURL($post_type, $post_id),
            __('Edit comment')                          => '',
        ]
    );
}

dcPage::open(
    __('Edit comment'),
    dcPage::jsConfirmClose('comment-form') .
    dcPage::jsLoad('js/_comment.js') .
    dcCore::app()->callBehavior('adminPostEditor', $comment_editor['xhtml'], 'comment', ['#comment_content'], 'xhtml') .
    # --BEHAVIOR-- adminCommentHeaders
    dcCore::app()->callBehavior('adminCommentHeaders'),
    $breadcrumb
);

if ($comment_id) {
    if (!empty($_GET['upd'])) {
        dcPage::success(__('Comment has been successfully updated.'));
    }

    $comment_mailto = '';
    if ($comment_email) {
        $comment_mailto = '<a href="mailto:' . html::escapeHTML($comment_email)
        . '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), dcCore::app()->blog->name))
        . '&amp;body='
        . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), $rs->getPostURL()))
        . '">' . __('Send an e-mail') . '</a>';
    }

    echo
    '<form action="' . dcCore::app()->adminurl->get('admin.comment') . '" method="post" id="comment-form">' .
    '<div class="fieldset">' .
    '<h3>' . __('Information collected') . '</h3>';

    if ($show_ip) {
        echo
        '<p>' . __('IP address:') . ' ' .
        '<a href="' . dcCore::app()->adminurl->get('admin.comments', ['ip' => $comment_ip]) . '">' . $comment_ip . '</a></p>';
    }

    echo
    '<p>' . __('Date:') . ' ' .
    dt::dt2str(__('%Y-%m-%d %H:%M'), $comment_dt) . '</p>' .
    '</div>' .

    '<h3>' . __('Comment submitted') . '</h3>' .
    '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Author:') . '</label>' .
    form::field('comment_author', 30, 255, [
        'default'    => html::escapeHTML($comment_author),
        'extra_html' => 'required placeholder="' . __('Author') . '"',
    ]) .
    '</p>' .

    '<p><label for="comment_email">' . __('Email:') . '</label>' .
    form::email('comment_email', 30, 255, html::escapeHTML($comment_email)) .
    '<span>' . $comment_mailto . '</span>' .
    '</p>' .

    '<p><label for="comment_site">' . __('Web site:') . '</label>' .
    form::url('comment_site', 30, 255, html::escapeHTML($comment_site)) .
    '</p>' .

    '<p><label for="comment_status">' . __('Status:') . '</label>' .
    form::combo(
        'comment_status',
        $status_combo,
        ['default' => $comment_status, 'disabled' => !$can_publish]
    ) .
    '</p>' .

    # --BEHAVIOR-- adminAfterCommentDesc
    dcCore::app()->callBehavior('adminAfterCommentDesc', $rs) .

    '<p class="area"><label for="comment_content">' . __('Comment:') . '</label> ' .
    form::textarea(
        'comment_content',
        50,
        10,
        [
            'default'    => html::escapeHTML($comment_content),
            'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
        ]
    ) .
    '</p>' .

    '<p>' . form::hidden('id', $comment_id) .
    dcCore::app()->formNonce() .
    '<input type="submit" accesskey="s" name="update" value="' . __('Save') . '" />' .
    ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';

    if ($can_delete) {
        echo ' <input type="submit" class="delete" name="delete" value="' . __('Delete') . '" />';
    }
    echo
        '</p>' .
        '</form>';
}

dcPage::helpBlock('core_comments');
dcPage::close();
