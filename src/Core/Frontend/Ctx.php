<?php
/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Frontend;

use Dotclear\App;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Record;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text;
use Dotclear\Interface\Core\BlogInterface;
use Exception;

class Ctx
{
    /**
     * Stack of context variables
     *
     * @var        array<string, array<int, mixed>>
     */
    public $stack = [];

    /**
     * Set a context variable
     *
     * @param      string  $name   The name
     * @param      mixed   $var    The variable
     */
    public function __set(string $name, $var)
    {
        if ($var === null) {
            $this->pop($name);
        } else {
            $this->stack[$name][] = &$var;
            if ($var instanceof Record) {
                $this->stack['cur_loop'][] = new MetaRecord($var);
            } elseif ($var instanceof MetaRecord) {
                $this->stack['cur_loop'][] = &$var;
            }
        }
    }

    /**
     * Gets the last saved value of a context variable.
     *
     * @param      string  $name   The variable name
     *
     * @return     mixed
     */
    public function __get(string $name)
    {
        if (!isset($this->stack[$name])) {
            return;
        }

        // Return last saved value
        $count = count($this->stack[$name]);
        if ($count > 0) {
            return $this->stack[$name][$count - 1];
        }
    }

    /**
     * Check if a context variable exists
     *
     * @param      string  $name   The name
     *
     * @return     bool
     */
    public function exists(string $name): bool
    {
        return isset($this->stack[$name][0]);
    }

    /**
     * Pops the last saved value of a context variable.
     *
     * @param      string  $name   The name
     */
    public function pop(string $name): void
    {
        if (isset($this->stack[$name])) {
            $v = array_pop($this->stack[$name]);
            if (($v instanceof Record || $v instanceof MetaRecord) && isset($this->stack['cur_loop'])) {
                array_pop($this->stack['cur_loop']);
            }
            unset($v);
        }
    }

    // Loop position tests

    /**
     * Loop position helper
     *
     * @param      int       $start   The start
     * @param      int       $length  The length
     * @param      int       $even    The even
     * @param      int       $modulo  The modulo
     *
     * @return     bool
     */
    public function loopPosition(int $start, ?int $length = null, ?int $even = null, ?int $modulo = null): bool
    {
        if (!$this->cur_loop) {
            return false;
        }

        $index = $this->cur_loop->index();
        $size  = $this->cur_loop->count();

        $test = false;
        if ($start >= 0) {
            $test = ($index >= $start);
            if ($length !== null) {
                if ($length >= 0) {
                    $test = $test && $index < ($start + $length);
                } else {
                    $test = $test && $index < ($size + $length);
                }
            }
        } else {
            $test = $index >= ($size + $start);
            if ($length !== null) {
                if ($length >= 0) {
                    $test = $test && $index < ($size + $start + $length);
                } else {
                    $test = $test && $index < ($size + $length);
                }
            }
        }

        if ($even !== null) {
            $test = $test && (($index % 2) === $even);
        }

        if ($modulo !== null) {
            $test = $test && (($index % $modulo) === 0);
        }

        return $test;
    }

    /**
     * @deprecated since version 2.11 , use Ctx::global_filters instead
     *
     * @param      string  $str         The string
     * @param      mixed   $enc_xml     The encode xml
     * @param      mixed   $rem_html    The rem html
     * @param      mixed   $cut_string  The cut string
     * @param      mixed   $lower_case  The lower case
     * @param      mixed   $upper_case  The upper case
     * @param      mixed   $enc_url     The encode url
     * @param      string  $tag         The tag
     *
     * @return     string
     */
    public static function global_filter(string $str, $enc_xml, $rem_html, $cut_string, $lower_case, $upper_case, $enc_url, $tag = '')
    {
        App::deprecated()->set('Ctx::global_filters()', '2.11');

        return self::global_filters(
            $str,
            [
                0             => null,      // Will receive the string to filter
                'encode_xml'  => $enc_xml,
                'remove_html' => $rem_html,
                'cut_string'  => $cut_string,
                'lower_case'  => $lower_case,
                'upper_case'  => ($upper_case == 1 ? 1 : 0),
                'capitalize'  => ($upper_case == 2 ? 1 : 0),
                'encode_url'  => $enc_url,
            ],
            $tag
        );
    }

    /**
     * Apply a filter on string
     *
     * @param string    $filter     The filter
     * @param string    $str        The string
     * @param mixed     $arg        The arguments (filter option as length, …)
     *
     * @return string
     */
    private static function default_filters($filter, string $str, $arg): string
    {
        return match ($filter) {
            // Remove HTML tags
            'strip_tags' => self::strip_tags($str),

            // Remove all HTML from string
            'remove_html' => (string) preg_replace('/\s+/', ' ', self::remove_html($str)),

            // Encode entities
            'encode_xml', 'encode_html' => self::encode_xml($str),

            // Cut string to specified length
            'cut_string' => self::cut_string($str, (int) $arg),

            // Lowercase string
            'lower_case' => self::lower_case($str),

            // Capitalize string
            'capitalize' => self::capitalize($str),

            // Uppercase string
            'upper_case' => self::upper_case($str),

            // Encode URL in string
            'encode_url' => self::encode_url($str),

            default => $str,
        };
    }

    /**
     * Apply all required filters on a string
     *
     * @param string                        $str    The string
     * @param array<int|string, mixed>      $args   The arguments containing required filter(s) to apply
     * @param string                        $tag    The tag
     *
     * @return string
     */
    public static function global_filters(?string $str, array $args, string $tag = ''): string
    {
        if ($str === null) {
            return '';
        }

        $filters = [
            'strip_tags',                             // Removes HTML tags (mono line)
            'remove_html',                            // Removes HTML tags
            'encode_xml', 'encode_html',              // Encode HTML entities
            'cut_string',                             // Cut string (length in $args['cut_string'])
            'lower_case', 'capitalize', 'upper_case', // Case transformations
            'encode_url',                             // URL encode (as for insert in query string)
        ];

        $args[0] = &$str;

        # --BEHAVIOR-- publicBeforeContentFilter -- string, array
        App::behavior()->callBehavior('publicBeforeContentFilterV2', $tag, $args);
        $str = $args[0];

        foreach ($filters as $filter) {
            # --BEHAVIOR-- publicContentFilter -- string, array, array<int,string>
            switch (App::behavior()->callBehavior('publicContentFilterV2', $tag, $args, $filter)) {
                case '1':
                    // 3rd party filter applied and must stop
                    break;
                case '0':
                default:
                    // 3rd party filter applied and should continue
                    // Apply default filter
                    if (isset($args[$filter]) && $args[$filter]) {
                        $str = self::default_filters($filter, $str, $args[$filter]);
                    }
            }
        }

        # --BEHAVIOR-- publicAfterContentFilter -- string, array
        App::behavior()->callBehavior('publicAfterContentFilterV2', $tag, $args);

        return $args[0];
    }

    /**
     * Encode URL in a string
     *
     * @param string    $str    The string
     *
     * @return string
     */
    public static function encode_url(string $str): string
    {
        return urlencode($str);
    }

    /**
     * Cut a string to the specified length
     *
     * @param string    $str        The string
     * @param int       $length     The length
     *
     * @return string
     */
    public static function cut_string(string $str, int $length): string
    {
        return Text::cutString($str, $length);
    }

    /**
     * Encode HTML entities in a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function encode_xml(string $str): string
    {
        return Html::escapeHTML($str);
    }

    /**
     * Remove potentially isolated figcaption's text from a string
     *
     * When using remove_html() or stript_tags(), we may have remaining figcaption's text without any image/audio media
     * This function will remove those cases from string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function remove_isolated_figcaption(string $str): string
    {
        // <figure><img …><figcaption>isolated text</figcaption></figure>
        $ret = preg_replace('/<figure[^>]*>([\t\n\r\s]*)(<a[^>]*>)*<img[^>]*>([\t\n\r\s]*)(<\/a[^>]*>)*([\t\n\r\s]*)<figcaption[^>]*>(.*?)<\/figcaption>([\t\n\r\s]*)<\/figure>/', '', $str);
        if ($ret !== null) {
            $str = $ret;
        }

        // <figure><figcaption>isolated text</figcaption><audio…>…</audio></figure>
        $ret = preg_replace('/<figure[^>]*>([\t\n\r\s]*)<figcaption[^>]*>(.*)<\/figcaption>([\t\n\r\s]*)<audio[^>]*>(([\t\n\r\s]|.)*)<\/audio>([\t\n\r\s]*)<\/figure>/', '', $str);
        if ($ret !== null) {
            $str = $ret;
        }

        return $str;
    }

    /**
     * Remove HTML from a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function remove_html(string $str): string
    {
        $str = self::remove_isolated_figcaption($str);

        return Html::decodeEntities(Html::clean($str));
    }

    /**
     * Encode HTML tags from a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function strip_tags(string $str): string
    {
        $str = self::remove_isolated_figcaption($str);

        return trim((string) preg_replace('/ {2,}/', ' ', str_replace(["\r", "\n", "\t"], ' ', Html::clean($str))));
    }

    /**
     * Lowercase a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function lower_case(string $str): string
    {
        return mb_strtolower($str);
    }

    /**
     * Uppercase a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function upper_case(string $str): string
    {
        return mb_strtoupper($str);
    }

    /**
     * Capitalize a string
     *
     * @param string    $str The string
     *
     * @return string
     */
    public static function capitalize(string $str): string
    {
        if ($str != '') {
            $str[0] = mb_strtoupper($str[0]);
        }

        return $str;
    }

    /**
     * Cope with cat_url argument
     *
     * @param array<string, mixed>     $args
     */
    public static function categoryPostParam(array &$args): void
    {
        $not = str_starts_with($args['cat_url'], '!');
        if ($not) {
            $args['cat_url'] = substr($args['cat_url'], 1);
        }

        $args['cat_url'] = preg_split('/\s*,\s*/', $args['cat_url'], -1, PREG_SPLIT_NO_EMPTY);

        if ($args['cat_url'] !== false) {
            $pattern = '/#self/';
            foreach ($args['cat_url'] as &$cat_url) {
                if ($not) {
                    $cat_url .= ' ?not';
                }
                if (App::frontend()->context()->exists('categories') && preg_match($pattern, $cat_url)) {
                    $cat_url = preg_replace($pattern, (string) App::frontend()->context()->categories->cat_url, $cat_url);
                } elseif (App::frontend()->context()->exists('posts') && preg_match($pattern, $cat_url)) {
                    $cat_url = preg_replace($pattern, (string) App::frontend()->context()->posts->cat_url, $cat_url);
                }
            }
        }
    }

    // Static methods for pagination

    /**
     * Return total number of pages depending on current URL type
     *
     * @return false|int
     */
    public static function PaginationNbPages()
    {
        if (App::frontend()->context()->pagination === null) {
            return false;
        }

        $nb_posts = App::frontend()->context()->pagination->f(0);
        if ((App::url()->getType() === 'default') || (App::url()->getType() === 'default-page')) {
            // Home page (not static)
            $nb_pages = (int) ceil(($nb_posts - App::frontend()->context()->nb_entry_first_page) / App::frontend()->context()->nb_entry_per_page + 1);
        } else {
            $nb_pages = (int) ceil($nb_posts / App::frontend()->context()->nb_entry_per_page);
        }

        return $nb_pages;
    }

    /**
     * Return current page number
     *
     * @param int   $offset     The offset
     *
     * @return int
     */
    public static function PaginationPosition(int $offset = 0): int
    {
        if (App::frontend()->getPageNumber() !== 0) {
            $current_page = App::frontend()->getPageNumber();
        } else {
            $current_page = 1;
        }

        $current_page = $current_page + $offset;

        $page_number = self::PaginationNbPages();
        if (!$page_number) {
            return $current_page;
        }

        if ($current_page > $page_number || $current_page <= 0) {
            // Outside range of pages
            return 1;
        }

        return $current_page;
    }

    /**
     * Check if the current page is the first one
     *
     * @return bool
     */
    public static function PaginationStart(): bool
    {
        if (App::frontend()->getPageNumber() !== 0) {
            return self::PaginationPosition() == 1;
        }

        return true;
    }

    /**
     * Check if the current page is the last one
     *
     * @return bool
     */
    public static function PaginationEnd(): bool
    {
        if (App::frontend()->getPageNumber() !== 0) {
            return self::PaginationPosition() == self::PaginationNbPages();
        }

        return false;
    }

    /**
     * Update the page number in the current requested URL
     *
     * If first page, remove it, else put /page/<page_number> in it.
     *
     * @param int   $offset     The offset
     *
     * @return string
     */
    public static function PaginationURL(int $offset = 0): string
    {
        $args = (string) $_SERVER['URL_REQUEST_PART'];
        $args = preg_replace('#(^|/)page/(\d+)$#', '', $args);

        $page_number = self::PaginationPosition($offset);
        $url         = App::blog()->url() . $args;
        if ($page_number > 1) {
            $url = preg_replace('#/$#', '', $url);
            $url .= '/page/' . $page_number;
        }

        // Cope with search param if any
        if (!empty($_GET['q'])) {
            $s = str_contains($url, '?') ? '&amp;' : '?';
            $url .= $s . 'q=' . rawurlencode($_GET['q']);
        }

        return $url;
    }

    /**
     * Return the robots policy
     *
     * @param null|string $base
     * @param null|string $over
     *
     * @return string
     */
    public static function robotsPolicy(?string $base, ?string $over): string
    {
        $policies = [
            'INDEX'   => 'INDEX',
            'FOLLOW'  => 'FOLLOW',
            'ARCHIVE' => 'ARCHIVE',
        ];

        $bases = preg_split('/\s*,\s*/', (string) $base);
        $overs = preg_split('/\s*,\s*/', (string) $over);

        $bases = $bases !== false ? array_flip($bases) : [];
        $overs = $overs !== false ? array_flip($overs) : [];

        foreach ($policies as $key => &$value) {
            if (isset($bases[$key]) || isset($bases['NO' . $key])) {
                $value = isset($bases['NO' . $key]) ? 'NO' . $key : $key;
            }
            if (isset($overs[$key]) || isset($overs['NO' . $key])) {
                $value = isset($overs['NO' . $key]) ? 'NO' . $key : $key;
            }
        }

        if ($policies['ARCHIVE'] === 'ARCHIVE') {
            // No need of ARCHIVE in robots policy, only NOARCHIVE
            unset($policies['ARCHIVE']);
        }

        return implode(', ', $policies);
    }

    // Smilies static methods

    /**
     * Get the smilies defined for a blog
     *
     * @param BlogInterface    $blog   The blog
     *
     * @return array<string, string>|false
     */
    public static function getSmilies(BlogInterface $blog)
    {
        $definitions = [];

        $paths = [];
        if (isset(App::frontend()->theme)) {
            $paths[] = App::frontend()->theme;
            if (isset(App::frontend()->parent_theme)) {
                $paths[] = App::frontend()->parent_theme;
            }
        }

        $definition_pattern = $blog->themesPath() . '/%s/smilies/smilies.txt';
        $base_url_pattern   = $blog->settings()->system->themes_url . '/%s/smilies/';

        foreach ($paths as $path) {
            $definition = sprintf($definition_pattern, $path);
            $base_url   = sprintf($base_url_pattern, $path);

            if (file_exists($definition)) {
                $definitions = self::smiliesDefinition($definition, $base_url);
            }
        }

        // Use default set
        $definition = __DIR__ . '/../smilies/smilies.txt';
        $base_url   = App::blog()->getQmarkURL() . 'pf=';

        if (file_exists($definition)) {
            return [...self::smiliesDefinition($definition, $base_url), ...$definitions];
        }

        return false;
    }

    /**
     * Read smilies definition from a file
     *
     * @param      string  $f      The file
     * @param      string  $url    The image base url
     *
     * @return     array<string, string>
     */
    public static function smiliesDefinition(string $f, string $url): array
    {
        $definitions = [];
        if ($smilies = file($f)) {
            foreach ($smilies as $smiley) {
                $smiley = trim($smiley);
                if (preg_match('|^([^\t\s]*)[\t\s]+(.*)$|', $smiley, $matches)) {
                    $smiley_code = '/(\G|[\s]+|>)(' . preg_quote($matches[1], '/') . ')([\s]+|[<]|\Z)/ms';
                    $smiley_img  = '$1<img src="' . $url . $matches[2] . '" ' .
                    'alt="$2" class="smiley">$3';
                    $definitions[$smiley_code] = $smiley_img;
                }
            }
        }

        return $definitions;
    }

    /**
     * Replace textual smilies in string by their image representation
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public static function addSmilies(string $str): string
    {
        if (!isset(App::frontend()->smilies)) {
            return $str;
        }

        // Process part adapted from SmartyPants engine (J. Gruber et al.) :

        $tokens = self::tokenizeHTML($str);
        $result = '';
        $in_pre = false; // Keep track of when we're inside <pre>, <code>, ... tags.

        foreach ($tokens as $cur_token) {
            if ($cur_token[0] === 'tag') {
                // Don't mess with quotes inside tags.
                $result .= $cur_token[1];
                if (preg_match('@<(/?)(?:pre|code|kbd|script|math)[\s>]@', $cur_token[1], $matches)) {
                    $in_pre = $matches[1] === '/';
                }
            } else {
                $text = $cur_token[1];
                if (App::frontend()->smilies && !$in_pre) {
                    // Not inside a pre/code, replace smileys
                    $text = preg_replace(
                        array_keys(App::frontend()->smilies),
                        array_values(App::frontend()->smilies),
                        $text
                    );
                }
                $result .= $text;
            }
        }

        return $result;
    }

    /**
     * Get HTML tokens from a string
     *
     * Function from SmartyPants engine (J. Gruber et al.)
     *
     *   Returns:    An array of the tokens comprising the input
     *               string. Each token is either a tag (possibly with nested,
     *               tags contained therein, such as \<a href="...">, or a
     *               run of text between tags. Each element of the array is a
     *               two-element array; the first is either 'tag' or 'text';
     *               the second is the actual value.
     *
     *   Regular expression derived from the _tokenize() subroutine in
     *   Brad Choate's MTRegex plugin.
     *   <http://www.bradchoate.com/past/mtregex.php>
     *
     * @param      string  $str    The HTML string
     *
     * @return     array<int, array{0:string, 1:string}>
     */
    private static function tokenizeHTML(string $str): array
    {
        $index  = 0;
        $tokens = [];

        $match = '(?s:<!(?:--.*?--\s*)+>)|' .   // comment
        '(?s:<\?.*?\?>)|' .                     // processing instruction
                                                // regular tags
        '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';

        $parts = preg_split("{($match)}", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts !== false) {
            foreach ($parts as $part) {
                if (++$index % 2 && $part != '') {
                    $tokens[] = ['text', $part];
                } else {
                    $tokens[] = ['tag', $part];
                }
            }
        }

        return $tokens;
    }

    // First post image helpers

    /**
     * Search an image in post content
     *
     * The search of a requested size is done from this size up to and including the original size, in ascending order
     *
     * @param      string  $size           The size
     * @param      bool    $with_category  Also search in category description if no image in content
     * @param      string  $class          The CSS class to apply to found image
     * @param      bool    $no_tag         Return only the found image URI if true
     * @param      bool    $content_only   Only content only, else search in excerpt too
     * @param      bool    $cat_only       Search only in category description
     *
     * @return     string
     */
    public static function EntryFirstImageHelper(string $size, bool $with_category, string $class = '', bool $no_tag = false, bool $content_only = false, bool $cat_only = false): string
    {
        try {
            $media = App::media();
            $sizes = implode('|', array_keys($media->getThumbSizes())) . '|o';
            if (!preg_match('/^' . $sizes . '$/', $size)) {
                $size = 's';
            }
            $p_url  = App::blog()->settings()->system->public_url;
            $p_site = (string) preg_replace('#^(.+?//.+?)/(.*)$#', '$1', App::blog()->url());
            $p_root = App::blog()->publicPath();

            $pattern = '(?:' . preg_quote($p_site, '/') . ')?' . preg_quote($p_url, '/');
            $pattern = sprintf('/<img.+?src="%s(.*?\.(?:jpg|jpeg|gif|png|svg|webp|avif))"[^>]+/msui', $pattern);

            $src = '';
            $alt = '';

            # We first look in post content
            if (!$cat_only && App::frontend()->context()->posts) {
                $subject = ($content_only ? '' : App::frontend()->context()->posts->post_excerpt_xhtml) . App::frontend()->context()->posts->post_content_xhtml;
                if (preg_match_all($pattern, $subject, $m) > 0) {
                    foreach ($m[1] as $i => $img) {
                        if (($src = self::ContentFirstImageLookup($p_root, $img, $size)) !== false) {
                            $dirname = str_replace('\\', '/', dirname($img));
                            $src     = $p_url . ($dirname != '/' ? $dirname : '') . '/' . $src;
                            if (preg_match('/alt="([^"]+)"/', $m[0][$i], $malt)) {
                                $alt = $malt[1];
                            }

                            break;
                        }
                    }
                }
            }

            # No src, look in category description if available
            if (!$src && $with_category && App::frontend()->context()->posts->cat_desc) {
                if (preg_match_all($pattern, (string) App::frontend()->context()->posts->cat_desc, $m) > 0) {
                    foreach ($m[1] as $i => $img) {
                        if (($src = self::ContentFirstImageLookup($p_root, $img, $size)) !== false) {
                            $dirname = str_replace('\\', '/', dirname($img));
                            $src     = $p_url . ($dirname != '/' ? $dirname : '') . '/' . $src;
                            if (preg_match('/alt="([^"]+)"/', $m[0][$i], $malt)) {
                                $alt = $malt[1];
                            }

                            break;
                        }
                    }
                }
            }

            if ($src) {
                if ($no_tag) {
                    return $src;
                }

                return '<img alt="' . $alt . '" src="' . $src . '" class="' . $class . '">';
            }
        } catch (Exception) {
            // Ignore exception as it is not important not finding any image in content in a public context
        }

        // Nothing found
        return '';
    }

    /**
     * Search an existing thumbnail image (according to the requested size) of an image
     *
     * @param      string       $root   The root
     * @param      string       $img    The image
     * @param      string       $size   The size
     *
     * @return     false|string
     */
    private static function ContentFirstImageLookup(string $root, string $img, string $size)
    {
        # Image extensions
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'wepb', 'avif'];

        # Get base name and extension
        $info = Path::info($img);
        $base = $info['base'];

        $res = false;

        try {
            $media        = App::media();
            $sizes        = implode('|', array_keys($media->getThumbSizes()));
            $thumb_prefix = App::media()->getThumbnailPrefix();
            if ($thumb_prefix !== '.') {
                // Exclude . (hidden files) and prefixed thumbnails
                $pattern_prefix = sprintf('(\.|%s)', preg_quote($thumb_prefix));
            } else {
                // Exclude . (hidden files)
                $pattern_prefix = '\.';
            }
            if (preg_match('/^' . $pattern_prefix . '(.+)_(' . $sizes . ')$/', $base, $m)) {
                $base = $m[1];
            }

            $res = false;
            if ($size !== 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.jpg')) {
                // Found a JPG thumbnail
                $res = $thumb_prefix . $base . '_' . $size . '.jpg';
            } elseif ($size !== 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.png')) {
                // Found a PNG thumbnail
                $res = $thumb_prefix . $base . '_' . $size . '.png';
            } elseif ($size !== 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.webp')) {
                // Found a WEBP thumbnail
                $res = $thumb_prefix . $base . '_' . $size . '.webp';
            } elseif ($size !== 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.avif')) {
                // Found an AVIF thumbnail
                $res = $thumb_prefix . $base . '_' . $size . '.avif';
            } else {
                // Look for original size
                $f = $root . '/' . $info['dirname'] . '/' . $base;
                if (file_exists($f . '.' . $info['extension'])) {
                    // Original file found (same format)
                    $res = $base . '.' . $info['extension'];
                } else {
                    // Look for original files with other formats
                    foreach ($formats as $format) {
                        if (file_exists($f . '.' . $format)) {
                            $res = $base . '.' . $format;

                            break;
                        } elseif (file_exists($f . '.' . strtoupper($format))) {
                            $res = $base . '.' . strtoupper($format);

                            break;
                        }
                    }
                }
            }
        } catch (Exception) {
            // Ignore exception as it is not important not finding any image in content in a public context
        }

        if ($res) {
            return $res;
        }

        return false;
    }
}
