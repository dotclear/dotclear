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
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Interface\Core\BlogSettingsInterface;
use Exception;

/**
 * @since 2.27 Before as admin/blog_pref.php
 */
class BlogPref
{
    use TraitProcess;

    protected static bool $standalone;
    protected static string $action;
    protected static string $redir;

    protected static string $blog_id;
    protected static int $blog_status;
    protected static string $blog_name;
    protected static string $blog_desc;
    protected static string $blog_url;
    protected static BlogSettingsInterface $blog_settings;

    protected static string $media_img_title_pattern;

    /**
     * @var array<string, string> $img_title_combo
     */
    protected static array $img_title_combo;

    /**
     * @var array<string, string> $date_formats_combo
     */
    protected static array $date_formats_combo;

    /**
     * @var array<string, string> $time_formats_combo
     */
    protected static array $time_formats_combo;

    /**
     * @var array<string, string> $url_scan_combo
     */
    protected static array $url_scan_combo;

    /**
     * @var array<string, string> $post_url_combo
     */
    protected static array $post_url_combo;

    /**
     * @var array<string, string> $note_title_tag_combo
     */
    protected static array $note_title_tag_combo;

    protected static int $now;

    /**
     * @var array<string, string> $img_default_size_combo
     */
    protected static array $img_default_size_combo;

    /**
     * @var array<string, string> $img_default_alignment_combo
     */
    protected static array $img_default_alignment_combo;

    /**
     * @var array<string, string> $img_default_legend_combo
     */
    protected static array $img_default_legend_combo;

    /**
     * @var array<string, string> $robots_policy_options
     */
    protected static array $robots_policy_options;

    /**
     * @var array<string, string> $jquery_versions_combo
     */
    protected static array $jquery_versions_combo;

    /**
     * @var Option[] $sleepmode_timeout_combo
     */
    protected static array $sleepmode_timeout_combo;

    public static function init(): bool
    {
        /*
         * Is standalone blog preferences?
         *
         * - true: come directly from blog's paramaters menu entry (or link)
         * - false: come from in blogs management (may be on a different blog ID than current)
         *
         * @var        bool
         */
        self::$standalone = !(App::backend()->edit_blog_mode ?? false);
        if (self::$standalone) {
            App::backend()->page()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]));

            self::$blog_id     = App::blog()->id();
            self::$blog_status = App::blog()->status();
            self::$blog_name   = App::blog()->name();
            self::$blog_desc   = App::blog()->desc();
            self::$blog_url    = App::blog()->url();

            self::$blog_settings = App::blog()->settings();

            self::$action = App::backend()->url()->get('admin.blog.pref');
            self::$redir  = App::backend()->url()->get('admin.blog.pref');
        } else {
            App::backend()->page()->checkSuper();

            self::$blog_id     = '';
            self::$blog_status = App::status()->blog()::OFFLINE;
            self::$blog_name   = '';
            self::$blog_desc   = '';
            self::$blog_url    = '';

            try {
                if (empty($_REQUEST['id']) || !is_string($_REQUEST['id'])) {
                    throw new Exception(__('No given blog id.'));
                }

                $rs = App::blogs()->getBlog($_REQUEST['id']);
                if ($rs->count() === 0) {
                    throw new Exception(__('No such blog.'));
                }

                self::$blog_id     = $rs->strField('blog_id');
                self::$blog_status = $rs->intField('blog_status');
                self::$blog_name   = $rs->strField('blog_name');
                self::$blog_desc   = $rs->strField('blog_desc');
                self::$blog_url    = $rs->strField('blog_url');

                self::$blog_settings = App::blogSettings()->createFromBlog(self::$blog_id);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            self::$action = App::backend()->url()->get('admin.blog');
            self::$redir  = App::backend()->url()->get('admin.blog', ['id' => '%s'], '&', true);
        }

        // Date format combo
        self::$now = time();

        $date_formats = self::$blog_settings->get('system')->get('date_formats');
        $time_formats = self::$blog_settings->get('system')->get('time_formats');

        /**
         * @var array<string, string>
         */
        $stack = ['' => ''];
        if (is_array($date_formats)) {
            foreach ($date_formats as $format) {
                if (is_string($format)) {
                    $stack[Date::str($format, self::$now)] = $format;
                }
            }
        }
        self::$date_formats_combo = $stack;

        /**
         * @var array<string, string>
         */
        $stack = ['' => ''];
        if (is_array($time_formats)) {
            foreach ($time_formats as $format) {
                if (is_string($format)) {
                    $stack[Date::str($format, self::$now)] = $format;
                }
            }
        }
        self::$time_formats_combo = $stack;

        // URL scan modes
        self::$url_scan_combo = [
            'PATH_INFO'    => 'path_info',
            'QUERY_STRING' => 'query_string',
        ];

        // Post URL combo
        self::$post_url_combo = [
            __('year/month/day/title') => '{y}/{m}/{d}/{t}',
            __('year/month/title')     => '{y}/{m}/{t}',
            __('year/title')           => '{y}/{t}',
            __('title')                => '{t}',
            __('post id/title')        => '{id}/{t}',
            __('post id')              => '{id}',
        ];

        $post_url_format = is_string($post_url_format = self::$blog_settings->get('system')->get('post_url_format')) ? $post_url_format : '';
        if ($post_url_format !== '' && !in_array($post_url_format, self::$post_url_combo)) {
            self::$post_url_combo[Html::escapeHTML($post_url_format)] = Html::escapeHTML($post_url_format);
        }

        // Note title tag combo
        self::$note_title_tag_combo = [
            __('H4') => '0',
            __('H3') => '1',
            __('P')  => '2',
        ];

        // Image title combo
        self::$img_title_combo = [
            __('(none)')                           => '',
            __('Description')                      => 'Description ;; separator(, )',
            __('Description, Date')                => 'Description ;; Date(%b %Y) ;; separator(, )',
            __('Description, Country')             => 'Description ;; Country ;; separator(, )',
            __('Description, Country, Date')       => 'Description ;; Country ;; Date(%b %Y) ;; separator(, )',
            __('Description, City')                => 'Description ;; City ;; separator(, )',
            __('Description, City, Country')       => 'Description ;; City ;; Country ;; separator(, )',
            __('Description, City, Country, Date') => 'Description ;; City ;; Country ;; Date(%b %Y) ;; separator(, )',
        ];

        self::$media_img_title_pattern = is_string($pattern = self::$blog_settings->get('system')->get('media_img_title_pattern')) ? $pattern : '';
        if (!in_array(self::$media_img_title_pattern, self::$img_title_combo)) {
            // Convert Title keyword to Description if present
            $converted = (string) preg_replace('/(^|\s|;)Title($|\s|;)/m', '$1Description$2', self::$media_img_title_pattern);
            if ($converted !== '' && $converted !== self::$media_img_title_pattern) {
                // Store new pattern
                self::$blog_settings->get('system')->put('media_img_title_pattern', $converted);

                if (!in_array($converted, self::$img_title_combo)) {
                    // Add custom pattern to combo
                    self::$img_title_combo = array_merge(
                        self::$img_title_combo,
                        [Html::escapeHTML($converted) => $converted]
                    );
                }
            }
        }

        // Image default size combo
        $stack = [];

        try {
            $media = App::media();

            $stack[__('original')] = 'o';
            foreach ($media->getThumbSizes() as $code => $size) {
                $stack[__($size[2])] = $code;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }
        self::$img_default_size_combo = $stack;

        // Image default alignment combo
        self::$img_default_alignment_combo = [
            __('None')   => 'none',
            __('Left')   => 'left',
            __('Right')  => 'right',
            __('Center') => 'center',
        ];

        // Image default legend and alternate text combo
        self::$img_default_legend_combo = [
            __('Legend and alternate text') => 'legend',
            __('Alternate text')            => 'title',
            __('None')                      => 'none',
        ];

        // Robots policy options
        self::$robots_policy_options = [
            'INDEX,FOLLOW'               => __("I would like search engines and archivers to index and archive my blog's content."),
            'INDEX,FOLLOW,NOARCHIVE'     => __("I would like search engines and archivers to index but not archive my blog's content."),
            'NOINDEX,NOFOLLOW,NOARCHIVE' => __("I would like to prevent search engines and archivers from indexing or archiving my blog's content."),
        ];

        // jQuery available versions
        $jquery_root = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'inc', 'js', 'jquery']);
        $stack       = [__('Default') . ' (' . App::config()->defaultJQuery() . ')' => ''];
        if (is_dir($jquery_root) && is_readable($jquery_root) && ($d = @dir($jquery_root)) !== false) {
            while (($entry = $d->read()) !== false) {
                if ($entry !== '.' && $entry !== '..' && !str_starts_with($entry, '.') && is_dir($jquery_root . '/' . $entry) && $entry !== App::config()->defaultJQuery()) {
                    $stack[$entry] = $entry;
                }
            }
        }
        self::$jquery_versions_combo = $stack;

        // SLeep mode timeout in second
        self::$sleepmode_timeout_combo = [
            new Option(__('Never'), (string) 0),
            new Option(__('Three months'), (string) 7_884_000),
            new Option(__('Six months'), (string) 15_768_000),
            new Option(__('One year'), (string) 31_536_000),
            new Option(__('Two years'), (string) 63_072_000),
        ];

        return self::status(true);
    }

    public static function process(): bool
    {
        if (self::$blog_id !== '' && $_POST !== [] && App::auth()->check(App::auth()->makePermissions(
            [
                App::auth()::PERMISSION_ADMIN,
            ]
        ), self::$blog_id)) {
            // Update a blog

            // Post data helpers
            $_Bool = fn (string $name): bool => !empty($_POST[$name]);
            $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
            $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

            $cur = App::blog()->openBlogCursor();

            $cur->blog_id   = $_Str('blog_id');
            $cur->blog_url  = preg_replace('/\?+$/', '?', $_Str('blog_url'));
            $cur->blog_name = $_Str('blog_name');
            $cur->blog_desc = $_Str('blog_desc');

            if (App::auth()->isSuperAdmin() && in_array($_POST['blog_status'], App::status()->blog()->combo())) {
                $cur->blog_status = $_Int('blog_status');
            }

            $media_img_t_size = $_Int('media_img_t_size');
            if ($media_img_t_size < 0) {
                $media_img_t_size = 100;
            }

            $media_img_s_size = $_Int('media_img_s_size');
            if ($media_img_s_size < 0) {
                $media_img_s_size = 240;
            }

            $media_img_m_size = $_Int('media_img_m_size');
            if ($media_img_m_size < 0) {
                $media_img_m_size = 448;
            }

            $media_video_width = $_Int('media_video_width');
            if ($media_video_width < 0) {
                $media_video_width = 400;
            }

            $media_video_height = $_Int('media_video_height');
            if ($media_video_height < 0) {
                $media_video_height = 300;
            }

            $nb_post_for_home = abs($_Int('nb_post_for_home'));
            if ($nb_post_for_home < 1) {
                $nb_post_for_home = 1;
            }

            $nb_post_per_page = abs($_Int('nb_post_per_page'));
            if ($nb_post_per_page < 1) {
                $nb_post_per_page = 1;
            }

            $nb_post_per_feed = abs($_Int('nb_post_per_feed'));
            if ($nb_post_per_feed < 1) {
                $nb_post_per_feed = 1;
            }

            $nb_comment_per_feed = abs($_Int('nb_comment_per_feed'));
            if ($nb_comment_per_feed < 1) {
                $nb_comment_per_feed = 1;
            }

            try {
                if ($cur->blog_id !== '' && $cur->blog_id !== self::$blog_id) {
                    $rs = App::blogs()->getBlog($cur->blog_id);
                    if ($rs->count() !== 0) {
                        throw new Exception(__('This blog ID is already used.'));
                    }
                }

                # --BEHAVIOR-- adminBeforeBlogUpdate -- Cursor, string
                App::behavior()->callBehavior('adminBeforeBlogUpdate', $cur, self::$blog_id);

                if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $_Str('lang'))) {
                    throw new Exception(__('Invalid language code'));
                }

                App::blogs()->updBlog(self::$blog_id, $cur);

                $blog_status = is_numeric($blog_status = $cur->blog_status) ? (int) $blog_status : 0;
                if (App::auth()->isSuperAdmin() && App::status()->blog()->isRestricted($blog_status)) {
                    // Remove this blog from user default blog
                    $blog_id = is_string($blog_id = $cur->blog_id) ? $blog_id : '';
                    if ($blog_id !== '') {
                        App::users()->removeUsersDefaultBlogs([
                            $blog_id,
                        ]);
                    }
                }

                # --BEHAVIOR-- adminAfterBlogUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterBlogUpdate', $cur, self::$blog_id);

                if (is_string($cur->blog_id) && $cur->blog_id !== '' && $cur->blog_id !== self::$blog_id) {
                    if (self::$blog_id === App::blog()->id()) {
                        App::blog()->loadFromBlog($cur->blog_id);
                        App::session()->set('sess_blog_id', $cur->blog_id);
                        self::$blog_settings = App::blog()->settings();
                    } else {
                        self::$blog_settings = App::blogSettings()->createFromBlog($cur->blog_id);
                    }

                    self::$blog_id = $cur->blog_id;
                }

                self::$blog_settings->get('system')->put('editor', $_Str('editor'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('copyright_notice', $_Str('copyright_notice'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('post_url_format', $_Str('post_url_format'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('lang', $_Str('lang'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('blog_timezone', $_Str('blog_timezone'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('date_format', $_Str('date_format'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('time_format', $_Str('time_format'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('comments_ttl', abs($_Int('comments_ttl')), App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('trackbacks_ttl', abs($_Int('trackbacks_ttl')), App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('allow_comments', $_Bool('allow_comments'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('allow_trackbacks', $_Bool('allow_trackbacks'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('comments_pub', !$_Bool('comments_pub'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('trackbacks_pub', !$_Bool('trackbacks_pub'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('comments_nofollow', $_Bool('comments_nofollow'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('wiki_comments', $_Bool('wiki_comments'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('comment_preview_optional', $_Bool('comment_preview_optional'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('note_title_tag', $_Str('note_title_tag'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('nb_post_for_home', $nb_post_for_home, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('nb_post_per_page', $nb_post_per_page, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('no_public_css', $_Bool('no_public_css'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('use_smilies', $_Bool('use_smilies'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('no_search', $_Bool('no_search'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('inc_subcats', $_Bool('inc_subcats'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('media_img_t_size', $media_img_t_size, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('media_img_s_size', $media_img_s_size, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('media_img_m_size', $media_img_m_size, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('media_thumbnail_prefix', $_Str('media_thumbnail_prefix'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_video_width', $media_video_width, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('media_video_height', $media_video_height, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('media_img_title_pattern', $_Str('media_img_title_pattern'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_img_use_dto_first', $_Bool('media_img_use_dto_first'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_img_no_date_alone', $_Bool('media_img_no_date_alone'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_img_default_size', $_Str('media_img_default_size'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_img_default_alignment', $_Str('media_img_default_alignment'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('media_img_default_link', $_Bool('media_img_default_link'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('media_img_default_legend', $_Str('media_img_default_legend'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('nb_post_per_feed', $nb_post_per_feed, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('nb_comment_per_feed', $nb_comment_per_feed, App::blogWorkspace()::NS_INT);
                self::$blog_settings->get('system')->put('short_feed_items', $_Bool('short_feed_items'), App::blogWorkspace()::NS_BOOL);
                if (isset($_POST['robots_policy'])) {
                    self::$blog_settings->get('system')->put('robots_policy', $_Str('robots_policy'), App::blogWorkspace()::NS_STRING);
                }
                self::$blog_settings->get('system')->put('allow_ai_tdm', $_Bool('allow_ai_tdm'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('legacy_needed', $_Bool('legacy_needed'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('jquery_needed', $_Bool('jquery_needed'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('jquery_version', $_Str('jquery_version'), App::blogWorkspace()::NS_STRING);
                self::$blog_settings->get('system')->put('prevents_clickjacking', $_Bool('prevents_clickjacking'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('static_home', $_Bool('static_home'), App::blogWorkspace()::NS_BOOL);
                self::$blog_settings->get('system')->put('static_home_url', $_Str('static_home_url'), App::blogWorkspace()::NS_STRING);

                self::$blog_settings->get('system')->put('sleepmode_timeout', $_Int('sleepmode_timeout'), App::blogWorkspace()::NS_INT);

                # --BEHAVIOR-- adminBeforeBlogSettingsUpdate -- BlogSettingsInterface
                App::behavior()->callBehavior('adminBeforeBlogSettingsUpdate', self::$blog_settings);

                if (App::auth()->isSuperAdmin() && in_array($_Str('url_scan'), self::$url_scan_combo)) {
                    self::$blog_settings->get('system')->put('url_scan', $_Str('url_scan'), App::blogWorkspace()::NS_STRING);
                }
                App::backend()->notices()->addSuccessNotice(__('Blog has been successfully updated.'));

                Http::redirect(sprintf(self::$redir, self::$blog_id));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        // Display
        if (self::$standalone) {
            $breadcrumb = App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(self::$blog_name) => '',
                    __('Blog settings')                => '',
                ]
            );
        } else {
            $breadcrumb = App::backend()->page()->breadcrumb(
                [
                    __('System')                                                     => '',
                    __('Blogs')                                                      => App::backend()->url()->get('admin.blogs'),
                    __('Blog settings') . ' : ' . Html::escapeHTML(self::$blog_name) => '',
                ]
            );
        }

        $editor      = App::auth()->prefs()->get('interface')->get('editor');
        $desc_editor = is_array($editor) && isset($editor['xhtml']) && is_string($editor['xhtml']) ? $editor['xhtml'] : '';

        $rte_flag  = true;
        $rte_flags = @App::auth()->prefs()->interface->rte_flags;
        if (is_array($rte_flags) && in_array('blog_descr', $rte_flags)) {
            $rte_flag = $rte_flags['blog_descr'];
        }

        App::backend()->page()->open(
            __('Blog settings'),
            App::backend()->page()->jsJson(
                'blog_pref',
                [
                    'url' => [
                        'popup_posts' => App::backend()->url()->get('admin.posts.popup', [
                            'popup'     => 1,
                            'plugin_id' => 'admin.blog_pref',
                            'type'      => 'page',
                        ], '&'),
                    ],
                    'msg' => [
                        'warning_path_info'    => __('Warning: except for special configurations, it is generally advised to have a trailing "/" in your blog URL in PATH_INFO mode.'),
                        'warning_query_string' => __('Warning: except for special configurations, it is generally advised to have a trailing "?" in your blog URL in QUERY_STRING mode.'),
                        'example_prefix'       => __('Sample:') . ' ',
                    ],
                ]
            ) .
            App::backend()->page()->jsConfirmClose('blog-form') .
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            ($rte_flag ? App::behavior()->callBehavior('adminPostEditor', $desc_editor, 'blog_desc', ['#blog_desc'], 'xhtml') : '') .
            App::backend()->page()->jsLoad('js/_blog_pref.js') .

            # --BEHAVIOR-- adminBlogPreferencesHeaders --
            App::behavior()->callBehavior('adminBlogPreferencesHeaders') .

            App::backend()->page()->jsPageTabs(),
            $breadcrumb
        );

        if (self::$blog_id !== '') {
            if (!empty($_GET['add'])) {
                App::backend()->notices()->success(__('Blog has been successfully created.'));
            }

            if (!empty($_GET['upd'])) {
                App::backend()->notices()->success(__('Blog has been successfully updated.'));
            }

            // Prepare tabs' content
            $tabs = [];

            // Blog parameters
            $prefs = [];

            // Standard parameters
            $standard = [];

            // Blog details
            $details = [];
            if (App::auth()->isSuperAdmin()) {
                $details[] = (new Select('blog_status'))
                    ->items(App::status()->blog()->combo())
                    ->default((string) self::$blog_status)
                    ->label(new Label(__('Blog status:'), Label::IL_TF));
            } else {
                /*
                 * Only super admins can change the blog ID and URL, but we need to pass
                 * their values to the POST request via hidden html input values  so as
                 * to allow admins to update other settings.
                 * Otherwise App::blogs()->getBlogCursor() throws an exception.
                 */
                $details[] = (new Hidden('blog_id', Html::escapeHTML(self::$blog_id)));
                $details[] = (new Hidden('blog_url', Html::escapeHTML(self::$blog_url)));
            }

            $standard[] = (new Fieldset('blog-details'))
                ->legend(new Legend(__('Blog details')))
                ->fields([
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Input('blog_name'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(self::$blog_name))
                                ->required(true)
                                ->placeholder(__('Blog name'))
                                ->title(__('Required field'))
                                ->lang(self::$blog_settings->get('system')->getStr('lang') ?? '')
                                ->spellcheck(true)
                                ->label(new Label((new Span('*'))->render() . __('Blog name:'), Label::IL_TF))
                                ->class('required'),
                        ]),
                    (new Para())
                        ->items([
                            (new Textarea('blog_desc', Html::escapeHTML(self::$blog_desc)))
                                ->rows(5)
                                ->cols(60)
                                ->lang(self::$blog_settings->get('system')->getStr('lang') ?? '')
                                ->spellcheck(true)
                                ->label(new Label(__('Blog description:'), Label::OL_TF)),
                        ]),
                    ... $details,
                ]);

            // Blog configuration
            $zones = [];
            foreach (Date::getZones(true, true) as $key => $value) {
                $zones[] = (new Optgroup($key))
                    ->items(array_map(fn ($key, $val): Option => new Option($key, $val), array_keys($value), array_values($value)));
            }

            $standard[] = (new Fieldset('blog-configuration'))
                ->legend(new Legend(__('Blog configuration')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Input('editor'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('editor')))
                                ->label(new Label(__('Blog editor name:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('lang'))
                                ->items(App::backend()->combos()->getAdminLangsCombo())
                                ->default((string) self::$blog_settings->get('system')->getStr('lang'))
                                ->translate(false)
                                ->label(new Label(__('Default language:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('blog_timezone'))
                                ->items($zones)
                                ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('blog_timezone')))
                                ->label(new Label(__('Blog timezone:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Input('copyright_notice'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('copyright_notice')))
                                ->lang(self::$blog_settings->get('system')->getStr('lang') ?? '')
                                ->spellcheck(true)
                                ->label(new Label(__('Copyright notice:'), Label::IL_TF)),
                        ]),
                ]);

            // Comments and trackbacks
            $standard[] = (new Fieldset('comments-trackbacks'))
                ->legend(new Legend(__('Comments and trackbacks')))
                ->fields([
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('allow_comments', (bool) self::$blog_settings->get('system')->get('allow_comments')))
                                                ->value(1)
                                                ->label(new Label(__('Accept comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comments_pub', ! (bool) self::$blog_settings->get('system')->get('comments_pub')))
                                                ->value(1)
                                                ->label(new Label(__('Moderate comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('comments_ttl', 0, 999, self::$blog_settings->get('system')->getInt('comments_ttl') ?? 0))
                                                ->label((new Label(__('Leave comments open for'), Label::IL_TF))
                                                    ->suffix(__('days')))
                                                ->extra('aria-describedby="comments_ttl_help"'),
                                        ]),
                                    (new Note())
                                        ->class('form-note')
                                        ->text(__('No limit: set to 0 (zero).'))
                                        ->id('comments_ttl_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('wiki_comments', (bool) self::$blog_settings->get('system')->get('wiki_comments')))
                                                ->value(1)
                                                ->label(new Label(__('Wiki syntax for comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comment_preview_optional', (bool) self::$blog_settings->get('system')->get('comment_preview_optional')))
                                                ->value(1)
                                                ->label(new Label(__('Preview of comment before submit is not mandatory'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('allow_trackbacks', (bool) self::$blog_settings->get('system')->get('allow_trackbacks')))
                                                ->value(1)
                                                ->label(new Label(__('Accept trackbacks'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('trackbacks_pub', ! (bool) self::$blog_settings->get('system')->get('trackbacks_pub')))
                                                ->value(1)
                                                ->label(new Label(__('Moderate trackbacks'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('trackbacks_ttl', 0, 999, self::$blog_settings->get('system')->getInt('trackbacks_ttl') ?? 0))
                                                ->label((new Label(__('Leave trackbacks open for'), Label::IL_TF))
                                                    ->suffix(__('days')))
                                                ->extra('aria-describedby="trackbacks_ttl_help"'),
                                        ]),
                                    (new Note())
                                        ->class('form-note')
                                        ->text(__('No limit: set to 0 (zero).'))
                                        ->id('trackbacks_ttl_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comments_nofollow', (bool) self::$blog_settings->get('system')->get('comments_nofollow')))
                                                ->value(1)
                                                ->label(new Label(__('Add "nofollow" relation on comments and trackbacks links'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Para())
                                ->class('col100')
                                ->items([
                                    (new Select('sleepmode_timeout'))
                                        ->items(self::$sleepmode_timeout_combo)
                                        ->default((string) self::$blog_settings->get('system')->getInt('sleepmode_timeout'))
                                        ->label(new Label(__('Disable all comments and trackbacks on the blog after a period of time without new posts:'), Label::IL_TF)),
                                ]),
                        ]),
                ]);

            // Blog presentation
            $standard[] = (new Fieldset('blog-presentation'))
                ->legend(new Legend(__('Blog presentation')))
                ->fields([
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            (new Input('date_format'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->value(self::$blog_settings->get('system')->getStr('date_format') ?? '')
                                                ->label(new Label(__('Date format:'), Label::OL_TF))
                                                ->translate(false)
                                                ->extra('aria-describedby="date_format_help"'),
                                            (new Select('date_format_select'))
                                                ->items(self::$date_formats_combo)
                                                ->translate(false)
                                                ->title(__('Pattern of date')),
                                        ]),
                                    (new Note())
                                        ->class(['chosen', 'form-note'])
                                        ->text(__('Sample:') . ' ' . Date::str(Html::escapeHTML(self::$blog_settings->get('system')->getStr('date_format') ?? '')))
                                        ->id('date_format_help'),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            (new Input('time_format'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->value(self::$blog_settings->get('system')->getStr('time_format') ?? '')
                                                ->label(new Label(__('Time format:'), Label::OL_TF))
                                                ->translate(false)
                                                ->extra('aria-describedby="time_format_help"'),
                                            (new Select('time_format_select'))
                                                ->items(self::$time_formats_combo)
                                                ->translate(false)
                                                ->title(__('Pattern of time')),
                                        ]),
                                    (new Note())
                                        ->class(['chosen', 'form-note'])
                                        ->text(__('Sample:') . ' ' . Date::str(Html::escapeHTML(self::$blog_settings->get('system')->getStr('time_format') ?? '')))
                                        ->id('time_format_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('no_public_css', (bool) self::$blog_settings->get('system')->get('no_public_css')))
                                                ->value(1)
                                                ->label(new Label(__('Don\'t load standard stylesheet (used for media alignement)'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('use_smilies', (bool) self::$blog_settings->get('system')->get('use_smilies')))
                                                ->value(1)
                                                ->label(new Label(__('Display smilies on entries and comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('no_search', (bool) self::$blog_settings->get('system')->get('no_search')))
                                                ->value(1)
                                                ->label(new Label(__('Disable internal search system'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_for_home', 1, 999, self::$blog_settings->get('system')->getInt('nb_post_for_home') ?? 0))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries on first page'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_per_page', 1, 999, self::$blog_settings->get('system')->getInt('nb_post_per_page') ?? 0))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries per page'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_per_feed', 1, 999, self::$blog_settings->get('system')->getInt('nb_post_per_feed') ?? 0))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries per feed'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_comment_per_feed', 1, 999, self::$blog_settings->get('system')->getInt('nb_comment_per_feed') ?? 0))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('comments per feed'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('short_feed_items', (bool) self::$blog_settings->get('system')->get('short_feed_items')))
                                                ->value(1)
                                                ->label(new Label(__('Truncate feeds'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('inc_subcats', (bool) self::$blog_settings->get('system')->get('inc_subcats')))
                                                ->value(1)
                                                ->label(new Label(__('Include sub-categories in category page and category posts feed'), Label::IL_FT)),
                                        ]),
                                ]),
                        ]),
                    (new Single('hr'))
                        ->class('clear'),
                    (new Para())
                        ->items([
                            (new Checkbox('static_home', (bool) self::$blog_settings->get('system')->get('static_home')))
                                ->value(1)
                                ->label(new Label(__('Display an entry as static home page'), Label::IL_FT)),
                        ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Input('static_home_url'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML(self::$blog_settings->get('system')->getStr('static_home_url') ?? ''))
                                ->label(new Label(__('Entry URL (its content will be used for the static home page):'), Label::IL_TF))
                                ->translate(false)
                                ->extra('aria-describedby="static_home_url_help"'),
                            (new Button('static_home_url_selector', __('Choose an entry'))),
                        ]),
                    (new Note())
                        ->class('form-note')
                        ->text(__('Leave empty to use the default presentation.'))
                        ->id('static_home_url_help'),
                ]);

            // Media and images
            $standard[] = (new Fieldset('medias-settings'))
                ->legend(new Legend(__('Media and images')))
                ->fields([
                    (new Div())
                        ->class('two-cols')
                        ->items([
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Div())
                                        ->items([
                                            (new Text('h5', __('Generated image sizes (max dimension in pixels)'))),
                                            (new Note())
                                                ->class(['form-note', 'warning'])
                                                ->text(__('Please note that if you change current settings bellow, they will now apply to all new images in the media manager.') . ' ' . __('Be carefull if you share it with other blogs in your installation.') . '<br>' . __('Set -1 to use the default size, set 0 to ignore this thumbnail size (images only).')),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_img_t_size', -1, 999, self::$blog_settings->get('system')->getInt('media_img_t_size') ?? 0))
                                                        ->label(new Label(__('Thumbnail'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_img_s_size', -1, 999, self::$blog_settings->get('system')->getInt('media_img_s_size') ?? 0))
                                                        ->label(new Label(__('Small'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_img_m_size', -1, 999, self::$blog_settings->get('system')->getInt('media_img_m_size') ?? 0))
                                                        ->label(new Label(__('Medium'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Input('media_thumbnail_prefix'))
                                                        ->size(1)
                                                        ->maxlength(1)
                                                        ->value(self::$blog_settings->get('system')->getStr('media_thumbnail_prefix') ?? '')
                                                        ->translate(false)
                                                        ->label(new Label(__('Thumbnail character prefix:'), Label::OL_TF)),
                                                ]),
                                            (new Note())
                                                ->class(['form-note', 'info'])
                                                ->text(__('Leave empty to use the default one (.)')),
                                        ]),
                                    (new Div())
                                        ->items([
                                            (new Text('h5', __('Default size of the inserted video (in pixels)'))),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_video_width', -1, 999, self::$blog_settings->get('system')->getInt('media_video_width') ?? 0))
                                                        ->label(new Label(__('Width'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_video_height', -1, 999, self::$blog_settings->get('system')->getInt('media_video_height') ?? 0))
                                                        ->label(new Label(__('Height'), Label::OL_TF)),
                                                ]),
                                            (new Note())
                                                ->class(['form-note', 'info'])
                                                ->text(__('A value of 0 means that the corresponding size is not included when inserting a video. A value of -1 restores the default value.')),
                                        ]),
                                ]),
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Div())
                                        ->items([
                                            (new Text('h5', __('Default image insertion attributes'))),
                                            (new Para())
                                                ->items([
                                                    (new Select('media_img_title_pattern'))
                                                        ->items(self::$img_title_combo)
                                                        ->default(Html::escapeHTML(self::$media_img_title_pattern))
                                                        ->label(new Label(__('Inserted image legend:'), Label::IL_TF)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_use_dto_first', (bool) self::$blog_settings->get('system')->get('media_img_use_dto_first')))
                                                        ->value(1)
                                                        ->label(new Label(__('Use original media date if possible'), Label::IL_FT)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_no_date_alone', (bool) self::$blog_settings->get('system')->get('media_img_no_date_alone')))
                                                        ->value(1)
                                                        ->label(new Label(__('Do not display date if alone in title'), Label::IL_FT))
                                                        ->extra('aria-describedby="media_img_no_date_alone_help"'),
                                                ]),
                                            (new Note())
                                                ->class(['form-note', 'info'])
                                                ->text(__('It is retrieved from the picture\'s metadata.'))
                                                ->id('media_img_no_date_alone_help'),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Select('media_img_default_size'))
                                                        ->items(self::$img_default_size_combo)
                                                        ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('media_img_default_size') ?? '') !== '' ? Html::escapeHTML(self::$blog_settings->get('system')->getStr('media_img_default_size')) : 'm')
                                                        ->label(new Label(__('Size of inserted image:'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Select('media_img_default_alignment'))
                                                        ->items(self::$img_default_alignment_combo)
                                                        ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('media_img_default_alignment') ?? ''))
                                                        ->label(new Label(__('Image alignment:'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_default_link', (bool) self::$blog_settings->get('system')->get('media_img_default_link')))
                                                        ->value(1)
                                                        ->label(new Label(__('Insert a link to the original image'), Label::IL_FT)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Select('media_img_default_legend'))
                                                        ->items(self::$img_default_legend_combo)
                                                        ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('media_img_default_legend') ?? ''))
                                                        ->label(new Label(__('Image legend and alternate text:'), Label::IL_TF)),
                                                ]),
                                        ]),
                                ]),
                        ]),
                ]);

            $prefs[] = (new Div('standard-pref'))
                ->items([
                    (new Text('h3', __('Blog parameters'))),
                    ... $standard,
                ]);

            // Advanced parameters
            $advanced = [];

            // Blog details
            if (App::auth()->isSuperAdmin()) {
                // Check URL of blog by testing it's ATOM feed
                $message = (new None());

                try {
                    $file    = self::$blog_url . App::url()->getURLFor('feed', 'atom');
                    $path    = '';
                    $status  = 404;
                    $content = '';

                    $client = HttpClient::initClient($file, $path);
                    if ($client !== false) {
                        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) && is_string($user_agent = $_SERVER['HTTP_USER_AGENT']) ? $user_agent : '';
                        $client->setTimeout(App::config()->queryTimeout());
                        $client->setUserAgent($user_agent);
                        $client->get($path);
                        $status  = $client->getStatus();
                        $content = $client->getContent();
                    }
                    if ($status !== 200) {
                        // Might be 404 (URL not found), 670 (blog not online), ...
                        $message = (new Note())
                            ->class(['form-note', 'warn'])
                            ->text(sprintf(
                                __('The URL of blog or the URL scan method might not be well set (<code>%1$s</code> return a <strong>%2$s</strong> status).'),
                                Html::escapeHTML($file),
                                (string) $status
                            ));
                    } elseif (!str_starts_with($content, '<?xml ')) {
                        // Not well formed XML feed
                        $message = (new Note())
                            ->class(['form-note', 'warn'])
                            ->text(sprintf(
                                __('The URL of blog or the URL scan method might not be well set (<code>%s</code> does not return an ATOM feed).'),
                                Html::escapeHTML($file)
                            ));
                    }
                } catch (Exception $e) {
                    App::error()->add($e->getMessage());
                }

                $advanced[] = (new Fieldset('blog_details'))
                    ->legend(new Legend(__('Blog details')))
                    ->fields([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Span('*'))->class('required')->render())),
                        (new Para())
                            ->items([
                                (new Input('blog_id'))
                                    ->size(30)
                                    ->maxlength(32)
                                    ->value(Html::escapeHTML(self::$blog_id))
                                    ->required(true)
                                    ->placeholder(__('Blog ID'))
                                    ->title(__('Required field'))
                                    ->translate(false)
                                    ->label(new Label((new Span('*'))->render() . __('Blog ID:'), Label::IL_TF))
                                    ->class('required'),
                            ]),
                        (new Note())
                            ->class('form-note')
                            ->text(__('At least 2 characters using letters, numbers or symbols.'))
                            ->id('blog_id_help'),
                        (new Note())
                            ->class(['form-note', 'warn'])
                            ->text(__('Please note that changing your blog ID may require changes in your public index.php file.'))
                            ->id('blog_id_warn'),
                        (new Para())
                            ->items([
                                (new Url('blog_url'))
                                    ->size(50)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$blog_url))
                                    ->required(true)
                                    ->placeholder(__('Blog URL'))
                                    ->title(__('Required field'))
                                    ->translate(false)
                                    ->label(new Label((new Span('*'))->render() . __('Blog URL:'), Label::IL_TF))
                                    ->class('required'),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('url_scan'))
                                    ->items(self::$url_scan_combo)
                                    ->default(self::$blog_settings->get('system')->getStr('url_scan'))
                                    ->label(new Label(__('URL scan method:'), Label::IL_TF)),
                            ]),
                        $message,
                    ]);
            }

            // Blog configuration
            $advanced[] = (new Fieldset('blog_config'))
                ->legend(new Legend(__('Blog configuration')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Select('post_url_format'))
                                ->items(self::$post_url_combo)
                                ->default(Html::escapeHTML(self::$blog_settings->get('system')->getStr('post_url_format') ?? ''))
                                ->translate(false)
                                ->label(new Label(__('New post URL format:'), Label::IL_TF))
                                ->extra('aria-describedby="post_url_format_help"'),
                        ]),
                    (new Para())
                        ->class(['chosen', 'form-note'])
                        ->id('post_url_format_help')
                        ->items([
                            (new Text(null, __('Sample:') . ' ' . App::blog()->getPostURL('', date('Y-m-d H:i:00', self::$now), __('Dotclear'), 42))),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('note_title_tag'))
                                ->items(self::$note_title_tag_combo)
                                ->default(self::$blog_settings->get('system')->getStr('note_title_tag') ?? '')
                                ->label(new Label(__('HTML tag for the title of the notes on the blog:'), Label::IL_TF)),
                        ]),
                ]);

            // Search engine policies
            $policies = function () {
                $index = 0;
                foreach (self::$robots_policy_options as $key => $value) {
                    yield (new Para())
                        ->items([
                            (new Radio(['robots_policy', 'robots_policy-' . $index], self::$blog_settings->get('system')->get('robots_policy') === $key))
                                ->value($key)
                                ->label(new Label($value, Label::IL_FT)),
                        ]);
                }
            };
            $advanced[] = (new Fieldset('blog_robots'))
                ->legend(new Legend(__('Search engines robots policy')))
                ->fields($policies());

            // AI text and data mining
            $advanced[] = (new Fieldset('blog_ai'))
                ->legend(new Legend(__('AI text and data mining')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('allow_ai_tdm', (bool) self::$blog_settings->get('system')->get('allow_ai_tdm')))
                                ->value(1)
                                ->label(new Label(__('Allow text and data analysis by AIs’ crawlers'), Label::IL_FT)),
                        ]),
                ]);

            // Legacy JS library
            $advanced[] = (new Fieldset('blog_legacy_js'))
                ->legend(new Legend(__('Legacy javascript library')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('legacy_needed', (bool) self::$blog_settings->get('system')->get('legacy_needed')))
                                ->value(1)
                                ->label(new Label(__('Load the Legacy JS library'), Label::IL_FT)),
                        ]),
                ]);

            // jQuery
            $advanced[] = (new Fieldset('blog_jquery_js'))
                ->legend(new Legend(__('jQuery javascript library')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('jquery_needed', (bool) self::$blog_settings->get('system')->get('jquery_needed')))
                                ->value(1)
                                ->label(new Label(__('Load the jQuery library'), Label::IL_FT)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('jquery_version'))
                                ->items(self::$jquery_versions_combo)
                                ->default(self::$blog_settings->get('system')->getStr('jquery_version'))
                                ->label(new Label(__('jQuery version to be loaded for this blog:'), Label::IL_TF)),
                        ]),
                ]);

            // Blog security
            $advanced[] = (new Fieldset('blog_security'))
                ->legend((new Legend(__('Blog security'))))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('prevents_clickjacking', (bool) self::$blog_settings->get('system')->get('prevents_clickjacking')))
                                ->value(1)
                                ->label(
                                    new Label(sprintf(
                                        __('Protect the blog from Clickjacking (see <a href="%s">Wikipedia</a>)'),
                                        'https://en.wikipedia.org/wiki/Clickjacking'
                                    ), Label::IL_FT)
                                ),
                        ]),
                ]);

            $prefs[] = (new Div('advanced-pref'))
                ->items([
                    (new Text('h3', __('Advanced parameters'))),
                    ... $advanced,
                ]);

            // Plugins parameters
            $prefs[] = (new Div('plugins-pref'))
                ->items([
                    (new Text('h3', __('Plugins parameters'))),
                    (new Capture(
                        App::behavior()->callBehavior(...),
                        ['adminBlogPreferencesFormV2', self::$blog_settings]
                    )),
                ]);

            // Buttons
            $prefs[] = (new Para())
                ->class('form-buttons')
                ->items([
                    (new Submit('submit-params', __('Save')))
                        ->accesskey('s'),
                    (new Button('go-back', __('Back')))
                        ->class(['go-back', 'reset', 'hidden-if-no-js']),
                    self::$standalone ? (new None()) : (new Hidden('id', self::$blog_id)),
                    App::nonce()->formNonce(),
                ]);

            // Additional stuff
            if (App::auth()->isSuperAdmin() && self::$blog_id !== App::blog()->id()) {
                $additional = (new Form('del-blog'))
                    ->action(App::backend()->url()->get('admin.blog.del'))
                    ->method('post')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Submit('submit-del-blog', __('Delete this blog')))
                                    ->class('delete'),
                                (new Hidden(['blog_id'], self::$blog_id)),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            } elseif (self::$blog_id === App::blog()->id()) {
                $additional = (new Note())
                    ->class('message')
                    ->text(__('The current blog cannot be deleted.'));
            } else {
                $additional = (new Note())
                    ->class('message')
                    ->text(__('Only superadmin can delete a blog.'));
            }

            $tabs[] = (new Div('params'))
                ->title(__('Parameters'))
                ->class('multi-part')
                ->items([
                    (new Form('blog-form'))
                        ->action(self::$action)
                        ->method('post')
                        ->fields($prefs),
                    $additional,
                ]);

            // Blog users
            $users = [];

            // Users on the blog (with permissions)
            $blog_users = App::blogs()->getBlogPermissions(self::$blog_id, App::auth()->isSuperAdmin());
            $perm_types = App::auth()->getPermissionsTypes();

            if ($blog_users === []) {
                $users[] = (new Note())
                    ->text(__('No users'));
            } else {
                if (App::auth()->isSuperAdmin()) {
                    $user_url_p = (new Link())
                        ->href(App::backend()->url()->get('admin.user', ['id' => '%1$s'], '&amp;', true))
                        ->text('%1$s')
                    ->render();
                } else {
                    $user_url_p = '%1$s';
                }

                // Sort users list on user_id key
                App::lexical()->lexicalKeySort($blog_users, App::lexical()::ADMIN_LOCALE);

                $post_types      = App::postTypes()->dump();
                $current_blog_id = App::blog()->id();
                if (self::$blog_id !== App::blog()->id()) {
                    App::blog()->loadFromBlog(self::$blog_id);
                }

                // Prepare user list
                foreach ($blog_users as $k => $v) {
                    // Check if user has at least one permission or is superadmin
                    if (count($v['p']) > 0 || $v['super']) {
                        $name        = is_string($v['name']) ? $v['name'] : '';
                        $firstname   = is_string($v['firstname']) ? $v['firstname'] : '';
                        $displayname = is_string($v['displayname']) ? $v['displayname'] : '';

                        // User email
                        $mail = is_string($v['email']) && $v['email'] !== '' ?
                            (new Link())
                                ->href('mailto:' . $v['email'])
                                ->text($v['email'])
                            ->render() :
                            __('(none)');

                        // User publications
                        $pubs = [];
                        foreach ($post_types as $pt) {
                            $prefs = [
                                'post_type' => $pt->get('type'),
                                'user_id'   => $k,
                            ];
                            $pubs[] = (new Li())
                                ->text(sprintf(__('%1$s: %2$s'), __($pt->get('label')), App::blog()->getPosts($prefs, true)->cardinal()));
                        }

                        // User permissions
                        $perms = [];
                        if ($v['super']) {
                            $perms[] = (new Li())
                                ->class('user_super')
                                ->items([
                                    (new Text(null, __('Super administrator'))),
                                    (new Single('br')),
                                    (new Span(__('All rights on all blogs.')))
                                        ->class('form-note'),
                                ]);
                        } else {
                            /**
                             * @var non-empty-array<string, bool> $user_permissions (see Auth::parsePermissions())
                             */
                            $user_permissions = $v['p'];
                            foreach (array_keys($user_permissions) as $p) {
                                $perm = (new Li());
                                if ($p === 'admin') {
                                    $super = (new Set())
                                        ->items([
                                            (new Single('br')),
                                            (new Span(__('All rights on this blog.')))
                                                ->class('form-note'),
                                        ]);
                                } else {
                                    $super = (new None());
                                }
                                if (isset($perm_types[$p])) {
                                    if ($p === 'admin') {
                                        $perm->class('user_admin');
                                    }
                                    $perm->items([
                                        (new Text(null, __($perm_types[$p]))),
                                        $super,
                                    ]);
                                } else {
                                    $perm->items([
                                        (new Text(null, sprintf(__('[%s] (unreferenced permission)'), $p))),
                                        $super,
                                    ]);
                                }
                                $perms[] = $perm;
                            }
                        }

                        // User actions
                        if (!$v['super'] && App::auth()->isSuperAdmin()) {
                            $action = (new Form(['user-action']))
                                ->action(App::backend()->url()->get('admin.user.actions'))
                                ->method('post')
                                ->fields([
                                    (new Para())
                                        ->class('change-user-perm')
                                        ->items([
                                            (new Submit('submit-user-perm'))
                                                ->class('reset')
                                                ->value(__('Change permissions')),
                                            (new Hidden(['redir'], App::backend()->url()->get('admin.blog.pref', ['id' => $k], '&') . '#users')),
                                            (new Hidden(['redir_label'], __('Back to user card'))),
                                            (new Hidden(['action'], 'perms')),
                                            (new Hidden(['users[]'], $k)),
                                            (new Hidden(['blogs[]'], self::$blog_id)),
                                            App::nonce()->formNonce(),
                                        ]),
                                ]);
                        } else {
                            $action = (new None());
                        }

                        // User card
                        $users[] = (new Div())
                            ->class(array_filter(['user-perm', ($v['super'] ? 'user_super' : '')]))
                            ->items([
                                (new Text('h4', sprintf($user_url_p, Html::escapeHTML($k)) . ' (' . Html::escapeHTML(App::users()->getUserCN($k, $name, $firstname, $displayname)) . ')')),
                                App::auth()->isSuperAdmin() ?
                                    (new Para())
                                        ->items([
                                            (new Text(null, __('Email:') . ' ' . $mail)),
                                        ]) :
                                    (new None()),
                                (new Text('h5', __('Publications on this blog:'))),
                                (new Ul())
                                    ->items($pubs),
                                (new Text('h5', __('Permissions:'))),
                                (new Ul())
                                    ->items($perms),
                                $action,
                            ]);
                    }
                }

                if ($current_blog_id !== App::blog()->id()) {
                    App::blog()->loadFromBlog($current_blog_id);
                }
            }

            $tabs[] = (new Div('users'))
                ->title(__('Users'))
                ->class('multi-part')
                ->items([
                    (new Text('h3', __('Users on this blog')))
                        ->class('out-of-screen-if-js'),
                    (new Div('blog-users'))
                        ->items($users),
                ]);

            // Render
            echo (new Set())
                ->items($tabs)
            ->render();
        }

        App::backend()->page()->helpBlock('core_blog_pref');
        App::backend()->page()->close();
    }
}
