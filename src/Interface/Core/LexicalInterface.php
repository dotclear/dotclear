<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * Lexical helper.
 *
 * @since 2.28
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
     * @param   array   $arr        single array of strings
     * @param   string  $namespace  admin/public/lang
     * @param   string  $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Locale specific array sorting function (preserving keys).
     *
     * @param   array   $arr        single array of strings
     * @param   string  $namespace  admin/public/lang
     * @param   string  $lang   language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Locale specific array sorting function (sorting keys).
     *
     * @param   array   $arr        single array of strings
     * @param   string  $namespace  admin/public/lang
     * @param   string  $lang       language to be used if $ns = 'lang'
     *
     * @return  bool
     */
    public function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool;

    /**
     * Sets the lexical language.
     *
     * @param   string  $namespace  The namespace (admin/public/lang)
     * @param   string  $lang       The language
     */
    public function setLexicalLang(string $namespace = '', string $lang = 'en_US'): void;
}
