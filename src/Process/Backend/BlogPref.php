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
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
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
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Radio;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Single;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @since 2.27 Before as admin/blog_pref.php
 */
class BlogPref extends Process
{
    public static function init(): bool
    {
        /**
         * Alias for App::backend()
         *
         * @var \Dotclear\Core\Backend\Utility
         */
        $data = App::backend();

        /*
         * Is standalone blog preferences?
         *
         * - true: come directly from blog's paramaters menu entry (or link)
         * - false: come from in blogs management (may be on a different blog ID than current)
         *
         * @var        bool
         */
        $data->standalone = !($data->edit_blog_mode ?? false);
        if ($data->standalone) {
            Page::check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]));

            $data->blog_id       = App::blog()->id();
            $data->blog_status   = App::blog()->status();
            $data->blog_name     = App::blog()->name();
            $data->blog_desc     = App::blog()->desc();
            $data->blog_settings = App::blog()->settings();
            $data->blog_url      = App::blog()->url();

            $data->action = App::backend()->url()->get('admin.blog.pref');
            $data->redir  = App::backend()->url()->get('admin.blog.pref');
        } else {
            Page::checkSuper();

            $data->blog_id       = false;
            $data->blog_status   = App::blog()::BLOG_OFFLINE;
            $data->blog_name     = '';
            $data->blog_desc     = '';
            $data->blog_settings = null;
            $data->blog_url      = '';

            try {
                if (empty($_REQUEST['id'])) {
                    throw new Exception(__('No given blog id.'));
                }

                $rs = App::blogs()->getBlog($_REQUEST['id']);
                if ($rs->count() === 0) {
                    throw new Exception(__('No such blog.'));
                }

                $data->blog_id       = $rs->blog_id;
                $data->blog_status   = $rs->blog_status;
                $data->blog_name     = $rs->blog_name;
                $data->blog_desc     = $rs->blog_desc;
                $data->blog_settings = App::blogSettings()->createFromBlog($data->blog_id);
                $data->blog_url      = $rs->blog_url;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }

            $data->action = App::backend()->url()->get('admin.blog');
            $data->redir  = App::backend()->url()->get('admin.blog', ['id' => '%s'], '&', true);
        }

        // Language codes
        $data->lang_combo = Combos::getAdminLangsCombo();

        // Status combo
        $data->status_combo = Combos::getBlogStatusesCombo();

        // Date format combo
        $data->now = time();

        $date_formats = $data->blog_settings?->system->date_formats;
        $time_formats = $data->blog_settings?->system->time_formats;

        $stack = ['' => ''];
        foreach ($date_formats as $format) {
            $stack[Date::str($format, $data->now)] = $format;
        }
        $data->date_formats_combo = $stack;

        $stack = ['' => ''];
        foreach ($time_formats as $format) {
            $stack[Date::str($format, $data->now)] = $format;
        }
        $data->time_formats_combo = $stack;

        // URL scan modes
        $data->url_scan_combo = [
            'PATH_INFO'    => 'path_info',
            'QUERY_STRING' => 'query_string',
        ];

        // Post URL combo
        $data->post_url_combo = [
            __('year/month/day/title') => '{y}/{m}/{d}/{t}',
            __('year/month/title')     => '{y}/{m}/{t}',
            __('year/title')           => '{y}/{t}',
            __('title')                => '{t}',
            __('post id/title')        => '{id}/{t}',
            __('post id')              => '{id}',
        ];
        if (!in_array($data->blog_settings?->system->post_url_format, $data->post_url_combo)) {
            $data->post_url_combo[Html::escapeHTML($data->blog_settings?->system->post_url_format)] = Html::escapeHTML($data->blog_settings?->system->post_url_format);
        }

        // Note title tag combo
        $data->note_title_tag_combo = [
            __('H4') => 0,
            __('H3') => 1,
            __('P')  => 2,
        ];

        // Image title combo
        $data->img_title_combo = [
            __('(none)')                           => '',
            __('Description')                      => 'Description ;; separator(, )',
            __('Description, Date')                => 'Description ;; Date(%b %Y) ;; separator(, )',
            __('Description, Country, Date')       => 'Description ;; Country ;; Date(%b %Y) ;; separator(, )',
            __('Description, City, Country, Date') => 'Description ;; City ;; Country ;; Date(%b %Y) ;; separator(, )',
        ];
        $data->media_img_title_pattern = $data->blog_settings?->system->media_img_title_pattern;
        if (!in_array($data->media_img_title_pattern, $data->img_title_combo)) {
            // Convert old patterns (with Title ;;) to new ones (with Description ;;)
            $old_img_title_combo = [
                'Title ;; separator(, )'                                   => 'Description ;; separator(, )',
                'Title ;; Date(%b %Y) ;; separator(, )'                    => 'Description ;; Date(%b %Y) ;; separator(, )',
                'Title ;; Country ;; Date(%b %Y) ;; separator(, )'         => 'Description ;; Country ;; Date(%b %Y) ;; separator(, )',
                'Title ;; City ;; Country ;; Date(%b %Y) ;; separator(, )' => 'Description ;; City ;; Country ;; Date(%b %Y) ;; separator(, )',
            ];
            if (in_array($data->media_img_title_pattern, $old_img_title_combo)) {
                // Store new pattern (with Description ;;)
                $data->blog_settings?->system->put('media_img_title_pattern', $old_img_title_combo[$data->media_img_title_pattern]);
                $data->media_img_title_pattern = $old_img_title_combo[$data->media_img_title_pattern];
            } else {
                // Add custom pattern to combo
                $data->img_title_combo = [
                    ...$data->img_title_combo,
                    Html::escapeHTML($data->media_img_title_pattern) => Html::escapeHTML($data->media_img_title_pattern),
                ];
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
        $data->img_default_size_combo = $stack;

        // Image default alignment combo
        $data->img_default_alignment_combo = [
            __('None')   => 'none',
            __('Left')   => 'left',
            __('Right')  => 'right',
            __('Center') => 'center',
        ];

        // Image default legend and alternate text combo
        $data->img_default_legend_combo = [
            __('Legend and alternate text') => 'legend',
            __('Alternate text')            => 'title',
            __('None')                      => 'none',
        ];

        // Robots policy options
        $data->robots_policy_options = [
            'INDEX,FOLLOW'               => __("I would like search engines and archivers to index and archive my blog's content."),
            'INDEX,FOLLOW,NOARCHIVE'     => __("I would like search engines and archivers to index but not archive my blog's content."),
            'NOINDEX,NOFOLLOW,NOARCHIVE' => __("I would like to prevent search engines and archivers from indexing or archiving my blog's content."),
        ];

        // jQuery available versions
        $jquery_root = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'inc', 'js', 'jquery']);
        $stack       = [__('Default') . ' (' . App::config()->defaultJQuery() . ')' => ''];
        if (is_dir($jquery_root) && is_readable($jquery_root) && ($d = @dir($jquery_root)) !== false) {
            while (($entry = $d->read()) !== false) {
                if ($entry != '.' && $entry != '..' && !str_starts_with($entry, '.') && is_dir($jquery_root . '/' . $entry) && $entry != App::config()->defaultJQuery()) {
                    $stack[$entry] = $entry;
                }
            }
        }
        $data->jquery_versions_combo = $stack;

        // SLeep mode timeout in second
        $data->sleepmode_timeout_combo = [
            __('Never')        => 0,
            __('Three months') => 7_884_000,
            __('Six months')   => 15_768_000,
            __('One year')     => 31_536_000,
            __('Two years')    => 63_072_000,
        ];

        return self::status(true);
    }

    public static function process(): bool
    {
        /**
         * Alias for App::backend()
         *
         * @var \Dotclear\Core\Backend\Utility
         */
        $data = App::backend();

        if ($data->blog_id && $_POST !== [] && App::auth()->check(App::auth()->makePermissions(
            [
                App::auth()::PERMISSION_ADMIN,
            ]
        ), $data->blog_id)) {
            // Update a blog
            $cur = App::blog()->openBlogCursor();

            $cur->blog_id   = $_POST['blog_id'];
            $cur->blog_url  = preg_replace('/\?+$/', '?', (string) $_POST['blog_url']);
            $cur->blog_name = $_POST['blog_name'];
            $cur->blog_desc = $_POST['blog_desc'];

            if (App::auth()->isSuperAdmin() && in_array($_POST['blog_status'], $data->status_combo)) {
                $cur->blog_status = (int) $_POST['blog_status'];
            }

            $media_img_t_size = (int) $_POST['media_img_t_size'];
            if ($media_img_t_size < 0) {
                $media_img_t_size = 100;
            }

            $media_img_s_size = (int) $_POST['media_img_s_size'];
            if ($media_img_s_size < 0) {
                $media_img_s_size = 240;
            }

            $media_img_m_size = (int) $_POST['media_img_m_size'];
            if ($media_img_m_size < 0) {
                $media_img_m_size = 448;
            }

            $media_video_width = (int) $_POST['media_video_width'];
            if ($media_video_width < 0) {
                $media_video_width = 400;
            }

            $media_video_height = (int) $_POST['media_video_height'];
            if ($media_video_height < 0) {
                $media_video_height = 300;
            }

            $nb_post_for_home = abs((int) $_POST['nb_post_for_home']);
            if ($nb_post_for_home < 1) {
                $nb_post_for_home = 1;
            }

            $nb_post_per_page = abs((int) $_POST['nb_post_per_page']);
            if ($nb_post_per_page < 1) {
                $nb_post_per_page = 1;
            }

            $nb_post_per_feed = abs((int) $_POST['nb_post_per_feed']);
            if ($nb_post_per_feed < 1) {
                $nb_post_per_feed = 1;
            }

            $nb_comment_per_feed = abs((int) $_POST['nb_comment_per_feed']);
            if ($nb_comment_per_feed < 1) {
                $nb_comment_per_feed = 1;
            }

            try {
                if ($cur->blog_id != null && $cur->blog_id != $data->blog_id) {
                    $rs = App::blogs()->getBlog($cur->blog_id);
                    if ($rs->count() !== 0) {
                        throw new Exception(__('This blog ID is already used.'));
                    }
                }

                # --BEHAVIOR-- adminBeforeBlogUpdate -- Cursor, string
                App::behavior()->callBehavior('adminBeforeBlogUpdate', $cur, $data->blog_id);

                if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', (string) $_POST['lang'])) {
                    throw new Exception(__('Invalid language code'));
                }

                App::blogs()->updBlog($data->blog_id, $cur);

                if (App::auth()->isSuperAdmin() && $cur->blog_status === App::blog()::BLOG_REMOVED) {
                    // Remove this blog from user default blog
                    App::users()->removeUsersDefaultBlogs([$cur->blog_id]);
                }

                # --BEHAVIOR-- adminAfterBlogUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterBlogUpdate', $cur, $data->blog_id);

                if ($cur->blog_id != null && $cur->blog_id != $data->blog_id) {
                    if ($data->blog_id == App::blog()->id()) {
                        App::blog()->loadFromBlog($cur->blog_id);
                        $_SESSION['sess_blog_id'] = $cur->blog_id;
                        $data->blog_settings      = App::blog()->settings();
                    } else {
                        $data->blog_settings = App::blogSettings()->createFromBlog($cur->blog_id);
                    }

                    $data->blog_id = $cur->blog_id;
                }

                $data->blog_settings->system->put('editor', $_POST['editor']);
                $data->blog_settings->system->put('copyright_notice', $_POST['copyright_notice']);
                $data->blog_settings->system->put('post_url_format', $_POST['post_url_format']);
                $data->blog_settings->system->put('lang', $_POST['lang']);
                $data->blog_settings->system->put('blog_timezone', $_POST['blog_timezone']);
                $data->blog_settings->system->put('date_format', $_POST['date_format']);
                $data->blog_settings->system->put('time_format', $_POST['time_format']);
                $data->blog_settings->system->put('comments_ttl', abs((int) $_POST['comments_ttl']));
                $data->blog_settings->system->put('trackbacks_ttl', abs((int) $_POST['trackbacks_ttl']));
                $data->blog_settings->system->put('allow_comments', !empty($_POST['allow_comments']));
                $data->blog_settings->system->put('allow_trackbacks', !empty($_POST['allow_trackbacks']));
                $data->blog_settings->system->put('comments_pub', empty($_POST['comments_pub']));
                $data->blog_settings->system->put('trackbacks_pub', empty($_POST['trackbacks_pub']));
                $data->blog_settings->system->put('comments_nofollow', !empty($_POST['comments_nofollow']));
                $data->blog_settings->system->put('wiki_comments', !empty($_POST['wiki_comments']));
                $data->blog_settings->system->put('comment_preview_optional', !empty($_POST['comment_preview_optional']));
                $data->blog_settings->system->put('note_title_tag', $_POST['note_title_tag']);
                $data->blog_settings->system->put('nb_post_for_home', $nb_post_for_home);
                $data->blog_settings->system->put('nb_post_per_page', $nb_post_per_page);
                $data->blog_settings->system->put('no_public_css', !empty($_POST['no_public_css']));
                $data->blog_settings->system->put('use_smilies', !empty($_POST['use_smilies']));
                $data->blog_settings->system->put('no_search', !empty($_POST['no_search']));
                $data->blog_settings->system->put('inc_subcats', !empty($_POST['inc_subcats']));
                $data->blog_settings->system->put('media_img_t_size', $media_img_t_size);
                $data->blog_settings->system->put('media_img_s_size', $media_img_s_size);
                $data->blog_settings->system->put('media_img_m_size', $media_img_m_size);
                $data->blog_settings->system->put('media_thumbnail_prefix', $_POST['media_thumbnail_prefix']);
                $data->blog_settings->system->put('media_video_width', $media_video_width);
                $data->blog_settings->system->put('media_video_height', $media_video_height);
                $data->blog_settings->system->put('media_img_title_pattern', $_POST['media_img_title_pattern']);
                $data->blog_settings->system->put('media_img_use_dto_first', !empty($_POST['media_img_use_dto_first']));
                $data->blog_settings->system->put('media_img_no_date_alone', !empty($_POST['media_img_no_date_alone']));
                $data->blog_settings->system->put('media_img_default_size', $_POST['media_img_default_size']);
                $data->blog_settings->system->put('media_img_default_alignment', $_POST['media_img_default_alignment']);
                $data->blog_settings->system->put('media_img_default_link', !empty($_POST['media_img_default_link']));
                $data->blog_settings->system->put('media_img_default_legend', $_POST['media_img_default_legend']);
                $data->blog_settings->system->put('nb_post_per_feed', $nb_post_per_feed);
                $data->blog_settings->system->put('nb_comment_per_feed', $nb_comment_per_feed);
                $data->blog_settings->system->put('short_feed_items', !empty($_POST['short_feed_items']));
                if (isset($_POST['robots_policy'])) {
                    $data->blog_settings->system->put('robots_policy', $_POST['robots_policy']);
                }
                $data->blog_settings->system->put('legacy_needed', !empty($_POST['legacy_needed']));
                $data->blog_settings->system->put('jquery_needed', !empty($_POST['jquery_needed']));
                $data->blog_settings->system->put('jquery_version', $_POST['jquery_version']);
                $data->blog_settings->system->put('prevents_clickjacking', !empty($_POST['prevents_clickjacking']));
                $data->blog_settings->system->put('static_home', !empty($_POST['static_home']));
                $data->blog_settings->system->put('static_home_url', $_POST['static_home_url']);

                $data->blog_settings->system->put('sleepmode_timeout', $_POST['sleepmode_timeout']);

                # --BEHAVIOR-- adminBeforeBlogSettingsUpdate -- BlogSettingsInterface
                App::behavior()->callBehavior('adminBeforeBlogSettingsUpdate', $data->blog_settings);

                if (App::auth()->isSuperAdmin() && in_array($_POST['url_scan'], $data->url_scan_combo)) {
                    $data->blog_settings->system->put('url_scan', $_POST['url_scan']);
                }
                Notices::addSuccessNotice(__('Blog has been successfully updated.'));

                Http::redirect(sprintf($data->redir, $data->blog_id));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        /**
         * Alias for App::backend()
         *
         * @var \Dotclear\Core\Backend\Utility
         */
        $data = App::backend();

        // Display
        if ($data->standalone) {
            $breadcrumb = Page::breadcrumb(
                [
                    Html::escapeHTML($data->blog_name) => '',
                    __('Blog settings')                => '',
                ]
            );
        } else {
            $breadcrumb = Page::breadcrumb(
                [
                    __('System')                                                     => '',
                    __('Blogs')                                                      => App::backend()->url()->get('admin.blogs'),
                    __('Blog settings') . ' : ' . Html::escapeHTML($data->blog_name) => '',
                ]
            );
        }

        $desc_editor = App::auth()->getOption('editor');
        $rte_flag    = true;
        $rte_flags   = @App::auth()->prefs()->interface->rte_flags;
        if (is_array($rte_flags) && in_array('blog_descr', $rte_flags)) {
            $rte_flag = $rte_flags['blog_descr'];
        }

        Page::open(
            __('Blog settings'),
            Page::jsJson(
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
            Page::jsConfirmClose('blog-form') .
            # --BEHAVIOR-- adminPostEditor -- string, string, string, array<int,string>, string
            ($rte_flag ? App::behavior()->callBehavior('adminPostEditor', $desc_editor['xhtml'], 'blog_desc', ['#blog_desc'], 'xhtml') : '') .
            Page::jsLoad('js/_blog_pref.js') .

            # --BEHAVIOR-- adminBlogPreferencesHeaders --
            App::behavior()->callBehavior('adminBlogPreferencesHeaders') .

            Page::jsPageTabs(),
            $breadcrumb
        );

        if ($data->blog_id) {
            if (!empty($_GET['add'])) {
                Notices::success(__('Blog has been successfully created.'));
            }

            if (!empty($_GET['upd'])) {
                Notices::success(__('Blog has been successfully updated.'));
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
                    ->items($data->status_combo)
                    ->default((string) $data->blog_status)
                    ->label(new Label(__('Blog status:'), Label::IL_TF));
            } else {
                /*
                 * Only super admins can change the blog ID and URL, but we need to pass
                 * their values to the POST request via hidden html input values  so as
                 * to allow admins to update other settings.
                 * Otherwise App::blogs()->getBlogCursor() throws an exception.
                 */
                $details[] = (new Hidden('blog_id', Html::escapeHTML($data->blog_id)));
                $details[] = (new Hidden('blog_url', Html::escapeHTML($data->blog_url)));
            }

            $standard[] = (new Fieldset('blog-details'))
                ->legend(new Legend(__('Blog details')))
                ->fields([
                    (new Note())
                        ->class('form-note')
                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                    (new Para())
                        ->items([
                            (new Input('blog_name'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML($data->blog_name))
                                ->required(true)
                                ->placeholder(__('Blog name'))
                                ->title(__('Required field'))
                                ->lang($data->blog_settings->system->lang)
                                ->spellcheck(true)
                                ->label(new Label((new Text('span', '*'))->render() . __('Blog name:'), Label::IL_TF))
                                ->class('required'),
                        ]),
                    (new Para())
                        ->items([
                            (new Textarea('blog_desc', Html::escapeHTML($data->blog_desc)))
                                ->rows(5)
                                ->cols(60)
                                ->lang($data->blog_settings->system->lang)
                                ->spellcheck(true)
                                ->label(new Label(__('Blog description:'), Label::OL_TF)),
                        ]),
                    ... $details,
                ]);

            // Blog configuration
            $standard[] = (new Fieldset('blog-configuration'))
                ->legend(new Legend(__('Blog configuration')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Input('editor'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML($data->blog_settings->system->editor))
                                ->label(new Label(__('Blog editor name:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('lang'))
                                ->items($data->lang_combo)
                                ->default((string) $data->blog_settings->system->lang)
                                ->label(new Label(__('Default language:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('blog_timezone'))
                                ->items(Date::getZones(true, true))
                                ->default(Html::escapeHTML($data->blog_settings->system->blog_timezone))
                                ->label(new Label(__('Blog timezone:'), Label::IL_TF)),
                        ]),
                    (new Para())
                        ->items([
                            (new Input('copyright_notice'))
                                ->size(30)
                                ->maxlength(255)
                                ->default(Html::escapeHTML($data->blog_settings->system->copyright_notice))
                                ->lang($data->blog_settings->system->lang)
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
                                            (new Checkbox('allow_comments', (bool) $data->blog_settings->system->allow_comments))
                                                ->value(1)
                                                ->label(new Label(__('Accept comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comments_pub', ! (bool) $data->blog_settings->system->comments_pub))
                                                ->value(1)
                                                ->label(new Label(__('Moderate comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('comments_ttl', 0, 999, (int) $data->blog_settings->system->comments_ttl))
                                                ->label((new Label(__('Leave comments open for'), Label::IL_TF))
                                                    ->suffix(__('days')))
                                                ->extra('aria-describedby="comments_ttl_help"'),
                                        ]),
                                    (new Note())
                                        ->class('form-note')
                                        ->text(__('No limit: leave blank.'))
                                        ->id('comments_ttl_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('wiki_comments', (bool) $data->blog_settings->system->wiki_comments))
                                                ->value(1)
                                                ->label(new Label(__('Wiki syntax for comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comment_preview_optional', (bool) $data->blog_settings->system->comment_preview_optional))
                                                ->value(1)
                                                ->label(new Label(__('Preview of comment before submit is not mandatory'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Checkbox('allow_trackbacks', (bool) $data->blog_settings->system->allow_trackbacks))
                                                ->value(1)
                                                ->label(new Label(__('Accept trackbacks'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('trackbacks_pub', ! (bool) $data->blog_settings->system->trackbacks_pub))
                                                ->value(1)
                                                ->label(new Label(__('Moderate trackbacks'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('trackbacks_ttl', 0, 999, (int) $data->blog_settings->system->trackbacks_ttl))
                                                ->label((new Label(__('Leave trackbacks open for'), Label::IL_TF))
                                                    ->suffix(__('days')))
                                                ->extra('aria-describedby="trackbacks_ttl_help"'),
                                        ]),
                                    (new Note())
                                        ->class('form-note')
                                        ->text(__('No limit: leave blank.'))
                                        ->id('trackbacks_ttl_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('comments_nofollow', (bool) $data->blog_settings->system->comments_nofollow))
                                                ->value(1)
                                                ->label(new Label(__('Add "nofollow" relation on comments and trackbacks links'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Para())
                                ->class('col100')
                                ->items([
                                    (new Select('sleepmode_timeout'))
                                        ->items($data->sleepmode_timeout_combo)
                                        ->default((string) $data->blog_settings->system->sleepmode_timeout)
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
                                                ->value($data->blog_settings->system->date_format)
                                                ->label(new Label(__('Time format:'), Label::OL_TF))
                                                ->extra('aria-describedby="date_format_help"'),
                                            (new Select('date_format_select'))
                                                ->items($data->date_formats_combo)
                                                ->title(__('Pattern of date')),
                                        ]),
                                    (new Note())
                                        ->class(['chosen', 'form-note'])
                                        ->text(__('Sample:') . ' ' . Date::str(Html::escapeHTML($data->blog_settings->system->date_format)))
                                        ->id('date_format_help'),
                                    (new Para())
                                        ->class('form-buttons')
                                        ->items([
                                            (new Input('time_format'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->value($data->blog_settings->system->time_format)
                                                ->label(new Label(__('Time format:'), Label::OL_TF))
                                                ->extra('aria-describedby="time_format_help"'),
                                            (new Select('time_format_select'))
                                                ->items($data->time_formats_combo)
                                                ->title(__('Pattern of time')),
                                        ]),
                                    (new Note())
                                        ->class(['chosen', 'form-note'])
                                        ->text(__('Sample:') . ' ' . Date::str(Html::escapeHTML($data->blog_settings->system->time_format)))
                                        ->id('time_format_help'),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('no_public_css', (bool) $data->blog_settings->system->no_public_css))
                                                ->value(1)
                                                ->label(new Label(__('Don\'t load standard stylesheet (used for media alignement)'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('use_smilies', (bool) $data->blog_settings->system->use_smilies))
                                                ->value(1)
                                                ->label(new Label(__('Display smilies on entries and comments'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('no_search', (bool) $data->blog_settings->system->no_search))
                                                ->value(1)
                                                ->label(new Label(__('Disable internal search system'), Label::IL_FT)),
                                        ]),
                                ]),
                            (new Div())
                                ->class('col')
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_for_home', 1, 999, (int) $data->blog_settings->system->nb_post_for_home))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries on first page'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_per_page', 1, 999, (int) $data->blog_settings->system->nb_post_per_page))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries per page'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_post_per_feed', 1, 999, (int) $data->blog_settings->system->nb_post_per_feed))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('entries per feed'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Number('nb_comment_per_feed', 1, 999, (int) $data->blog_settings->system->nb_comment_per_feed))
                                                ->label(
                                                    (new Label(__('Display'), Label::IL_TF))
                                                    ->suffix(__('comments per feed'))
                                                ),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('short_feed_items', (bool) $data->blog_settings->system->short_feed_items))
                                                ->value(1)
                                                ->label(new Label(__('Truncate feeds'), Label::IL_FT)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Checkbox('inc_subcats', (bool) $data->blog_settings->system->inc_subcats))
                                                ->value(1)
                                                ->label(new Label(__('Include sub-categories in category page and category posts feed'), Label::IL_FT)),
                                        ]),
                                ]),
                        ]),
                    (new Single('hr'))
                        ->class('clear'),
                    (new Para())
                        ->items([
                            (new Checkbox('static_home', (bool) $data->blog_settings->system->static_home))
                                ->value(1)
                                ->label(new Label(__('Display an entry as static home page'), Label::IL_FT)),
                        ]),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            (new Input('static_home_url'))
                                ->size(30)
                                ->maxlength(255)
                                ->value(Html::escapeHTML($data->blog_settings->system->static_home_url))
                                ->label(new Label(__('Entry URL (its content will be used for the static home page):'), Label::IL_TF))
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
                                                    (new Number('media_img_t_size', -1, 999, (int) $data->blog_settings->system->media_img_t_size))
                                                        ->label(new Label(__('Thumbnail'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_img_s_size', -1, 999, (int) $data->blog_settings->system->media_img_s_size))
                                                        ->label(new Label(__('Small'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_img_m_size', -1, 999, (int) $data->blog_settings->system->media_img_m_size))
                                                        ->label(new Label(__('Medium'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Input('media_thumbnail_prefix'))
                                                        ->size(1)
                                                        ->maxlength(1)
                                                        ->value((string) $data->blog_settings->system->media_thumbnail_prefix)
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
                                                    (new Number('media_video_width', -1, 999, (int) $data->blog_settings->system->media_video_width))
                                                        ->label(new Label(__('Width'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Number('media_video_height', -1, 999, (int) $data->blog_settings->system->media_video_height))
                                                        ->label(new Label(__('Height'), Label::OL_TF)),
                                                ]),
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
                                                        ->items($data->img_title_combo)
                                                        ->default(Html::escapeHTML($data->media_img_title_pattern))
                                                        ->label(new Label(__('Inserted image legend:'), Label::IL_TF)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_use_dto_first', (bool) $data->blog_settings->system->media_img_use_dto_first))
                                                        ->value(1)
                                                        ->label(new Label(__('Use original media date if possible'), Label::IL_FT)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_no_date_alone', (bool) $data->blog_settings->system->media_img_no_date_alone))
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
                                                        ->items($data->img_default_size_combo)
                                                        ->default(Html::escapeHTML($data->blog_settings->system->media_img_default_size) !== '' ? Html::escapeHTML($data->blog_settings->system->media_img_default_size) : 'm')
                                                        ->label(new Label(__('Size of inserted image:'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->class('field')
                                                ->items([
                                                    (new Select('media_img_default_alignment'))
                                                        ->items($data->img_default_alignment_combo)
                                                        ->default(Html::escapeHTML($data->blog_settings->system->media_img_default_alignment))
                                                        ->label(new Label(__('Image alignment:'), Label::OL_TF)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Checkbox('media_img_default_link', (bool) $data->blog_settings->system->media_img_default_link))
                                                        ->value(1)
                                                        ->label(new Label(__('Insert a link to the original image'), Label::IL_FT)),
                                                ]),
                                            (new Para())
                                                ->items([
                                                    (new Select('media_img_default_legend'))
                                                        ->items($data->img_default_legend_combo)
                                                        ->default(Html::escapeHTML($data->blog_settings->system->media_img_default_legend))
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
                    $file    = $data->blog_url . App::url()->getURLFor('feed', 'atom');
                    $path    = '';
                    $status  = 404;
                    $content = '';

                    $client = HttpClient::initClient($file, $path);
                    if ($client !== false) {
                        $client->setTimeout(App::config()->queryTimeout());
                        $client->setUserAgent($_SERVER['HTTP_USER_AGENT']);
                        $client->get($path);
                        $status  = $client->getStatus();
                        $content = $client->getContent();
                    }
                    if ($status !== 200) {
                        // Might be 404 (URL not found), 670 (blog not online), ...
                        $message = (new Note())
                            ->class(['form-note', 'warn'])
                            ->text(sprintf(
                                __('The URL of blog or the URL scan method might not be well set (<code>%s</code> return a <strong>%s</strong> status).'),
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

                $advanced[] = (new Fieldset())
                    ->legend(new Legend(__('Blog details')))
                    ->fields([
                        (new Note())
                            ->class('form-note')
                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), (new Text('span', '*'))->class('required')->render())),
                        (new Para())
                            ->items([
                                (new Input('blog_id'))
                                    ->size(30)
                                    ->maxlength(32)
                                    ->value(Html::escapeHTML($data->blog_id))
                                    ->required(true)
                                    ->placeholder(__('Blog ID'))
                                    ->title(__('Required field'))
                                    ->label(new Label((new Text('span', '*'))->render() . __('Blog ID:'), Label::IL_TF))
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
                                    ->value(Html::escapeHTML($data->blog_url))
                                    ->required(true)
                                    ->placeholder(__('Blog URL'))
                                    ->title(__('Required field'))
                                    ->label(new Label((new Text('span', '*'))->render() . __('Blog URL:'), Label::IL_TF))
                                    ->class('required'),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('url_scan'))
                                    ->items($data->url_scan_combo)
                                    ->default((string) $data->blog_settings->system->url_scan)
                                    ->label(new Label(__('URL scan method:'), Label::IL_TF)),
                            ]),
                        $message,
                    ]);
            }

            // Blog configuration
            $advanced[] = (new Fieldset())
                ->legend(new Legend(__('Blog configuration')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Select('post_url_format'))
                                ->items($data->post_url_combo)
                                ->default(Html::escapeHTML($data->blog_settings->system->post_url_format))
                                ->label(new Label(__('New post URL format:'), Label::IL_TF))
                                ->extra('aria-describedby="post_url_format_help"'),
                        ]),
                    (new Para())
                        ->class(['chosen', 'form-note'])
                        ->id('post_url_format_help')
                        ->items([
                            (new Text(null, __('Sample:') . ' ' . App::blog()->getPostURL('', date('Y-m-d H:i:00', $data->now), __('Dotclear'), 42))),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('note_title_tag'))
                                ->items($data->note_title_tag_combo)
                                ->default((string) $data->blog_settings->system->note_title_tag)
                                ->label(new Label(__('HTML tag for the title of the notes on the blog:'), Label::IL_TF)),
                        ]),
                ]);

            // Search engine policies
            $policies = function () use ($data) {
                $index = 0;
                foreach ($data->robots_policy_options as $key => $value) {
                    yield (new Para())
                        ->items([
                            (new Radio(['robots_policy', 'robots_policy-' . $index], $data->blog_settings->system->robots_policy === $key))
                                ->value($key)
                                ->label(new Label($value, Label::IL_FT)),
                        ]);
                }
            };
            $advanced[] = (new Fieldset())
                ->legend(new Legend(__('Search engines robots policy')))
                ->fields($policies());

            // Legacy JS library
            $advanced[] = (new Fieldset())
                ->legend(new Legend(__('Legacy javascript library')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('legacy_needed', (bool) $data->blog_settings->system->legacy_needed))
                                ->value(1)
                                ->label(new Label(__('Load the Legacy JS library'), Label::IL_FT)),
                        ]),
                ]);

            // jQuery
            $advanced[] = (new Fieldset())
                ->legend(new Legend(__('jQuery javascript library')))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('jquery_needed', (bool) $data->blog_settings->system->jquery_needed))
                                ->value(1)
                                ->label(new Label(__('Load the jQuery library'), Label::IL_FT)),
                        ]),
                    (new Para())
                        ->items([
                            (new Select('jquery_version'))
                                ->items($data->jquery_versions_combo)
                                ->default((string) $data->blog_settings->system->jquery_version)
                                ->label(new Label(__('jQuery version to be loaded for this blog:'), Label::IL_TF)),
                        ]),
                ]);

            // Blog security
            $advanced[] = (new Fieldset())
                ->legend((new Legend(__('Blog security'))))
                ->fields([
                    (new Para())
                        ->items([
                            (new Checkbox('prevents_clickjacking', (bool) $data->blog_settings->system->prevents_clickjacking))
                                ->value(1)
                                ->label(new Label(__('Protect the blog from Clickjacking (see <a href="https://en.wikipedia.org/wiki/Clickjacking">Wikipedia</a>)'), Label::IL_FT)),
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
                        ['adminBlogPreferencesFormV2', $data->blog_settings]
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
                    $data->standalone ? (new None()) : (new Hidden('id', $data->blog_id)),
                    App::nonce()->formNonce(),
                ]);

            // Additional stuff
            if (App::auth()->isSuperAdmin() && $data->blog_id !== App::blog()->id()) {
                $additional = (new Form('del-blog'))
                    ->action(App::backend()->url()->get('admin.blog.del'))
                    ->method('post')
                    ->fields([
                        (new Para())
                            ->items([
                                (new Submit('submit-del-blog', __('Delete this blog')))
                                    ->class('delete'),
                                (new Hidden(['blog_id'], $data->blog_id)),
                                App::nonce()->formNonce(),
                            ]),
                    ]);
            } elseif ($data->blog_id === App::blog()->id()) {
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
                        ->action($data->action)
                        ->method('post')
                        ->fields($prefs),
                    $additional,
                ]);

            // Blog users
            $users = [];

            // Users on the blog (with permissions)
            $data->blog_users = App::blogs()->getBlogPermissions($data->blog_id, App::auth()->isSuperAdmin());
            $perm_types       = App::auth()->getPermissionsTypes();

            if ($data->blog_users === []) {
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
                $blog_users = $data->blog_users;
                if (App::lexical()->lexicalKeySort($blog_users, App::lexical()::ADMIN_LOCALE)) {
                    $data->blog_users = $blog_users;
                }

                $post_types      = App::postTypes()->dump();
                $current_blog_id = App::blog()->id();
                if ($data->blog_id !== App::blog()->id()) {
                    App::blog()->loadFromBlog($data->blog_id);
                }

                // Prepare user list
                foreach ($data->blog_users as $k => $v) {
                    if ((is_countable($v['p']) ? count($v['p']) : 0) > 0) {
                        // User email
                        $mail = $v['email'] ?
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
                                ->text(sprintf(__('%1$s: %2$s'), __($pt->get('label')), App::blog()->getPosts($prefs, true)->f(0)));
                        }

                        // User permissions
                        $perms = [];
                        if ($v['super']) {
                            $perms[] = (new Li())
                                ->class('user_super')
                                ->items([
                                    (new Text(null, __('Super administrator'))),
                                    (new Single('br')),
                                    (new Text('span', __('All rights on all blogs.')))
                                        ->class('form-note'),
                                ]);
                        } else {
                            foreach ($v['p'] as $p => $V) {
                                $perm = (new Li());
                                if ($p === 'admin') {
                                    $super = (new Set())
                                        ->items([
                                            (new Single('br')),
                                            (new Text('span', __('All rights on this blog.')))
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
                                            (new Hidden(['redir'], App::backend()->url()->get('admin.blog.pref', ['id' => $k], '&'))),
                                            (new Hidden(['action'], 'perms')),
                                            (new Hidden(['users[]'], $k)),
                                            (new Hidden(['blogs[]'], $data->blog_id)),
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
                                (new Text('h4', sprintf($user_url_p, Html::escapeHTML($k)) . ' (' . Html::escapeHTML(App::users()->getUserCN($k, $v['name'], $v['firstname'], $v['displayname'])) . ')')),
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

        Page::helpBlock('core_blog_pref');
        Page::close();
    }
}
