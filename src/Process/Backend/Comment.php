<?php
/**
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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/comment.php
 */
class Comment extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::backend()->show_ip = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());

        App::backend()->comment_id      = null;
        App::backend()->comment_dt      = '';
        App::backend()->comment_author  = '';
        App::backend()->comment_email   = '';
        App::backend()->comment_site    = '';
        App::backend()->comment_content = '';
        App::backend()->comment_ip      = '';
        App::backend()->comment_status  = '';
        // Unused yet:
        App::backend()->comment_trackback   = false;
        App::backend()->comment_spam_status = '';
        //

        App::backend()->comment_editor = App::auth()->getOption('editor');

        // Status combo
        App::backend()->status_combo = Combos::getCommentStatusesCombo();

        return self::status(true);
    }

    public static function process(): bool
    {
        $params = [];
        if (!empty($_POST['add']) && !empty($_POST['post_id'])) {
            // Adding comment (comming from post form, comments tab)

            try {
                App::backend()->rs = App::blog()->getPosts(['post_id' => $_POST['post_id'], 'post_type' => '']);

                if (App::backend()->rs->isEmpty()) {
                    throw new Exception(__('Entry does not exist.'));
                }

                $cur = App::blog()->openCommentCursor();

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = App::filter()->HTMLfilter($_POST['comment_content']);
                $cur->post_id         = (int) $_POST['post_id'];

                # --BEHAVIOR-- adminBeforeCommentCreate -- Cursor
                App::behavior()->callBehavior('adminBeforeCommentCreate', $cur);

                App::backend()->comment_id = App::blog()->addComment($cur);

                # --BEHAVIOR-- adminAfterCommentCreate -- Cursor, string|int
                App::behavior()->callBehavior('adminAfterCommentCreate', $cur, App::backend()->comment_id);

                Notices::addSuccessNotice(__('Comment has been successfully created.'));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
            Http::redirect(App::postTypes()->get(App::backend()->rs->post_type)->adminUrl(App::backend()->rs->post_id, false, ['co' => 1]));
        }

        App::backend()->rs         = null;
        App::backend()->post_id    = '';
        App::backend()->post_type  = '';
        App::backend()->post_title = '';

        if (!empty($_REQUEST['id'])) {
            $params['comment_id'] = $_REQUEST['id'];

            try {
                App::backend()->rs = App::blog()->getComments($params);
                if (!App::backend()->rs->isEmpty()) {
                    App::backend()->comment_id      = App::backend()->rs->comment_id;
                    App::backend()->post_id         = App::backend()->rs->post_id;
                    App::backend()->post_type       = App::backend()->rs->post_type;
                    App::backend()->post_title      = App::backend()->rs->post_title;
                    App::backend()->comment_dt      = App::backend()->rs->comment_dt;
                    App::backend()->comment_author  = App::backend()->rs->comment_author;
                    App::backend()->comment_email   = App::backend()->rs->comment_email;
                    App::backend()->comment_site    = App::backend()->rs->comment_site;
                    App::backend()->comment_content = App::backend()->rs->comment_content;
                    App::backend()->comment_ip      = App::backend()->rs->comment_ip;
                    App::backend()->comment_status  = App::backend()->rs->comment_status;
                    // Unused yet:
                    App::backend()->comment_trackback   = (bool) App::backend()->rs->comment_trackback;
                    App::backend()->comment_spam_status = App::backend()->rs->comment_spam_status;
                    //
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!App::backend()->comment_id && !App::error()->flag()) {
            App::error()->add(__('No comments'));
        }

        $can_edit = App::backend()->can_delete = App::backend()->can_publish = false;

        if (!App::error()->flag() && isset(App::backend()->rs)) {
            $can_edit = App::backend()->can_delete = App::backend()->can_publish = App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id());

            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()) && App::auth()->userID() == App::backend()->rs->user_id) {
                $can_edit = true;
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_DELETE,
                ]), App::blog()->id())) {
                    App::backend()->can_delete = true;
                }
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_PUBLISH,
                ]), App::blog()->id())) {
                    App::backend()->can_publish = true;
                }
            }

            if (!empty($_POST['update']) && $can_edit) {
                // update comment

                $cur = App::blog()->openCommentCursor();

                $cur->comment_author  = $_POST['comment_author'];
                $cur->comment_email   = Html::clean($_POST['comment_email']);
                $cur->comment_site    = Html::clean($_POST['comment_site']);
                $cur->comment_content = App::filter()->HTMLfilter($_POST['comment_content']);

                if (isset($_POST['comment_status'])) {
                    $cur->comment_status = (int) $_POST['comment_status'];
                }

                try {
                    # --BEHAVIOR-- adminBeforeCommentUpdate -- Cursor
                    App::behavior()->callBehavior('adminBeforeCommentUpdate', $cur, App::backend()->comment_id);

                    App::blog()->updComment(App::backend()->comment_id, $cur);

                    # --BEHAVIOR-- adminAfterCommentUpdate -- Cursor, string|int
                    App::behavior()->callBehavior('adminAfterCommentUpdate', $cur, App::backend()->comment_id);

                    Notices::addSuccessNotice(__('Comment has been successfully updated.'));
                    App::backend()->url()->redirect('admin.comment', ['id' => App::backend()->comment_id]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!empty($_POST['delete']) && App::backend()->can_delete) {
                // delete comment

                try {
                    # --BEHAVIOR-- adminBeforeCommentDelete -- string|int
                    App::behavior()->callBehavior('adminBeforeCommentDelete', App::backend()->comment_id);

                    App::blog()->delComment(App::backend()->comment_id);

                    Notices::addSuccessNotice(__('Comment has been successfully deleted.'));
                    Http::redirect(App::postTypes()->get(App::backend()->rs->post_type)->adminUrl(App::backend()->rs->post_id, false, ['co' => 1]));
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!$can_edit) {
                App::error()->add(__("You can't edit this comment."));
            }
        }

        return true;
    }

    public static function render(): void
    {
        $breadcrumb = [
            Html::escapeHTML(App::blog()->name()) => '',
        ];

        if (App::postTypes()->exists(App::backend()->post_type)) {
            $breadcrumb[Html::escapeHTML(__(App::postTypes()->get(App::backend()->post_type)->get('label')))] = '';
        }
        if (App::backend()->comment_id) {
            $breadcrumb[Html::escapeHTML(App::backend()->post_title)] = App::postTypes()->get(App::backend()->post_type)->adminUrl(App::backend()->post_id) . '&amp;co=1#c' . App::backend()->comment_id;
        } else {
            $breadcrumb[Html::escapeHTML(App::backend()->post_title)] = App::postTypes()->get(App::backend()->post_type)->adminUrl(App::backend()->post_id);
        }
        $breadcrumb[__('Edit comment')] = '';

        Page::open(
            __('Edit comment'),
            Page::jsConfirmClose('comment-form') .
            Page::jsLoad('js/_comment.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
            App::behavior()->callBehavior('adminPostEditor', App::backend()->comment_editor['xhtml'], 'comment', ['#comment_content'], 'xhtml') .
            # --BEHAVIOR-- adminCommentHeaders --
            App::behavior()->callBehavior('adminCommentHeaders'),
            Page::breadcrumb($breadcrumb)
        );

        if (App::backend()->comment_id) {
            if (!empty($_GET['upd'])) {
                Notices::success(__('Comment has been successfully updated.'));
            }

            $comment_mailto = '';
            if (App::backend()->comment_email) {
                $comment_mailto = '<a href="mailto:' . Html::escapeHTML(App::backend()->comment_email) .
                    '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), App::blog()->name())) .
                    '&amp;body=' . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), App::backend()->rs->getPostURL())) . '">' . __('Send an e-mail') . '</a>';
            }

            echo
            '<form action="' . App::backend()->url()->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="fieldset">' .
            '<h3>' . __('Information collected') . '</h3>';

            if (App::backend()->show_ip) {
                echo
                '<p>' . __('IP address:') . ' ' .
                '<a href="' . App::backend()->url()->get('admin.comments', ['ip' => App::backend()->comment_ip]) . '">' . App::backend()->comment_ip . '</a></p>';
            }

            echo
            '<p>' . __('Date:') . ' ' .
            Date::dt2str(__('%Y-%m-%d %H:%M'), App::backend()->comment_dt) . '</p>' .
            '</div>' .

            '<h3>' . __('Comment submitted') . '</h3>' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr>' . __('Author:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(App::backend()->comment_author),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(App::backend()->comment_email)) .
            '<span>' . $comment_mailto . '</span>' .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(App::backend()->comment_site)) .
            '</p>' .

            '<p><label for="comment_status">' . __('Status:') . '</label>' .
            form::combo(
                'comment_status',
                App::backend()->status_combo,
                ['default' => App::backend()->comment_status, 'disabled' => !App::backend()->can_publish]
            ) .
            '</p>' .

            # --BEHAVIOR-- adminAfterCommentDesc -- MetaRecord
            App::behavior()->callBehavior('adminAfterCommentDesc', App::backend()->rs) .

            '<p class="area"><label for="comment_content">' . __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                10,
                [
                    'default'    => Html::escapeHTML(App::backend()->comment_content),
                    'extra_html' => 'lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"',
                ]
            ) .
            '</p>' .

            '<p>' . form::hidden('id', App::backend()->comment_id) .
            App::nonce()->getFormNonce() .
            '<input type="submit" accesskey="s" name="update" value="' . __('Save') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';

            if (App::backend()->can_delete) {
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
