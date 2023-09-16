<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Helper\Text;
use Dotclear\Interface\Core\LexicalInterface;
use UnhandledMatchError;

/**
 * @brief   Lexical helper.
 */
class Lexical implements LexicalInterface
{
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
            match ($namespace) {
                // Set locale with user prefs
                self::ADMIN_LOCALE => setlocale(LC_COLLATE, App::auth()->getInfo('user_lang')),
                // Set locale with blog params
                self::PUBLIC_LOCALE => setlocale(LC_COLLATE, App::blog()->settings()->get('system')->get('lang') ?? $lang),
                // Set locale with arg
                self::CUSTOM_LOCALE => setlocale(LC_COLLATE, $lang),
            };
        } catch (UnhandledMatchError) {
        }
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
