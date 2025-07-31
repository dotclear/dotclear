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
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\File;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Textarea;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

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

        $fonts = Blowup::fontsList();

        // Preview top image
        $preview_image = '';
        if (App::backend()->can_write_images) {
            if (App::backend()->blowup_user['top_image'] === 'custom' && App::backend()->blowup_user['uploaded']) {
                $preview_image = Http::concatURL(App::blog()->url(), Blowup::imagesURL() . '/page-t.png');
            } else {
                $preview_image = Blowup::themeURL() . '/alpha-img/page-t/' . App::backend()->blowup_user['top_image'] . '.png';
            }
        }

        // Import / Export configuration
        $choices  = [];
        $excludes = ['uploaded', 'top_height'];
        if (App::backend()->blowup_user['top_image'] === 'custom') {
            $tmp_exclude[] = 'top_image';
        }
        foreach (App::backend()->blowup_user as $key => $value) {
            if (!in_array($key, $excludes)) {
                $choices[] = $key . ':' . '"' . $value . '"';
            }
        }
        $export_code = implode('; ', $choices);

        echo (new Div())
            ->class('fieldset')
            ->items([
                (new Text('h3', __('Customization')))
                    ->id('theme_config'),
                // Here will come style selector (is JS is enabled), see config.js
                // h4 + p + select
                (new Text('h4', __('General'))),
                App::backend()->can_write_images ?
                    (new Set())
                        ->items([
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Color('body_bg_c', App::backend()->blowup_user['body_bg_c']))
                                        ->label((new Label(__('Background color:'), Label::OL_TF))),
                                ]),
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Select('body_bg_g'))
                                        ->items(App::backend()->gradient_types)
                                        ->default(App::backend()->blowup_user['body_bg_g'])
                                        ->label((new Label(__('Background color fill:'), Label::OL_TF))),
                                ]),
                        ]) :
                    (new None()),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('body_txt_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['body_txt_f'])
                            ->label((new Label(__('Main text font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('body_txt_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['body_txt_s'])
                            ->label((new Label(__('Main text font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('body_txt_c', App::backend()->blowup_user['body_txt_c']))
                            ->label((new Label(__('Main text color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('body_line_height'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['body_line_height'])
                            ->label((new Label(__('Text line height:'), Label::OL_TF))),
                    ]),
                (new Text('h4', __('Links')))
                    ->class('border-top'),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('body_link_c', App::backend()->blowup_user['body_link_c']))
                            ->label((new Label(__('Links color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('body_link_v_c', App::backend()->blowup_user['body_link_v_c']))
                            ->label((new Label(__('Visited links color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('body_link_f_c', App::backend()->blowup_user['body_link_f_c']))
                            ->label((new Label(__('Focus links color:'), Label::OL_TF))),
                    ]),
                (new Text('h4', __('Page top')))
                    ->class('border-top'),
                App::backend()->can_write_images ?
                    (new Set())
                        ->items([
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Color('prelude_c', App::backend()->blowup_user['prelude_c']))
                                        ->label((new Label(__('Prelude color:'), Label::OL_TF))),
                                ]),
                        ]) :
                    (new None()),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Checkbox('blog_title_hide', App::backend()->blowup_user['blog_title_hide']))
                            ->label((new Label(__('Hide main title'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('blog_title_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['blog_title_f'])
                            ->label((new Label(__('Main title font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('blog_title_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['blog_title_s'])
                            ->label((new Label(__('Main title font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('blog_title_c', App::backend()->blowup_user['blog_title_c']))
                            ->label((new Label(__('Main title color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('blog_title_a'))
                            ->items([__('center') => 'center', __('left') => 'left', __('right') => 'right'])
                            ->default(App::backend()->blowup_user['blog_title_a'])
                            ->label((new Label(__('Main title alignment:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('blog_title_p'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['blog_title_p'])
                            ->label((new Label(__('Main title position (x:y)'), Label::OL_TF))),
                    ]),
                App::backend()->can_write_images ?
                    (new Set())
                        ->items([
                            (new Text('h5', __('Top image')))
                                ->class('pretty-title'),
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Select('top_image'))
                                        ->items(App::backend()->top_images)
                                        ->default(App::backend()->blowup_user['top_image'] ?: 'default')
                                        ->label((new Label(__('Top image'), Label::OL_TF))),
                                ]),
                            (new Note())
                                ->class(['form-note', 'info'])
                                ->text(__('Choose "Custom..." to upload your own image.')),
                            (new Para('uploader'))
                                ->items([
                                    (new File('upfile'))
                                        ->size(35)
                                        ->label((new Label(sprintf(__('JPEG or PNG file, 800 pixels wide, maximum size %s'), Files::size(App::config()->maxUploadSize())), Label::OL_TF))),
                                ]),
                            (new Text('h5', __('Preview'))),
                            (new Div())
                                ->class('grid')
                                ->extra('style="width:800px;border:1px solid #ccc;"')
                                ->items([
                                    (new Img($preview_image, 'image-preview'))
                                        ->extra('style="display:block;"'),
                                ]),
                        ]) :
                    (new None()),
                (new Text('h4', __('Sidebar')))
                    ->class('border-top'),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('sidebar_position'))
                            ->items([__('right') => 'right', __('left') => 'left'])
                            ->default(App::backend()->blowup_user['sidebar_position'])
                            ->label((new Label(__('Sidebar position:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('sidebar_text_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['sidebar_text_f'])
                            ->label((new Label(__('Sidebar text font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('sidebar_text_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['sidebar_text_s'])
                            ->label((new Label(__('Sidebar text font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_text_c', App::backend()->blowup_user['sidebar_text_c']))
                            ->label((new Label(__('Sidebar text color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('sidebar_title_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['sidebar_title_f'])
                            ->label((new Label(__('Sidebar titles font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('sidebar_title_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['sidebar_title_s'])
                            ->label((new Label(__('Sidebar titles font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_title_c', App::backend()->blowup_user['sidebar_title_c']))
                            ->label((new Label(__('Sidebar titles color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('sidebar_title2_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['sidebar_title2_f'])
                            ->label((new Label(__('Sidebar 2nd level titles font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('sidebar_title2_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['sidebar_title2_s'])
                            ->label((new Label(__('Sidebar 2nd level titles font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_title2_c', App::backend()->blowup_user['sidebar_title2_c']))
                            ->label((new Label(__('Sidebar 2nd level titles color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_line_c', App::backend()->blowup_user['sidebar_line_c']))
                            ->label((new Label(__('Sidebar lines color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_link_c', App::backend()->blowup_user['sidebar_link_c']))
                            ->label((new Label(__('Sidebar links color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_link_v_c', App::backend()->blowup_user['sidebar_link_v_c']))
                            ->label((new Label(__('Sidebar visited links color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('sidebar_link_f_c', App::backend()->blowup_user['sidebar_link_f_c']))
                            ->label((new Label(__('Sidebar focus links color:'), Label::OL_TF))),
                    ]),
                (new Text('h4', __('Entries')))
                    ->class('border-top'),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('date_title_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['date_title_f'])
                            ->label((new Label(__('Date title font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('date_title_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['date_title_s'])
                            ->label((new Label(__('Date title font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('date_title_c', App::backend()->blowup_user['date_title_c']))
                            ->label((new Label(__('Date title color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('post_title_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['post_title_f'])
                            ->label((new Label(__('Entry title font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('post_title_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['post_title_s'])
                            ->label((new Label(__('Entry title font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_title_c', App::backend()->blowup_user['post_title_c']))
                            ->label((new Label(__('Entry title color:'), Label::OL_TF))),
                    ]),
                App::backend()->can_write_images ?
                    (new Set())
                        ->items([
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Color('post_comment_bg_c', App::backend()->blowup_user['post_comment_bg_c']))
                                        ->label((new Label(__('Comment background color:'), Label::OL_TF))),
                                ]),
                        ]) :
                    (new None()),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_comment_c', App::backend()->blowup_user['post_comment_c']))
                            ->label((new Label(__('Comment text color:'), Label::OL_TF))),
                    ]),
                App::backend()->can_write_images ?
                    (new Set())
                        ->items([
                            (new Para())
                                ->class('field')
                                ->items([
                                    (new Color('post_commentmy_bg_c', App::backend()->blowup_user['post_commentmy_bg_c']))
                                        ->label((new Label(__('My comment background color:'), Label::OL_TF))),
                                ]),
                        ]) :
                    (new None()),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_commentmy_c', App::backend()->blowup_user['post_commentmy_c']))
                            ->label((new Label(__('My comment text color:'), Label::OL_TF))),
                    ]),
                (new Text('h4', __('Footer')))
                    ->class('border-top'),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Select('footer_f'))
                            ->items($fonts)
                            ->default(App::backend()->blowup_user['footer_f'])
                            ->label((new Label(__('Footer font:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('footer_s'))
                            ->size(7)
                            ->maxlength(7)
                            ->default(App::backend()->blowup_user['footer_s'])
                            ->label((new Label(__('Footer font size:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('footer_c', App::backend()->blowup_user['footer_c']))
                            ->label((new Label(__('Footer color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('footer_l_c', App::backend()->blowup_user['footer_l_c']))
                            ->label((new Label(__('Footer links color:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('footer_bg_c', App::backend()->blowup_user['footer_bg_c']))
                            ->label((new Label(__('Footer background color:'), Label::OL_TF))),
                    ]),
                (new Text('h4', __('Additional CSS')))
                    ->class('border-top'),
                (new Para())
                    ->items([
                        (new Textarea('extra_css', Html::escapeHTML(App::backend()->blowup_user['extra_css'])))
                            ->title(__('Additional CSS'))
                            ->class('maximal')
                            ->cols(72)
                            ->rows(5)
                            ->label((new Label(__('Any additional CSS styles (must be written using the CSS syntax):'), Label::OL_TF))),
                    ]),
            ])
        ->render();

        echo (new Div())
            ->class('fieldset')
            ->items([
                (new Text('h3', __('Configuration import / export')))
                    ->id('bu_export'),
                (new Div('bu_export_content'))
                    ->items([
                        (new Note())
                            ->class(['form-note', 'info'])
                            ->text(__('You can share your configuration using the following code. To apply a configuration, paste the code, click on "Apply code" and save.')),
                        (new Para())
                            ->items([
                                (new Textarea('export_code', $export_code))
                                    ->title(__('Copy this code:'))
                                    ->class('maximal')
                                    ->cols(72)
                                    ->rows(5)
                                    ->label((new Label(__('Copy this code:'), Label::OL_TF))),
                            ]),
                    ]),
            ])
        ->render();

        Page::helpBlock('blowupConfig');
    }
}
