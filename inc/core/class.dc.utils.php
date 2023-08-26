<?php
/**
 * @brief Dotclear helper methods
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Helper\Text;

class dcUtils
{
    public const ADMIN_LOCALE  = 'admin';
    public const PUBLIC_LOCALE = 'public';
    public const CUSTOM_LOCALE = 'lang';

    /**
     * Make a path from a list of names
     *
     * The .. (parent folder) names will be reduce if possible by removing their's previous item
     * Ex: path(['main', 'sub', '..', 'inc']) will return 'main/inc'
     *
     * @param      array   $elements   The elements
     * @param      string  $separator  The separator
     *
     * @return     string
     */
    public static function path(array $elements, string $separator = DIRECTORY_SEPARATOR): string
    {
        // Flattened all elements in list
        $flatten = function (array $list) {
            $new = [];
            array_walk_recursive($list, function ($array) use (&$new) { $new[] = $array; });

            return $new;
        };
        $flat = $flatten($elements);

        if ($separator !== '') {
            // Explode all elements with given separator
            $list = [];
            foreach ($flat as $value) {
                array_push($list, ... explode($separator, $value));
            }
        } else {
            $list = $flat;
        }

        $table = [];
        foreach ($list as $element) {
            if ($element === '..' && count($table)) {
                array_pop($table);  // Remove previous element from $table
            } elseif ($element !== '.') {
                array_push($table, $element);   // Add element to $table
            }
        }

        return implode($separator, $table);
    }

    /**
     * Static function that returns user's common name given to his
     * <var>user_id</var>, <var>user_name</var>, <var>user_firstname</var> and
     * <var>user_displayname</var>.
     *
     * @param      string  $user_id           The user identifier
     * @param      string  $user_name         The user name
     * @param      string  $user_firstname    The user firstname
     * @param      string  $user_displayname  The user displayname
     *
     * @return     string  The user cn.
     */
    public static function getUserCN(string $user_id, ?string $user_name, ?string $user_firstname, ?string $user_displayname): string
    {
        if (!empty($user_displayname)) {
            return $user_displayname;
        }

        if (!empty($user_name)) {
            if (!empty($user_firstname)) {
                return $user_firstname . ' ' . $user_name;
            }

            return $user_name;
        } elseif (!empty($user_firstname)) {
            return $user_firstname;
        }

        return $user_id;
    }

    /**
     * Cleanup a list of IDs
     *
     * @param      mixed  $ids    The identifiers
     *
     * @return     array
     */
    public static function cleanIds($ids): array
    {
        $clean_ids = [];

        if (!is_array($ids) && !($ids instanceof ArrayObject)) {
            $ids = [$ids];
        }

        foreach ($ids as $id) {
            if (is_array($id) || ($id instanceof ArrayObject)) {
                $clean_ids = array_merge($clean_ids, self::cleanIds($id));
            } else {
                $id = abs((int) $id);

                if (!empty($id)) {
                    $clean_ids[] = $id;
                }
            }
        }

        return $clean_ids;
    }

    /**
     * Compare two versions with option of using only main numbers.
     *
     * @param  string    $current_version    Current version
     * @param  string    $required_version    Required version
     * @param  string    $operator            Comparison operand
     * @param  bool      $strict                Use full version
     *
     * @return bool      True if comparison success
     */
    public static function versionsCompare(string $current_version, string $required_version, string $operator = '>=', bool $strict = true): bool
    {
        if ($strict) {
            $current_version  = preg_replace('!-r(\d+)$!', '-p$1', $current_version);
            $required_version = preg_replace('!-r(\d+)$!', '-p$1', $required_version);
        } else {
            $current_version  = preg_replace('/^([0-9\.]+)(.*?)$/', '$1', $current_version);
            $required_version = preg_replace('/^([0-9\.]+)(.*?)$/', '$1', $required_version);
        }

        return (bool) version_compare($current_version, $required_version, $operator);
    }

    /**
     * Appends a version to a resource URL fragment.
     *
     * @param      string       $src      The source
     * @param      string       $version  The version
     *
     * @return     string
     */
    private static function appendVersion(string $src, ?string $version = ''): string
    {
        if (defined('DC_DEBUG') && DC_DEBUG) {
            return $src;
        }

        return $src .
            (strpos($src, '?') === false ? '?' : '&amp;') .
            'v=' . (defined('DC_DEV') && DC_DEV === true ? md5(uniqid()) : ($version ?: DC_VERSION));
    }

    /**
     * Return a HTML CSS resource load (usually in HTML head)
     *
     * @param      string       $src        The source
     * @param      string       $media      The media
     * @param      string       $version    The version
     *
     * @return     string
     */
    public static function cssLoad(string $src, string $media = 'screen', ?string $version = null): string
    {
        $escaped_src = Html::escapeHTML($src);
        if ($version !== null) {
            $escaped_src = dcUtils::appendVersion($escaped_src, $version);
        }

        return '<link rel="stylesheet" href="' . $escaped_src . '" type="text/css" media="' . $media . '" />' . "\n";
    }

    /**
     * @deprecated since 2.27 use My::cssLoad()
     *
     * @param      string       $src        The source
     * @param      string       $media      The media
     * @param      string       $version    The version
     *
     * @return     string
     */
    public static function cssModuleLoad(string $src, string $media = 'screen', ?string $version = null): string
    {
        dcDeprecated::set('My::cssLoad()', '2.27');

        return self::cssLoad(dcCore::app()->blog->getPF($src), $media, $version);
    }

    /**
     * Return a HTML JS resource load (usually in HTML head)
     *
     * @param      string       $src        The source
     * @param      string       $version    The version
     * @param      bool         $module     Load source as JS module
     *
     * @return     string
     */
    public static function jsLoad(string $src, ?string $version = null, bool $module = false): string
    {
        $escaped_src = Html::escapeHTML($src);
        if ($version !== null) {
            $escaped_src = dcUtils::appendVersion($escaped_src, $version);
        }

        return '<script ' . ($module ? 'type="module" ' : '') . 'src="' . $escaped_src . '"></script>' . "\n";
    }

    /**
     * @deprecated since 2.27 use My::jsLoad()
     *
     * @param      string       $src        The source
     * @param      string       $version    The version
     * @param      bool         $module     Load source as JS module
     *
     * @return     string
     */
    public static function jsModuleLoad(string $src, ?string $version = null, bool $module = false): string
    {
        dcDeprecated::set('My::jsLoad()', '2.27');

        return self::jsLoad(dcCore::app()->blog->getPF($src), $version);
    }

    /**
     * Return a list of javascript variables définitions code
     *
     * @deprecated 2.15 use dcUtils::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript
     *
     * @param      array  $vars   The variables
     *
     * @return     string  javascript code (inside <script… ></script>)
     */
    public static function jsVars(array $vars): string
    {
        dcDeprecated::set('dcUtils::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        $ret = '<script>' . "\n";
        foreach ($vars as $var => $value) {
            $ret .= 'var ' . $var . ' = ' . (is_string($value) ? '"' . Html::escapeJS($value) . '"' : $value) . ';' . "\n";
        }
        $ret .= "</script>\n";

        return $ret;
    }

    /**
     * Return a javascript variable definition line code
     *
     * @deprecated 2.15 use dcUtils::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript
     *
     * @param      string  $name       variable name
     * @param      mixed   $value      value
     *
     * @return     string  javascript code
     */
    public static function jsVar(string $name, $value): string
    {
        dcDeprecated::set('dcUtils::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        return dcUtils::jsVars([$name => $value]);
    }

    /**
     * Return a list of variables into a HML script (application/json) container
     *
     * @param      string  $id     The identifier
     * @param      mixed   $vars   The variables
     *
     * @return     string
     */
    public static function jsJson(string $id, $vars): string
    {
        // Use echo dcUtils::jsLoad(dcCore::app()->blog->getPF('util.js'));
        // to call the JS dotclear.getData() decoder in public mode
        return '<script type="application/json" id="' . Html::escapeHTML($id) . '-data">' . "\n" .
            json_encode($vars, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) . "\n" . '</script>';
    }

    /**
     * Locale specific array sorting function
     *
     * @param array $arr single array of strings
     * @param string $namespace admin/public/lang
     * @param string $lang language to be used if $ns = 'lang'
     *
     * @return bool
     */
    public static function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        dcUtils::setLexicalLang($namespace, $lang);

        return usort($arr, [self::class, 'lexicalSortHelper']);
    }

    /**
     * Locale specific array sorting function (preserving keys)
     *
     * @param array $arr single array of strings
     * @param string $namespace admin/public/lang
     * @param string $lang language to be used if $ns = 'lang'
     *
     * @return bool
     */
    public static function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        dcUtils::setLexicalLang($namespace, $lang);

        return uasort($arr, [self::class, 'lexicalSortHelper']);
    }

    /**
     * Locale specific array sorting function (sorting keys)
     *
     * @param array $arr single array of strings
     * @param string $namespace admin/public/lang
     * @param string $lang language to be used if $ns = 'lang'
     *
     * @return bool
     */
    public static function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        dcUtils::setLexicalLang($namespace, $lang);

        return uksort($arr, [self::class, 'lexicalSortHelper']);
    }

    /**
     * Sets the lexical language.
     *
     * @param      string  $namespace   The namespace (admin/public/lang)
     * @param      string  $lang        The language
     */
    public static function setLexicalLang(string $namespace = '', string $lang = 'en_US')
    {
        try {
            // Switch to appropriate locale depending on $ns
            match ($namespace) {
                // Set locale with user prefs
                self::ADMIN_LOCALE => setlocale(LC_COLLATE, dcCore::app()->auth->getInfo('user_lang')),
                // Set locale with blog params
                self::PUBLIC_LOCALE => setlocale(LC_COLLATE, dcCore::app()->blog->settings->system->lang),
                // Set locale with arg
                self::CUSTOM_LOCALE => setlocale(LC_COLLATE, $lang),
            };
        } catch (UnhandledMatchError) {
        }
    }

    /**
     * Callback helper for lexical sort
     *
     * @param      mixed  $a
     * @param      mixed  $b
     *
     * @return     int
     */
    private static function lexicalSortHelper($a, $b): int
    {
        return strcoll(strtolower(Text::removeDiacritics($a)), strtolower(Text::removeDiacritics($b)));
    }

    /**
     * Removes diacritics from a string.
     *
     * Removes diacritics from strings containing Latin-1 Supplement, Latin Extended-A,
     * Latin Extended-B and Latin Extended Additional special characters.
     *
     * see https://github.com/infralabs/DiacriticsRemovePHP
     *
     * @param      string  $str    The string
     *
     * @return     string
     *
     * @deprecated Since 2.26 Use Text::removeDiacritics() instead
     */
    public static function removeDiacritics(string $str): string
    {
        return Text::removeDiacritics($str);
    }

    /**
     * Empty templates cache directory
     */
    public static function emptyTemplatesCache(): void
    {
        if (defined('DC_TPL_CACHE') && is_dir(DC_TPL_CACHE . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER)) {
            Files::deltree(DC_TPL_CACHE . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER);
        }
    }
}
