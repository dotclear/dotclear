<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

$standalone = !isset($edit_blog_mode);

$blog_id = false;

if ($standalone) {
    require dirname(__FILE__) . '/../inc/admin/prepend.php';
    dcPage::check('admin');
    $blog_id       = $core->blog->id;
    $blog_status   = $core->blog->status;
    $blog_name     = $core->blog->name;
    $blog_desc     = $core->blog->desc;
    $blog_settings = $core->blog->settings;
    $blog_url      = $core->blog->url;

    $action = $core->adminurl->get("admin.blog.pref");
    $redir  = $core->adminurl->get("admin.blog.pref");
} else {
    dcPage::checkSuper();
    try
    {
        if (empty($_REQUEST['id'])) {
            throw new Exception(__('No given blog id.'));
        }
        $rs = $core->getBlog($_REQUEST['id']);

        if (!$rs) {
            throw new Exception(__('No such blog.'));
        }

        $blog_id       = $rs->blog_id;
        $blog_status   = $rs->blog_status;
        $blog_name     = $rs->blog_name;
        $blog_desc     = $rs->blog_desc;
        $blog_settings = new dcSettings($core, $blog_id);
        $blog_url      = $rs->blog_url;
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    $action = $core->adminurl->get("admin.blog");
    $redir  = $core->adminurl->get("admin.blog", array('id' => "%s"), '&', true);
}

# Language codes
$lang_combo = dcAdminCombos::getAdminLangsCombo();

# Status combo
$status_combo = dcAdminCombos::getBlogStatusescombo();

# Date format combo
$now                = time();
$date_formats       = $blog_settings->system->date_formats;
$time_formats       = $blog_settings->system->time_formats;
$date_formats_combo = array('' => '');
foreach ($date_formats as $format) {
    $date_formats_combo[dt::str($format, $now)] = $format;
}
$time_formats_combo = array('' => '');
foreach ($time_formats as $format) {
    $time_formats_combo[dt::str($format, $now)] = $format;
}

# URL scan modes
$url_scan_combo = array(
    'PATH_INFO'    => 'path_info',
    'QUERY_STRING' => 'query_string'
);

# Post URL combo
$post_url_combo = array(
    __('year/month/day/title') => '{y}/{m}/{d}/{t}',
    __('year/month/title')     => '{y}/{m}/{t}',
    __('year/title')           => '{y}/{t}',
    __('title')                => '{t}',
    __('post id/title')        => '{id}/{t}',
    __('post id')              => '{id}'
);
if (!in_array($blog_settings->system->post_url_format, $post_url_combo)) {
    $post_url_combo[html::escapeHTML($blog_settings->system->post_url_format)] = html::escapeHTML($blog_settings->system->post_url_format);
}

# Note title tag combo
$note_title_tag_combo = array(
    __('H4') => 0,
    __('H3') => 1,
    __('P')  => 2
);

# Image title combo
$img_title_combo = array(
    __('(none)')                     => '',
    __('Title')                      => 'Title ;; separator(, )',
    __('Title, Date')                => 'Title ;; Date(%b %Y) ;; separator(, )',
    __('Title, Country, Date')       => 'Title ;; Country ;; Date(%b %Y) ;; separator(, )',
    __('Title, City, Country, Date') => 'Title ;; City ;; Country ;; Date(%b %Y) ;; separator(, )'
);
if (!in_array($blog_settings->system->media_img_title_pattern, $img_title_combo)) {
    $img_title_combo[html::escapeHTML($blog_settings->system->media_img_title_pattern)] = html::escapeHTML($blog_settings->system->media_img_title_pattern);
}

# Image default size combo
$img_default_size_combo = array();
try {
    $media                                  = new dcMedia($core);
    $img_default_size_combo[__('original')] = 'o';
    foreach ($media->thumb_sizes as $code => $size) {
        $img_default_size_combo[__($size[2])] = $code;
    }
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Image default alignment combo
$img_default_alignment_combo = array(
    __('None')   => 'none',
    __('Left')   => 'left',
    __('Right')  => 'right',
    __('Center') => 'center'
);

# Image default legend and title combo
$img_default_legend_combo = array(
    __('Legend and title') => 'legend',
    __('Title')            => 'title',
    __('None')             => 'none'
);

# Robots policy options
$robots_policy_options = array(
    'INDEX,FOLLOW'               => __("I would like search engines and archivers to index and archive my blog's content."),
    'INDEX,FOLLOW,NOARCHIVE'     => __("I would like search engines and archivers to index but not archive my blog's content."),
    'NOINDEX,NOFOLLOW,NOARCHIVE' => __("I would like to prevent search engines and archivers from indexing or archiving my blog's content.")
);

# jQuery available versions
$jquery_root           = dirname(__FILE__) . '/../inc/js/jquery';
$jquery_versions_combo = array(__('Default') . ' (' . DC_DEFAULT_JQUERY . ')' => DC_DEFAULT_JQUERY);
if (is_dir($jquery_root) && is_readable($jquery_root)) {
    if (($d = @dir($jquery_root)) !== false) {
        while (($entry = $d->read()) !== false) {
            if ($entry != '.' && $entry != '..' && substr($entry, 0, 1) != '.' && is_dir($jquery_root . '/' . $entry)) {
                if ($entry != DC_DEFAULT_JQUERY) {
                    $jquery_versions_combo[$entry] = $entry;
                }
            }
        }
    }
}

# Update a blog
if ($blog_id && !empty($_POST) && $core->auth->check('admin', $blog_id)) {
    $cur            = $core->con->openCursor($core->prefix . 'blog');
    $cur->blog_id   = $_POST['blog_id'];
    $cur->blog_url  = preg_replace('/\?+$/', '?', $_POST['blog_url']);
    $cur->blog_name = $_POST['blog_name'];
    $cur->blog_desc = $_POST['blog_desc'];

    if ($core->auth->isSuperAdmin() && in_array($_POST['blog_status'], $status_combo)) {
        $cur->blog_status = (int) $_POST['blog_status'];
    }

    $media_img_t_size = (integer) $_POST['media_img_t_size'];
    if ($media_img_t_size < 0) {$media_img_t_size = 100;}

    $media_img_s_size = (integer) $_POST['media_img_s_size'];
    if ($media_img_s_size < 0) {$media_img_s_size = 240;}

    $media_img_m_size = (integer) $_POST['media_img_m_size'];
    if ($media_img_m_size < 0) {$media_img_m_size = 448;}

    $media_video_width = (integer) $_POST['media_video_width'];
    if ($media_video_width < 0) {$media_video_width = 400;}

    $media_video_height = (integer) $_POST['media_video_height'];
    if ($media_video_height < 0) {$media_video_height = 300;}

    $nb_post_for_home = abs((integer) $_POST['nb_post_for_home']);
    if ($nb_post_for_home < 1) {$nb_post_for_home = 1;}

    $nb_post_per_page = abs((integer) $_POST['nb_post_per_page']);
    if ($nb_post_per_page < 1) {$nb_post_per_page = 1;}

    $nb_post_per_feed = abs((integer) $_POST['nb_post_per_feed']);
    if ($nb_post_per_feed < 1) {$nb_post_per_feed = 1;}

    $nb_comment_per_feed = abs((integer) $_POST['nb_comment_per_feed']);
    if ($nb_comment_per_feed < 1) {$nb_comment_per_feed = 1;}

    try
    {
        if ($cur->blog_id != null && $cur->blog_id != $blog_id) {
            $rs = $core->getBlog($cur->blog_id);

            if ($rs) {
                throw new Exception(__('This blog ID is already used.'));
            }
        }

        # --BEHAVIOR-- adminBeforeBlogUpdate
        $core->callBehavior('adminBeforeBlogUpdate', $cur, $blog_id);

        if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $_POST['lang'])) {
            throw new Exception(__('Invalid language code'));
        }

        $core->updBlog($blog_id, $cur);

        # --BEHAVIOR-- adminAfterBlogUpdate
        $core->callBehavior('adminAfterBlogUpdate', $cur, $blog_id);

        if ($cur->blog_id != null && $cur->blog_id != $blog_id) {
            if ($blog_id == $core->blog->id) {
                $core->setBlog($cur->blog_id);
                $_SESSION['sess_blog_id'] = $cur->blog_id;
                $blog_settings            = $core->blog->settings;
            } else {
                $blog_settings = new dcSettings($core, $cur->blog_id);
            }

            $blog_id = $cur->blog_id;
        }

        $blog_settings->addNameSpace('system');

        $blog_settings->system->put('editor', $_POST['editor']);
        $blog_settings->system->put('copyright_notice', $_POST['copyright_notice']);
        $blog_settings->system->put('post_url_format', $_POST['post_url_format']);
        $blog_settings->system->put('lang', $_POST['lang']);
        $blog_settings->system->put('blog_timezone', $_POST['blog_timezone']);
        $blog_settings->system->put('date_format', $_POST['date_format']);
        $blog_settings->system->put('time_format', $_POST['time_format']);
        $blog_settings->system->put('comments_ttl', abs((integer) $_POST['comments_ttl']));
        $blog_settings->system->put('trackbacks_ttl', abs((integer) $_POST['trackbacks_ttl']));
        $blog_settings->system->put('allow_comments', !empty($_POST['allow_comments']));
        $blog_settings->system->put('allow_trackbacks', !empty($_POST['allow_trackbacks']));
        $blog_settings->system->put('comments_pub', empty($_POST['comments_pub']));
        $blog_settings->system->put('trackbacks_pub', empty($_POST['trackbacks_pub']));
        $blog_settings->system->put('comments_nofollow', !empty($_POST['comments_nofollow']));
        $blog_settings->system->put('wiki_comments', !empty($_POST['wiki_comments']));
        $blog_settings->system->put('comment_preview_optional', !empty($_POST['comment_preview_optional']));
        $blog_settings->system->put('enable_xmlrpc', !empty($_POST['enable_xmlrpc']));
        $blog_settings->system->put('note_title_tag', $_POST['note_title_tag']);
        $blog_settings->system->put('nb_post_for_home', $nb_post_for_home);
        $blog_settings->system->put('nb_post_per_page', $nb_post_per_page);
        $blog_settings->system->put('use_smilies', !empty($_POST['use_smilies']));
        $blog_settings->system->put('no_search', !empty($_POST['no_search']));
        $blog_settings->system->put('inc_subcats', !empty($_POST['inc_subcats']));
        $blog_settings->system->put('media_img_t_size', $media_img_t_size);
        $blog_settings->system->put('media_img_s_size', $media_img_s_size);
        $blog_settings->system->put('media_img_m_size', $media_img_m_size);
        $blog_settings->system->put('media_video_width', $media_video_width);
        $blog_settings->system->put('media_video_height', $media_video_height);
        $blog_settings->system->put('media_flash_fallback', !empty($_POST['media_flash_fallback']));
        $blog_settings->system->put('media_img_title_pattern', $_POST['media_img_title_pattern']);
        $blog_settings->system->put('media_img_use_dto_first', !empty($_POST['media_img_use_dto_first']));
        $blog_settings->system->put('media_img_no_date_alone', !empty($_POST['media_img_no_date_alone']));
        $blog_settings->system->put('media_img_default_size', $_POST['media_img_default_size']);
        $blog_settings->system->put('media_img_default_alignment', $_POST['media_img_default_alignment']);
        $blog_settings->system->put('media_img_default_link', !empty($_POST['media_img_default_link']));
        $blog_settings->system->put('media_img_default_legend', $_POST['media_img_default_legend']);
        $blog_settings->system->put('nb_post_per_feed', $nb_post_per_feed);
        $blog_settings->system->put('nb_comment_per_feed', $nb_comment_per_feed);
        $blog_settings->system->put('short_feed_items', !empty($_POST['short_feed_items']));
        if (isset($_POST['robots_policy'])) {
            $blog_settings->system->put('robots_policy', $_POST['robots_policy']);
        }
        $blog_settings->system->put('jquery_version', $_POST['jquery_version']);
        $blog_settings->system->put('prevents_clickjacking', !empty($_POST['prevents_clickjacking']));

        # --BEHAVIOR-- adminBeforeBlogSettingsUpdate
        $core->callBehavior('adminBeforeBlogSettingsUpdate', $blog_settings);

        if ($core->auth->isSuperAdmin() && in_array($_POST['url_scan'], $url_scan_combo)) {
            $blog_settings->system->put('url_scan', $_POST['url_scan']);
        }
        dcPage::addSuccessNotice(__('Blog has been successfully updated.'));

        http::redirect(sprintf($redir, $blog_id));
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

// Display

if ($standalone) {
    $breadcrumb = dcPage::breadcrumb(
        array(
            html::escapeHTML($blog_name) => '',
            __('Blog settings')          => ''
        )
    );
} else {
    $breadcrumb = dcPage::breadcrumb(
        array(
            __('System')                                               => '',
            __('Blogs')                                                => $core->adminurl->get("admin.blogs"),
            __('Blog settings') . ' : ' . html::escapeHTML($blog_name) => ''
        ));
}

$desc_editor = $core->auth->getOption('editor');
$rte_flag    = true;
$rte_flags   = @$core->auth->user_prefs->interface->rte_flags;
if (is_array($rte_flags) && in_array('blog_descr', $rte_flags)) {
    $rte_flag = $rte_flags['blog_descr'];
}

dcPage::open(__('Blog settings'),
    '<script type="text/javascript">' . "\n" .
    dcPage::jsVar('dotclear.msg.warning_path_info',
        __('Warning: except for special configurations, it is generally advised to have a trailing "/" in your blog URL in PATH_INFO mode.')) . "\n" .
    dcPage::jsVar('dotclear.msg.warning_query_string',
        __('Warning: except for special configurations, it is generally advised to have a trailing "?" in your blog URL in QUERY_STRING mode.')) . "\n" .
    "</script>" .
    dcPage::jsConfirmClose('blog-form') .
    ($rte_flag ? $core->callBehavior('adminPostEditor', $desc_editor['xhtml'], 'blog_desc', array('#blog_desc'), 'xhtml') : '') .
    dcPage::jsLoad('js/_blog_pref.js') .

    # --BEHAVIOR-- adminBlogPreferencesHeaders
    $core->callBehavior('adminBlogPreferencesHeaders') .

    dcPage::jsPageTabs(),
    $breadcrumb
);

if ($blog_id) {
    if (!empty($_GET['add'])) {
        dcPage::success(__('Blog has been successfully created.'));
    }

    if (!empty($_GET['upd'])) {
        dcPage::success(__('Blog has been successfully updated.'));
    }

    echo
    '<div class="multi-part" id="params" title="' . __('Parameters') . '">' .
    '<h3 class="out-of-screen-if-js">' . __('Parameters') . '</h3>' .
        '<form action="' . $action . '" method="post" id="blog-form">';

    echo
    '<div class="fieldset"><h4>' . __('Blog details') . '</h4>' .
    $core->formNonce();

    echo
    '<p><label for="blog_name" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog name:') . '</label>' .
    form::field('blog_name', 30, 255,
        array(
            'default'    => html::escapeHTML($blog_name),
            'extra_html' => 'required placeholder="' . __('Blog name') . '"'
        )
    ) . '</p>';

    echo
    '<p class="area"><label for="blog_desc">' . __('Blog description:') . '</label>' .
    form::textarea('blog_desc', 60, 5, html::escapeHTML($blog_desc)) . '</p>';

    if ($core->auth->isSuperAdmin()) {
        echo
        '<p><label for="blog_status">' . __('Blog status:') . '</label>' .
        form::combo('blog_status', $status_combo, $blog_status) . '</p>';

    } else {
        /*
        Only super admins can change the blog ID and URL, but we need to pass
        their values to the POST request via hidden html input values  so as
        to allow admins to update other settings.
        Otherwise dcCore::getBlogCursor() throws an exception.
         */
        echo
        form::hidden('blog_id', html::escapeHTML($blog_id)) .
        form::hidden('blog_url', html::escapeHTML($blog_url));
    }

    echo '</div>';

    echo
    '<div class="fieldset"><h4>' . __('Blog configuration') . '</h4>' .

    '<p><label for="editor">' . __('Blog editor name:') . '</label>' .
    form::field('editor', 30, 255, html::escapeHTML($blog_settings->system->editor)) .
    '</p>' .

    '<p><label for="lang">' . __('Default language:') . '</label>' .
    form::combo('lang', $lang_combo, $blog_settings->system->lang, 'l10n') .
    '</p>' .

    '<p><label for="blog_timezone">' . __('Blog timezone:') . '</label>' .
    form::combo('blog_timezone', dt::getZones(true, true), html::escapeHTML($blog_settings->system->blog_timezone)) .
    '</p>' .

    '<p><label for="copyright_notice">' . __('Copyright notice:') . '</label>' .
    form::field('copyright_notice', 30, 255, html::escapeHTML($blog_settings->system->copyright_notice)) .
        '</p>' .

        '</div>';

    echo
    '<div class="fieldset"><h4>' . __('Comments and trackbacks') . '</h4>' .

    '<div class="two-cols">' .

    '<div class="col">' .
    '<p><label for="allow_comments" class="classic">' .
    form::checkbox('allow_comments', '1', $blog_settings->system->allow_comments) .
    __('Accept comments') . '</label></p>' .
    '<p><label for="comments_pub" class="classic">' .
    form::checkbox('comments_pub', '1', !$blog_settings->system->comments_pub) .
    __('Moderate comments') . '</label></p>' .
    '<p><label for="comments_ttl" class="classic">' . sprintf(__('Leave comments open for %s days') . '.',
        form::number('comments_ttl', array(
            'min'     => 0,
            'max'     => 999,
            'default' => $blog_settings->system->comments_ttl)
        )) .
    '</label></p>' .
    '<p class="form-note">' . __('No limit: leave blank.') . '</p>' .
    '<p><label for="wiki_comments" class="classic">' .
    form::checkbox('wiki_comments', '1', $blog_settings->system->wiki_comments) .
    __('Wiki syntax for comments') . '</label></p>' .
    '<p><label for="comment_preview_optional" class="classic">' .
    form::checkbox('comment_preview_optional', '1', $blog_settings->system->comment_preview_optional) .
    __('Preview of comment before submit is not mandatory') . '</label></p>' .
    '</div>' .

    '<div class="col">' .
    '<p><label for="allow_trackbacks" class="classic">' .
    form::checkbox('allow_trackbacks', '1', $blog_settings->system->allow_trackbacks) .
    __('Accept trackbacks') . '</label></p>' .
    '<p><label for="trackbacks_pub" class="classic">' .
    form::checkbox('trackbacks_pub', '1', !$blog_settings->system->trackbacks_pub) .
    __('Moderate trackbacks') . '</label></p>' .
    '<p><label for="trackbacks_ttl" class="classic">' . sprintf(__('Leave trackbacks open for %s days') . '.',
        form::number('trackbacks_ttl', array(
            'min'     => 0,
            'max'     => 999,
            'default' => $blog_settings->system->trackbacks_ttl)
        )) .
    '</label></p>' .
    '<p class="form-note">' . __('No limit: leave blank.') . '</p>' .
    '<p><label for="comments_nofollow" class="classic">' .
    form::checkbox('comments_nofollow', '1', $blog_settings->system->comments_nofollow) .
    __('Add "nofollow" relation on comments and trackbacks links') . '</label></p>' .
    '</div>' .
    '<br class="clear" />' . //Opera sucks

    '</div>' .
    '<br class="clear" />' . //Opera sucks
    '</div>';

    echo
    '<div class="fieldset"><h4>' . __('Blog presentation') . '</h4>' .
    '<div class="two-cols">' .
    '<div class="col">' .
    '<p><label for="date_format">' . __('Date format:') . '</label> ' .
    form::field('date_format', 30, 255, html::escapeHTML($blog_settings->system->date_format)) .
    form::combo('date_format_select', $date_formats_combo, array('extra_html' => 'title="' . __('Pattern of date') . '"')) .
    '</p>' .
    '<p class="chosen form-note">' . __('Sample:') . ' ' . dt::str(html::escapeHTML($blog_settings->system->date_format)) . '</p>' .

    '<p><label for="time_format">' . __('Time format:') . '</label>' .
    form::field('time_format', 30, 255, html::escapeHTML($blog_settings->system->time_format)) .
    form::combo('time_format_select', $time_formats_combo, array('extra_html' => 'title="' . __('Pattern of time') . '"')) .
    '</p>' .
    '<p class="chosen form-note">' . __('Sample:') . ' ' . dt::str(html::escapeHTML($blog_settings->system->time_format)) . '</p>' .

    '<p><label for="use_smilies" class="classic">' .
    form::checkbox('use_smilies', '1', $blog_settings->system->use_smilies) .
    __('Display smilies on entries and comments') . '</label></p>' .

    '<p><label for="no_search" class="classic">' .
    form::checkbox('no_search', '1', $blog_settings->system->no_search) .
    __('Disable internal search system') . '</label></p>' .
    '</div>' .

    '<div class="col">' .
    '<p><label for="nb_post_for_home" class="classic">' . sprintf(__('Display %s entries on home page'),
        form::number('nb_post_for_home', array(
            'min'     => 1,
            'max'     => 999,
            'default' => $blog_settings->system->nb_post_for_home)
        )) .
    '</label></p>' .

    '<p><label for="nb_post_per_page" class="classic">' . sprintf(__('Display %s entries per page'),
        form::number('nb_post_per_page', array(
            'min'     => 1,
            'max'     => 999,
            'default' => $blog_settings->system->nb_post_per_page)
        )) .
    '</label></p>' .

    '<p><label for="nb_post_per_feed" class="classic">' . sprintf(__('Display %s entries per feed'),
        form::number('nb_post_per_feed', array(
            'min'     => 1,
            'max'     => 999,
            'default' => $blog_settings->system->nb_post_per_feed)
        )) .
    '</label></p>' .

    '<p><label for="nb_comment_per_feed" class="classic">' . sprintf(__('Display %s comments per feed'),
        form::number('nb_comment_per_feed', array(
            'min'     => 1,
            'max'     => 999,
            'default' => $blog_settings->system->nb_comment_per_feed)
        )) .
    '</label></p>' .

    '<p><label for="short_feed_items" class="classic">' .
    form::checkbox('short_feed_items', '1', $blog_settings->system->short_feed_items) .
    __('Truncate feeds') . '</label></p>' .

    '<p><label for="inc_subcats" class="classic">' .
    form::checkbox('inc_subcats', '1', $blog_settings->system->inc_subcats) .
    __('Include sub-categories in category page and category posts feed') . '</label></p>' .
    '</div>' .
    '</div>' .
    '<br class="clear" />' . //Opera sucks
    '</div>';

    echo
    '<div class="fieldset"><h4 id="medias-settings">' . __('Media and images') . '</h4>' .
    '<p class="form-note warning">' .
    __('Please note that if you change current settings bellow, they will now apply to all new images in the media manager.') .
    ' ' . __('Be carefull if you share it with other blogs in your installation.') . '<br />' .
    __('Set -1 to use the default size, set 0 to ignore this thumbnail size (images only).') . '</p>' .

    '<div class="two-cols">' .
    '<div class="col">' .
    '<h5>' . __('Generated image sizes (max dimension in pixels)') . '</h5>' .
    '<p class="field"><label for="media_img_t_size">' . __('Thumbnail') . '</label> ' .
    form::number('media_img_t_size', array(
        'min'     => -1,
        'max'     => 999,
        'default' => $blog_settings->system->media_img_t_size
    )) .
    '</p>' .

    '<p class="field"><label for="media_img_s_size">' . __('Small') . '</label> ' .
    form::number('media_img_s_size', array(
        'min'     => -1,
        'max'     => 999,
        'default' => $blog_settings->system->media_img_s_size
    )) .
    '</p>' .

    '<p class="field"><label for="media_img_m_size">' . __('Medium') . '</label> ' .
    form::number('media_img_m_size', array(
        'min'     => -1,
        'max'     => 999,
        'default' => $blog_settings->system->media_img_m_size
    )) .
    '</p>' .

    '<h5>' . __('Default size of the inserted video (in pixels)') . '</h5>' .
    '<p class="field"><label for="media_video_width">' . __('Width') . '</label> ' .
    form::number('media_video_width', array(
        'min'     => -1,
        'max'     => 999,
        'default' => $blog_settings->system->media_video_width
    )) .
    '</p>' .

    '<p class="field"><label for="media_video_height">' . __('Height') . '</label> ' .
    form::number('media_video_height', array(
        'min'     => -1,
        'max'     => 999,
        'default' => $blog_settings->system->media_video_height
    )) .
    '</p>' .

    '<h5>' . __('Flash player') . '</h5>' .
    '<p><label for="media_flash_fallback">' .
    form::checkbox('media_flash_fallback', '1', $blog_settings->system->media_flash_fallback) .
    __('Insert Flash player fallback for video (mp4 or m4v) and audio (mp3) media') . '</label></p>' .
    '<p class="form-note info">' . __('For flv video, the Flash player will be anyway inserted.') . '</p>' .
    '</div>' .

    '<div class="col">' .
    '<h5>' . __('Default image insertion attributes') . '</h5>' .
    '<p class="vertical-separator"><label for="media_img_title_pattern">' . __('Inserted image title') . '</label>' .
    form::combo('media_img_title_pattern', $img_title_combo, html::escapeHTML($blog_settings->system->media_img_title_pattern)) . '</p>' .
    '<p><label for="media_img_use_dto_first" class="classic">' .
    form::checkbox('media_img_use_dto_first', '1', $blog_settings->system->media_img_use_dto_first) .
    __('Use original media date if possible') . '</label></p>' .
    '<p><label for="media_img_no_date_alone" class="classic">' .
    form::checkbox('media_img_no_date_alone', '1', $blog_settings->system->media_img_no_date_alone) .
    __('Do not display date if alone in title') . '</label></p>' .
    '<p class="form-note info">' . __('It is retrieved from the picture\'s metadata.') . '</p>' .

    '<p class="field vertical-separator"><label for="media_img_default_size">' . __('Size of inserted image:') . '</label>' .
    form::combo('media_img_default_size', $img_default_size_combo,
        (html::escapeHTML($blog_settings->system->media_img_default_size) != '' ? html::escapeHTML($blog_settings->system->media_img_default_size) : 'm')) .
    '</p>' .
    '<p class="field"><label for="media_img_default_alignment">' . __('Image alignment:') . '</label>' .
    form::combo('media_img_default_alignment', $img_default_alignment_combo, html::escapeHTML($blog_settings->system->media_img_default_alignment)) .
    '</p>' .
    '<p><label for="media_img_default_link">' .
    form::checkbox('media_img_default_link', '1', $blog_settings->system->media_img_default_link) .
    __('Insert a link to the original image') . '</label></p>' .
    '<p class="field"><label for="media_img_default_legend">' . __('Image legend and title:') . '</label>' .
    form::combo('media_img_default_legend', $img_default_legend_combo, html::escapeHTML($blog_settings->system->media_img_default_legend)) .
    '</p>' .
    '</div>' .
    '</div>' .
    '<br class="clear" />' . //Opera sucks

    '</div>';

    echo '<div id="advanced-pref"><h3>' . __('Advanced parameters') . '</h3>';

    if ($core->auth->isSuperAdmin()) {
        echo '<div class="fieldset"><h4>' . __('Blog details') . '</h4>';
        echo
        '<p><label for="blog_id" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog ID:') . '</label>' .
        form::field('blog_id', 30, 32, html::escapeHTML($blog_id), '', '', false, 'required placeholder="' . __('Blog ID') . '"') . '</p>' .
        '<p class="form-note">' . __('At least 2 characters using letters, numbers or symbols.') . '</p> ' .
        '<p class="form-note warn">' . __('Please note that changing your blog ID may require changes in your public index.php file.') . '</p>';

        echo
        '<p><label for="blog_url" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Blog URL:') . '</label>' .
        form::url('blog_url', array(
            'size'       => 50,
            'max'        => 255,
            'default'    => html::escapeHTML($blog_url),
            'extra_html' => 'required placeholder="' . __('Blog URL') . '"'
        )) .
        '</p>' .

        '<p><label for="url_scan">' . __('URL scan method:') . '</label>' .
        form::combo('url_scan', $url_scan_combo, $blog_settings->system->url_scan) . '</p>';

        try
        {
            # Test URL of blog by testing it's ATOM feed
            $file    = $blog_url . $core->url->getURLFor('feed', 'atom');
            $path    = '';
            $status  = '404';
            $content = '';

            $client = netHttp::initClient($file, $path);
            if ($client !== false) {
                $client->setTimeout(4);
                $client->setUserAgent($_SERVER['HTTP_USER_AGENT']);
                $client->get($path);
                $status  = $client->getStatus();
                $content = $client->getContent();
            }
            if ($status != '200') {
                // Might be 404 (URL not found), 670 (blog not online), ...
                echo
                '<p class="form-note warn">' .
                sprintf(__('The URL of blog or the URL scan method might not be well set (<code>%s</code> return a <strong>%s</strong> status).'),
                    html::escapeHTML($file), $status) .
                    '</p>';
            } else {
                if (substr($content, 0, 6) != '<?xml ') {
                    // Not well formed XML feed
                    echo
                    '<p class="form-note warn">' .
                    sprintf(__('The URL of blog or the URL scan method might not be well set (<code>%s</code> does not return an ATOM feed).'),
                        html::escapeHTML($file)) .
                        '</p>';
                }
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
        echo '</div>';
    }

    echo
    '<div class="fieldset"><h4>' . __('Blog configuration') . '</h4>' .

    '<p><label for="post_url_format">' . __('New post URL format:') . '</label>' .
    form::combo('post_url_format', $post_url_combo, html::escapeHTML($blog_settings->system->post_url_format)) .
    '</p>' .
    '<p class="chosen form-note">' . __('Sample:') . ' ' . $core->blog->getPostURL('', date('Y-m-d H:i:00', $now), __('Dotclear'), 42) . '</p>' .
    '</p>' .

    '<p><label for="note_title_tag">' . __('HTML tag for the title of the notes on the blog:') . '</label>' .
    form::combo('note_title_tag', $note_title_tag_combo, $blog_settings->system->note_title_tag) .
    '</p>' .

    '<p><label for="enable_xmlrpc" class="classic">' .
    form::checkbox('enable_xmlrpc', '1', $blog_settings->system->enable_xmlrpc) .
    __('Enable XML/RPC interface') . '</label>' . '</p>' .
    '<p class="form-note info">' . __('XML/RPC interface allows you to edit your blog with an external client.') . '</p>';

    if ($blog_settings->system->enable_xmlrpc) {
        echo
        '<p>' . __('XML/RPC interface is active. You should set the following parameters on your XML/RPC client:') . '</p>' .
        '<ul>' .
        '<li>' . __('Server URL:') . ' <strong><code>' .
        sprintf(DC_XMLRPC_URL, $core->blog->url, $core->blog->id) .
        '</code></strong></li>' .
        '<li>' . __('Blogging system:') . ' <strong><code>Movable Type</code></strong></li>' .
        '<li>' . __('User name:') . ' <strong><code>' . $core->auth->userID() . '</code></strong></li>' .
        '<li>' . __('Password:') . ' <strong><code>&lt;' . __('your password') . '&gt;</code></strong></li>' .
        '<li>' . __('Blog ID:') . ' <strong><code>1</code></strong></li>' .
            '</ul>';
    }

    echo
        '</div>';

    // Search engines policies
    echo '<div class="fieldset"><h4>' . __('Search engines robots policy') . '</h4>';

    $i = 0;
    foreach ($robots_policy_options as $k => $v) {
        echo '<p><label for="robots_policy-' . $i . '" class="classic">' .
        form::radio(array('robots_policy', 'robots_policy-' . $i), $k, $blog_settings->system->robots_policy == $k) . ' ' . $v . '</label></p>';
        $i++;
    }

    echo '</div>';

    echo '<div class="fieldset"><h4>' . __('jQuery javascript library') . '</h4>' .

    '<p><label for="jquery_version" class="classic">' . __('jQuery version to be loaded for this blog:') . '</label>' . ' ' .
    form::combo('jquery_version', $jquery_versions_combo, $blog_settings->system->jquery_version) .
    '</p>' .
    '<br class="clear" />' . //Opera sucks

    '</div>';

    echo '<div class="fieldset"><h4>' . __('Blog security') . '</h4>' .

    '<p><label for="prevents_clickjacking" class="classic">' .
    form::checkbox('prevents_clickjacking', '1', $blog_settings->system->prevents_clickjacking) .
    __('Protect the blog from Clickjacking (see <a href="https://en.wikipedia.org/wiki/Clickjacking">Wikipedia</a>)') . '</label></p>' .
    '<br class="clear" />' . //Opera sucks

    '</div>';

    echo '</div>'; // End advanced

    echo '<div id="plugins-pref"><h3>' . __('Plugins parameters') . '</h3>';

    # --BEHAVIOR-- adminBlogPreferencesForm
    $core->callBehavior('adminBlogPreferencesForm', $core, $blog_settings);

    echo '</div>'; // End 3rd party, aka plugins

    echo
    '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
        (!$standalone ? form::hidden('id', $blog_id) : '') .
        '</p>' .
        '</form>';

    if ($core->auth->isSuperAdmin() && $blog_id != $core->blog->id) {
        echo
        '<form action="' . $core->adminurl->get("admin.blog.del") . '" method="post">' .
        '<p><input type="submit" class="delete" value="' . __('Delete this blog') . '" />' .
        form::hidden(array('blog_id'), $blog_id) .
        $core->formNonce() . '</p>' .
            '</form>';
    } else {
        if ($blog_id == $core->blog->id) {
            echo '<p class="message">' . __('The current blog cannot be deleted.') . '</p>';
        } else {
            echo '<p class="message">' . __('Only superadmin can delete a blog.') . '</p>';
        }
    }

    echo '</div>';

    #
    # Users on the blog (with permissions)

    $blog_users = $core->getBlogPermissions($blog_id, $core->auth->isSuperAdmin());
    $perm_types = $core->auth->getPermissionsTypes();

    echo
    '<div class="multi-part" id="users" title="' . __('Users') . '">' .
    '<h3 class="out-of-screen-if-js">' . __('Users on this blog') . '</h3>';

    if (empty($blog_users)) {
        echo '<p>' . __('No users') . '</p>';
    } else {
        if ($core->auth->isSuperAdmin()) {
            $user_url_p = '<a href="' . $core->adminurl->get("admin.user", array('id' => '%1$s'), '&amp;', true) . '">%1$s</a>';
        } else {
            $user_url_p = '%1$s';
        }

        # Sort users list on user_id key
        dcUtils::lexicalKeySort($blog_users);

        $post_type       = $core->getPostTypes();
        $current_blog_id = $core->blog->id;
        if ($blog_id != $core->blog->id) {
            $core->setBlog($blog_id);
        }

        echo '<div>';
        foreach ($blog_users as $k => $v) {
            if (count($v['p']) > 0) {
                echo
                '<div class="user-perm' . ($v['super'] ? ' user_super' : '') . '">' .
                '<h4>' . sprintf($user_url_p, html::escapeHTML($k)) .
                ' (' . html::escapeHTML(dcUtils::getUserCN(
                    $k, $v['name'], $v['firstname'], $v['displayname']
                )) . ')</h4>';

                if ($core->auth->isSuperAdmin()) {
                    echo
                    '<p>' . __('Email:') . ' ' .
                        ($v['email'] != '' ? '<a href="mailto:' . $v['email'] . '">' . $v['email'] . '</a>' : __('(none)')) .
                        '</p>';
                }

                echo
                '<h5>' . __('Publications on this blog:') . '</h5>' .
                    '<ul>';
                foreach ($post_type as $type => $pt_info) {
                    $params = array(
                        'post_type' => $type,
                        'user_id'   => $k
                    );
                    echo '<li>' . sprintf(__('%1$s: %2$s'), __($pt_info['label']), $core->blog->getPosts($params, true)->f(0)) . '</li>';
                }
                echo
                    '</ul>';

                echo
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

                if (!$v['super'] && $core->auth->isSuperAdmin()) {
                    echo
                    '<form action="' . $core->adminurl->get('admin.user.actions') . '" method="post">' .
                    '<p class="change-user-perm"><input type="submit" class="reset" value="' . __('Change permissions') . '" />' .
                    form::hidden(array('redir'), $core->adminurl->get("admin.blog.pref", array('id' => $k), '&')) .
                    form::hidden(array('action'), 'perms') .
                    form::hidden(array('users[]'), $k) .
                    form::hidden(array('blogs[]'), $blog_id) .
                    $core->formNonce() .
                        '</p>' .
                        '</form>';
                }
                echo '</div>';
            }
        }
        echo '</div>';
        if ($current_blog_id != $core->blog->id) {
            $core->setBlog($current_blog_id);
        }
    }

    echo '</div>';
}

dcPage::helpBlock('core_blog_pref');
dcPage::close();
