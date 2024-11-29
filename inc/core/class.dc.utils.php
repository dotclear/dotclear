<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */

use Dotclear\App;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text;

/**
 * Helpers handler.
 *
 * @deprecated dcUtils is deprecated since 2.28, use appropriate class and methods instead...
 */
class dcUtils
{
    /**
     * @deprecated  since 2.28, use App::lexical()::ADMIN_LOCALE instaed
     *
     * @var     string  ADMIN_LOCALE
     */
    public const ADMIN_LOCALE = 'admin';

    /**
     * @deprecated  since 2.28, use App::lexical()::PUBLIC_LOCALE instaed
     *
     * @var     string  PUBLIC_LOCALE
     */
    public const PUBLIC_LOCALE = 'public';

    /**
     * @deprecated  since 2.28, use App::lexical()::CUSTOM_LOCALE instaed
     *
     * @var     string  CUSTOM_LOCALE
     */
    public const CUSTOM_LOCALE = 'lang';

    /**
     * Make a path from a list of names.
     *
     * @deprecated  since 2.28, use Path::reduce() instead
     *
     * @param   array<string>   $elements   The elements
     * @param   string          $separator  The separator
     *
     * @return  string
     */
    public static function path(array $elements, string $separator = DIRECTORY_SEPARATOR): string
    {
        App::deprecated()->set('Path::reduce()', '2.28');

        return Path::reduce($elements, $separator);
    }

    /**
     * Build user's common name.
     *
     * @deprecated  since 2.28, use App::users()->getUserCN() instead
     *
     * @param   string  $user_id           The user identifier
     * @param   string  $user_name          The user name
     * @param   string  $user_firstname     The user firstname
     * @param   string  $user_displayname   The user displayname
     *
     * @return  string  The user cn.
     */
    public static function getUserCN(string $user_id, ?string $user_name, ?string $user_firstname, ?string $user_displayname): string
    {
        App::deprecated()->set('App::users()->getUserCN()', '2.28');

        return App::users()->getUserCN($user_id, $user_name, $user_firstname, $user_displayname);
    }

    /**
     * Cleanup a list of IDs.
     *
     * @deprecated  since 2.28, use App::blog()->cleanIds() instead
     *
     * @param   mixed   $ids    The identifiers
     *
     * @return  array<int,int>
     */
    public static function cleanIds($ids): array
    {
        App::deprecated()->set('App::blog()->cleanIds()', '2.28');

        return App::blog()->cleanIds($ids);
    }

    /**
     * Compare two versions with option of using only main numbers.
     *
     * @deprecated  since 2.28, use App::plugins()->versionsCompare() instead
     *
     * @param   string  $current_version    Current version
     * @param   string  $required_version   Required version
     * @param   string  $operator           Comparison operand
     * @param   bool    $strict             Use full version
     *
     * @return  bool    True if comparison success
     */
    public static function versionsCompare(string $current_version, string $required_version, string $operator = '>=', bool $strict = true): bool
    {
        App::deprecated()->set('Modules::versionCompare()', '2.28');

        return App::plugins()->versionsCompare($current_version, $required_version, $operator, $strict);
    }

    /**
     * Return a HTML CSS resource load (usually in HTML head)
     *
     * @deprecated  since 2.28, use App::plugins()->cssLoad() instead
     *
     * @param   string  $src        The source
     * @param   string  $media      The media
     * @param   string  $version    The version
     *
     * @return  string
     */
    public static function cssLoad(string $src, string $media = 'screen', ?string $version = null): string
    {
        App::deprecated()->set('Modules::cssLoad()', '2.28');

        return App::plugins()->cssLoad($src, $media, $version);
    }

    /**
     * @deprecated  since 2.27, use My::cssLoad() instead
     *
     * @param   string  $src        The source
     * @param   string  $media      The media
     * @param   string  $version    The version
     *
     * @return  string
     */
    public static function cssModuleLoad(string $src, string $media = 'screen', ?string $version = null): string
    {
        App::deprecated()->set('My::cssLoad()', '2.27');

        return App::plugins()->cssLoad(App::blog()->getPF($src), $media, $version);
    }

    /**
     * Return a HTML JS resource load (usually in HTML head).
     *
     * @deprecated  since 2.28, use App::plugins()->jsLoad() instead
     *
     * @param   string  $src        The source
     * @param   string  $version    The version
     * @param   bool    $module     Load source as JS module
     *
     * @return  string
     */
    public static function jsLoad(string $src, ?string $version = null, bool $module = false): string
    {
        App::deprecated()->set('Modules::jsLoad()', '2.28');

        return App::plugins()->jsLoad($src, $version, $module);
    }

    /**
     * @deprecated  since 2.27, use My::jsLoad() instead
     *
     * @param   string  $src        The source
     * @param   string  $version    The version
     * @param   bool    $module     Load source as JS module
     *
     * @return  string
     */
    public static function jsModuleLoad(string $src, ?string $version = null, bool $module = false): string
    {
        App::deprecated()->set('My::jsLoad()', '2.27');

        return App::plugins()->jsLoad(App::blog()->getPF($src), $version);
    }

    /**
     * Return a list of javascript variables définitions code.
     *
     * @deprecated  since 2.15, use Html::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript instead
     *
     * @param   array<string, mixed>   $vars   The variables
     *
     * @return  string  javascript code (inside <script… ></script>)
     */
    public static function jsVars(array $vars): string
    {
        App::deprecated()->set('Html::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        $ret = '<script>' . "\n";
        foreach ($vars as $var => $value) {
            $ret .= 'var ' . $var . ' = ' . (is_string($value) ? '"' . Html::escapeJS($value) . '"' : $value) . ';' . "\n";
        }
        $ret .= "</script>\n";

        return $ret;
    }

    /**
     * Return a javascript variable definition line code.
     *
     * @deprecated  since 2.15, use Html::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript instead
     *
     * @param   string  $name   variable name
     * @param   mixed   $value  value
     *
     * @return  string  javascript code
     */
    public static function jsVar(string $name, $value): string
    {
        App::deprecated()->set('Html::jsJson() and dotclear.getData()/dotclear.mergeDeep() in javascript', '2.15');

        return self::jsVars([$name => $value]);
    }

    /**
     * Return a list of variables into a HML script (application/json) container.
     *
     * @deprecated  since 2.28, use Html::jsJson() instead
     *
     * @param   string  $id     The identifier
     * @param   mixed   $vars   The variables
     *
     * @return  string
     */
    public static function jsJson(string $id, $vars): string
    {
        App::deprecated()->set('Html::jsJson()', '2.28');

        return Html::jsJson($id, $vars);
    }

    /**
     * Locale specific array sorting function.
     *
     * @deprecated  since 2.28, use App:lexical()->lexicalSort() instead
     *
     * @param   array<string>   $arr        single array of strings
     * @param   string          $namespace  admin/public/lang
     * @param   string          $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public static function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        App::deprecated()->set('App::lexical()->lexicalSort()', '2.28');

        return App::lexical()->lexicalSort($arr, $namespace, $lang);
    }

    /**
     * Locale specific array sorting function (preserving keys).
     *
     * @deprecated  since 2.28, use App:lexical()->lexicalArraySort() instead
     *
     * @param   array<string, string>   $arr        single array of strings
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public static function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        App::deprecated()->set('App::lexical()->lexicalArraySort()', '2.28');

        return App::lexical()->lexicalArraySort($arr, $namespace, $lang);
    }

    /**
     * Locale specific array sorting function (sorting keys).
     *
     * @deprecated  since 2.28, use App:lexical()->lexicalKeySort() instead
     *
     * @param   array<string>   $arr        single array of strings
     * @param   string          $namespace  admin/public/lang
     * @param   string          $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     *
     * @phpstan-param-out array<string, mixed> $arr
     */
    public static function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        App::deprecated()->set('App::lexical()->lexicalKeySort()', '2.28');

        return App::lexical()->lexicalKeySort($arr, $namespace, $lang);
    }

    /**
     * Sets the lexical language.
     *
     * @deprecated  since 2.28, use App:lexical()->setLexicalLang() instead
     *
     * @param   string  $namespace  The namespace (admin/public/lang)
     * @param   string  $lang       The language
     */
    public static function setLexicalLang(string $namespace = '', string $lang = 'en_US'): void
    {
        App::deprecated()->set('App::lexical()->setLexicalLang()', '2.28');

        App::lexical()->setLexicalLang($namespace, $lang);
    }

    /**
     * Removes diacritics from a string.
     *
     * Removes diacritics from strings containing Latin-1 Supplement, Latin Extended-A,
     * Latin Extended-B and Latin Extended Additional special characters.
     *
     * see https://github.com/infralabs/DiacriticsRemovePHP
     *
     * @deprecated  since 2.26, use Text::removeDiacritics() instead
     *
     * @param   string  $str    The string
     *
     * @return  string
     */
    public static function removeDiacritics(string $str): string
    {
        App::deprecated()->set('Text::removeDiacritics()', '2.26');

        return Text::removeDiacritics($str);
    }
}
