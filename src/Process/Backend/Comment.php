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
use Dotclear\Database\MetaRecord;
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
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @since 2.27 Before as admin/comment.php
 */
class Comment
{
    use TraitProcess;

    protected static MetaRecord $rs;
    protected static bool $can_delete;
    protected static bool $can_publish;
    protected static int $comment_id;
    protected static string $comment_dt;
    protected static string $comment_author;
    protected static string $comment_email;
    protected static string $comment_site;
    protected static string $comment_content;
    protected static string $comment_ip;
    protected static int $comment_status;
    protected static int $post_id;
    protected static string $post_type;
    protected static string $post_title;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        self::$comment_id      = 0;
        self::$comment_dt      = '';
        self::$comment_author  = '';
        self::$comment_email   = '';
        self::$comment_site    = '';
        self::$comment_content = '';
        self::$comment_ip      = '';
        self::$comment_status  = 0;

        return self::status(true);
    }

    public static function process(): bool
    {
        // Post data helpers
        $_Int = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        $params  = [];
        $post_id = $_Int('post_id');

        if (!empty($_POST['add']) && $post_id !== 0) {
            // Adding comment (comming from post form, comments tab)

            try {
                self::$rs = App::blog()->getPosts(['post_id' => $post_id, 'post_type' => '']);

                if (self::$rs->isEmpty()) {
                    throw new Exception(__('Entry does not exist.'));
                }

                $cur = App::blog()->openCommentCursor();

                $cur->comment_author  = $_Str('comment_author');
                $cur->comment_email   = Html::clean($_Str('comment_email'));
                $cur->comment_site    = Html::clean($_Str('comment_site'));
                $cur->comment_content = App::filter()->HTMLfilter($_Str('comment_content'));
                $cur->post_id         = $post_id;

                # --BEHAVIOR-- adminBeforeCommentCreate -- Cursor
                App::behavior()->callBehavior('adminBeforeCommentCreate', $cur);

                self::$comment_id = App::blog()->addComment($cur);

                # --BEHAVIOR-- adminAfterCommentCreate -- Cursor, string|int
                App::behavior()->callBehavior('adminAfterCommentCreate', $cur, self::$comment_id);

                App::backend()->notices()->addSuccessNotice(__('Comment has been successfully created.'));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
            Http::redirect(App::postTypes()->get(self::$rs->strField('post_type'))->adminUrl(self::$rs->intField('post_id'), false, ['co' => 1]));
        }

        self::$post_id    = 0;
        self::$post_type  = '';
        self::$post_title = '';

        if (!empty($_REQUEST['id'])) {
            $params['comment_id'] = $_REQUEST['id'];

            try {
                self::$rs = App::blog()->getComments($params);
                if (!self::$rs->isEmpty()) {
                    self::$comment_id      = self::$rs->intField('comment_id');
                    self::$post_id         = self::$rs->intField('post_id');
                    self::$post_type       = self::$rs->strField('post_type');
                    self::$post_title      = self::$rs->strField('post_title');
                    self::$comment_dt      = self::$rs->strField('comment_dt');
                    self::$comment_author  = self::$rs->strField('comment_author');
                    self::$comment_email   = self::$rs->strField('comment_email');
                    self::$comment_site    = self::$rs->strField('comment_site');
                    self::$comment_content = self::$rs->strField('comment_content');
                    self::$comment_ip      = self::$rs->strField('comment_ip');
                    self::$comment_status  = self::$rs->intField('comment_status');
                } else {
                    App::backend()->notices()->addErrorNotice('This comment does not exist.');
                    App::backend()->url()->redirect('admin.comments');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (self::$comment_id === 0 && !App::error()->flag()) {
            App::error()->add(__('No comments'));
        }

        $can_edit = self::$can_delete = self::$can_publish = false;

        if (!App::error()->flag() && isset(self::$rs)) {
            $can_edit = self::$can_delete = self::$can_publish = App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id());

            if (!App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()) && App::auth()->userID() == self::$rs->user_id) {
                $can_edit = true;
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_DELETE,
                ]), App::blog()->id())) {
                    self::$can_delete = true;
                }
                if (App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_PUBLISH,
                ]), App::blog()->id())) {
                    self::$can_publish = true;
                }
            }

            if (!empty($_POST['update']) && $can_edit) {
                // update comment

                $cur = App::blog()->openCommentCursor();

                $cur->comment_author  = $_Str('comment_author');
                $cur->comment_email   = Html::clean($_Str('comment_email'));
                $cur->comment_site    = Html::clean($_Str('comment_site'));
                $cur->comment_content = App::filter()->HTMLfilter($_Str('comment_content'));

                if (isset($_POST['comment_status'])) {
                    $cur->comment_status = $_Int('comment_status');
                }

                try {
                    # --BEHAVIOR-- adminBeforeCommentUpdate -- Cursor
                    App::behavior()->callBehavior('adminBeforeCommentUpdate', $cur, self::$comment_id);

                    App::blog()->updComment(self::$comment_id, $cur);

                    # --BEHAVIOR-- adminAfterCommentUpdate -- Cursor, string|int
                    App::behavior()->callBehavior('adminAfterCommentUpdate', $cur, self::$comment_id);

                    App::backend()->notices()->addSuccessNotice(__('Comment has been successfully updated.'));
                    App::backend()->url()->redirect('admin.comment', ['id' => self::$comment_id]);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            if (!empty($_POST['delete']) && self::$can_delete) {
                // delete comment

                try {
                    # --BEHAVIOR-- adminBeforeCommentDelete -- string|int
                    App::behavior()->callBehavior('adminBeforeCommentDelete', self::$comment_id);

                    App::blog()->delComment(self::$comment_id);

                    App::backend()->notices()->addSuccessNotice(__('Comment has been successfully deleted.'));
                    Http::redirect(App::postTypes()->get(self::$rs->strField('post_type'))->adminUrl(self::$rs->intField('post_id'), false, ['co' => 1]));
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
        $show_ip = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());

        $editor         = App::auth()->prefs()->get('interface')->get('editor');
        $comment_editor = is_array($editor) && isset($editor['xhtml']) && is_string($editor['xhtml']) ? $editor['xhtml'] : '';

        $user_lang = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

        $breadcrumb = [
            Html::escapeHTML(App::blog()->name()) => '',
        ];

        if (App::postTypes()->exists(self::$post_type)) {
            $breadcrumb[Html::escapeHTML(__(App::postTypes()->get(self::$post_type)->get('label')))] = App::postTypes()->get(self::$post_type)->listAdminUrl();
        }

        $breadcrumb[Html::escapeHTML(self::$post_title)] = App::postTypes()->get(self::$post_type)->adminUrl(self::$post_id);
        $breadcrumb[__('Edit comment')]                  = '';

        $post_url = is_string($post_url = self::$rs->getPostURL()) ? $post_url : '';

        App::backend()->page()->open(
            __('Edit comment'),
            App::backend()->page()->jsConfirmClose('comment-form') .
            App::backend()->page()->jsLoad('js/_comment.js') .
            # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
            App::behavior()->callBehavior('adminPostEditor', $comment_editor, 'comment', ['#comment_content'], 'xhtml') .
            # --BEHAVIOR-- adminCommentHeaders --
            App::behavior()->callBehavior('adminCommentHeaders'),
            App::backend()->page()->breadcrumb($breadcrumb)
        );

        if (self::$comment_id !== 0) {
            if (!empty($_GET['upd'])) {
                App::backend()->notices()->success(__('Comment has been successfully updated.'));
            }

            echo (new Form('comment-form'))
                ->method('post')
                ->action(App::backend()->url()->get('admin.comment'))
                ->fields([
                    (new Fieldset())
                        ->legend(new Legend(__('Information collected')))
                        ->items([
                            $show_ip ?
                                (new Para())
                                    ->items([
                                        (new Text(null, __('IP address:') . ' ')),
                                        (new Link())
                                            ->href(App::backend()->url()->get('admin.comments', ['ip' => self::$comment_ip]))
                                            ->text(self::$comment_ip),
                                    ]) :
                                (new None()),
                            (new Para())
                                ->items([
                                    (new Text(null, __('Date:') . ' ' . Date::dt2str(__('%Y-%m-%d %H:%M'), self::$comment_dt))),
                                ]),
                        ]),
                    (new Text('h3', __('Comment submitted'))),
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Input('comment_author'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML(self::$comment_author))
                                ->required(true)
                                ->placeholder(__('Author'))
                                ->translate(false)
                                ->label((new Label(
                                    (new Span('*'))->render() . __('Author:'),
                                    Label::OL_TF
                                ))),
                        ]),
                    (new Para())
                        ->items([
                            (new Email('comment_email', Html::escapeHTML(self::$comment_email)))
                                ->size(30)
                                ->maxlength(255)
                                ->translate(false)
                                ->label(
                                    (new Label(__('Email:'), Label::OL_TF))
                                    ->suffix(self::$comment_email !== '' ?
                                        (new Link())
                                            ->href('mailto:' . Html::escapeHTML(self::$comment_email) . '?subject=' . rawurlencode(sprintf(__('Your comment on my blog %s'), App::blog()->name())) . '&amp;body=' . rawurlencode(sprintf(__("Hi!\n\nYou wrote a comment on:\n%s\n\n\n"), $post_url)))
                                            ->text(__('Send an e-mail'))
                                        ->render() :
                                        '')
                                ),

                        ]),
                    (new Para())
                        ->items([
                            (new Url('comment_site', Html::escapeHTML(self::$comment_site)))
                                ->size(30)
                                ->maxlength(255)
                                ->translate(false)
                                ->label(new Label(__('Web site:'), Label::OL_TF)),

                        ]),
                    (new Para())
                        ->items([
                            (new Select('comment_status'))
                                ->items(App::status()->comment()->combo())
                                ->default(self::$comment_status)
                                ->disabled(!self::$can_publish)
                                ->label(new Label(__('Status:'), Label::OL_TF)),
                        ]),
                    # --BEHAVIOR-- adminAfterCommentDesc -- MetaRecord
                    (new Text(null, App::behavior()->callBehavior('adminAfterCommentDesc', self::$rs))),
                    (new Para())
                        ->class('area')
                        ->items([
                            (new Textarea('comment_content', Html::escapeHTML(self::$comment_content)))
                                ->cols(50)
                                ->rows(10)
                                ->lang($user_lang)
                                ->spellcheck(true)
                                ->label(new Label(__('Comment:'), Label::OL_TF)),
                        ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Hidden('id', (string) self::$comment_id)),
                            App::nonce()->formNonce(),
                            (new Submit('update', __('Save')))
                                ->accesskey('s'),
                            (new Button('back', __('Back')))
                                ->class(['go-back', 'reset', 'hidden-if-no-js']),
                            self::$can_delete ?
                            (new Submit('delete', __('Delete')))
                                ->class('delete') :
                            (new None()),
                        ]),
                ])
            ->render();
        }

        App::backend()->page()->helpBlock('core_comments');
        App::backend()->page()->close();
    }
}
