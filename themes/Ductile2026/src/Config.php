<?php

/**
 * @brief Ductile 2026, Refresh of ductile Dotclear 2 theme
 *
 * @package Dotclear
 * @subpackage Themes
 *
 * @copyright Kozlika, Franck Paul and contributors
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Theme\Ductile2026;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   The module configuration process.
 * @ingroup ductile
 */
class Config
{
    use TraitProcess;

    public static function init(): bool
    {
        // limit to backend permissions
        if (!self::status(My::checkContext(My::CONFIG))) {
            return false;
        }

        // load locales
        My::l10n('admin');

        $img_path = My::path() . '/img/';

        // Load contextual help
        App::themes()->loadModuleL10Nresources(My::id(), App::lang()->getLang());

        $ductile_base = [
            // HTML
            'logo_src' => null,
        ];

        /**
         * @return array<array-key, mixed>
         */
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
        App::backend()->ductile_user = array_merge($ductile_base, App::backend()->ductile_user);

        $ductile_stickers = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_stickers');
        $ductile_stickers = @unserialize((string) $ductile_stickers);

        // If no stickers defined, add feed Atom one
        if (!is_array($ductile_stickers)) {
            $ductile_stickers = [[
                'label' => __('Subscribe'),
                'url'   => App::blog()->url() . App::url()->getURLFor('feed', 'atom'),
                'image' => 'sticker-feed.svg',
            ]];
        }

        $ductile_stickers_full = [];
        // Get all sticker images already used
        foreach ($ductile_stickers as $v) {
            $ductile_stickers_full[] = $v['image'];
        }
        // Get all sticker-*.svg in img folder of theme
        $ductile_stickers_images = Files::scandir($img_path);
        foreach ($ductile_stickers_images as $v) {
            if (preg_match('/^sticker\-(.*)\.svg$/', $v) && !in_array($v, $ductile_stickers_full)) {
                // image not already used
                $ductile_stickers[] = [
                    'label' => null,
                    'url'   => null,
                    'image' => $v, ];
            }
        }
        App::backend()->ductile_stickers = $ductile_stickers;

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

                $logo_src = isset($_POST['user_image']) && is_string($logo_src = $_POST['user_image']) && $logo_src !== '' ? $logo_src : My::fileURL('img/logo-ductile.svg');

                $ductile_user['logo_src'] = $logo_src;

                App::backend()->ductile_user = $ductile_user;

                /**
                 * @var array<array{label: string, url: string, image: string}>
                 */
                $ductile_stickers = [];
                for ($i = 0; $i < (is_countable($_POST['sticker_image']) ? count($_POST['sticker_image']) : 0); $i++) {
                    $ductile_stickers[] = [
                        'label' => $_POST['sticker_label'][$i],
                        'url'   => $_POST['sticker_url'][$i],
                        'image' => $_POST['sticker_image'][$i],
                    ];
                }

                $order = [];
                if (!empty($_POST['order'])) {
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

                App::backend()->ductile_user = $ductile_user;

                // Save settings
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_style', serialize(App::backend()->ductile_user));
                App::blog()->settings()->themes->put(App::blog()->settings()->system->theme . '_stickers', serialize(App::backend()->ductile_stickers));

                // Blog refresh
                App::blog()->triggerBlog();

                // Template cache reset
                App::cache()->emptyTemplatesCache();

                App::backend()->notices()->addSuccessNotice(__('Theme configuration upgraded.'));
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

        $logo_src = is_string($logo_src = App::backend()->ductile_user['logo_src']) && $logo_src !== '' ? $logo_src : My::fileURL('img/logo-ductile.svg');

        // Helpers

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
                                (new Hidden(['dynorder[]', 'dynorder-' . $i], (string) $i)),
                            ]),
                        (new Td())
                            ->items([
                                (new Hidden(['sticker_image[]'], $v['image'])),
                                (new Img(My::fileURL('img/' . $v['image'])))
                                    ->width(42)
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

        echo (new Set())
            ->items([
                (new Fieldset())
                    ->legend((new Legend(__('Logo'))))
                    ->items([
                        (new Para())
                            ->items([
                                (new Img('user_image_src'))
                                    ->id('user_image_src')
                                    ->class('header-image')
                                    ->src($logo_src)
                                    ->alt(__('Image URL:')),
                            ]),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                (new Button('user_image_selector', __('Change')))
                                    ->type('button')
                                    ->id('user_image_selector'),
                                (new Button('user_image_reset', __('Reset')))
                                    ->class('delete')
                                    ->type('button')
                                    ->id('user_image_reset'),
                            ]),
                        (new Hidden('user_image', $logo_src)),
                        (new Input('theme-url'))
                            ->type('hidden')
                            ->value(My::fileURL('')),
                    ]),
                (new Fieldset())
                    ->legend((new Legend(__('Stickers'))))
                    ->items([
                        (new Div())
                            ->class('table-outer')
                            ->items([
                                (new Table())
                                    ->class('dragable')
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
                    ]),
            ])
        ->render();

        App::backend()->page()->helpBlock('ductile');
    }
}
