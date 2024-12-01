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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

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
                } else {
                    Notices::addErrorNotice('This comment does not exist.');
                    App::backend()->url()->redirect('admin.comments');
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

            echo (new Form('comment-form'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.comment'))
                ->fields([
                    (new Fieldset())
                        ->legend(new Legend(__('Information collected')))
                        ->items([
                            App::backend()->show_ip ?
                                (new Para())
                                    ->items([
                                        (new Text(null, __('IP address:') . ' ')),
                                        (new Link())
                                            ->href(App::backend()->url()->get('admin.comments', ['ip' => App::backend()->comment_ip]))
                                            ->text(App::backend()->comment_ip),
                                    ]) :
                                (new None()),
                            (new Para())
                                ->items([
                                    (new Text(null, __('Date:') . ' ' . Date::dt2str(__('%Y-%m-%d %H:%M'), App::backend()->comment_dt))),
                                ]),
                        ]),
                    (new Text('h3', __('Comment submitted'))),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Input('comment_author'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML(App::backend()->comment_author))
                                ->required(true)
                                ->placeholder(__('Author'))
                                ->label((new Label(
                                    (new Text('span', '*'))->render() . __('Author:'),
                                    Label::OL_TF
                                ))),
                        ]),
                    (new Para())
                        ->items([
                            (new Email('comment_email', Html::escapeHTML(App::backend()->comment_email)))
                                ->size(30)
                                ->maxlength(255)
                                ->label(
                                    (new Label(__('Email:'), Label::OL_TF))
                                    ->suffix(App::backend()->comment_email ?
                                        (new Link())
                                            ->href('mailto:' . Html::escapeHTML(App::backend()->comment_email) . '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), App::blog()->name())) . '&amp;body=' . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), App::backend()->rs->getPostURL())))
                                            ->text(__('Send an e-mail'))
                                        ->render() :
                                        '')
                                ),

                        ]),
                    (new Para())
                        ->items([
                            (new Url('comment_site', Html::escapeHTML(App::backend()->comment_site)))
                                ->size(30)
                                ->maxlength(255)
                                ->label(new Label(__('Web site:'), Label::OL_TF)),

                        ]),
                    (new Para())
                        ->items([
                            (new Select('comment_status'))
                                ->items(App::backend()->status_combo)
                                ->default(App::backend()->comment_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Status:'), Label::OL_TF)),
                        ]),
                    # --BEHAVIOR-- adminAfterCommentDesc -- MetaRecord
                    (new Text(null, App::behavior()->callBehavior('adminAfterCommentDesc', App::backend()->rs))),
                    (new Para())
                        ->class('area')
                        ->items([
                            (new Textarea('comment_content', Html::escapeHTML(App::backend()->comment_content)))
                                ->cols(50)
                                ->rows(10)
                                ->lang(App::auth()->getInfo('user_lang'))
                                ->spellcheck(true)
                                ->label(new Label(__('Comment:'), Label::OL_TF)),
                        ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Hidden('id', App::backend()->comment_id)),
                            App::nonce()->formNonce(),
                            (new Submit('update', __('Save')))
                                ->accesskey('s'),
                            (new Button('back', __('Back')))
                                ->class(['go-back', 'reset', 'hidden-if-no-js']),
                            App::backend()->can_delete ?
                            (new Submit('delete', __('Delete')))
                                ->class('delete') :
                            (new None()),
                        ]),
                ])
            ->render();
        }

        Page::helpBlock('core_comments');
        Page::close();
    }
}
