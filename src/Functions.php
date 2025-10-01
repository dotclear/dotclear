<?php

/**
 * @package         Dotclear
 * @subpackage      Function
 *
 * @defsubpackage   Function    Application root functions
 *
 * @copyright       Olivier Meunier & Association Dotclear
 * @copyright       AGPL-3.0
 */
declare(strict_types=1);

use Dotclear\Helper\L10n;

// Load Autoloader file
require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'Autoloader.php']);

// Add root folder for namespaced and autoloaded classes
Autoloader::me()->addNamespace('Dotclear', __DIR__);

if (!function_exists('dotclear_exit')) {
    /**
     * Terminate application/process
     *
     * It is not possible to disable, or create a namespaced function shadowing the global exit() function.
     * So, in order to test code, we should be able to mock this event, using dotclear_exit() global function.
     */
    function dotclear_exit(string|int $status = 0): never
    {
        exit($status);
    }
}

if (!function_exists('__')) {
    /**
     * Translated string
     *
     * @see Dotclear\Helper\L10n::trans()
     *
     * @param      string       $singular Singular form of the string
     * @param      string|null  $plural Plural form of the string (optionnal)
     * @param      int|null     $count Context number for plural form (optionnal)
     *
     * @return     string   translated string
     */
    function __(string $singular, ?string $plural = null, ?int $count = null): string
    {
        return L10n::trans($singular, $plural, $count);
    }
}