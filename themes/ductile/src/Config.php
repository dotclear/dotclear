<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\ductile;

use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\ThemeConfig;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Caption;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Color;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;

/**
 * @brief   The module configuration process.
 * @ingroup ductile
 *
 * @todo switch Helper/Html/Form/...
 */
class Config extends Process
{
    public static function init(): bool
    {
        // limit to backend permissions
        if (!self::status(My::checkContext(My::CONFIG))) {
            return false;
        }

        // load locales
        My::l10n('admin');

        if (preg_match('#^http(s)?://#', (string) App::blog()->settings()->system->themes_url)) {
            App::backend()->img_url = Http::concatURL(App::blog()->settings()->system->themes_url, App::blog()->settings()->system->theme . '/img/');
        } else {
            App::backend()->img_url = Http::concatURL(App::blog()->url(), App::blog()->settings()->system->themes_url . '/' . App::blog()->settings()->system->theme . '/img/');
        }

        $img_path = My::path() . '/img/';
        $tpl_path = My::path() . '/tpl/';

        App::backend()->standalone_config = (bool) App::themes()->moduleInfo(App::blog()->settings()->system->theme, 'standalone_config');

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        $list_types = [
            __('Title') => 'title',
            __('Short') => 'short',
            __('Full')  => 'full',
        ];
        // Get all _entry-*.html in tpl folder of theme
        $list_types_templates = Files::scandir($tpl_path);
        foreach ($list_types_templates as $v) {
            if (preg_match('/^_entry\-(.*)\.html$/', $v, $m) && !in_array($m[1], $list_types)) {
                // template not already in full list
                $list_types[__($m[1])] = $m[1];
            }
        }
        App::backend()->list_types = $list_types;

        App::backend()->contexts = [
            'default'      => __('Home (first page)'),
            'default-page' => __('Home (other pages)'),
            'category'     => __('Entries for a category'),
            'tag'          => __('Entries for a tag'),
            'search'       => __('Search result entries'),
            'archive'      => __('Month archive entries'),
        ];

        App::backend()->fonts = [
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
            __('Monospace')         => 'Monospace',
        ];

        App::backend()->webfont_apis = [
            __('none')                => '',
            __('javascript (Adobe)')  => 'js',
            __('stylesheet (Google)') => 'css',
        ];

        App::backend()->font_families = [
            // Theme standard
            'Ductile body'      => '"Century Schoolbook", "Century Schoolbook L", Georgia, serif',
            'Ductile alternate' => '"Franklin gothic medium", "arial narrow", "DejaVu Sans Condensed", "helvetica neue", helvetica, sans-serif',

            // Serif families
            'Times New Roman' => 'Cambria, "Hoefler Text", Utopia, "Liberation Serif", "Nimbus Roman No9 L Regular", Times, "Times New Roman", serif',
            'Georgia'         => 'Constantia, "Lucida Bright", Lucidabright, "Lucida Serif", Lucida, "DejaVu Serif", "Bitstream Vera Serif", "Liberation Serif", Georgia, serif',
            'Garamond'        => '"Palatino Linotype", Palatino, Palladio, "URW Palladio L", "Book Antiqua", Baskerville, "Bookman Old Style", "Bitstream Charter", "Nimbus Roman No9 L", Garamond, "Apple Garamond", "ITC Garamond Narrow", "New Century Schoolbook", "Century Schoolbook", "Century Schoolbook L", Georgia, serif',

            // Sans-serif families
            'Helvetica/Arial' => 'Frutiger, "Frutiger Linotype", Univers, Calibri, "Gill Sans", "Gill Sans MT", "Myriad Pro", Myriad, "DejaVu Sans Condensed", "Liberation Sans", "Nimbus Sans L", Tahoma, Geneva, "Helvetica Neue", Helvetica, Arial, sans-serif',
            'Verdana'         => 'Corbel, "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Bitstream Vera Sans", "Liberation Sans", Verdana, "Verdana Ref", sans-serif',
            'Trebuchet MS'    => '"Segoe UI", Candara, "Bitstream Vera Sans", "DejaVu Sans", "Bitstream Vera Sans", "Trebuchet MS", Verdana, "Verdana Ref", sans-serif',

            // Cursive families
            'Impact' => 'Impact, Haettenschweiler, "Franklin Gothic Bold", Charcoal, "Helvetica Inserat", "Bitstream Vera Sans Bold", "Arial Black", sans-serif',

            // Monospace families
            'Monospace' => 'Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", Monaco, "Courier New", Courier, monospace',
        ];

        $ductile_base = [
            // HTML
            'subtitle_hidden' => null,
            'logo_src'        => null,
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
            'post_simple_title_c'      => null,
        ];

        $ductile_lists_base = [
            'default'      => 'short',
            'default-page' => 'short',
            'category'     => 'short',
            'tag'          => 'short',
            'search'       => 'short',
            'archive'      => 'short',
        ];

        App::backend()->ductile_counts_base = [
            'default'      => null,
            'default-page' => null,
            'category'     => null,
            'tag'          => null,
            'search'       => null,
        ];

        $getSetting = function (string $name, array $default): array {
            // Get current setting
            $setting = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_' . $name);
            if (is_null($setting)) {
                // No setting in DB, return default
                return $default;
            }
            $setting = @unserialize($setting);
            if (!is_array($setting)) {
                // Setting is not an array, return default
                return $default;
            }

            return $setting;
        };

        App::backend()->ductile_user = $getSetting('style', []);
        App::backend()->ductile_user = [...$ductile_base, ...App::backend()->ductile_user];

        App::backend()->ductile_lists = $getSetting('entries_lists', $ductile_lists_base);
        App::backend()->ductile_lists = [...$ductile_lists_base, ...App::backend()->ductile_lists];

        App::backend()->ductile_counts = $getSetting('entries_counts', App::backend()->ductile_counts_base);
        App::backend()->ductile_counts = [...App::backend()->ductile_counts_base, ...App::backend()->ductile_counts];

        $ductile_stickers = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_stickers');
        $ductile_stickers = @unserialize((string) $ductile_stickers);

        // If no stickers defined, add feed Atom one
        if (!is_array($ductile_stickers)) {
            $ductile_stickers = [[
                'label' => __('Subscribe'),
                'url'   => App::blog()->url() .
                App::url()->getURLFor('feed', 'atom'),
                'image' => 'sticker-feed.png',
            ]];
        }

        $ductile_stickers_full = [];
        // Get all sticker images already used
        foreach ($ductile_stickers as $v) {
            $ductile_stickers_full[] = $v['image'];
        }
        // Get all sticker-*.png in img folder of theme
        $ductile_stickers_images = Files::scandir($img_path);
        foreach ($ductile_stickers_images as $v) {
            if (preg_match('/^sticker\-(.*)\.png$/', $v) && !in_array($v, $ductile_stickers_full)) {
                // image not already used
                $ductile_stickers[] = [
                    'label' => null,
                    'url'   => null,
                    'image' => $v, ];
            }
        }
        App::backend()->ductile_stickers = $ductile_stickers;

        App::backend()->conf_tab = $_POST['conf_tab'] ?? 'html';

        return self::status();
    }

    /**
     * Processes the request(s).
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if ($_POST !== []) {
            try {
                // HTML
                $ductile_user = App::backend()->ductile_user;

                $ductile_user['subtitle_hidden'] = (int) !empty($_POST['subtitle_hidden']);
                $ductile_user['logo_src']        = $_POST['logo_src'];

                App::backend()->ductile_user = $ductile_user;

                $ductile_stickers = [];
                for ($i = 0; $i < (is_countable($_POST['sticker_image']) ? count($_POST['sticker_image']) : 0); $i++) {
                    $ductile_stickers[] = [
                        'label' => $_POST['sticker_label'][$i],
                        'url'   => $_POST['sticker_url'][$i],
                        'image' => $_POST['sticker_image'][$i],
                    ];
                }

                $order = [];
                if (empty($_POST['ds_order']) && !empty($_POST['order'])) {
                    $order = $_POST['order'];
                    asort($order);
                    $order = array_keys($order);
                }
                if ($order !== []) {
                    $new_ductile_stickers = [];
                    foreach ($order as $k) {
                        $new_ductile_stickers[] = [
                            'label' => $ductile_stickers[$k]['label'],
                            'url'   => $ductile_stickers[$k]['url'],
                            'image' => $ductile_stickers[$k]['image'],
                        ];
                    }
                    $ductile_stickers = $new_ductile_stickers;
                }
                App::backend()->ductile_stickers = $ductile_stickers;

                $ductile_lists = App::backend()->ductile_lists;
                for ($i = 0; $i < (is_countable($_POST['list_type']) ? count($_POST['list_type']) : 0); $i++) {
                    $ductile_lists[$_POST['list_ctx'][$i]] = $_POST['list_type'][$i];
                }
                App::backend()->ductile_lists = $ductile_lists;

                $ductile_counts = App::backend()->ductile_counts;
                for ($i = 0; $i < (is_countable($_POST['count_nb']) ? count($_POST['count_nb']) : 0); $i++) {
                    $ductile_counts[$_POST['count_ctx'][$i]] = $_POST['count_nb'][$i];
                }
                App::backend()->ductile_counts = $ductile_counts;

                // CSS
                $ductile_user = App::backend()->ductile_user;

                $ductile_user['body_font']           = $_POST['body_font'];
                $ductile_user['body_webfont_family'] = $_POST['body_webfont_family'];
                $ductile_user['body_webfont_url']    = $_POST['body_webfont_url'];
                $ductile_user['body_webfont_api']    = $_POST['body_webfont_api'];

                $ductile_user['alternate_font']           = $_POST['alternate_font'];
                $ductile_user['alternate_webfont_family'] = $_POST['alternate_webfont_family'];
                $ductile_user['alternate_webfont_url']    = $_POST['alternate_webfont_url'];
                $ductile_user['alternate_webfont_api']    = $_POST['alternate_webfont_api'];

                $ductile_user['blog_title_w'] = (int) !empty($_POST['blog_title_w']);
                $ductile_user['blog_title_s'] = ThemeConfig::adjustFontSize($_POST['blog_title_s']);
                $ductile_user['blog_title_c'] = ThemeConfig::adjustColor($_POST['blog_title_c']);

                $ductile_user['post_title_w'] = (int) !empty($_POST['post_title_w']);
                $ductile_user['post_title_s'] = ThemeConfig::adjustFontSize($_POST['post_title_s']);
                $ductile_user['post_title_c'] = ThemeConfig::adjustColor($_POST['post_title_c']);

                $ductile_user['post_link_w']   = (int) !empty($_POST['post_link_w']);
                $ductile_user['post_link_v_c'] = ThemeConfig::adjustColor($_POST['post_link_v_c']);
                $ductile_user['post_link_f_c'] = ThemeConfig::adjustColor($_POST['post_link_f_c']);

                $ductile_user['post_simple_title_c'] = ThemeConfig::adjustColor($_POST['post_simple_title_c']);

                $ductile_user['blog_title_w_m'] = (int) !empty($_POST['blog_title_w_m']);
                $ductile_user['blog_title_s_m'] = ThemeConfig::adjustFontSize($_POST['blog_title_s_m']);
                $ductile_user['blog_title_c_m'] = ThemeConfig::adjustColor($_POST['blog_title_c_m']);

                $ductile_user['post_title_w_m'] = (int) !empty($_POST['post_title_w_m']);
                $ductile_user['post_title_s_m'] = ThemeConfig::adjustFontSize($_POST['post_title_s_m']);
                $ductile_user['post_title_c_m'] = ThemeConfig::adjustColor($_POST['post_title_c_m']);

                App::backend()->ductile_user = $ductile_user;

                // Save settings
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_style', serialize(App::backend()->ductile_user));
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_stickers', serialize(App::backend()->ductile_stickers));
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_entries_lists', serialize(App::backend()->ductile_lists));
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_entries_counts', serialize(App::backend()->ductile_counts));

                // Blog refresh
                App::blog()->triggerBlog();

                // Template cache reset
                App::cache()->emptyTemplatesCache();

                Notices::addSuccessNotice(__('Theme configuration upgraded.'));
                App::backend()->url()->redirect('admin.blog.theme', ['conf' => '1']);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Helpers

        $fontDef = fn ($c): string => isset(App::backend()->font_families[$c]) ?
            (new Text('abbr', __('Font family')))
                ->title(Html::escapeHTML(App::backend()->font_families[$c]))
            ->render() :
            '';

        $stickers = function () {
            $count = 0;
            foreach (App::backend()->ductile_stickers as $i => $v) {
                $count++;
                yield (new Tr())
                    ->id('l_' . $i)
                    ->cols([
                        (new Td())
                            ->class([App::auth()->prefs()->accessibility->nodragdrop ? '' : 'handle', 'minimal'])
                            ->items([
                                (new Number(['order[' . $i . ']'], 0, count(App::backend()->ductile_stickers), $count))
                                    ->class('position'),
                                (new Hidden(['dynorder[]', 'dynorder-' . $i], $i)),
                            ]),
                        (new Td())
                            ->items([
                                (new Hidden(['sticker_image[]'], $v['image'])),
                                (new Img(My::fileURL('img/' . $v['image'])))
                                    ->alt($v['image']),
                            ]),
                        (new Td())
                            ->items([
                                (new Input(['sticker_label[]', 'dsl-' . $i]))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->default($v['label']),
                            ]),
                        (new Td())
                            ->items([
                                (new Input(['sticker_url[]', 'dsu-' . $i]))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->default($v['url']),
                            ]),
                    ]);
            }
        };

        $counters = function () {
            foreach (App::backend()->ductile_lists as $k => $v) {
                yield (new Tr())
                    ->items([
                        (new Td())
                            ->text(App::backend()->contexts[$k]),
                        (new Td())
                            ->items([
                                (new Hidden(['list_ctx[]'], $k)),
                                (new Select(['list_type[]']))
                                    ->items(App::backend()->list_types)
                                    ->default($v),
                            ]),
                        (new Td())
                            ->items([
                                array_key_exists($k, App::backend()->ductile_counts) ?
                                    (new Set())
                                        ->items([
                                            (new Hidden(['count_ctx[]'], $k)),
                                            (new Number(['count_nb[]'], 0, 999, (int) App::backend()->ductile_counts[$k])),
                                        ]) :
                                    (new None()),
                            ]),
                    ]);
            }
        };

        // HTML Tab

        echo (new Div('ductile-html'))
            ->class('multi-part')
            ->title(__('Content'))
            ->items([
                (new Text('h3', __('Content'))),
                (new Text('h4', __('Header')))
                    ->class(['border-top', 'pretty-title']),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Checkbox('subtitle_hidden', App::backend()->ductile_user['subtitle_hidden']))
                            ->label((new Label(__('Hide blog description:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Input('logo_src'))
                            ->size(40)
                            ->maxlength(255)
                            ->default(App::backend()->ductile_user['logo_src'])
                            ->label((new Label(__('Logo URL:'), Label::OL_TF))),
                    ]),
                App::plugins()->moduleExists('simpleMenu') ?
                    (new Note())
                        ->text(sprintf(
                            __('To configure the top menu go to the <a href="%s">Simple Menu administration page</a>.'),
                            App::backend()->url()->get('admin.plugin.simpleMenu')
                        ))
                        ->class(['form-note', 'info']) :
                    (new None()),
                (new Text('h4', __('Stickers')))
                    ->class(['border-top', 'pretty-title']),
                (new Div())
                    ->class('table-outer')
                    ->items([
                        (new Table())
                            ->class('dragable')
                            ->caption((new Caption(__('Stickers (footer)'))))
                            ->thead((new Thead())
                                ->items([
                                    (new Tr())
                                        ->items([
                                            (new Th())
                                                ->scope('col'),
                                            (new Th())
                                                ->text(__('Image'))
                                                ->scope('col'),
                                            (new Th())
                                                ->text(__('Label'))
                                                ->scope('col'),
                                            (new Th())
                                                ->text(__('URL'))
                                                ->scope('col'),
                                        ]),
                                ]))
                            ->tbody((new Tbody('stickerslist'))
                                ->items([
                                    ...$stickers(),
                                ])),
                    ]),
                (new Text('h4', __('Entries list types and limits')))
                    ->class(['border-top', 'pretty-title']),
                (new Table('entrieslist'))
                    ->caption((new Caption(__('Entries lists')))
                        ->class('hidden'))
                    ->thead((new Thead())
                        ->items([
                            (new Tr())
                                ->items([
                                    (new Td())
                                        ->scope('col')
                                        ->text(__('Context')),
                                    (new Td())
                                        ->scope('col')
                                        ->text(__('Entries list type')),
                                    (new Td())
                                        ->scope('col')
                                        ->text(__('Number of entries')),
                                ]),
                        ]))
                    ->tbody((new Tbody())
                        ->items([
                            ...$counters(),
                        ])),
                (new Hidden('ds_order', '')),
            ])
        ->render();

        // CSS tab

        echo (new Div('ductile-css'))
            ->class('multi-part')
            ->title(__('Presentation'))
            ->items([
                (new Text('h3', __('General settings'))),
                (new Text('h4', __('Fonts')))
                    ->class(['border-top', 'pretty-title']),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h5', __('Main text'))),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Select('body_font'))
                                            ->items(App::backend()->fonts)
                                            ->default(App::backend()->ductile_user['body_font'])
                                            ->label((new Label(__('Main font:'), Label::OL_TF))
                                                ->suffix(empty(App::backend()->ductile_user['body_font']) ? '' : $fontDef(App::backend()->ductile_user['body_font']))),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('Set to Default to use a webfont.')),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('body_webfont_family'))
                                            ->size(25)
                                            ->maxlength(255)
                                            ->default(App::backend()->ductile_user['body_webfont_family'])
                                            ->label((new Label(__('Webfont family:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Url('body_webfont_url'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->default(App::backend()->ductile_user['body_webfont_url'])
                                            ->label((new Label(__('Webfont URL:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Select('body_webfont_api'))
                                            ->items(App::backend()->webfont_apis)
                                            ->default(App::backend()->ductile_user['body_webfont_api'])
                                            ->label((new Label(__('Webfont API:'), Label::OL_TF))),
                                    ]),
                            ]),
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h5', __('Secondary text'))),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Select('alternate_font'))
                                            ->items(App::backend()->fonts)
                                            ->default(App::backend()->ductile_user['alternate_font'])
                                            ->label((new Label(__('Secondary font:'), Label::OL_TF))
                                                ->suffix(empty(App::backend()->ductile_user['alternate_font']) ? '' : $fontDef(App::backend()->ductile_user['alternate_font']))),
                                    ]),
                                (new Note())
                                    ->class('form-note')
                                    ->text(__('Set to Default to use a webfont.')),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('alternate_webfont_family'))
                                            ->size(25)
                                            ->maxlength(255)
                                            ->default(App::backend()->ductile_user['alternate_webfont_family'])
                                            ->label((new Label(__('Webfont family:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Url('alternate_webfont_url'))
                                            ->size(50)
                                            ->maxlength(255)
                                            ->default(App::backend()->ductile_user['alternate_webfont_url'])
                                            ->label((new Label(__('Webfont URL:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Select('alternate_webfont_api'))
                                            ->items(App::backend()->webfont_apis)
                                            ->default(App::backend()->ductile_user['alternate_webfont_api'])
                                            ->label((new Label(__('Webfont API:'), Label::OL_TF))),
                                    ]),
                            ]),
                    ]),
                (new Text('h4', __('Titles')))
                    ->class(['border-top', 'pretty-title']),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h5', __('Blog title'))),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Checkbox('blog_title_w', App::backend()->ductile_user['blog_title_w']))
                                            ->label((new Label(__('In bold:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('blog_title_s'))
                                            ->size(7)
                                            ->maxlength(7)
                                            ->default(App::backend()->ductile_user['blog_title_s'])
                                            ->label((new Label(__('Font size (in em by default):'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Color('blog_title_c', App::backend()->ductile_user['blog_title_c']))
                                            ->label((new Label(__('Color:'), Label::OL_TF))
                                                ->suffix(ThemeConfig::contrastRatio(
                                                    App::backend()->ductile_user['blog_title_c'],
                                                    '#ffffff',
                                                    (empty(App::backend()->ductile_user['blog_title_s']) ? '2em' : App::backend()->ductile_user['blog_title_s']),
                                                    (bool) App::backend()->ductile_user['blog_title_w']
                                                ))),
                                    ]),
                            ]),
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h5', __('Post title'))),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Checkbox('post_title_w', App::backend()->ductile_user['post_title_w']))
                                            ->label((new Label(__('In bold:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('post_title_s'))
                                            ->size(7)
                                            ->maxlength(7)
                                            ->default(App::backend()->ductile_user['post_title_s'])
                                            ->label((new Label(__('Font size (in em by default):'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Color('post_title_c', App::backend()->ductile_user['post_title_c']))
                                            ->label((new Label(__('Color:'), Label::OL_TF))
                                                ->suffix(ThemeConfig::contrastRatio(
                                                    App::backend()->ductile_user['post_title_c'],
                                                    '#ffffff',
                                                    (empty(App::backend()->ductile_user['post_title_s']) ? '2.5em' : App::backend()->ductile_user['post_title_s']),
                                                    (bool) App::backend()->ductile_user['post_title_w']
                                                ))),
                                    ]),
                            ]),
                    ]),
                (new Text('h5', __('Titles without link'))),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_simple_title_c', App::backend()->ductile_user['post_simple_title_c']))
                            ->label((new Label(__('Color:'), Label::OL_TF))
                                ->suffix(ThemeConfig::contrastRatio(
                                    App::backend()->ductile_user['post_simple_title_c'],
                                    '#ffffff',
                                    '1.1em',    // H5 minimum size
                                    false
                                ))),
                    ]),
                (new Text('h4', __('Inside posts links')))
                    ->class(['border-top', 'pretty-title']),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Checkbox('post_link_w', App::backend()->ductile_user['post_link_w']))
                            ->label((new Label(__('In bold:'), Label::OL_TF))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_link_v_c', App::backend()->ductile_user['post_link_v_c']))
                            ->label((new Label(__('Normal and visited links color:'), Label::OL_TF))
                                ->suffix(ThemeConfig::contrastRatio(
                                    App::backend()->ductile_user['post_link_v_c'],
                                    '#ffffff',
                                    '1em',
                                    (bool) App::backend()->ductile_user['post_link_w']
                                ))),
                    ]),
                (new Para())
                    ->class('field')
                    ->items([
                        (new Color('post_link_f_c', App::backend()->ductile_user['post_link_f_c']))
                            ->label((new Label(__('Active, hover and focus links color:'), Label::OL_TF))
                                ->suffix(ThemeConfig::contrastRatio(
                                    App::backend()->ductile_user['post_link_f_c'],
                                    '#ebebee',
                                    '1em',
                                    (bool) App::backend()->ductile_user['post_link_w']
                                ))),
                    ]),
                (new Text('h3', __('Mobile specific settings'))),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h4', __('Blog title')))
                                    ->class(['border-top', 'pretty-title']),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Checkbox('blog_title_w_m', App::backend()->ductile_user['blog_title_w_m']))
                                            ->label((new Label(__('In bold:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('blog_title_s_m'))
                                            ->size(7)
                                            ->maxlength(7)
                                            ->default(App::backend()->ductile_user['blog_title_s_m'])
                                            ->label((new Label(__('Font size (in em by default):'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Color('blog_title_c_m', App::backend()->ductile_user['blog_title_c_m']))
                                            ->label((new Label(__('Color:'), Label::OL_TF))
                                                ->suffix(ThemeConfig::contrastRatio(
                                                    App::backend()->ductile_user['blog_title_c_m'],
                                                    '#d7d7dc',
                                                    empty(App::backend()->ductile_user['blog_title_s_m']) ? '1.8em' : App::backend()->ductile_user['blog_title_s_m'],
                                                    (bool) App::backend()->ductile_user['blog_title_w_m']
                                                ))),
                                    ]),
                            ]),
                        (new Div())
                            ->class('col')
                            ->items([
                                (new Text('h4', __('Post title')))
                                    ->class(['border-top', 'pretty-title']),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Checkbox('post_title_w_m', App::backend()->ductile_user['post_title_w_m']))
                                            ->label((new Label(__('In bold:'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Input('post_title_s_m'))
                                            ->size(7)
                                            ->maxlength(7)
                                            ->default(App::backend()->ductile_user['post_title_s_m'])
                                            ->label((new Label(__('Font size (in em by default):'), Label::OL_TF))),
                                    ]),
                                (new Para())
                                    ->class('field')
                                    ->items([
                                        (new Color('post_title_c_m', App::backend()->ductile_user['post_title_c_m']))
                                            ->label((new Label(__('Color:'), Label::OL_TF))
                                                ->suffix(ThemeConfig::contrastRatio(
                                                    App::backend()->ductile_user['post_title_c_m'],
                                                    '#ffffff',
                                                    empty(App::backend()->ductile_user['post_title_s_m']) ? '1.8em' : App::backend()->ductile_user['post_title_s_m'],
                                                    (bool) App::backend()->ductile_user['post_title_w_m']
                                                ))),
                                    ]),
                            ]),
                    ]),

            ])
        ->render();

        Page::helpBlock('ductile');
    }
}
