<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Lexical helper interface.
 *
 * @since   2.28
 */
interface LexicalInterface
{
    /**
     * Admin locale process name.
     *
     * @var     string  ADMIN_LOCALE
     */
    public const ADMIN_LOCALE = 'admin';

    /**
     * Public locale process name.
     *
     * @var     string  PUBLIC_LOCALE
     */
    public const PUBLIC_LOCALE = 'public';

    /**
     * Custom locale name.
     *
     * @var     string  CUSTOM_LOCALE
     */
    public const CUSTOM_LOCALE = 'lang';

    /**
     * Locale specific array sorting function.
     *
     * @param   array<string>   $arr        single array of strings
     * @param   string          $namespace  admin/public/lang
     * @param   string          $lang       language to be used if $ns = 'lang'
     */
    public function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Locale specific array sorting function (preserving keys).
     *
     * @param   array<string, string>   $arr        single associative array of strings
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     */
    public function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Locale specific array sorting function (sorting keys).
     *
     * @param   array<string, mixed>    $arr        single associative array of strings
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     */
    public function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Sets the lexical language.
     *
     * @param   string  $namespace  The namespace (admin/public/lang)
     * @param   string  $lang       The language
     */
    public function setLexicalLang(string $namespace = '', string $lang = 'en_US'): void;

    /**
     * Locale specific string comparison function.
     *
     * @param   string                  $a          1st string
     * @param   string                  $b          2nd string
     * @param   string                  $namespace  admin/public/lang
     * @param   string                  $lang       language to be used if $ns = 'lang'
     */
    public function lexicalCompare(string $a, string $b, string $namespace = '', string $lang = 'en_US'): int;
}
