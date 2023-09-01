<?php
/**
 * @since 2.27 Before as admin/comments.php
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
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Filter\FilterComments;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Comments extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        if (!empty($_POST['delete_all_spam'])) {
            // Remove all spams

            try {
                App::blog()->delJunkComments();
                $_SESSION['comments_del_spam'] = true;
                App::backend()->url->redirect('admin.comments');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Filters

        App::backend()->comment_filter = new FilterComments();

        // get list params
        $params = App::backend()->comment_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title'          => 'post_title',
            'comment_author'      => 'comment_author',
            'comment_spam_filter' => 'comment_spam_filter', ];

        # --BEHAVIOR-- adminCommentsSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminCommentsSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(App::backend()->comment_filter->sortby, $sortby_lex) ?
            App::con()->lexFields($sortby_lex[App::backend()->comment_filter->sortby]) :
            App::backend()->comment_filter->sortby) . ' ' . App::backend()->comment_filter->order;

        // default filter ? do not display spam
        if (!App::backend()->comment_filter->show() && App::backend()->comment_filter->status == '') {
            $params['comment_status_not'] = dcBlog::COMMENT_JUNK;
        }
        $params['no_content'] = true;

        // Actions

        App::backend()->default_action = '';
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id) && App::backend()->comment_filter->status == -2) {
            App::backend()->default_action = 'delete';
        }

        App::backend()->comments_actions_page = new ActionsComments(App::backend()->url->get('admin.comments'));

        if (App::backend()->comments_actions_page->process()) {
            return self::status(false);
        }

        // List

        App::backend()->comment_list = null;

        try {
            $comments = App::blog()->getComments($params);
            $counter  = App::blog()->getComments($params, true);

            App::backend()->comment_list = new ListingComments($comments, $counter->f(0));
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        // IP are available only for super-admin and admin
        $show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id
        );

        Page::open(
            __('Comments and trackbacks'),
            Page::jsLoad('js/_comments.js') . App::backend()->comment_filter->js(App::backend()->url->get('admin.comments')),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name) => '',
                    __('Comments and trackbacks')       => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            Notices::success(__('Selected comments have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Notices::success(__('Selected comments have been successfully deleted.'));
        }

        if (!App::error()->flag()) {
            if (isset($_SESSION['comments_del_spam'])) {
                Notices::message(__('Spam comments have been successfully deleted.'));
                unset($_SESSION['comments_del_spam']);
            }

            $spam_count = App::blog()->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
            if ($spam_count > 0) {
                echo
                '<form action="' . App::backend()->url->get('admin.comments') . '" method="post" class="fieldset">';

                if (!App::backend()->comment_filter->show() || (App::backend()->comment_filter->status != -2)) {
                    if ($spam_count == 1) {
                        echo '<p>' . sprintf(__('You have one spam comment.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                        '<a href="' . App::backend()->url->get('admin.comments', ['status' => -2]) . '">' . __('Show it.') . '</a></p>';
                    } elseif ($spam_count > 1) {
                        echo '<p>' . sprintf(__('You have %s spam comments.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                        '<a href="' . App::backend()->url->get('admin.comments', ['status' => -2]) . '">' . __('Show them.') . '</a></p>';
                    }
                }

                echo
                '<p>' .
                App::nonce()->getFormNonce() .
                '<input name="delete_all_spam" class="delete" type="submit" value="' . __('Delete all spams') . '" /></p>';

                # --BEHAVIOR-- adminCommentsSpamForm --
                App::behavior()->callBehavior('adminCommentsSpamForm');

                echo
                '</form>';
            }

            App::backend()->comment_filter->display('admin.comments');

            // Show comments

            App::backend()->comment_list->display(
                App::backend()->comment_filter->page,
                App::backend()->comment_filter->nb,
                '<form action="' . App::backend()->url->get('admin.comments') . '" method="post" id="form-comments">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
                form::combo(
                    'action',
                    App::backend()->comments_actions_page->getCombo(),
                    ['default' => App::backend()->default_action, 'extra_html' => 'title="' . __('Actions') . '"']
                ) .
                App::nonce()->getFormNonce() .
                '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
                App::backend()->url->getHiddenFormFields('admin.comments', App::backend()->comment_filter->values(true)) .
                '</div>' .

                '</form>',
                App::backend()->comment_filter->show(),
                (App::backend()->comment_filter->show() || (App::backend()->comment_filter->status == -2)),
                $show_ip
            );
        }

        Page::helpBlock('core_comments');
        Page::close();
    }
}
