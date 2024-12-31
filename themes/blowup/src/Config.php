<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\blowup;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemeConfig;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

/**
 * @brief   The module configuration process.
 * @ingroup blowup
 */
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // load locales
        My::l10n('admin');

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        App::backend()->standalone_config = (bool) App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'standalone_config');

        App::backend()->can_write_images = Blowup::canWriteImages();
        App::backend()->can_write_css    = Blowup::canWriteCss();

        $blowup_base = [
            'body_bg_c' => null,
            'body_bg_g' => 'light',

            'body_txt_f'       => null,
            'body_txt_s'       => null,
            'body_txt_c'       => null,
            'body_line_height' => null,

            'top_image'  => 'default',
            'top_height' => null,
            'uploaded'   => null,

            'blog_title_hide' => null,
            'blog_title_f'    => null,
            'blog_title_s'    => null,
            'blog_title_c'    => null,
            'blog_title_a'    => null,
            'blog_title_p'    => null,

            'body_link_c'   => null,
            'body_link_f_c' => null,
            'body_link_v_c' => null,

            'sidebar_position' => null,
            'sidebar_text_f'   => null,
            'sidebar_text_s'   => null,
            'sidebar_text_c'   => null,
            'sidebar_title_f'  => null,
            'sidebar_title_s'  => null,
            'sidebar_title_c'  => null,
            'sidebar_title2_f' => null,
            'sidebar_title2_s' => null,
            'sidebar_title2_c' => null,
            'sidebar_line_c'   => null,
            'sidebar_link_c'   => null,
            'sidebar_link_f_c' => null,
            'sidebar_link_v_c' => null,

            'date_title_f' => null,
            'date_title_s' => null,
            'date_title_c' => null,

            'post_title_f'        => null,
            'post_title_s'        => null,
            'post_title_c'        => null,
            'post_comment_bg_c'   => null,
            'post_comment_c'      => null,
            'post_commentmy_bg_c' => null,
            'post_commentmy_c'    => null,

            'prelude_c'   => null,
            'footer_f'    => null,
            'footer_s'    => null,
            'footer_c'    => null,
            'footer_l_c'  => null,
            'footer_bg_c' => null,

            'extra_css' => null,
        ];

        $blowup_user = App::blog()->settings()->themes->blowup_style;

        if ($blowup_user) {
            $blowup_user = @unserialize($blowup_user);
        }
        if (!$blowup_user || !is_array($blowup_user)) {
            $blowup_user = [];
        }

        App::backend()->blowup_user = [...$blowup_base, ...$blowup_user];

        App::backend()->gradient_types = [
            __('Light linear gradient')  => 'light',
            __('Medium linear gradient') => 'medium',
            __('Dark linear gradient')   => 'dark',
            __('Solid color')            => 'solid',
        ];

        App::backend()->top_images = array_merge([__('Custom...') => 'custom'], array_flip(Blowup::$top_images));

        if ($_POST !== []) {
            try {
                $blowup_user = App::backend()->blowup_user;

                $blowup_user['body_txt_f']       = $_POST['body_txt_f'];
                $blowup_user['body_txt_s']       = ThemeConfig::adjustFontSize($_POST['body_txt_s']);
                $blowup_user['body_txt_c']       = ThemeConfig::adjustColor($_POST['body_txt_c']);
                $blowup_user['body_line_height'] = ThemeConfig::adjustFontSize($_POST['body_line_height']);

                $blowup_user['blog_title_hide'] = (int) !empty($_POST['blog_title_hide']);
                $update_blog_title              = !$blowup_user['blog_title_hide'] && (
                    !empty($_POST['blog_title_f']) || !empty($_POST['blog_title_s']) || !empty($_POST['blog_title_c']) || !empty($_POST['blog_title_a']) || !empty($_POST['blog_title_p'])
                );

                if ($update_blog_title) {
                    $blowup_user['blog_title_f'] = $_POST['blog_title_f'];
                    $blowup_user['blog_title_s'] = ThemeConfig::adjustFontSize($_POST['blog_title_s']);
                    $blowup_user['blog_title_c'] = ThemeConfig::adjustColor($_POST['blog_title_c']);
                    $blowup_user['blog_title_a'] = preg_match('/^(left|center|right)$/', ($_POST['blog_title_a'] ?? '')) ? $_POST['blog_title_a'] : null;
                    $blowup_user['blog_title_p'] = ThemeConfig::adjustPosition($_POST['blog_title_p']);
                }

                $blowup_user['body_link_c']   = ThemeConfig::adjustColor($_POST['body_link_c']);
                $blowup_user['body_link_f_c'] = ThemeConfig::adjustColor($_POST['body_link_f_c']);
                $blowup_user['body_link_v_c'] = ThemeConfig::adjustColor($_POST['body_link_v_c']);

                $blowup_user['sidebar_text_f']   = ($_POST['sidebar_text_f'] ?? null);
                $blowup_user['sidebar_text_s']   = ThemeConfig::adjustFontSize($_POST['sidebar_text_s']);
                $blowup_user['sidebar_text_c']   = ThemeConfig::adjustColor($_POST['sidebar_text_c']);
                $blowup_user['sidebar_title_f']  = ($_POST['sidebar_title_f'] ?? null);
                $blowup_user['sidebar_title_s']  = ThemeConfig::adjustFontSize($_POST['sidebar_title_s']);
                $blowup_user['sidebar_title_c']  = ThemeConfig::adjustColor($_POST['sidebar_title_c']);
                $blowup_user['sidebar_title2_f'] = ($_POST['sidebar_title2_f'] ?? null);
                $blowup_user['sidebar_title2_s'] = ThemeConfig::adjustFontSize($_POST['sidebar_title2_s']);
                $blowup_user['sidebar_title2_c'] = ThemeConfig::adjustColor($_POST['sidebar_title2_c']);
                $blowup_user['sidebar_line_c']   = ThemeConfig::adjustColor($_POST['sidebar_line_c']);
                $blowup_user['sidebar_link_c']   = ThemeConfig::adjustColor($_POST['sidebar_link_c']);
                $blowup_user['sidebar_link_f_c'] = ThemeConfig::adjustColor($_POST['sidebar_link_f_c']);
                $blowup_user['sidebar_link_v_c'] = ThemeConfig::adjustColor($_POST['sidebar_link_v_c']);

                $blowup_user['sidebar_position'] = ($_POST['sidebar_position'] ?? '') == 'left' ? 'left' : null;

                $blowup_user['date_title_f'] = ($_POST['date_title_f'] ?? null);
                $blowup_user['date_title_s'] = ThemeConfig::adjustFontSize($_POST['date_title_s']);
                $blowup_user['date_title_c'] = ThemeConfig::adjustColor($_POST['date_title_c']);

                $blowup_user['post_title_f']     = ($_POST['post_title_f'] ?? null);
                $blowup_user['post_title_s']     = ThemeConfig::adjustFontSize($_POST['post_title_s']);
                $blowup_user['post_title_c']     = ThemeConfig::adjustColor($_POST['post_title_c']);
                $blowup_user['post_comment_c']   = ThemeConfig::adjustColor($_POST['post_comment_c']);
                $blowup_user['post_commentmy_c'] = ThemeConfig::adjustColor($_POST['post_commentmy_c']);

                $blowup_user['footer_f']    = ($_POST['footer_f'] ?? null);
                $blowup_user['footer_s']    = ThemeConfig::adjustFontSize($_POST['footer_s']);
                $blowup_user['footer_c']    = ThemeConfig::adjustColor($_POST['footer_c']);
                $blowup_user['footer_l_c']  = ThemeConfig::adjustColor($_POST['footer_l_c']);
                $blowup_user['footer_bg_c'] = ThemeConfig::adjustColor($_POST['footer_bg_c']);

                $blowup_user['extra_css'] = ThemeConfig::cleanCSS($_POST['extra_css']);

                if (App::backend()->can_write_images) {
                    $uploaded = null;

                    if ($blowup_user['uploaded'] && is_file(Blowup::imagesPath() . '/' . $blowup_user['uploaded'])) {
                        $uploaded = Blowup::imagesPath() . '/' . $blowup_user['uploaded'];
                    }

                    if (!empty($_FILES['upfile']) && !empty($_FILES['upfile']['name'])) {
                        Files::uploadStatus($_FILES['upfile']);
                        $uploaded                = Blowup::uploadImage($_FILES['upfile']);
                        $blowup_user['uploaded'] = basename($uploaded);
                    }

                    $blowup_user['top_image'] = in_array(($_POST['top_image'] ?? ''), App::backend()->top_images) ?
                        $_POST['top_image'] :
                        'default';

                    $blowup_user['body_bg_c'] = ThemeConfig::adjustColor($_POST['body_bg_c']);
                    $blowup_user['body_bg_g'] = in_array(($_POST['body_bg_g'] ?? ''), App::backend()->gradient_types) ?
                        $_POST['body_bg_g'] :
                        '';

                    $blowup_user['post_comment_bg_c']   = ThemeConfig::adjustColor($_POST['post_comment_bg_c']);
                    $blowup_user['post_commentmy_bg_c'] = ThemeConfig::adjustColor($_POST['post_commentmy_bg_c']);

                    $blowup_user['prelude_c'] = ThemeConfig::adjustColor($_POST['prelude_c']);

                    Blowup::createImages($blowup_user, $uploaded);
                }

                if (App::backend()->can_write_css) {
                    Blowup::createCss($blowup_user);
                }

                App::blog()->settings()->themes->put('blowup_style', serialize($blowup_user));
                App::blog()->triggerBlog();

                App::backend()->blowup_user = $blowup_user;

                Notices::addSuccessNotice(__('Theme configuration has been successfully updated.'));
                App::backend()->url()->redirect('admin.blog.theme', ['conf' => '1']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Legacy mode
        if (!App::backend()->standalone_config) {
            echo '</form>';
        }

        echo
        '<p><a class="back" href="' . App::backend()->url()->get('admin.blog.theme') . '">' . __('Back to Blog appearance') . '</a></p>' .

        '<form id="theme_config" action="' . App::backend()->url()->get('admin.blog.theme', ['conf' => '1']) . '" method="post" enctype="multipart/form-data">' .

        '<div class="fieldset"><h3>' . __('Customization') . '</h3>' .
        '<h4>' . __('General') . '</h4>';

        if (App::backend()->can_write_images) {
            echo
            '<p class="field"><label for="body_bg_c">' . __('Background color:') . '</label> ' .
            form::color('body_bg_c', ['default' => App::backend()->blowup_user['body_bg_c']]) . '</p>' .

            '<p class="field"><label for="body_bg_g">' . __('Background color fill:') . '</label> ' .
            form::combo('body_bg_g', App::backend()->gradient_types, App::backend()->blowup_user['body_bg_g']) . '</p>';
        }

        echo
        '<p class="field"><label for="body_txt_f">' . __('Main text font:') . '</label> ' .
        form::combo('body_txt_f', Blowup::fontsList(), App::backend()->blowup_user['body_txt_f']) . '</p>' .

        '<p class="field"><label for="body_txt_s">' . __('Main text font size:') . '</label> ' .
        form::field('body_txt_s', 7, 7, App::backend()->blowup_user['body_txt_s']) . '</p>' .

        '<p class="field"><label for="body_txt_c">' . __('Main text color:') . '</label> ' .
        form::color('body_txt_c', ['default' => App::backend()->blowup_user['body_txt_c']]) . '</p>' .

        '<p class="field"><label for="body_line_height">' . __('Text line height:') . '</label> ' .
        form::field('body_line_height', 7, 7, App::backend()->blowup_user['body_line_height']) . '</p>' .

        '<h4 class="border-top">' . __('Links') . '</h4>' .
        '<p class="field"><label for="body_link_c">' . __('Links color:') . '</label> ' .
        form::color('body_link_c', ['default' => App::backend()->blowup_user['body_link_c']]) . '</p>' .

        '<p class="field"><label for="body_link_v_c">' . __('Visited links color:') . '</label> ' .
        form::color('body_link_v_c', ['default' => App::backend()->blowup_user['body_link_v_c']]) . '</p>' .

        '<p class="field"><label for="body_link_f_c">' . __('Focus links color:') . '</label> ' .
        form::color('body_link_f_c', ['default' => App::backend()->blowup_user['body_link_f_c']]) . '</p>' .

        '<h4 class="border-top">' . __('Page top') . '</h4>';

        if (App::backend()->can_write_images) {
            echo
            '<p class="field"><label for="prelude_c">' . __('Prelude color:') . '</label> ' .
            form::color('prelude_c', ['default' => App::backend()->blowup_user['prelude_c']]) . '</p>';
        }

        echo
        '<p class="field"><label for="blog_title_hide">' . __('Hide main title') . '</label> ' .
        form::checkbox('blog_title_hide', 1, App::backend()->blowup_user['blog_title_hide']) . '</p>' .

        '<p class="field"><label for="blog_title_f">' . __('Main title font:') . '</label> ' .
        form::combo('blog_title_f', Blowup::fontsList(), App::backend()->blowup_user['blog_title_f']) . '</p>' .

        '<p class="field"><label for="blog_title_s">' . __('Main title font size:') . '</label> ' .
        form::field('blog_title_s', 7, 7, App::backend()->blowup_user['blog_title_s']) . '</p>' .

        '<p class="field"><label for="blog_title_c">' . __('Main title color:') . '</label> ' .
        form::color('blog_title_c', ['default' => App::backend()->blowup_user['blog_title_c']]) . '</p>' .

        '<p class="field"><label for="blog_title_a">' . __('Main title alignment:') . '</label> ' .
        form::combo('blog_title_a', [__('center') => 'center', __('left') => 'left', __('right') => 'right'], App::backend()->blowup_user['blog_title_a']) . '</p>' .

        '<p class="field"><label for="blog_title_p">' . __('Main title position (x:y)') . '</label> ' .
        form::field('blog_title_p', 7, 7, App::backend()->blowup_user['blog_title_p']) . '</p>';

        if (App::backend()->can_write_images) {
            if (App::backend()->blowup_user['top_image'] == 'custom' && App::backend()->blowup_user['uploaded']) {
                $preview_image = Http::concatURL(App::blog()->url(), Blowup::imagesURL() . '/page-t.png');
            } else {
                $preview_image = Blowup::themeURL() . '/alpha-img/page-t/' . App::backend()->blowup_user['top_image'] . '.png';
            }

            echo
            '<h5 class="pretty-title">' . __('Top image') . '</h5>' .
            '<p class="field"><label for="top_image">' . __('Top image') . '</label> ' .
            form::combo('top_image', App::backend()->top_images, (App::backend()->blowup_user['top_image'] ?: 'default')) . '</p>' .
            '<p>' . __('Choose "Custom..." to upload your own image.') . '</p>' .

            '<p id="uploader"><label for="upfile">' . __('Add your image:') . '</label> ' .
            ' (' . sprintf(__('JPEG or PNG file, 800 pixels wide, maximum size %s'), Files::size(App::config()->maxUploadSize())) . ')' .
            '<input type="file" name="upfile" id="upfile" size="35">' .
            '</p>' .

            '<h5>' . __('Preview') . '</h5>' .
            '<div class="grid" style="width:800px;border:1px solid #ccc;">' .
            '<img style="display:block;" src="' . $preview_image . '" alt="" id="image-preview">' .
            '</div>';
        }

        echo
        '<h4 class="border-top">' . __('Sidebar') . '</h4>' .
        '<p class="field"><label for="sidebar_position">' . __('Sidebar position:') . '</label> ' .
        form::combo('sidebar_position', [__('right') => 'right', __('left') => 'left'], App::backend()->blowup_user['sidebar_position']) . '</p>' .

        '<p class="field"><label for="sidebar_text_f">' . __('Sidebar text font:') . '</label> ' .
        form::combo('sidebar_text_f', Blowup::fontsList(), App::backend()->blowup_user['sidebar_text_f']) . '</p>' .

        '<p class="field"><label for="sidebar_text_s">' . __('Sidebar text font size:') . '</label> ' .
        form::field('sidebar_text_s', 7, 7, App::backend()->blowup_user['sidebar_text_s']) . '</p>' .

        '<p class="field"><label for="sidebar_text_c">' . __('Sidebar text color:') . '</label> ' .
        form::color('sidebar_text_c', ['default' => App::backend()->blowup_user['sidebar_text_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_title_f">' . __('Sidebar titles font:') . '</label> ' .
        form::combo('sidebar_title_f', Blowup::fontsList(), App::backend()->blowup_user['sidebar_title_f']) . '</p>' .

        '<p class="field"><label for="sidebar_title_s">' . __('Sidebar titles font size:') . '</label> ' .
        form::field('sidebar_title_s', 7, 7, App::backend()->blowup_user['sidebar_title_s']) . '</p>' .

        '<p class="field"><label for="sidebar_title_c">' . __('Sidebar titles color:') . '</label> ' .
        form::color('sidebar_title_c', ['default' => App::backend()->blowup_user['sidebar_title_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_title2_f">' . __('Sidebar 2nd level titles font:') . '</label> ' .
        form::combo('sidebar_title2_f', Blowup::fontsList(), App::backend()->blowup_user['sidebar_title2_f']) . '</p>' .

        '<p class="field"><label for="sidebar_title2_s">' . __('Sidebar 2nd level titles font size:') . '</label> ' .
        form::field('sidebar_title2_s', 7, 7, App::backend()->blowup_user['sidebar_title2_s']) . '</p>' .

        '<p class="field"><label for="sidebar_title2_c">' . __('Sidebar 2nd level titles color:') . '</label> ' .
        form::color('sidebar_title2_c', ['default' => App::backend()->blowup_user['sidebar_title2_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_line_c">' . __('Sidebar lines color:') . '</label> ' .
        form::color('sidebar_line_c', ['default' => App::backend()->blowup_user['sidebar_line_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_link_c">' . __('Sidebar links color:') . '</label> ' .
        form::color('sidebar_link_c', ['default' => App::backend()->blowup_user['sidebar_link_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_link_v_c">' . __('Sidebar visited links color:') . '</label> ' .
        form::color('sidebar_link_v_c', ['default' => App::backend()->blowup_user['sidebar_link_v_c']]) . '</p>' .

        '<p class="field"><label for="sidebar_link_f_c">' . __('Sidebar focus links color:') . '</label> ' .
        form::color('sidebar_link_f_c', ['default' => App::backend()->blowup_user['sidebar_link_f_c']]) . '</p>' .

        '<h4 class="border-top">' . __('Entries') . '</h4>' .
        '<p class="field"><label for="date_title_f">' . __('Date title font:') . '</label> ' .
        form::combo('date_title_f', Blowup::fontsList(), App::backend()->blowup_user['date_title_f']) . '</p>' .

        '<p class="field"><label for="date_title_s">' . __('Date title font size:') . '</label> ' .
        form::field('date_title_s', 7, 7, App::backend()->blowup_user['date_title_s']) . '</p>' .

        '<p class="field"><label for="date_title_c">' . __('Date title color:') . '</label> ' .
        form::color('date_title_c', ['default' => App::backend()->blowup_user['date_title_c']]) . '</p>' .

        '<p class="field"><label for="post_title_f">' . __('Entry title font:') . '</label> ' .
        form::combo('post_title_f', Blowup::fontsList(), App::backend()->blowup_user['post_title_f']) . '</p>' .

        '<p class="field"><label for="post_title_s">' . __('Entry title font size:') . '</label> ' .
        form::field('post_title_s', 7, 7, App::backend()->blowup_user['post_title_s']) . '</p>' .

        '<p class="field"><label for="post_title_c">' . __('Entry title color:') . '</label> ' .
        form::color('post_title_c', ['default' => App::backend()->blowup_user['post_title_c']]) . '</p>';

        if (App::backend()->can_write_images) {
            echo
            '<p class="field"><label for="post_comment_bg_c">' . __('Comment background color:') . '</label> ' .
            form::color('post_comment_bg_c', ['default' => App::backend()->blowup_user['post_comment_bg_c']]) . '</p>';
        }

        echo
        '<p class="field"><label for="post_comment_c">' . __('Comment text color:') . '</label> ' .
        form::color('post_comment_c', ['default' => App::backend()->blowup_user['post_comment_c']]) . '</p>';

        if (App::backend()->can_write_images) {
            echo
            '<p class="field"><label for="post_commentmy_bg_c">' . __('My comment background color:') . '</label> ' .
            form::color('post_commentmy_bg_c', ['default' => App::backend()->blowup_user['post_commentmy_bg_c']]) . '</p>';
        }

        echo
        '<p class="field"><label for="post_commentmy_c">' . __('My comment text color:') . '</label> ' .
        form::color('post_commentmy_c', ['default' => App::backend()->blowup_user['post_commentmy_c']]) . '</p>' .

        '<h4 class="border-top">' . __('Footer') . '</h4>' .
        '<p class="field"><label for="footer_f">' . __('Footer font:') . '</label> ' .
        form::combo('footer_f', Blowup::fontsList(), App::backend()->blowup_user['footer_f']) . '</p>' .

        '<p class="field"><label for="footer_s">' . __('Footer font size:') . '</label> ' .
        form::field('footer_s', 7, 7, App::backend()->blowup_user['footer_s']) . '</p>' .

        '<p class="field"><label for="footer_c">' . __('Footer color:') . '</label> ' .
        form::color('footer_c', ['default' => App::backend()->blowup_user['footer_c']]) . '</p>' .

        '<p class="field"><label for="footer_l_c">' . __('Footer links color:') . '</label> ' .
        form::color('footer_l_c', ['default' => App::backend()->blowup_user['footer_l_c']]) . '</p>' .

        '<p class="field"><label for="footer_bg_c">' . __('Footer background color:') . '</label> ' .
        form::color('footer_bg_c', ['default' => App::backend()->blowup_user['footer_bg_c']]) . '</p>' .

        '<h4 class="border-top">' . __('Additional CSS') . '</h4>' .
        '<p><label for="extra_css">' . __('Any additional CSS styles (must be written using the CSS syntax):') . '</label> ' .
        form::textarea('extra_css', 72, 5, [
            'default'    => Html::escapeHTML(App::backend()->blowup_user['extra_css']),
            'class'      => 'maximal',
            'extra_html' => 'title="' . __('Additional CSS') . '"',
        ]) .
        '</p>' .
        '</div>';

        // Import / Export configuration
        $tmp_array   = [];
        $tmp_exclude = ['uploaded', 'top_height'];
        if (App::backend()->blowup_user['top_image'] == 'custom') {
            $tmp_exclude[] = 'top_image';
        }
        foreach (App::backend()->blowup_user as $k => $v) {
            if (!in_array($k, $tmp_exclude)) {
                $tmp_array[] = $k . ':' . '"' . $v . '"';
            }
        }
        echo
        '<div class="fieldset">' .
        '<h3 id="bu_export">' . __('Configuration import / export') . '</h3>' .
        '<div id="bu_export_content">' .
        '<p>' . __('You can share your configuration using the following code. To apply a configuration, paste the code, click on "Apply code" and save.') . '</p>' .
        '<p>' . form::textarea('export_code', 72, 5, [
            'default'    => implode('; ', $tmp_array),
            'class'      => 'maximal',
            'extra_html' => 'title="' . __('Copy this code:') . '"',
        ]) . '</p>' .
        '</div>' .
        '</div>' .

        '<p class="clear"><input type="submit" value="' . __('Save') . '">' .
        App::nonce()->getFormNonce() .
        '</p>' .
        '</form>';

        Page::helpBlock('blowupConfig');

        // Legacy mode
        if (!App::backend()->standalone_config) {
            echo '<form style="display:none">';
        }
    }
}
