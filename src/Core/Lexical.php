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

    public function lexicalSort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return usort($arr, $this->lexicalSortHelper(...));
    }

    public function lexicalArraySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return uasort($arr, $this->lexicalSortHelper(...));
    }

    public function lexicalKeySort(array &$arr, string $namespace = '', string $lang = 'en_US'): bool
    {
        $this->setLexicalLang($namespace, $lang);

        return uksort($arr, $this->lexicalSortHelper(...));
    }

    public function setLexicalLang(string $namespace = '', string $lang = 'en_US'): void
    {
        try {
            // Switch to appropriate locale depending on $ns
            $set_lang = match ($namespace) {
                // Set locale with user prefs
                self::ADMIN_LOCALE => is_string($user_lang = $this->core->auth()->getInfo('user_lang')) ? $user_lang : '',

                // Set locale with blog params
                self::PUBLIC_LOCALE => is_string($blog_lang = $this->core->blog()->settings()->get('system')->get('lang')) ? $blog_lang : $lang,

                // Set locale with arg
                self::CUSTOM_LOCALE => $lang,
            };

            setlocale(LC_COLLATE, $set_lang);
        } catch (UnhandledMatchError) {
        }
    }

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
