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
use Dotclear\Core\Backend\Listing\ListingComments;
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
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/comments.php
 */
class Comments
{
    use TraitProcess;

    protected static ActionsComments $comments_actions_page;

    protected static string $default_action;

    protected static ListingComments $comment_list;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        if (!empty($_POST['delete_all_spam'])) {
            // Remove all spams

            try {
                App::blog()->delJunkComments();
                App::session()->set('comments_del_spam', true);
                App::backend()->url()->redirect('admin.comments');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Filters

        App::backend()->comment_filter = App::backend()->filter()->comments(); // Backward compatibility

        // get list params
        $params = App::backend()->filter()->comments()->params();

        // lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title'          => 'post_title',
            'comment_author'      => 'comment_author',
            'comment_spam_filter' => 'comment_spam_filter',
        ];

        # --BEHAVIOR-- adminCommentsSortbyLexCombo -- array<int,array<string,string>>
        App::behavior()->callBehavior('adminCommentsSortbyLexCombo', [&$sortby_lex]);

        $sortby = is_string($sortby = App::backend()->filter()->comments()->sortby) ? $sortby : '';
        $order  = is_string($order = App::backend()->filter()->comments()->order) ? $order : '';

        $params['order'] = (array_key_exists($sortby, $sortby_lex) ? App::db()->con()->lexFields($sortby_lex[$sortby]) : $sortby) . ' ' . $order;

        // default filter ? do not display spam
        if (!App::backend()->filter()->comments()->show() && App::backend()->filter()->comments()->status == '') {
            $params['comment_status_not'] = App::status()->comment()::JUNK;
        }
        $params['no_content'] = true;

        // Actions

        self::$default_action = '';
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_DELETE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id()) && App::backend()->filter()->comments()->status == -2) {
            self::$default_action = 'delete';
        }

        self::$comments_actions_page = App::backend()->action()->comments(App::backend()->url()->get('admin.comments'));

        if (self::$comments_actions_page->process()) {
            return self::status(false);
        }

        // List
        try {
            $comments = App::blog()->getComments($params);
            $counter  = App::blog()->getComments($params, true);

            self::$comment_list = App::backend()->listing()->comments($comments, $counter->cardinal());
        } catch (Exception $exception) {
            App::error()->add($exception->getMessage());
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

        App::backend()->page()->open(
            __('Comments and trackbacks'),
            App::backend()->page()->jsLoad('js/_comments.js') . App::backend()->filter()->comments()->js(App::backend()->url()->get('admin.comments')),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Comments and trackbacks')         => '',
                ]
            )
        );
        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('Selected comments have been successfully updated.'));
        } elseif (!empty($_GET['del'])) {
            App::backend()->notices()->success(__('Selected comments have been successfully deleted.'));
        }

        if (!App::error()->flag()) {
            if (App::session()->get('comments_del_spam') != '') {
                App::backend()->notices()->message(__('Spam comments have been successfully deleted.'));
                App::session()->unset('comments_del_spam');
            }

            $spam_count = App::blog()->getComments(['comment_status' => App::status()->comment()::JUNK], true)->cardinal();
            if ($spam_count > 0) {
                if (!App::backend()->filter()->comments()->show() || (App::backend()->filter()->comments()->status != -2)) {
                    if ($spam_count === 1) {
                        $count = (new Para())
                            ->class('form-buttons')
                            ->items([
                                new Text(null, sprintf(__('You have one spam comment.'), '<strong>' . $spam_count . '</strong>')),
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => -2]))
                                    ->text(__('Show it.')),
                            ]);
                    } else {
                        $count = (new Para())
                            ->class('form-buttons')
                            ->items([
                                new Text(null, sprintf(__('You have %s spam comments.'), '<strong>' . $spam_count . '</strong>')),
                                (new Link())
                                    ->href(App::backend()->url()->get('admin.comments', ['status' => -2]))
                                    ->text(__('Show them.')),
                            ]);
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

            App::backend()->filter()->comments()->display('admin.comments');

            // Show comments
            $page = is_numeric($page = App::backend()->filter()->comments()->page) ? (int) $page : 0;
            $nb   = is_numeric($nb = App::backend()->filter()->comments()->nb) ? (int) $nb : 30;

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
                                        ->items(self::$comments_actions_page->getCombo())
                                        ->value(self::$default_action)
                                        ->title(__('Actions'))
                                        ->label(new Label(__('Selected comments action:'), Label::IL_TF)),
                                    App::nonce()->formNonce(),
                                    (new Submit('do-action', __('ok'))),
                                    ...App::backend()->url()->hiddenFormFields('admin.comments', App::backend()->filter()->comments()->values(true)),
                                ]),
                        ]),
                ])
            ->render();

            self::$comment_list->display(
                $page,
                $nb,
                $form,
                App::backend()->filter()->comments()->show(),
                (App::backend()->filter()->comments()->show() || (App::backend()->filter()->comments()->status == -2)),
                $show_ip
            );
        }

        App::backend()->page()->helpBlock('core_comments');
        App::backend()->page()->close();
    }
}
