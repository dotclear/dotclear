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
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module backend manage page process.
 * @ingroup pages
 */
class ManagePage
{
    use TraitProcess;

    // Local static properties

    /**
     * Current page record
     */
    private static MetaRecord $post;

    /**
     * Is a post loaded?
     */
    private static bool $post_loaded;

    /**
     * Instance of backend actions
     */
    private static BackendActionsComments $actions;

    /**
     * Have the current backend actions been rendered?
     */
    private static bool $actions_rendered;

    /**
     * Backend page title
     */
    private static string $page_title;

    private static string $next_link;

    private static string $prev_link;

    private static string $next_headlink = '';

    private static string $prev_headlink = '';

    /**
     * @var array<string, string> $available_formats
     */
    private static array $available_formats;

    public static function init(): bool
    {
        if (My::checkContext(My::MANAGE)) {
            self::status(($_REQUEST['act'] ?? 'list') === 'page');
        }

        return self::status();
    }

    /**
     * @todo Switch to a more typed mechanism in order to keep class type between references
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::backend()->page()->check(App::auth()->makePermissions([
            Pages::PERMISSION_PAGES,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        $params = [];

        $user_tz = is_string($user_tz = App::auth()->getInfo('user_tz')) ? $user_tz : 'UTC';
        Date::setTZ($user_tz);

        App::backend()->post_id            = '';
        App::backend()->post_dt            = '';
        App::backend()->post_format        = App::auth()->prefs()->get('interface')->getStr('post_format');
        App::backend()->post_editor        = App::auth()->prefs()->get('interface')->get('editor');
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

        self::$page_title = __('New page');

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

        // If user can't publish
        if (!App::backend()->can_publish) {
            App::backend()->post_status = App::status()->post()::PENDING;
        }

        // Formaters combo
        $core_formaters    = App::formater()->getFormaters();
        $available_formats = ['' => ''];
        foreach ($core_formaters as $formats) {
            foreach ($formats as $format) {
                $available_formats[App::formater()->getFormaterName($format)] = $format;
            }
        }

        self::$available_formats = $available_formats;

        // Validation flag
        App::backend()->bad_dt = false;

        // Get page informations

        self::$post_loaded = false;
        if (!empty($_REQUEST['id'])) {
            $params['post_type'] = 'page';
            $params['post_id']   = $_REQUEST['id'];

            self::$post = App::blog()->getPosts($params);

            if (self::$post->isEmpty()) {
                App::backend()->notices()->addErrorNotice(__('This page does not exist.'));
                My::redirect();
            } else {
                self::$post_loaded = true;

                App::backend()->post_id            = self::$post->intField('post_id');
                App::backend()->post_dt            = date('Y-m-d H:i', (int) strtotime(self::$post->strField('post_dt')));
                App::backend()->post_format        = self::$post->strField('post_format');
                App::backend()->post_password      = self::$post->strField('post_password');
                App::backend()->post_url           = self::$post->strField('post_url');
                App::backend()->post_lang          = self::$post->strField('post_lang');
                App::backend()->post_title         = self::$post->strField('post_title');
                App::backend()->post_excerpt       = self::$post->strField('post_excerpt');
                App::backend()->post_excerpt_xhtml = self::$post->strField('post_excerpt_xhtml');
                App::backend()->post_content       = self::$post->strField('post_content');
                App::backend()->post_content_xhtml = self::$post->strField('post_content_xhtml');
                App::backend()->post_notes         = self::$post->strField('post_notes');
                App::backend()->post_status        = self::$post->intField('post_status');
                App::backend()->post_position      = self::$post->intField('post_position');
                App::backend()->post_open_comment  = self::$post->boolField('post_open_comment');
                App::backend()->post_open_tb       = self::$post->boolField('post_open_tb');
                App::backend()->post_selected      = self::$post->boolField('post_selected');

                self::$page_title = __('Edit page');

                App::backend()->can_edit_page = self::$post->isEditable();
                App::backend()->can_delete    = self::$post->isDeletable();

                $next_rs = App::blog()->getNextPost(self::$post, 1);
                $prev_rs = App::blog()->getNextPost(self::$post, -1);

                if ($next_rs instanceof MetaRecord) {
                    self::$next_link = sprintf(
                        App::backend()->post_link,
                        $next_rs->intField('post_id'),
                        'next',
                        Html::escapeHTML(trim(Html::clean($next_rs->strField('post_title')))),
                        __('Next page') . '&nbsp;&#187;'
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
                        App::backend()->post_link,
                        $prev_rs->intField('post_id'),
                        'prev',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        '&#171;&nbsp;' . __('Previous page')
                    );
                    self::$prev_headlink = sprintf(
                        $post_headlink,
                        'previous',
                        Html::escapeHTML(trim(Html::clean($prev_rs->strField('post_title')))),
                        $prev_rs->intField('post_id')
                    );
                }

                try {
                    App::backend()->post_media = App::media()->getPostMedia(App::backend()->post_id);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }
        }

        self::$actions = new BackendActionsComments(
            My::manageUrl([], '&'),
            [
                'act'           => 'page',
                'id'            => (string) App::backend()->post_id,
                'action_anchor' => 'comments',
                'section'       => 'comments',
            ]
        );

        self::$actions_rendered = false;
        if (self::$actions->process()) {
            self::$actions_rendered = true;

            return true;
        }

        if ($_POST !== [] && App::backend()->can_edit_page) {
            // Post data helpers
            $_Bool = fn (string $name): bool => !empty($_POST[$name]);
            $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
            $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

            // Format content

            App::backend()->post_format  = $_Str('post_format');
            App::backend()->post_excerpt = $_Str('post_excerpt');
            App::backend()->post_content = $_Str('post_content');
            App::backend()->post_title   = $_Str('post_title');
            App::backend()->post_status  = $_Int('post_status');

            if (empty($_POST['post_dt'])) {
                App::backend()->post_dt = '';
            } else {
                try {
                    App::backend()->post_dt = strtotime($_Str('post_dt'));
                    if (!App::backend()->post_dt || App::backend()->post_dt === -1) {
                        App::backend()->bad_dt = true;

                        throw new Exception(__('Invalid publication date'));
                    }

                    App::backend()->post_dt = date('Y-m-d H:i', App::backend()->post_dt);
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }
            }

            App::backend()->post_open_comment = $_Bool('post_open_comment');
            App::backend()->post_open_tb      = $_Bool('post_open_tb');
            App::backend()->post_selected     = $_Bool('post_selected');
            App::backend()->post_lang         = $_Str('post_lang');
            App::backend()->post_password     = empty($_POST['post_password']) ? null : $_Str('post_password');
            App::backend()->post_position     = $_Int('post_position');
            App::backend()->post_notes        = $_Str('post_notes');
            App::backend()->post_url          = $_Str('post_url');

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
            App::blog()->settings()->get('system')->set('post_url_format', '{t}');

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

        if (self::$actions_rendered) {
            self::$actions->render();

            return;
        }

        // Languages combo
        $lang_combo = App::backend()->combos()->getLangsCombo(
            App::blog()->getLangs([
                'order_by' => 'nb_post',
                'order'    => 'desc',
            ]),
            true,
            true
        );

        $default_tab = 'edit-entry';
        if (!App::backend()->can_edit_page) {
            $default_tab = '';
        }

        if (!empty($_GET['co'])) {
            $default_tab = 'comments';
        }

        # HTML conversion
        if (!empty($_GET['xconv'])) {
            App::backend()->post_excerpt = App::backend()->post_excerpt_xhtml;
            App::backend()->post_content = App::backend()->post_content_xhtml;
            App::backend()->post_format  = 'xhtml';

            App::backend()->notices()->addMessageNotice(__('Don\'t forget to validate your HTML conversion by saving your post.'));
        }

        // 3rd party conversion
        if (!empty($_GET['convert']) && !empty($_GET['convert-format'])) {
            $params = new ArrayObject([
                'excerpt' => App::backend()->post_excerpt,
                'content' => App::backend()->post_content,
                'format'  => App::backend()->post_format,
            ]);

            $convert = is_string($convert = $_GET['convert-format']) ? Html::escapeHTML($convert) : '';

            # --BEHAVIOR-- adminConvertBeforePostEdit -- string, ArrayObject
            $msg = App::behavior()->callBehavior('adminConvertBeforePostEdit', $convert, $params);
            if ($msg !== '') {
                App::backend()->post_excerpt = $params['excerpt'];
                App::backend()->post_content = $params['content'];
                App::backend()->post_format  = $params['format'];

                App::backend()->notices()->addMessageNotice($msg);
            }
        }

        $admin_post_behavior = '';
        if (App::backend()->post_editor && is_array(App::backend()->post_editor)) {
            $p_edit      = '';
            $c_edit      = '';
            $post_format = is_string($post_format = App::backend()->post_format) ? $post_format : '';

            if (!empty(App::backend()->post_editor[$post_format])) {
                $p_edit = App::backend()->post_editor[$post_format];
            }

            if (!empty(App::backend()->post_editor['xhtml'])) {
                $c_edit = App::backend()->post_editor['xhtml'];
            }

            if ($p_edit == $c_edit) {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, string[], string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content', '#comment_content'],
                    App::backend()->post_format
                );
            } else {
                # --BEHAVIOR-- adminPostEditor -- string, string, string, string[], string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $p_edit,
                    'page',
                    ['#post_excerpt', '#post_content'],
                    App::backend()->post_format
                );
                # --BEHAVIOR-- adminPostEditor -- string, string, string, string[], string
                $admin_post_behavior .= App::behavior()->callBehavior(
                    'adminPostEditor',
                    $c_edit,
                    'comment',
                    ['#comment_content'],
                    'xhtml'
                );
            }
        }

        App::backend()->page()->openModule(
            self::$page_title . ' - ' . My::name(),
            App::backend()->page()->jsModal() .
            App::backend()->page()->jsJson('pages_page', ['confirm_delete_post' => __('Are you sure you want to delete this page?')]) .
            App::backend()->page()->jsLoad('js/_post.js') .
            My::jsLoad('page') .
            $admin_post_behavior .
            App::backend()->page()->jsConfirmClose('entry-form', 'comment-form') .
            # --BEHAVIOR-- adminPageHeaders --
            App::behavior()->callBehavior('adminPageHeaders') .
            App::backend()->page()->jsPageTabs($default_tab) .
            self::$next_headlink . "\n" . self::$prev_headlink
        );

        if (App::backend()->post_id) {
            $post_status = is_numeric($post_status = App::backend()->post_status) ? (int) $post_status : 0;
            $post_title  = is_string($post_title = App::backend()->post_title) ? $post_title : '';

            $img_status       = App::status()->post()->image($post_status)->render();
            $edit_entry_title = '&ldquo;' . Html::escapeHTML(trim(Html::clean($post_title))) . '&rdquo;' . ' ' . $img_status;
        } else {
            $img_status       = '';
            $edit_entry_title = self::$page_title;
        }

        echo App::backend()->page()->breadcrumb(
            [
                Html::escapeHTML(App::blog()->name()) => '',
                My::name()                            => App::backend()->getPageURL(),
                $edit_entry_title                     => '',
            ]
        ) .
        App::backend()->notices()->getNotices();

        if (!empty($_GET['upd'])) {
            App::backend()->notices()->success(__('Page has been successfully updated.'));
        } elseif (!empty($_GET['crea'])) {
            App::backend()->notices()->success(__('Page has been successfully created.'));
        } elseif (!empty($_GET['attached'])) {
            App::backend()->notices()->success(__('File has been successfully attached.'));
        } elseif (!empty($_GET['rmattach'])) {
            App::backend()->notices()->success(__('Attachment has been successfully removed.'));
        }

        if (App::backend()->post_id && !App::status()->post()->isRestricted(self::$post->intField('post_status'))) {
            $post_url   = self::$post->getURL();
            $post_title = is_string($post_title = App::backend()->post_title) ? $post_title : '';

            echo (new Para())
                ->items([
                    (new Link())
                        ->class(['onblog_link', 'outgoing'])
                        ->href($post_url)
                        ->title(Html::escapeHTML(trim(Html::clean($post_title))))
                        ->text(__('Go to this page on the site') . ' ' . (new Img('images/outgoing-link.svg'))->alt('')->render()),
                ])
            ->render();
        }

        if (App::backend()->post_id) {
            $items = [];
            if (isset(self::$prev_link)) {
                $items[] = new Text(null, self::$prev_link);
            }

            if (isset(self::$next_link)) {
                $items[] = new Text(null, self::$next_link);
            }

            # --BEHAVIOR-- adminPageNavLinks -- MetaRecord|null
            $items[] = new Capture(App::behavior()->callBehavior(...), ['adminPageNavLinks', self::$post_loaded ? self::$post : null]);

            echo (new Para())
                ->class('nav_prevnext')
                ->items($items)
            ->render();
        }

        # Exit if we cannot view page
        if (!App::backend()->can_view_page) {
            App::backend()->page()->closeModule();

            return;
        }

        /* Post form if we can edit page
        -------------------------------------------------------- */
        if (App::backend()->can_edit_page) {
            $post_id       = is_numeric($post_id = App::backend()->post_id) ? (int) $post_id : 0;
            $post_status   = is_numeric($post_status = App::backend()->post_status) ? (int) $post_status : 0;
            $post_dt       = is_string($post_dt = App::backend()->post_dt) ? $post_dt : '';
            $post_lang     = is_string($post_lang = App::backend()->post_lang) ? $post_lang : '';
            $post_format   = is_string($post_format = App::backend()->post_format) ? $post_format : '';
            $post_position = is_numeric($post_position = App::backend()->post_position) ? (int) $post_position : 0;
            $post_password = is_string($post_password = App::backend()->post_password) ? $post_password : '';
            $post_url      = is_string($post_url = App::backend()->post_url) ? $post_url : '';
            $post_title    = is_string($post_title = App::backend()->post_title) ? $post_title : '';
            $post_excerpt  = is_string($post_excerpt = App::backend()->post_excerpt) ? $post_excerpt : '';
            $post_content  = is_string($post_content = App::backend()->post_content) ? $post_content : '';
            $post_notes    = is_string($post_notes = App::backend()->post_notes) ? $post_notes : '';

            $edit_size = App::auth()->prefs()->get('interface')->getInt('edit_size') ?? 24;

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
                                ->default($post_status)
                                ->disabled(!App::backend()->can_publish)
                                ->label(new Label(__('Page status') . ' ' . $img_status, Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_dt' => (new Para())->items([
                            (new Datetime('post_dt'))
                                ->value(Html::escapeHTML(Date::str('%Y-%m-%dT%H:%M', strtotime($post_dt))))
                                ->class(App::backend()->bad_dt ? 'invalid' : [])
                                ->label(new Label(__('Publication date and hour'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_lang' => (new Para())->items([
                            (new Select('post_lang'))
                                ->items($lang_combo)
                                ->default($post_lang)
                                ->translate(false)
                                ->label(new Label(__('Page language'), Label::OUTSIDE_LABEL_BEFORE)),
                        ])
                        ->render(),

                        'post_format' => (new Para())->items([
                            (new Select('post_format'))
                                ->items(self::$available_formats)
                                ->default($post_format)
                                ->label((new Label(__('Text formatting'), Label::OUTSIDE_LABEL_BEFORE))->id('label_format')),
                            (new Span())
                                ->class(['format_control', 'control_no_xhtml'])
                                ->items([
                                    (new Link('convert-xhtml'))
                                        ->class(['button', App::backend()->post_id && $post_format === 'xhtml' ? 'hide' : ''])
                                        ->href(My::manageUrl(['act' => 'page', 'id' => $post_id, 'xconv' => '1']))
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
                                ->value($post_position)
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
                                (new Checkbox('post_open_comment', (bool) App::backend()->post_open_comment))
                                    ->value(1)
                                    ->label((new Label(__('Accept comments'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->get('system')->getBool('allow_comments') ?
                                (
                                    self::isContributionAllowed($post_id, strtotime($post_dt), true) ?
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
                                (new Checkbox('post_open_tb', (bool) App::backend()->post_open_tb))
                                    ->value(1)
                                    ->label((new Label(__('Accept trackbacks'), Label::INSIDE_TEXT_AFTER))),
                            ]),
                            App::blog()->settings()->get('system')->getBool('allow_trackbacks') ?
                                (
                                    self::isContributionAllowed($post_id, strtotime($post_dt), true) ?
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

                        'post_hide' => (new Para())->items([
                            (new Checkbox('post_selected', (bool) App::backend()->post_selected))
                                ->value(1)
                                ->label((new Label(__('Hide in widget Pages'), Label::INSIDE_TEXT_AFTER))),
                        ])
                        ->render(),

                        'post_password' => (new Para())->items([
                            (new Password('post_password'))
                                ->autocomplete('new-password')
                                ->class('maximal')
                                ->value(Html::escapeHTML($post_password))
                                ->size(10)
                                ->maxlength(32)
                                ->label((new Label(__('Password'), Label::OUTSIDE_TEXT_BEFORE))),
                        ])
                        ->render(),

                        'post_url' => (new Div())->class('lockable')->items([
                            (new Para())->items([
                                (new Input('post_url'))
                                    ->class('maximal')
                                    ->value(Html::escapeHTML($post_url))
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

            /**
             * @var ArrayObject<string, string> $main_items
             */
            $main_items = new ArrayObject(
                [
                    'post_title' => (new Para())->items([
                        (new Input('post_title'))
                            ->value(Html::escapeHTML($post_title))
                            ->size(20)
                            ->maxlength(255)
                            ->required(true)
                            ->class('maximal')
                            ->placeholder(__('Title'))
                            ->lang($post_lang)
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
                            ->value(Html::escapeHTML($post_excerpt))
                            ->cols(50)
                            ->rows(5)
                            ->lang($post_lang)
                            ->spellcheck(true)
                            ->label(
                                (new Label(
                                    __('Excerpt:') . ' ' . (new Span(__('Introduction to the page.')))->class('form-note')->render(),
                                    Label::OUTSIDE_TEXT_BEFORE
                                ))
                                ->class('bold')
                            ),
                    ])
                    ->render(),

                    'post_content' => (new Para())->class('area')->id('content-area')->items([
                        (new Textarea('post_content'))
                            ->value(Html::escapeHTML($post_content))
                            ->cols(50)
                            ->rows($edit_size)
                            ->required(true)
                            ->lang($post_lang)
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
                            ->value(Html::escapeHTML($post_notes))
                            ->cols(50)
                            ->rows(5)
                            ->lang($post_lang)
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

            # --BEHAVIOR-- adminPageFormItems -- ArrayObject, ArrayObject, MetaRecord|null
            App::behavior()->callBehavior('adminPageFormItems', $main_items, $sidebar_items, self::$post_loaded ? self::$post : null);

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
            if (App::backend()->post_id) {
                $preview_url = App::blog()->url() .
                    App::url()->getURLFor(
                        'pagespreview',
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
                $buttons[] = (new Hidden('id', (string) $post_id));
            }

            $title = (App::backend()->post_id ? __('Edit page') : __('New page'));

            // Everything is ready, time to display this form
            echo (new Div())
                ->class('multi-part')
                ->title($title)
                ->data([
                    'page-tabs-info'  => ' &rsaquo; ' . App::formater()->getFormaterName($post_format),
                    'page-tabs-class' => 'edit-format-' . $post_format,
                ])
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
                                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                                                    (new Text(null, $main_part)),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageForm', self::$post_loaded ? self::$post : null])),
                                                    (new Para())
                                                        ->class(['border-top', 'form-buttons'])
                                                        ->items([
                                                            ...My::hiddenFields(),
                                                            ...$buttons,
                                                        ]),
                                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterButtons', self::$post_loaded ? self::$post : null])),
                                                ]),
                                        ]),
                                ]),
                            (new Div())
                                ->id('entry-sidebar')
                                ->role('complementary')
                                ->items([
                                    (new Text(null, $side_part)),
                                    (new Capture(App::behavior()->callBehavior(...), ['adminPageFormSidebar', self::$post_loaded ? self::$post : null])),
                                ]),
                        ]),
                    (new Capture(App::behavior()->callBehavior(...), ['adminPageAfterForm', self::$post_loaded ? self::$post : null])),
                ])
            ->render();

            // Attachment removing form
            if (App::backend()->post_id && !empty(App::backend()->post_media)) {
                echo (new Form('attachment-remove-hide'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.post.media'))
                    ->fields([
                        App::nonce()->formNonce(),
                        (new Hidden(['post_id'], (string) $post_id)),
                        (new Hidden(['media_id'], '')),
                        (new Hidden(['remove'], '1')),
                    ])
                ->render();
            }
        }

        if (App::backend()->post_id) {
            // Comments and trackbacks

            $post_id = is_numeric($post_id = App::backend()->post_id) ? (int) $post_id : 0;

            $user_cn    = is_string($user_cn = App::auth()->getInfo('user_cn')) ? $user_cn : '';
            $user_email = is_string($user_email = App::auth()->getInfo('user_email')) ? $user_email : '';
            $user_url   = is_string($user_url = App::auth()->getInfo('user_url')) ? $user_url : '';
            $user_lang  = is_string($user_lang = App::auth()->getInfo('user_lang')) ? $user_lang : '';

            $params = ['post_id' => $post_id, 'order' => 'comment_dt ASC'];

            $comments   = App::blog()->getComments([...$params, 'comment_trackback' => 0]);
            $trackbacks = App::blog()->getComments([...$params, 'comment_trackback' => 1]);

            # Actions combo box
            $combo_action = self::$actions->getCombo();
            $has_action   = $combo_action !== [] && (!$trackbacks->isEmpty() || !$comments->isEmpty());

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
                                'id'      => $post_id,
                                'co'      => '1',
                                'section' => 'comments',
                                'redir'   => My::manageUrl([
                                    'act' => 'page',
                                    'id'  => $post_id,
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
                        ->class('new-stuff')
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
                                                    (new Hidden('post_id', (string) $post_id)),
                                                    (new Submit(['add'], __('Save'))),
                                                ]),
                                        ]),
                                ]),
                        ]),
                ])
            ->render();
        }

        App::backend()->page()->helpBlock('page', 'core_wiki');

        App::backend()->page()->closeModule();
    }

    # Controls comments or trakbacks capabilities

    /**
     * Determines if contribution is allowed.
     *
     * @param   int         $id         Post identifier
     * @param   int|false   $dt         Post date
     * @param   bool        $comment    It is a comment?
     *
     * @return  bool    True if contribution allowed, False otherwise.
     */
    protected static function isContributionAllowed(int $id, int|false $dt, bool $comment = true): bool
    {
        if ($id === 0) {
            return true;
        }

        $dt = (int) $dt;    // False = 0

        if ($comment) {
            $ttl = App::blog()->settings()->get('system')->getInt('comments_ttl', false);
            if ($ttl === 0 || (time() - $ttl * 86400 < $dt)) {
                return true;
            }
        } else {
            $ttl = App::blog()->settings()->get('system')->getInt('trackbacks_ttl', false);
            if ($ttl === 0 || (time() - $ttl * 86400 < $dt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Shows the comments or trackbacks.
     *
     * @param   Metarecord  $rs             Recordset
     * @param   bool        $has_action     Indicates if action is available
     */
    protected static function showComments(MetaRecord $rs, bool $has_action): string
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
                        ->title(__('Select this comment')) :
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
                        ->title(__('Edit this comment'))
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
