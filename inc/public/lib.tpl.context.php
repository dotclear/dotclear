<?php
/**
 * @package Dotclear
 * @subpackage Public
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

class context
{
    public $stack = [];

    public function __set($name, $var)
    {
        if ($var === null) {
            $this->pop($name);
        } else {
            $this->stack[$name][] = &$var;
            if ($var instanceof record) {
                $this->stack['cur_loop'][] = &$var;
            }
        }
    }

    public function __get($name)
    {
        if (!isset($this->stack[$name])) {
            return;
        }

        $n = count($this->stack[$name]);
        if ($n > 0) {
            return $this->stack[$name][($n - 1)];
        }
    }

    public function exists($name)
    {
        return isset($this->stack[$name][0]);
    }

    public function pop($name)
    {
        if (isset($this->stack[$name])) {
            $v = array_pop($this->stack[$name]);
            if ($v instanceof record) {
                array_pop($this->stack['cur_loop']);
            }
            unset($v);
        }
    }

    # Loop position tests
    public function loopPosition($start, $length = null, $even = null, $modulo = null)
    {
        if (!$this->cur_loop) { // @phpstan-ignore-line
            return false;
        }

        $index = $this->cur_loop->index();
        $size  = $this->cur_loop->count();

        $test = false;
        if ($start >= 0) {
            $test = $index >= $start;
            if ($length !== null) {
                if ($length >= 0) {
                    $test = $test && $index < $start + $length;
                } else {
                    $test = $test && $index < $size + $length;
                }
            }
        } else {
            $test = $index >= $size + $start;
            if ($length !== null) {
                if ($length >= 0) {
                    $test = $test && $index < $size + $start + $length;
                } else {
                    $test = $test && $index < $size + $length;
                }
            }
        }

        if ($even !== null) {
            $test = $test && $index % 2 == $even;
        }

        if ($modulo !== null) {
            $test = $test && ($index % $modulo == 0);
        }

        return $test;
    }

    /**
    @deprecated since version 2.11 , use tpl_context::global_filters instead
     */
    public static function global_filter($str,
        $encode_xml, $remove_html, $cut_string, $lower_case, $upper_case, $encode_url, $tag = '')
    {
        return self::global_filters(
            $str,
            [0                => null,
                'encode_xml'  => $encode_xml,
                'remove_html' => $remove_html,
                'cut_string'  => $cut_string,
                'lower_case'  => $lower_case,
                'upper_case'  => ($upper_case == 1 ? 1 : 0),
                'capitalize'  => ($upper_case == 2 ? 1 : 0),
                'encode_url'  => $encode_url],
            $tag);
    }

    private static function default_filters($filter, $str, $arg)
    {
        switch ($filter) {
            case 'strip_tags':
                return self::strip_tags($str);

            case 'remove_html':
                return preg_replace('/\s+/', ' ', self::remove_html($str));

            case 'encode_xml':
            case 'encode_html':
                return self::encode_xml($str);

            case 'cut_string':
                return self::cut_string($str, (integer) $arg);

            case 'lower_case':
                return self::lower_case($str);

            case 'capitalize':
                return self::capitalize($str);

            case 'upper_case':
                return self::upper_case($str);

            case 'encode_url':
                return self::encode_url($str);
        }

        return $str;
    }

    public static function global_filters($str, $args, $tag = '')
    {
        $filters = [
            'strip_tags',                             // Removes HTML tags (mono line)
            'remove_html',                            // Removes HTML tags
            'encode_xml', 'encode_html',              // Encode HTML entities
            'cut_string',                             // Cut string (length in $args['cut_string'])
            'lower_case', 'capitalize', 'upper_case', // Case transformations
            'encode_url'                             // URL encode (as for insert in query string)
        ];

        $args[0] = &$str;

        # --BEHAVIOR-- publicBeforeContentFilter
        $res = $GLOBALS['core']->callBehavior('publicBeforeContentFilter', $GLOBALS['core'], $tag, $args);
        $str = $args[0];

        foreach ($filters as $filter) {
            # --BEHAVIOR-- publicContentFilter
            switch ($GLOBALS['core']->callBehavior('publicContentFilter', $GLOBALS['core'], $tag, $args, $filter)) {
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

        # --BEHAVIOR-- publicAfterContentFilter
        $res = $GLOBALS['core']->callBehavior('publicAfterContentFilter', $GLOBALS['core'], $tag, $args);
        $str = $args[0];

        return $str;
    }

    public static function encode_url($str)
    {
        return urlencode($str);
    }

    public static function cut_string($str, $l)
    {
        return text::cutString($str, $l);
    }

    public static function encode_xml($str)
    {
        return html::escapeHTML($str);
    }

    public static function remove_isolated_figcaption($str)
    {
        // When using remove_html() or stript_tags(), we may have remaining figcaption's text without any image/audio media
        // This function will remove those cases from string

        // <figure><img …><figcaption>isolated text</figcaption></figure>
        $str = preg_replace('/<figure[^>]*>([\t\n\r\s]*)(a[^>]*>)*<img[^>]*>([\t\n\r\s]*)(\/a[^>]*>)*([\t\n\r\s]*)<figcaption[^>]*>(.*?)<\/figcaption>([\t\n\r\s]*)<\/figure>/', '', $str);

        // <figure><figcaption>isolated text</figcaption><audio…>…</audio></figure>
        $str = preg_replace('/<figure[^>]*>([\t\n\r\s]*)<figcaption[^>]*>(.*)<\/figcaption>([\t\n\r\s]*)<audio[^>]*>(([\t\n\r\s]|.)*)<\/audio>([\t\n\r\s]*)<\/figure>/', '', $str);

        return $str;
    }

    public static function remove_html($str)
    {
        $str = self::remove_isolated_figcaption($str);

        return html::decodeEntities(html::clean($str));
    }

    public static function strip_tags($str)
    {
        $str = self::remove_isolated_figcaption($str);

        return trim(preg_replace('/ {2,}/', ' ', str_replace(["\r", "\n", "\t"], ' ', html::clean($str))));
    }

    public static function lower_case($str)
    {
        return mb_strtolower($str);
    }

    public static function upper_case($str)
    {
        return mb_strtoupper($str);
    }

    public static function capitalize($str)
    {
        if ($str != '') {
            $str[0] = mb_strtoupper($str[0]);
        }

        return $str;
    }

    public static function categoryPostParam(&$p)
    {
        $not = substr($p['cat_url'], 0, 1) == '!';
        if ($not) {
            $p['cat_url'] = substr($p['cat_url'], 1);
        }

        $p['cat_url'] = preg_split('/\s*,\s*/', $p['cat_url'], -1, PREG_SPLIT_NO_EMPTY);

        foreach ($p['cat_url'] as &$v) {
            if ($not) {
                $v .= ' ?not';
            }
            if ($GLOBALS['_ctx']->exists('categories') && preg_match('/#self/', $v)) {
                $v = preg_replace('/#self/', $GLOBALS['_ctx']->categories->cat_url, $v);
            } elseif ($GLOBALS['_ctx']->exists('posts') && preg_match('/#self/', $v)) {
                $v = preg_replace('/#self/', $GLOBALS['_ctx']->posts->cat_url, $v);
            }
        }
    }

    # Static methods for pagination
    public static function PaginationNbPages()
    {
        global $_ctx;

        if ($_ctx->pagination === null) {
            return false;
        }

        $nb_posts = $_ctx->pagination->f(0);
        if (($GLOBALS['core']->url->type == 'default') || ($GLOBALS['core']->url->type == 'default-page')) {
            $nb_pages = ceil(($nb_posts - $_ctx->nb_entry_first_page) / $_ctx->nb_entry_per_page + 1);
        } else {
            $nb_pages = ceil($nb_posts / $_ctx->nb_entry_per_page);
        }

        return $nb_pages;
    }

    public static function PaginationPosition($offset = 0)
    {
        if (isset($GLOBALS['_page_number'])) {
            $p = (integer) $GLOBALS['_page_number'];
        } else {
            $p = 1;
        }

        $p = $p + $offset;

        $n = self::PaginationNbPages();
        if (!$n) {
            return $p;
        }

        if ($p > $n || $p <= 0) {
            return 1;
        }

        return $p;
    }

    public static function PaginationStart()
    {
        if (isset($GLOBALS['_page_number'])) {
            return self::PaginationPosition() == 1;
        }

        return true;
    }

    public static function PaginationEnd()
    {
        if (isset($GLOBALS['_page_number'])) {
            return self::PaginationPosition() == self::PaginationNbPages();
        }

        return false;
    }

    public static function PaginationURL($offset = 0)
    {
        $args = $_SERVER['URL_REQUEST_PART'];

        $n = self::PaginationPosition($offset);

        $args = preg_replace('#(^|/)page/([0-9]+)$#', '', $args);

        $url = $GLOBALS['core']->blog->url . $args;

        if ($n > 1) {
            $url = preg_replace('#/$#', '', $url);
            $url .= '/page/' . $n;
        }

        # If search param
        if (!empty($_GET['q'])) {
            $s = strpos($url, '?') !== false ? '&amp;' : '?';
            $url .= $s . 'q=' . rawurlencode($_GET['q']);
        }

        return $url;
    }

    # Robots policy
    public static function robotsPolicy($base, $over)
    {
        $pol  = ['INDEX' => 'INDEX', 'FOLLOW' => 'FOLLOW', 'ARCHIVE' => 'ARCHIVE'];
        $base = array_flip(preg_split('/\s*,\s*/', $base));
        $over = array_flip(preg_split('/\s*,\s*/', $over));

        foreach ($pol as $k => &$v) {
            if (isset($base[$k]) || isset($base['NO' . $k])) {
                $v = isset($base['NO' . $k]) ? 'NO' . $k : $k;
            }
            if (isset($over[$k]) || isset($over['NO' . $k])) {
                $v = isset($over['NO' . $k]) ? 'NO' . $k : $k;
            }
        }

        if ($pol['ARCHIVE'] == 'ARCHIVE') {
            unset($pol['ARCHIVE']);
        }

        return implode(', ', $pol);
    }

    # Smilies static methods
    public static function getSmilies($blog)
    {
        $path = [];
        if (isset($GLOBALS['__theme'])) {
            $path[] = $GLOBALS['__theme'];
            if (isset($GLOBALS['__parent_theme'])) {
                $path[] = $GLOBALS['__parent_theme'];
            }
        }
        $path[]     = 'default';
        $definition = $blog->themes_path . '/%s/smilies/smilies.txt';
        $base_url   = $blog->settings->system->themes_url . '/%s/smilies/';

        $res = [];

        foreach ($path as $t) {
            if (file_exists(sprintf($definition, $t))) {
                $base_url = sprintf($base_url, $t);

                return self::smiliesDefinition(sprintf($definition, $t), $base_url);
            }
        }

        return false;
    }

    public static function smiliesDefinition($f, $url)
    {
        $def = file($f);

        $res = [];
        foreach ($def as $v) {
            $v = trim($v);
            if (preg_match('|^([^\t\s]*)[\t\s]+(.*)$|', $v, $matches)) {
                $r = '/(\G|[\s]+|>)(' . preg_quote($matches[1], '/') . ')([\s]+|[<]|\Z)/ms';
                $s = '$1<img src="' . $url . $matches[2] . '" ' .
                    'alt="$2" class="smiley" />$3';
                $res[$r] = $s;
            }
        }

        return $res;
    }

    public static function addSmilies($str)
    {
        if (!isset($GLOBALS['__smilies']) || !is_array($GLOBALS['__smilies'])) {
            return $str;
        }

        # Process part adapted from SmartyPants engine (J. Gruber et al.) :

        $tokens = self::tokenizeHTML($str);
        $result = '';
        $in_pre = 0; # Keep track of when we're inside <pre> or <code> tags.

        foreach ($tokens as $cur_token) {
            if ($cur_token[0] == 'tag') {
                # Don't mess with quotes inside tags.
                $result .= $cur_token[1];
                if (preg_match('@<(/?)(?:pre|code|kbd|script|math)[\s>]@', $cur_token[1], $matches)) {
                    $in_pre = isset($matches[1]) && $matches[1] == '/' ? 0 : 1;
                }
            } else {
                $t = $cur_token[1];
                if (!$in_pre) {
                    $t = preg_replace(array_keys($GLOBALS['__smilies']), array_values($GLOBALS['__smilies']), $t);
                }
                $result .= $t;
            }
        }

        return $result;
    }

    private static function tokenizeHTML($str)
    {
        # Function from SmartyPants engine (J. Gruber et al.)
        #
        #   Parameter:  String containing HTML markup.
        #   Returns:    An array of the tokens comprising the input
        #               string. Each token is either a tag (possibly with nested,
        #               tags contained therein, such as <a href="<MTFoo>">, or a
        #               run of text between tags. Each element of the array is a
        #               two-element array; the first is either 'tag' or 'text';
        #               the second is the actual value.
        #
        #
        #   Regular expression derived from the _tokenize() subroutine in
        #   Brad Choate's MTRegex plugin.
        #   <http://www.bradchoate.com/past/mtregex.php>
        #
        $index  = 0;
        $tokens = [];

        $match = '(?s:<!(?:--.*?--\s*)+>)|' . # comment
        '(?s:<\?.*?\?>)|' . # processing instruction
        # regular tags
        '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';

        $parts = preg_split("{($match)}", $str, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $part) {
            if (++$index % 2 && $part != '') {
                $tokens[] = ['text', $part];
            } else {
                $tokens[] = ['tag', $part];
            }
        }

        return $tokens;
    }

    # First post image helpers
    public static function EntryFirstImageHelper($size, $with_category, $class = '', $no_tag = false, $content_only = false, $cat_only = false)
    {
        global $core, $_ctx;

        try {
            $media = new dcMedia($core);
            $sizes = implode('|', array_keys($media->thumb_sizes)) . '|o';
            if (!preg_match('/^' . $sizes . '$/', $size)) {
                $size = 's';
            }
            $p_url  = $core->blog->settings->system->public_url;
            $p_site = preg_replace('#^(.+?//.+?)/(.*)$#', '$1', $core->blog->url);
            $p_root = $core->blog->public_path;

            $pattern = '(?:' . preg_quote($p_site, '/') . ')?' . preg_quote($p_url, '/');
            $pattern = sprintf('/<img.+?src="%s(.*?\.(?:jpg|jpeg|gif|png|svg|webp))"[^>]+/msui', $pattern);

            $src = '';
            $alt = '';

            # We first look in post content
            if (!$cat_only && $_ctx->posts) {
                $subject = ($content_only ? '' : $_ctx->posts->post_excerpt_xhtml) . $_ctx->posts->post_content_xhtml;
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
            if (!$src && $with_category && $_ctx->posts->cat_desc) {
                if (preg_match_all($pattern, $_ctx->posts->cat_desc, $m) > 0) {
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
                };
            }

            if ($src) {
                if ($no_tag) {
                    return $src;
                }

                return '<img alt="' . $alt . '" src="' . $src . '" class="' . $class . '" />';
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    private static function ContentFirstImageLookup($root, $img, $size)
    {
        global $core;

        # Image extensions
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'wepb'];

        # Get base name and extension
        $info = path::info($img);
        $base = $info['base'];

        $res = false;

        try {
            $media = new dcMedia($core);
            $sizes = implode('|', array_keys($media->thumb_sizes));
            if (preg_match('/^\.(.+)_(' . $sizes . ')$/', $base, $m)) {
                $base = $m[1];
            }

            $res = false;
            if ($size != 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.jpg')) {
                $res = '.' . $base . '_' . $size . '.jpg';
            } elseif ($size != 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.png')) {
                $res = '.' . $base . '_' . $size . '.png';
            } elseif ($size != 'o' && file_exists($root . '/' . $info['dirname'] . '/.' . $base . '_' . $size . '.webp')) {
                $res = '.' . $base . '_' . $size . '.webp';
            } else {
                $f = $root . '/' . $info['dirname'] . '/' . $base;
                if (file_exists($f . '.' . $info['extension'])) {
                    $res = $base . '.' . $info['extension'];
                } else {
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
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }

        if ($res) {
            return $res;
        }

        return false;
    }
}
