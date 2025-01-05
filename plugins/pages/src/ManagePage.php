<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Datetime;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use UnhandledMatchError;

/**
 * @brief   The module backend manage page process.
 * @ingroup pages
 */
class ManagePage extends Process
{
    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['act'] ?? 'list') === 'page');
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $params = [];
        Page::check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(App::auth()->getInfo('user_tz') ?? 'UTC');

        App::backend()->post_id            = '';
        App::backend()->post_dt            = '';
        App::backend()->post_format        = App::auth()->getOption('post_format');
        App::backend()->post_editor        = App::auth()->getOption('editor');
        App::backend()->post_password      = '';
        App::backend()->post_url           = '';
        App::backend()->post_lang          = App::auth()->getInfo('user_lang');
        App::backend()->post_title         = '';
        App::backend()->post_excerpt       = '';
        App::backend()->post_excerpt_xhtml = '';
        App::backend()->post_content       = '';
        App::backend()->post_content_xhtml = '';
        App::backend()->post_notes         = '';
        App::backend()->post_status        = App::auth()->getInfo('user_post_status');
        App::backend()->post_position      = 0;
        App::backend()->post_open_comment  = false;
        App::backend()->post_open_tb       = false;
        App::backend()->post_selected      = false;

        App::backend()->post_media = [];

        App::backend()->page_title = __('New page');

        App::backend()->can_view_page = true;
        App::backend()->can_edit_page = App::auth()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_USAGE,
        ]), App::blog()->id());
        App::backend()->can_publish = App::auth()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        App::backend()->can_delete = false;

        $post_headlink = '<link rel="%s" title="%s" href="' . My::manageUrl(['act' => 'page', 'id' => '%s'], parametric: true) . '">';

        App::backend()->post_link = '<a href="' . My::manageUrl(['act' => 'page', 'id' => '%s'], parametric: true) . '" class="%s" title="%s">%s</a>';

        App::backend()->next_link = App::backend()->prev_link = App::backend()->next_headlink = App::backend()->prev_headlink = null;

        // If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::status()->post()->level('pending');
        }

        // Status combo
        App::backend()->status_combo = App::status()->post()->combo();

        // Formaters combo
        $core_formaters    = App::formater()->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }
        App::backend()->available_formats = $available_formats;

        // Languages combo
        App::backend()->lang_combo = Combos::getLangsCombo(
            App::blog()->getLangs(['order' => 'asc']),
            true
        );

        // Validation flag
        App::backend()->bad_dt = false;

        // Get page informations

        App::backend()->post = null;
        if (!empty($_REQUEST['id'])) {
            $params['post_type'] = 'page';
            $params['post_id']   = $_REQUEST['id'];

            App::backend()->post = App::blog()->getPosts($params);

            if (App::backend()->post->isEmpty()) {
                Notices::addErrorNotice(__('This page does not exist.'));
                My::redirect();
            } else {
                App::backend()->post_id            = (int) App::backend()->post->post_id;
                App::backend()->post_dt            = date('Y-m-d H:i', (int) strtotime(App::backend()->post->post_dt));
                App::backend()->post_format        = App::backend()->post->post_format;
                App::backend()->post_password      = App::backend()->post->post_password;
                App::backend()->post_url           = App::backend()->post->post_url;
                App::backend()->post_lang          = App::backend()->post->post_lang;
                App::backend()->post_title         = App::backend()->post->post_title;
                App::backend()->post_excerpt       = App::backend()->post->post_excerpt;
                App::backend()->post_excerpt_xhtml = App::backend()->post->post_excerpt_xhtml;
                App::backend()->post_content       = App::backend()->post->post_content;
                App::backend()->post_content_xhtml = App::backend()->post->post_content_xhtml;
                App::backend()->post_notes         = App::backend()->post->post_notes;
                App::backend()->post_status        = App::backend()->post->post_status;
                App::backend()->post_position      = (int) App::backend()->post->post_position;
                App::backend()->post_open_comment  = (bool) App::backend()->post->post_open_comment;
                App::backend()->post_open_tb       = (bool) App::backend()->post->post_open_tb;
                App::backend()->post_selected      = (bool) App::backend()->post->post_selected;

                App::backend()->page_title = __('Edit page');

                App::backend()->can_edit_page = App::backend()->post->isEditable();
                App::backend()->can_delete    = App::backend()->post->isDeletable();

                $next_rs = App::blog()->getNextPost(App::backend()->post, 1);
                $prev_rs = App::blog()->getNextPost(App::backend()->post, -1);

                if ($next_rs instanceof MetaRecord) {
                    App::backend()->next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->post_id,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next page') . '&nbsp;&#187;'
                    );
                    App::backend()->next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        $next_rs->post_id
                    );
                }

                if ($prev_rs instanceof MetaRecord) {
                    App::backend()->prev_link = sprintf(
                        App::backend()->post_link,
                        $prev_rs->post_id,
                        'prev',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        '&#171;&nbsp;' . __('Previous page')
                    );
                    App::backend()->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
                    );
                }

                try {
                    App::backend()->post_media = App::media()->getPostMedia(App::backend()->post_id);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        App::backend()->comments_actions_page = new BackendActionsComments(
            My::manageUrl([], '&'),
            [
                'act'           => 'page',
                'id'            => App::backend()->post_id,
                'action_anchor' => 'comments',
                'section'       => 'comments',
            ]
        );

        App::backend()->comments_actions_page_rendered = null;
        if (App::backend()->comments_actions_page->process()) {
            App::backend()->comments_actions_page_rendered = true;

            return true;
        }

        if ($_POST !== [] && App::backend()->can_edit_page) {
            // Format content

            App::backend()->post_format  = $_POST['post_format'];
            App::backend()->post_excerpt = $_POST['post_excerpt'];
            App::backend()->post_content = $_POST['post_content'];

            App::backend()->post_title = $_POST['post_title'];

            if (isset($_POST['post_status'])) {
                App::backend()->post_status = (int) $_POST['post_status'];
            }

            if (empty($_POST['post_dt'])) {
                App::backend()->post_dt = '';
            } else {
                try {
                    App::backend()->post_dt = strtotime((string) $_POST['post_dt']);
                    if (!App::backend()->post_dt || App::backend()->post_dt == -1) {
                        App::backend()->bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }
                    App::backend()->post_dt = date('Y-m-d H:i', App::backend()->post_dt);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            App::backend()->post_open_comment = !empty($_POST['post_open_comment']);
            App::backend()->post_open_tb      = !empty($_POST['post_open_tb']);
            App::backend()->post_selected     = !empty($_POST['post_selected']);
            App::backend()->post_lang         = $_POST['post_lang'];
            App::backend()->post_password     = empty($_POST['post_password']) ? null : $_POST['post_password'];
            App::backend()->post_position     = (int) $_POST['post_position'];

            App::backend()->post_notes = $_POST['post_notes'];

            if (isset($_POST['post_url'])) {
                App::backend()->post_url = $_POST['post_url'];
            }

            [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
            ] = [
                App::backend()->post_excerpt,
                App::backend()->post_excerpt_xhtml,
                App::backend()->post_content,
                App::backend()->post_content_xhtml,
            ];

            App::blog()->setPostContent(
                (int) App::backend()->post_id,
                App::backend()->post_format,
                App::backend()->post_lang,
                $post_excerpt,
                $post_excerpt_xhtml,
                $post_content,
                $post_content_xhtml
            );

            [
                App::backend()->post_excerpt,
                App::backend()->post_excerpt_xhtml,
                App::backend()->post_content,
                App::backend()->post_content_xhtml
            ] = [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml,
            ];
        }

        if (!empty($_POST['delete']) && App::backend()->can_delete) {
            // Delete page

            try {
                # --BEHAVIOR-- adminBeforePageDelete -- int
                App::behavior()->callBehavior('adminBeforePageDelete', App::backend()->post_id);
                App::blog()->delPost((int) App::backend()->post_id);
                My::redirect();
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ($_POST !== [] && !empty($_POST['save']) && App::backend()->can_edit_page && !App::backend()->bad_dt) {
            // Create or update page

            $cur = App::blog()->openPostCursor();

            // Magic tweak :)
            App::blog()->settings()->system->post_url_format = '{t}';

            $cur->post_type          = 'page';
            $cur->post_dt            = App::backend()->post_dt ? date('Y-m-d H:i:00', (int) strtotime((string) App::backend()->post_dt)) : '';
            $cur->post_format        = App::backend()->post_format;
            $cur->post_password      = App::backend()->post_password;
            $cur->post_lang          = App::backend()->post_lang;
            $cur->post_title         = App::backend()->post_title;
            $cur->post_excerpt       = App::backend()->post_excerpt;
            $cur->post_excerpt_xhtml = App::backend()->post_excerpt_xhtml;
            $cur->post_content       = App::backend()->post_content;
            $cur->post_content_xhtml = App::backend()->post_content_xhtml;
            $cur->post_notes         = App::backend()->post_notes;
            $cur->post_status        = App::backend()->post_status;
            $cur->post_position      = App::backend()->post_position;
            $cur->post_open_comment  = (int) App::backend()->post_open_comment;
            $cur->post_open_tb       = (int) App::backend()->post_open_tb;
            $cur->post_selected      = (int) App::backend()->post_selected;

            if (isset($_POST['post_url'])) {
                $cur->post_url = App::backend()->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (App::backend()->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePageUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminBeforePageUpdate', $cur, App::backend()->post_id);

                    App::blog()->updPost(App::backend()->post_id, $cur);

                    # --BEHAVIOR-- adminAfterPageUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageUpdate', $cur, App::backend()->post_id);

                    My::redirect(['act' => 'page', 'id' => App::backend()->post_id, 'upd' => '1']);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            } else {
                $cur->user_id = App::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePageCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforePageCreate', $cur);

                    $return_id = App::blog()->addPost($cur);

                    # --BEHAVIOR-- adminAfterPageCreate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPageCreate', $cur, $return_id);

                    My::redirect(['act' => 'page', 'id' => $return_id, 'crea' => '1']);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (App::backend()->comments_actions_page_rendered) {
            App::backend()->comments_actions_page->render();

            return;
        }

        App::backend()->default_tab = 'edit-entry';
        if (!App::backend()->can_edit_page) {
            App::backend()->default_tab = '';
        }
        if (!empty($_GET['co'])) {
            App::backend()->default_tab = 'comments';
        }

        $admin_post_behavior = '';
        if (App::backend()->post_editor) {
            $p_edit = $c_edit = '';
            if (!empty(App::backend()->post_editor[App::backend()->post_format])) {
                $p_edit = App::backend()->post_editor[App::backend()->post_format];
            }
            if (!empty(App::backend()->post_editor['xhtml'])) {
                $c_edit = App::backend()->post_editor['xhtml'];
            }
            if ($p_edit == $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    App::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content'],
                    App::backend()->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        Page::openModule(
            App::backend()->page_title . ' - ' . My::name(),
            Page::jsModal() .
            Page::jsJson('pages_page', ['confirm_delete_post' => __('Are you sure you want to delete this page?')]) .
            Page::jsLoad('js/_post.js') .
            My::jsLoad('page') .
            $admin_post_behavior .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPageHeaders --
            App::behavior()->callBehavior('adminPageHeaders') .
            Page::jsPageTabs(App::backend()->default_tab) .
            App::backend()->next_headlink . "\n" . App::backend()->prev_headlink
        );

        $img_status         = '';
        $img_status_pattern = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->class(['mark', 'mark-%3$s'])
            ->render();

        if (App::backend()->post_id) {
            try {
                $img_status = match ((int) App::backend()->post_status) {
                    App::status()->post()->level('published')   => sprintf($img_status_pattern, __('Published'), 'published.svg', 'published'),
                    App::status()->post()->level('unpublished') => sprintf($img_status_pattern, __('Unpublished'), 'unpublished.svg', 'unpublished'),
                    App::status()->post()->level('scheduled')   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.svg', 'scheduled'),
                    App::status()->post()->level('pending')     => sprintf($img_status_pattern, __('Pending'), 'pending.svg', 'pending'),
                };
            } catch (UnhandledMatchError) {
            }
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean(App::backend()->post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $edit_entry_title = App::backend()->page_title;
        }
        echo Page::breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => App::backend()->getPageURL(),
                $edit_entry_title                     => '',
            ]
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('Page has been successfully updated.'));
        } elseif (!empty($_GET['crea'])) {
            Notices::success(__('Page has been successfully created.'));
        } elseif (!empty($_GET['attached'])) {
            Notices::success(__('File has been successfully attached.'));
        } elseif (!empty($_GET['rmattach'])) {
            Notices::success(__('Attachment has been successfully removed.'));
        }

        # HTML conversion
        if (!empty($_GET['xconv'])) {
            App::backend()->post_excerpt = App::backend()->post_excerpt_xhtml;
            App::backend()->post_content = App::backend()->post_content_xhtml;
            App::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your HTML conversion by saving your post.'));
        }

        if (App::backend()->post_id && (int) App::backend()->post->post_status >= App::status()->post()->level('published')) {
            echo (new Para())
                ->items([
                    (new Link())
                        ->class(['onblog_link', 'outgoing'])
                        ->href(App::backend()->post->getURL())
                        ->title(Html::escapeHTML(trim(Html::clean(App::backend()->post_title))))
                        ->text(__('Go to this page on the site') . ' ' . (new Img('images/outgoing-link.svg'))->render()),
                ])
            ->render();
        }

        if (App::backend()->post_id) {
            $items = [];
            if (App::backend()->prev_link) {
                $items[] = new Text(null, App::backend()->prev_link);
            }
            if (App::backend()->next_link) {
                $items[] = new Text(null, App::backend()->next_link);
            }

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            $items[] = new Capture(App::behavior()->callBehavior(...), ['adminPageNavLinks', App::backend()->post ?? null]);

            echo (new Para())
                ->class('nav_prevnext')
                ->items($items)
            ->render();
        }

        # Exit if we cannot view page
        if (!App::backend()->can_view_page) {
            Page::closeModule();

            return;
        }

        /* Post form if we can edit page
        -------------------------------------------------------- */
        if (App::backend()->can_edit_page) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => (new Para())->class('entry-status')->items([
                            (new Select('post_status'))
                                ->items(App::backend()->status_combo)
                                ->default(App::backend()->post_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Page status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_dt' => (new Para())->items([
                            (new Datetime('post_dt'))
                                ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(App::backend()->post_dt))))
                                ->class(App::backend()->bad_dt ? 'invalid' : [])
                                ->label(new Label(__('Publication date and hour'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_lang' => (new Para())->items([
                            (new Select('post_lang'))
                                ->items(App::backend()->lang_combo)
                                ->default(App::backend()->post_lang)
                                ->label(new Label(__('Page language'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_format' => (new Para())->items([
                            (new Select('post_format'))
                                ->items(App::backend()->available_formats)
                                ->default(App::backend()->post_format)
                                ->label((new Label(__('Text formatting'), Label::OUTSIDE_LABEL_BEFORE))->id('label_format')),
                            (new Div(null, 'span'))->class(['format_control', 'control_no_xhtml'])->items([
                                (new Link('convert-xhtml'))
                                    ->class(['button', App::backend()->post_id && App::backend()->post_format != 'wiki' ? ' hide' : ''])
                                    ->href(My::manageUrl(['act' => 'page', 'id' => App::backend()->post_id, 'xconv' => '1']))
                                    ->text(__('Convert to HTML')),
                            ]),
                        ])
                        ->render(),
                    ],
                ],

                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_position' => (new Para())->items([
                            (new Number('post_position'))
                                ->value(App::backend()->post_position)
                                ->min(0)
                                ->label(new Label(__('Page position'), Label::INSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),
                    ],
                ],

                'options-box' => [
                    'title' => __('Options'),
                    'items' => [
                        'post_open_comment_tb' => (new Div())->items([
                            (new Text('h5'))->id('label_comment_tb')->text(__('Comments and trackbacks list')),
                            (new Para())->items([
                                (new Checkbox('post_open_comment', App::backend()->post_open_comment))
                                    ->value(1)
                                    ->label((new Label(__('Accept comments'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->system->allow_comments ?
                                (
                                    self::isContributionAllowed(App::backend()->post_id, strtotime(App::backend()->post_dt), true) ?
                                    (new None())
                                    :
                                    (new Note())
                                        ->class(['form-note', 'warn'])
                                        ->text(__('Warning: Comments are not more accepted for this entry.'))
                                ) :
                                (new Note())
                                    ->class(['form-note', 'warn'])
                                    ->text(__('Comments are not accepted on this blog so far.')),
                            (new Para())->items([
                                (new Checkbox('post_open_tb', App::backend()->post_open_tb))
                                    ->value(1)
                                    ->label((new Label(__('Accept trackbacks'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->system->allow_trackbacks ?
                                (
                                    self::isContributionAllowed(App::backend()->post_id, strtotime(App::backend()->post_dt), true) ?
                                    (new None())
                                    :
                                    (new Note())
                                        ->class(['form-note', 'warn'])
                                        ->text(__('Warning: Trackbacks are not more accepted for this entry.'))
                                ) :
                                (new Note())
                                    ->class(['form-note', 'warn'])
                                    ->text(__('Trackbacks are not accepted on this blog so far.')),
                        ])
                        ->render(),

                        'post_hide' => (new Para())->items([
                            (new Checkbox('post_selected', App::backend()->post_selected))
                                ->value(1)
                                ->label((new Label(__('Hide in widget Pages'), Label::INSIDE_TEXT_AFTER))),
                        ])
                        ->render(),

                        'post_password' => (new Para())->items([
                            (new Password('post_password'))
                                ->class('maximal')
                                ->size(10)
                                ->maxlength(32)
                                ->label((new Label(__('Password'), Label::OUTSIDE_TEXT_BEFORE))),
                        ])
                        ->render(),

                        'post_url' => (new Div())->class('lockable')->items([
                            (new Para())->items([
                                (new Input('post_url'))
                                    ->class('maximal')
                                    ->value(Html::escapeHTML(App::backend()->post_url))
                                    ->size(10)
                                    ->maxlength(255)
                                    ->label((new Label(__('Edit basename'), Label::OUTSIDE_TEXT_BEFORE))),
                            ]),
                            (new Note())
                                ->class(['form-note', 'warn'])
                                ->text(__('Warning: If you set the URL manually, it may conflict with another page.')),
                        ])
                        ->render(),
                    ],
                ],
            ]);

            $main_items = new ArrayObject(
                [
                    'post_title' => (new Para())->items([
                        (new Input('post_title'))
                            ->value(Html::escapeHTML(App::backend()->post_title))
                            ->size(20)
                            ->maxlength(255)
                            ->required(true)
                            ->class('maximal')
                            ->placeholder(__('Title'))
                            ->lang(App::backend()->post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    (new Text('span', '*'))->render() . __('Title:'),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class(['required', 'no-margin', 'bold'])
                            )
                            ->title(__('Required field')),
                    ])
                    ->render(),

                    'post_excerpt' => (new Para())->class('area')->id('excerpt-area')->items([
                        (new Textarea('post_excerpt'))
                            ->value(Html::escapeHTML(App::backend()->post_excerpt))
                            ->cols(50)
                            ->rows(5)
                            ->lang(App::backend()->post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    __('Excerpt:') . ' ' . (new Text('span', __('Introduction to the page.')))->class('form-note')->render(),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class('bold')
                            ),
                    ])
                    ->render(),

                    'post_content' => (new Para())->class('area')->id('content-area')->items([
                        (new Textarea('post_content'))
                            ->value(Html::escapeHTML(App::backend()->post_content))
                            ->cols(50)
                            ->rows(App::auth()->getOption('edit_size'))
                            ->required(true)
                            ->lang(App::backend()->post_lang)
                            ->spellcheck(true)
                            ->placeholder(__('Content'))
                            ->label(
                                (new Label(
                                    (new Text('span', '*'))->render() . __('Content:'),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class(['required', 'bold'])
                            ),
                    ])
                    ->render(),

                    'post_notes' => (new Para())->class('area')->id('notes-area')->items([
                        (new Textarea('post_notes'))
                            ->value(Html::escapeHTML(App::backend()->post_excerpt))
                            ->cols(50)
                            ->rows(5)
                            ->lang(App::backend()->post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    __('Personal notes:') . ' ' . (new Text('span', __('Unpublished notes.')))->class('form-note')->render(),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class('bold')
                            ),
                    ])
                    ->render(),
                ]
            );

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null
            App::behavior()->callBehavior('adminPageFormItems', $main_items, $sidebar_items, App::backend()->post ?? null);

            // Prepare main and side parts
            $side_part_items = [];
            foreach ($sidebar_items as $id => $c) {
                $side_part_items[] = (new Div())
                    ->id($id)
                    ->class('sb-box')
                    ->items([
                        (new Text('h4', $c['title'])),
                        (new Text('', implode('', $c['items']))),
                    ])
                    ->render();
            }
            $side_part = implode('', $side_part_items);
            $main_part = implode('', iterator_to_array($main_items));

            // Prepare buttons
            $buttons   = [];
            $buttons[] = (new Submit(['save'], __('Save') . ' (s)'))
                ->accesskey('s');
            if (App::backend()->post_id) {
                $preview_url = App::blog()->url() .
                    App::url()->getURLFor(
                        'pagespreview',
                        App::auth()->userID() . '/' .
                        Http::browserUID(App::config()->masterKey() . App::auth()->userID() . App::auth()->cryptLegacy((string) App::auth()->userID())) .
                        '/' . App::backend()->post->post_url
                    );

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->interface->blank_preview;

                $preview_class  = $blank_preview ? '' : 'modal';
                $preview_target = $blank_preview ? '' : 'target="_blank"';

                $buttons[] = (new Link('post-preview'))
                    ->href($preview_url)
                    ->extra($preview_target)
                    ->class(['button', $preview_class])
                    ->accesskey('p')
                    ->text(__('Preview') . ' (p)');
                $buttons[] = (new Button(['back'], __('Back')))->class(['go-back','reset','hidden-if-no-js']);
            } else {
                $buttons[] = (new Link('post-cancel'))
                    ->href(My::manageUrl(['act' => 'list']))
                    ->class('button')
                    ->accesskey('c')
                    ->text(__('Cancel') . ' (c)');
            }

            if (App::backend()->can_delete) {
                $buttons[] = (new Submit(['delete'], __('Delete')))
                    ->class('delete');
            }
            if (App::backend()->post_id) {
                $buttons[] = (new Hidden('id', (string) App::backend()->post_id));
            }

            $format = (new Text(
                'span',
                ' &rsaquo; ' . App::formater()->getFormaterName(App::backend()->post_format) . ''
            ));
            $title = (App::backend()->post_id ? __('Edit page') : __('New page')) . $format->render();

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->id('edit-entry')
                ->items([
                    (new Form('entry-form'))
                        ->method('post')
                        ->action(My::manageUrl(['act' => 'page']))
                        ->fields([
                            (new Div())
                                ->id('entry-wrapper')
                                ->items([
                                    (new Div())
                                        ->id('entry-content')
                                        ->items([
                                            (new Div())
                                                ->class('constrained')
                                                ->items([
                                                    (new Text('h3', __('Edit page')))
                                                        ->class('out-of-screen-if-js'),
                                                    (new Note())
                                                        ->class('form-note')
                                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                                                    (new Text(null, $main_part)),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageForm', App::backend()->post ?? null])),
                                                    (new Para())
                                                        ->class(['border-top', 'form-buttons'])
                                                        ->items([
                                                            ...My::hiddenFields(),
                                                            ...$buttons,
                                                        ]),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterButtons', App::backend()->post ?? null])),
                                                ]),
                                        ]),
                                ]),
                            (new Div())
                                ->id('entry-sidebar')
                                ->extra('role="complementary"')
                                ->items([
                                    (new Text(null, $side_part)),
                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageFormSidebar', App::backend()->post ?? null])),
                                ]),
                        ]),
                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterForm', App::backend()->post ?? null])),
                ])
            ->render();

            // Attachment removing form
            if (App::backend()->post_id && !empty(App::backend()->post_media)) {
                echo (new Form('attachment-remove-hide'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.post.media'))
                    ->fields([
                        App::nonce()->formNonce(),
                        (new Hidden(['post_id'], (string) App::backend()->post_id)),
                        (new Hidden(['media_id'], '')),
                        (new Hidden(['remove'], '1')),
                    ])
                ->render();
            }
        }

        if (App::backend()->post_id) {
            // Comments and trackbacks

            $params = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments   = App::blog()->getComments([...$params, 'comment_trackback' => 0]);
            $trackbacks = App::blog()->getComments([...$params, 'comment_trackback' => 1]);

            # Actions combo box
            $combo_action = App::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());

            // Prepare form
            $fields = [];

            $fields[] = (new Text('h3', __('Trackbacks')));
            if (!$trackbacks->isEmpty()) {
                $fields[] = (new Text(null, self::showComments($trackbacks, $has_action)));
            } else {
                $fields[] = (new Note())->text(__('No trackback'));
            }

            $fields[] = (new Text('h3', __('Comments')));
            if (!$comments->isEmpty()) {
                $fields[] = (new Text(null, self::showComments($comments, $has_action)));
            } else {
                $fields[] = (new Note())->text(__('No comments'));
            }

            if ($has_action) {
                $fields[] = (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Para())->class(['col', 'checkboxes-helpers']),
                        (new Para())->class(['col', 'right', 'form-buttons'])->items([
                            (new Select('action'))
                                ->items($combo_action)
                                ->label(new Label(__('Selected comments action:'), Label::OL_TF)),
                            ...My::hiddenFields([
                                'act'     => 'page',
                                'id'      => App::backend()->post_id,
                                'co'      => '1',
                                'section' => 'comments',
                                'redir'   => My::manageUrl([
                                    'act' => 'page',
                                    'id'  => App::backend()->post_id,
                                    'co'  => '1',
                                ]),
                            ]),
                            (new Submit('do-action-comm', __('Ok'))),
                        ]),
                    ]);
            }

            echo (new Div())
                ->id('comments')
                ->class('multi-part')
                ->title(__('Comments'))
                ->items([
                    (new Para())
                        ->class('top-add')
                        ->items([
                            (new Link())->class(['button', 'add'])->href('#comment-form')->text(__('Add a comment')),
                        ]),
                    $has_action ?
                    (new Form('comments-action'))
                        ->method('post')
                        ->action(My::manageUrl())
                        ->fields($fields) :
                    (new Set())
                        ->items($fields),
                    //Add a comment
                    (new Form('comment-form'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.comment'))
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('Add a comment')))
                                ->fields([
                                    (new Note())
                                        ->class('form-note')
                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                                    (new Div())
                                        ->class('constrained')
                                        ->items([
                                            (new Para())
                                                ->items([
                                                    (new Input('comment_author'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML(App::auth()->getInfo('user_cn')))
                                                        ->required(true)
                                                        ->placeholder(__('Author'))
                                                        ->label((new Label(
                                                            (new Text('span', '*'))->render() . __('Name:'),
                                                            Label::OUTSIDE_TEXT_BEFORE
                                                        ))->class('required')),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Email('comment_email'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML(App::auth()->getInfo('user_email')))
                                                        ->autocomplete('email')
                                                        ->label(new Label(__('Email:'), Label::OUTSIDE_TEXT_BEFORE)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Url('comment_site'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML(App::auth()->getInfo('user_url')))
                                                        ->autocomplete('url')
                                                        ->label(new Label(__('Web site:'), Label::OUTSIDE_TEXT_BEFORE)),
                                                ]),
                                            (new Para())
                                                ->class('area')
                                                ->items([
                                                    (new Textarea('comment_content'))
                                                        ->cols(50)
                                                        ->rows(8)
                                                        ->lang(App::auth()->getInfo('user_lang'))
                                                        ->spellcheck(true)
                                                        ->placeholder(__('Comment'))
                                                        ->required(true)
                                                        ->label((new Label(
                                                            (new Text('span', '*'))->render() . __('Comment'),
                                                            Label::OUTSIDE_TEXT_BEFORE
                                                        ))->class('required')),
                                                ]),
                                            (new Para())
                                                ->class('form-buttons')
                                                ->items([
                                                    App::nonce()->formNonce(),
                                                    (new Hidden('post_id', (string) App::backend()->post_id)),
                                                    (new Submit(['add'], __('Save'))),
                                                ]),
                                        ]),
                                ]),
                        ]),
                ])
            ->render();
        }

        Page::helpBlock('page', 'core_wiki');

        Page::closeModule();
    }

    # Controls comments or trakbacks capabilities

    /**
     * Determines if contribution is allowed.
     *
     * @param   mixed   $id     The identifier
     * @param   mixed   $dt     The date
     * @param   bool    $com    It is comment?
     *
     * @return  bool    True if contribution allowed, False otherwise.
     */
    protected static function isContributionAllowed($id, $dt, bool $com = true): bool
    {
        if (!$id) {
            return true;
        }
        if ($com) {
            if ((App::blog()->settings()->system->comments_ttl == 0) || (time() - App::blog()->settings()->system->comments_ttl * 86400 < $dt)) {
                return true;
            }
        } elseif ((App::blog()->settings()->system->trackbacks_ttl == 0) || (time() - App::blog()->settings()->system->trackbacks_ttl * 86400 < $dt)) {
            return true;
        }

        return false;
    }

    /**
     * Shows the comments or trackbacks.
     *
     * @param   mixed   $rs             Recordset
     * @param   bool    $has_action     Indicates if action is available
     */
    protected static function showComments($rs, bool $has_action): string
    {
        // IP are available only for super-admin and admin
        $show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id()
        );

        $img_status_pattern = (new Img('images/%2$s'))
            ->alt('%1$s')
            ->class(['mark', 'mark-%3$s'])
            ->render();

        $rows = [];
        while ($rs->fetch()) {
            $cols        = [];
            $comment_url = App::backend()->url()->get('admin.comment', ['id' => $rs->comment_id]);

            $sts_class = '';
            switch ((int) $rs->comment_status) {
                case App::status()->comment()->level('published'):
                    $img_status = sprintf($img_status_pattern, __('Published'), 'published.svg', 'published');
                    $sts_class  = 'sts-online';

                    break;
                case App::status()->comment()->level('unpublished'):
                    $img_status = sprintf($img_status_pattern, __('Unpublished'), 'unpublished.svg', 'unpublished');
                    $sts_class  = 'sts-offline';

                    break;
                case App::status()->comment()->level('pending'):
                    $img_status = sprintf($img_status_pattern, __('Pending'), 'pending.svg', 'pending');
                    $sts_class  = 'sts-pending';

                    break;
                case App::status()->comment()->level('junk'):
                    $img_status = sprintf($img_status_pattern, __('Junk'), 'junk.svg', 'junk light-only') . sprintf($img_status_pattern, __('Junk'), 'junk-dark.svg', 'junk dark-only');
                    $sts_class  = 'sts-junk';

                    break;
                default:
                    $img_status = '';

                    break;
            }

            $cols[] = (new Td())
                ->class('nowrap')
                ->items([
                    $has_action ?
                    (new Checkbox(['comments[]']))
                        ->value($rs->comment_id)
                        ->title(__('Select this comment')) :
                    (new None()),
                ]);

            $cols[] = (new Td())
                ->class('maximal')
                ->text($rs->comment_author);

            $cols[] = (new Td())
                ->class('nowrap')
                ->text(Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt));

            if ($show_ip) {
                $cols[] = (new Td())
                    ->class('nowrap')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.comment', ['ip' => $rs->comment_ip]))
                            ->text($rs->comment_ip),
                    ]);
            }

            $cols[] = (new Td())
                ->class(['nowrap', 'status'])
                ->text($img_status);

            $cols[] = (new Td())
                ->class(['nowrap', 'status'])
                ->items([
                    (new Link())
                        ->href($comment_url)
                        ->title(__('Edit this comment'))
                        ->items([
                            (new Img('images/edit.svg'))->class(['mark', 'mark-edit', 'light-only'])->alt(''),
                            (new Img('images/edit-dark.svg'))->class(['mark', 'mark-edit', 'dark-only'])->alt(''),
                            (new Text(null, ' ' . __('Edit'))),
                        ]),
                ]);

            $rows[] = (new Tr())
                ->class(array_filter(['line', $rs->comment_status <= App::status()->comment()->level('unpublished') ? 'offline ' : '', $sts_class]))
                ->id('c' . $rs->comment_id)
                ->cols($cols);
        }

        $cols   = [];
        $cols[] = (new Th())
            ->class(['nowrap', 'first'])
            ->colspan(2)
            ->text(__('Author'));
        $cols[] = (new Th())
            ->text(__('Date'));
        if ($show_ip) {
            $cols[] = (new Th())
                ->class('nowrap')
                ->text(__('IP address'));
        }
        $cols[] = (new Th())
            ->text(__('Status'));
        $cols[] = (new Th())
            ->text(__('Edit'));

        return (new Table())
            ->class('comments-list')
            ->thead((new Thead())->rows([(new Tr())->cols($cols)]))
            ->tbody((new Tbody())->rows($rows))
        ->render();
    }
}
