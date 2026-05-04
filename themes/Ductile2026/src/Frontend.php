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
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief   The module frontend process.
 * @ingroup ductile
 */
class Frontend
{
    use TraitProcess;

    /**
     * Init the process.
     */
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    /**
     * Processes
     */
    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        # Behaviors
        App::behavior()->addBehaviors([
            'publicHeadContent'  => self::publicHeadContent(...),
            'publicInsideFooter' => self::publicInsideFooter(...),
        ]);

        # Templates
        App::frontend()->template()->addValue('ductileLogoSrc', self::ductileLogoSrc(...));

        return true;
    }

    /**
     * Tpl:ductileLogoSrc template element
     */
    public static function ductileLogoSrc(): string
    {
        return '<?= ' . self::class . '::ductileLogoSrcHelper() ?>';
    }

    /**
     * Helper for Tpl:ductileLogoSrc
     */
    public static function ductileLogoSrcHelper(): string
    {
        $img_url = My::fileURL('img/logo-ductile.svg');

        $theme = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        if ($theme !== '') {
            $style = App::blog()->settings()->themes->get($theme . '_style');
            if (is_array($style) && isset($style['logo_src']) && $style['logo_src'] && is_string($style['logo_src'])) {
                $scheme = is_string($scheme = parse_url($style['logo_src'], PHP_URL_SCHEME)) ? $scheme : '';
                if ($scheme !== '') {
                    // Return complete URL which includes scheme
                    $img_url = $style['logo_src'];
                } else {
                    // Return theme resource URL
                    $img_url = My::fileURL($style['logo_src']);
                }
            }
        }

        return $img_url;
    }

    /**
     * Public inside footer behavior callback
     */
    public static function publicInsideFooter(): void
    {
        $items   = [];
        $img_url = My::fileURL('img/');

        $theme = is_string($theme = App::blog()->settings()->system->theme) ? $theme : '';
        if ($theme !== '') {
            /**
             * @var array<array{label: string, url: string, image: string}>
             */
            $stickers = is_array($stickers = App::blog()->settings()->themes->get($theme . '_stickers')) ? $stickers : [];
            if ($stickers !== []) {
                $stickers = array_filter($stickers, self::cleanStickers(...));
                if ($stickers !== []) {
                    $count = 1;
                    foreach ($stickers as $sticker) {
                        $items[] = self::setSticker(
                            $count,
                            ($count === count($stickers)),
                            $sticker['label'],
                            $sticker['url'],
                            $img_url . $sticker['image']
                        );
                        $count++;
                    }
                }
            }

            if ($items === []) {
                $items[] = self::setSticker(
                    1,
                    true,
                    __('Subscribe'),
                    App::blog()->url() . App::url()->getURLFor('feed', 'atom'),
                    $img_url . 'sticker-feed.svg'
                );
            }

            echo (new Ul())
                ->id('stickers')
                ->items($items)
            ->render();
        }
    }

    /**
     * Check if a sticker is fully defined
     *
     * @param      array<string, mixed>  $s      sticker properties
     */
    protected static function cleanStickers(array $s): bool
    {
        $check = fn (string $key): bool => isset($s[$key]) && is_string($s[$key]);

        return $check('label') && $check('url') && $check('image');
    }

    /**
     * Sets the sticker.
     *
     * @param      int          $position  The position
     * @param      bool         $last      The last
     * @param      string       $label     The label
     * @param      string       $url       The url
     * @param      string       $image     The image
     *
     * @return     Li       sticker
     */
    protected static function setSticker(int $position, bool $last, ?string $label = '', string $url = '', string $image = ''): Li
    {
        return (new Li())
            ->id('sticker' . $position)
            ->class($last ? 'last' : '')
            ->items([
                (new Link())
                    ->href($url)
                    ->items([
                        (new Img($image))
                            ->alt(''),
                        (new Span($label)),
                    ]),
            ]);
    }

    /**
     * Public head content behavior callback
     */
    public static function publicHeadContent(): void
    {
        echo
        My::jsLoad('ductile');
    }
}
