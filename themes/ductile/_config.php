<?php
/**
 * @brief Ductile, a theme for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

l10n::set(dirname(__FILE__) . '/locales/' . $_lang . '/admin');

if (preg_match('#^http(s)?://#', $core->blog->settings->system->themes_url)) {
    $img_url = http::concatURL($core->blog->settings->system->themes_url, '/' . $core->blog->settings->system->theme . '/img/');
} else {
    $img_url = http::concatURL($core->blog->url, $core->blog->settings->system->themes_url . '/' . $core->blog->settings->system->theme . '/img/');
}
$img_path = dirname(__FILE__) . '/img/';

$tpl_path = dirname(__FILE__) . '/tpl/';

$standalone_config = (boolean) $core->themes->moduleInfo($core->blog->settings->system->theme, 'standalone_config');

// Load contextual help
if (file_exists(dirname(__FILE__) . '/locales/' . $_lang . '/resources.php')) {
    require dirname(__FILE__) . '/locales/' . $_lang . '/resources.php';
}

$list_types = array(
    __('Title') => 'title',
    __('Short') => 'short',
    __('Full')  => 'full'
);
// Get all _entry-*.html in tpl folder of theme
$list_types_templates = files::scandir($tpl_path);
if (is_array($list_types_templates)) {
    foreach ($list_types_templates as $v) {
        if (preg_match('/^_entry\-(.*)\.html$/', $v, $m)) {
            if (isset($m[1])) {
                if (!in_array($m[1], $list_types)) {
                    // template not already in full list
                    $list_types[__($m[1])] = $m[1];
                }
            }
        }
    }
}

$contexts = array(
    'default'      => __('Home (first page)'),
    'default-page' => __('Home (other pages)'),
    'category'     => __('Entries for a category'),
    'tag'          => __('Entries for a tag'),
    'search'       => __('Search result entries'),
    'archive'      => __('Month archive entries')
);

$fonts = array(
    __('Default')           => '',
    __('Ductile primary')   => 'Ductile body',
    __('Ductile secondary') => 'Ductile alternate',
    __('Times New Roman')   => 'Times New Roman',
    __('Georgia')           => 'Georgia',
    __('Garamond')          => 'Garamond',
    __('Helvetica/Arial')   => 'Helvetica/Arial',
    __('Verdana')           => 'Verdana',
    __('Trebuchet MS')      => 'Trebuchet MS',
    __('Impact')            => 'Impact',
    __('Monospace')         => 'Monospace'
);

$webfont_apis = array(
    __('none')                => '',
    __('javascript (Adobe)')  => 'js',
    __('stylesheet (Google)') => 'css'
);

$font_families = array(
    // Theme standard
    'Ductile body'      => '"Century Schoolbook", "Century Schoolbook L", Georgia, serif',
    'Ductile alternate' => '"Franklin gothic medium", "arial narrow", "DejaVu Sans Condensed", "helvetica neue", helvetica, sans-serif',

    // Serif families
    'Times New Roman'   => 'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
    'Georgia'           => 'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif", "Bitstream Vera Serif", "Liberation Serif", Georgia, serif',
    'Garamond'          => '"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',

    // Sans-serif families
    'Helvetica/Arial'   => 'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
    'Verdana'           => 'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
    'Trebuchet MS'      => '"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',

    // Cursive families
    'Impact'            => 'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',

    // Monospace families
    'Monospace'         => 'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace'
);

function fontDef($c)
{
    global $font_families;

    return isset($font_families[$c]) ? '<span style="position:absolute;top:0;left:32em;">' . $font_families[$c] . '</span>' : '';
}

$ductile_base = array(
    // HTML
    'subtitle_hidden'          => null,
    'logo_src'                 => null,
    'preview_not_mandatory'    => null,
    // CSS
    'body_font'                => null,
    'body_webfont_family'      => null,
    'body_webfont_url'         => null,
    'body_webfont_api'         => null,
    'alternate_font'           => null,
    'alternate_webfont_family' => null,
    'alternate_webfont_url'    => null,
    'alternate_webfont_api'    => null,
    'blog_title_w'             => null,
    'blog_title_s'             => null,
    'blog_title_c'             => null,
    'post_title_w'             => null,
    'post_title_s'             => null,
    'post_title_c'             => null,
    'post_link_w'              => null,
    'post_link_v_c'            => null,
    'post_link_f_c'            => null,
    'blog_title_w_m'           => null,
    'blog_title_s_m'           => null,
    'blog_title_c_m'           => null,
    'post_title_w_m'           => null,
    'post_title_s_m'           => null,
    'post_title_c_m'           => null,
    'post_simple_title_c'      => null
);

$ductile_lists_base = array(
    'default'      => 'short',
    'default-page' => 'short',
    'category'     => 'short',
    'tag'          => 'short',
    'search'       => 'short',
    'archive'      => 'short'
);

$ductile_counts_base = array(
    'default'      => null,
    'default-page' => null,
    'category'     => null,
    'tag'          => null,
    'search'       => null
);

$ductile_user = $core->blog->settings->themes->get($core->blog->settings->system->theme . '_style');
$ductile_user = @unserialize($ductile_user);
if (!is_array($ductile_user)) {
    $ductile_user = array();
}
$ductile_user = array_merge($ductile_base, $ductile_user);

$ductile_lists = $core->blog->settings->themes->get($core->blog->settings->system->theme . '_entries_lists');
$ductile_lists = @unserialize($ductile_lists);
if (!is_array($ductile_lists)) {
    $ductile_lists = $ductile_lists_base;
}
$ductile_lists = array_merge($ductile_lists_base, $ductile_lists);

$ductile_counts = $core->blog->settings->themes->get($core->blog->settings->system->theme . '_entries_counts');
$ductile_counts = @unserialize($ductile_counts);
if (!is_array($ductile_counts)) {
    $ductile_counts = $ductile_counts_base;
}
$ductile_counts = array_merge($ductile_counts_base, $ductile_counts);

$ductile_stickers = $core->blog->settings->themes->get($core->blog->settings->system->theme . '_stickers');
$ductile_stickers = @unserialize($ductile_stickers);

// If no stickers defined, add feed Atom one
if (!is_array($ductile_stickers)) {
    $ductile_stickers = array(array(
        'label' => __('Subscribe'),
        'url'   => $core->blog->url .
        $core->url->getURLFor('feed', 'atom'),
        'image' => 'sticker-feed.png'
    ));
}

$ductile_stickers_full = array();
// Get all sticker images already used
if (is_array($ductile_stickers)) {
    foreach ($ductile_stickers as $v) {
        $ductile_stickers_full[] = $v['image'];
    }
}
// Get all sticker-*.png in img folder of theme
$ductile_stickers_images = files::scandir($img_path);
if (is_array($ductile_stickers_images)) {
    foreach ($ductile_stickers_images as $v) {
        if (preg_match('/^sticker\-(.*)\.png$/', $v)) {
            if (!in_array($v, $ductile_stickers_full)) {
                // image not already used
                $ductile_stickers[] = array(
                    'label' => null,
                    'url'   => null,
                    'image' => $v);
            }
        }
    }
}

$conf_tab = isset($_POST['conf_tab']) ? $_POST['conf_tab'] : 'html';

if (!empty($_POST)) {
    try
    {
        # HTML
        if ($conf_tab == 'html') {
            $ductile_user['subtitle_hidden']       = (integer) !empty($_POST['subtitle_hidden']);
            $ductile_user['logo_src']              = $_POST['logo_src'];
            $ductile_user['preview_not_mandatory'] = (integer) !empty($_POST['preview_not_mandatory']);

            $ductile_stickers = array();
            for ($i = 0; $i < count($_POST['sticker_image']); $i++) {
                $ductile_stickers[] = array(
                    'label' => $_POST['sticker_label'][$i],
                    'url'   => $_POST['sticker_url'][$i],
                    'image' => $_POST['sticker_image'][$i]
                );
            }

            $order = array();
            if (empty($_POST['ds_order']) && !empty($_POST['order'])) {
                $order = $_POST['order'];
                asort($order);
                $order = array_keys($order);
            }
            if (!empty($order)) {
                $new_ductile_stickers = array();
                foreach ($order as $i => $k) {
                    $new_ductile_stickers[] = array(
                        'label' => $ductile_stickers[$k]['label'],
                        'url'   => $ductile_stickers[$k]['url'],
                        'image' => $ductile_stickers[$k]['image']
                    );
                }
                $ductile_stickers = $new_ductile_stickers;
            }

            for ($i = 0; $i < count($_POST['list_type']); $i++) {
                $ductile_lists[$_POST['list_ctx'][$i]] = $_POST['list_type'][$i];
            }

            for ($i = 0; $i < count($_POST['count_nb']); $i++) {
                $ductile_counts[$_POST['count_ctx'][$i]] = $_POST['count_nb'][$i];
            }

        }

        # CSS
        if ($conf_tab == 'css') {
            $ductile_user['body_font']           = $_POST['body_font'];
            $ductile_user['body_webfont_family'] = $_POST['body_webfont_family'];
            $ductile_user['body_webfont_url']    = $_POST['body_webfont_url'];
            $ductile_user['body_webfont_api']    = $_POST['body_webfont_api'];

            $ductile_user['alternate_font']           = $_POST['alternate_font'];
            $ductile_user['alternate_webfont_family'] = $_POST['alternate_webfont_family'];
            $ductile_user['alternate_webfont_url']    = $_POST['alternate_webfont_url'];
            $ductile_user['alternate_webfont_api']    = $_POST['alternate_webfont_api'];

            $ductile_user['blog_title_w'] = (integer) !empty($_POST['blog_title_w']);
            $ductile_user['blog_title_s'] = dcThemeConfig::adjustFontSize($_POST['blog_title_s']);
            $ductile_user['blog_title_c'] = dcThemeConfig::adjustColor($_POST['blog_title_c']);

            $ductile_user['post_title_w'] = (integer) !empty($_POST['post_title_w']);
            $ductile_user['post_title_s'] = dcThemeConfig::adjustFontSize($_POST['post_title_s']);
            $ductile_user['post_title_c'] = dcThemeConfig::adjustColor($_POST['post_title_c']);

            $ductile_user['post_link_w']   = (integer) !empty($_POST['post_link_w']);
            $ductile_user['post_link_v_c'] = dcThemeConfig::adjustColor($_POST['post_link_v_c']);
            $ductile_user['post_link_f_c'] = dcThemeConfig::adjustColor($_POST['post_link_f_c']);

            $ductile_user['post_simple_title_c'] = dcThemeConfig::adjustColor($_POST['post_simple_title_c']);

            $ductile_user['blog_title_w_m'] = (integer) !empty($_POST['blog_title_w_m']);
            $ductile_user['blog_title_s_m'] = dcThemeConfig::adjustFontSize($_POST['blog_title_s_m']);
            $ductile_user['blog_title_c_m'] = dcThemeConfig::adjustColor($_POST['blog_title_c_m']);

            $ductile_user['post_title_w_m'] = (integer) !empty($_POST['post_title_w_m']);
            $ductile_user['post_title_s_m'] = dcThemeConfig::adjustFontSize($_POST['post_title_s_m']);
            $ductile_user['post_title_c_m'] = dcThemeConfig::adjustColor($_POST['post_title_c_m']);
        }

        $core->blog->settings->addNamespace('themes');
        $core->blog->settings->themes->put($core->blog->settings->system->theme . '_style', serialize($ductile_user));
        $core->blog->settings->themes->put($core->blog->settings->system->theme . '_stickers', serialize($ductile_stickers));
        $core->blog->settings->themes->put($core->blog->settings->system->theme . '_entries_lists', serialize($ductile_lists));
        $core->blog->settings->themes->put($core->blog->settings->system->theme . '_entries_counts', serialize($ductile_counts));

        // Blog refresh
        $core->blog->triggerBlog();

        // Template cache reset
        $core->emptyTemplatesCache();

        dcPage::message(__('Theme configuration upgraded.'), true, true);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

// Legacy mode
if (!$standalone_config) {
    echo '</form>';
}

# HTML Tab

echo '<div class="multi-part" id="themes-list' . ($conf_tab == 'html' ? '' : '-html') . '" title="' . __('Content') . '">' .
'<h3>' . __('Content') . '</h3>';

echo '<form id="theme_config" action="' . $core->adminurl->get('admin.blog.theme', array('conf' => '1')) .
    '" method="post" enctype="multipart/form-data">';

echo '<h4>' . __('Header') . '</h4>' .
'<p class="field"><label for="subtitle_hidden">' . __('Hide blog description:') . '</label> ' .
form::checkbox('subtitle_hidden', 1, $ductile_user['subtitle_hidden']) . '</p>';
echo '<p class="field"><label for="logo_src">' . __('Logo URL:') . '</label> ' .
form::field('logo_src', 40, 255, $ductile_user['logo_src']) . '</p>';
if ($core->plugins->moduleExists('simpleMenu')) {
    echo '<p>' . sprintf(__('To configure the top menu go to the <a href="%s">Simple Menu administration page</a>.'),
        $core->adminurl->get('admin.plugin.simpleMenu')) . '</p>';
}

echo '<h4 class="border-top pretty-title">' . __('Stickers') . '</h4>';

echo
'<div class="table-outer">' .
'<table class="dragable">' . '<caption>' . __('Stickers (footer)') . '</caption>' .
'<thead>' .
'<tr>' .
'<th scope="col">' . '</th>' .
'<th scope="col">' . __('Image') . '</th>' .
'<th scope="col">' . __('Label') . '</th>' .
'<th scope="col">' . __('URL') . '</th>' .
    '</tr>' .
    '</thead>' .
    '<tbody id="stickerslist">';
$count = 0;
foreach ($ductile_stickers as $i => $v) {
    $count++;
    echo
    '<tr class="line" id="l_' . $i . '">' .
    '<td class="handle minimal">' . form::number(array('order[' . $i . ']'), array(
        'min'     => 0,
        'default' => $count,
        'class'   => 'position'
    )) .
    form::hidden(array('dynorder[]', 'dynorder-' . $i), $i) . '</td>' .
    '<td>' . form::hidden(array('sticker_image[]'), $v['image']) . '<img src="' . $img_url . $v['image'] . '" alt="' . $v['image'] . '" /> ' . '</td>' .
    '<td scope="row">' . form::field(array('sticker_label[]', 'dsl-' . $i), 20, 255, $v['label']) . '</td>' .
    '<td>' . form::field(array('sticker_url[]', 'dsu-' . $i), 40, 255, $v['url']) . '</td>' .
        '</tr>';
}
echo
    '</tbody>' .
    '</table></div>';

echo '<h4 class="border-top pretty-title">' . __('Entries list types and limits') . '</h4>';

echo '<table id="entrieslist">' . '<caption class="hidden">' . __('Entries lists') . '</caption>' .
'<thead>' .
'<tr>' .
'<th scope="col">' . __('Context') . '</th>' .
'<th scope="col">' . __('Entries list type') . '</th>' .
'<th scope="col">' . __('Number of entries') . '</th>' .
    '</tr>' .
    '</thead>' .
    '<tbody>';
foreach ($ductile_lists as $k => $v) {
    echo
    '<tr>' .
    '<td scope="row">' . $contexts[$k] . '</td>' .
    '<td>' . form::hidden(array('list_ctx[]'), $k) . form::combo(array('list_type[]'), $list_types, $v) . '</td>';
    if (array_key_exists($k, $ductile_counts)) {
        echo '<td>' . form::hidden(array('count_ctx[]'), $k) . form::number(array('count_nb[]'), array(
            'min'     => 0,
            'max'     => 999,
            'default' => $ductile_counts[$k]
        )) . '</td>';
    } else {
        echo '<td></td>';
    }
    echo
        '</tr>';
}
echo
    '</tbody>' .
    '</table>';

echo '<h4 class="border-top pretty-title">' . __('Miscellaneous options') . '</h4>';
echo '<p><label for="preview_not_mandatory" class="classic">' . __('Comment preview is not mandatory:') . '</label> ' .
form::checkbox('preview_not_mandatory', 1, $ductile_user['preview_not_mandatory']) . '</p>';

echo '<p><input type="hidden" name="conf_tab" value="html" /></p>';
echo '<p class="clear">' . form::hidden('ds_order', '') . '<input type="submit" value="' . __('Save') . '" />' . $core->formNonce() . '</p>';
echo '</form>';

echo '</div>'; // Close tab

# CSS tab

echo '<div class="multi-part" id="themes-list' . ($conf_tab == 'css' ? '' : '-css') . '" title="' . __('Presentation') . '">';

echo '<form id="theme_config" action="' . $core->adminurl->get('admin.blog.theme', array('conf' => '1')) .
    '" method="post" enctype="multipart/form-data">';

echo '<h3>' . __('General settings') . '</h3>';

echo '<h4 class="pretty-title">' . __('Fonts') . '</h4>';

echo '<div class="two-cols">';
echo '<div class="col">';
echo
'<h5>' . __('Main text') . '</h5>' .
'<p class="field"><label for="body_font">' . __('Main font:') . '</label> ' .
form::combo('body_font', $fonts, $ductile_user['body_font']) .
(!empty($ductile_user['body_font']) ? ' ' . fontDef($ductile_user['body_font']) : '') .
' <span class="form-note">' . __('Set to Default to use a webfont.') . '</span>' .
'</p>' .
'<p class="field"><label for="body_webfont_family">' . __('Webfont family:') . '</label> ' .
form::field('body_webfont_family', 25, 255, $ductile_user['body_webfont_family']) . '</p>' .
'<p class="field"><label for="body_webfont_url">' . __('Webfont URL:') . '</label> ' .
form::url('body_webfont_url', 50, 255, $ductile_user['body_webfont_url']) . '</p>' .
'<p class="field"><label for="body_webfont_url">' . __('Webfont API:') . '</label> ' .
form::combo('body_webfont_api', $webfont_apis, $ductile_user['body_webfont_api']) . '</p>';
echo '</div>';
echo '<div class="col">';
echo
'<h5>' . __('Secondary text') . '</h5>' .
'<p class="field"><label for="alternate_font">' . __('Secondary font:') . '</label> ' .
form::combo('alternate_font', $fonts, $ductile_user['alternate_font']) .
(!empty($ductile_user['alternate_font']) ? ' ' . fontDef($ductile_user['alternate_font']) : '') .
' <span class="form-note">' . __('Set to Default to use a webfont.') . '</span>' .
'</p>' .
'<p class="field"><label for="alternate_webfont_family">' . __('Webfont family:') . '</label> ' .
form::field('alternate_webfont_family', 25, 255, $ductile_user['alternate_webfont_family']) . '</p>' .
'<p class="field"><label for="alternate_webfont_url">' . __('Webfont URL:') . '</label> ' .
form::url('alternate_webfont_url', 50, 255, $ductile_user['alternate_webfont_url']) . '</p>' .
'<p class="field"><label for="alternate_webfont_api">' . __('Webfont API:') . '</label> ' .
form::combo('alternate_webfont_api', $webfont_apis, $ductile_user['alternate_webfont_api']) . '</p>';
echo '</div>';
echo '</div>';

echo '<h4 class="clear border-top pretty-title">' . __('Titles') . '</h4>';
echo '<div class="two-cols">';
echo '<div class="col">';
echo '<h5>' . __('Blog title') . '</h5>' .
'<p class="field"><label for="blog_title_w">' . __('In bold:') . '</label> ' .
form::checkbox('blog_title_w', 1, $ductile_user['blog_title_w']) . '</p>' .

'<p class="field"><label for="blog_title_s">' . __('Font size (in em by default):') . '</label> ' .
form::field('blog_title_s', 7, 7, $ductile_user['blog_title_s']) . '</p>' .

'<p class="field picker"><label for="blog_title_c">' . __('Color:') . '</label> ' .
form::color('blog_title_c', array('default' => $ductile_user['blog_title_c'])) .
dcThemeConfig::contrastRatio($ductile_user['blog_title_c'], '#ffffff',
    (!empty($ductile_user['blog_title_s']) ? $ductile_user['blog_title_s'] : '2em'),
    $ductile_user['blog_title_w']) .
    '</p>';

echo '</div>';
echo '<div class="col">';

echo '<h5>' . __('Post title') . '</h5>' .
'<p class="field"><label for="post_title_w">' . __('In bold:') . '</label> ' .
form::checkbox('post_title_w', 1, $ductile_user['post_title_w']) . '</p>' .

'<p class="field"><label for="post_title_s">' . __('Font size (in em by default):') . '</label> ' .
form::field('post_title_s', 7, 7, $ductile_user['post_title_s']) . '</p>' .

'<p class="field picker"><label for="post_title_c">' . __('Color:') . '</label> ' .
form::color('post_title_c', array('default' => $ductile_user['post_title_c'])) .
dcThemeConfig::contrastRatio($ductile_user['post_title_c'], '#ffffff',
    (!empty($ductile_user['post_title_s']) ? $ductile_user['post_title_s'] : '2.5em'),
    $ductile_user['post_title_w']) .
    '</p>';

echo '</div>';
echo '</div>';

echo '<h5>' . __('Titles without link') . '</h5>' .

'<p class="field picker"><label for="post_simple_title_c">' . __('Color:') . '</label> ' .
form::color('post_simple_title_c', array('default' => $ductile_user['post_simple_title_c'])) .
dcThemeConfig::contrastRatio($ductile_user['post_simple_title_c'], '#ffffff',
    '1.1em', // H5 minimum size
    false) .
    '</p>';

echo '<h4 class="border-top pretty-title">' . __('Inside posts links') . '</h4>' .
'<p class="field"><label for="post_link_w">' . __('In bold:') . '</label> ' .
form::checkbox('post_link_w', 1, $ductile_user['post_link_w']) . '</p>' .

'<p class="field picker"><label for="post_link_v_c">' . __('Normal and visited links color:') . '</label> ' .
form::color('post_link_v_c', array('default' => $ductile_user['post_link_v_c'])) .
dcThemeConfig::contrastRatio($ductile_user['post_link_v_c'], '#ffffff',
    '1em',
    $ductile_user['post_link_w']) .
'</p>' .

'<p class="field picker"><label for="post_link_f_c">' . __('Active, hover and focus links color:') . '</label> ' .
form::color('post_link_f_c', array('default' => $ductile_user['post_link_f_c'])) .
dcThemeConfig::contrastRatio($ductile_user['post_link_f_c'], '#ebebee',
    '1em',
    $ductile_user['post_link_w']) .
    '</p>';

echo '<h3 class="border-top">' . __('Mobile specific settings') . '</h3>';

echo '<div class="two-cols">';
echo '<div class="col">';

echo '<h4 class="pretty-title">' . __('Blog title') . '</h4>' .
'<p class="field"><label for="blog_title_w_m">' . __('In bold:') . '</label> ' .
form::checkbox('blog_title_w_m', 1, $ductile_user['blog_title_w_m']) . '</p>' .

'<p class="field"><label for="blog_title_s_m">' . __('Font size (in em by default):') . '</label> ' .
form::field('blog_title_s_m', 7, 7, $ductile_user['blog_title_s_m']) . '</p>' .

'<p class="field picker"><label for="blog_title_c_m">' . __('Color:') . '</label> ' .
form::color('blog_title_c_m', array('default' => $ductile_user['blog_title_c_m'])) .
dcThemeConfig::contrastRatio($ductile_user['blog_title_c_m'], '#d7d7dc',
    (!empty($ductile_user['blog_title_s_m']) ? $ductile_user['blog_title_s_m'] : '1.8em'),
    $ductile_user['blog_title_w_m']) .
    '</p>';

echo '</div>';
echo '<div class="col">';

echo '<h4 class="pretty-title">' . __('Post title') . '</h4>' .
'<p class="field"><label for="post_title_w_m">' . __('In bold:') . '</label> ' .
form::checkbox('post_title_w_m', 1, $ductile_user['post_title_w_m']) . '</p>' .

'<p class="field"><label for="post_title_s_m">' . __('Font size (in em by default):') . '</label> ' .
form::field('post_title_s_m', 7, 7, $ductile_user['post_title_s_m']) . '</p>' .

'<p class="field picker"><label for="post_title_c_m">' . __('Color:') . '</label> ' .
form::color('post_title_c_m', array('default' => $ductile_user['post_title_c_m'])) .
dcThemeConfig::contrastRatio($ductile_user['post_title_c_m'], '#ffffff',
    (!empty($ductile_user['post_title_s_m']) ? $ductile_user['post_title_s_m'] : '1.5em'),
    $ductile_user['post_title_w_m']) .
    '</p>';

echo '</div>';
echo '</div>';

echo '<p><input type="hidden" name="conf_tab" value="css" /></p>';
echo '<p class="clear border-top"><input type="submit" value="' . __('Save') . '" />' . $core->formNonce() . '</p>';
echo '</form>';

echo '</div>'; // Close tab

dcPage::helpBlock('ductile');

// Legacy mode
if (!$standalone_config) {
    echo '<form style="display:none">';
}
