<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require_once __DIR__ . '/../inc/admin/prepend.php';

class adminBlogPref
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        /**
         * Alias for dcCore::app()->admin
         *
         * @var dcAdmin
         */
        $da = dcCore::app()->admin;

        /*
         * Is standalone blog preferences?
         *
         * - true: come directly from blog's paramaters menu entry (or link)
         * - false: come from in blogs management (may be on a different blog ID than current)
         *
         * @var        bool
         */
        $da->standalone = !(isset($da->edit_blog_mode) && $da->edit_blog_mode);
        if ($da->standalone) {
            dcPage::check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_ADMIN,
            ]));

            $da->blog_id       = dcCore::app()->blog->id;
            $da->blog_status   = dcCore::app()->blog->status;
            $da->blog_name     = dcCore::app()->blog->name;
            $da->blog_desc     = dcCore::app()->blog->desc;
            $da->blog_settings = dcCore::app()->blog->settings;
            $da->blog_url      = dcCore::app()->blog->url;

            $da->action = dcCore::app()->adminurl->get('admin.blog.pref');
            $da->redir  = dcCore::app()->adminurl->get('admin.blog.pref');
        } else {
            dcPage::checkSuper();

            $da->blog_id       = false;
            $da->blog_status   = dcBlog::BLOG_OFFLINE;
            $da->blog_name     = '';
            $da->blog_desc     = '';
            $da->blog_settings = null;
            $da->blog_url      = '';

            try {
                if (empty($_REQUEST['id'])) {
                    throw new Exception(__('No given blog id.'));
                }
                $rs = dcCore::app()->getBlog($_REQUEST['id']);

                if (!$rs) {
                    throw new Exception(__('No such blog.'));
                }

                $da->blog_id       = $rs->blog_id;
                $da->blog_status   = $rs->blog_status;
                $da->blog_name     = $rs->blog_name;
                $da->blog_desc     = $rs->blog_desc;
                $da->blog_settings = new dcSettings($da->blog_id);
                $da->blog_url      = $rs->blog_url;
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }

            $da->action = dcCore::app()->adminurl->get('admin.blog');
            $da->redir  = dcCore::app()->adminurl->get('admin.blog', ['id' => '%s'], '&', true);
        }

        // Language codes
        $da->lang_combo = dcAdminCombos::getAdminLangsCombo();

        // Status combo
        $da->status_combo = dcAdminCombos::getBlogStatusescombo();

        // Date format combo
        $da->now = time();

        $date_formats = $da->blog_settings->system->date_formats;
        $time_formats = $da->blog_settings->system->time_formats;

        $stack = ['' => ''];
        foreach ($date_formats as $format) {
            $stack[dt::str($format, $da->now)] = $format;
        }
        $da->date_formats_combo = $stack;

        $stack = ['' => ''];
        foreach ($time_formats as $format) {
            $stack[dt::str($format, $da->now)] = $format;
        }
        $da->time_formats_combo = $stack;

        // URL scan modes
        $da->url_scan_combo = [
            'PATH_INFO'    => 'path_info',
            'QUERY_STRING' => 'query_string',
        ];

        // Post URL combo
        $da->post_url_combo = [
            __('year/month/day/title') => '{y}/{m}/{d}/{t}',
            __('year/month/title')     => '{y}/{m}/{t}',
            __('year/title')           => '{y}/{t}',
            __('title')                => '{t}',
            __('post id/title')        => '{id}/{t}',
            __('post id')              => '{id}',
        ];
        if (!in_array($da->blog_settings->system->post_url_format, $da->post_url_combo)) {
            $da->post_url_combo[html::escapeHTML($da->blog_settings->system->post_url_format)] = html::escapeHTML($da->blog_settings->system->post_url_format);
        }

        // Note title tag combo
        $da->note_title_tag_combo = [
            __('H4') => 0,
            __('H3') => 1,
            __('P')  => 2,
        ];

        // Image title combo
        $da->img_title_combo = [
            __('(none)')                     => '',
            __('Title')                      => 'Title ;; separator(, )',
            __('Title, Date')                => 'Title ;; Date(%b %Y) ;; separator(, )',
            __('Title, Country, Date')       => 'Title ;; Country ;; Date(%b %Y) ;; separator(, )',
            __('Title, City, Country, Date') => 'Title ;; City ;; Country ;; Date(%b %Y) ;; separator(, )',
        ];
        if (!in_array($da->blog_settings->system->media_img_title_pattern, $da->img_title_combo)) {
            $da->img_title_combo[html::escapeHTML($da->blog_settings->system->media_img_title_pattern)] = html::escapeHTML($da->blog_settings->system->media_img_title_pattern);
        }

        // Image default size combo
        $stack = [];

        try {
            $media = new dcMedia();

            $stack[__('original')] = 'o';
            foreach ($media->thumb_sizes as $code => $size) {
                $stack[__($size[2])] = $code;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
        $da->img_default_size_combo = $stack;

        // Image default alignment combo
        $da->img_default_alignment_combo = [
            __('None')   => 'none',
            __('Left')   => 'left',
            __('Right')  => 'right',
            __('Center') => 'center',
        ];

        // Image default legend and title combo
        $da->img_default_legend_combo = [
            __('Legend and title') => 'legend',
            __('Title')            => 'title',
            __('None')             => 'none',
        ];

        // Robots policy options
        $da->robots_policy_options = [
            'INDEX,FOLLOW'               => __("I would like search engines and archivers to index and archive my blog's content."),
            'INDEX,FOLLOW,NOARCHIVE'     => __("I would like search engines and archivers to index but not archive my blog's content."),
            'NOINDEX,NOFOLLOW,NOARCHIVE' => __("I would like to prevent search engines and archivers from indexing or archiving my blog's content."),
        ];

        // jQuery available versions
        $jquery_root = __DIR__ . '/../inc/js/jquery';
        $stack       = [__('Default') . ' (' . DC_DEFAULT_JQUERY . ')' => ''];
        if (is_dir($jquery_root) && is_readable($jquery_root) && ($d = @dir($jquery_root)) !== false) {
            while (($entry = $d->read()) !== false) {
                if ($entry != '.' && $entry != '..' && substr($entry, 0, 1) != '.' && is_dir($jquery_root . '/' . $entry) && $entry != DC_DEFAULT_JQUERY) {
                    $stack[$entry] = $entry;
                }
            }
        }
        $da->jquery_versions_combo = $stack;

        // SLeep mode timeout in second
        $da->sleepmode_timeout_combo = [
            __('Do not put blog in sleep mode') => 0,
            __('Three month')  => 7884000,
            __('Six mouth')    => 15768000,
            __('One year')     => 31536000,
            __('Two year')     => 63072000,
        ];
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        /**
         * Alias for dcCore::app()->admin
         *
         * @var dcAdmin
         */
        $da = dcCore::app()->admin;

        if ($da->blog_id && !empty($_POST) && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions(
            [
                dcAuth::PERMISSION_ADMIN,
            ]
        ), $da->blog_id)) {
            // Update a blog
            $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcBlog::BLOG_TABLE_NAME);

            $cur->blog_id   = $_POST['blog_id'];
            $cur->blog_url  = preg_replace('/\?+$/', '?', $_POST['blog_url']);
            $cur->blog_name = $_POST['blog_name'];
            $cur->blog_desc = $_POST['blog_desc'];

            if (dcCore::app()->auth->isSuperAdmin() && in_array($_POST['blog_status'], $da->status_combo)) {
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
                if ($cur->blog_id != null && $cur->blog_id != $da->blog_id) {
                    $rs = dcCore::app()->getBlog($cur->blog_id);

                    if ($rs) {
                        throw new Exception(__('This blog ID is already used.'));
                    }
                }

                # --BEHAVIOR-- adminBeforeBlogUpdate
                dcCore::app()->callBehavior('adminBeforeBlogUpdate', $cur, $da->blog_id);

                if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $_POST['lang'])) {
                    throw new Exception(__('Invalid language code'));
                }

                dcCore::app()->updBlog($da->blog_id, $cur);

                # --BEHAVIOR-- adminAfterBlogUpdate
                dcCore::app()->callBehavior('adminAfterBlogUpdate', $cur, $da->blog_id);

                if ($cur->blog_id != null && $cur->blog_id != $da->blog_id) {
                    if ($da->blog_id == dcCore::app()->blog->id) {
                        dcCore::app()->setBlog($cur->blog_id);
                        $_SESSION['sess_blog_id'] = $cur->blog_id;
                        $da->blog_settings        = dcCore::app()->blog->settings;
                    } else {
                        $da->blog_settings = new dcSettings($cur->blog_id);
                    }

                    $da->blog_id = $cur->blog_id;
                }

                $da->blog_settings->addNameSpace('system');

                $da->blog_settings->system->put('editor', $_POST['editor']);
                $da->blog_settings->system->put('copyright_notice', $_POST['copyright_notice']);
                $da->blog_settings->system->put('post_url_format', $_POST['post_url_format']);
                $da->blog_settings->system->put('lang', $_POST['lang']);
                $da->blog_settings->system->put('blog_timezone', $_POST['blog_timezone']);
                $da->blog_settings->system->put('date_format', $_POST['date_format']);
                $da->blog_settings->system->put('time_format', $_POST['time_format']);
                $da->blog_settings->system->put('comments_ttl', abs((int) $_POST['comments_ttl']));
                $da->blog_settings->system->put('trackbacks_ttl', abs((int) $_POST['trackbacks_ttl']));
                $da->blog_settings->system->put('allow_comments', !empty($_POST['allow_comments']));
                $da->blog_settings->system->put('allow_trackbacks', !empty($_POST['allow_trackbacks']));
                $da->blog_settings->system->put('comments_pub', empty($_POST['comments_pub']));
                $da->blog_settings->system->put('trackbacks_pub', empty($_POST['trackbacks_pub']));
                $da->blog_settings->system->put('comments_nofollow', !empty($_POST['comments_nofollow']));
                $da->blog_settings->system->put('wiki_comments', !empty($_POST['wiki_comments']));
                $da->blog_settings->system->put('comment_preview_optional', !empty($_POST['comment_preview_optional']));
                $da->blog_settings->system->put('note_title_tag', $_POST['note_title_tag']);
                $da->blog_settings->system->put('nb_post_for_home', $nb_post_for_home);
                $da->blog_settings->system->put('nb_post_per_page', $nb_post_per_page);
                $da->blog_settings->system->put('use_smilies', !empty($_POST['use_smilies']));
                $da->blog_settings->system->put('no_search', !empty($_POST['no_search']));
                $da->blog_settings->system->put('inc_subcats', !empty($_POST['inc_subcats']));
                $da->blog_settings->system->put('media_img_t_size', $media_img_t_size);
                $da->blog_settings->system->put('media_img_s_size', $media_img_s_size);
                $da->blog_settings->system->put('media_img_m_size', $media_img_m_size);
                $da->blog_settings->system->put('media_video_width', $media_video_width);
                $da->blog_settings->system->put('media_video_height', $media_video_height);
                $da->blog_settings->system->put('media_img_title_pattern', $_POST['media_img_title_pattern']);
                $da->blog_settings->system->put('media_img_use_dto_first', !empty($_POST['media_img_use_dto_first']));
                $da->blog_settings->system->put('media_img_no_date_alone', !empty($_POST['media_img_no_date_alone']));
                $da->blog_settings->system->put('media_img_default_size', $_POST['media_img_default_size']);
                $da->blog_settings->system->put('media_img_default_alignment', $_POST['media_img_default_alignment']);
                $da->blog_settings->system->put('media_img_default_link', !empty($_POST['media_img_default_link']));
                $da->blog_settings->system->put('media_img_default_legend', $_POST['media_img_default_legend']);
                $da->blog_settings->system->put('nb_post_per_feed', $nb_post_per_feed);
                $da->blog_settings->system->put('nb_comment_per_feed', $nb_comment_per_feed);
                $da->blog_settings->system->put('short_feed_items', !empty($_POST['short_feed_items']));
                if (isset($_POST['robots_policy'])) {
                    $da->blog_settings->system->put('robots_policy', $_POST['robots_policy']);
                }
                $da->blog_settings->system->put('jquery_needed', !empty($_POST['jquery_needed']));
                $da->blog_settings->system->put('jquery_version', $_POST['jquery_version']);
                $da->blog_settings->system->put('prevents_clickjacking', !empty($_POST['prevents_clickjacking']));
                $da->blog_settings->system->put('static_home', !empty($_POST['static_home']));
                $da->blog_settings->system->put('static_home_url', $_POST['static_home_url']);

                $da->blog_settings->system->put('sleepmode_timeout', $_POST['sleepmode_timeout']);

                # --BEHAVIOR-- adminBeforeBlogSettingsUpdate
                dcCore::app()->callBehavior('adminBeforeBlogSettingsUpdate', $da->blog_settings);

                if (dcCore::app()->auth->isSuperAdmin() && in_array($_POST['url_scan'], $da->url_scan_combo)) {
                    $da->blog_settings->system->put('url_scan', $_POST['url_scan']);
                }
                dcPage::addSuccessNotice(__('Blog has been successfully updated.'));

                http::redirect(sprintf($da->redir, $da->blog_id));
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        /**
         * Alias for dcCore::app()->admin
         *
         * @var dcAdmin
         */
        $da = dcCore::app()->admin;

        // Display
        if ($da->standalone) {
            $breadcrumb = dcPage::breadcrumb(
                [
                    html::escapeHTML($da->blog_name) => '',
                    __('Blog settings')              => '',
                ]
            );
        } else {
            $breadcrumb = dcPage::breadcrumb(
                [
                    __('System')                                                   => '',
                    __('Blogs')                                                    => dcCore::app()->adminurl->get('admin.blogs'),
                    __('Blog settings') . ' : ' . html::escapeHTML($da->blog_name) => '',
                ]
            );
        }

        $desc_editor = dcCore::app()->auth->getOption('editor');
        $rte_flag    = true;
        $rte_flags   = @dcCore::app()->auth->user_prefs->interface->rte_flags;
        if (is_array($rte_flags) && in_array('blog_descr', $rte_flags)) {
            $rte_flag = $rte_flags['blog_descr'];
        }

        dcPage::open(
            __('Blog settings'),
            dcPage::jsJson('blog_pref', [
                'warning_path_info'    => __('Warning: except for special configurations, it is generally advised to have a trailing "/" in your blog URL in PATH_INFO mode.'),
                'warning_query_string' => __('Warning: except for special configurations, it is generally advised to have a trailing "?" in your blog URL in QUERY_STRING mode.'),
            ]) .
            dcPage::jsConfirmClose('blog-form') .
            ($rte_flag ? dcCore::app()->callBehavior('adminPostEditor', $desc_editor['xhtml'], 'blog_desc', ['#blog_desc'], 'xhtml') : '') .
            dcPage::jsLoad('js/_blog_pref.js') .

            # --BEHAVIOR-- adminBlogPreferencesHeaders
            dcCore::app()->callBehavior('adminBlogPreferencesHeaders') .

            dcPage::jsPageTabs(),
            $breadcrumb
        );

        if ($da->blog_id) {
            if (!empty($_GET['add'])) {
                dcPage::success(__('Blog has been successfully created.'));
            }

            if (!empty($_GET['upd'])) {
                dcPage::success(__('Blog has been successfully updated.'));
            }

            echo
            '<div class="multi-part" id="params" title="' . __('Parameters') . '">' .
            '<div id="standard-pref"><h3>' . __('Blog parameters') . '</h3>' .
            '<form action="' . $da->action . '" method="post" id="blog-form">' .
            '<div class="fieldset"><h4>' . __('Blog details') . '</h4>' .
            dcCore::app()->formNonce() .
            '<p><label for="blog_name" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog name:') . '</label>' .
            form::field(
                'blog_name',
                30,
                255,
                [
                    'default'    => html::escapeHTML($da->blog_name),
                    'extra_html' => 'required placeholder="' . __('Blog name') . '" lang="' . $da->blog_settings->system->lang . '" spellcheck="true"',
                ]
            ) . '</p>' .
            '<p class="area"><label for="blog_desc">' . __('Blog description:') . '</label>' .
            form::textarea(
                'blog_desc',
                60,
                5,
                [
                    'default'    => html::escapeHTML($da->blog_desc),
                    'extra_html' => 'lang="' . $da->blog_settings->system->lang . '" spellcheck="true"',
                ]
            ) . '</p>';

            if (dcCore::app()->auth->isSuperAdmin()) {
                echo
                '<p><label for="blog_status">' . __('Blog status:') . '</label>' .
                form::combo('blog_status', $da->status_combo, $da->blog_status) . '</p>';
            } else {
                /*
                 * Only super admins can change the blog ID and URL, but we need to pass
                 * their values to the POST request via hidden html input values  so as
                 * to allow admins to update other settings.
                 * Otherwise dcCore::getBlogCursor() throws an exception.
                 */
                echo
                form::hidden('blog_id', html::escapeHTML($da->blog_id)) .
                form::hidden('blog_url', html::escapeHTML($da->blog_url));
            }

            echo '</div>' .

            '<div class="fieldset"><h4>' . __('Blog configuration') . '</h4>' .
            '<p><label for="editor">' . __('Blog editor name:') . '</label>' .
            form::field('editor', 30, 255, html::escapeHTML($da->blog_settings->system->editor)) .
            '</p>' .
            '<p><label for="lang">' . __('Default language:') . '</label>' .
            form::combo('lang', $da->lang_combo, $da->blog_settings->system->lang, 'l10n') .
            '</p>' .
            '<p><label for="blog_timezone">' . __('Blog timezone:') . '</label>' .
            form::combo('blog_timezone', dt::getZones(true, true), html::escapeHTML($da->blog_settings->system->blog_timezone)) .
            '</p>' .
            '<p><label for="copyright_notice">' . __('Copyright notice:') . '</label>' .
            form::field(
                'copyright_notice',
                30,
                255,
                [
                    'default'    => html::escapeHTML($da->blog_settings->system->copyright_notice),
                    'extra_html' => 'lang="' . $da->blog_settings->system->lang . '" spellcheck="true"',
                ]
            ) .
            '</p>' .
            '</div>' .

            '<div class="fieldset"><h4>' . __('Comments and trackbacks') . '</h4>' .
            '<div class="two-cols">' .

            '<div class="col">' .
            '<p><label for="allow_comments" class="classic">' .
            form::checkbox('allow_comments', '1', $da->blog_settings->system->allow_comments) .
            __('Accept comments') . '</label></p>' .
            '<p><label for="comments_pub" class="classic">' .
            form::checkbox('comments_pub', '1', !$da->blog_settings->system->comments_pub) .
            __('Moderate comments') . '</label></p>' .
            '<p><label for="comments_ttl" class="classic">' . sprintf(
                __('Leave comments open for %s days') . '.',
                form::number(
                    'comments_ttl',
                    [
                        'min'        => 0,
                        'max'        => 999,
                        'default'    => $da->blog_settings->system->comments_ttl,
                        'extra_html' => 'aria-describedby="comments_ttl_help"', ]
                )
            ) .
            '</label></p>' .
            '<p class="form-note" id="comments_ttl_help">' . __('No limit: leave blank.') . '</p>' .
            '<p><label for="wiki_comments" class="classic">' .
            form::checkbox('wiki_comments', '1', $da->blog_settings->system->wiki_comments) .
            __('Wiki syntax for comments') . '</label></p>' .
            '<p><label for="comment_preview_optional" class="classic">' .
            form::checkbox('comment_preview_optional', '1', $da->blog_settings->system->comment_preview_optional) .
            __('Preview of comment before submit is not mandatory') . '</label></p>' .
            '</div>' .

            '<div class="col">' .
            '<p><label for="allow_trackbacks" class="classic">' .
            form::checkbox('allow_trackbacks', '1', $da->blog_settings->system->allow_trackbacks) .
            __('Accept trackbacks') . '</label></p>' .
            '<p><label for="trackbacks_pub" class="classic">' .
            form::checkbox('trackbacks_pub', '1', !$da->blog_settings->system->trackbacks_pub) .
            __('Moderate trackbacks') . '</label></p>' .
            '<p><label for="trackbacks_ttl" class="classic">' . sprintf(
                __('Leave trackbacks open for %s days') . '.',
                form::number(
                    'trackbacks_ttl',
                    [
                        'min'        => 0,
                        'max'        => 999,
                        'default'    => $da->blog_settings->system->trackbacks_ttl,
                        'extra_html' => 'aria-describedby="trackbacks_ttl_help"', ]
                )
            ) .
            '</label></p>' .
            '<p class="form-note" id="trackbacks_ttl_help">' . __('No limit: leave blank.') . '</p>' .
            '<p><label for="comments_nofollow" class="classic">' .
            form::checkbox('comments_nofollow', '1', $da->blog_settings->system->comments_nofollow) .
            __('Add "nofollow" relation on comments and trackbacks links') . '</label></p>' .
            '</div>' . '<br class="clear" />' . //Opera sucks

            '</div>' . '<br class="clear" />' . //Opera sucks
            '</div>' .

            '<div class="fieldset"><h4>' . __('Blog presentation') . '</h4>' .
            '<div class="two-cols">' .
            '<div class="col">' .
            '<p><label for="date_format">' . __('Date format:') . '</label> ' .
            form::field('date_format', 30, 255, html::escapeHTML($da->blog_settings->system->date_format), '', '', false, 'aria-describedby="date_format_help"') .
            form::combo('date_format_select', $da->date_formats_combo, ['extra_html' => 'title="' . __('Pattern of date') . '"']) .
            '</p>' .
            '<p class="chosen form-note" id="date_format_help">' . __('Sample:') . ' ' . dt::str(html::escapeHTML($da->blog_settings->system->date_format)) . '</p>' .

            '<p><label for="time_format">' . __('Time format:') . '</label>' .
            form::field('time_format', 30, 255, html::escapeHTML($da->blog_settings->system->time_format), '', '', false, 'aria-describedby="time_format_help"') .
            form::combo('time_format_select', $da->time_formats_combo, ['extra_html' => 'title="' . __('Pattern of time') . '"']) .
            '</p>' .
            '<p class="chosen form-note" id="time_format_help">' . __('Sample:') . ' ' . dt::str(html::escapeHTML($da->blog_settings->system->time_format)) . '</p>' .

            '<p><label for="use_smilies" class="classic">' .
            form::checkbox('use_smilies', '1', $da->blog_settings->system->use_smilies) .
            __('Display smilies on entries and comments') . '</label></p>' .

            '<p><label for="no_search" class="classic">' .
            form::checkbox('no_search', '1', $da->blog_settings->system->no_search) .
            __('Disable internal search system') . '</label></p>' .

            '</div>' .

            '<div class="col">' .

            '<p><label for="nb_post_for_home" class="classic">' . sprintf(
                __('Display %s entries on first page'),
                form::number(
                    'nb_post_for_home',
                    [
                        'min'     => 1,
                        'max'     => 999,
                        'default' => $da->blog_settings->system->nb_post_for_home, ]
                )
            ) .
            '</label></p>' .

            '<p><label for="nb_post_per_page" class="classic">' . sprintf(
                __('Display %s entries per page'),
                form::number(
                    'nb_post_per_page',
                    [
                        'min'     => 1,
                        'max'     => 999,
                        'default' => $da->blog_settings->system->nb_post_per_page, ]
                )
            ) .
            '</label></p>' .

            '<p><label for="nb_post_per_feed" class="classic">' . sprintf(
                __('Display %s entries per feed'),
                form::number(
                    'nb_post_per_feed',
                    [
                        'min'     => 1,
                        'max'     => 999,
                        'default' => $da->blog_settings->system->nb_post_per_feed, ]
                )
            ) .
            '</label></p>' .

            '<p><label for="nb_comment_per_feed" class="classic">' . sprintf(
                __('Display %s comments per feed'),
                form::number(
                    'nb_comment_per_feed',
                    [
                        'min'     => 1,
                        'max'     => 999,
                        'default' => $da->blog_settings->system->nb_comment_per_feed, ]
                )
            ) .
            '</label></p>' .

            '<p><label for="short_feed_items" class="classic">' .
            form::checkbox('short_feed_items', '1', $da->blog_settings->system->short_feed_items) .
            __('Truncate feeds') . '</label></p>' .

            '<p><label for="inc_subcats" class="classic">' .
            form::checkbox('inc_subcats', '1', $da->blog_settings->system->inc_subcats) .
            __('Include sub-categories in category page and category posts feed') . '</label></p>' .
            '</div>' .
            '</div>' .
            '<br class="clear" />' . //Opera sucks

            '<hr />' .

            '<p><label for="static_home" class="classic">' .
            form::checkbox('static_home', '1', $da->blog_settings->system->static_home) .
            __('Display an entry as static home page') . '</label></p>' .

            '<p><label for="static_home_url" class="classic">' . __('Entry URL (its content will be used for the static home page):') . '</label> ' .
            form::field('static_home_url', 30, 255, html::escapeHTML($da->blog_settings->system->static_home_url), '', '', false, 'aria-describedby="static_home_url_help"') .
            ' <button type="button" id="static_home_url_selector">' . __('Choose an entry') . '</button>' .
            '</p>' .
            '<p class="form-note" id="static_home_url_help">' . __('Leave empty to use the default presentation.') . '</p> ' .

            '</div>' .

            '<div class="fieldset"><h4 id="medias-settings">' . __('Media and images') . '</h4>' .
            '<p class="form-note warning">' .
            __('Please note that if you change current settings bellow, they will now apply to all new images in the media manager.') .
            ' ' . __('Be carefull if you share it with other blogs in your installation.') . '<br />' .
            __('Set -1 to use the default size, set 0 to ignore this thumbnail size (images only).') . '</p>' .

            '<div class="two-cols">' .
            '<div class="col">' .
            '<h5>' . __('Generated image sizes (max dimension in pixels)') . '</h5>' .
            '<p class="field"><label for="media_img_t_size">' . __('Thumbnail') . '</label> ' .
            form::number('media_img_t_size', [
                'min'     => -1,
                'max'     => 999,
                'default' => $da->blog_settings->system->media_img_t_size,
            ]) .
            '</p>' .

            '<p class="field"><label for="media_img_s_size">' . __('Small') . '</label> ' .
            form::number('media_img_s_size', [
                'min'     => -1,
                'max'     => 999,
                'default' => $da->blog_settings->system->media_img_s_size,
            ]) .
            '</p>' .

            '<p class="field"><label for="media_img_m_size">' . __('Medium') . '</label> ' .
            form::number('media_img_m_size', [
                'min'     => -1,
                'max'     => 999,
                'default' => $da->blog_settings->system->media_img_m_size,
            ]) .
            '</p>' .

            '<h5>' . __('Default size of the inserted video (in pixels)') . '</h5>' .
            '<p class="field"><label for="media_video_width">' . __('Width') . '</label> ' .
            form::number('media_video_width', [
                'min'     => -1,
                'max'     => 999,
                'default' => $da->blog_settings->system->media_video_width,
            ]) .
            '</p>' .

            '<p class="field"><label for="media_video_height">' . __('Height') . '</label> ' .
            form::number('media_video_height', [
                'min'     => -1,
                'max'     => 999,
                'default' => $da->blog_settings->system->media_video_height,
            ]) .
            '</p>' .
            '</div>' .

            '<div class="col">' .
            '<h5>' . __('Default image insertion attributes') . '</h5>' .
            '<p class="vertical-separator"><label for="media_img_title_pattern">' . __('Inserted image title') . '</label>' .
            form::combo('media_img_title_pattern', $da->img_title_combo, html::escapeHTML($da->blog_settings->system->media_img_title_pattern)) . '</p>' .
            '<p><label for="media_img_use_dto_first" class="classic">' .
            form::checkbox('media_img_use_dto_first', '1', $da->blog_settings->system->media_img_use_dto_first) .
            __('Use original media date if possible') . '</label></p>' .
            '<p><label for="media_img_no_date_alone" class="classic">' .
            form::checkbox('media_img_no_date_alone', '1', $da->blog_settings->system->media_img_no_date_alone, '', '', false, 'aria-describedby="media_img_no_date_alone_help"') .
            __('Do not display date if alone in title') . '</label></p>' .
            '<p class="form-note info" id="media_img_no_date_alone_help">' . __('It is retrieved from the picture\'s metadata.') . '</p>' .

            '<p class="field vertical-separator"><label for="media_img_default_size">' . __('Size of inserted image:') . '</label>' .
            form::combo(
                'media_img_default_size',
                $da->img_default_size_combo,
                (html::escapeHTML($da->blog_settings->system->media_img_default_size) != '' ? html::escapeHTML($da->blog_settings->system->media_img_default_size) : 'm')
            ) .
            '</p>' .
            '<p class="field"><label for="media_img_default_alignment">' . __('Image alignment:') . '</label>' .
            form::combo('media_img_default_alignment', $da->img_default_alignment_combo, html::escapeHTML($da->blog_settings->system->media_img_default_alignment)) .
            '</p>' .
            '<p><label for="media_img_default_link">' .
            form::checkbox('media_img_default_link', '1', $da->blog_settings->system->media_img_default_link) .
            __('Insert a link to the original image') . '</label></p>' .
            '<p class="field"><label for="media_img_default_legend">' . __('Image legend and title:') . '</label>' .
            form::combo('media_img_default_legend', $da->img_default_legend_combo, html::escapeHTML($da->blog_settings->system->media_img_default_legend)) .
            '</p>' .
            '</div>' .
            '</div>' . '<br class="clear" />' . //Opera sucks

            '</div>' .
            '</div>' .

            '<div id="advanced-pref"><h3>' . __('Advanced parameters') . '</h3>';

            if (dcCore::app()->auth->isSuperAdmin()) {
                echo '<div class="fieldset"><h4>' . __('Blog details') . '</h4>' .

                '<p><label for="blog_id" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog ID:') . '</label>' .
                form::field('blog_id', 30, 32, html::escapeHTML($da->blog_id), '', '', false, 'required placeholder="' . __('Blog ID') . '" aria-describedby="blog_id_help blog_id_warn"') . '</p>' .
                '<p class="form-note" id="blog_id_help">' . __('At least 2 characters using letters, numbers or symbols.') . '</p> ' .
                '<p class="form-note warn" id="blog_id_warn">' . __('Please note that changing your blog ID may require changes in your public index.php file.') . '</p>' .

                '<p><label for="blog_url" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog URL:') . '</label>' .
                form::url('blog_url', [
                    'size'       => 50,
                    'max'        => 255,
                    'default'    => html::escapeHTML($da->blog_url),
                    'extra_html' => 'required placeholder="' . __('Blog URL') . '"',
                ]) .
                '</p>' .

                '<p><label for="url_scan">' . __('URL scan method:') . '</label>' .
                form::combo('url_scan', $da->url_scan_combo, $da->blog_settings->system->url_scan) . '</p>';

                try {
                    # Test URL of blog by testing it's ATOM feed
                    $file    = $da->blog_url . dcCore::app()->url->getURLFor('feed', 'atom');
                    $path    = '';
                    $status  = '404';
                    $content = '';

                    $client = netHttp::initClient($file, $path);
                    if ($client !== false) {
                        $client->setTimeout(DC_QUERY_TIMEOUT);
                        $client->setUserAgent($_SERVER['HTTP_USER_AGENT']);
                        $client->get($path);
                        $status  = $client->getStatus();
                        $content = $client->getContent();
                    }
                    if ($status != '200') {
                        // Might be 404 (URL not found), 670 (blog not online), ...
                        echo
                        '<p class="form-note warn">' .
                        sprintf(
                            __('The URL of blog or the URL scan method might not be well set (<code>%s</code> return a <strong>%s</strong> status).'),
                            html::escapeHTML($file),
                            $status
                        ) .
                        '</p>';
                    } else {
                        if (substr($content, 0, 6) != '<?xml ') {
                            // Not well formed XML feed
                            echo
                            '<p class="form-note warn">' .
                            sprintf(
                                __('The URL of blog or the URL scan method might not be well set (<code>%s</code> does not return an ATOM feed).'),
                                html::escapeHTML($file)
                            ) .
                            '</p>';
                        }
                    }
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
                echo '</div>';
            }

            echo
            '<div class="fieldset"><h4>' . __('Blog configuration') . '</h4>' .

            '<p><label for="post_url_format">' . __('New post URL format:') . '</label>' .
            form::combo('post_url_format', $da->post_url_combo, html::escapeHTML($da->blog_settings->system->post_url_format), '', '', false, 'aria-describedby="post_url_format_help"') .
            '</p>' .
            '<p class="chosen form-note" id="post_url_format_help">' . __('Sample:') . ' ' . dcCore::app()->blog->getPostURL('', date('Y-m-d H:i:00', $da->now), __('Dotclear'), 42) . '</p>' .
            '</p>' .

            '<p><label for="note_title_tag">' . __('HTML tag for the title of the notes on the blog:') . '</label>' .
            form::combo('note_title_tag', $da->note_title_tag_combo, $da->blog_settings->system->note_title_tag) .
            '</p>' .

            '</div>' .

            // Search engines policies
            '<div class="fieldset"><h4>' . __('Search engines robots policy') . '</h4>';

            $i = 0;
            foreach ($da->robots_policy_options as $k => $v) {
                echo
                '<p><label for="robots_policy-' . $i . '" class="classic">' .
                form::radio(['robots_policy', 'robots_policy-' . $i], $k, $da->blog_settings->system->robots_policy == $k) . ' ' . $v . '</label></p>';

                $i++;
            }

            echo '</div>' .

            '<div class="fieldset"><h4>' . __('jQuery javascript library') . '</h4>' .
            '<p><label for="jquery_needed" class="classic">' .
            form::checkbox('jquery_needed', '1', $da->blog_settings->system->jquery_needed) .
            __('Load the jQuery library') . '</label></p>' .

            '<p><label for="jquery_version" class="classic">' . __('jQuery version to be loaded for this blog:') . '</label>' . ' ' .
            form::combo('jquery_version', $da->jquery_versions_combo, $da->blog_settings->system->jquery_version) .
            '</p>' . '<br class="clear" />' . //Opera sucks

            '</div>' .

            '<div class="fieldset"><h4>' . __('Blog security') . '</h4>' .

            '<p><label for="prevents_clickjacking" class="classic">' .
            form::checkbox('prevents_clickjacking', '1', $da->blog_settings->system->prevents_clickjacking) .
            __('Protect the blog from Clickjacking (see <a href="https://en.wikipedia.org/wiki/Clickjacking">Wikipedia</a>)') . '</label></p>' .

            '<p><label for="sleepmode_timeout" class="classic">' . __('Close all blog comments and trackbacks after a period without any new post:') . '</label>' . ' ' .
            form::combo('sleepmode_timeout', $da->sleepmode_timeout_combo, $da->blog_settings->system->sleepmode_timeout) .
            '</p>' . '<br class="clear" />' . //Opera sucks

            '</div>' .

            '</div>' . // End advanced

            '<div id="plugins-pref"><h3>' . __('Plugins parameters') . '</h3>';

            # --BEHAVIOR-- adminBlogPreferencesForm
            dcCore::app()->callBehavior('adminBlogPreferencesFormV2', $da->blog_settings);

            echo '</div>' . // End 3rd party, aka plugins

            '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
            ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
            (!$da->standalone ? form::hidden('id', $da->blog_id) : '') .
            '</p>' .
            '</form>';

            if (dcCore::app()->auth->isSuperAdmin() && $da->blog_id != dcCore::app()->blog->id) {
                echo
                '<form action="' . dcCore::app()->adminurl->get('admin.blog.del') . '" method="post">' .
                '<p><input type="submit" class="delete" value="' . __('Delete this blog') . '" />' .
                form::hidden(['blog_id'], $da->blog_id) .
                dcCore::app()->formNonce() . '</p>' .
                '</form>';
            } else {
                if ($da->blog_id == dcCore::app()->blog->id) {
                    echo '<p class="message">' . __('The current blog cannot be deleted.') . '</p>';
                } else {
                    echo '<p class="message">' . __('Only superadmin can delete a blog.') . '</p>';
                }
            }

            echo '</div>';

            #
            # Users on the blog (with permissions)

            $da->blog_users = dcCore::app()->getBlogPermissions($da->blog_id, dcCore::app()->auth->isSuperAdmin());
            $perm_types     = dcCore::app()->auth->getPermissionsTypes();

            echo
            '<div class="multi-part" id="users" title="' . __('Users') . '">' .
            '<h3 class="out-of-screen-if-js">' . __('Users on this blog') . '</h3>';

            if (empty($da->blog_users)) {
                echo '<p>' . __('No users') . '</p>';
            } else {
                if (dcCore::app()->auth->isSuperAdmin()) {
                    $user_url_p = '<a href="' . dcCore::app()->adminurl->get('admin.user', ['id' => '%1$s'], '&amp;', true) . '">%1$s</a>';
                } else {
                    $user_url_p = '%1$s';
                }

                // Sort users list on user_id key
                $blog_users = $da->blog_users;
                if (dcUtils::lexicalKeySort($blog_users)) {
                    $da->blog_users = $blog_users;
                }

                $post_type       = dcCore::app()->getPostTypes();
                $current_blog_id = dcCore::app()->blog->id;
                if ($da->blog_id != dcCore::app()->blog->id) {
                    dcCore::app()->setBlog($da->blog_id);
                }

                echo '<div>';
                foreach ($da->blog_users as $k => $v) {
                    if (count($v['p']) > 0) {
                        echo
                        '<div class="user-perm' . ($v['super'] ? ' user_super' : '') . '">' .
                        '<h4>' . sprintf($user_url_p, html::escapeHTML($k)) .
                        ' (' . html::escapeHTML(dcUtils::getUserCN(
                            $k,
                            $v['name'],
                            $v['firstname'],
                            $v['displayname']
                        )) . ')</h4>';

                        if (dcCore::app()->auth->isSuperAdmin()) {
                            echo
                            '<p>' . __('Email:') . ' ' .
                            ($v['email'] != '' ? '<a href="mailto:' . $v['email'] . '">' . $v['email'] . '</a>' : __('(none)')) .
                            '</p>';
                        }

                        echo
                        '<h5>' . __('Publications on this blog:') . '</h5>' .
                        '<ul>';
                        foreach ($post_type as $type => $pt_info) {
                            $params = [
                                'post_type' => $type,
                                'user_id'   => $k,
                            ];
                            echo '<li>' . sprintf(__('%1$s: %2$s'), __($pt_info['label']), dcCore::app()->blog->getPosts($params, true)->f(0)) . '</li>';
                        }
                        echo
                        '</ul>' .

                        '<h5>' . __('Permissions:') . '</h5>' .
                        '<ul>';
                        if ($v['super']) {
                            echo '<li class="user_super">' . __('Super administrator') . '<br />' .
                            '<span class="form-note">' . __('All rights on all blogs.') . '</span></li>';
                        } else {
                            foreach ($v['p'] as $p => $V) {
                                if (isset($perm_types[$p])) {
                                    echo '<li ' . ($p == 'admin' ? 'class="user_admin"' : '') . '>' . __($perm_types[$p]);
                                } else {
                                    echo '<li>' . sprintf(__('[%s] (unreferenced permission)'), $p);
                                }

                                if ($p == 'admin') {
                                    echo '<br /><span class="form-note">' . __('All rights on this blog.') . '</span>';
                                }
                                echo '</li>';
                            }
                        }
                        echo
                        '</ul>';

                        if (!$v['super'] && dcCore::app()->auth->isSuperAdmin()) {
                            echo
                            '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post">' .
                            '<p class="change-user-perm"><input type="submit" class="reset" value="' . __('Change permissions') . '" />' .
                            form::hidden(['redir'], dcCore::app()->adminurl->get('admin.blog.pref', ['id' => $k], '&')) .
                            form::hidden(['action'], 'perms') .
                            form::hidden(['users[]'], $k) .
                            form::hidden(['blogs[]'], $da->blog_id) .
                            dcCore::app()->formNonce() .
                            '</p>' .
                            '</form>';
                        }
                        echo '</div>';
                    }
                }
                echo '</div>';
                if ($current_blog_id != dcCore::app()->blog->id) {
                    dcCore::app()->setBlog($current_blog_id);
                }
            }

            echo '</div>';
        }

        dcPage::helpBlock('core_blog_pref');
        dcPage::close();
    }
}

adminBlogPref::init();
adminBlogPref::process();
adminBlogPref::render();
