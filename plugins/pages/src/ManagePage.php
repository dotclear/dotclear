<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;
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

        $post_headlink = '<link rel="%s" title="%s" href="' . My::manageUrl(['act' => 'page', 'id' => '%s']) . '" />';

        App::backend()->post_link = '<a href="' . My::manageUrl(['act' => 'page', 'id' => '%s']) . '" title="%s">%s</a>';

        App::backend()->next_link = App::backend()->prev_link = App::backend()->next_headlink = App::backend()->prev_headlink = null;

        // If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::blog()::POST_PENDING;
        }

        // Status combo
        App::backend()->status_combo = Combos::getPostStatusesCombo();

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
                App::error()->add(__('This page does not exist.'));
                App::backend()->can_view_page = false;
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

                if ($next_rs !== null) {
                    App::backend()->next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->post_id,
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

                if ($prev_rs !== null) {
                    App::backend()->prev_link = sprintf(
                        App::backend()->post_link,
                        $prev_rs->post_id,
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

        if (!empty($_POST) && App::backend()->can_edit_page) {
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
                    App::backend()->post_dt = strtotime($_POST['post_dt']);
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
            App::backend()->post_password     = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
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

        if (!empty($_POST) && !empty($_POST['save']) && App::backend()->can_edit_page && !App::backend()->bad_dt) {
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
        $img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

        if (App::backend()->post_id) {
            try {
                $img_status = match (App::backend()->post_status) {
                    App::blog()::POST_PUBLISHED   => sprintf($img_status_pattern, __('Published'), 'check-on.png'),
                    App::blog()::POST_UNPUBLISHED => sprintf($img_status_pattern, __('Unpublished'), 'check-off.png'),
                    App::blog()::POST_SCHEDULED   => sprintf($img_status_pattern, __('Scheduled'), 'scheduled.png'),
                    App::blog()::POST_PENDING     => sprintf($img_status_pattern, __('Pending'), 'check-wrn.png'),
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

        if (App::backend()->post_id && App::backend()->post->post_status == App::blog()::POST_PUBLISHED) {
            echo
            '<p><a class="onblog_link outgoing" href="' . App::backend()->post->getURL() . '" title="' . Html::escapeHTML(trim(Html::clean(App::backend()->post_title))) . '">' . __('Go to this page on the site') . ' <img src="images/outgoing-link.svg" alt="" /></a></p>';
        }

        echo '';

        if (App::backend()->post_id) {
            echo
            '<p class="nav_prevnext">';
            if (App::backend()->prev_link) {
                echo
                App::backend()->prev_link;
            }
            if (App::backend()->next_link && App::backend()->prev_link) {
                echo
                ' | ';
            }
            if (App::backend()->next_link) {
                echo
                App::backend()->next_link;
            }

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            App::behavior()->callBehavior('adminPageNavLinks', App::backend()->post ?? null);

            echo
            '</p>';
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
                        'post_status' => '<p><label for="post_status">' . __('Page status') . '</label> ' .
                        form::combo(
                            'post_status',
                            App::backend()->status_combo,
                            ['default' => App::backend()->post_status, 'disabled' => !App::backend()->can_publish]
                        ) .
                        '</p>',
                        'post_dt' => '<p><label for="post_dt">' . __('Publication date and hour') . '</label>' .
                        form::datetime('post_dt', [
                            'default' => Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime(App::backend()->post_dt))),
                            'class'   => (App::backend()->bad_dt ? 'invalid' : ''),
                        ]) .
                        '</p>',
                        'post_lang' => '<p><label for="post_lang">' . __('Page language') . '</label>' .
                        form::combo('post_lang', App::backend()->lang_combo, App::backend()->post_lang) .
                        '</p>',
                        'post_format' => '<div>' .
                        '<h5 id="label_format"><label for="post_format" class="classic">' . __('Text formatting') . '</label></h5>' .
                        '<p>' . form::combo('post_format', App::backend()->available_formats, App::backend()->post_format, 'maximal') . '</p>' .
                        '<p class="format_control control_wiki">' .
                        '<a id="convert-xhtml" class="button' . (App::backend()->post_id && App::backend()->post_format != 'wiki' ? ' hide' : '') .
                        '" href="' . My::manageUrl(['act' => 'page', 'id' => App::backend()->post_id, 'xconv' => '1']) . '">' .
                        __('Convert to HTML') . '</a></p></div>', ], ],
                'metas-box' => [
                    'title' => __('Filing'),
                    'items' => [
                        'post_position' => '<p><label for="post_position" class="classic">' . __('Page position') . '</label> ' .
                        form::number('post_position', [
                            'default' => App::backend()->post_position,
                        ]) .
                        '</p>', ], ],
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
                        'post_hide' => '<p><label for="post_selected" class="classic">' . form::checkbox('post_selected', 1, App::backend()->post_selected) . ' ' .
                        __('Hide in widget Pages') . '</label>' .
                        '</p>',
                        'post_password' => '<p><label for="post_password">' . __('Password') . '</label>' .
                        form::field('post_password', 10, 32, Html::escapeHTML(App::backend()->post_password), 'maximal') .
                        '</p>',
                        'post_url' => '<div class="lockable">' .
                        '<p><label for="post_url">' . __('Edit basename') . '</label>' .
                        form::field('post_url', 10, 255, Html::escapeHTML(App::backend()->post_url), 'maximal') .
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
                        'default'    => Html::escapeHTML(App::backend()->post_title),
                        'class'      => 'maximal',
                        'extra_html' => 'required placeholder="' . __('Title') . '" lang="' . App::backend()->post_lang . '" spellcheck="true"',
                    ]) .
                    '</p>',

                    'post_excerpt' => '<p class="area" id="excerpt-area"><label for="post_excerpt" class="bold">' . __('Excerpt:') . ' <span class="form-note">' .
                    __('Introduction to the page.') . '</span></label> ' .
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
                    'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
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

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null
            App::behavior()->callBehavior('adminPageFormItems', $main_items, $sidebar_items, App::backend()->post ?? null);

            echo
            '<div class="multi-part" title="' . (App::backend()->post_id ? __('Edit page') : __('New page')) .
            sprintf(' &rsaquo; %s', App::formater()->getFormaterName(App::backend()->post_format)) . '" id="edit-entry">' .
            '<form action="' . My::manageUrl(['act' => 'page']) . '" method="post" id="entry-form">' .
            '<div id="entry-wrapper">' .
            '<div id="entry-content"><div class="constrained">' .
            '<h3 class="out-of-screen-if-js">' . __('Edit page') . '</h3>';

            foreach ($main_items as $item) {
                echo $item;
            }

            # --BEHAVIOR-- adminPageForm -- MetaRecord|null
            App::behavior()->callBehavior('adminPageForm', App::backend()->post ?? null);

            echo
            '<p class="border-top">' .
            (App::backend()->post_id ? form::hidden('id', App::backend()->post_id) : '') .
            '<input type="submit" value="' . __('Save') . ' (s)" accesskey="s" name="save" /> ';

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

                $preview_class  = $blank_preview ? '' : ' modal';
                $preview_target = $blank_preview ? '' : ' target="_blank"';

                echo
                '<a id="post-preview" href="' . $preview_url . '" class="button' . $preview_class . '" accesskey="p"' . $preview_target . '>' . __('Preview') . ' (p)' . '</a>' .
                ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />';
            } else {
                echo
                '<a id="post-cancel" href="' . App::backend()->url()->get('admin.home') . '" class="button" accesskey="c">' . __('Cancel') . ' (c)</a>';
            }

            echo(App::backend()->can_delete ?
                ' <input type="submit" class="delete" value="' . __('Delete') . '" name="delete" />' :
                '') .
            App::nonce()->getFormNonce() .
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
            App::behavior()->callBehavior('adminPageFormSidebar', App::backend()->post ?? null);

            echo
            '</div>' . // End #entry-sidebar
            '</form>';

            # --BEHAVIOR-- adminPostForm -- MetaRecord|null
            App::behavior()->callBehavior('adminPageAfterForm', App::backend()->post ?? null);

            echo
            '</div>'; // End

            if (App::backend()->post_id && !empty(App::backend()->post_media)) {
                echo
                '<form action="' . App::backend()->url()->get('admin.post.media') . '" id="attachment-remove-hide" method="post">' .
                '<div>' .
                form::hidden(['post_id'], App::backend()->post_id) .
                form::hidden(['media_id'], '') .
                form::hidden(['remove'], 1) .
                App::nonce()->getFormNonce() .
                '</div>' .
                '</form>';
            }
        }

        if (App::backend()->post_id) {
            // Comments and trackbacks

            $params = ['post_id' => App::backend()->post_id, 'order' => 'comment_dt ASC'];

            $comments   = App::blog()->getComments(array_merge($params, ['comment_trackback' => 0]));
            $trackbacks = App::blog()->getComments(array_merge($params, ['comment_trackback' => 1]));

            # Actions combo box
            $combo_action = App::backend()->comments_actions_page->getCombo();
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
                    'id'      => App::backend()->post_id,
                    'co'      => '1',
                    'section' => 'comments',
                    'redir'   => My::manageUrl([
                        'act' => 'page',
                        'id'  => App::backend()->post_id,
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

            '<form action="' . App::backend()->url()->get('admin.comment') . '" method="post" id="comment-form">' .
            '<div class="constrained">' .
            '<p><label for="comment_author" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Name:') . '</label>' .
            form::field('comment_author', 30, 255, [
                'default'    => Html::escapeHTML(App::auth()->getInfo('user_cn')),
                'extra_html' => 'required placeholder="' . __('Author') . '"',
            ]) .
            '</p>' .

            '<p><label for="comment_email">' . __('Email:') . '</label>' .
            form::email('comment_email', [
                'size'         => 30,
                'default'      => Html::escapeHTML(App::auth()->getInfo('user_email')),
                'autocomplete' => 'email',
            ]) .
            '</p>' .

            '<p><label for="comment_site">' . __('Web site:') . '</label>' .
            form::url('comment_site', [
                'size'         => 30,
                'default'      => Html::escapeHTML(App::auth()->getInfo('user_url')),
                'autocomplete' => 'url',
            ]) .
            '</p>' .

            '<p class="area"><label for="comment_content" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' .
            __('Comment:') . '</label> ' .
            form::textarea('comment_content', 50, 8, ['extra_html' => 'required placeholder="' . __('Comment') . '"']) .
            '</p>' .

            '<p>' . form::hidden('post_id', App::backend()->post_id) .
            App::nonce()->getFormNonce() .
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
        } else {
            if ((App::blog()->settings()->system->trackbacks_ttl == 0) || (time() - App::blog()->settings()->system->trackbacks_ttl * 86400 < $dt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shows the comments or trackbacks.
     *
     * @param   mixed   $rs             Recordset
     * @param   bool    $has_action     Indicates if action is available
     */
    protected static function showComments($rs, bool $has_action): void
    {
        // IP are available only for super-admin and admin
        $show_ip = App::auth()->check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            App::blog()->id()
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
            $comment_url = App::backend()->url()->get('admin.comment', ['id' => $rs->comment_id]);

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
            '<tr class="line ' . ($rs->comment_status != App::blog()::COMMENT_PUBLISHED ? ' offline ' : '') . $sts_class . '" id="c' . $rs->comment_id . '">' .
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
                '<a href="' . App::backend()->url()->get('admin.comment', ['ip' => $rs->comment_ip]) . '">' . $rs->comment_ip . '</a>' .
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
