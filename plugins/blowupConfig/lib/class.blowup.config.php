<?php
/**
 * @brief blowupConfig, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class blowupConfig
{
    protected static $css_folder = 'blowup-css';
    protected static $img_folder = 'blowup-images';

    protected static $fonts = array(
        'sans-serif' => array(
            'ss1' => 'Arial, Helvetica, sans-serif',
            'ss2' => 'Verdana,Geneva, Arial, Helvetica, sans-serif',
            'ss3' => '"Lucida Grande", "Lucida Sans Unicode", sans-serif',
            'ss4' => '"Trebuchet MS", Helvetica, sans-serif',
            'ss5' => 'Impact, Charcoal, sans-serif'
        ),

        'serif'      => array(
            's1' => 'Times, "Times New Roman", serif',
            's2' => 'Georgia, serif',
            's3' => 'Baskerville, "Palatino Linotype", serif'
        ),

        'monospace'  => array(
            'm1' => '"Andale Mono", "Courier New", monospace',
            'm2' => '"Courier New", Courier, mono, monospace'
        )
    );

    protected static $fonts_combo = array();
    protected static $fonts_list  = array();

    public static $top_images = array(
        'default'        => 'Default',
        'blank'          => 'Blank',
        'light-trails-1' => 'Light Trails 1',
        'light-trails-2' => 'Light Trails 2',
        'light-trails-3' => 'Light Trails 3',
        'light-trails-4' => 'Light Trails 4',
        'butterflies'    => 'Butterflies',
        'flourish-1'     => 'Flourished 1',
        'flourish-2'     => 'Flourished 2',
        'animals'        => 'Animals',
        'plumetis'       => 'Plumetis',
        'flamingo'       => 'Flamingo',
        'rabbit'         => 'Rabbit',
        'roadrunner-1'   => 'Road Runner 1',
        'roadrunner-2'   => 'Road Runner 2',
        'typo'           => 'Typo'
    );

    public static function fontsList()
    {
        if (empty(self::$fonts_combo)) {
            self::$fonts_combo[__('default')] = '';
            foreach (self::$fonts as $family => $g) {
                $fonts = array();
                foreach ($g as $code => $font) {
                    $fonts[str_replace('"', '', $font)] = $code;
                }
                self::$fonts_combo[$family] = $fonts;
            }
        }

        return self::$fonts_combo;
    }

    public static function fontDef($c)
    {
        if (empty(self::$fonts_list)) {
            foreach (self::$fonts as $family => $g) {
                foreach ($g as $code => $font) {
                    self::$fonts_list[$code] = $font;
                }
            }
        }

        return isset(self::$fonts_list[$c]) ? self::$fonts_list[$c] : null;
    }

    public static function cssPath()
    {
        return dcThemeConfig::cssPath(self::$css_folder);
    }

    public static function cssURL()
    {
        return dcThemeConfig::cssURL(self::$css_folder);
    }

    public static function canWriteCss($create = false)
    {
        return dcThemeConfig::canWriteCss(self::$css_folder, $create);
    }

    protected static function backgroundImg(&$css, $selector, $value, $image)
    {
        dcThemeConfig::backgroundImg(self::$img_folder, $css, $selector, $value, $image);
    }

    private static function writeCss($theme, $css)
    {
        dcThemeConfig::writeCSS(self::$css_folder, $theme, $css);
    }

    public static function dropCss($theme)
    {
        dcThemeConfig::dropCss(self::$css_folder, $theme);
    }

    public static function publicCssUrlHelper()
    {
        return dcThemeConfig::publicCssUrlHelper(self::$css_folder);
    }

    public static function imagesPath()
    {
        return dcThemeConfig::imagesPath(self::$img_folder);
    }

    public static function imagesURL()
    {
        return dcThemeConfig::imagesURL(self::$img_folder);
    }

    public static function canWriteImages($create = false)
    {
        return dcThemeConfig::canWriteImages(self::$img_folder, $create);
    }

    public static function uploadImage($f)
    {
        return dcThemeConfig::uploadImage(self::$img_folder, $f, 800);
    }

    public static function dropImage($img)
    {
        dcThemeConfig::dropImage(self::$img_folder, $img);
    }

    public static function createCss($s)
    {
        global $core;

        if ($s === null) {
            return;
        }

        $css = array();

        /* Sidebar position
        ---------------------------------------------- */
        if ($s['sidebar_position'] == 'left') {
            $css['#wrapper']['background-position'] = '-300px 0';
            $css['#main']['float']                  = 'right';
            $css['#sidebar']['float']               = 'left';
        }

        /* Properties
        ---------------------------------------------- */
        dcThemeConfig::prop($css, 'body', 'background-color', $s['body_bg_c']);

        dcThemeConfig::prop($css, 'body', 'color', $s['body_txt_c']);
        dcThemeConfig::prop($css, '.post-tags li a:link, .post-tags li a:visited, .post-info-co a:link, .post-info-co a:visited', 'color', $s['body_txt_c']);
        dcThemeConfig::prop($css, '#page', 'font-size', $s['body_txt_s']);
        dcThemeConfig::prop($css, 'body', 'font-family', self::fontDef($s['body_txt_f']));

        dcThemeConfig::prop($css, '.post-content, .post-excerpt, #comments dd, #pings dd, dd.comment-preview', 'line-height', $s['body_line_height']);

        if (!$s['blog_title_hide']) {
            dcThemeConfig::prop($css, '#top h1 a', 'color', $s['blog_title_c']);
            dcThemeConfig::prop($css, '#top h1', 'font-size', $s['blog_title_s']);
            dcThemeConfig::prop($css, '#top h1', 'font-family', self::fontDef($s['blog_title_f']));

            if ($s['blog_title_a'] == 'right' || $s['blog_title_a'] == 'left') {
                $css['#top h1'][$s['blog_title_a']] = '0px';
                $css['#top h1']['width']            = 'auto';
            }

            if ($s['blog_title_p']) {
                $_p                    = explode(':', $s['blog_title_p']);
                $css['#top h1']['top'] = $_p[1] . 'px';
                if ($s['blog_title_a'] != 'center') {
                    $_a                  = $s['blog_title_a'] == 'right' ? 'right' : 'left';
                    $css['#top h1'][$_a] = $_p[0] . 'px';
                }
            }
        } else {
            dcThemeConfig::prop($css, '#top h1 span', 'text-indent', '-5000px');
            dcThemeConfig::prop($css, '#top h1', 'top', '0px');
            $css['#top h1 a'] = array(
                'display' => 'block',
                'height'  => $s['top_height'] ? ($s['top_height'] - 10) . 'px' : '120px',
                'width'   => '800px'
            );
        }
        dcThemeConfig::prop($css, '#top', 'height', $s['top_height']);

        dcThemeConfig::prop($css, '.day-date', 'color', $s['date_title_c']);
        dcThemeConfig::prop($css, '.day-date', 'font-family', self::fontDef($s['date_title_f']));
        dcThemeConfig::prop($css, '.day-date', 'font-size', $s['date_title_s']);

        dcThemeConfig::prop($css, 'a', 'color', $s['body_link_c']);
        dcThemeConfig::prop($css, 'a:visited', 'color', $s['body_link_v_c']);
        dcThemeConfig::prop($css, 'a:hover, a:focus, a:active', 'color', $s['body_link_f_c']);

        dcThemeConfig::prop($css, '#comment-form input, #comment-form textarea', 'color', $s['body_link_c']);
        dcThemeConfig::prop($css, '#comment-form input.preview', 'color', $s['body_link_c']);
        dcThemeConfig::prop($css, '#comment-form input.preview:hover', 'background', $s['body_link_f_c']);
        dcThemeConfig::prop($css, '#comment-form input.preview:hover', 'border-color', $s['body_link_f_c']);
        dcThemeConfig::prop($css, '#comment-form input.submit', 'color', $s['body_link_c']);
        dcThemeConfig::prop($css, '#comment-form input.submit:hover', 'background', $s['body_link_f_c']);
        dcThemeConfig::prop($css, '#comment-form input.submit:hover', 'border-color', $s['body_link_f_c']);

        dcThemeConfig::prop($css, '#sidebar', 'font-family', self::fontDef($s['sidebar_text_f']));
        dcThemeConfig::prop($css, '#sidebar', 'font-size', $s['sidebar_text_s']);
        dcThemeConfig::prop($css, '#sidebar', 'color', $s['sidebar_text_c']);

        dcThemeConfig::prop($css, '#sidebar h2', 'font-family', self::fontDef($s['sidebar_title_f']));
        dcThemeConfig::prop($css, '#sidebar h2', 'font-size', $s['sidebar_title_s']);
        dcThemeConfig::prop($css, '#sidebar h2', 'color', $s['sidebar_title_c']);

        dcThemeConfig::prop($css, '#sidebar h3', 'font-family', self::fontDef($s['sidebar_title2_f']));
        dcThemeConfig::prop($css, '#sidebar h3', 'font-size', $s['sidebar_title2_s']);
        dcThemeConfig::prop($css, '#sidebar h3', 'color', $s['sidebar_title2_c']);

        dcThemeConfig::prop($css, '#sidebar ul', 'border-top-color', $s['sidebar_line_c']);
        dcThemeConfig::prop($css, '#sidebar li', 'border-bottom-color', $s['sidebar_line_c']);
        dcThemeConfig::prop($css, '#topnav ul', 'border-bottom-color', $s['sidebar_line_c']);

        dcThemeConfig::prop($css, '#sidebar li a', 'color', $s['sidebar_link_c']);
        dcThemeConfig::prop($css, '#sidebar li a:visited', 'color', $s['sidebar_link_v_c']);
        dcThemeConfig::prop($css, '#sidebar li a:hover, #sidebar li a:focus, #sidebar li a:active', 'color', $s['sidebar_link_f_c']);
        dcThemeConfig::prop($css, '#search input', 'color', $s['sidebar_link_c']);
        dcThemeConfig::prop($css, '#search .submit', 'color', $s['sidebar_link_c']);
        dcThemeConfig::prop($css, '#search .submit:hover', 'background', $s['sidebar_link_f_c']);
        dcThemeConfig::prop($css, '#search .submit:hover', 'border-color', $s['sidebar_link_f_c']);

        dcThemeConfig::prop($css, '.post-title', 'color', $s['post_title_c']);
        dcThemeConfig::prop($css, '.post-title a, .post-title a:visited', 'color', $s['post_title_c']);
        dcThemeConfig::prop($css, '.post-title', 'font-family', self::fontDef($s['post_title_f']));
        dcThemeConfig::prop($css, '.post-title', 'font-size', $s['post_title_s']);

        dcThemeConfig::prop($css, '#comments dd', 'background-color', $s['post_comment_bg_c']);
        dcThemeConfig::prop($css, '#comments dd', 'color', $s['post_comment_c']);
        dcThemeConfig::prop($css, '#comments dd.me', 'background-color', $s['post_commentmy_bg_c']);
        dcThemeConfig::prop($css, '#comments dd.me', 'color', $s['post_commentmy_c']);

        dcThemeConfig::prop($css, '#prelude, #prelude a', 'color', $s['prelude_c']);

        dcThemeConfig::prop($css, '#footer p', 'background-color', $s['footer_bg_c']);
        dcThemeConfig::prop($css, '#footer p', 'color', $s['footer_c']);
        dcThemeConfig::prop($css, '#footer p', 'font-size', $s['footer_s']);
        dcThemeConfig::prop($css, '#footer p', 'font-family', self::fontDef($s['footer_f']));
        dcThemeConfig::prop($css, '#footer p a', 'color', $s['footer_l_c']);

        /* Images
        ------------------------------------------------------ */
        self::backgroundImg($css, 'body', $s['body_bg_c'], 'body-bg.png');
        self::backgroundImg($css, 'body', $s['body_bg_g'] != 'light', 'body-bg.png');
        self::backgroundImg($css, 'body', $s['prelude_c'], 'body-bg.png');
        self::backgroundImg($css, '#top', $s['body_bg_c'], 'page-t.png');
        self::backgroundImg($css, '#top', $s['body_bg_g'] != 'light', 'page-t.png');
        self::backgroundImg($css, '#top', $s['uploaded'] || $s['top_image'], 'page-t.png');
        self::backgroundImg($css, '#footer', $s['body_bg_c'], 'page-b.png');
        self::backgroundImg($css, '#comments dt', $s['post_comment_bg_c'], 'comment-t.png');
        self::backgroundImg($css, '#comments dd', $s['post_comment_bg_c'], 'comment-b.png');
        self::backgroundImg($css, '#comments dt.me', $s['post_commentmy_bg_c'], 'commentmy-t.png');
        self::backgroundImg($css, '#comments dd.me', $s['post_commentmy_bg_c'], 'commentmy-b.png');

        $res = '';
        foreach ($css as $selector => $values) {
            $res .= $selector . " {\n";
            foreach ($values as $k => $v) {
                $res .= $k . ':' . $v . ";\n";
            }
            $res .= "}\n";
        }

        $res .= $s['extra_css'];

        if (!self::canWriteCss(true)) {
            throw new Exception(__('Unable to create css file.'));
        }

        # erase old css file
        self::dropCss($core->blog->settings->system->theme);

        # create new css file into public blowup-css subdirectory
        self::writeCss($core->blog->settings->system->theme, $res);

        return $res;
    }

    public static function createImages(&$config, $uploaded)
    {
        $body_color       = $config['body_bg_c'];
        $prelude_color    = $config['prelude_c'];
        $gradient         = $config['body_bg_g'];
        $comment_color    = $config['post_comment_bg_c'];
        $comment_color_my = $config['post_commentmy_bg_c'];
        $top_image        = $config['top_image'];

        $config['top_height'] = null;

        if ($top_image != 'custom' && !isset(self::$top_images[$top_image])) {
            $top_image = 'default';
        }
        if ($uploaded && !is_file($uploaded)) {
            $uploaded = null;
        }

        if (!self::canWriteImages(true)) {
            throw new Exception(__('Unable to create images.'));
        }

        $body_fill = array(
            'light'  => dirname(__FILE__) . '/../alpha-img/gradient-l.png',
            'medium' => dirname(__FILE__) . '/../alpha-img/gradient-m.png',
            'dark'   => dirname(__FILE__) . '/../alpha-img/gradient-d.png'
        );

        $body_g = isset($body_fill[$gradient]) ? $body_fill[$gradient] : false;

        if ($top_image == 'custom' && $uploaded) {
            $page_t = $uploaded;
        } else {
            $page_t = dirname(__FILE__) . '/../alpha-img/page-t/' . $top_image . '.png';
        }

        $body_bg         = dirname(__FILE__) . '/../alpha-img/body-bg.png';
        $page_t_mask     = dirname(__FILE__) . '/../alpha-img/page-t/image-mask.png';
        $page_b          = dirname(__FILE__) . '/../alpha-img/page-b.png';
        $comment_t       = dirname(__FILE__) . '/../alpha-img/comment-t.png';
        $comment_b       = dirname(__FILE__) . '/../alpha-img/comment-b.png';
        $default_bg      = '#e0e0e0';
        $default_prelude = '#ededed';

        self::dropImage(basename($body_bg));
        self::dropImage('page-t.png');
        self::dropImage(basename($page_b));
        self::dropImage(basename($comment_t));
        self::dropImage(basename($comment_b));

        $body_color    = dcThemeConfig::adjustColor($body_color);
        $prelude_color = dcThemeConfig::adjustColor($prelude_color);
        $comment_color = dcThemeConfig::adjustColor($comment_color);

        if ($top_image || $body_color || $gradient != 'light' || $prelude_color || $uploaded) {
            if (!$body_color) {
                $body_color = $default_bg;
            }
            $body_color = sscanf($body_color, '#%2X%2X%2X');

            # Create body gradient with color
            $d_body_bg = imagecreatetruecolor(50, 180);
            $fill      = imagecolorallocate($d_body_bg, $body_color[0], $body_color[1], $body_color[2]);
            imagefill($d_body_bg, 0, 0, $fill);

            # User choosed a gradient
            if ($body_g) {
                $s_body_bg = imagecreatefrompng($body_g);
                imagealphablending($s_body_bg, true);
                imagecopy($d_body_bg, $s_body_bg, 0, 0, 0, 0, 50, 180);
                imagedestroy($s_body_bg);
            }

            if (!$prelude_color) {
                $prelude_color = $default_prelude;
            }
            $prelude_color = sscanf($prelude_color, '#%2X%2X%2X');

            $s_prelude = imagecreatetruecolor(50, 30);
            $fill      = imagecolorallocate($s_prelude, $prelude_color[0], $prelude_color[1], $prelude_color[2]);
            imagefill($s_prelude, 0, 0, $fill);
            imagecopy($d_body_bg, $s_prelude, 0, 0, 0, 0, 50, 30);

            imagepng($d_body_bg, self::imagesPath() . '/' . basename($body_bg));
        }

        if ($top_image || $body_color || $gradient != 'light') {
            # Create top image from uploaded image
            $size = getimagesize($page_t);
            $size = $size[1];
            $type = files::getMimeType($page_t);

            $d_page_t = imagecreatetruecolor(800, $size);

            if ($type == 'image/png') {
                $s_page_t = @imagecreatefrompng($page_t);
            } else {
                $s_page_t = @imagecreatefromjpeg($page_t);
            }

            if (!$s_page_t) {
                throw new exception(__('Unable to open image.'));
            }

            $fill = imagecolorallocate($d_page_t, $body_color[0], $body_color[1], $body_color[2]);
            imagefill($d_page_t, 0, 0, $fill);

            if ($type == 'image/png') {
                # PNG, we only add body gradient and image
                imagealphablending($s_page_t, true);
                imagecopyresized($d_page_t, $d_body_bg, 0, 0, 0, 50, 800, 130, 50, 130);
                imagecopy($d_page_t, $s_page_t, 0, 0, 0, 0, 800, $size);
            } else {
                # JPEG, we add image and a frame with rounded corners
                imagecopy($d_page_t, $s_page_t, 0, 0, 0, 0, 800, $size);

                imagecopy($d_page_t, $d_body_bg, 0, 0, 0, 50, 8, 4);
                imagecopy($d_page_t, $d_body_bg, 0, 4, 0, 54, 4, 4);
                imagecopy($d_page_t, $d_body_bg, 792, 0, 0, 50, 8, 4);
                imagecopy($d_page_t, $d_body_bg, 796, 4, 0, 54, 4, 4);

                $mask = imagecreatefrompng($page_t_mask);
                imagealphablending($mask, true);
                imagecopy($d_page_t, $mask, 0, 0, 0, 0, 800, 11);
                imagedestroy($mask);

                $fill = imagecolorallocate($d_page_t, 255, 255, 255);
                imagefilledrectangle($d_page_t, 0, 11, 3, $size - 1, $fill);
                imagefilledrectangle($d_page_t, 796, 11, 799, $size - 1, $fill);
                imagefilledrectangle($d_page_t, 0, $size - 9, 799, $size - 1, $fill);
            }

            $config['top_height'] = ($size) . 'px';

            imagepng($d_page_t, self::imagesPath() . '/page-t.png');

            imagedestroy($d_body_bg);
            imagedestroy($d_page_t);
            imagedestroy($s_page_t);

            # Create bottom image with color
            $d_page_b = imagecreatetruecolor(800, 8);
            $fill     = imagecolorallocate($d_page_b, $body_color[0], $body_color[1], $body_color[2]);
            imagefill($d_page_b, 0, 0, $fill);

            $s_page_b = imagecreatefrompng($page_b);
            imagealphablending($s_page_b, true);
            imagecopy($d_page_b, $s_page_b, 0, 0, 0, 0, 800, 160);

            imagepng($d_page_b, self::imagesPath() . '/' . basename($page_b));

            imagedestroy($d_page_b);
            imagedestroy($s_page_b);
        }

        if ($comment_color) {
            self::commentImages($comment_color, $comment_t, $comment_b, basename($comment_t), basename($comment_b));
        }
        if ($comment_color_my) {
            self::commentImages($comment_color_my, $comment_t, $comment_b, 'commentmy-t.png', 'commentmy-b.png');
        }
    }

    protected static function commentImages($comment_color, $comment_t, $comment_b, $dest_t, $dest_b)
    {
        $comment_color = sscanf($comment_color, '#%2X%2X%2X');

        $d_comment_t = imagecreatetruecolor(500, 25);
        $fill        = imagecolorallocate($d_comment_t, $comment_color[0], $comment_color[1], $comment_color[2]);
        imagefill($d_comment_t, 0, 0, $fill);

        $s_comment_t = imagecreatefrompng($comment_t);
        imagealphablending($s_comment_t, true);
        imagecopy($d_comment_t, $s_comment_t, 0, 0, 0, 0, 500, 25);

        imagepng($d_comment_t, self::imagesPath() . '/' . $dest_t);
        imagedestroy($d_comment_t);
        imagedestroy($s_comment_t);

        $d_comment_b = imagecreatetruecolor(500, 7);
        $fill        = imagecolorallocate($d_comment_b, $comment_color[0], $comment_color[1], $comment_color[2]);
        imagefill($d_comment_b, 0, 0, $fill);

        $s_comment_b = imagecreatefrompng($comment_b);
        imagealphablending($s_comment_b, true);
        imagecopy($d_comment_b, $s_comment_b, 0, 0, 0, 0, 500, 7);

        imagepng($d_comment_b, self::imagesPath() . '/' . $dest_b);
        imagedestroy($d_comment_b);
        imagedestroy($s_comment_b);
    }

}
