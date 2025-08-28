<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Text;
use Dotclear\Interface\Core\LexicalInterface;
use UnhandledMatchError;

/**
 * @brief   Lexical helper.
 *
 * @since   2.28, lexical features have been grouped in this class
 * @since   2.36, constructor arguments has been replaced by Core instance
 */
class Lexical implements LexicalInterface
{
    /**
     * Constructs a new instance.
     *
     * @param   Core    $core   The core container
     */
    public function __construct(
        protected Core $core
    ) {
    }

    /**
     * Locale specific array sorting function.
     *
     * @param   string[]        $arr        single array of strings
     * @param   string          $namespace  admin/public/lang
     * @param   string          $lang       language to be used if $ns = 'lang'
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
                self::ADMIN_LOCALE => setlocale(LC_COLLATE, $this->core->auth()->getInfo('user_lang')),
                // Set locale with blog params
                self::PUBLIC_LOCALE => setlocale(LC_COLLATE, $this->core->blog()->settings()->get('system')->get('lang') ?? $lang),
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
     */
    public function lexicalCompare(string $a, string $b, string $namespace = '', string $lang = 'en_US'): int
    {
        $this->setLexicalLang($namespace, $lang);

        return $this->lexicalSortHelper($a, $b);
    }

    /**
     * Callback helper for lexical sort.
     */
    private function lexicalSortHelper(string $a, string $b): int
    {
        return strcoll(strtolower(Text::removeDiacritics($a)), strtolower(Text::removeDiacritics($b)));
    }
}
