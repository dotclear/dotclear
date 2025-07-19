<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

use Dotclear\Helper\L10n;

if (!function_exists('__')) {
    /**
     * Translated string
     *
     * @see Dotclear\Helper\L10n::trans()
     *
     * @param      string   $singular Singular form of the string
     * @param      string   $plural Plural form of the string (optionnal)
     * @param      integer  $count Context number for plural form (optionnal)
     *
     * @return     string   translated string
     */
    function __(string $singular, ?string $plural = null, ?int $count = null): string
    {
        return L10n::trans($singular, $plural, $count);
    }
}
