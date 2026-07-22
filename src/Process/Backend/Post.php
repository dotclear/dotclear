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
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Text as Txt;
use Dotclear\Interface\Core\TrackbackInterface;
use Exception;

/**
 * @since 2.27 Before as admin/post.php
 */
class Post
{
    use TraitProcess;

    protected static int $post_id;  // Mirrored in App::backend()->post_id

    protected static int $cat_id;

    protected static int $post_status;

    protected static string $post_title;

    protected static string $post_format;

    protected static string $post_lang;

    protected static int $post_dt;

    protected static string $post_password;

    protected static string $post_url;

    protected static string $post_excerpt;

    protected static string $post_excerpt_xhtml;

    protected static string $post_content;

    protected static string $post_content_xhtml;

    protected static string $post_notes;

    protected static bool $post_selected;

    protected static bool $post_open_comment;

    protected static bool $post_open_tb;

    protected static MetaRecord $post;

    protected static string $page_title;

    protected static bool $can_edit;

    protected static bool $can_publish;

    protected static bool $can_delete;

    protected static string $post_link;     // Mirrored in App::backend()->post_link

    protected static string $next_link;

    protected static string $prev_link;

    protected static string $next_headlink;

    protected static string $prev_headlink;

    /**
     * @var array<string, string> $available_formats
     */
    protected static array $available_formats;

    /**
     * @var array<array-key, OptGroup|Option> $lang_combo
     */
    protected static array $lang_combo;

    protected static ActionsComments $comments_actions_page;

    protected static TrackbackInterface $tb;

    protected static string $tb_urls;

    protected static string $tb_excerpt;

    /**
     * @var Option[] $categories_combo
     */
    protected static array $categories_combo;

    public static function init(): bool
    {
        $params = [];
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';
        Date::setTZ($user_tz);

        self::$post_id            = 0;
        self::$cat_id             = 0;
        self::$post_dt            = 0;
        self::$post_format        = App::auth()->prefs()->get('interface')->getStr('post_format', false);
        self::$post_password      = '';
        self::$post_url           = '';
        self::$post_lang          = is_string($post_lang = App::auth()->getInfo('user_lang')) ? $post_lang : '';
        self::$post_title         = '';
        self::$post_excerpt       = '';
        self::$post_excerpt_xhtml = '';
        self::$post_content       = '';
        self::$post_content_xhtml = '';
        self::$post_notes         = '';
        self::$post_status        = is_numeric($post_status = App::auth()->getInfo('user_post_status')) ? (int) $post_status : 0;
        self::$post_selected      = false;
        self::$post_open_comment  = App::blog()->settings()->get('system')->getBool('allow_comments', false);
        self::$post_open_tb       = App::blog()->settings()->get('system')->getBool('allow_trackbacks', false);

        self::$page_title = __('New post');

        self::$can_edit = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        self::$can_publish = App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_PUBLISH,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id());
        self::$can_delete = false;

        $post_headlink = '<link rel="%s" title="%s" href="' . App::backend()->url()->get('admin.post', ['id' => '%s'], '&amp;', true) . '">';

        self::$post_link = '<a href="' . App::backend()->url()->get('admin.post', ['id' => '%s'], '&amp;', true) . '" class="%s" title="%s">%s</a>';

        self::$next_link     = '';
        self::$prev_link     = '';
        self::$next_headlink = '';
        self::$prev_headlink = '';

        # If user can't publish
        if (!self::$can_publish) {
            self::$post_status = App::status()->post()::PENDING;
        }

        // May be used by 3rd party code
        App::backend()->post_id   = self::$post_id;
        App::backend()->post_link = self::$post_link;

        # Getting categories
        self::$categories_combo = App::backend()->combos()->getCategoriesCombo(
            App::blog()->getCategories()
        );

        // Formats combo
        $core_formaters = App::formater()->getFormaters();
        /**
         * @var array<string, string> $available_formats
         */
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }

        self::$available_formats = $available_formats;

        // Languages combo
        self::$lang_combo = App::backend()->combos()->getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true,
            true
        );

        // Validation flag
        App::backend()->bad_dt = false;

        // Trackbacks
        self::$tb         = App::trackback();
        self::$tb_urls    = '';
        self::$tb_excerpt = '';

        // Get entry informations

        if (!empty($_REQUEST['id'])) {
            self::$page_title = __('Edit post');

            $params['post_id'] = $_REQUEST['id'];

            self::$post = App::blog()->getPosts($params);

            if (self::$post->isEmpty()) {
                App::backend()->notices()->addErrorNotice('This entry does not exist.');
                App::backend()->url()->redirect('admin.posts');
            } else {
                self::$post_id            = self::$post->intField('post_id');
                self::$cat_id             = self::$post->intField('cat_id');
                self::$post_dt            = (int) strtotime(self::$post->strField('post_dt'));
                self::$post_format        = self::$post->strField('post_format');
                self::$post_password      = self::$post->strField('post_password');
                self::$post_url           = self::$post->strField('post_url');
                self::$post_lang          = self::$post->strField('post_lang');
                self::$post_title         = self::$post->strField('post_title');
                self::$post_excerpt       = self::$post->strField('post_excerpt');
                self::$post_excerpt_xhtml = self::$post->strField('post_excerpt_xhtml');
                self::$post_content       = self::$post->strField('post_content');
                self::$post_content_xhtml = self::$post->strField('post_content_xhtml');
                self::$post_notes         = self::$post->strField('post_notes');
                self::$post_status        = self::$post->intField('post_status');
                self::$post_selected      = self::$post->boolField('post_selected');
                self::$post_open_comment  = self::$post->boolField('post_open_comment');
                self::$post_open_tb       = self::$post->boolField('post_open_tb');

                self::$can_edit   = (bool) self::$post->isEditable();
                self::$can_delete = (bool) self::$post->isDeletable();

                // May be used by 3rd party code
                App::backend()->post_id = self::$post_id;

                $next_rs = App::blog()->getNextPost(self::$post, 1);
                $prev_rs = App::blog()->getNextPost(self::$post, -1);

                if ($next_rs instanceof MetaRecord) {
                    self::$next_link = sprintf(
                        self::$post_link,
                        $next_rs->intField('post_id'),
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->strField('post_title')))),
                        __('Next post') . '&nbsp;&#187;'
                    );
                    self::$next_headlink = sprintf(
                        $post_headlink,
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->strField('post_title')))),
                        $next_rs->intField('post_id')
                    );
                }

                if ($prev_rs instanceof MetaRecord) {
                    self::$prev_link = sprintf(
                        self::$post_link,
                        $prev_rs->intField('post_id'),
                        'prev',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        '&#171;&nbsp;' . __('Previous post')
                    );
                    self::$prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        $prev_rs->intField('post_id')
                    );
                }

                // Sanitize trackbacks excerpt
                $buffer = empty($_POST['tb_excerpt']) || !is_string($_POST['tb_excerpt']) ?
                    self::$post_excerpt_xhtml . ' ' . self::$post_content_xhtml :
                    $_POST['tb_excerpt'];
                $buffer = (string) preg_replace(
                    '/\s+/ms',
                    ' ',
                    Txt::cutString(Html::escapeHTML(Html::decodeEntities(Html::clean($buffer))), 255)
                );
                self::$tb_excerpt = $buffer;
            }
        }

        $anchor = isset($_REQUEST['section']) && $_REQUEST['section'] == 'trackbacks' ? 'trackbacks' : 'comments';

        self::$comments_actions_page = App::backend()->action()->comments(
            App::backend()->url()->get('admin.post'),
            [
                'id'            => (string) self::$post_id,
                'action_anchor' => $anchor,
                'section'       => $anchor,
            ]
        );

        if (self::$comments_actions_page->process()) {
            return self::status(false);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        // Post data helpers
        $_Bool = fn (string $name): bool => !empty($_POST[$name]);
        $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        if (!empty($_POST['ping'])) {
            // Ping blogs

            if (!empty($_POST['tb_urls'])
                && is_string($_POST['tb_urls'])
                && self::$post_id !== 0
                && !App::status()->post()->isRestricted(self::$post_status)
                && self::$can_edit
            ) {
                // @phpstan-ignore assign.propertyType (false positive, why the previous is_string() is not memorized?)
                self::$tb_urls = $_POST['tb_urls'];
                self::$tb_urls = str_replace("\r", '', self::$tb_urls);

                $tb_post_title = Html::escapeHTML(trim(Html::clean(self::$post_title)));
                $tb_post_url   = self::$post->getURL();

                foreach (explode("\n", self::$tb_urls) as $tb_url) {
                    try {
                        # --BEHAVIOR-- adminBeforePingTrackback -- string, string, string, string, string
                        App::behavior()->callBehavior(
                            'adminBeforePingTrackback',
                            $tb_url,
                            self::$post_id,
                            $tb_post_title,
                            self::$tb_excerpt,
                            $tb_post_url
                        );

                        self::$tb->ping(
                            $tb_url,
                            self::$post_id,
                            $tb_post_title,
                            self::$tb_excerpt,
                            $tb_post_url
                        );
                    } catch (Exception $e) {
                        App::error()->add($e->getMessage());
                    }
                }

                if (!App::error()->flag()) {
                    App::backend()->notices()->addSuccessNotice(__('All pings sent.'));
                    App::backend()->url()->redirect(
                        'admin.post',
                        ['id' => self::$post_id, 'tb' => '1']
                    );
                }
            }
        } elseif ($_POST !== [] && self::$can_edit) {
            // Format excerpt and content

            self::$post_format  = $_Str('post_format');
            self::$post_excerpt = $_Str('post_excerpt');
            self::$post_content = $_Str('post_content');

            self::$post_title = $_Str('post_title');
            self::$cat_id     = $_Int('cat_id');

            if (isset($_POST['post_status'])) {
                self::$post_status = $_Int('post_status');
            }

            if (empty($_POST['post_dt'])) {
                self::$post_dt = 0;
            } else {
                try {
                    self::$post_dt = (int) strtotime($_Str('post_dt'));
                    if (self::$post_dt === 0 || self::$post_dt === -1) {
                        App::backend()->bad_dt = true;

                        throw new Exception(__('Invalid publication date.'));
                    }
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            self::$post_open_comment = $_Bool('post_open_comment');
            self::$post_open_tb      = $_Bool('post_open_tb');
            self::$post_selected     = $_Bool('post_selected');
            self::$post_lang         = $_Str('post_lang');
            self::$post_password     = $_Str('post_password');
            self::$post_url          = $_Str('post_url');
            self::$post_notes        = $_Str('post_notes');

            [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml
            ] = [
                self::$post_excerpt,
                self::$post_excerpt_xhtml,
                self::$post_content,
                self::$post_content_xhtml,
            ];

            App::blog()->setPostContent(
                self::$post_id,
                self::$post_format,
                self::$post_lang,
                $post_excerpt,
                $post_excerpt_xhtml,
                $post_content,
                $post_content_xhtml
            );

            [
                self::$post_excerpt,
                self::$post_excerpt_xhtml,
                self::$post_content,
                self::$post_content_xhtml
            ] = [
                $post_excerpt, $post_excerpt_xhtml, $post_content, $post_content_xhtml,
            ];
        }

        if (!empty($_POST['delete']) && self::$can_delete) {
            // Delete post

            try {
                # --BEHAVIOR-- adminBeforePostDelete -- string|int
                App::behavior()->callBehavior('adminBeforePostDelete', self::$post_id);
                App::blog()->delPost(self::$post_id);
                App::backend()->url()->redirect('admin.posts');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if ($_POST !== [] && !empty($_POST['save']) && self::$can_edit && !App::backend()->bad_dt) {
            // Create or update post

            if (!empty($_POST['new_cat_title'])
                && is_string($_POST['new_cat_title'])
                && App::auth()->check(App::auth()->makePermissions([
                    App::auth()::PERMISSION_CATEGORIES,
                ]), App::blog()->id())
            ) {
                // Create category

                $cur_cat = App::blog()->categories()->openCategoryCursor();

                $cur_cat->cat_title = $_POST['new_cat_title'];
                $cur_cat->cat_url   = '';

                $parent_cat = $_Int('new_cat_parent');

                # --BEHAVIOR-- adminBeforeCategoryCreate -- Cursor
                App::behavior()->callBehavior('adminBeforeCategoryCreate', $cur_cat);

                self::$cat_id = App::blog()->addCategory($cur_cat, $parent_cat);

                # --BEHAVIOR-- adminAfterCategoryCreate -- Cursor, string|int
                App::behavior()->callBehavior('adminAfterCategoryCreate', $cur_cat, self::$cat_id);
            }

            $cur = App::blog()->openPostCursor();

            $cur->cat_id             = self::$cat_id  !== 0 ? self::$cat_id : null;
            $cur->post_dt            = self::$post_dt !== 0 ? date('Y-m-d H:i:00', self::$post_dt) : '';
            $cur->post_format        = self::$post_format;
            $cur->post_password      = self::$post_password !== '' ? self::$post_password : null;
            $cur->post_url           = self::$post_url      !== '' ? self::$post_url : null;
            $cur->post_lang          = self::$post_lang;
            $cur->post_title         = self::$post_title;
            $cur->post_excerpt       = self::$post_excerpt;
            $cur->post_excerpt_xhtml = self::$post_excerpt_xhtml;
            $cur->post_content       = self::$post_content;
            $cur->post_content_xhtml = self::$post_content_xhtml;
            $cur->post_notes         = self::$post_notes;
            $cur->post_status        = self::$post_status;
            $cur->post_selected      = (int) self::$post_selected;
            $cur->post_open_comment  = (int) self::$post_open_comment;
            $cur->post_open_tb       = (int) self::$post_open_tb;

            // Back to UTC in order to keep UTC datetime for creadt/upddt
            Date::setTZ('UTC');

            if (self::$post_id !== 0) {
                // Update post

                try {
                    # --BEHAVIOR-- adminBeforePostUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminBeforePostUpdate', $cur, self::$post_id);

                    App::blog()->updPost(self::$post_id, $cur);

                    # --BEHAVIOR-- adminAfterPostUpdate -- Cursor, int
                    App::behavior()->callBehavior('adminAfterPostUpdate', $cur, self::$post_id);
                    App::backend()->notices()->addSuccessNotice(sprintf(__('The post "%s" has been successfully updated.'), Html::escapeHTML(trim(Html::clean(self::$post_title)))));
                    App::backend()->url()->redirect(
                        'admin.post',
                        ['id' => self::$post_id]
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

                    App::backend()->notices()->addSuccessNotice(__('Entry has been successfully created.'));
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
        self::$categories_combo = App::backend()->combos()->getCategoriesCombo(
            App::blog()->getCategories()
        );

        return true;
    }

    public static function render(): void
    {
        $default_tab = 'edit-entry';
        if (!self::$can_edit) {
            $default_tab = '';
        }

        if (!empty($_GET['co'])) {
            $default_tab = 'comments';
        } elseif (!empty($_GET['tb'])) {
            $default_tab = 'trackbacks';
        }

        // HTML conversion
        if (!empty($_GET['xconv'])) {
            self::$post_excerpt = self::$post_excerpt_xhtml;
            self::$post_content = self::$post_content_xhtml;
            self::$post_format  = 'xhtml';

            App::backend()->notices()->addMessageNotice(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
        }

        // 3rd party conversion
        if (!empty($_GET['convert'])
            && !empty($_GET['convert-format'])
            && is_string($_GET['convert-format'])
        ) {
            /**
             * @var ArrayObject<string, string> $params
             */
            $params = new ArrayObject([
                'excerpt' => self::$post_excerpt,
                'content' => self::$post_content,
                'format'  => self::$post_format,
            ]);
            $convert = Html::escapeHTML($_GET['convert-format']);

            # --BEHAVIOR-- adminConvertBeforePostEdit -- string, ArrayObject
            $msg = App::behavior()->callBehavior('adminConvertBeforePostEdit', $convert, $params);
            if ($msg !== '') {
                self::$post_excerpt = (string) $params['excerpt'];
                self::$post_content = (string) $params['content'];
                self::$post_format  = (string) $params['format'];

                App::backend()->notices()->addMessageNotice($msg);
            }
        }

        $admin_post_behavior = '';

        $post_editor = App::auth()->prefs()->get('interface')->get('editor');
        if (is_array($post_editor)) {
            $p_edit = '';
            $c_edit = '';

            if (!empty($post_editor[self::$post_format]) && is_string($post_editor[self::$post_format])) {
                $p_edit = $post_editor[self::$post_format];
            }

            if (!empty($post_editor['xhtml']) && is_string($post_editor['xhtml'])) {
                $c_edit = $post_editor['xhtml'];
            }

            if ($p_edit === $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    self::$post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, array<int,string>, string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'post',
                    ['#post_excerpt', '#post_content'],
                    self::$post_format
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

        if (self::$post_id !== 0) {
            $img_status       = App::status()->post()->image(self::$post_status)->render();
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean(self::$post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $img_status       = '';
            $edit_entry_title = self::$page_title;
        }

        // Check if entry URL basename use year, month or date
        $check_dt = preg_match('/{[y|m|d]}/', App::blog()->settings()->get('system')->getStr('post_url_format', false));

        // Check if entry URL basename use title
        $check_title = preg_match('/{t}/', (string) App::blog()->settings()->get('system')->getStr('post_url_format', false));

        App::backend()->page()->open(
            self::$page_title . ' - ' . __('Posts'),
            App::backend()->page()->jsModal() .
            App::backend()->page()->jsMetaEditor() .
            $admin_post_behavior .
            App::backend()->page()->jsJson('post_options', [
                'entryurl_dt'    => $check_dt,
                'entryurl_title' => $check_title,
            ]) .
            App::backend()->page()->jsLoad('js/_post.js') .
            App::backend()->page()->jsLoad('js/_trackbacks.js') .
            App::backend()->page()->jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPostHeaders --
            App::behavior()->callBehavior('adminPostHeaders') .
            App::backend()->page()->jsPageTabs($default_tab) .
            self::$next_headlink . "\n" . self::$prev_headlink,
            App::backend()->page()->breadcrumb(
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
            App::backend()->notices()->success(__('Entry has been successfully updated.'));
        } elseif (!empty($_GET['crea'])) {
            App::backend()->notices()->success(__('Entry has been successfully created.'));
        } elseif (!empty($_GET['attached'])) {
            App::backend()->notices()->success(__('File has been successfully attached.'));
        } elseif (!empty($_GET['rmattach'])) {
            App::backend()->notices()->success(__('Attachment has been successfully removed.'));
        }

        if (!empty($_GET['creaco'])) {
            App::backend()->notices()->success(__('Comment has been successfully created.'));
        }

        if (!empty($_GET['tbsent'])) {
            App::backend()->notices()->success(__('All pings sent.'));
        }

        if (self::$post_id !== 0 && !App::status()->post()->isRestricted(self::$post->intField('post_status'))) {
            $post_url = self::$post->getURL();
            if ($post_url !== '') {
                echo (new Para())
                    ->items([
                        (new Link())
                            ->class(['onblog_link', 'outgoing'])
                            ->href($post_url)
                            ->title(Html::escapeHTML(trim(Html::clean(self::$post_title))))
                            ->text(__('Go to this entry on the site') . ' ' . (new Img('images/outgoing-link.svg'))->alt('')->render()),
                    ])
                ->render();
            }
        }

        if (self::$post_id !== 0) {
            $items = [];
            if (self::$prev_link !== '') {
                $items[] = new Text(null, self::$prev_link);
            }

            if (self::$next_link !== '') {
                $items[] = new Text(null, self::$next_link);
            }

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            $items[] = new Capture(App::behavior()->callBehavior(...), ['adminPosNavLinks', self::$post ?? null, 'post']);

            echo (new Para())
                ->class('nav_prevnext')
                ->items($items)
            ->render();
        }

        /* Post form if we can edit post
        -------------------------------------------------------- */
        if (self::$can_edit) {
            /**
             * @var ArrayObject<string, array{title: string, items: array<string, string>}> $sidebar_items
             */
            $sidebar_items = new ArrayObject([
                'status-box' => [
                    'title' => __('Status'),
                    'items' => [
                        'post_status' => (new Para())->class('entry-status')->items([
                            (new Select('post_status'))
                                ->items(App::status()->post()->combo())
                                ->default(self::$post_status)
                                ->disabled(!self::$can_publish)
                                ->label(new Label(__('Entry status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_dt' => (new Para())->items([
                            (new Datetime('post_dt'))
                                ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', self::$post_dt)))
                                ->class(App::backend()->bad_dt ? 'invalid' : [])
                                ->label(new Label(__('Publication date and hour'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_lang' => (new Para())->items([
                            (new Select('post_lang'))
                                ->items(self::$lang_combo)
                                ->default(self::$post_lang)
                                ->translate(false)
                                ->label(new Label(__('Entry language'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_format' => (new Para())->items([
                            (new Select('post_format'))
                                ->items(self::$available_formats)
                                ->default(self::$post_format)
                                ->label((new Label(__('Text formatting'), Label::OUTSIDE_LABEL_BEFORE))->id('label_format')),
                            (new Span())
                                ->class(['format_control', 'control_no_xhtml'])
                                ->items([
                                    (new Link('convert-xhtml'))
                                        ->class(['button', self::$post_id !== 0 && self::$post_format === 'xhtml' ? 'hide' : ''])
                                        ->href(App::backend()->url()->get('admin.post', ['id' => self::$post_id, 'xconv' => '1']))
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
                            (new Checkbox('post_selected', self::$post_selected))
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
                                        ->items(self::$categories_combo)
                                        ->default(self::$cat_id)
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
                                                ->autocomplete('off')
                                                ->label(new Label(__('Title:'), Label::OL_TF)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Select('new_cat_parent'))
                                                ->items(self::$categories_combo)
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
                                (new Checkbox('post_open_comment', self::$post_open_comment))
                                    ->value(1)
                                    ->label((new Label(__('Accept comments'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->get('system')->getBool('allow_comments') ?
                                (
                                    self::isContributionAllowed(self::$post_id, self::$post_dt, true) ?
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
                                (new Checkbox('post_open_tb', self::$post_open_tb))
                                    ->value(1)
                                    ->label((new Label(__('Accept trackbacks'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->get('system')->getBool('allow_trackbacks') ?
                                (
                                    self::isContributionAllowed(self::$post_id, self::$post_dt, true) ?
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
                                ->autocomplete('new-password')
                                ->class('maximal')
                                ->value(Html::escapeHTML(self::$post_password))
                                ->size(10)
                                ->maxlength(32)
                                ->translate(false)
                                ->label((new Label(__('Password'), Label::OUTSIDE_TEXT_BEFORE))),
                        ])
                        ->render(),

                        'post_url' => (new Div())->class('lockable')->items([
                            (new Para())->items([
                                (new Input('post_url'))
                                    ->class('maximal')
                                    ->value(Html::escapeHTML(self::$post_url))
                                    ->size(10)
                                    ->maxlength(255)
                                    ->translate(false)
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

            /**
             * @var ArrayObject<string, string> $main_items
             */
            $main_items = new ArrayObject(
                [
                    'post_title' => (new Para())->items([
                        (new Input('post_title'))
                            ->value(Html::escapeHTML(self::$post_title))
                            ->size(20)
                            ->maxlength(255)
                            ->required(true)
                            ->class('maximal')
                            ->placeholder(__('Title'))
                            ->lang(self::$post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    (new Span('*'))->render() . __('Title:'),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class(['required', 'no-margin', 'bold'])
                            )
                            ->title(__('Required field')),
                    ])
                    ->render(),

                    'post_excerpt' => (new Para())->class('area')->id('excerpt-area')->items([
                        (new Textarea('post_excerpt'))
                            ->value(Html::escapeHTML(self::$post_excerpt))
                            ->cols(50)
                            ->rows(5)
                            ->lang(self::$post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    __('Excerpt:') . ' ' . (new Span(__('Introduction to the post.')))->class('form-note')->render(),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class('bold')
                            ),
                    ])
                    ->render(),

                    'post_content' => (new Para())->class('area')->id('content-area')->items([
                        (new Textarea('post_content'))
                            ->value(Html::escapeHTML(self::$post_content))
                            ->cols(50)
                            ->rows(App::auth()->prefs()->get('interface')->getInt('edit_size', false))
                            ->required(true)
                            ->lang(self::$post_lang)
                            ->spellcheck(true)
                            ->placeholder(__('Content'))
                            ->label(
                                (new Label(
                                    (new Span('*'))->render() . __('Content:'),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class(['required', 'bold'])
                            ),
                    ])
                    ->render(),

                    'post_notes' => (new Para())->class('area')->id('notes-area')->items([
                        (new Textarea('post_notes'))
                            ->value(Html::escapeHTML(self::$post_notes))
                            ->cols(50)
                            ->rows(5)
                            ->lang(self::$post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    __('Personal notes:') . ' ' . (new Span(__('Unpublished notes.')))->class('form-note')->render(),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class('bold')
                            ),
                    ])
                    ->render(),
                ]
            );

            # --BEHAVIOR-- adminPostFormItems -- ArrayObject, ArrayObject, MetaRecord|null, string
            App::behavior()->callBehavior('adminPostFormItems', $main_items, $sidebar_items, self::$post ?? null, 'post');

            // Prepare main and side parts
            $side_part_items = [];
            foreach ($sidebar_items as $id => $c) {
                $side_part_items[] = (new Div())
                    ->id($id)
                    ->class('sb-box')
                    ->items([
                        (new Text('h4', $c['title'])),
                        (new Text(null, implode('', $c['items']))),
                    ])
                    ->render();
            }

            $side_part = implode('', $side_part_items);
            $main_part = implode('', iterator_to_array($main_items));

            // Prepare buttons
            $buttons   = [];
            $buttons[] = (new Submit(['save'], __('Save') . ' (s)'))
                ->accesskey('s');
            if (self::$post_id !== 0) {
                $preview_url = App::blog()->url() .
                    App::url()->getURLFor(
                        'preview',
                        App::auth()->userID() . '/' .
                        Http::browserUID(App::config()->masterKey() . App::auth()->userID() . App::auth()->cryptLegacy((string) App::auth()->userID())) .
                        '/' . self::$post->strField('post_url')
                    );

                // Prevent browser caching on preview
                $preview_url .= (parse_url($preview_url, PHP_URL_QUERY) ? '&' : '?') . 'rand=' . md5((string) random_int(0, mt_getrandmax()));

                $blank_preview = App::auth()->prefs()->get('interface')->getBool('blank_preview', false);

                $preview_class  = $blank_preview ? '' : 'modal';
                $preview_target = $blank_preview ? 'target="_blank"' : '';

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

            if (self::$can_delete) {
                $buttons[] = (new Submit(['delete'], __('Delete')))
                    ->class('delete');
            }

            if (self::$post_id !== 0) {
                $buttons[] = (new Hidden('id', (string) self::$post_id));
            }

            $title = (self::$post_id !== 0 ? __('Edit post') : __('New post'));

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->data([
                    'page-tabs-info'  => ' &rsaquo; ' . App::formater()->getFormaterName(self::$post_format),
                    'page-tabs-class' => 'edit-format-' . self::$post_format,
                ])
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
                                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                                    (new Text(null, $main_part)),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostForm', self::$post ?? null, 'post'])),
                                                    (new Para())
                                                        ->class(['border-top', 'form-buttons'])
                                                        ->items([
                                                            App::nonce()->formNonce(),
                                                            ...$buttons,
                                                        ]),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostAfterButtons', self::$post ?? null])),
                                                ]),
                                        ]),
                                ]),
                            (new Div())
                                ->id('entry-sidebar')
                                ->role('complementary')
                                ->items([
                                    (new Text(null, $side_part)),
                                    (new Capture(App::behavior()->callBehavior(...), ['adminPostFormSidebar', self::$post ?? null])),
                                ]),
                        ]),
                    (new Capture(App::behavior()->callBehavior(...), ['adminPostAfterForm', self::$post ?? null, 'post'])),
                ])
            ->render();
        }

        if (self::$post_id !== 0) {
            // Comments

            $params = ['post_id' => self::$post_id, 'order' => 'comment_dt ASC'];

            $comments = App::blog()->getComments([...$params, 'comment_trackback' => 0]);

            # Actions combo box
            $combo_action = self::$comments_actions_page->getCombo();
            $has_action   = $combo_action !== [] && !$comments->isEmpty();

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
                            (new Hidden('id', (string) self::$post_id)),
                            App::nonce()->formNonce(),
                            (new Submit('do-action-comm', __('Ok'))),
                        ]),
                    ]);
            }

            $user_cn    = is_string($user_cn = App::auth()->getInfo('user_cn')) ? $user_cn : '';
            $user_email = is_string($user_email = App::auth()->getInfo('user_email')) ? $user_email : '';
            $user_url   = is_string($user_url = App::auth()->getInfo('user_url')) ? $user_url : '';
            $user_lang  = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

            echo (new Div())
                ->id('comments')
                ->class('multi-part')
                ->title(__('Comments'))
                ->items([
                    (new Para())
                        ->class('new-stuff')
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
                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                    (new Div())
                                        ->class('constrained')
                                        ->items([
                                            (new Para())
                                                ->items([
                                                    (new Input('comment_author'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML($user_cn))
                                                        ->required(true)
                                                        ->placeholder(__('Author'))
                                                        ->label((new Label(
                                                            (new Span('*'))->render() . __('Name:'),
                                                            Label::OUTSIDE_TEXT_BEFORE
                                                        ))->class('required')),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Email('comment_email'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML($user_email))
                                                        ->autocomplete('email')
                                                        ->label(new Label(__('Email:'), Label::OUTSIDE_TEXT_BEFORE)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Url('comment_site'))
                                                        ->size(30)
                                                        ->maxlength(255)
                                                        ->value(Html::escapeHTML($user_url))
                                                        ->autocomplete('url')
                                                        ->label(new Label(__('Web site:'), Label::OUTSIDE_TEXT_BEFORE)),
                                                ]),
                                            (new Para())
                                                ->class('area')
                                                ->items([
                                                    (new Textarea('comment_content'))
                                                        ->cols(50)
                                                        ->rows(8)
                                                        ->lang($user_lang)
                                                        ->spellcheck(true)
                                                        ->placeholder(__('Comment'))
                                                        ->required(true)
                                                        ->label((new Label(
                                                            (new Span('*'))->render() . __('Comment'),
                                                            Label::OUTSIDE_TEXT_BEFORE
                                                        ))->class('required')),
                                                ]),
                                            (new Para())
                                                ->class('form-buttons')
                                                ->items([
                                                    App::nonce()->formNonce(),
                                                    (new Hidden('post_id', (string) self::$post_id)),
                                                    (new Submit(['add'], __('Save'))),
                                                ]),
                                        ]),
                                ]),
                        ]),
                ])
            ->render();
        }

        if (self::$post_id !== 0 && !App::status()->post()->isRestricted(self::$post_status)) {
            // Trackbacks

            $params     = ['post_id' => self::$post_id, 'order' => 'comment_dt ASC'];
            $trackbacks = App::blog()->getComments([...$params, 'comment_trackback' => 1]);

            // Actions combo box
            $combo_action = self::$comments_actions_page->getCombo();
            $has_action   = $combo_action !== [] && !$trackbacks->isEmpty();

            if (!empty($_GET['tb_auto'])) {
                self::$tb_urls = implode("\n", self::$tb->discover(self::$post_excerpt_xhtml . ' ' . self::$post_content_xhtml));
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
                            (new Hidden('id', (string) self::$post_id)),
                            App::nonce()->formNonce(),
                            (new Submit('do-action-comm', __('Ok'))),
                        ]),
                    ]);
            }

            $pingsSent = function (): Set|None {
                $pings = self::$tb->getPostPings(self::$post_id);
                if ($pings->isEmpty()) {
                    return new None();
                }

                $list = [];
                while ($pings->fetch()) {
                    $list[] = (new Li())
                        ->text(Date::dt2str(__('%Y-%m-%d %H:%M'), $pings->strField('ping_dt')) . ' - ' . $pings->strField('ping_url'));
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
                    self::$can_edit ?
                        //Add a trackback
                        (new Form('trackback-form'))
                            ->method('post')
                            ->action(App::backend()->url()->get('admin.post', ['id' => self::$post_id]))
                            ->fields([
                                (new Fieldset())
                                    ->legend(new Legend(__('Ping blogs')))
                                    ->fields([
                                        (new Para())
                                            ->items([
                                                (new Textarea('tb_urls'))
                                                    ->cols(60)
                                                    ->rows(5)
                                                    ->value(self::$tb_urls)
                                                    ->label(new Label(__('URLs to ping:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Textarea('tb_excerpt'))
                                                    ->cols(60)
                                                    ->rows(5)
                                                    ->value(self::$tb_excerpt)
                                                    ->label(new Label(__('Excerpt to send:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('form-buttons')
                                            ->items([
                                                App::nonce()->formNonce(),
                                                (new Submit('ping', __('Ping blogs'))),
                                                (new Link())
                                                    ->href(App::backend()->url()->get('admin.post', [
                                                        'id'      => self::$post_id,
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

        App::backend()->page()->helpBlock('core_post', 'core_trackbacks', 'core_wiki');
        App::backend()->page()->close();
    }

    /**
     * Controls comments or trakbacks capabilities
     *
     * @param      int     $id     The post identifier
     * @param      int     $dt     The date
     * @param      bool    $com    True if comment, false if trackback
     *
     * @return     bool    True if contribution allowed, False otherwise.
     */
    protected static function isContributionAllowed(int $id, int $dt, bool $com = true): bool
    {
        if ($id === 0) {
            return true;
        }

        if ($com) {
            if (App::blog()->settings()->get('system')->getInt('comments_ttl', false) === 0
                || (time() - App::blog()->settings()->get('system')->getInt('comments_ttl', false) * 86400 < $dt)
            ) {
                return true;
            }
        } elseif (App::blog()->settings()->get('system')->getInt('trackbacks_ttl', false) === 0
            || (time() - App::blog()->settings()->get('system')->getInt('trackbacks_ttl', false) * 86400 < $dt)
        ) {
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
            $comment_url = App::backend()->url()->get('admin.comment', ['id' => $rs->intField('comment_id')]);
            $sts_class   = App::status()->comment()->id($rs->intField('comment_status'));

            $cols[] = (new Td())
                ->class('nowrap')
                ->items([
                    $has_action ?
                    (new Checkbox(['comments[]']))
                        ->value($rs->intField('comment_id'))
                        ->title($tb ? __('select this trackback') : __('select this comment')) :
                    (new None()),
                ]);

            $cols[] = (new Td())
                ->class('maximal')
                ->text($rs->strField('comment_author'));

            $cols[] = (new Td())
                ->class('nowrap')
                ->text(Date::dt2str(__('%Y-%m-%d %H:%M'), $rs->strField('comment_dt')));

            if ($show_ip) {
                $cols[] = (new Td())
                    ->class('nowrap')
                    ->items([
                        (new Link())
                            ->href(App::backend()->url()->get('admin.comment', ['ip' => $rs->strField('comment_ip')]))
                            ->text($rs->strField('comment_ip')),
                    ]);
            }

            $cols[] = (new Td())
                ->class(['nowrap', 'status'])
                ->text(App::status()->comment()->image($rs->intField('comment_status'))->render());

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
                ->class(array_filter(['line', App::status()->comment()->isRestricted($rs->intField('comment_status')) ? '' : 'offline ', $sts_class]))
                ->id('c' . $rs->intField('comment_id'))
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

        return (new Div())
            ->class('table-outer')
            ->items([
                (new Table())
                    ->class('comments-list')
                    ->thead((new Thead())->rows([(new Tr())->cols($cols)]))
                    ->tbody((new Tbody())->rows($rows)),
                (new Para())
                    ->class('info')
                    ->items([
                        (new Text(
                            null,
                            __('Legend: ') . (new Set())
                            ->separator(' - ')
                            ->items([
                                ... array_map(fn (Status $k): Img|Set|Text => App::status()->comment()->image($k->id(), true), App::status()->comment()->dump(false)),
                            ])
                            ->render(),
                        )),
                    ]),
            ])
        ->render();
    }
}
