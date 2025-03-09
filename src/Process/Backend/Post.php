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

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsComments;
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
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
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
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Txt;
use Exception;

/**
 * @since 2.27 Before as admin/post.php
 *
 * @todo switch Helper/Html/Form/...
 */
class Post extends Process
{
    public static function init(): bool
    {
        $params = [];
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(App::auth()->getInfo('user_tz') ?? 'UTC');

        App::backend()->post_id            = '';
        App::backend()->cat_id             = '';
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
        App::backend()->post_selected      = false;
        App::backend()->post_open_comment  = App::blog()->settings()->system->allow_comments;
        App::backend()->post_open_tb       = App::blog()->settings()->system->allow_trackbacks;

        App::backend()->page_title = __('New post');

        App::backend()->can_view_page = true;
        App::backend()->can_edit_post = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        App::backend()->can_publish = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        App::backend()->can_delete = false;

        $post_headlink            = '<link rel="%s" title="%s" href="' . App::backend()->url()->get('admin.post', ['id' => '%s'], '&amp;', true) . '">';
        App::backend()->post_link = '<a href="' . App::backend()->url()->get('admin.post', ['id' => '%s'], '&amp;', true) . '" class="%s" title="%s">%s</a>';

        App::backend()->next_link     = null;
        App::backend()->prev_link     = null;
        App::backend()->next_headlink = null;
        App::backend()->prev_headlink = null;

        # If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::status()->post()::PENDING;
        }

        # Getting categories
        App::backend()->categories_combo = Combos::getCategoriesCombo(
            App::blog()->getCategories()
        );

        App::backend()->status_combo = App::status()->post()->combo();

        // Formats combo
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
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true
        );

        // Validation flag
        App::backend()->bad_dt = false;

        // Trackbacks
        App::backend()->tb      = App::trackback();
        App::backend()->tb_urls = App::backend()->tb_excerpt = '';

        // Get entry informations

        App::backend()->post = null;

        if (!empty($_REQUEST['id'])) {
            App::backend()->page_title = __('Edit post');

            $params['post_id'] = $_REQUEST['id'];

            App::backend()->post = App::blog()->getPosts($params);

            if (App::backend()->post->isEmpty()) {
                Notices::addErrorNotice('This entry does not exist.');
                App::backend()->url()->redirect('admin.posts');
            } else {
                App::backend()->post_id            = App::backend()->post->post_id;
                App::backend()->cat_id             = App::backend()->post->cat_id;
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
                App::backend()->post_selected      = (bool) App::backend()->post->post_selected;
                App::backend()->post_open_comment  = (bool) App::backend()->post->post_open_comment;
                App::backend()->post_open_tb       = (bool) App::backend()->post->post_open_tb;

                App::backend()->can_edit_post = App::backend()->post->isEditable();
                App::backend()->can_delete    = App::backend()->post->isDeletable();

                $next_rs = App::blog()->getNextPost(App::backend()->post, 1);
                $prev_rs = App::blog()->getNextPost(App::backend()->post, -1);

                if ($next_rs instanceof MetaRecord) {
                    App::backend()->next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->post_id,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next entry') . '&nbsp;&#187;'
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
                        '&#171;&nbsp;' . __('Previous entry')
                    );
                    App::backend()->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
                    );
                }

                // Sanitize trackbacks excerpt
                $buffer = empty($_POST['tb_excerpt']) ?
                    App::backend()->post_excerpt_xhtml . ' ' . App::backend()->post_content_xhtml :
                    $_POST['tb_excerpt'];
                $buffer = preg_replace(
                    '/\s+/ms',
                    ' ',
                    Txt::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean($buffer))), 255)
                );
                App::backend()->tb_excerpt = $buffer;
            }
        }
        $anchor = isset($_REQUEST['section']) && $_REQUEST['section'] == 'trackbacks' ? 'trackbacks' : 'comments';

        App::backend()->comments_actions_page = new ActionsComments(
            App::backend()->url()->get('admin.post'),
            [
                'id'            => App::backend()->post_id,
                'action_anchor' => $anchor,
                'section'       => $anchor,
            ]
        );

        if (App::backend()->comments_actions_page->process()) {
            return self::status(false);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_POST['ping'])) {
            // Ping blogs

            if (!empty($_POST['tb_urls']) && App::backend()->post_id && !App::status()->post()->isRestricted((int) App::backend()->post_status) && App::backend()->can_edit_post) {
                App::backend()->tb_urls = $_POST['tb_urls'];
                App::backend()->tb_urls = (string) str_replace("\r", '', App::backend()->tb_urls);  // @phpstan-ignore-line

                $tb_post_title = Html::escapeHTML(trim(Html::clean(App::backend()->post_title)));
                $tb_post_url   = App::backend()->post->getURL();

                foreach (explode("\n", App::backend()->tb_urls) as $tb_url) {
                    try {
                        # --BEHAVIOR-- adminBeforePingTrackback -- string, string, string, string, string
                        App::behavior()->callBehavior(
                            'adminBeforePingTrackback',
                            $tb_url,
                            App::backend()->post_id,
                            $tb_post_title,
                            App::backend()->tb_excerpt,
                            $tb_post_url
                        );

                        App::backend()->tb->ping(
                            $tb_url,
                            (int) App::backend()->post_id,
                            $tb_post_title,
                            App::backend()->tb_excerpt,
                            $tb_post_url
                        );
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('All pings sent.'));
                    App::backend()->url()->redirect(
                        'admin.post',
                        ['id' => App::backend()->post_id, 'tb' => '1']
                    );
                }
            }
        } elseif ($_POST !== [] && App::backend()->can_edit_post) {
            // Format excerpt and content

            App::backend()->post_format  = $_POST['post_format'];
            App::backend()->post_excerpt = $_POST['post_excerpt'];
            App::backend()->post_content = $_POST['post_content'];

            App::backend()->post_title = $_POST['post_title'];

            App::backend()->cat_id = (int) $_POST['cat_id'];

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
                App::backend()->post_id,
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
            // Delete post

            try {
                # --BEHAVIOR-- adminBeforePostDelete -- string|int
                App::behavior()->callBehavior('adminBeforePostDelete', App::backend()->post_id);
                App::blog()->delPost(App::backend()->post_id);
                App::backend()->url()->redirect('admin.posts');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ($_POST !== [] && !empty($_POST['save']) && App::backend()->can_edit_post && !App::backend()->bad_dt) {
            // Create or update post

            if (!empty($_POST['new_cat_title']) && App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id())) {
                // Create category

                $cur_cat = App::blog()->categories()->openCategoryCursor();

                $cur_cat->cat_title = $_POST['new_cat_title'];
                $cur_cat->cat_url   = '';

                $parent_cat = empty($_POST['new_cat_parent']) ? '' : $_POST['new_cat_parent'];

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                App::backend()->cat_id = App::blog()->addCategory($cur_cat, (int) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string|int
                App::behavior()->callBehavior('adminAfterCategoryCreate', $cur_cat, App::backend()->cat_id);
            }

            $cur = App::blog()->openPostCursor();

            $cur->cat_id  = (App::backend()->cat_id ?: null);
            $cur->post_dt = App::backend()->post_dt ?
                date('Y-m-d H:i:00', (int) strtotime(App::backend()->post_dt)) :
                '';
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
            $cur->post_selected      = (int) App::backend()->post_selected;
            $cur->post_open_comment  = (int) App::backend()->post_open_comment;
            $cur->post_open_tb       = (int) App::backend()->post_open_tb;

            if (isset($_POST['post_url'])) {
                $cur->post_url = App::backend()->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (App::backend()->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePostUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminBeforePostUpdate', $cur, (int) App::backend()->post_id);

                    App::blog()->updPost(App::backend()->post_id, $cur);

                    # --BEHAVIOR-- adminAfterPostUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPostUpdate', $cur, (int) App::backend()->post_id);
                    Notices::addSuccessNotice(sprintf(__('The post "%s" has been successfully updated'), Html::escapeHTML(trim(Html::clean($cur->post_title)))));
                    App::backend()->url()->redirect(
                        'admin.post',
                        ['id' => App::backend()->post_id]
                    );
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            } else {
                $cur->user_id = App::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePostCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforePostCreate', $cur);

                    $return_id = App::blog()->addPost($cur);

                    # --BEHAVIOR-- adminAfterPostCreate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPostCreate', $cur, $return_id);

                    Notices::addSuccessNotice(__('Entry has been successfully created.'));
                    App::backend()->url()->redirect(
                        'admin.post',
                        ['id' => $return_id]
                    );
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        // Getting categories (a new category may have been created during process)
        App::backend()->categories_combo = Combos::getCategoriesCombo(
            App::blog()->getCategories()
        );

        return true;
    }

    public static function render(): void
    {
        App::backend()->default_tab = 'edit-entry';
        if (!App::backend()->can_edit_post) {
            App::backend()->default_tab = '';
        }
        if (!empty($_GET['co'])) {
            App::backend()->default_tab = 'comments';
        } elseif (!empty($_GET['tb'])) {
            App::backend()->default_tab = 'trackbacks';
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
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    App::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content'],
                    App::backend()->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        if (App::backend()->post_id) {
            $img_status       = App::status()->post()->image((int) App::backend()->post_status)->render();
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean(App::backend()->post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $img_status       = '';
            $edit_entry_title = App::backend()->page_title;
        }

        Page::open(
            App::backend()->page_title . ' - ' . __('Posts'),
            Page::jsModal() .
            Page::jsMetaEditor() .
            $admin_post_behavior .
            Page::jsLoad('js/_post.js') .
            Page::jsLoad('js/_trackbacks.js') .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPostHeaders --
            App::behavior()->callBehavior('adminPostHeaders') .
            Page::jsPageTabs(App::backend()->default_tab) .
            App::backend()->next_headlink . "\n" . App::backend()->prev_headlink,
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::blog()->name()) => '',
                    __('Posts')                           => App::backend()->url()->get('admin.posts'),
                    $edit_entry_title                     => '',
                ]
            ),
            [
                'x-frame-allow' => App::blog()->url(),
            ]
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('Entry has been successfully updated.'));
        } elseif (!empty($_GET['crea'])) {
            Notices::success(__('Entry has been successfully created.'));
        } elseif (!empty($_GET['attached'])) {
            Notices::success(__('File has been successfully attached.'));
        } elseif (!empty($_GET['rmattach'])) {
            Notices::success(__('Attachment has been successfully removed.'));
        }

        if (!empty($_GET['creaco'])) {
            Notices::success(__('Comment has been successfully created.'));
        }
        if (!empty($_GET['tbsent'])) {
            Notices::success(__('All pings sent.'));
        }

        // HTML conversion
        if (!empty($_GET['xconv'])) {
            App::backend()->post_excerpt = App::backend()->post_excerpt_xhtml;
            App::backend()->post_content = App::backend()->post_content_xhtml;
            App::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        if (App::backend()->post_id && !App::status()->post()->isRestricted((int) App::backend()->post->post_status)) {
            echo (new Para())
                ->items([
                    (new Link())
                        ->class(['onblog_link', 'outgoing'])
                        ->href(App::backend()->post->getURL())
                        ->title(Html::escapeHTML(trim(Html::clean(App::backend()->post_title))))
                        ->text(__('Go to this entry on the site') . ' ' . (new Img('images/outgoing-link.svg'))->render()),
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
            $items[] = new Capture(App::behavior()->callBehavior(...), ['adminPosNavLinks', App::backend()->post ?? null, 'post']);

            echo (new Para())
                ->class('nav_prevnext')
                ->items($items)
            ->render();
        }

        // Exit if we cannot view page
        if (!App::backend()->can_view_page) {
            Page::helpBlock('core_post');
            Page::close();
            exit;
        }

        /* Post form if we can edit post
        -------------------------------------------------------- */
        if (App::backend()->can_edit_post) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => (new Para())->class('entry-status')->items([
                            (new Select('post_status'))
                                ->items(App::backend()->status_combo)
                                ->default(App::backend()->post_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Entry status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
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
                                ->translate(false)
                                ->label(new Label(__('Entry language'), Label::OUTSIDE_LABEL_BEFORE)),
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
                                    ->href(App::backend()->url()->get('admin.post', ['id' => App::backend()->post_id, 'xconv' => '1']))
                                    ->text(__('Convert to HTML')),
                            ]),
                        ])
                        ->render(),
                    ],
                ],

                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => (new Para())->items([
                            (new Checkbox('post_selected', App::backend()->post_selected))
                                ->value(1)
                                ->label(new Label(__('Selected entry'), Label::IL_FT)),
                        ])
                        ->render(),

                        'cat_id' => (new Div())->items([
                            (new Text('h5', __('Category')))
                                ->id('label_cat_id'),
                            (new Para())
                                ->items([
                                    (new Select('cat_id'))
                                        ->items(App::backend()->categories_combo)
                                        ->default(App::backend()->cat_id)
                                        ->class('maximal')
                                        ->label(new Label(__('Category:'), Label::OL_TF)),
                                ]),
                            App::auth()->check(App::auth()->makePermissions([App::auth()::PERMISSION_CATEGORIES]), App::blog()->id()) ?
                            (new Div())
                                ->items([
                                    (new Text('h5', __('Add a new category')))
                                        ->id('create_cat'),
                                    (new Para())
                                        ->items([
                                            (new Input('new_cat_title'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->class('maximal')
                                                ->label(new Label(__('Title:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Select('new_cat_parent'))
                                                ->items(App::backend()->categories_combo)
                                                ->class('maximal')
                                                ->label(new Label(__('Parent:'), Label::OL_TF)),
                                        ]),
                                ]) :
                            (new None()),
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
                                        ->text(__('Warning: Comments are no longer accepted for this entry.'))
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
                                        ->text(__('Warning: Trackbacks are no longer accepted for this entry.'))
                                ) :
                                (new Note())
                                    ->class(['form-note', 'warn'])
                                    ->text(__('Trackbacks are not accepted on this blog so far.')),
                        ])
                        ->render(),

                        'post_password' => (new Para())->items([
                            (new Password('post_password'))
                                ->class('maximal')
                                ->value(Html::escapeHTML(App::backend()->post_password))
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
                                ->text(__('Warning: If you set the URL manually, it may conflict with another entry.')),
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
                                    __('Excerpt:') . ' ' . (new Text('span', __('Introduction to the post.')))->class('form-note')->render(),
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
                            ->value(Html::escapeHTML(App::backend()->post_notes))
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

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null, string
            App::behavior()->callBehavior('adminPostFormItems', $main_items, $sidebar_items, App::backend()->post ?? null, 'post');

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
                        'preview',
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
                    ->href(App::backend()->url()->get('admin.posts'))
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
            $title = (App::backend()->post_id ? __('Edit post') : __('New post')) . $format->render();

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->id('edit-entry')
                ->items([
                    (new Form('entry-form'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.post'))
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
                                                    (new Text('h3', __('Edit post')))
                                                        ->class('out-of-screen-if-js'),
                                                    (new Note())
                                                        ->class('form-note')
                                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                                                    (new Text(null, $main_part)),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostForm', App::backend()->post ?? null, 'post'])),
                                                    (new Para())
                                                        ->class(['border-top', 'form-buttons'])
                                                        ->items([
                                                            App::nonce()->formNonce(),
                                                            ...$buttons,
                                                        ]),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostAfterButtons', App::backend()->post ?? null])),
                                                ]),
                                        ]),
                                ]),
                            (new Div())
                                ->id('entry-sidebar')
                                ->extra('role="complementary"')
                                ->items([
                                    (new Text(null, $side_part)),
                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostFormSidebar', App::backend()->post ?? null])),
                                ]),
                        ]),
                    (new Capture(App::behavior()->callBehavior(...), ['adminPostAfterForm', App::backend()->post ?? null, 'post'])),
                ])
            ->render();
        }

        if (App::backend()->post_id) {
            // Comments

            $params = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments = App::blog()->getComments([...$params, 'comment_trackback' => 0]);

            # Actions combo box
            $combo_action = App::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$comments->isEmpty();

            // Prepare form
            $fields = [];

            $fields[] = (new Text('h3', __('Comments')));
            if (!$comments->isEmpty()) {
                $fields[] = (new Text(null, self::showComments($comments, $has_action, false)));
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
                            (new Hidden('section', 'comments')),
                            (new Hidden('id', App::backend()->post_id)),
                            App::nonce()->formNonce(),
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
                    (new Form('form-comments'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.post'))
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

        if (App::backend()->post_id && !App::status()->post()->isRestricted((int) App::backend()->post_status)) {
            // Trackbacks

            $params     = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];
            $trackbacks = App::blog()->getComments([...$params, 'comment_trackback' => 1]);

            // Actions combo box
            $combo_action = App::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$trackbacks->isEmpty();

            if (!empty($_GET['tb_auto'])) {
                App::backend()->tb_urls = implode("\n", App::backend()->tb->discover(App::backend()->post_excerpt_xhtml . ' ' . App::backend()->post_content_xhtml));
            }

            // Prepare form
            $fields = [];

            $fields[] = (new Text('h3', __('Trackbacks')));
            if (!$trackbacks->isEmpty()) {
                $fields[] = (new Text(null, self::showComments($trackbacks, $has_action, true)));
            } else {
                $fields[] = (new Note())->text(__('No trackback'));
            }

            if ($has_action) {
                $fields[] = (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Para())->class(['col', 'checkboxes-helpers']),
                        (new Para())->class(['col', 'right', 'form-buttons'])->items([
                            (new Select('action'))
                                ->items($combo_action)
                                ->label(new Label(__('Selected trackbacks action:'), Label::OL_TF)),
                            (new Hidden('section', 'trackbacks')),
                            (new Hidden('id', App::backend()->post_id)),
                            App::nonce()->formNonce(),
                            (new Submit('do-action-comm', __('Ok'))),
                        ]),
                    ]);
            }

            $pingsSent = function (): Set|None {
                $pings = App::backend()->tb->getPostPings((int) App::backend()->post_id);
                if ($pings->isEmpty()) {
                    return (new None());
                }

                $list = [];
                while ($pings->fetch()) {
                    $list[] = (new Li())
                        ->text(Date::dt2str(__('%Y-%m-%d %H:%M'), $pings->ping_dt) . ' - ' . $pings->ping_url);
                }

                return (new Set())
                    ->items([
                        (new Text('h3', __('Previously sent pings'))),
                        (new Ul())
                            ->class('nice')
                            ->items($list),
                    ]);
            };

            echo (new Div())
                ->id('trackbacks')
                ->class('multi-part')
                ->title(__('Trackbacks'))
                ->items([
                    $has_action ?
                    (new Form('form-trackbacks'))
                        ->method('post')
                        ->action(App::backend()->url()->get('admin.post'))
                        ->fields($fields) :
                    (new Set())
                        ->items($fields),
                    App::backend()->can_edit_post ?
                        //Add a trackback
                        (new Form('trackback-form'))
                            ->method('post')
                            ->action(App::backend()->url()->get('admin.post', ['id' => App::backend()->post_id]))
                            ->fields([
                                (new Fieldset())
                                    ->legend(new Legend(__('Ping blogs')))
                                    ->fields([
                                        (new Para())
                                            ->items([
                                                (new Textarea('tb_urls'))
                                                    ->cols(60)
                                                    ->rows(5)
                                                    ->value(App::backend()->tb_urls)
                                                    ->label(new Label(__('URLs to ping:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Textarea('tb_excerpt'))
                                                    ->cols(60)
                                                    ->rows(5)
                                                    ->value(App::backend()->tb_excerpt)
                                                    ->label(new Label(__('Excerpt to send:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('form-buttons')
                                            ->items([
                                                App::nonce()->formNonce(),
                                                (new Submit('ping', __('Ping blogs'))),
                                                (new Link())
                                                    ->href(App::backend()->url()->get('admin.post', [
                                                        'id'      => App::backend()->post_id,
                                                        'tb_auto' => 1,
                                                        'tb'      => 1,
                                                    ]))
                                                    ->text(__('Auto discover ping URLs'))
                                                    ->class('button'),
                                            ]),
                                        $pingsSent(),
                                    ]),
                            ]) :
                        (new None()),
                ])
            ->render();
        }

        Page::helpBlock('core_post', 'core_trackbacks', 'core_wiki');
        Page::close();
    }

    /**
     * Controls comments or trakbacks capabilities
     *
     * @param      mixed   $id     The identifier
     * @param      mixed   $dt     The date
     * @param      bool    $com    The com
     *
     * @return     bool    True if contribution allowed, False otherwise.
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
     * @param       Metarecord  $rs             Recordset
     * @param       bool        $has_action     Indicates if action is available
     * @param       bool        $tb             Is trackbacks?
     */
    protected static function showComments(MetaRecord $rs, bool $has_action, bool $tb = false): string
    {
        // IP are available only for super-admin and admin
        $show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id()
        );

        $rows = [];
        while ($rs->fetch()) {
            $cols        = [];
            $comment_url = App::backend()->url()->get('admin.comment', ['id' => $rs->comment_id]);
            $sts_class   = App::status()->comment()->id((int) $rs->comment_status);

            $cols[] = (new Td())
                ->class('nowrap')
                ->items([
                    $has_action ?
                    (new Checkbox(['comments[]']))
                        ->value($rs->comment_id)
                        ->title($tb ? __('select this trackback') : __('select this comment')) :
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
                ->text(App::status()->comment()->image((int) $rs->comment_status)->render());

            $cols[] = (new Td())
                ->class(['nowrap', 'status'])
                ->items([
                    (new Link())
                        ->href($comment_url)
                        ->title($tb ? __('Edit this trackback') : __('Edit this comment'))
                        ->items([
                            (new Img('images/edit.svg'))->class(['mark', 'mark-edit', 'light-only'])->alt(''),
                            (new Img('images/edit-dark.svg'))->class(['mark', 'mark-edit', 'dark-only'])->alt(''),
                            (new Text(null, ' ' . __('Edit'))),
                        ]),
                ]);

            $rows[] = (new Tr())
                ->class(array_filter(['line', App::status()->comment()->isRestricted($rs->comment_status) ? '' : 'offline ', $sts_class]))
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
