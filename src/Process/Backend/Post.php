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
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;
use form;

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

        // IP are available only for super-admin and admin
        App::backend()->show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id()
        );

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
            App::backend()->post_status = App::status()->post()->level('pending');
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
            App::blog()->getLangs(['order' => 'asc']),
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
                    Text::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean($buffer))), 255)
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

            if (!empty($_POST['tb_urls']) && App::backend()->post_id && App::backend()->post_status >= App::status()->post()->level('published') && App::backend()->can_edit_post) {
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

        if (App::backend()->post_id) {
            $img_status_pattern = '<img class="mark mark-%3$s" alt="%1$s" src="images/%2$s">';

            $img_status = match ((int) App::backend()->post_status) {
                App::status()->post()->level('published')   => sprintf($img_status_pattern, __('Published'), 'published.svg', 'published'),
                App::status()->post()->level('unpublished') => sprintf($img_status_pattern, __('Unpublished'), 'unpublished.svg', 'unpublished'),
                App::status()->post()->level('scheduled')   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.svg', 'scheduled'),
                App::status()->post()->level('pending')     => sprintf($img_status_pattern, __('Pending'), 'pending.svg', 'pending'),
                default                                     => '',
            };

            $edit_entry_str  = __('&ldquo;%s&rdquo;');
            $page_title_edit = sprintf($edit_entry_str, Html::escapeHTML(trim(Html::clean(App::backend()->post_title)))) . ' ' . $img_status;
        } else {
            $img_status      = '';
            $page_title_edit = '';
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
                    (App::backend()->post_id ?
                        $page_title_edit :
                        App::backend()->page_title) => '',
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

        // XHTML conversion
        if (!empty($_GET['xconv'])) {
            App::backend()->post_excerpt = App::backend()->post_excerpt_xhtml;
            App::backend()->post_content = App::backend()->post_content_xhtml;
            App::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        if (App::backend()->post_id && App::backend()->post->post_status >= App::status()->post()->level('published')) {
            echo
            '<p><a class="onblog_link outgoing" href="' . App::backend()->post->getURL() . '" title="' . Html::escapeHTML(trim(Html::clean(App::backend()->post_title))) . '">' . __('Go to this entry on the site') . ' <img src="images/outgoing-link.svg" alt=""></a></p>';
        }
        if (App::backend()->post_id) {
            echo
            '<p class="nav_prevnext">';
            if (App::backend()->prev_link) {
                echo
                App::backend()->prev_link;
            }
            if (App::backend()->next_link) {
                echo
                App::backend()->next_link;
            }

            # --BEHAVIOR-- adminPostNavLinks -- MetaRecord|null, string
            App::behavior()->callBehavior('adminPostNavLinks', App::backend()->post ?? null, 'post');

            echo
            '</p>';
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
                        'post_status' => '<p class="entry-status"><label for="post_status">' . __('Entry status') . ' ' . $img_status . '</label>' .
                        form::combo(
                            'post_status',
                            App::backend()->status_combo,
                            ['default' => App::backend()->post_status, 'class' => 'maximal', 'disabled' => !App::backend()->can_publish]
                        ) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::datetime('post_dt', [
                            'default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(App::backend()->post_dt))),
                            'class'   => (App::backend()->bad_dt ? 'invalid' : ''),
                        ]) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Entry language') . '</label>' .
                        form::combo('post_lang', App::backend()->lang_combo, App::backend()->post_lang) .
                        '</p>',
                        'post_format' => '<p><label for="post_format" class="classic" id="label_format">' . __('Text formatting') . '</label>' .
                        form::combo('post_format', App::backend()->available_formats, App::backend()->post_format, 'maximal') .
                        '<span class="format_control control_no_xhtml">' .
                        '<a id="convert-xhtml" class="button' . (App::backend()->post_id && App::backend()->post_format != 'wiki' ? ' hide' : '') . '" href="' .
                        App::backend()->url()->get('admin.post', ['id' => App::backend()->post_id, 'xconv' => '1']) .
                        '">' .
                        __('Convert to HTML') . '</a></span></p>',
                    ],
                ],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => '<p><label for="post_selected" class="classic">' .
                        form::checkbox('post_selected', 1, App::backend()->post_selected) . ' ' .
                        __('Selected entry') . '</label></p>',
                        'cat_id' => '<div>' .
                        '<h5 id="label_cat_id">' . __('Category') . '</h5>' .
                        '<p><label for="cat_id">' . __('Category:') . '</label>' .
                        form::combo('cat_id', App::backend()->categories_combo, App::backend()->cat_id, 'maximal') .
                        '</p>' .
                        (App::auth()->check(App::auth()->makePermissions([
                            App::auth()::PERMISSION_CATEGORIES,
                        ]), App::blog()->id()) ?
                            '<div>' .
                            '<h5 id="create_cat">' . __('Add a new category') . '</h5>' .
                            '<p><label for="new_cat_title">' . __('Title:') . ' ' .
                            form::field('new_cat_title', 30, 255, ['class' => 'maximal']) . '</label></p>' .
                            '<p><label for="new_cat_parent">' . __('Parent:') . ' ' .
                            form::combo('new_cat_parent', App::backend()->categories_combo, '', 'maximal') .
                            '</label></p>' .
                            '</div>' :
                            '') .
                        '</div>',
                    ],
                ],
                'options-box' => [
                    'title' => __('Options'),
                    'items' => [
                        'post_open_comment_tb' => '<div>' .
                        '<h5 id="label_comment_tb">' . __('Comments and trackbacks list') . '</h5>' .
                        '<p><label for="post_open_comment" class="classic">' .
                        form::checkbox('post_open_comment', 1, App::backend()->post_open_comment) . ' ' .
                        __('Accept comments') . '</label></p>' .
                        (App::blog()->settings()->system->allow_comments ?
                            (self::isContributionAllowed(App::backend()->post_id, strtotime(App::backend()->post_dt), true) ? '' : '<p class="form-note warn">' .
                            __('Warning: Comments are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' .
                            __('Comments are not accepted on this blog so far.') . '</p>') .
                        '<p><label for="post_open_tb" class="classic">' .
                        form::checkbox('post_open_tb', 1, App::backend()->post_open_tb) . ' ' .
                        __('Accept trackbacks') . '</label></p>' .
                        (App::blog()->settings()->system->allow_trackbacks ?
                            (self::isContributionAllowed(App::backend()->post_id, strtotime(App::backend()->post_dt), false) ? '' : '<p class="form-note warn">' .
                            __('Warning: Trackbacks are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' . __('Trackbacks are not accepted on this blog so far.') . '</p>') .
                        '</div>',
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, Html::escapeHTML(App::backend()->post_password), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, Html::escapeHTML(App::backend()->post_url), 'maximal') .
                        '</p>' .
                        '<p class="form-note warn">' .
                        __('Warning: If you set the URL manually, it may conflict with another entry.') .
                        '</p></div>',
                    ],
                ],
            ]);

            $main_items = new ArrayObject(
                [
                    'post_title' => '<p class="col">' .
                    '<label class="required no-margin bold" for="post_title"><span>*</span> ' . __('Title:') . '</label>' .
                    form::field('post_title', 20, 255, [
                        'default'    => Html::escapeHTML(App::backend()->post_title),
                        'class'      => 'maximal',
                        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . App::backend()->post_lang . '" spellcheck="true"',
                    ]) .
                    '</p>',

                    'post_excerpt' => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
                    __('Introduction to the post.') . '</span></label> ' .
                    form::textarea(
                        'post_excerpt',
                        50,
                        5,
                        [
                            'default'    => Html::escapeHTML(App::backend()->post_excerpt),
                            'extra_html' => 'lang="' . App::backend()->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',

                    'post_content' => '<p class="area" id="content-area"><label class="required bold" ' .
                    'for="post_content"><span>*</span> ' . __('Content:') . '</label> ' .
                    form::textarea(
                        'post_content',
                        50,
                        App::auth()->getOption('edit_size'),
                        [
                            'default'    => Html::escapeHTML(App::backend()->post_content),
                            'extra_html' => 'required placeholder="' . __('Content') . '" lang="' . App::backend()->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',

                    'post_notes' => '<p class="area" id="notes-area"><label for="post_notes" class="bold">' . __('Personal notes:') . ' <span class="form-note">' .
                    __('Unpublished notes.') . '</span></label>' .
                    form::textarea(
                        'post_notes',
                        50,
                        5,
                        [
                            'default'    => Html::escapeHTML(App::backend()->post_notes),
                            'extra_html' => 'lang="' . App::backend()->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',
                ]
            );

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null, string
            App::behavior()->callBehavior('adminPostFormItems', $main_items, $sidebar_items, App::backend()->post ?? null, 'post');

            echo
            '<div class="multi-part" title="' . (App::backend()->post_id ? __('Edit post') : __('New post')) .
            sprintf('<span> &rsaquo; %s</span>', App::formater()->getFormaterName(App::backend()->post_format)) . '" id="edit-entry">' .
            '<form action="' . App::backend()->url()->get('admin.post') . '" method="post" id="entry-form">' .
            '<div id="entry-wrapper">' .
            '<div id="entry-content"><div class="constrained">' .
            '<h3 class="out-of-screen-if-js">' . __('Edit post') . '</h3>' .
            '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>';

            foreach ($main_items as $id => $item) {
                echo $item;
            }

            # --BEHAVIOR-- adminPostForm (may be deprecated) -- MetaRecord|null, string
            App::behavior()->callBehavior('adminPostForm', App::backend()->post ?? null, 'post');

            echo
            '<p class="border-top form-buttons">' .
            (App::backend()->post_id ? form::hidden('id', App::backend()->post_id) : '') .
            '<input type="submit" value="' . __('Save') . ' (s)" ' .
            'accesskey="s" name="save"> ';

            if (App::backend()->post_id) {
                $preview_url = App::blog()->url() . App::url()->getURLFor('preview', App::auth()->userID() . '/' . Http::browserUID(App::config()->masterKey() . App::auth()->userID() . App::auth()->cryptLegacy((string) App::auth()->userID())) . '/' . App::backend()->post->post_url);

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->interface->blank_preview;

                $preview_class  = $blank_preview ? '' : ' modal';
                $preview_target = $blank_preview ? '' : ' target="_blank"';

                echo
                '<a id="post-preview" href="' . $preview_url . '" class="button' . $preview_class . '" accesskey="p"' . $preview_target . '>' . __('Preview') . ' (p)' . '</a>' .
                ' <input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js">';
            } else {
                echo
                '<a id="post-cancel" href="' . App::backend()->url()->get('admin.posts') . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
            }

            echo(App::backend()->can_delete ? ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete">' : '') .
            App::nonce()->getFormNonce() .
            '</p>';

            # --BEHAVIOR-- adminPostAfterButtons -- MetaRecord|null, string
            App::behavior()->callBehavior('adminPostAfterButtons', App::backend()->post ?? null, 'post');

            echo
            '</div></div>' . // End #entry-content
            '</div>' .       // End #entry-wrapper

            '<div id="entry-sidebar" role="complementary">';

            foreach ($sidebar_items as $id => $c) {
                echo
                '<div id="' . $id . '" class="sb-box">' .
                '<h4>' . $c['title'] . '</h4>';
                foreach ($c['items'] as $e_content) {
                    echo $e_content;
                }
                echo
                '</div>';
            }

            # --BEHAVIOR-- adminPostFormSidebar (may be deprecated) -- MetaRecord|null, string
            App::behavior()->callBehavior('adminPostFormSidebar', App::backend()->post ?? null, 'post');

            echo
            '</div>' . // End #entry-sidebar
            '</form>';

            # --BEHAVIOR-- adminPostAfterForm -- MetaRecord|null, string
            App::behavior()->callBehavior('adminPostAfterForm', App::backend()->post ?? null, 'post');

            echo
            '</div>';
        }

        if (App::backend()->post_id) {
            // Comments

            $params = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments = App::blog()->getComments([...$params, 'comment_trackback' => 0]);

            $combo_action = App::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$comments->isEmpty();

            echo
            '<div id="comments" class="clear multi-part" title="' . __('Comments') . '">' .
            '<p class="top-add"><a class="button add" href="#comment-form">' . __('Add a comment') . '</a></p>';

            if ($has_action) {
                echo
                '<form action="' . App::backend()->url()->get('admin.post') . '" id="form-comments" method="post">';
            }

            echo
            '<h3>' . __('Comments') . '</h3>';
            if (!$comments->isEmpty()) {
                self::showComments($comments, $has_action, false, App::backend()->show_ip);
            } else {
                echo
                '<p>' . __('No comments') . '</p>';
            }

            if ($has_action) {
                echo
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
                form::combo('action', $combo_action) .
                form::hidden(['section'], 'comments') .
                form::hidden(['id'], App::backend()->post_id) .
                App::nonce()->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '"></p>' .
                '</div>' .
                '</form>';
            }

            // Add a comment

            echo
            '<div class="fieldset clear">' .
            '<h3>' . __('Add a comment') . '</h3>' .
            '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>' .

            '<form action="' . App::backend()->url()->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="constrained">' .
            '<p><label for="comment_author" class="required"><span>*</span> ' . __('Name:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(App::auth()->getInfo('user_cn')),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(App::auth()->getInfo('user_email'))) .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(App::auth()->getInfo('user_url'))) .
            '</p>' .

            '<p class="area"><label for="comment_content" class="required"><span>*</span> ' .
            __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                8,
                ['extra_html' => 'required placeholder="' . __('Comment') . '" lang="' . App::auth()->getInfo('user_lang') . '" spellcheck="true"']
            ) .
            '</p>' .

            '<p>' .
            form::hidden('post_id', App::backend()->post_id) .
            App::nonce()->getFormNonce() .
            '<input type="submit" name="add" value="' . __('Save') . '"></p>' .
            '</div>' . #constrained

            '</form>' .
            '</div>' . #add comment
            '</div>'; #comments
        }

        if (App::backend()->post_id && App::backend()->post_status >= App::status()->post()->level('published')) {
            // Trackbacks

            $params     = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];
            $trackbacks = App::blog()->getComments([...$params, 'comment_trackback' => 1]);

            // Actions combo box
            $combo_action = App::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$trackbacks->isEmpty();

            if (!empty($_GET['tb_auto'])) {
                App::backend()->tb_urls = implode("\n", App::backend()->tb->discover(App::backend()->post_excerpt_xhtml . ' ' . App::backend()->post_content_xhtml));
            }

            echo
            '<div id="trackbacks" class="clear multi-part" title="' . __('Trackbacks') . '">';

            if ($has_action) {
                // tracbacks actions
                echo
                '<form action="' . App::backend()->url()->get('admin.post') . '" id="form-trackbacks" method="post">';
            }

            echo
            '<h3>' . __('Trackbacks received') . '</h3>';

            if (!$trackbacks->isEmpty()) {
                self::showComments($trackbacks, $has_action, true, App::backend()->show_ip);
            } else {
                echo
                '<p>' . __('No trackback') . '</p>';
            }

            if ($has_action) {
                echo
                '<div class="two-cols">' .
                '<p class="col checkboxes-helpers"></p>' .

                '<p class="col right"><label for="action" class="classic">' . __('Selected trackbacks action:') . '</label> ' .
                form::combo('action', $combo_action) .
                form::hidden('id', App::backend()->post_id) .
                form::hidden(['section'], 'trackbacks') .
                App::nonce()->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '"></p>' .
                '</div>' .
                '</form>';
            }

            if (App::backend()->can_edit_post) {
                // Add trackbacks

                echo
                '<div class="fieldset clear">';

                echo
                '<h3>' . __('Ping blogs') . '</h3>' .
                '<form action="' . App::backend()->url()->get('admin.post', ['id' => App::backend()->post_id]) . '" id="trackback-form" method="post">' .
                '<p><label for="tb_urls" class="area">' . __('URLs to ping:') . '</label>' .
                form::textarea('tb_urls', 60, 5, App::backend()->tb_urls) .
                '</p>' .

                '<p><label for="tb_excerpt" class="area">' . __('Excerpt to send:') . '</label>' .
                form::textarea('tb_excerpt', 60, 5, App::backend()->tb_excerpt) . '</p>' .

                '<p>' .
                App::nonce()->getFormNonce() .
                '<input type="submit" name="ping" value="' . __('Ping blogs') . '">' .
                (empty($_GET['tb_auto']) ? '&nbsp;&nbsp;<a class="button" href="' . App::backend()->url()->get('admin.post', ['id' => App::backend()->post_id, 'tb_auto' => 1, 'tb' => 1]) . '">' . __('Auto discover ping URLs') . '</a>' :
                    '') .
                '</p>' .
                '</form>';

                $pings = App::backend()->tb->getPostPings((int) App::backend()->post_id);
                if (!$pings->isEmpty()) {
                    echo
                    '<h3>' . __('Previously sent pings') . '</h3>' .
                    '<ul class="nice">';
                    while ($pings->fetch()) {
                        echo
                        '<li>' . Date::dt2str(__('%Y-%m-%d %H:%M'), $pings->ping_dt) . ' - ' . $pings->ping_url . '</li>';
                    }
                    echo
                    '</ul>';
                }

                echo
                '</div>';
            }

            echo
            '</div>'; // Trackbacks
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
     * @param      MetaRecord   $rs          The recordset
     * @param      bool         $has_action  Indicates if action is possible
     * @param      bool         $tb          Is trackbacks?
     * @param      bool         $show_ip     Show ip?
     */
    protected static function showComments(MetaRecord $rs, bool $has_action, bool $tb = false, bool $show_ip = true): void
    {
        echo
            '<div class="table-outer">' .
            '<table class="comments-list"><tr>' .
            '<th colspan="2" class="first">' . __('Author') . '</th>' .
            '<th>' . __('Date') . '</th>' .
            (App::backend()->show_ip ? '<th class="nowrap">' . __('IP address') . '</th>' : '') .
            '<th>' . __('Status') . '</th>' .
            '<th>' . __('Edit') . '</th>' .
            '</tr>';

        $comments = [];
        if (isset($_REQUEST['comments'])) {
            foreach ($_REQUEST['comments'] as $v) {
                $comments[(int) $v] = true;
            }
        }

        while ($rs->fetch()) {
            $comment_url = App::backend()->url()->get('admin.comment', ['id' => $rs->comment_id]);

            $img        = '<img alt="%1$s" class="mark mark-%3$s" src="images/%2$s">';
            $img_status = '';
            $sts_class  = '';
            switch ((int) $rs->comment_status) {
                case App::status()->comment()->level('published'):
                    $img_status = sprintf($img, __('Published'), 'published.svg', 'published');
                    $sts_class  = 'sts-online';

                    break;
                case App::status()->comment()->level('unpublished'):
                    $img_status = sprintf($img, __('Unpublished'), 'unpublished.svg', 'unpublished');
                    $sts_class  = 'sts-offline';

                    break;
                case App::status()->comment()->level('pending'):
                    $img_status = sprintf($img, __('Pending'), 'pending.svg', 'pending');
                    $sts_class  = 'sts-pending';

                    break;
                case App::status()->comment()->level('junk'):
                    $img_status = sprintf($img, __('Junk'), 'junk.svg', 'junk light-only') . sprintf($img, __('Junk'), 'junk-dark.svg', 'junk dark-only');
                    $sts_class  = 'sts-junk';

                    break;
            }

            echo
            '<tr class="line ' . ($rs->comment_status <= App::status()->comment()->level('unpublished') ? ' offline ' : '') . $sts_class . '"' .
            ' id="c' . $rs->comment_id . '">';

            echo
            '<td class="nowrap">';
            if ($has_action) {
                echo form::checkbox(
                    ['comments[]'],
                    $rs->comment_id,
                    [
                        'checked'    => isset($comments[$rs->comment_id]),
                        'extra_html' => 'title="' . ($tb ? __('select this trackback') : __('select this comment')) . '"',
                    ]
                );
            }
            echo
            '</td>' .
            '<td class="maximal">' . Html::escapeHTML($rs->comment_author) . '</td>' .
            '<td class="nowrap">' .
                '<time datetime="' . Date::iso8601((int) strtotime($rs->comment_dt), App::auth()->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt) .
                '</time>' .
            '</td>';

            if ($show_ip) {
                echo
                '<td class="nowrap"><a href="' . App::backend()->url()->get('admin.comments', ['ip' => $rs->comment_ip]) . '">' . $rs->comment_ip . '</a></td>';
            }
            echo
            '<td class="nowrap status">' . $img_status . '</td>' .
            '<td class="nowrap status"><a href="' . $comment_url . '" title="' . __('Edit this comment') . '">' .
            '<img class="mark mark-edit light-only" src="images/edit.svg" alt="">' .
            '<img class="mark mark-edit dark-only" src="images/edit-dark.svg" alt="">' .
            ' ' . __('Edit') . '</a></td>' .
            '</tr>';
        }

        echo
        '</table></div>';
    }
}
