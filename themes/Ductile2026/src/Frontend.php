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

use ArrayObject;
use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\File\Files;

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
        App::frontend()->template()->addValue('ductileEntriesList', self::ductileEntriesList(...));
        App::frontend()->template()->addValue('ductileLogoSrc', self::ductileLogoSrc(...));

        return true;
    }

    /**
     * Tpl:ductileEntriesList template element
     *
     * @param      ArrayObject<string, string>  $attr   The attribute
     *
     * @return     string       rendered element
     */
    public static function ductileEntriesList(ArrayObject $attr): string
    {
        $tpl_path   = My::path() . '/tpl/';
        $list_types = ['title', 'short', 'full'];

        // Get all _entry-*.html in tpl folder of theme
        $list_types_templates = Files::scandir($tpl_path);
        foreach ($list_types_templates as $v) {
            if (preg_match('/^_entry\-(.*)\.html$/', $v, $m) && !in_array($m[1], $list_types)) {
                // template not already in full list
                $list_types[] = $m[1];
            }
        }

        $default = isset($attr['default']) ? trim($attr['default']) : 'short';
        $ret     = '<?php ' . "\n" .
        'switch (' . self::class . '::ductileEntriesListHelper(\'' . $default . '\')) {' . "\n";

        foreach ($list_types as $v) {
            $ret .= '   case \'' . $v . '\':' . "\n" .
            '?>' . "\n" .
            App::frontend()->template()->includeFile(['src' => '_entry-' . $v . '.html']) . "\n" .
                '<?php ' . "\n" .
                '       break;' . "\n";
        }

        return $ret . '}' . "\n" . '?>';
    }

    /**
     * Helper for Tpl:ductileEntriesList
     *
     * @param      string  $default  The default
     */
    public static function ductileEntriesListHelper(string $default): string
    {
        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_entries_lists');
        if ($s !== null) {
            if (is_array($s) && isset($s[App::url()->getType()])) {
                return $s[App::url()->getType()];
            }
        }

        return $default;
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

        $style = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_style');
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

        return $img_url;
    }

    /**
     * Public inside footer behavior callback
     */
    public static function publicInsideFooter(): void
    {
        $res     = '';
        $default = false;
        $img_url = My::fileURL('img/');

        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_stickers');

        if ($s === null) {
            $default = true;
        } else {
            if (!is_array($s)) {
                $default = true;
            } else {
                $s = array_filter($s, self::cleanStickers(...));
                if (count($s) === 0) {
                    $default = true;
                } else {
                    $count = 1;
                    foreach ($s as $sticker) {
                        $res .= self::setSticker($count, ($count === count($s)), $sticker['label'], $sticker['url'], $img_url . $sticker['image']);
                        $count++;
                    }
                }
            }
        }

        if ($default || $res === '') {
            $res = self::setSticker(1, true, __('Subscribe'), App::blog()->url() .
                App::url()->getURLFor('feed', 'atom'), $img_url . 'sticker-feed.svg');
        }

        if ($res !== '') {
            $res = '<ul id="stickers">' . "\n" . $res . '</ul>' . "\n";
            echo $res;
        }
    }

    /**
     * Check if a sticker is fully defined
     *
     * @param      array<string, mixed>  $s      sticker properties
     */
    protected static function cleanStickers(array $s): bool
    {
        return isset($s['label']) && isset($s['url']) && isset($s['image']) && $s['label'] != null && $s['url'] != null && $s['image'] != null;
    }

    /**
     * Sets the sticker.
     *
     * @param      int          $position  The position
     * @param      bool         $last      The last
     * @param      null|string  $label     The label
     * @param      null|string  $url       The url
     * @param      null|string  $image     The image
     *
     * @return     string       rendered sticker
     */
    protected static function setSticker(int $position, bool $last, ?string $label = '', ?string $url = '', ?string $image = ''): string
    {
        return '<li id="sticker' . $position . '"' . ($last ? ' class="last"' : '') . '>' . "\n" .
            '<a href="' . $url . '">' . "\n" .
            '<img alt="" src="' . $image . '">' . "\n" .
            '<span>' . $label . '</span>' . "\n" .
            '</a>' . "\n" .
            '</li>' . "\n";
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
