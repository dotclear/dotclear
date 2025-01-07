<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Filter\FilterComments;
use Dotclear\Core\Backend\Listing\ListingComments;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @since 2.27 Before as admin/comments.php
 */
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
                App::backend()->url()->redirect('admin.comments');
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
        App::behavior()->callBehavior('adminCommentsSortbyLexCombo', [&$sortby_lex]);

        $params['order'] = (array_key_exists(App::backend()->comment_filter->sortby, $sortby_lex) ?
            App::con()->lexFields($sortby_lex[App::backend()->comment_filter->sortby]) :
            App::backend()->comment_filter->sortby) . ' ' . App::backend()->comment_filter->order;

        // default filter ? do not display spam
        if (!App::backend()->comment_filter->show() && App::backend()->comment_filter->status == '') {
            $params['comment_status_not'] = App::status()->comment()::JUNK;
        }
        $params['no_content'] = true;

        // Actions

        App::backend()->default_action = '';
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id()) && App::backend()->comment_filter->status == -2) {
            App::backend()->default_action = 'delete';
        }

        App::backend()->comments_actions_page = new ActionsComments(App::backend()->url()->get('admin.comments'));

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
            App::blog()->id()
        );

        Page::open(
            __('Comments and trackbacks'),
            Page::jsLoad('js/_comments.js') . App::backend()->comment_filter->js(App::backend()->url()->get('admin.comments')),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Comments and trackbacks')         => '',
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

            $spam_count = App::blog()->getComments(['comment_status' => App::status()->comment()::JUNK], true)->f(0);
            if ($spam_count > 0) {
                if (!App::backend()->comment_filter->show() || (App::backend()->comment_filter->status != -2)) {
                    if ($spam_count == 1) {
                        $count = (new Para())
                            ->class('form-buttons')
                            ->items([
                                new Text(null, sprintf(__('You have one spam comment.'), '<strong>' . $spam_count . '</strong>')),
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => -2]))
                                    ->text(__('Show it.')),
                            ]);
                    } elseif ($spam_count > 1) {
                        $count = (new Para())
                            ->class('form-buttons')
                            ->items([
                                new Text(null, sprintf(__('You have %s spam comments.'), '<strong>' . $spam_count . '</strong>')),
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => -2]))
                                    ->text(__('Show them.')),
                            ]);
                    } else {
                        $count = (new None());
                    }
                } else {
                    $count = (new None());
                }

                echo (new Form('form-spams'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.comments'))
                    ->class('fieldset')
                    ->fields([
                        $count,
                        (new Para())
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit('delete_all_spam', __('Delete all spams')))
                                    ->class('delete'),
                            ]),
                        # --BEHAVIOR-- adminCommentsSpamForm --
                        (new Capture(App::behavior()->callBehavior(...), ['adminCommentsSpamFormV2'])),
                    ])
                ->render();
            }

            App::backend()->comment_filter->display('admin.comments');

            // Show comments

            $form = (new Form('form-comments'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.comments'))
                ->fields([
                    (new Text(null, '%s')),
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Para())
                                ->class(['col', 'checkboxes-helpers']),
                            (new Para())
                                ->class(['col', 'right', 'form-buttons'])
                                ->items([
                                    (new Select('action'))
                                        ->items(App::backend()->comments_actions_page->getCombo())
                                        ->value(App::backend()->default_action)
                                        ->title(__('Actions'))
                                        ->label(new Label(__('Selected comments action:'), Label::IL_TF)),
                                    App::nonce()->formNonce(),
                                    (new Submit('do-action', __('ok'))),
                                    ...App::backend()->url()->hiddenFormFields('admin.comments', App::backend()->comment_filter->values(true)),
                                ]),
                        ]),
                ])
            ->render();

            App::backend()->comment_list->display(
                App::backend()->comment_filter->page,
                App::backend()->comment_filter->nb,
                $form,
                App::backend()->comment_filter->show(),
                (App::backend()->comment_filter->show() || (App::backend()->comment_filter->status == -2)),
                $show_ip
            );
        }

        Page::helpBlock('core_comments');
        Page::close();
    }
}
