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

namespace Dotclear\Backend;

use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Filter\FilterComments;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class Comments extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_USAGE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]));

        if (!empty($_POST['delete_all_spam'])) {
            // Remove all spams

            try {
                dcCore::app()->blog->delJunkComments();
                $_SESSION['comments_del_spam'] = true;
                dcCore::app()->adminurl->redirect('admin.comments');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Filters

        dcCore::app()->admin->comment_filter = new FilterComments();

        // get list params
        $params = dcCore::app()->admin->comment_filter->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title'          => 'post_title',
            'comment_author'      => 'comment_author',
            'comment_spam_filter' => 'comment_spam_filter', ];

        # --BEHAVIOR-- adminCommentsSortbyLexCombo -- array<int,array<string,string>>
        dcCore::app()->callBehavior('adminCommentsSortbyLexCombo', [& $sortby_lex]);

        $params['order'] = (array_key_exists(dcCore::app()->admin->comment_filter->sortby, $sortby_lex) ?
            dcCore::app()->con->lexFields($sortby_lex[dcCore::app()->admin->comment_filter->sortby]) :
            dcCore::app()->admin->comment_filter->sortby) . ' ' . dcCore::app()->admin->comment_filter->order;

        // default filter ? do not display spam
        if (!dcCore::app()->admin->comment_filter->show() && dcCore::app()->admin->comment_filter->status == '') {
            $params['comment_status_not'] = dcBlog::COMMENT_JUNK;
        }
        $params['no_content'] = true;

        // Actions

        dcCore::app()->admin->default_action = '';
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_DELETE,
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id) && dcCore::app()->admin->comment_filter->status == -2) {
            dcCore::app()->admin->default_action = 'delete';
        }

        dcCore::app()->admin->comments_actions_page = new ActionsComments(dcCore::app()->adminurl->get('admin.comments'));

        if (dcCore::app()->admin->comments_actions_page->process()) {
            return self::status(false);
        }

        // List

        dcCore::app()->admin->comment_list = null;

        try {
            $comments = dcCore::app()->blog->getComments($params);
            $counter  = dcCore::app()->blog->getComments($params, true);

            dcCore::app()->admin->comment_list = new ListingComments($comments, $counter->f(0));
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return self::status(true);
    }

    public static function render(): void
    {
        Page::open(
            __('Comments and trackbacks'),
            Page::jsLoad('js/_comments.js') . dcCore::app()->admin->comment_filter->js(dcCore::app()->adminurl->get('admin.comments')),
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Comments and trackbacks')               => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            Page::success(__('Selected comments have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            Page::success(__('Selected comments have been successfully deleted.'));
        }

        if (!dcCore::app()->error->flag()) {
            if (isset($_SESSION['comments_del_spam'])) {
                Page::message(__('Spam comments have been successfully deleted.'));
                unset($_SESSION['comments_del_spam']);
            }

            $spam_count = dcCore::app()->blog->getComments(['comment_status' => dcBlog::COMMENT_JUNK], true)->f(0);
            if ($spam_count > 0) {
                echo
                '<form action="' . dcCore::app()->adminurl->get('admin.comments') . '" method="post" class="fieldset">';

                if (!dcCore::app()->admin->comment_filter->show() || (dcCore::app()->admin->comment_filter->status != -2)) {
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

                # --BEHAVIOR-- adminCommentsSpamForm --
                dcCore::app()->callBehavior('adminCommentsSpamForm');

                echo
                '</form>';
            }

            dcCore::app()->admin->comment_filter->display('admin.comments');

            // Show comments

            dcCore::app()->admin->comment_list->display(
                dcCore::app()->admin->comment_filter->page,
                dcCore::app()->admin->comment_filter->nb,
                '<form action="' . dcCore::app()->adminurl->get('admin.comments') . '" method="post" id="form-comments">' .

                '%s' .

                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
                form::combo(
                    'action',
                    dcCore::app()->admin->comments_actions_page->getCombo(),
                    ['default' => dcCore::app()->admin->default_action, 'extra_html' => 'title="' . __('Actions') . '"']
                ) .
                dcCore::app()->formNonce() .
                '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
                dcCore::app()->adminurl->getHiddenFormFields('admin.comments', dcCore::app()->admin->comment_filter->values(true)) .
                '</div>' .

                '</form>',
                dcCore::app()->admin->comment_filter->show(),
                (dcCore::app()->admin->comment_filter->show() || (dcCore::app()->admin->comment_filter->status == -2)),
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id)
            );
        }

        Page::helpBlock('core_comments');
        Page::close();
    }
}
