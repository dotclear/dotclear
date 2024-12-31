<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Theme\ductile;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;

/**
 * @brief   The module frontend process.
 * @ingroup ductile
 */
class Frontend extends Process
{
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
        App::frontend()->template()->addBlock('EntryIfContentIsCut', self::EntryIfContentIsCut(...));
        App::frontend()->template()->addValue('ductileNbEntryPerPage', self::ductileNbEntryPerPage(...));
        App::frontend()->template()->addValue('ductileLogoSrc', self::ductileLogoSrc(...));

        return true;
    }

    /**
     * Tpl:ductileNbEntryPerPage template element
     *
     * @param      ArrayObject<string, string>  $attr   The attribute
     *
     * @return     string       the rendered element
     */
    public static function ductileNbEntryPerPage(ArrayObject $attr): string
    {
        $nb = $attr['nb'] ?? null;

        return '<?php ' . self::class . '::ductileNbEntryPerPageHelper(' . strval((int) $nb) . '); ?>';
    }

    /**
     * Nb of entries per page helper (set the according ctx entry)
     *
     * @param      int   $nb     The number of entries
     */
    public static function ductileNbEntryPerPageHelper(int $nb): void
    {
        $nb_other = $nb_first = 0;

        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_entries_counts');
        if ($s !== null) {
            $s = @unserialize($s);
            if (is_array($s)) {
                switch (App::url()->getType()) {
                    case 'default':
                    case 'default-page':
                        if (isset($s['default'])) {
                            $nb_first = $nb_other = (int) $s['default'];
                        }
                        if (isset($s['default-page'])) {
                            $nb_other = (int) $s['default-page'];
                        }

                        break;
                    default:
                        if (isset($s[App::url()->getType()])) {
                            // Nb de billets par page défini par la config du thème
                            $nb_first = $nb_other = (int) $s[App::url()->getType()];
                        }

                        break;
                }
            }
        }

        if ($nb_other == 0 && $nb) {
            // Nb de billets par page défini par défaut dans le template
            $nb_other = $nb_first = $nb;
        }

        if ($nb_other > 0) {
            App::frontend()->context()->nb_entry_per_page = $nb_other;
        }
        if ($nb_first > 0) {
            App::frontend()->context()->nb_entry_first_page = $nb_first;
        }
    }

    /**
     * Tpl:EntryIfContentIsCut template block
     *
     * @param      ArrayObject<string, mixed>   $attr   The attribute
     * @param      string                       $content  The content
     *
     * @return     string       rendered element
     */
    public static function EntryIfContentIsCut(ArrayObject $attr, string $content): string
    {
        if (empty($attr['cut_string']) || !empty($attr['full'])) {
            return '';
        }

        $urls = '0';
        if (!empty($attr['absolute_urls'])) {
            $urls = '1';
        }

        $short              = App::frontend()->template()->getFilters($attr);
        $cut                = $attr['cut_string'];
        $attr['cut_string'] = 0;
        $full               = App::frontend()->template()->getFilters($attr);
        $attr['cut_string'] = $cut;

        return '<?php if (strlen(' . sprintf($full, 'App::frontend()->context()->posts->getContent(' . $urls . ')') . ') > ' .
        'strlen(' . sprintf($short, 'App::frontend()->context()->posts->getContent(' . $urls . ')') . ')) : ?>' .
            $content .
            '<?php endif; ?>';
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
            $s = @unserialize($s);
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
        $img_url = My::fileURL('img/logo.png');

        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_style');
        if ($s === null) {
            // no settings yet, return default logo
            return $img_url;
        }
        $s = @unserialize($s);
        if (!is_array($s)) {
            // settings error, return default logo
            return $img_url;
        }

        if (isset($s['logo_src']) && $s['logo_src']) {
            if ((str_starts_with((string) $s['logo_src'], '/')) || (parse_url((string) $s['logo_src'], PHP_URL_SCHEME) != '')) {
                // absolute URL
                $img_url = $s['logo_src'];
            } else {
                // relative URL (base = img folder of ductile theme)
                $img_url = My::fileURL('img/' . $s['logo_src']);
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
            $s = @unserialize($s);
            if (!is_array($s)) {
                $default = true;
            } else {
                $s = array_filter($s, self::cleanStickers(...));
                if (count($s) == 0) {
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
                App::url()->getURLFor('feed', 'atom'), $img_url . 'sticker-feed.png');
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
        '<style type="text/css">' . "\n" .
        '/* ' . __('Additionnal style directives') . ' */' . "\n" .
        self::ductileStyleHelper() .
        "</style>\n" .
        My::jsLoad('ductile') .
        self::ductileWebfontHelper();
    }

    /**
     * Set a selector property
     *
     * @param      array<string, array<string, mixed>>      $css       The css
     * @param      string                                   $selector  The selector
     * @param      string                                   $prop      The property
     * @param      mixed                                    $value     The value
     */
    public static function prop(array &$css, string $selector, string $prop, $value): void
    {
        if ($value) {
            $css[$selector][$prop] = $value;
        }
    }

    /**
     * Font helper
     */
    public static function ductileWebfontHelper(): string
    {
        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_style');

        if ($s === null) {
            return '';
        }

        $s = @unserialize($s);
        if (!is_array($s)) {
            return '';
        }

        $ret = '';
        $css = [];

        $uri = [];
        if (!isset($s['body_font']) || ($s['body_font'] == '') && isset($s['body_webfont_api']) && isset($s['body_webfont_family']) && isset($s['body_webfont_url'])) {
            // See if webfont defined for main font
            $uri[] = $s['body_webfont_url'];
            switch ($s['body_webfont_api']) {
                case 'js':
                    $ret .= sprintf('<script src="%s"></script>', $s['body_webfont_url']) . "\n";

                    break;
                case 'css':
                    $ret .= sprintf('<link type="text/css" href="%s" rel="stylesheet">', $s['body_webfont_url']) . "\n";

                    break;
            }
            # Main font
            $selectors = 'body, .supranav li a span, #comments.me, a.comment-number';
            self::prop($css, $selectors, 'font-family', $s['body_webfont_family']);
        }
        if (!isset($s['alternate_font']) || ($s['alternate_font'] == '') && isset($s['alternate_webfont_api']) && isset($s['alternate_webfont_family']) && isset($s['alternate_webfont_url'])) {
            // See if webfont defined for secondary font
            if (!in_array($s['alternate_webfont_url'], $uri)) {
                switch ($s['alternate_webfont_api']) {
                    case 'js':
                        $ret .= sprintf('<script src="%s"></script>', $s['alternate_webfont_url']) . "\n";

                        break;
                    case 'css':
                        $ret .= sprintf('<link type="text/css" href="%s" rel="stylesheet">', $s['alternate_webfont_url']) . "\n";

                        break;
                }
            }
            # Secondary font
            $selectors = '#blogdesc, .supranav, #content-info, #subcategories, #comments-feed, #sidebar h2, #sidebar h3, #footer';
            self::prop($css, $selectors, 'font-family', $s['alternate_webfont_family']);
        }
        # Style directives
        $res = '';
        foreach ($css as $selector => $values) {
            $res .= $selector . " {\n";
            foreach ($values as $k => $v) {
                $res .= $k . ':' . $v . ";\n";
            }
            $res .= "}\n";
        }
        if ($res !== '') {
            $ret .= '<style type="text/css">' . "\n" . $res . '</style>' . "\n";
        }

        return $ret;
    }

    /**
     * Style helper
     */
    public static function ductileStyleHelper(): string
    {
        $s = App::blog()->settings()->themes->get(App::blog()->settings()->system->theme . '_style');

        if ($s === null) {
            return '';
        }

        $s = @unserialize($s);
        if (!is_array($s)) {
            return '';
        }

        $css = [];

        # Properties

        # Blog description
        $selectors = '#blogdesc';
        if (isset($s['subtitle_hidden'])) {
            self::prop($css, $selectors, 'display', ($s['subtitle_hidden'] ? 'none' : null));
        }

        # Main font
        $selectors = 'body, .supranav li a span, #comments.me, a.comment-number';
        if (isset($s['body_font'])) {
            self::prop($css, $selectors, 'font-family', self::fontDef($s['body_font']));
        }

        # Secondary font
        $selectors = '#blogdesc, .supranav, #content-info, #subcategories, #comments-feed, #sidebar h2, #sidebar h3, #footer';
        if (isset($s['alternate_font'])) {
            self::prop($css, $selectors, 'font-family', self::fontDef($s['alternate_font']));
        }

        # Inside posts links font weight
        $selectors = '.post-excerpt a, .post-content a';
        if (isset($s['post_link_w'])) {
            self::prop($css, $selectors, 'font-weight', ($s['post_link_w'] ? 'bold' : 'normal'));
        }

        # Inside posts links colors (normal, visited)
        $selectors = '.post-excerpt a:link, .post-excerpt a:visited, .post-content a:link, .post-content a:visited';
        if (isset($s['post_link_v_c'])) {
            self::prop($css, $selectors, 'color', $s['post_link_v_c']);
        }

        # Inside posts links colors (hover, active, focus)
        $selectors = '.post-excerpt a:hover, .post-excerpt a:active, .post-excerpt a:focus, .post-content a:hover, .post-content a:active, .post-content a:focus';
        if (isset($s['post_link_f_c'])) {
            self::prop($css, $selectors, 'color', $s['post_link_f_c']);
        }

        # Style directives
        $res = '';
        foreach ($css as $selector => $values) {
            $res .= $selector . " {\n";
            foreach ($values as $k => $v) {
                $res .= $k . ':' . $v . ";\n";
            }
            $res .= "}\n";
        }

        # Large screens
        $css_large = [];

        # Blog title font weight
        $selectors = 'h1, h1 a:link, h1 a:visited, h1 a:hover, h1 a:visited, h1 a:focus';
        if (isset($s['blog_title_w'])) {
            self::prop($css_large, $selectors, 'font-weight', ($s['blog_title_w'] ? 'bold' : 'normal'));
        }

        # Blog title font size
        $selectors = 'h1';
        if (isset($s['blog_title_s'])) {
            self::prop($css_large, $selectors, 'font-size', $s['blog_title_s']);
        }

        # Blog title color
        $selectors = 'h1 a:link, h1 a:visited, h1 a:hover, h1 a:visited, h1 a:focus';
        if (isset($s['blog_title_c'])) {
            self::prop($css_large, $selectors, 'color', $s['blog_title_c']);
        }

        # Post title font weight
        $selectors = 'h2.post-title, h2.post-title a:link, h2.post-title a:visited, h2.post-title a:hover, h2.post-title a:visited, h2.post-title a:focus';
        if (isset($s['post_title_w'])) {
            self::prop($css_large, $selectors, 'font-weight', ($s['post_title_w'] ? 'bold' : 'normal'));
        }

        # Post title font size
        $selectors = 'h2.post-title';
        if (isset($s['post_title_s'])) {
            self::prop($css_large, $selectors, 'font-size', $s['post_title_s']);
        }

        # Post title color
        $selectors = 'h2.post-title a:link, h2.post-title a:visited, h2.post-title a:hover, h2.post-title a:visited, h2.post-title a:focus';
        if (isset($s['post_title_c'])) {
            self::prop($css_large, $selectors, 'color', $s['post_title_c']);
        }

        # Simple title color (title without link)
        $selectors = '#content-info h2, .post-title, .post h3, .post h4, .post h5, .post h6, .arch-block h3';
        if (isset($s['post_simple_title_c'])) {
            self::prop($css_large, $selectors, 'color', $s['post_simple_title_c']);
        }

        # Style directives for large screens
        if ($css_large !== []) {
            $res .= '@media only screen and (min-width: 481px) {' . "\n";
            foreach ($css_large as $selector => $values) {
                $res .= $selector . " {\n";
                foreach ($values as $k => $v) {
                    $res .= $k . ':' . $v . ";\n";
                }
                $res .= "}\n";
            }
            $res .= "}\n";
        }

        # Small screens
        $css_small = [];

        # Blog title font weight
        $selectors = 'h1, h1 a:link, h1 a:visited, h1 a:hover, h1 a:visited, h1 a:focus';
        if (isset($s['blog_title_w_m'])) {
            self::prop($css_small, $selectors, 'font-weight', ($s['blog_title_w_m'] ? 'bold' : 'normal'));
        }

        # Blog title font size
        $selectors = 'h1';
        if (isset($s['blog_title_s_m'])) {
            self::prop($css_small, $selectors, 'font-size', $s['blog_title_s_m']);
        }

        # Blog title color
        $selectors = 'h1 a:link, h1 a:visited, h1 a:hover, h1 a:visited, h1 a:focus';
        if (isset($s['blog_title_c_m'])) {
            self::prop($css_small, $selectors, 'color', $s['blog_title_c_m']);
        }

        # Post title font weight
        $selectors = 'h2.post-title, h2.post-title a:link, h2.post-title a:visited, h2.post-title a:hover, h2.post-title a:visited, h2.post-title a:focus';
        if (isset($s['post_title_w_m'])) {
            self::prop($css_small, $selectors, 'font-weight', ($s['post_title_w_m'] ? 'bold' : 'normal'));
        }

        # Post title font size
        $selectors = 'h2.post-title';
        if (isset($s['post_title_s_m'])) {
            self::prop($css_small, $selectors, 'font-size', $s['post_title_s_m']);
        }

        # Post title color
        $selectors = 'h2.post-title a:link, h2.post-title a:visited, h2.post-title a:hover, h2.post-title a:visited, h2.post-title a:focus';
        if (isset($s['post_title_c_m'])) {
            self::prop($css_small, $selectors, 'color', $s['post_title_c_m']);
        }

        # Style directives for small screens
        if ($css_small !== []) {
            $res .= '@media only screen and (max-width: 480px) {' . "\n";
            foreach ($css_small as $selector => $values) {
                $res .= $selector . " {\n";
                foreach ($values as $k => $v) {
                    $res .= $k . ':' . $v . ";\n";
                }
                $res .= "}\n";
            }
            $res .= "}\n";
        }

        return $res;
    }

    /**
     * Return CSS font family depending on given setting
     *
     * @param      mixed   $c      Font family
     */
    protected static function fontDef($c): ?string
    {
        $fonts = [
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

        return $fonts[$c] ?? null;
    }
}
