<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Text;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\LexicalInterface;
use UnhandledMatchError;

/**
 * @brief   Lexical helper.
 *
 * @since   2.28, lexical features have been grouped in this class
 */
class Lexical implements LexicalInterface
{
    /**
     * Constructor.
     *
     * @param   AuthInterface   $auth   The authentication instance
     * @param   BlogInterface   $blog   The blog instance
     */
    public function __construct(
        protected AuthInterface $auth,
        protected BlogInterface $blog,
    ) {
    }

    /**
     * Locale specific array sorting function.
     *
     * @param   array<string>   $arr        single array of strings
     * @param   string          $namespace  admin/public/lang
     * @param   string          $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return usort($arr, $this->lexicalSortHelper(...));
    }

    /**
     * Locale specific array sorting function (preserving keys).
     *
     * @param   array<string, string>   $arr        single associative array of strings
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return uasort($arr, $this->lexicalSortHelper(...));
    }

    /**
     * Locale specific array sorting function (sorting keys).
     *
     * @param   array<string, mixed>    $arr        single associative array of values
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return uksort($arr, $this->lexicalSortHelper(...));
    }

    /**
     * Sets the lexical language.
     *
     * @param   string  $namespace  The namespace (admin/public/lang)
     * @param   string  $lang       The language
     */
    public function setLexicalLang(string $namespace = '', string $lang = 'en_US'): void
    {
        try {
            // Switch to appropriate locale depending on $ns
            match ($namespace) {
                // Set locale with user prefs
                self::ADMIN_LOCALE => setlocale(LC_COLLATE, $this->auth->getInfo('user_lang')),
                // Set locale with blog params
                self::PUBLIC_LOCALE => setlocale(LC_COLLATE, $this->blog->settings()->get('system')->get('lang') ?? $lang),
                // Set locale with arg
                self::CUSTOM_LOCALE => setlocale(LC_COLLATE, $lang),
            };
        } catch (UnhandledMatchError) {
        }
    }

    /**
     * Locale specific string comparison function.
     *
     * @param   string  $a          1st string
     * @param   string  $b          2nd string
     * @param   string  $namespace  The namespace (admin/public/lang)
     * @param   string  $lang       The language
     *
     * @return     int
     */
    public function lexicalCompare(string $a, string $b, string $namespace = '', string $lang = 'en_US'): int
    {
        $this->setLexicalLang($namespace, $lang);

        return self::lexicalSortHelper($a, $b);
    }

    /**
     * Callback helper for lexical sort.
     *
     * @param   mixed   $a
     * @param   mixed   $b
     *
     * @return  int
     */
    private function lexicalSortHelper($a, $b): int
    {
        return strcoll(strtolower(Text::removeDiacritics($a)), strtolower(Text::removeDiacritics($b)));
    }
}
