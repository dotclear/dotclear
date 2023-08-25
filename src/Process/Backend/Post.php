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
use dcCore;
use dcMedia;
use dcTrackback;
use Dotclear\Core\Backend\Action\ActionsComments;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
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
        Page::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(dcCore::app()->auth->getInfo('user_tz') ?? 'UTC');

        // IP are available only for super-admin and admin
        dcCore::app()->admin->show_ip = dcCore::app()->auth->check(
            dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]),
            dcCore::app()->blog->id
        );

        dcCore::app()->admin->post_id            = '';
        dcCore::app()->admin->cat_id             = '';
        dcCore::app()->admin->post_dt            = '';
        dcCore::app()->admin->post_format        = dcCore::app()->auth->getOption('post_format');
        dcCore::app()->admin->post_editor        = dcCore::app()->auth->getOption('editor');
        dcCore::app()->admin->post_password      = '';
        dcCore::app()->admin->post_url           = '';
        dcCore::app()->admin->post_lang          = dcCore::app()->auth->getInfo('user_lang');
        dcCore::app()->admin->post_title         = '';
        dcCore::app()->admin->post_excerpt       = '';
        dcCore::app()->admin->post_excerpt_xhtml = '';
        dcCore::app()->admin->post_content       = '';
        dcCore::app()->admin->post_content_xhtml = '';
        dcCore::app()->admin->post_notes         = '';
        dcCore::app()->admin->post_status        = dcCore::app()->auth->getInfo('user_post_status');
        dcCore::app()->admin->post_selected      = false;
        dcCore::app()->admin->post_open_comment  = dcCore::app()->blog->settings->system->allow_comments;
        dcCore::app()->admin->post_open_tb       = dcCore::app()->blog->settings->system->allow_trackbacks;

        dcCore::app()->admin->page_title = __('New post');

        dcCore::app()->admin->can_view_page = true;
        dcCore::app()->admin->can_edit_post = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id);
        dcCore::app()->admin->can_publish = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_PUBLISH,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id);
        dcCore::app()->admin->can_delete = false;

        $post_headlink                  = '<link rel="%s" title="%s" href="' . dcCore::app()->admin->url->get('admin.post', ['id' => '%s'], '&amp;', true) . '" />';
        dcCore::app()->admin->post_link = '<a href="' . dcCore::app()->admin->url->get('admin.post', ['id' => '%s'], '&amp;', true) . '" title="%s">%s</a>';

        dcCore::app()->admin->next_link     = null;
        dcCore::app()->admin->prev_link     = null;
        dcCore::app()->admin->next_headlink = null;
        dcCore::app()->admin->prev_headlink = null;

        # If user can't publish
        if (!dcCore::app()->admin->can_publish) {
            dcCore::app()->admin->post_status = dcBlog::POST_PENDING;
        }

        # Getting categories
        dcCore::app()->admin->categories_combo = Combos::getCategoriesCombo(
            dcCore::app()->blog->getCategories()
        );

        dcCore::app()->admin->status_combo = Combos::getPostStatusesCombo();

        // Formats combo
        $core_formaters    = dcCore::app()->formater->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[dcCore::app()->formater->getFormaterName($format)] = $format;
            }
        }
        dcCore::app()->admin->available_formats = $available_formats;

        // Languages combo
        dcCore::app()->admin->lang_combo = Combos::getLangsCombo(
            dcCore::app()->blog->getLangs(['order' => 'asc']),
            true
        );

        // Validation flag
        dcCore::app()->admin->bad_dt = false;

        // Trackbacks
        dcCore::app()->admin->tb      = new dcTrackback();
        dcCore::app()->admin->tb_urls = dcCore::app()->admin->tb_excerpt = '';

        // Get entry informations

        dcCore::app()->admin->post = null;

        if (!empty($_REQUEST['id'])) {
            dcCore::app()->admin->page_title = __('Edit post');

            $params['post_id'] = $_REQUEST['id'];

            dcCore::app()->admin->post = dcCore::app()->blog->getPosts($params);

            if (dcCore::app()->admin->post->isEmpty()) {
                dcCore::app()->error->add(__('This entry does not exist.'));
                dcCore::app()->admin->can_view_page = false;
            } else {
                dcCore::app()->admin->post_id            = dcCore::app()->admin->post->post_id;
                dcCore::app()->admin->cat_id             = dcCore::app()->admin->post->cat_id;
                dcCore::app()->admin->post_dt            = date('Y-m-d H:i', strtotime(dcCore::app()->admin->post->post_dt));
                dcCore::app()->admin->post_format        = dcCore::app()->admin->post->post_format;
                dcCore::app()->admin->post_password      = dcCore::app()->admin->post->post_password;
                dcCore::app()->admin->post_url           = dcCore::app()->admin->post->post_url;
                dcCore::app()->admin->post_lang          = dcCore::app()->admin->post->post_lang;
                dcCore::app()->admin->post_title         = dcCore::app()->admin->post->post_title;
                dcCore::app()->admin->post_excerpt       = dcCore::app()->admin->post->post_excerpt;
                dcCore::app()->admin->post_excerpt_xhtml = dcCore::app()->admin->post->post_excerpt_xhtml;
                dcCore::app()->admin->post_content       = dcCore::app()->admin->post->post_content;
                dcCore::app()->admin->post_content_xhtml = dcCore::app()->admin->post->post_content_xhtml;
                dcCore::app()->admin->post_notes         = dcCore::app()->admin->post->post_notes;
                dcCore::app()->admin->post_status        = dcCore::app()->admin->post->post_status;
                dcCore::app()->admin->post_selected      = (bool) dcCore::app()->admin->post->post_selected;
                dcCore::app()->admin->post_open_comment  = (bool) dcCore::app()->admin->post->post_open_comment;
                dcCore::app()->admin->post_open_tb       = (bool) dcCore::app()->admin->post->post_open_tb;

                dcCore::app()->admin->can_edit_post = dcCore::app()->admin->post->isEditable();
                dcCore::app()->admin->can_delete    = dcCore::app()->admin->post->isDeletable();

                $next_rs = dcCore::app()->blog->getNextPost(dcCore::app()->admin->post, 1);
                $prev_rs = dcCore::app()->blog->getNextPost(dcCore::app()->admin->post, -1);

                if ($next_rs !== null) {
                    dcCore::app()->admin->next_link = sprintf(
                        dcCore::app()->admin->post_link,
                        $next_rs->post_id,
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next entry') . '&nbsp;&#187;'
                    );
                    dcCore::app()->admin->next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        $next_rs->post_id
                    );
                }

                if ($prev_rs !== null) {
                    dcCore::app()->admin->prev_link = sprintf(
                        dcCore::app()->admin->post_link,
                        $prev_rs->post_id,
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        '&#171;&nbsp;' . __('Previous entry')
                    );
                    dcCore::app()->admin->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
                    );
                }

                try {
                    dcCore::app()->media = new dcMedia();
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }

                // Sanitize trackbacks excerpt
                $buffer = empty($_POST['tb_excerpt']) ?
                    dcCore::app()->admin->post_excerpt_xhtml . ' ' . dcCore::app()->admin->post_content_xhtml :
                    $_POST['tb_excerpt'];
                $buffer = preg_replace(
                    '/\s+/ms',
                    ' ',
                    Text::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean($buffer))), 255)
                );
                dcCore::app()->admin->tb_excerpt = $buffer;
            }
        }
        if (isset($_REQUEST['section']) && $_REQUEST['section'] == 'trackbacks') {
            $anchor = 'trackbacks';
        } else {
            $anchor = 'comments';
        }

        dcCore::app()->admin->comments_actions_page = new ActionsComments(
            dcCore::app()->admin->url->get('admin.post'),
            [
                'id'            => dcCore::app()->admin->post_id,
                'action_anchor' => $anchor,
                'section'       => $anchor,
            ]
        );

        if (dcCore::app()->admin->comments_actions_page->process()) {
            return self::status(false);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (!empty($_POST['ping'])) {
            // Ping blogs

            if (!empty($_POST['tb_urls']) && dcCore::app()->admin->post_id && dcCore::app()->admin->post_status == dcBlog::POST_PUBLISHED && dcCore::app()->admin->can_edit_post) {
                dcCore::app()->admin->tb_urls = $_POST['tb_urls'];
                dcCore::app()->admin->tb_urls = str_replace("\r", '', dcCore::app()->admin->tb_urls);

                $tb_post_title = Html::escapeHTML(trim(Html::clean(dcCore::app()->admin->post_title)));
                $tb_post_url   = dcCore::app()->admin->post->getURL();

                foreach (explode("\n", dcCore::app()->admin->tb_urls) as $tb_url) {
                    try {
                        # --BEHAVIOR-- adminBeforePingTrackback -- string, string, string, string, string
                        dcCore::app()->behavior->callBehavior(
                            'adminBeforePingTrackback',
                            $tb_url,
                            dcCore::app()->admin->post_id,
                            $tb_post_title,
                            dcCore::app()->admin->tb_excerpt,
                            $tb_post_url
                        );

                        dcCore::app()->admin->tb->ping(
                            $tb_url,
                            (int) dcCore::app()->admin->post_id,
                            $tb_post_title,
                            dcCore::app()->admin->tb_excerpt,
                            $tb_post_url
                        );
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }
                }

                if (!dcCore::app()->error->flag()) {
                    Notices::addSuccessNotice(__('All pings sent.'));
                    dcCore::app()->admin->url->redirect(
                        'admin.post',
                        ['id' => dcCore::app()->admin->post_id, 'tb' => '1']
                    );
                }
            }
        } elseif (!empty($_POST) && dcCore::app()->admin->can_edit_post) {
            // Format excerpt and content

            dcCore::app()->admin->post_format  = $_POST['post_format'];
            dcCore::app()->admin->post_excerpt = $_POST['post_excerpt'];
            dcCore::app()->admin->post_content = $_POST['post_content'];

            dcCore::app()->admin->post_title = $_POST['post_title'];

            dcCore::app()->admin->cat_id = (int) $_POST['cat_id'];

            if (isset($_POST['post_status'])) {
                dcCore::app()->admin->post_status = (int) $_POST['post_status'];
            }

            if (empty($_POST['post_dt'])) {
                dcCore::app()->admin->post_dt = '';
            } else {
                try {
                    dcCore::app()->admin->post_dt = strtotime($_POST['post_dt']);
                    if (!dcCore::app()->admin->post_dt || dcCore::app()->admin->post_dt == -1) {
                        dcCore::app()->admin->bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }
                    dcCore::app()->admin->post_dt = date('Y-m-d H:i', dcCore::app()->admin->post_dt);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            dcCore::app()->admin->post_open_comment = !empty($_POST['post_open_comment']);
            dcCore::app()->admin->post_open_tb      = !empty($_POST['post_open_tb']);
            dcCore::app()->admin->post_selected     = !empty($_POST['post_selected']);
            dcCore::app()->admin->post_lang         = $_POST['post_lang'];
            dcCore::app()->admin->post_password     = !empty($_POST['post_password']) ? $_POST['post_password'] : null;

            dcCore::app()->admin->post_notes = $_POST['post_notes'];

            if (isset($_POST['post_url'])) {
                dcCore::app()->admin->post_url = $_POST['post_url'];
            }

            [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
            ] = [
                dcCore::app()->admin->post_excerpt,
                dcCore::app()->admin->post_excerpt_xhtml,
                dcCore::app()->admin->post_content,
                dcCore::app()->admin->post_content_xhtml,
            ];

            dcCore::app()->blog->setPostContent(
                dcCore::app()->admin->post_id,
                dcCore::app()->admin->post_format,
                dcCore::app()->admin->post_lang,
                $post_excerpt,
                $post_excerpt_xhtml,
                $post_content,
                $post_content_xhtml
            );

            [
                dcCore::app()->admin->post_excerpt,
                dcCore::app()->admin->post_excerpt_xhtml,
                dcCore::app()->admin->post_content,
                dcCore::app()->admin->post_content_xhtml
            ] = [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml,
            ];
        }

        if (!empty($_POST['delete']) && dcCore::app()->admin->can_delete) {
            // Delete post

            try {
                # --BEHAVIOR-- adminBeforePostDelete -- string|int
                dcCore::app()->behavior->callBehavior('adminBeforePostDelete', dcCore::app()->admin->post_id);
                dcCore::app()->blog->delPost(dcCore::app()->admin->post_id);
                dcCore::app()->admin->url->redirect('admin.posts');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST) && !empty($_POST['save']) && dcCore::app()->admin->can_edit_post && !dcCore::app()->admin->bad_dt) {
            // Create or update post

            if (!empty($_POST['new_cat_title']) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CATEGORIES,
            ]), dcCore::app()->blog->id)) {
                // Create category

                $cur_cat = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME);

                $cur_cat->cat_title = $_POST['new_cat_title'];
                $cur_cat->cat_url   = '';

                $parent_cat = !empty($_POST['new_cat_parent']) ? $_POST['new_cat_parent'] : '';

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                dcCore::app()->behavior->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                dcCore::app()->admin->cat_id = dcCore::app()->blog->addCategory($cur_cat, (int) $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string|int
                dcCore::app()->behavior->callBehavior('adminAfterCategoryCreate', $cur_cat, dcCore::app()->admin->cat_id);
            }

            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);

            $cur->cat_id  = (dcCore::app()->admin->cat_id ?: null);
            $cur->post_dt = dcCore::app()->admin->post_dt ?
                date('Y-m-d H:i:00', strtotime(dcCore::app()->admin->post_dt)) :
                '';
            $cur->post_format        = dcCore::app()->admin->post_format;
            $cur->post_password      = dcCore::app()->admin->post_password;
            $cur->post_lang          = dcCore::app()->admin->post_lang;
            $cur->post_title         = dcCore::app()->admin->post_title;
            $cur->post_excerpt       = dcCore::app()->admin->post_excerpt;
            $cur->post_excerpt_xhtml = dcCore::app()->admin->post_excerpt_xhtml;
            $cur->post_content       = dcCore::app()->admin->post_content;
            $cur->post_content_xhtml = dcCore::app()->admin->post_content_xhtml;
            $cur->post_notes         = dcCore::app()->admin->post_notes;
            $cur->post_status        = dcCore::app()->admin->post_status;
            $cur->post_selected      = (int) dcCore::app()->admin->post_selected;
            $cur->post_open_comment  = (int) dcCore::app()->admin->post_open_comment;
            $cur->post_open_tb       = (int) dcCore::app()->admin->post_open_tb;

            if (isset($_POST['post_url'])) {
                $cur->post_url = dcCore::app()->admin->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (dcCore::app()->admin->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePostUpdate -- Cursor, int
                    dcCore::app()->behavior->callBehavior('adminBeforePostUpdate', $cur, (int) dcCore::app()->admin->post_id);

                    dcCore::app()->blog->updPost(dcCore::app()->admin->post_id, $cur);

                    # --BEHAVIOR-- adminAfterPostUpdate -- Cursor, int
                    dcCore::app()->behavior->callBehavior('adminAfterPostUpdate', $cur, (int) dcCore::app()->admin->post_id);
                    Notices::addSuccessNotice(sprintf(__('The post "%s" has been successfully updated'), Html::escapeHTML(trim(Html::clean($cur->post_title)))));
                    dcCore::app()->admin->url->redirect(
                        'admin.post',
                        ['id' => dcCore::app()->admin->post_id]
                    );
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            } else {
                $cur->user_id = dcCore::app()->auth->userID();

                try {
                    # --BEHAVIOR-- adminBeforePostCreate -- Cursor
                    dcCore::app()->behavior->callBehavior('adminBeforePostCreate', $cur);

                    $return_id = dcCore::app()->blog->addPost($cur);

                    # --BEHAVIOR-- adminAfterPostCreate -- Cursor, int
                    dcCore::app()->behavior->callBehavior('adminAfterPostCreate', $cur, $return_id);

                    Notices::addSuccessNotice(__('Entry has been successfully created.'));
                    dcCore::app()->admin->url->redirect(
                        'admin.post',
                        ['id' => $return_id]
                    );
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        // Getting categories (a new category may have been created during process)
        dcCore::app()->admin->categories_combo = Combos::getCategoriesCombo(
            dcCore::app()->blog->getCategories()
        );

        return true;
    }

    public static function render(): void
    {
        dcCore::app()->admin->default_tab = 'edit-entry';
        if (!dcCore::app()->admin->can_edit_post) {
            dcCore::app()->admin->default_tab = '';
        }
        if (!empty($_GET['co'])) {
            dcCore::app()->admin->default_tab = 'comments';
        } elseif (!empty($_GET['tb'])) {
            dcCore::app()->admin->default_tab = 'trackbacks';
        }

        if (dcCore::app()->admin->post_id) {
            $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

            $img_status = match ((int) dcCore::app()->admin->post_status) {
                dcBlog::POST_PUBLISHED   => sprintf($img_status_pattern, __('Published'), 'check-on.png'),
                dcBlog::POST_UNPUBLISHED => sprintf($img_status_pattern, __('Unpublished'), 'check-off.png'),
                dcBlog::POST_SCHEDULED   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png'),
                dcBlog::POST_PENDING     => sprintf($img_status_pattern, __('Pending'), 'check-wrn.png'),
                default                  => '',
            };

            $edit_entry_str  = __('&ldquo;%s&rdquo;');
            $page_title_edit = sprintf($edit_entry_str, Html::escapeHTML(trim(Html::clean(dcCore::app()->admin->post_title)))) . ' ' . $img_status;
        } else {
            $img_status      = '';
            $page_title_edit = '';
        }

        $admin_post_behavior = '';
        if (dcCore::app()->admin->post_editor) {
            $p_edit = $c_edit = '';
            if (!empty(dcCore::app()->admin->post_editor[dcCore::app()->admin->post_format])) {
                $p_edit = dcCore::app()->admin->post_editor[dcCore::app()->admin->post_format];
            }
            if (!empty(dcCore::app()->admin->post_editor['xhtml'])) {
                $c_edit = dcCore::app()->admin->post_editor['xhtml'];
            }
            if ($p_edit == $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= dcCore::app()->behavior->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    dcCore::app()->admin->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= dcCore::app()->behavior->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content'],
                    dcCore::app()->admin->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= dcCore::app()->behavior->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        Page::open(
            dcCore::app()->admin->page_title . ' - ' . __('Posts'),
            Page::jsModal() .
            Page::jsMetaEditor() .
            $admin_post_behavior .
            Page::jsLoad('js/_post.js') .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPostHeaders --
            dcCore::app()->behavior->callBehavior('adminPostHeaders') .
            Page::jsPageTabs(dcCore::app()->admin->default_tab) .
            dcCore::app()->admin->next_headlink . "\n" . dcCore::app()->admin->prev_headlink,
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Posts')                                 => dcCore::app()->admin->url->get('admin.posts'),
                    (dcCore::app()->admin->post_id ?
                        $page_title_edit :
                        dcCore::app()->admin->page_title) => '',
                ]
            ),
            [
                'x-frame-allow' => dcCore::app()->blog->url,
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
            dcCore::app()->admin->post_excerpt = dcCore::app()->admin->post_excerpt_xhtml;
            dcCore::app()->admin->post_content = dcCore::app()->admin->post_content_xhtml;
            dcCore::app()->admin->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        if (dcCore::app()->admin->post_id && dcCore::app()->admin->post->post_status == dcBlog::POST_PUBLISHED) {
            echo
            '<p><a class="onblog_link outgoing" href="' . dcCore::app()->admin->post->getURL() . '" title="' . Html::escapeHTML(trim(Html::clean(dcCore::app()->admin->post_title))) . '">' . __('Go to this entry on the site') . ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
        }
        if (dcCore::app()->admin->post_id) {
            echo
            '<p class="nav_prevnext">';
            if (dcCore::app()->admin->prev_link) {
                echo
                dcCore::app()->admin->prev_link;
            }
            if (dcCore::app()->admin->next_link && dcCore::app()->admin->prev_link) {
                echo
                ' | ';
            }
            if (dcCore::app()->admin->next_link) {
                echo
                dcCore::app()->admin->next_link;
            }

            # --BEHAVIOR-- adminPostNavLinks -- MetaRecord|null, string
            dcCore::app()->behavior->callBehavior('adminPostNavLinks', dcCore::app()->admin->post ?? null, 'post');

            echo
            '</p>';
        }

        // Exit if we cannot view page
        if (!dcCore::app()->admin->can_view_page) {
            Page::helpBlock('core_post');
            Page::close();
            exit;
        }

        /* Post form if we can edit post
        -------------------------------------------------------- */
        if (dcCore::app()->admin->can_edit_post) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => '<p class="entry-status"><label for="post_status">' . __('Entry status') . ' ' . $img_status . '</label>' .
                        form::combo(
                            'post_status',
                            dcCore::app()->admin->status_combo,
                            ['default' => dcCore::app()->admin->post_status, 'class' => 'maximal', 'disabled' => !dcCore::app()->admin->can_publish]
                        ) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::datetime('post_dt', [
                            'default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(dcCore::app()->admin->post_dt))),
                            'class'   => (dcCore::app()->admin->bad_dt ? 'invalid' : ''),
                        ]) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Entry language') . '</label>' .
                        form::combo('post_lang', dcCore::app()->admin->lang_combo, dcCore::app()->admin->post_lang) .
                        '</p>',
                        'post_format' => '<div>' .
                        '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                        '<p>' . form::combo('post_format', dcCore::app()->admin->available_formats, dcCore::app()->admin->post_format, 'maximal') . '</p>' .
                        '<p class="format_control control_no_xhtml">' .
                        '<a id="convert-xhtml" class="button' . (dcCore::app()->admin->post_id && dcCore::app()->admin->post_format != 'wiki' ? ' hide' : '') . '" href="' .
                        dcCore::app()->admin->url->get('admin.post', ['id' => dcCore::app()->admin->post_id, 'xconv' => '1']) .
                        '">' .
                        __('Convert to HTML') . '</a></p></div>',
                    ],
                ],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_selected' => '<p><label for="post_selected" class="classic">' .
                        form::checkbox('post_selected', 1, dcCore::app()->admin->post_selected) . ' ' .
                        __('Selected entry') . '</label></p>',
                        'cat_id' => '<div>' .
                        '<h5 id="label_cat_id">' . __('Category') . '</h5>' .
                        '<p><label for="cat_id">' . __('Category:') . '</label>' .
                        form::combo('cat_id', dcCore::app()->admin->categories_combo, dcCore::app()->admin->cat_id, 'maximal') .
                        '</p>' .
                        (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                            dcAuth::PERMISSION_CATEGORIES,
                        ]), dcCore::app()->blog->id) ?
                            '<div>' .
                            '<h5 id="create_cat">' . __('Add a new category') . '</h5>' .
                            '<p><label for="new_cat_title">' . __('Title:') . ' ' .
                            form::field('new_cat_title', 30, 255, ['class' => 'maximal']) . '</label></p>' .
                            '<p><label for="new_cat_parent">' . __('Parent:') . ' ' .
                            form::combo('new_cat_parent', dcCore::app()->admin->categories_combo, '', 'maximal') .
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
                        form::checkbox('post_open_comment', 1, dcCore::app()->admin->post_open_comment) . ' ' .
                        __('Accept comments') . '</label></p>' .
                        (dcCore::app()->blog->settings->system->allow_comments ?
                            (self::isContributionAllowed(dcCore::app()->admin->post_id, strtotime(dcCore::app()->admin->post_dt), true) ? '' : '<p class="form-note warn">' .
                            __('Warning: Comments are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' .
                            __('Comments are not accepted on this blog so far.') . '</p>') .
                        '<p><label for="post_open_tb" class="classic">' .
                        form::checkbox('post_open_tb', 1, dcCore::app()->admin->post_open_tb) . ' ' .
                        __('Accept trackbacks') . '</label></p>' .
                        (dcCore::app()->blog->settings->system->allow_trackbacks ?
                            (self::isContributionAllowed(dcCore::app()->admin->post_id, strtotime(dcCore::app()->admin->post_dt), false) ? '' : '<p class="form-note warn">' .
                            __('Warning: Trackbacks are not more accepted for this entry.') . '</p>') :
                            '<p class="form-note warn">' . __('Trackbacks are not accepted on this blog so far.') . '</p>') .
                        '</div>',
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, Html::escapeHTML(dcCore::app()->admin->post_password), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, Html::escapeHTML(dcCore::app()->admin->post_url), 'maximal') .
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
                        'default'    => Html::escapeHTML(dcCore::app()->admin->post_title),
                        'class'      => 'maximal',
                        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . dcCore::app()->admin->post_lang . '" spellcheck="true"',
                    ]) .
                    '</p>',

                    'post_excerpt' => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
                    __('Introduction to the post.') . '</span></label> ' .
                    form::textarea(
                        'post_excerpt',
                        50,
                        5,
                        [
                            'default'    => Html::escapeHTML(dcCore::app()->admin->post_excerpt),
                            'extra_html' => 'lang="' . dcCore::app()->admin->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',

                    'post_content' => '<p class="area" id="content-area"><label class="required bold" ' .
                    'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
                    form::textarea(
                        'post_content',
                        50,
                        dcCore::app()->auth->getOption('edit_size'),
                        [
                            'default'    => Html::escapeHTML(dcCore::app()->admin->post_content),
                            'extra_html' => 'required placeholder="' . __('Content') . '" lang="' . dcCore::app()->admin->post_lang . '" spellcheck="true"',
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
                            'default'    => Html::escapeHTML(dcCore::app()->admin->post_notes),
                            'extra_html' => 'lang="' . dcCore::app()->admin->post_lang . '" spellcheck="true"',
                        ]
                    ) .
                    '</p>',
                ]
            );

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null, string
            dcCore::app()->behavior->callBehavior('adminPostFormItems', $main_items, $sidebar_items, dcCore::app()->admin->post ?? null, 'post');

            echo
            '<div class="multi-part" title="' . (dcCore::app()->admin->post_id ? __('Edit post') : __('New post')) .
            sprintf(' &rsaquo; %s', dcCore::app()->formater->getFormaterName(dcCore::app()->admin->post_format)) . '" id="edit-entry">' .
            '<form action="' . dcCore::app()->admin->url->get('admin.post') . '" method="post" id="entry-form">' .
            '<div id="entry-wrapper">' .
            '<div id="entry-content"><div class="constrained">' .
            '<h3 class="out-of-screen-if-js">' . __('Edit post') . '</h3>';

            foreach ($main_items as $id => $item) {
                echo $item;
            }

            # --BEHAVIOR-- adminPostForm (may be deprecated) -- MetaRecord|null, string
            dcCore::app()->behavior->callBehavior('adminPostForm', dcCore::app()->admin->post ?? null, 'post');

            echo
            '<p class="border-top">' .
            (dcCore::app()->admin->post_id ? form::hidden('id', dcCore::app()->admin->post_id) : '') .
            '<input type="submit" value="' . __('Save') . ' (s)" ' .
            'accesskey="s" name="save" /> ';

            if (dcCore::app()->admin->post_id) {
                $preview_url = dcCore::app()->blog->url . dcCore::app()->url->getURLFor('preview', dcCore::app()->auth->userID() . '/' . Http::browserUID(DC_MASTER_KEY . dcCore::app()->auth->userID() . dcCore::app()->auth->cryptLegacy(dcCore::app()->auth->userID())) . '/' . dcCore::app()->admin->post->post_url);

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = dcCore::app()->auth->user_prefs->interface->blank_preview;

                $preview_class  = $blank_preview ? '' : ' modal';
                $preview_target = $blank_preview ? '' : ' target="_blank"';

                echo
                '<a id="post-preview" href="' . $preview_url . '" class="button' . $preview_class . '" accesskey="p"' . $preview_target . '>' . __('Preview') . ' (p)' . '</a>' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';
            } else {
                echo
                '<a id="post-cancel" href="' . dcCore::app()->admin->url->get('admin.home') . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
            }

            echo(dcCore::app()->admin->can_delete ? ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' : '') .
            dcCore::app()->nonce->getFormNonce() .
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
            dcCore::app()->behavior->callBehavior('adminPostFormSidebar', dcCore::app()->admin->post ?? null, 'post');

            echo
            '</div>' . // End #entry-sidebar
            '</form>';

            # --BEHAVIOR-- adminPostAfterForm -- MetaRecord|null, string
            dcCore::app()->behavior->callBehavior('adminPostAfterForm', dcCore::app()->admin->post ?? null, 'post');

            echo
            '</div>';
        }

        if (dcCore::app()->admin->post_id) {
            // Comments

            $params = ['post_id' => dcCore::app()->admin->post_id, 'order' => 'comment_dt ASC'];

            $comments = dcCore::app()->blog->getComments(array_merge($params, ['comment_trackback' => 0]));

            $combo_action = dcCore::app()->admin->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$comments->isEmpty();

            echo
            '<div id="comments" class="clear multi-part" title="' . __('Comments') . '">' .
            '<p class="top-add"><a class="button add" href="#comment-form">' . __('Add a comment') . '</a></p>';

            if ($has_action) {
                echo
                '<form action="' . dcCore::app()->admin->url->get('admin.post') . '" id="form-comments" method="post">';
            }

            echo
            '<h3>' . __('Comments') . '</h3>';
            if (!$comments->isEmpty()) {
                self::showComments($comments, $has_action, false, dcCore::app()->admin->show_ip);
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
                form::hidden(['id'], dcCore::app()->admin->post_id) .
                dcCore::app()->nonce->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '" /></p>' .
                '</div>' .
                '</form>';
            }

            // Add a comment

            echo
            '<div class="fieldset clear">' .
            '<h3>' . __('Add a comment') . '</h3>' .

            '<form action="' . dcCore::app()->admin->url->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="constrained">' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(dcCore::app()->auth->getInfo('user_cn')),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', 30, 255, Html::escapeHTML(dcCore::app()->auth->getInfo('user_email'))) .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', 30, 255, Html::escapeHTML(dcCore::app()->auth->getInfo('user_url'))) .
            '</p>' .

            '<p class="area"><label for="comment_content" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('Comment:') . '</label> ' .
            form::textarea(
                'comment_content',
                50,
                8,
                ['extra_html' => 'required placeholder="' . __('Comment') . '" lang="' . dcCore::app()->auth->getInfo('user_lang') . '" spellcheck="true"']
            ) .
            '</p>' .

            '<p>' .
            form::hidden('post_id', dcCore::app()->admin->post_id) .
            dcCore::app()->nonce->getFormNonce() .
            '<input type="submit" name="add" value="' . __('Save') . '" /></p>' .
            '</div>' . #constrained

            '</form>' .
            '</div>' . #add comment
            '</div>'; #comments
        }

        if (dcCore::app()->admin->post_id && dcCore::app()->admin->post_status == dcBlog::POST_PUBLISHED) {
            // Trackbacks

            $params     = ['post_id' => dcCore::app()->admin->post_id, 'order' => 'comment_dt ASC'];
            $trackbacks = dcCore::app()->blog->getComments(array_merge($params, ['comment_trackback' => 1]));

            // Actions combo box
            $combo_action = dcCore::app()->admin->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && !$trackbacks->isEmpty();

            if (!empty($_GET['tb_auto'])) {
                dcCore::app()->admin->tb_urls = implode("\n", dcCore::app()->admin->tb->discover(dcCore::app()->admin->post_excerpt_xhtml . ' ' . dcCore::app()->admin->post_content_xhtml));
            }

            echo
            '<div id="trackbacks" class="clear multi-part" title="' . __('Trackbacks') . '">';

            if ($has_action) {
                // tracbacks actions
                echo
                '<form action="' . dcCore::app()->admin->url->get('admin.post') . '" id="form-trackbacks" method="post">';
            }

            echo
            '<h3>' . __('Trackbacks received') . '</h3>';

            if (!$trackbacks->isEmpty()) {
                self::showComments($trackbacks, $has_action, true, dcCore::app()->admin->show_ip);
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
                form::hidden('id', dcCore::app()->admin->post_id) .
                form::hidden(['section'], 'trackbacks') .
                dcCore::app()->nonce->getFormNonce() .
                '<input type="submit" value="' . __('ok') . '" /></p>' .
                '</div>' .
                '</form>';
            }

            if (dcCore::app()->admin->can_edit_post) {
                // Add trackbacks

                echo
                '<div class="fieldset clear">';

                echo
                '<h3>' . __('Ping blogs') . '</h3>' .
                '<form action="' . dcCore::app()->admin->url->get('admin.post', ['id' => dcCore::app()->admin->post_id]) . '" id="trackback-form" method="post">' .
                '<p><label for="tb_urls" class="area">' . __('URLs to ping:') . '</label>' .
                form::textarea('tb_urls', 60, 5, dcCore::app()->admin->tb_urls) .
                '</p>' .

                '<p><label for="tb_excerpt" class="area">' . __('Excerpt to send:') . '</label>' .
                form::textarea('tb_excerpt', 60, 5, dcCore::app()->admin->tb_excerpt) . '</p>' .

                '<p>' .
                dcCore::app()->nonce->getFormNonce() .
                '<input type="submit" name="ping" value="' . __('Ping blogs') . '" />' .
                (empty($_GET['tb_auto']) ? '&nbsp;&nbsp;<a class="button" href="' . dcCore::app()->admin->url->get('admin.post', ['id' => dcCore::app()->admin->post_id, 'tb_auto' => 1, 'tb' => 1]) . '">' . __('Auto discover ping URLs') . '</a>' :
                    '') .
                '</p>' .
                '</form>';

                $pings = dcCore::app()->admin->tb->getPostPings((int) dcCore::app()->admin->post_id);
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
            if ((dcCore::app()->blog->settings->system->comments_ttl == 0) || (time() - dcCore::app()->blog->settings->system->comments_ttl * 86400 < $dt)) {
                return true;
            }
        } else {
            if ((dcCore::app()->blog->settings->system->trackbacks_ttl == 0) || (time() - dcCore::app()->blog->settings->system->trackbacks_ttl * 86400 < $dt)) {
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
            (dcCore::app()->admin->show_ip ? '<th class="nowrap">' . __('IP address') . '</th>' : '') .
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
            $comment_url = dcCore::app()->admin->url->get('admin.comment', ['id' => $rs->comment_id]);

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
                '<time datetime="' . Date::iso8601(strtotime($rs->comment_dt), dcCore::app()->auth->getInfo('user_tz')) . '">' .
                Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt) .
                '</time>' .
            '</td>';

            if ($show_ip) {
                echo
                '<td class="nowrap"><a href="' . dcCore::app()->admin->url->get('admin.comments', ['ip' => $rs->comment_ip]) . '">' . $rs->comment_ip . '</a></td>';
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
