<?php
/**
 * @since 2.27 Before as admin/comment.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class Comment extends Process
{
    public static function init(): bool
    {
        Page::check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_USAGE,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::backend()->show_ip = Core::auth()->check(Core::auth()->makePermissions([
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id);

        Core::backend()->comment_id      = null;
        Core::backend()->comment_dt      = '';
        Core::backend()->comment_author  = '';
        Core::backend()->comment_email   = '';
        Core::backend()->comment_site    = '';
        Core::backend()->comment_content = '';
        Core::backend()->comment_ip      = '';
        Core::backend()->comment_status  = '';
        // Unused yet:
        Core::backend()->comment_trackback   = false;
        Core::backend()->comment_spam_status = '';
        //

        Core::backend()->comment_editor = Core::auth()->getOption('editor');

        // Status combo
        Core::backend()->status_combo = Combos::getCommentStatusesCombo();

        return self::status(true);
    }

    public static function process(): bool
    {
        $params = [];
        if (!empty($_POST['add']) && !empty($_POST['post_id'])) {
            // Adding comment (comming from post form, comments tab)

            try {
                Core::backend()->rs = Core::blog()->getPosts(['post_id' => $_POST['post_id'], 'post_type' => '']);

                if (Core::backend()->rs->isEmpty()) {
                    throw new Exception(__('Entry does not exist.'));
                }

                $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME);

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = Core::filter()->HTMLfilter($_POST['comment_content']);
                $cur->post_id         = (int) $_POST['post_id'];

                # --BEHAVIOR-- adminBeforeCommentCreate -- Cursor
                Core::behavior()->callBehavior('adminBeforeCommentCreate', $cur);

                Core::backend()->comment_id = Core::blog()->addComment($cur);

                # --BEHAVIOR-- adminAfterCommentCreate -- Cursor, string|int
                Core::behavior()->callBehavior('adminAfterCommentCreate', $cur, Core::backend()->comment_id);

                Notices::addSuccessNotice(__('Comment has been successfully created.'));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
            Http::redirect(Core::postTypes()->get(Core::backend()->rs->post_type)->adminUrl(Core::backend()->rs->post_id, false, ['co' => 1]));
        }

        Core::backend()->rs         = null;
        Core::backend()->post_id    = '';
        Core::backend()->post_type  = '';
        Core::backend()->post_title = '';

        if (!empty($_REQUEST['id'])) {
            $params['comment_id'] = $_REQUEST['id'];

            try {
                Core::backend()->rs = Core::blog()->getComments($params);
                if (!Core::backend()->rs->isEmpty()) {
                    Core::backend()->comment_id      = Core::backend()->rs->comment_id;
                    Core::backend()->post_id         = Core::backend()->rs->post_id;
                    Core::backend()->post_type       = Core::backend()->rs->post_type;
                    Core::backend()->post_title      = Core::backend()->rs->post_title;
                    Core::backend()->comment_dt      = Core::backend()->rs->comment_dt;
                    Core::backend()->comment_author  = Core::backend()->rs->comment_author;
                    Core::backend()->comment_email   = Core::backend()->rs->comment_email;
                    Core::backend()->comment_site    = Core::backend()->rs->comment_site;
                    Core::backend()->comment_content = Core::backend()->rs->comment_content;
                    Core::backend()->comment_ip      = Core::backend()->rs->comment_ip;
                    Core::backend()->comment_status  = Core::backend()->rs->comment_status;
                    // Unused yet:
                    Core::backend()->comment_trackback   = (bool) Core::backend()->rs->comment_trackback;
                    Core::backend()->comment_spam_status = Core::backend()->rs->comment_spam_status;
                    //
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!Core::backend()->comment_id && !dcCore::app()->error->flag()) {
            dcCore::app()->error->add(__('No comments'));
        }

        $can_edit = Core::backend()->can_delete = Core::backend()->can_publish = false;

        if (!dcCore::app()->error->flag() && isset(Core::backend()->rs)) {
            $can_edit = Core::backend()->can_delete = Core::backend()->can_publish = Core::auth()->check(Core::auth()->makePermissions([
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id);

            if (!Core::auth()->check(Core::auth()->makePermissions([
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]), Core::blog()->id) && Core::auth()->userID() == Core::backend()->rs->user_id) {
                $can_edit = true;
                if (Core::auth()->check(Core::auth()->makePermissions([
                    Core::auth()::PERMISSION_DELETE,
                ]), Core::blog()->id)) {
                    Core::backend()->can_delete = true;
                }
                if (Core::auth()->check(Core::auth()->makePermissions([
                    Core::auth()::PERMISSION_PUBLISH,
                ]), Core::blog()->id)) {
                    Core::backend()->can_publish = true;
                }
            }

            if (!empty($_POST['update']) && $can_edit) {
                // update comment

                $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::COMMENT_TABLE_NAME);

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = Core::filter()->HTMLfilter($_POST['comment_content']);

                if (isset($_POST['comment_status'])) {
                    $cur->comment_status = (int) $_POST['comment_status'];
                }

                try {
                    # --BEHAVIOR-- adminBeforeCommentUpdate -- Cursor
                    Core::behavior()->callBehavior('adminBeforeCommentUpdate', $cur, Core::backend()->comment_id);

                    Core::blog()->updComment(Core::backend()->comment_id, $cur);

                    # --BEHAVIOR-- adminAfterCommentUpdate -- Cursor, string|int
                    Core::behavior()->callBehavior('adminAfterCommentUpdate', $cur, Core::backend()->comment_id);

                    Notices::addSuccessNotice(__('Comment has been successfully updated.'));
                    Core::backend()->url->redirect('admin.comment', ['id' => Core::backend()->comment_id]);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            if (!empty($_POST['delete']) && Core::backend()->can_delete) {
                // delete comment

                try {
                    # --BEHAVIOR-- adminBeforeCommentDelete -- string|int
                    Core::behavior()->callBehavior('adminBeforeCommentDelete', Core::backend()->comment_id);

                    Core::blog()->delComment(Core::backend()->comment_id);

                    Notices::addSuccessNotice(__('Comment has been successfully deleted.'));
                    Http::redirect(Core::postTypes()->get(Core::backend()->rs->post_type)->adminUrl(Core::backend()->rs->post_id, false, ['co' => 1]));
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            if (!$can_edit) {
                dcCore::app()->error->add(__("You can't edit this comment."));
            }
        }

        return true;
    }

    public static function render(): void
    {
        $breadcrumb = [
            Html::escapeHTML(Core::blog()->name) => '',
        ];

        if (Core::postTypes()->exists(Core::backend()->post_type)) {
            $breadcrumb[Html::escapeHTML(__(Core::postTypes()->get(Core::backend()->post_type)->label))] = '';
        }
        if (Core::backend()->comment_id) {
            $breadcrumb[Html::escapeHTML(Core::backend()->post_title)] = Core::postTypes()->get(Core::backend()->post_type)->adminUrl(Core::backend()->post_id) . '&amp;co=1#c' . Core::backend()->comment_id;
        } else {
            $breadcrumb[Html::escapeHTML(Core::backend()->post_title)] = Core::postTypes()->get(Core::backend()->post_type)->adminUrl(Core::backend()->post_id);
        }
        $breadcrumb[__('Edit comment')] = '';

        Page::open(
            __('Edit comment'),
            Page::jsConfirmClose('comment-form') .
            Page::jsLoad('js/_comment.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
            Core::behavior()->callBehavior('adminPostEditor', Core::backend()->comment_editor['xhtml'], 'comment', ['#comment_content'], 'xhtml') .
            # --BEHAVIOR-- adminCommentHeaders --
            Core::behavior()->callBehavior('adminCommentHeaders'),
            Page::breadcrumb($breadcrumb)
        );

        if (Core::backend()->comment_id) {
            if (!empty($_GET['upd'])) {
                Notices::success(__('Comment has been successfully updated.'));
            }

            $comment_mailto = '';
            if (Core::backend()->comment_email) {
                $comment_mailto = '<a href="mailto:' . Html::escapeHTML(Core::backend()->comment_email) .
                    '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), Core::blog()->name)) .
                    '&amp;body=' . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), Core::backend()->rs->getPostURL())) . '">' . __('Send an e-mail') . '</a>';
            }

            echo
            '<form action="' . Core::backend()->url->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="fieldset">' .
            '<h3>' . __('Information collected') . '</h3>';

            if (Core::backend()->show_ip) {
                echo
                '<p>' . __('IP address:') . ' ' .
                '<a href="' . Core::backend()->url->get('admin.comments', ['ip' => Core::backend()->comment_ip]) . '">' . Core::backend()->comment_ip . '</a></p>';
            }

            echo
            '<p>' . __('Date:') . ' ' .
            Date::dt2str(__('%Y-%m-%d %H:%M'), Core::backend()->comment_dt) . '</p>' .
            '</div>' .

            '<h3>' . __('Comment submitted') . '</h3>' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Author:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(Core::backend()->comment_author),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(Core::backend()->comment_email)) .
            '<span>' . $comment_mailto . '</span>' .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(Core::backend()->comment_site)) .
            '</p>' .

            '<p><label for="comment_status">' . __('Status:') . '</label>' .
            form::combo(
                'comment_status',
                Core::backend()->status_combo,
                ['default' => Core::backend()->comment_status, 'disabled' => !Core::backend()->can_publish]
            ) .
            '</p>' .

            # --BEHAVIOR-- adminAfterCommentDesc -- MetaRecord
            Core::behavior()->callBehavior('adminAfterCommentDesc', Core::backend()->rs) .

            '<p class="area"><label for="comment_content">' . __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                10,
                [
                    'default'    => Html::escapeHTML(Core::backend()->comment_content),
                    'extra_html' => 'lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) .
            '</p>' .

            '<p>' . form::hidden('id', Core::backend()->comment_id) .
            Core::nonce()->getFormNonce() .
            '<input type="submit" accesskey="s" name="update" value="' . __('Save') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';

            if (Core::backend()->can_delete) {
                echo ' <input type="submit" class="delete" name="delete" value="' . __('Delete') . '" />';
            }
            echo
            '</p>' .
            '</form>';
        }

        Page::helpBlock('core_comments');
        Page::close();
    }
}
