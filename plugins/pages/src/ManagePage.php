<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use dcBlog;
use dcCore;
use dcMedia;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;
use UnhandledMatchError;

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
        Page::check(Core::auth()->makePermissions([
            My::PERMISSION_PAGES,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        Date::setTZ(Core::auth()->getInfo('user_tz') ?? 'UTC');

        Core::backend()->post_id            = '';
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
        Core::backend()->post_position      = 0;
        Core::backend()->post_open_comment  = false;
        Core::backend()->post_open_tb       = false;
        Core::backend()->post_selected      = false;

        Core::backend()->post_media = [];

        Core::backend()->page_title = __('New page');

        Core::backend()->can_view_page = true;
        Core::backend()->can_edit_page = Core::auth()->check(Core::auth()->makePermissions([
            My::PERMISSION_PAGES,
            Core::auth()::PERMISSION_USAGE,
        ]), Core::blog()->id);
        Core::backend()->can_publish = Core::auth()->check(Core::auth()->makePermissions([
            My::PERMISSION_PAGES,
            Core::auth()::PERMISSION_PUBLISH,
            Core::auth()::PERMISSION_CONTENT_ADMIN,
        ]), Core::blog()->id);
        Core::backend()->can_delete = false;

        $post_headlink = '<link rel="%s" title="%s" href="' . My::manageUrl(['act' => 'page', 'id' => '%s']) . '" />';

        Core::backend()->post_link = '<a href="' . My::manageUrl(['act' => 'page', 'id' => '%s']) . '" title="%s">%s</a>';

        Core::backend()->next_link = Core::backend()->prev_link = Core::backend()->next_headlink = Core::backend()->prev_headlink = null;

        // If user can't publish
        if (!Core::backend()->can_publish) {
            Core::backend()->post_status = dcBlog::POST_PENDING;
        }

        // Status combo
        Core::backend()->status_combo = Combos::getPostStatusesCombo();

        // Formaters combo
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

        // Get page informations

        Core::backend()->post = null;
        if (!empty($_REQUEST['id'])) {
            $params['post_type'] = 'page';
            $params['post_id']   = $_REQUEST['id'];

            Core::backend()->post = Core::blog()->getPosts($params);

            if (Core::backend()->post->isEmpty()) {
                dcCore::app()->error->add(__('This page does not exist.'));
                Core::backend()->can_view_page = false;
            } else {
                Core::backend()->post_id            = (int) Core::backend()->post->post_id;
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
                Core::backend()->post_position      = (int) Core::backend()->post->post_position;
                Core::backend()->post_open_comment  = (bool) Core::backend()->post->post_open_comment;
                Core::backend()->post_open_tb       = (bool) Core::backend()->post->post_open_tb;
                Core::backend()->post_selected      = (bool) Core::backend()->post->post_selected;

                Core::backend()->page_title = __('Edit page');

                Core::backend()->can_edit_page = Core::backend()->post->isEditable();
                Core::backend()->can_delete    = Core::backend()->post->isDeletable();

                $next_rs = Core::blog()->getNextPost(Core::backend()->post, 1);
                $prev_rs = Core::blog()->getNextPost(Core::backend()->post, -1);

                if ($next_rs !== null) {
                    Core::backend()->next_link = sprintf(
                        Core::backend()->post_link,
                        $next_rs->post_id,
                        Html::escapeHTML(trim(Html::clean($next_rs->post_title))),
                        __('Next page') . '&nbsp;&#187;'
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
                        '&#171;&nbsp;' . __('Previous page')
                    );
                    Core::backend()->prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->post_title))),
                        $prev_rs->post_id
                    );
                }

                try {
                    dcCore::app()->media             = new dcMedia();
                    Core::backend()->post_media = dcCore::app()->media->getPostMedia(Core::backend()->post_id);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        Core::backend()->comments_actions_page = new BackendActionsComments(
            My::manageUrl([], '&'),
            [
                'act'           => 'page',
                'id'            => Core::backend()->post_id,
                'action_anchor' => 'comments',
                'section'       => 'comments',
            ]
        );

        Core::backend()->comments_actions_page_rendered = null;
        if (Core::backend()->comments_actions_page->process()) {
            Core::backend()->comments_actions_page_rendered = true;

            return true;
        }

        if (!empty($_POST) && Core::backend()->can_edit_page) {
            // Format content

            Core::backend()->post_format  = $_POST['post_format'];
            Core::backend()->post_excerpt = $_POST['post_excerpt'];
            Core::backend()->post_content = $_POST['post_content'];

            Core::backend()->post_title = $_POST['post_title'];

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
                    dcCore::app()->error->add($e->getMessage());
                }
            }

            Core::backend()->post_open_comment = !empty($_POST['post_open_comment']);
            Core::backend()->post_open_tb      = !empty($_POST['post_open_tb']);
            Core::backend()->post_selected     = !empty($_POST['post_selected']);
            Core::backend()->post_lang         = $_POST['post_lang'];
            Core::backend()->post_password     = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
            Core::backend()->post_position     = (int) $_POST['post_position'];

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
            // Delete page

            try {
                # --BEHAVIOR-- adminBeforePageDelete -- int
                Core::behavior()->callBehavior('adminBeforePageDelete', Core::backend()->post_id);
                Core::blog()->delPost(Core::backend()->post_id);
                My::redirect();
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST) && !empty($_POST['save']) && Core::backend()->can_edit_page && !Core::backend()->bad_dt) {
            // Create or update page

            $cur = Core::con()->openCursor(Core::con()->prefix() . dcBlog::POST_TABLE_NAME);

            // Magic tweak :)
            Core::blog()->settings->system->post_url_format = '{t}';

            $cur->post_type          = 'page';
            $cur->post_dt            = Core::backend()->post_dt ? date('Y-m-d H:i:00', strtotime(Core::backend()->post_dt)) : '';
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
            $cur->post_position      = Core::backend()->post_position;
            $cur->post_open_comment  = (int) Core::backend()->post_open_comment;
            $cur->post_open_tb       = (int) Core::backend()->post_open_tb;
            $cur->post_selected      = (int) Core::backend()->post_selected;

            if (isset($_POST['post_url'])) {
                $cur->post_url = Core::backend()->post_url;
            }

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (Core::backend()->post_id) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePageUpdate -- Cursor, int
                    Core::behavior()->callBehavior('adminBeforePageUpdate', $cur, Core::backend()->post_id);

                    Core::blog()->updPost(Core::backend()->post_id, $cur);

                    # --BEHAVIOR-- adminAfterPageUpdate -- Cursor, int
                    Core::behavior()->callBehavior('adminAfterPageUpdate', $cur, Core::backend()->post_id);

                    My::redirect(['act' => 'page', 'id' => Core::backend()->post_id, 'upd' => '1']);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            } else {
                $cur->user_id = Core::auth()->userID();

                try {
                    # --BEHAVIOR-- adminBeforePageCreate -- Cursor
                    Core::behavior()->callBehavior('adminBeforePageCreate', $cur);

                    $return_id = Core::blog()->addPost($cur);

                    # --BEHAVIOR-- adminAfterPageCreate -- Cursor, int
                    Core::behavior()->callBehavior('adminAfterPageCreate', $cur, $return_id);

                    My::redirect(['act' => 'page', 'id' => $return_id, 'crea' => '1']);
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        if (Core::backend()->comments_actions_page_rendered) {
            Core::backend()->comments_actions_page->render();

            return;
        }

        Core::backend()->default_tab = 'edit-entry';
        if (!Core::backend()->can_edit_page) {
            Core::backend()->default_tab = '';
        }
        if (!empty($_GET['co'])) {
            Core::backend()->default_tab = 'comments';
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
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    Core::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content'],
                    Core::backend()->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
                $admin_post_behavior .= Core::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        Page::openModule(
            Core::backend()->page_title . ' - ' . My::name(),
            Page::jsModal() .
            Page::jsJson('pages_page', ['confirm_delete_post' => __('Are you sure you want to delete this page?')]) .
            Page::jsLoad('js/_post.js') .
            My::jsLoad('page') .
            $admin_post_behavior .
            Page::jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPageHeaders --
            Core::behavior()->callBehavior('adminPageHeaders') .
            Page::jsPageTabs(Core::backend()->default_tab) .
            Core::backend()->next_headlink . "\n" . Core::backend()->prev_headlink
        );

        $img_status         = '';
        $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

        if (Core::backend()->post_id) {
            try {
                $img_status = match (Core::backend()->post_status) {
                    dcBlog::POST_PUBLISHED   => sprintf($img_status_pattern, __('Published'), 'check-on.png'),
                    dcBlog::POST_UNPUBLISHED => sprintf($img_status_pattern, __('Unpublished'), 'check-off.png'),
                    dcBlog::POST_SCHEDULED   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png'),
                    dcBlog::POST_PENDING     => sprintf($img_status_pattern, __('Pending'), 'check-wrn.png'),
                };
            } catch (UnhandledMatchError) {
            }
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean(Core::backend()->post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $edit_entry_title = Core::backend()->page_title;
        }
        echo Page::breadcrumb(
            [
                Html::escapeHTML(Core::blog()->name) => '',
                My::name()                                  => Core::backend()->getPageURL(),
                $edit_entry_title                           => '',
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
            Core::backend()->post_excerpt = Core::backend()->post_excerpt_xhtml;
            Core::backend()->post_content = Core::backend()->post_content_xhtml;
            Core::backend()->post_format  = 'xhtml';

            Notices::message(__('Don\'t forget to validate your HTML conversion by saving your post.'));
        }

        if (Core::backend()->post_id && Core::backend()->post->post_status == dcBlog::POST_PUBLISHED) {
            echo
            '<p><a class="onblog_link outgoing" href="' . Core::backend()->post->getURL() . '" title="' . Html::escapeHTML(trim(Html::clean(Core::backend()->post_title))) . '">' . __('Go to this page on the site') . ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
        }

        echo '';

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

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            Core::behavior()->callBehavior('adminPageNavLinks', Core::backend()->post ?? null);

            echo
            '</p>';
        }

        # Exit if we cannot view page
        if (!Core::backend()->can_view_page) {
            Page::closeModule();

            return;
        }

        /* Post form if we can edit page
        -------------------------------------------------------- */
        if (Core::backend()->can_edit_page) {
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => '<p><label for="post_status">' . __('Page status') . '</label> ' .
                        form::combo(
                            'post_status',
                            Core::backend()->status_combo,
                            ['default' => Core::backend()->post_status, 'disabled' => !Core::backend()->can_publish]
                        ) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::datetime('post_dt', [
                            'default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(Core::backend()->post_dt))),
                            'class'   => (Core::backend()->bad_dt ? 'invalid' : ''),
                        ]) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Page language') . '</label>' .
                        form::combo('post_lang', Core::backend()->lang_combo, Core::backend()->post_lang) .
                        '</p>',
                        'post_format' => '<div>' .
                        '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                        '<p>' . form::combo('post_format', Core::backend()->available_formats, Core::backend()->post_format, 'maximal') . '</p>' .
                        '<p class="format_control control_wiki">' .
                        '<a id="convert-xhtml" class="button' . (Core::backend()->post_id && Core::backend()->post_format != 'wiki' ? ' hide' : '') .
                        '" href="' . My::manageUrl(['act' => 'page', 'id' => Core::backend()->post_id, 'xconv' => '1']) . '">' .
                        __('Convert to HTML') . '</a></p></div>', ], ],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_position' => '<p><label for="post_position" class="classic">' . __('Page position') . '</label> ' .
                        form::number('post_position', [
                            'default' => Core::backend()->post_position,
                        ]) .
                        '</p>', ], ],
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
                        'post_hide' => '<p><label for="post_selected" class="classic">' . form::checkbox('post_selected', 1, Core::backend()->post_selected) . ' ' .
                        __('Hide in widget Pages') . '</label>' .
                        '</p>',
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, Html::escapeHTML(Core::backend()->post_password), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, Html::escapeHTML(Core::backend()->post_url), 'maximal') .
                        '</p>' .
                        '<p class="form-note warn">' .
                        __('Warning: If you set the URL manually, it may conflict with another page.') .
                        '</p></div>',
                    ], ], ]);
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
                    __('Introduction to the page.') . '</span></label> ' .
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

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null
            Core::behavior()->callBehavior('adminPageFormItems', $main_items, $sidebar_items, Core::backend()->post ?? null);

            echo
            '<div class="multi-part" title="' . (Core::backend()->post_id ? __('Edit page') : __('New page')) .
            sprintf(' &rsaquo; %s', Core::formater()->getFormaterName(Core::backend()->post_format)) . '" id="edit-entry">' .
            '<form action="' . My::manageUrl(['act' => 'page']) . '" method="post" id="entry-form">' .
            '<div id="entry-wrapper">' .
            '<div id="entry-content"><div class="constrained">' .
            '<h3 class="out-of-screen-if-js">' . __('Edit page') . '</h3>';

            foreach ($main_items as $item) {
                echo $item;
            }

            # --BEHAVIOR-- adminPageForm -- MetaRecord|null
            Core::behavior()->callBehavior('adminPageForm', Core::backend()->post ?? null);

            echo
            '<p class="border-top">' .
            (Core::backend()->post_id ? form::hidden('id', Core::backend()->post_id) : '') .
            '<input type="submit" value="' . __('Save') . ' (s)" accesskey="s" name="save" /> ';

            if (Core::backend()->post_id) {
                $preview_url = Core::blog()->url .
                    dcCore::app()->url->getURLFor(
                        'pagespreview',
                        Core::auth()->userID() . '/' .
                        Http::browserUID(DC_MASTER_KEY . Core::auth()->userID() . Core::auth()->cryptLegacy(Core::auth()->userID())) .
                        '/' . Core::backend()->post->post_url
                    );

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

            echo(Core::backend()->can_delete ?
                ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' :
                '') .
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

            # --BEHAVIOR-- adminPageFormSidebar -- MetaRecord|null
            Core::behavior()->callBehavior('adminPageFormSidebar', Core::backend()->post ?? null);

            echo
            '</div>' . // End #entry-sidebar
            '</form>';

            # --BEHAVIOR-- adminPostForm -- MetaRecord|null
            Core::behavior()->callBehavior('adminPageAfterForm', Core::backend()->post ?? null);

            echo
            '</div>'; // End

            if (Core::backend()->post_id && !empty(Core::backend()->post_media)) {
                echo
                '<form action="' . Core::backend()->url->get('admin.post.media') . '" id="attachment-remove-hide" method="post">' .
                '<div>' .
                form::hidden(['post_id'], Core::backend()->post_id) .
                form::hidden(['media_id'], '') .
                form::hidden(['remove'], 1) .
                Core::nonce()->getFormNonce() .
                '</div>' .
                '</form>';
            }
        }

        if (Core::backend()->post_id) {
            // Comments and trackbacks

            $params = ['post_id' => Core::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments   = Core::blog()->getComments(array_merge($params, ['comment_trackback' => 0]));
            $trackbacks = Core::blog()->getComments(array_merge($params, ['comment_trackback' => 1]));

            # Actions combo box
            $combo_action = Core::backend()->comments_actions_page->getCombo();
            $has_action   = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());

            echo
            '<div id="comments" class="multi-part" title="' . __('Comments') . '">';

            echo
            '<p class="top-add"><a class="button add" href="#comment-form">' . __('Add a comment') . '</a></p>';

            if ($has_action) {
                echo
                '<form action="' . My::manageUrl() . '" method="post">';
            }

            echo
            '<h3>' . __('Trackbacks') . '</h3>';

            if (!$trackbacks->isEmpty()) {
                self::showComments($trackbacks, $has_action);
            } else {
                echo
                '<p>' . __('No trackback') . '</p>';
            }

            echo '<h3>' . __('Comments') . '</h3>';
            if (!$comments->isEmpty()) {
                self::showComments($comments, $has_action);
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
                My::parsedHiddenFields([
                    'act'     => 'page',
                    'id'      => Core::backend()->post_id,
                    'co'      => '1',
                    'section' => 'comments',
                    'redir'   => My::manageUrl([
                        'act' => 'page',
                        'id'  => Core::backend()->post_id,
                        'co'  => '1',
                    ]),
                ]) .
                '<input type="submit" value="' . __('ok') . '" /></p>' .
                '</div>' .
                '</form>';
            }

            //Add a comment

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
            form::email('comment_email', [
                'size'         => 30,
                'default'      => Html::escapeHTML(Core::auth()->getInfo('user_email')),
                'autocomplete' => 'email',
            ]) .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', [
                'size'         => 30,
                'default'      => Html::escapeHTML(Core::auth()->getInfo('user_url')),
                'autocomplete' => 'url',
            ]) .
            '</p>' .

            '<p class="area"><label for="comment_content" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('Comment:') . '</label> ' .
            form::textarea('comment_content', 50, 8, ['extra_html' => 'required placeholder="' . __('Comment') . '"']) .
            '</p>' .

            '<p>' . form::hidden('post_id', Core::backend()->post_id) .
            Core::nonce()->getFormNonce() .
            '<input type="submit" name="add" value="' . __('Save') . '" /></p>' .
            '</div>' . #constrained

            '</form>' .
            '</div>' . #add comment
            '</div>'; #comments
        }

        Page::helpBlock('page', 'core_wiki');

        Page::closeModule();
    }

    # Controls comments or trakbacks capabilities

    /**
     * Determines if contribution is allowed.
     *
     * @param      mixed   $id     The identifier
     * @param      mixed   $dt     The date
     * @param      bool    $com    It is comment?
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
     * @param      mixed   $rs          Recordset
     * @param      bool    $has_action  Indicates if action is available
     */
    protected static function showComments($rs, bool $has_action): void
    {
        // IP are available only for super-admin and admin
        $show_ip = Core::auth()->check(
            Core::auth()->makePermissions([
                Core::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            Core::blog()->id
        );

        echo
        '<table class="comments-list"><tr>' .
        '<th colspan="2" class="nowrap first">' . __('Author') . '</th>' .
        '<th>' . __('Date') . '</th>';

        if ($show_ip) {
            echo '<th class="nowrap">' . __('IP address') . '</th>';
        }

        echo
        '<th>' . __('Status') . '</th>' .
        '<th>' . __('Edit') . '</th>' .
        '</tr>';

        while ($rs->fetch()) {
            $comment_url = Core::backend()->url->get('admin.comment', ['id' => $rs->comment_id]);

            $img       = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
            $sts_class = '';
            switch ($rs->comment_status) {
                case 1:
                    $img_status = sprintf($img, __('Published'), 'check-on.png');
                    $sts_class  = 'sts-online';

                    break;
                case 0:
                    $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                    $sts_class  = 'sts-offline';

                    break;
                case -1:
                    $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                    $sts_class  = 'sts-pending';

                    break;
                case -2:
                    $img_status = sprintf($img, __('Junk'), 'junk.png');
                    $sts_class  = 'sts-junk';

                    break;
                default:
                    $img_status = '';

                    break;
            }

            echo
            '<tr class="line ' . ($rs->comment_status != dcBlog::COMMENT_PUBLISHED ? ' offline ' : '') . $sts_class . '" id="c' . $rs->comment_id . '">' .
            '<td class="nowrap">' .
            ($has_action ?
                form::checkbox(
                    ['comments[]'],
                    $rs->comment_id,
                    [
                        'extra_html' => 'title="' . __('Select this comment') . '"',
                    ]
                ) :
                '') .
            '</td>' .
            '<td class="maximal">' . $rs->comment_author . '</td>' .
            '<td class="nowrap">' . Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->comment_dt) . '</td>';

            if ($show_ip) {
                echo
                '<td class="nowrap">' .
                '<a href="' . Core::backend()->url->get('admin.comment', ['ip' => $rs->comment_ip]) . '">' . $rs->comment_ip . '</a>' .
                '</td>';
            }

            echo
            '<td class="nowrap status">' . $img_status . '</td>' .
            '<td class="nowrap status"><a href="' . $comment_url . '">' .
            '<img src="images/edit-mini.png" alt="" title="' . __('Edit this comment') . '" /> ' . __('Edit') . '</a></td>' .
            '</tr>';
        }

        echo
        '</table>';
    }
}
