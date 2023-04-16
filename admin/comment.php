<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

require __DIR__ . '/../inc/admin/prepend.php';

class adminComment
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        dcPage::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        dcCore::app()->admin->show_ip = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id);

        dcCore::app()->admin->comment_id      = null;
        dcCore::app()->admin->comment_dt      = '';
        dcCore::app()->admin->comment_author  = '';
        dcCore::app()->admin->comment_email   = '';
        dcCore::app()->admin->comment_site    = '';
        dcCore::app()->admin->comment_content = '';
        dcCore::app()->admin->comment_ip      = '';
        dcCore::app()->admin->comment_status  = '';
        // Unused yet:
        dcCore::app()->admin->comment_trackback   = false;
        dcCore::app()->admin->comment_spam_status = '';
        //

        dcCore::app()->admin->comment_editor = dcCore::app()->auth->getOption('editor');

        // Status combo
        dcCore::app()->admin->status_combo = dcAdminCombos::getCommentStatusesCombo();
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        $params = [];
        if (!empty($_POST['add']) && !empty($_POST['post_id'])) {
            // Adding comment (comming from post form, comments tab)

            try {
                dcCore::app()->admin->rs = dcCore::app()->blog->getPosts(['post_id' => $_POST['post_id'], 'post_type' => '']);

                if (dcCore::app()->admin->rs->isEmpty()) {
                    throw new Exception(__('Entry does not exist.'));
                }

                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = dcCore::app()->HTMLfilter($_POST['comment_content']);
                $cur->post_id         = (int) $_POST['post_id'];

                # --BEHAVIOR-- adminBeforeCommentCreate -- cursor
                dcCore::app()->callBehavior('adminBeforeCommentCreate', $cur);

                dcCore::app()->admin->comment_id = dcCore::app()->blog->addComment($cur);

                # --BEHAVIOR-- adminAfterCommentCreate -- cursor, string|int
                dcCore::app()->callBehavior('adminAfterCommentCreate', $cur, dcCore::app()->admin->comment_id);

                dcPage::addSuccessNotice(__('Comment has been successfully created.'));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
            Http::redirect(dcCore::app()->getPostAdminURL(dcCore::app()->admin->rs->post_type, dcCore::app()->admin->rs->post_id, false) . '&co=1');
        }

        dcCore::app()->admin->rs         = null;
        dcCore::app()->admin->post_id    = '';
        dcCore::app()->admin->post_type  = '';
        dcCore::app()->admin->post_title = '';

        if (!empty($_REQUEST['id'])) {
            $params['comment_id'] = $_REQUEST['id'];

            try {
                dcCore::app()->admin->rs = dcCore::app()->blog->getComments($params);
                if (!dcCore::app()->admin->rs->isEmpty()) {
                    dcCore::app()->admin->comment_id      = dcCore::app()->admin->rs->comment_id;
                    dcCore::app()->admin->post_id         = dcCore::app()->admin->rs->post_id;
                    dcCore::app()->admin->post_type       = dcCore::app()->admin->rs->post_type;
                    dcCore::app()->admin->post_title      = dcCore::app()->admin->rs->post_title;
                    dcCore::app()->admin->comment_dt      = dcCore::app()->admin->rs->comment_dt;
                    dcCore::app()->admin->comment_author  = dcCore::app()->admin->rs->comment_author;
                    dcCore::app()->admin->comment_email   = dcCore::app()->admin->rs->comment_email;
                    dcCore::app()->admin->comment_site    = dcCore::app()->admin->rs->comment_site;
                    dcCore::app()->admin->comment_content = dcCore::app()->admin->rs->comment_content;
                    dcCore::app()->admin->comment_ip      = dcCore::app()->admin->rs->comment_ip;
                    dcCore::app()->admin->comment_status  = dcCore::app()->admin->rs->comment_status;
                    // Unused yet:
                    dcCore::app()->admin->comment_trackback   = (bool) dcCore::app()->admin->rs->comment_trackback;
                    dcCore::app()->admin->comment_spam_status = dcCore::app()->admin->rs->comment_spam_status;
                    //
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!dcCore::app()->admin->comment_id && !dcCore::app()->error->flag()) {
            dcCore::app()->error->add(__('No comments'));
        }

        $can_edit = dcCore::app()->admin->can_delete = dcCore::app()->admin->can_publish = false;

        if (!dcCore::app()->error->flag() && isset(dcCore::app()->admin->rs)) {
            $can_edit = dcCore::app()->admin->can_delete = dcCore::app()->admin->can_publish = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);

            if (!dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id) && dcCore::app()->auth->userID() == dcCore::app()->admin->rs->user_id) {
                $can_edit = true;
                if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_DELETE,
                ]), dcCore::app()->blog->id)) {
                    dcCore::app()->admin->can_delete = true;
                }
                if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_PUBLISH,
                ]), dcCore::app()->blog->id)) {
                    dcCore::app()->admin->can_publish = true;
                }
            }

            if (!empty($_POST['update']) && $can_edit) {
                // update comment

                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::COMMENT_TABLE_NAME);

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = dcCore::app()->HTMLfilter($_POST['comment_content']);

                if (isset($_POST['comment_status'])) {
                    $cur->comment_status = (int) $_POST['comment_status'];
                }

                try {
                    # --BEHAVIOR-- adminBeforeCommentUpdate -- cursor
                    dcCore::app()->callBehavior('adminBeforeCommentUpdate', $cur, dcCore::app()->admin->comment_id);

                    dcCore::app()->blog->updComment(dcCore::app()->admin->comment_id, $cur);

                    # --BEHAVIOR-- adminAfterCommentUpdate -- cursor, string|int
                    dcCore::app()->callBehavior('adminAfterCommentUpdate', $cur, dcCore::app()->admin->comment_id);

                    dcPage::addSuccessNotice(__('Comment has been successfully updated.'));
                    dcCore::app()->adminurl->redirect('admin.comment', ['id' => dcCore::app()->admin->comment_id]);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            if (!empty($_POST['delete']) && dcCore::app()->admin->can_delete) {
                // delete comment

                try {
                    # --BEHAVIOR-- adminBeforeCommentDelete -- string|int
                    dcCore::app()->callBehavior('adminBeforeCommentDelete', dcCore::app()->admin->comment_id);

                    dcCore::app()->blog->delComment(dcCore::app()->admin->comment_id);

                    dcPage::addSuccessNotice(__('Comment has been successfully deleted.'));
                    Http::redirect(dcCore::app()->getPostAdminURL(dcCore::app()->admin->rs->post_type, dcCore::app()->admin->rs->post_id) . '&co=1');
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            if (!$can_edit) {
                dcCore::app()->error->add(__("You can't edit this comment."));
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        $breadcrumb = [
            Html::escapeHTML(dcCore::app()->blog->name) => '',
        ];
        $posts_types = dcCore::app()->getPostTypes();
        if (array_key_exists(dcCore::app()->admin->post_type, $posts_types)) {
            $breadcrumb[Html::escapeHTML(__($posts_types[dcCore::app()->admin->post_type]['label']))] = '';
        }
        if (dcCore::app()->admin->comment_id) {
            $breadcrumb[Html::escapeHTML(dcCore::app()->admin->post_title)] = dcCore::app()->getPostAdminURL(dcCore::app()->admin->post_type, dcCore::app()->admin->post_id) . '&amp;co=1#c' . dcCore::app()->admin->comment_id;
        } else {
            $breadcrumb[Html::escapeHTML(dcCore::app()->admin->post_title)] = dcCore::app()->getPostAdminURL(dcCore::app()->admin->post_type, dcCore::app()->admin->post_id);
        }
        $breadcrumb[__('Edit comment')] = '';

        dcPage::open(
            __('Edit comment'),
            dcPage::jsConfirmClose('comment-form') .
            dcPage::jsLoad('js/_comment.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
            dcCore::app()->callBehavior('adminPostEditor', dcCore::app()->admin->comment_editor['xhtml'], 'comment', ['#comment_content'], 'xhtml') .
            # --BEHAVIOR-- adminCommentHeaders --
            dcCore::app()->callBehavior('adminCommentHeaders'),
            dcPage::breadcrumb($breadcrumb)
        );

        if (dcCore::app()->admin->comment_id) {
            if (!empty($_GET['upd'])) {
                dcPage::success(__('Comment has been successfully updated.'));
            }

            $comment_mailto = '';
            if (dcCore::app()->admin->comment_email) {
                $comment_mailto = '<a href="mailto:' . Html::escapeHTML(dcCore::app()->admin->comment_email) .
                    '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), dcCore::app()->blog->name)) .
                    '&amp;body=' . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), dcCore::app()->admin->rs->getPostURL())) . '">' . __('Send an e-mail') . '</a>';
            }

            echo
            '<form action="' . dcCore::app()->adminurl->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="fieldset">' .
            '<h3>' . __('Information collected') . '</h3>';

            if (dcCore::app()->admin->show_ip) {
                echo
                '<p>' . __('IP address:') . ' ' .
                '<a href="' . dcCore::app()->adminurl->get('admin.comments', ['ip' => dcCore::app()->admin->comment_ip]) . '">' . dcCore::app()->admin->comment_ip . '</a></p>';
            }

            echo
            '<p>' . __('Date:') . ' ' .
            dt::dt2str(__('%Y-%m-%d %H:%M'), dcCore::app()->admin->comment_dt) . '</p>' .
            '</div>' .

            '<h3>' . __('Comment submitted') . '</h3>' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Author:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(dcCore::app()->admin->comment_author),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(dcCore::app()->admin->comment_email)) .
            '<span>' . $comment_mailto . '</span>' .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(dcCore::app()->admin->comment_site)) .
            '</p>' .

            '<p><label for="comment_status">' . __('Status:') . '</label>' .
            form::combo(
                'comment_status',
                dcCore::app()->admin->status_combo,
                ['default' => dcCore::app()->admin->comment_status, 'disabled' => !dcCore::app()->admin->can_publish]
            ) .
            '</p>' .

            # --BEHAVIOR-- adminAfterCommentDesc -- dcRecord
            dcCore::app()->callBehavior('adminAfterCommentDesc', dcCore::app()->admin->rs) .

            '<p class="area"><label for="comment_content">' . __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                10,
                [
                    'default'    => Html::escapeHTML(dcCore::app()->admin->comment_content),
                    'extra_html' => 'lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) .
            '</p>' .

            '<p>' . form::hidden('id', dcCore::app()->admin->comment_id) .
            dcCore::app()->formNonce() .
            '<input type="submit" accesskey="s" name="update" value="' . __('Save') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';

            if (dcCore::app()->admin->can_delete) {
                echo ' <input type="submit" class="delete" name="delete" value="' . __('Delete') . '" />';
            }
            echo
            '</p>' .
            '</form>';
        }

        dcPage::helpBlock('core_comments');
        dcPage::close();
    }
}

adminComment::init();
adminComment::process();
adminComment::render();
