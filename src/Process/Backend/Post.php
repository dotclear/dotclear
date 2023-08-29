<?php
/**
 * @since 2.27 Before as admin/post.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use dcAuth;
use dcBlog;
use dcCategories;
use dcTrackback;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;
use form;

class Post extends Process
{
    public static function init(): bool
    {
        $params = [];
        Page::check(Core::auth()->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(Core::auth()->getInfo('user_tz') ?? 'UTC');

        // IP are available only for super-admin and admin
        Core::backend()->show_ip = Core::auth()->check(
            Core::auth()->makePermissions([
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]),
            Core::blog()->id
        );

        Core::backend()->post_id            = '';
        Core::backend()->cat_id             = '';
        Core::backend()->post_dt            = '';
        Core::backend()->post_format        = Core::auth()->getOption('post_format');
        Core::backend()->post_editor        = Core::auth()->getOption('editor');
        Core::backend()->post_password      = '';
        Core::backend()->post_url           = '';
        Core::backend()->post_lang          = Core::auth()->getInfo('user_lang');
        Core::backend()->post_title         = '';
        Core::backend()->post_excerpt       = '';
        Core::backend()->post_excerpt_xhtml = '';
        Core::backend()->post_content       = '';
        Core::backend()->post_content_xhtml = '';
        Core::backend()->post_notes         = '';
        Core::backend()->post_status        = Core::auth()->getInfo('user_post_status');
        Core::backend()->post_selected      = false;
        Core::backend()->post_open_comment  = Core::blog()->settings->system->allow_comments;
        Core::backend()->post_open_tb       = Core::blog()->settings->system->allow_trackbacks;

        Core::backend()->page_title = __('New post');

        Core::backend()->can_view_page = true;
        Core::backend()->can_edit_post = Core::auth()->check(Core::auth()->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id);
        Core::backend()->can_publish = Core::auth()->check(Core::auth()->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id);
        Core::backend()->can_delete = false;

        $post_headlink             = '<link rel="%s" title="%s" href="' . Core::backend()->url->get('admin.post', ['id' => '%s'], '&amp;', true) . '" />';
        Core::backend()->post_link = '<a href="' . Core::backend()->url->get('admin.post', ['id' => '%s'], '&amp;', true) . '" title="%s">%s</a>';

        Core::backend()->next_link     = null;
        Core::backend()->prev_link     = null;
        Core::backend()->next_headlink = null;
        Core::backend()->prev_headlink = null;

        # If user can't publish
        if (!Core::backend()->can_publish) {
            Core::backend()->post_status = dcBlog::POST_PENDING;
        }

        # Getting categories
        Core::backend()->categories_combo = Combos::getCategoriesCombo(
            Core::blog()->getCategories()
        );

        Core::backend()->status_combo = Combos::getPostStatusesCombo();

        // Formats combo
        $core_formaters    = Core::formater()->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[Core::formater()->getFormaterName($format)] = $format;
            }
        }
        Core::backend()->available_formats = $available_formats;

        // Languages combo
        Core::backend()->lang_combo = Combos::getLangsCombo(
            Core::blog()->getLangs(['order' => 'asc']),
            true
        );

        // Validation flag
        Core::backend()->bad_dt = false;

        // Trackbacks
        Core::backend()->tb      = new dcTrackback();
        Core::backend()->tb_urls = Core::backend()->tb_excerpt = '';

        // Get entry informations

        Core::backend()->post = null;

        if (!empty($_REQUEST['id'])) {
            Core::backend()->page_title = __('Edit post');

            $params['post_id'] = $_REQUEST['id'];

            Core::backend()->post = Core::blog()->getPosts($params);

            if (Core::backend()->post->isEmpty()) {
                Core::error()->add(__('This entry does not exist.'));
                Core::backend()->can_view_page = false;
            } else {
                Core::backend()->post_id            = Core::backend()->post->post_id;
                Core::backend()->cat_id             = Core::backend()->post->cat_id;
                Core::backend()->post_dt            = date('Y-m-d H:i', strtotime(Core::backend()->post->post_dt));
                Core::backend()->post_format        = Core::backend()->post->post_format;
                Core::backend()->post_password      = Core::backend()->post->post_password;
                Core::backend()->post_url           = Core::backend()->post->post_url;
                Core::backend()->post_lang          = Core::backend()->post->post_lang;
                Core::backend()->post_title         = Core::backend()->post->post_title;
                Core::backend()->post_excerpt       = Core::backend()->post->post_excerpt;
                Core::backend()->post_excerpt_xhtml = Core::backend()->post->post_excerpt_xhtml;
                Core::backend()->post_content       = Core::backend()->post->post_content;
                Core::backend()->post_content_xhtml = Core::backend()->post->post_content_xhtml;
                Core::backend()->post_notes         = Core::backend()->post->post_notes;
                Core::backend()->post_status        = Core::backend()->post->post_status;
                Core::backend()->post_selected      = (bool) Core::backend()->post->post_selected;
                Core::backend()->post_open_comment  = (bool) Core::backend()->post->post_open_comment;
                Core::backend()->post_open_tb       = (bool) Core::backend()->post->post_open_tb;

                Core::backend()->can_edit_post = Core::backend()->post->isEditable();
                Core::backend()->can_delete    = Core::backend()->post->isDeletable();

                $next_rs = Core::blog()->getNextPost(Core::backend()->post, 1);
                $prev_rs = Core::blog()->getNextPost(Core::backend()->post, -1);

                if ($next_rs !== null) {
                    Core::backend()->next_link = sprintf(
                        Core::backend()->post_link,
                        $next_rs->post_id,
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next entry') . '&nbsp;&#187;'
                    );
                    Core::backend()->next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        $next_rs->post_id
                    );
                }

                if ($prev_rs !== null) {
                    Core::backend()->prev_link = sprintf(
                        Core::backend()->post_link,
                        $prev_rs->post_id,
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        '&#171;&nbsp;' . __('Previous entry')
                    );
                    Core::backend()->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
                    );
                }

                // Sanitize trackbacks excerpt
                $buffer = empty($_POST['tb_excerpt']) ?
                    Core::backend()->post_excerpt_xhtml . ' ' . Core::backend()->post_content_xhtml :
                    $_POST['tb_excerpt'];
                $buffer = preg_replace(
                    '/\s+/ms',
                    ' ',
                    Text::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean($buffer))), 255)
                );
                Core::backend()->tb_excerpt = $buffer;
            }
        }
        if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'trackbacks') {
            $anchor = 'trackbacks';
        } else {
            $anchor = 'comments';
        }

        Core::backend()->comments_actions_page = new ActionsComments(
            Core::backend()->url->get('admin.post'),
            [
                'id'            => Core::backend()->post_id,
                'action_anchor' => $anchor,
                'section'       => $anchor,
            ]
        );

        if (Core::backend()->comments_actions_page->process()) {
            return self::status(false);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_POST['ping'])) {
            // Ping blogs

            if (!empty($_POST['tb_urls']) && Core::backend()->post_id && Core::backend()->post_status == dcBlog::POST_PUBLISHED && Core::backend()->can_edit_post) {
                Core::backend()->tb_urls = $_POST['tb_urls'];
                Core::backend()->tb_urls = str_replace("\r", '', Core::backend()->tb_urls);

                $tb_post_title = Html::escapeHTML(trim(Html::clean(Core::backend()->post_title)));
                $tb_post_url   = Core::backend()->post->getURL();

                foreach (explode("\n", Core::backend()->tb_urls) as $tb_url) {
                    try {
                        # --BEHAVIOR-- adminBeforePingTrackback -- string, string, string, string, string
                        Core::behavior()->callBehavior(
                            'adminBeforePingTrackback',
                            $tb_url,
                            Core::backend()->post_id,
                            $tb_post_title,
                            Core::backend()->tb_excerpt,
                            $tb_post_url
                        );

                        Core::backend()->tb->ping(
                            $tb_url,
                            (int) Core::backend()->post_id,
                            $tb_post_title,
                            Core::backend()->tb_excerpt,
                            $tb_post_url
                        );
                    } catch (Exception $e) {
                        Core::error()->add($e->getMessage());
                    }
                }

                if (!Core::error()->flag()) {
                    Notices::addSuccessNotice(__('All pings sent.'));
                    Core::backend()->url->redirect(
                        'admin.post',
                        ['id' => Core::backend()->post_id, 'tb' => '1']
                    );
                }
            }
        } elseif (!empty($_POST) && Core::backend()->can_edit_post) {
            // Format excerpt and content

            Core::backend()->post_format  = $_POST['post_format'];
            Core::backend()->post_excerpt = $_POST['post_excerpt'];
            Core::backend()->post_content = $_POST['post_content'];

            Core::backend()->post_title = $_POST['post_title'];

            Core::backend()->cat_id = (int) $_POST['cat_id'];

            if (isset($_POST['post_status'])) {
                Core::backend()->post_status = (int) $_POST['post_status'];
            }

            if (empty($_POST['post_dt'])) {
                Core::backend()->post_dt = '';
            } else {
                try {
                    Core::backend()->post_dt = strtotime($_POST['post_dt']);
                    if (!Core::backend()->post_dt || Core::backend()->post_dt == -1) {
                        Core::backend()->bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }
                    Core::backend()->post_dt = date('Y-m-d H:i', Core::backend()->post_dt);
                } catch (Exception $e) {
                    Core::error()->add($e->getMessage());
                }
            }

            Core::backend()->post_open_comment = !empty($_POST['post_open_comment']);
            Core::backend()->post_open_tb      = !empty($_POST['post_open_tb']);
            Core::backend()->post_selected     = !empty($_POST['post_selected']);
            Core::backend()->post_lang         = $_POST['post_lang'];
            Core::backend()->post_password     = !empty($_POST['post_password']) ? $_POST['post_password'] : null;

            Core::backend()->post_notes = $_POST['post_notes'];

            if (isset($_POST['post_url'])) {
                Core::backend()->post_url = $_POST['post_url'];
            }

            [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
            ] = [
                Core::backend()->post_excerpt,
                Core::backend()->post_excerpt_xhtml,
                Core::backend()->post_content,
                Core::backend()->post_content_xhtml,
            ];

            Core::blog()->setPostContent(
                Core::backend()->post_id,
                Core::backend()->post_format,
                Core::backend()->post_lang,
                $post_excerpt,
                $post_excerpt_xhtml,
                $post_content,
                $post_content_xhtml
            );

            [
                Core::backend()->post_excerpt,
                Core::backend()->post_excerpt_xhtml,
                Core::backend()->post_content,
                Core::backend()->post_content_xhtml
            ] = [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml,
            ];
        }

        if (!empty($_POST['delete']) && Core::backend()->can_delete) {
            // Delete post

            try {
                # --BEHAVIOR-- adminBeforePostDelete -- string|int
                Core::behavior()->callBehavior('adminBeforePostDelete', Core::backend()->post_id);
                Core::blog()->delPost(Core::backend()->post_id);
                Core::backend()->url->redirect('admin.posts');
            } catch (Exception $e) {
                Core::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST) && !empty($_POST['save']) && Core::backend()->can_edit_post && !Core::backend()->bad_dt) {
            // Create or update post

            if (!empty($_POST['new_cat_title']) && Core::auth()->check(Core::auth()->makePermissions([
                dcAuth::PERMISSION_CATEGORIES,
            ]), Core::blog()->id)) {
                // Create category

                $cur_cat = Core::con()->openCursor(Core::con()->prefix() . dcCategories::CATEGORY_TABLE_NAME);

                $cur_cat->cat_title = $_POST['new_cat_title'];
                $cur_cat->cat_url   = '';

                $parent_cat = !empty($_POST['new_cat_parent']) ? $_POST['new_cat_parent'] : '';

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                Core::behavior()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                Core::backend()->cat_id = Core::blog()->addCategory($cur_cat, (int) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string|int
                Core::behavior()->callBehavior('adminAfterCategoryCreate', $cur_cat, Core::backend()->cat_id);
            }

            $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::POST_TABLE_NAME);

            $cur->cat_id  = (Core::backend()->cat_id ?: null);
            $cur->post_dt = Core::backend()->post_dt ?
                date('Y-m-d H:i:00', strtotime(Core::backend()->post_dt)) :
                '';
            $cur->post_format        = Core::backend()->post_format;
            $cur->post_password      = Core::backend()->post_password;
            $cur->post_lang          = Core::backend()->post_lang;
            $cur->post_title         = Core::backend()->post_title;
            $cur->post_excerpt       = Core::backend()->post_excerpt;
            $cur->post_excerpt_xhtml = Core::backend()->post_excerpt_xhtml;
            $cur->post_content       = Core::backend()->post_content;
            $cur->post_content_xhtml = Core::backend()->post_content_xhtml;
            $cur->post_notes         = Core::backend()->post_notes;
            $cur->post_status        = Core::backend()->post_status;
            $cur->post_selected      = (int) Core::backend()->post_selected;
            $cur->post_open_comment  = (int) Core::backend()->post_open_comment;
            $cur->post_open_tb       = (int) Core::backend()->post_open_tb;

            if (isset($_POST['post_url'])) {
                $cur->post_url = Core::backend()->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (Core::backend()->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePostUpdate -- Cursor, int
                    Core::behavior()->callBehavior('adminBeforePostUpdate', $cur, (int) Core::backend()->post_id);

                    Core::blog()->updPost(Core::backend()->post_id, $cur);

                    # --BEHAVIOR-- adminAfterPostUpdate -- Cursor, int
                    Core::behavior()->callBehavior('adminAfterPostUpdate', $cur, (int) Core::backend()->post_id);
                    Notices::addSuccessNotice(sprintf(__('The post "%s" has been successfully updated'), Html::escapeHTML(trim(Html::clean($cur->post_title)))));
                    Core::backend()->url->redirect(
                        'admin.post',
                        ['id' => Core::backend()->post_id]
                    );
                } catch (Exception $e) {
                    Core::error()->add($e->getMessage());
                }
            } else {
                $cur->user_id = Core::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePostCreate -- Cursor
                    Core::behavior()->callBehavior('adminBeforePostCreate', $cur);

                    $return_id = Core::blog()->addPost($cur);

                    # --BEHAVIOR-- adminAfterPostCreate -- Cursor, int
                    Core::behavior()->callBehavior('adminAfterPostCreate', $cur, $return_id);

                    Notices::addSuccessNotice(__('Entry has been successfully created.'));
                    Core::backend()->url->redirect(
                        'admin.post',
                        ['id' => $return_id]
                    );
                } catch (Exception $e) {
                    Core::error()->add($e->getMessage());
                }
            }
        }

        // Getting categories (a new category may have been created during process)
        Core::backend()->categories_combo = Combos::getCategoriesCombo(
            Core::blog()->getCategories()
        );

        return true;
    }

    public static function render(): void
    {
        Core::backend()->default_tab = 'edit-entry';
        if (!Core::backend()->can_edit_post) {
            Core::backend()->default_tab = '';
        }
        if (!empty($_GET['co'])) {
            Core::backend()->default_tab = 'comments';
        } elseif (!empty($_GET['tb'])) {
            Core::backend()->default_tab = 'trackbacks';
        }

        if (Core::backend()->post_id) {
            $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

            $img_status = match ((int) Core::backend()->post_status) {
                dcBlog::POST_PUBLISHED   => sprintf($img_status_pattern, __('Published'), 'check-on.png'),
                dcBlog::POST_UNPUBLISHED => sprintf($img_status_pattern, __('Unpublished'), 'check-off.png'),
                dcBlog::POST_SCHEDULED   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png'),
                dcBlog::POST_PENDING     => sprintf($img_status_pattern, __('Pending'), 'check-wrn.png'),
                default                  => '',
            };

            $edit_entry_str  = __('&ldquo;%s&rdquo;');
            $page_title_edit = sprintf($edit_entry_str, Html::escapeHTML(trim(Html::clean(Core::backend()->post_title)))) . ' ' . $img_status;
        } else {
            $img_status      = '';
            $page_title_edit = '';
        }

        $admin_post_behavior = '';
        if (Core::backend()->post_editor) {
            $p_edit = $c_edit = '';
            if (!empty(Core::backend()->post_editor[Core::backend()->post_format])) {
                $p_edit = Core::backend()->post_editor[Core::backend()->post_format];
            }
            if (!empty(Core::backend()->post_editor['xhtml'])) {
                $c_edit = Core::backend()->post_editor['xhtml'];
            }
            if ($p_edit == $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    Core::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content'],
                    Core::backend()->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        Page::open(
            Core::backend()->page_title . ' - ' . __('Posts'),
            Page::jsModal() .
            Page::jsMetaEditor() .
            $admin_post_behavior .
            Page::jsLoad('js/_post.js') .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPostHeaders --
            Core::behavior()->callBehavior('adminPostHeaders') .
            Page::jsPageTabs(Core::backend()->default_tab) .
            Core::backend()->next_headlink . "\n" . Core::backend()->prev_headlink,
            Page::breadcrumb(
                [
                    Html::escapeHTML(Core::blog()->name) => '',
                    __('Posts')                          => Core::backend()->url->get('admin.posts'),
                    (Core::backend()->post_id ?
                        $page_title_edit :
                        Core::backend()->page_title) => '',
                ]
            ),
            [
                'x-frame-allow' => Core::blog()->url,
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
            Core::backend()->post_excerpt = Core::backend()->post_excerpt_xhtml;
            Core::backend()->post_content = Core::backend()->post_content_xhtml;
            Core::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        if (Core::backend()->post_id && Core::backend()->post->post_status == dcBlog::POST_PUBLISHED) {
            echo
            '<p><a class="onblog_link outgoing" href="' . Core::backend()->post->getURL() . '" title="' . Html::escapeHTML(trim(Html::clean(Core::backend()->post_title))) . '">' . __('Go to this entry on the site') . ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
        }
        if (Core::backend()->post_id) {
            echo
            '<p class="nav_prevnext">';
            if (Core::backend()->prev_link) {
                echo
                Core::backend()->prev_link;
            }
            if (Core::backend()->next_link && Core::backend()->prev_link) {
                echo
                ' | ';
            }
            if (Core::backend()->next_link) {
                echo
                Core::backend()->next_link;
            }

            # --BEHAVIOR-- adminPostNavLinks -- MetaRecord|null, string
            Core::behavior()->callBehavior('adminPostNavLinks', Core::backend()->post ?? null, 'post');

            echo
            '</p>';
        }

        // Exit if we cannot view page
        if (!Core::backend()->can_view_page) {
            Page::helpBlock('core_post');
            Page::close();
            exit;
        }

        /* Post form if we can edit post
        -------------------------------------------------------- */
        if (Core::backend()->can_edit_post) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => '<p class="entry-status"><label for="post_status">' . __('Entry status') . ' ' . $img_status . '</label>' .
                        form::combo(
                            'post_status',
                            Core::backend()->status_combo,
                            ['default' => Core::backend()->post_status, 'class' => 'maximal', 'disabled' => !Core::backend()->can_publish]
                        ) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::datetime('post_dt', [
                            'default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(Core::backend()->post_dt))),
                            'class'   => (Core::backend()->bad_dt ? 'invalid' : ''),
                        ]) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Entry language') . '</label>' .
                        form::combo('post_lang', Core::backend()->lang_combo, Core::backend()->post_lang) .
                        '</p>',
                        'post_format' => '<div>' .
                        '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                        '<p>' . form::combo('post_format', Core::backend()->available_formats, Core::backend()->post_format, 'maximal') . '</p>' .
                        '<p class="format_control control_no_xhtml">' .
                        '<a id="convert-xhtml" class="button' . (Core::backend()->post_id && Core::backend()->post_format != 'wiki' ? ' hide' : '') . '" href="' .
                        Core::backend()->url->get('admin.post', ['id' => Core::backend()->post_id, 'xconv' => '1']) .
                        '">' .
                        __('Convert to HTML') . '</a></p></div>',
                    ],
                ],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => '<p><label for="post_selected" class="classic">' .
                        form::checkbox('post_selected', 1, Core::backend()->post_selected) . ' ' .
                        __('Selected entry') . '</label></p>',
                        'cat_id' => '<div>' .
                        '<h5 id="label_cat_id">' . __('Category') . '</h5>' .
                        '<p><label for="cat_id">' . __('Category:') . '</label>' .
                        form::combo('cat_id', Core::backend()->categories_combo, Core::backend()->cat_id, 'maximal') .
                        '</p>' .
                        (Core::auth()->check(Core::auth()->makePermissions([
                            dcAuth::PERMISSION_CATEGORIES,
                        ]), Core::blog()->id) ?
                            '<div>' .
                            '<h5 id="create_cat">' . __('Add a new category') . '</h5>' .
                            '<p><label for="new_cat_title">' . __('Title:') . ' ' .
                            form::field('new_cat_title', 30, 255, ['class' => 'maximal']) . '</label></p>' .
                            '<p><label for="new_cat_parent">' . __('Parent:') . ' ' .
                            form::combo('new_cat_parent', Core::backend()->categories_combo, '', 'maximal') .
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
                        form::checkbox('post_open_comment', 1, Core::backend()->post_open_comment) . ' ' .
                        __('Accept comments') . '</label></p>' .
                        (Core::blog()->settings->system->allow_comments ?
                            (self::isContributionAllowed(Core::backend()->post_id, strtotime(Core::backend()->post_dt), true) ? '' : '<p class="form-note warn">' .
                            __('Warning: Comments are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' .
                            __('Comments are not accepted on this blog so far.') . '</p>') .
                        '<p><label for="post_open_tb" class="classic">' .
                        form::checkbox('post_open_tb', 1, Core::backend()->post_open_tb) . ' ' .
                        __('Accept trackbacks') . '</label></p>' .
                        (Core::blog()->settings->system->allow_trackbacks ?
                            (self::isContributionAllowed(Core::backend()->post_id, strtotime(Core::backend()->post_dt), false) ? '' : '<p class="form-note warn">' .
                            __('Warning: Trackbacks are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' . __('Trackbacks are not accepted on this blog so far.') . '</p>') .
                        '</div>',
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, Html::escapeHTML(Core::backend()->post_password), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, Html::escapeHTML(Core::backend()->post_url), 'maximal') .
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
                    '<label class="required no-margin bold" for="post_title"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
                    form::field('post_title', 20, 255, [
                        'default'    => Html::escapeHTML(Core::backend()->post_title),
                        'class'      => 'maximal',
                        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . Core::backend()->post_lang . '" spellcheck="true"',
                    ]) .
                    '</p>',

                    'post_excerpt' => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
                    __('Introduction to the post.') . '</span></label> ' .
                    form::textarea(
                        'post_excerpt',
                        50,
                        5,
                        [
                            'default'    => Html::escapeHTML(Core::backend()->post_excerpt),
                            'extra_html' => 'lang="' . Core::backend()->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',

                    'post_content' => '<p class="area" id="content-area"><label class="required bold" ' .
                    'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
                    form::textarea(
                        'post_content',
                        50,
                        Core::auth()->getOption('edit_size'),
                        [
                            'default'    => Html::escapeHTML(Core::backend()->post_content),
                            'extra_html' => 'required placeholder="' . __('Content') . '" lang="' . Core::backend()->post_lang . '" spellcheck="true"',
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
                            'default'    => Html::escapeHTML(Core::backend()->post_notes),
                            'extra_html' => 'lang="' . Core::backend()->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',
                ]
            );

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null, string
            Core::behavior()->callBehavior('adminPostFormItems', $main_items, $sidebar_items, Core::backend()->post ?? null, 'post');

            echo
            '<div class="multi-part" title="' . (Core::backend()->post_id ? __('Edit post') : __('New post')) .
            sprintf(' &rsaquo; %s', Core::formater()->getFormaterName(Core::backend()->post_format)) . '" id="edit-entry">' .
            '<form action="' . Core::backend()->url->get('admin.post') . '" method="post" id="entry-form">' .
            '<div id="entry-wrapper">' .
            '<div id="entry-content"><div class="constrained">' .
            '<h3 class="out-of-screen-if-js">' . __('Edit post') . '</h3>';

            foreach ($main_items as $id => $item) {
                echo $item;
            }

            # --BEHAVIOR-- adminPostForm (may be deprecated) -- MetaRecord|null, string
            Core::behavior()->callBehavior('adminPostForm', Core::backend()->post ?? null, 'post');

            echo
            '<p class="border-top">' .
            (Core::backend()->post_id ? form::hidden('id', Core::backend()->post_id) : '') .
            '<input type="submit" value="' . __('Save') . ' (s)" ' .
            'accesskey="s" name="save" /> ';

            if (Core::backend()->post_id) {
                $preview_url = Core::blog()->url . Core::url()->getURLFor('preview', Core::auth()->userID() . '/' . Http::browserUID(DC_MASTER_KEY . Core::auth()->userID() . Core::auth()->cryptLegacy(Core::auth()->userID())) . '/' . Core::backend()->post->post_url);

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = Core::auth()->user_prefs->interface->blank_preview;

                $preview_class  = $blank_preview ? '' : ' modal';
                $preview_target = $blank_preview ? '' : ' target="_blank"';

                echo
                '<a id="post-preview" href="' . $preview_url . '" class="button' . $preview_class . '" accesskey="p"' . $preview_target . '>' . __('Preview') . ' (p)' . '</a>' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';
            } else {
                echo
                '<a id="post-cancel" href="' . Core::backend()->url->get('admin.home') . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
            }

            echo(Core::backend()->can_delete ? ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' : '') .
            Core::nonce()->getFormNonce() .
            '</p>';

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
            Core::behavior()->callBehavior('adminPostFormSidebar', Core::backend()->post ?? null, 'post');

            echo
            '</div>' . // End #entry-sidebar
            '</form>';

            # --BEHAVIOR-- adminPostAfterForm -- MetaRecord|null, string
            Core::behavior()->callBehavior('adminPostAfterForm', Core::backend()->post ?? null, 'post');

            echo
            '</div>';
        }

        if (Core::backend()->post_id) {
            // Comments

            $params = ['post_id' => Core::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments = Core::blog()->getComments(array_merge($params, ['comment_trackback' => 0]));

            $combo_action = Core::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$comments->isEmpty();

            echo
            '<div id="comments" class="clear multi-part" title="' . __('Comments') . '">' .
            '<p class="top-add"><a class="button add" href="#comment-form">' . __('Add a comment') . '</a></p>';

            if ($has_action) {
                echo
                '<form action="' . Core::backend()->url->get('admin.post') . '" id="form-comments" method="post">';
            }

            echo
            '<h3>' . __('Comments') . '</h3>';
            if (!$comments->isEmpty()) {
                self::showComments($comments, $has_action, false, Core::backend()->show_ip);
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
                form::hidden(['id'], Core::backend()->post_id) .
                Core::nonce()->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '" /></p>' .
                '</div>' .
                '</form>';
            }

            // Add a comment

            echo
            '<div class="fieldset clear">' .
            '<h3>' . __('Add a comment') . '</h3>' .

            '<form action="' . Core::backend()->url->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="constrained">' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(Core::auth()->getInfo('user_cn')),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(Core::auth()->getInfo('user_email'))) .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(Core::auth()->getInfo('user_url'))) .
            '</p>' .

            '<p class="area"><label for="comment_content" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                8,
                ['extra_html' => 'required placeholder="' . __('Comment') . '" lang="' . Core::auth()->getInfo('user_lang') . '" spellcheck="true"']
            ) .
            '</p>' .

            '<p>' .
            form::hidden('post_id', Core::backend()->post_id) .
            Core::nonce()->getFormNonce() .
            '<input type="submit" name="add" value="' . __('Save') . '" /></p>' .
            '</div>' . #constrained

            '</form>' .
            '</div>' . #add comment
            '</div>'; #comments
        }

        if (Core::backend()->post_id && Core::backend()->post_status == dcBlog::POST_PUBLISHED) {
            // Trackbacks

            $params     = ['post_id' => Core::backend()->post_id, 'order' => 'comment_dt ASC'];
            $trackbacks = Core::blog()->getComments(array_merge($params, ['comment_trackback' => 1]));

            // Actions combo box
            $combo_action = Core::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$trackbacks->isEmpty();

            if (!empty($_GET['tb_auto'])) {
                Core::backend()->tb_urls = implode("\n", Core::backend()->tb->discover(Core::backend()->post_excerpt_xhtml . ' ' . Core::backend()->post_content_xhtml));
            }

            echo
            '<div id="trackbacks" class="clear multi-part" title="' . __('Trackbacks') . '">';

            if ($has_action) {
                // tracbacks actions
                echo
                '<form action="' . Core::backend()->url->get('admin.post') . '" id="form-trackbacks" method="post">';
            }

            echo
            '<h3>' . __('Trackbacks received') . '</h3>';

            if (!$trackbacks->isEmpty()) {
                self::showComments($trackbacks, $has_action, true, Core::backend()->show_ip);
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
                form::hidden('id', Core::backend()->post_id) .
                form::hidden(['section'], 'trackbacks') .
                Core::nonce()->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '" /></p>' .
                '</div>' .
                '</form>';
            }

            if (Core::backend()->can_edit_post) {
                // Add trackbacks

                echo
                '<div class="fieldset clear">';

                echo
                '<h3>' . __('Ping blogs') . '</h3>' .
                '<form action="' . Core::backend()->url->get('admin.post', ['id' => Core::backend()->post_id]) . '" id="trackback-form" method="post">' .
                '<p><label for="tb_urls" class="area">' . __('URLs to ping:') . '</label>' .
                form::textarea('tb_urls', 60, 5, Core::backend()->tb_urls) .
                '</p>' .

                '<p><label for="tb_excerpt" class="area">' . __('Excerpt to send:') . '</label>' .
                form::textarea('tb_excerpt', 60, 5, Core::backend()->tb_excerpt) . '</p>' .

                '<p>' .
                Core::nonce()->getFormNonce() .
                '<input type="submit" name="ping" value="' . __('Ping blogs') . '" />' .
                (empty($_GET['tb_auto']) ? '&nbsp;&nbsp;<a class="button" href="' . Core::backend()->url->get('admin.post', ['id' => Core::backend()->post_id, 'tb_auto' => 1, 'tb' => 1]) . '">' . __('Auto discover ping URLs') . '</a>' :
                    '') .
                '</p>' .
                '</form>';

                $pings = Core::backend()->tb->getPostPings((int) Core::backend()->post_id);
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
            if ((Core::blog()->settings->system->comments_ttl == 0) || (time() - Core::blog()->settings->system->comments_ttl * 86400 < $dt)) {
                return true;
            }
        } else {
            if ((Core::blog()->settings->system->trackbacks_ttl == 0) || (time() - Core::blog()->settings->system->trackbacks_ttl * 86400 < $dt)) {
                return true;
            }
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
            (Core::backend()->show_ip ? '<th class="nowrap">' . __('IP address') . '</th>' : '') .
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
            $comment_url = Core::backend()->url->get('admin.comment', ['id' => $rs->comment_id]);

            $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
            $img_status = '';
            $sts_class  = '';
            switch ($rs->comment_status) {
                case dcBlog::COMMENT_PUBLISHED:
                    $img_status = sprintf($img, __('Published'), 'check-on.png');
                    $sts_class  = 'sts-online';

                    break;
                case dcBlog::COMMENT_UNPUBLISHED:
                    $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                    $sts_class  = 'sts-offline';

                    break;
                case dcBlog::COMMENT_PENDING:
                    $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                    $sts_class  = 'sts-pending';

                    break;
                case dcBlog::COMMENT_JUNK:
                    $img_status = sprintf($img, __('Junk'), 'junk.png');
                    $sts_class  = 'sts-junk';

                    break;
            }

            echo
            '<tr class="line ' . ($rs->comment_status != dcBlog::COMMENT_PUBLISHED ? ' offline ' : '') . $sts_class . '"' .
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
                '<time datetime="' . Date::iso8601(strtotime($rs->comment_dt), Core::auth()->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt) .
                '</time>' .
            '</td>';

            if ($show_ip) {
                echo
                '<td class="nowrap"><a href="' . Core::backend()->url->get('admin.comments', ['ip' => $rs->comment_ip]) . '">' . $rs->comment_ip . '</a></td>';
            }
            echo
            '<td class="nowrap status">' . $img_status . '</td>' .
            '<td class="nowrap status"><a href="' . $comment_url . '">' .
            '<img src="images/edit-mini.png" alt="" title="' . __('Edit this comment') . '" /> ' . __('Edit') . '</a></td>' .
            '</tr>';
        }

        echo
        '</table></div>';
    }
}
