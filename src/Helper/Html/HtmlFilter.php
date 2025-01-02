<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Html;

use XMLParser;

/**
 * @class HtmlFilter
 *
 * HTML code filter
 *
 * This class removes all unwanted tags and attributes from an HTML string.
 *
 * This was inspired by Ulf Harnhammar's Kses (http://sourceforge.net/projects/kses)
 */
class HtmlFilter
{
    /**
     * Parser handle
     */
    private readonly XMLParser $parser;

    /**
     * HTML content
     *
     * @var string
     */
    public $content;

    /**
     * Current tag
     */
    private ?string $tag = null;

    /**
     * Constructs a new instance.
     *
     * @param      bool  $keep_aria  Keep aria attributes
     * @param      bool  $keep_data  Keep data elements
     * @param      bool  $keep_js    Keep javascript elements
     */
    public function __construct(bool $keep_aria = false, bool $keep_data = false, bool $keep_js = false)
    {
        $this->parser = xml_parser_create('UTF-8');
        if (version_compare(PHP_VERSION, '8.4.0', '<')) {
            xml_set_object($this->parser, $this); // No more needed with PHP 8.4
        }
        xml_set_element_handler(
            $this->parser,
            $this->tag_open(...),
            $this->tag_close(...)
        );
        xml_set_character_data_handler($this->parser, $this->cdata(...));
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, 0);

        $this->removeTags(
            'applet',
            'base',
            'basefont',
            'body',
            'center',
            'dir',
            'font',
            'frame',
            'frameset',
            'head',
            'html',
            'isindex',
            'link',
            'menu',
            'menuitem',
            'meta',
            'noframes',
            'script',
            'noscript',
            'style'
        );

        // Remove aria-* and data-* attributes if necessary (tidy extension does it, not ready for HTML5)
        if (!$keep_aria) {
            $this->removePatternAttributes('^aria-[\-\w]+$');
            $this->removeAttributes('role');
        }
        if (!$keep_data) {
            $this->removePatternAttributes('^data-[\-\w].*$');
        }

        if (!$keep_js) {
            // Remove events attributes
            $this->removeArrayAttributes($this->event_attrs);
            // Remove inline JS in URI
            $this->removeHosts('javascript');
        }
    }

    /**
     * Append hosts
     *
     * Appends hosts to remove from URI. Each method argument is a host. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeHosts('javascript');
     * ```
     *
     * @param      mixed  ...$args  The arguments
     */
    public function removeHosts(...$args): void
    {
        foreach ($this->argsArray([...$args]) as $host) {
            $this->removed_hosts[] = $host;
        }
    }

    /**
     * Append tags
     *
     * Appends tags to remove. Each method argument is a tag. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeTags('frame','script');
     * ```
     *
     * @param      mixed  ...$args  The arguments
     */
    public function removeTags(...$args): void
    {
        foreach ($this->argsArray([...$args]) as $tag) {
            $this->removed_tags[] = $tag;
        }
    }

    /**
     * Append attributes
     *
     * Appends attributes to remove. Each method argument is an attribute. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeAttributes('onclick','onunload');
     * ```
     *
     * @param      mixed  ...$args  The arguments
     */
    public function removeAttributes(...$args): void
    {
        foreach ($this->argsArray([...$args]) as $a) {
            $this->removed_attrs[] = $a;
        }
    }

    /**
     * Append array of attributes
     *
     * Appends attributes to remove. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeAttributes(['onload','onerror']);
     * ```
     *
     * @param      array<string>  $attrs  The attributes
     */
    public function removeArrayAttributes(array $attrs): void
    {
        foreach ($attrs as $a) {
            $this->removed_attrs[] = $a;
        }
    }

    /**
     * Append attribute patterns
     *
     * Appends attribute patterns to remove. Each method argument is an attribute pattern. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeAttributes('data-.*');
     * ```
     *
     * @param      mixed  ...$args  The arguments
     */
    public function removePatternAttributes(...$args): void
    {
        foreach ($this->argsArray([...$args]) as $a) {
            $this->removed_pattern_attrs[] = $a;
        }
    }

    /**
     * Append attributes for tags
     *
     * Appends attributes to remove from specific tags. Each method argument is
     * an array of tags with attributes. Example:
     *
     * ```php
     * $filter = new HtmlFilter();
     * $filter->removeTagAttributes(['a' => ['src','title']]);
     * ```
     *
     * @param      string  $tag         The tag
     * @param      mixed   ...$args     The arguments
     */
    public function removeTagAttributes(string $tag, ...$args): void
    {
        foreach ($this->argsArray([...$args]) as $a) {
            $this->removed_tag_attrs[$tag][] = $a;
        }
    }

    /**
     * Known tags
     *
     * Creates a list of known tags.
     *
     * @param array<string, array<string>>        $tags        Tags array
     */
    public function setTags(array $tags): void
    {
        $this->tags = $tags;
    }

    /**
     * Apply filter
     *
     * This method applies filter on given <var>$str</var> string. It will first
     * try to use tidy extension if exists and then apply the filter.
     *
     * @param string    $str        String to filter
     * @param boolean   $tidy       Use tidy extension if present
     *
     * @return string               Filtered string
     */
    public function apply(string $str, bool $tidy = true): string
    {
        if ($tidy && extension_loaded('tidy') && class_exists('tidy')) {
            $config = [
                'doctype'                     => 'strict',
                'drop-proprietary-attributes' => true,
                'escape-cdata'                => true,
                'indent'                      => false,
                'join-classes'                => false,
                'join-styles'                 => true,
                'lower-literals'              => true,
                'output-xhtml'                => true,
                'show-body-only'              => true,
                'wrap'                        => 80,
            ];

            $str = '<p>tt</p>' . $str; // Fixes a big issue

            $tidy = new \tidy();
            $tidy->parseString($str, $config, 'utf8');
            $tidy->cleanRepair();

            /* @phpstan-ignore-next-line */
            $str = (string) $tidy;

            $str = (string) preg_replace('#^<p>tt</p>\s?#', '', $str);  // @phpstan-ignore-line
        } else {
            $str = $this->miniTidy($str);
        }

        # Removing open comments, open CDATA and processing instructions
        $str = (string) preg_replace('%<!--.*?-->%msu', '', (string) $str);  // @phpstan-ignore-line
        $str = str_replace('<!--', '', $str);
        $str = (string) preg_replace('%<!\[CDATA\[.*?\]\]>%msu', '', $str);
        $str = str_replace('<![CDATA[', '', $str);

        # Transform processing instructions
        $str = str_replace('<?', '&gt;?', $str);
        $str = str_replace('?>', '?&lt;', $str);

        $str = Html::decodeEntities($str, true);

        $this->content = '';
        xml_parse($this->parser, '<all>' . $str . '</all>');

        return $this->content;
    }

    /**
     * Mini Tidy, used if tidy extension is not loaded (see above)
     *
     * @param      string  $str    The string
     */
    private function miniTidy(string $str): string
    {
        return (string) preg_replace_callback('%(<(?!(\s*?/|!)).*?>)%msu', $this->miniTidyFixTag(...), $str);
    }

    /**
     * Tag (with its attributes) helper for miniTidy(), see above
     *
     * @param      array<string>   $match  The match
     */
    private function miniTidyFixTag(array $match): string
    {
        return (string) preg_replace_callback('%(=")(.*?)(")%msu', $this->miniTidyFixAttr(...), $match[1]);
    }

    /**
     * Attribute (with its value) helper for miniTidyFixTag(), see above
     *
     * Escape entities in attributes value
     *
     * @param      array<string>   $match  The match
     */
    private function miniTidyFixAttr(array $match): string
    {
        return $match[1] . Html::escapeHTML(Html::decodeEntities($match[2])) . $match[3];
    }

    /**
     * Return a (almost) flatten and cleaned array
     *
     * @param      array<mixed>  $args   The arguments
     *
     * @return     array<mixed>
     */
    private function argsArray(array $args): array
    {
        $result = [];
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $result = [...$result, ...$arg];
            } else {
                $result[] = (string) $arg;
            }
        }

        return array_unique($result);
    }

    /**
     * xml_set_element_handler() open tag handler
     *
     * @param      mixed                    $parser  The parser (resource|XMLParser)
     * @param      string                   $tag     The tag
     * @param      array<string, mixed>     $attrs   The attributes
     */
    protected function tag_open($parser, string $tag, array $attrs): void
    {
        $this->tag = strtolower($tag);

        if ($this->tag === 'all') {
            return;
        }

        if ($this->allowedTag($this->tag)) {
            $this->content .= '<' . $tag . $this->getAttrs($tag, $attrs);

            if (in_array($this->tag, $this->single_tags)) {
                $this->content .= ' />';
            } else {
                $this->content .= '>';
            }
        }
    }

    /**
     * xml_set_element_handler() close tag handler
     *
     * @param      mixed                $parser  The parser (resource|XMLParser)
     * @param      string               $tag     The tag
     */
    protected function tag_close($parser, string $tag): void
    {
        if (!in_array($tag, $this->single_tags) && $this->allowedTag($tag)) {
            $this->content .= '</' . $tag . '>';
        }
    }

    /**
     * xml_set_character_data_handler() data handler
     *
     * @param      mixed                $parser  The parser (resource|XMLParser)
     * @param      string               $cdata   The cdata
     */
    protected function cdata($parser, string $cdata): void
    {
        $this->content .= Html::escapeHTML($cdata);
    }

    /**
     * Gets the allowed attributes.
     *
     * @param      string                   $tag    The tag
     * @param      array<string, mixed>     $attrs  The attributes
     *
     * @return     string  The attributes.
     */
    private function getAttrs(string $tag, array $attrs): string
    {
        $res = '';
        foreach ($attrs as $n => $v) {
            if ($this->allowedAttr($tag, $n)) {
                $res .= $this->getAttr($n, $v);
            }
        }

        return $res;
    }

    /**
     * Gets the attribute with its value.
     *
     * @param      string  $attr   The attribute
     * @param      string  $value  The value
     *
     * @return     string  The attribute.
     */
    private function getAttr(string $attr, string $value): string
    {
        $value = (string) preg_replace('/\xad+/', '', $value);

        if (in_array($attr, $this->uri_attrs)) {
            $value = $this->getURI($value);
        }

        return ' ' . $attr . '="' . Html::escapeHTML($value) . '"';
    }

    /**
     * Sanitize an URI value
     *
     * @param      string  $uri    The uri
     *
     * @return     string  The uri.
     */
    private function getURI(string $uri): string
    {
        // Trim URI
        $uri = trim($uri);
        // Remove escaped Unicode characters
        $uri = preg_replace('/\\\u[a-fA-F0-9]{4}/', '', $uri);
        // Sanitize and parse URL
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        if ($uri !== false) {
            $u = @parse_url($uri);
            if (is_array($u) && (empty($u['scheme']) || in_array($u['scheme'], $this->allowed_schemes)) && (empty($u['host']) || !in_array($u['host'], $this->removed_hosts))) {
                return $uri;
            }
        }

        return '#';
    }

    /**
     * Check if a tag is allowed
     *
     * @param      string  $tag    The tag
     */
    private function allowedTag(string $tag): bool
    {
        return !in_array($tag, $this->removed_tags) && isset($this->tags[$tag]);
    }

    /**
     * Check if a tag's attribute is allowed
     *
     * @param      string  $tag    The tag
     * @param      string  $attr   The attribute
     */
    private function allowedAttr(string $tag, string $attr): bool
    {
        if (in_array($attr, $this->removed_attrs)) {
            return false;
        }

        if (isset($this->removed_tag_attrs[$tag]) && in_array($attr, $this->removed_tag_attrs[$tag])) {
            return false;
        }

        // Check if:
        // - not in tag allowed attributes and
        // - not in allowed generic attributes and
        // - not in allowed event attributes and
        // - not in allowed grep attributes
        return isset($this->tags[$tag]) && !(!in_array($attr, $this->tags[$tag]) && !in_array($attr, $this->gen_attrs) && !in_array($attr, $this->event_attrs) && !$this->allowedPatternAttr($attr));
    }

    /**
     * Check if a tag's attribute is in allowed grep attributes
     *
     * @param      string  $attr   The attribute
     */
    private function allowedPatternAttr(string $attr): bool
    {
        foreach ($this->removed_pattern_attrs as $pattern) {
            if (preg_match('/' . $pattern . '/u', $attr)) {
                return false;
            }
        }
        foreach ($this->grep_attrs as $pattern) {
            if (preg_match('/' . $pattern . '/u', $attr)) {
                return true;
            }
        }

        return false;
    }

    /* Tags and attributes definitions
        * Source: https://developer.mozilla.org/fr/docs/Web/HTML/
       ------------------------------------------------------- */
    /**
     * Stack of removed tags
     *
     * @var array<string>
     */
    private array $removed_tags = [];

    /**
     * Stack of removed attributes
     *
     * @var array<string>
     */
    private array $removed_attrs = [];

    /**
     * Stack of removed attibutes (via pattern)
     *
     * @var array<string>
     */
    private array $removed_pattern_attrs = [];

    /**
     * Stack of removed tags' attributes
     *
     * @var array<string, array<string>>
     */
    private array $removed_tag_attrs = [];

    /**
     * Stack of removed hosts
     *
     * @var array<string>
     */
    private array $removed_hosts = [];

    /**
     * List of allowed schemes (URI)
     *
     * @var array<string>
     */
    private array $allowed_schemes = [
        'data',
        'http',
        'https',
        'ftp',
        'mailto',
        'news',
    ];

    /**
     * List of attributes which allow URI value
     *
     * @var array<string>
     */
    private array $uri_attrs = [
        'action',
        'background',
        'cite',
        'classid',
        'code',
        'codebase',
        'data',
        'download',
        'formaction',
        'href',
        'longdesc',
        'profile',
        'src',
        'usemap',
    ];

    /**
     * List of generic attributes
     *
     * @var array<string>
     */
    private array $gen_attrs = [
        'accesskey',
        'class',
        'contenteditable',
        'contextmenu',
        'dir',
        'draggable',
        'dropzone',
        'hidden',
        'id',
        'itemid',
        'itemprop',
        'itemref',
        'itemscope',
        'itemtype',
        'lang',
        'role',
        'slot',
        'spellcheck',
        'style',
        'tabindex',
        'title',
        'translate',
        'xml:base',
        'xml:lang', ];

    /**
     * List of events attributes
     *
     * @var array<string>
     */
    private array $event_attrs = [
        'onabort',
        'onafterprint',
        'onautocomplete',
        'onautocompleteerror',
        'onbeforeprint',
        'onbeforeunload',
        'onblur',
        'oncancel',
        'oncanplay',
        'oncanplaythrough',
        'onchange',
        'onclick',
        'onclose',
        'oncontextmenu',
        'oncuechange',
        'ondblclick',
        'ondrag',
        'ondragend',
        'ondragenter',
        'ondragexit',
        'ondragleave',
        'ondragover',
        'ondragstart',
        'ondrop',
        'ondurationchange',
        'onemptied',
        'onended',
        'onerror',
        'onfocus',
        'onhashchange',
        'oninput',
        'oninvalid',
        'onkeydown',
        'onkeypress',
        'onkeyup',
        'onlanguagechange',
        'onload',
        'onloadeddata',
        'onloadedmetadata',
        'onloadstart',
        'onmessage',
        'onmousedown',
        'onmouseenter',
        'onmouseleave',
        'onmousemove',
        'onmouseout',
        'onmouseover',
        'onmouseup',
        'onmousewheel',
        'onoffline',
        'ononline',
        'onpause',
        'onplay',
        'onplaying',
        'onpopstate',
        'onprogress',
        'onratechange',
        'onredo',
        'onreset',
        'onresize',
        'onscroll',
        'onseeked',
        'onseeking',
        'onselect',
        'onshow',
        'onsort',
        'onstalled',
        'onstorage',
        'onsubmit',
        'onsuspend',
        'ontimeupdate',
        'ontoggle',
        'onundo',
        'onunload',
        'onvolumechange',
        'onwaiting',
    ];

    /**
     * List of pattern'ed attributes
     *
     * @var array<string>
     */
    private array $grep_attrs = [
        '^aria-[\-\w]+$',
        '^data-[\-\w].*$',
    ];

    /**
     * List of single tags
     *
     * @var array<string>
     */
    private array $single_tags = [
        'area',
        'base',
        'basefont',
        'br',
        'col',
        'embed',
        'frame',
        'hr',
        'img',
        'input',
        'isindex',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    ];

    /**
     * List of tags and their attributes
     *
     * @var array<string, array<string>>
     */
    private array $tags = [
        // A
        'a' => ['charset', 'coords', 'download', 'href', 'hreflang', 'name', 'ping', 'referrerpolicy',
            'rel', 'rev', 'shape', 'target', 'type', ],
        'abbr'    => [],
        'acronym' => [],
        'address' => [],
        'applet'  => ['align', 'alt', 'archive', 'code', 'codebase', 'datafld', 'datasrc', 'height', 'hspace',
            'mayscript', 'name', 'object', 'vspace', 'width', ],
        'area' => ['alt', 'coords', 'download', 'href', 'name', 'media', 'nohref', 'referrerpolicy', 'rel',
            'shape', 'target', 'type', ],
        'article' => [],
        'aside'   => [],
        'audio'   => ['autoplay', 'buffered', 'controls', 'loop', 'muted', 'played', 'preload', 'src', 'volume'],
        // B
        'b'          => [],
        'base'       => ['href', 'target'],
        'basefont'   => ['color', 'face', 'size'],
        'bdi'        => [],
        'bdo'        => [],
        'big'        => [],
        'blockquote' => ['cite'],
        'body'       => ['alink', 'background', 'bgcolor', 'bottommargin', 'leftmargin', 'link', 'text', 'rightmargin',
            'text', 'topmargin', 'vlink', ],
        'br'     => ['clear'],
        'button' => ['autofocus', 'autocomplete', 'disabled', 'form', 'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'name', 'type', 'value'],
        // C
        'canvas'   => ['height', 'width'],
        'caption'  => ['align'],
        'center'   => [],
        'cite'     => [],
        'code'     => [],
        'col'      => ['align', 'bgcolor', 'char', 'charoff', 'span', 'valign', 'width'],
        'colgroup' => ['align', 'bgcolor', 'char', 'charoff', 'span', 'valign', 'width'],
        // D
        'data'     => ['value'],
        'datalist' => [],
        'dd'       => ['nowrap'],
        'del'      => ['cite', 'datetime'],
        'details'  => ['open'],
        'dfn'      => [],
        'dialog'   => ['open'],
        'dir'      => ['compact'],
        'div'      => ['align'],
        'dl'       => [],
        'dt'       => [],
        // E
        'em'    => [],
        'embed' => ['height', 'src', 'type', 'width'],
        // F
        'fieldset'   => ['disabled', 'form', 'name'],
        'figcaption' => [],
        'figure'     => [],
        'font'       => ['color', 'face', 'size'],
        'footer'     => [],
        'form'       => ['accept', 'accept-charset', 'action', 'autocapitalize', 'autocomplete', 'enctype', 'method',
            'name', 'novalidate', 'target', ],
        'frame'    => ['frameborder', 'marginheight', 'marginwidth', 'name', 'noresize', 'scrolling', 'src'],
        'frameset' => ['cols', 'rows'],
        // G
        // H
        'h1'   => ['align'],
        'h2'   => ['align'],
        'h3'   => ['align'],
        'h4'   => ['align'],
        'h5'   => ['align'],
        'h6'   => ['align'],
        'head' => ['profile'],
        'hr'   => ['align', 'color', 'noshade', 'size', 'width'],
        'html' => ['manifest', 'version', 'xmlns'],
        // I
        'i'      => [],
        'iframe' => ['align', 'allowfullscreen', 'allowpaymentrequest', 'frameborder', 'height', 'longdesc',
            'marginheight', 'marginwidth', 'name', 'referrerpolicy', 'sandbox', 'scrolling', 'src', 'srcdoc', 'width', ],
        'img' => ['align', 'alt', 'border', 'crossorigin', 'decoding', 'height', 'hspace', 'ismap', 'longdesc',
            'name', 'referrerpolicy', 'sizes', 'src', 'srcset', 'usemap', 'vspace', 'width', ],
        'input' => ['accept', 'alt', 'autocomplete', 'autofocus', 'capture', 'checked', 'disabled', 'form',
            'formaction', 'formenctype', 'formmethod', 'formnovalidate', 'formtarget', 'height', 'inputmode', 'ismap',
            'list', 'max', 'maxlength', 'min', 'minlength', 'multiple', 'name', 'pattern', 'placeholder', 'readonly',
            'required', 'selectionDirection', 'selectionEnd', 'selectionStart', 'size', 'spellcheck', 'src', 'step', 'type',
            'usemap', 'value', 'width', ],
        'ins'     => ['cite', 'datetime'],
        'isindex' => ['action', 'prompt'],
        // J
        // K
        'kbd'    => [],
        'keygen' => ['autofocus', 'challenge', 'disabled', 'form', 'keytype', 'name'],
        // L
        'label'  => ['for', 'form'],
        'legend' => [],
        'li'     => ['type', 'value'],
        'link'   => ['as', 'crossorigin', 'charset', 'disabled', 'href', 'hreflang', 'integrity', 'media', 'methods', 'prefetch', 'referrerpolicy', 'rel', 'rev', 'sizes', 'target', 'type'],
        // M
        'main'     => [],
        'map'      => ['name'],
        'mark'     => [],
        'menu'     => ['label', 'type'],
        'menuitem' => ['checked', 'command', 'default', 'disabled', 'icon', 'label', 'radiogroup', 'type'],
        'meta'     => ['charset', 'content', 'http-equiv', 'name', 'scheme'],
        'meter'    => ['form', 'high', 'low', 'max', 'min', 'optimum', 'value'],
        // N
        'nav'      => [],
        'noframes' => [],
        'noscript' => [],
        // O
        'object' => ['archive', 'border', 'classid', 'codebase', 'codetype', 'data', 'declare', 'form', 'height',
            'hspace', 'name', 'standby', 'type', 'typemustmatch', 'usemap', 'width', ],
        'ol'       => ['compact', 'reversed', 'start', 'type'],
        'optgroup' => ['disabled', 'label'],
        'option'   => ['disabled', 'label', 'selected', 'value'],
        'output'   => ['for', 'form', 'name'],
        // P
        'p'        => ['align'],
        'param'    => ['name', 'type', 'value', 'valuetype'],
        'picture'  => [],
        'pre'      => ['cols', 'width', 'wrap'],
        'progress' => ['max', 'value'],
        // Q
        'q' => ['cite'],
        // R
        'rp'   => [],
        'rt'   => [],
        'rtc'  => [],
        'ruby' => [],
        // S
        's'      => [],
        'samp'   => [],
        'script' => ['async', 'charset', 'crossorigin', 'defer', 'integrity', 'language', 'nomodule', 'nonce',
            'src', 'type', ],
        'section' => [],
        'select'  => ['autofocus', 'disabled', 'form', 'multiple', 'name', 'required', 'size'],
        'small'   => [],
        'source'  => ['media', 'sizes', 'src', 'srcset', 'type'],
        'span'    => [],
        'strike'  => [],
        'strong'  => [],
        'style'   => ['media', 'nonce', 'scoped', 'type'],
        'sub'     => [],
        'summary' => [],
        'sup'     => [],
        // T
        'table' => ['align', 'bgcolor', 'border', 'cellpadding', 'cellspacing', 'frame', 'rules', 'summary', 'width'],
        'tbody' => ['align', 'bgcolor', 'char', 'charoff', 'valign'],
        'td'    => ['abbr', 'align', 'axis', 'bgcolor', 'char', 'charoff', 'colspan', 'headers', 'nowrap',
            'rowspan', 'scope', 'valign', 'width', ],
        'template' => [],
        'textarea' => ['autocomplete', 'autofocus', 'cols', 'disabled', 'form', 'maxlength', 'minlength', 'name',
            'placeholder', 'readonly', 'rows', 'spellcheck', 'wrap', ],
        'tfoot' => ['align', 'bgcolor', 'char', 'charoff', 'valign'],
        'th'    => ['abbr', 'align', 'axis', 'bgcolor', 'char', 'charoff', 'colspan', 'headers', 'nowrap',
            'rowspan', 'scope', 'valign', 'width', ],
        'thead' => ['align', 'bgcolor', 'char', 'charoff', 'valign'],
        'time'  => ['datetime'],
        'title' => [],
        'tr'    => ['align', 'bgcolor', 'char', 'charoff', 'valign'],
        'track' => ['default', 'kind', 'label', 'src', 'srclang'],
        'tt'    => [],
        // U
        'u'  => [],
        'ul' => ['compact', 'type'],
        // V
        'var'   => [],
        'video' => ['autoplay', 'buffered', 'controls', 'crossorigin', 'height', 'loop', 'muted', 'played',
            'playsinline', 'preload', 'poster', 'src', 'width', ],
        // W
        'wbr' => [],
        // X
        // Y
        // Z
    ];
}
