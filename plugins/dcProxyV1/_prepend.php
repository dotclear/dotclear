<?php
/**
 * @brief dcProxyV1, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class dcProxyV1
{
    public static function classAliases(array $aliases)
    {
        foreach ($aliases as $aliasName => $realName) {
            class_alias($realName, $aliasName);
        }
    }
}

// Classes aliases
dcProxyV1::classAliases([
    // alias → real name (including namespace if necessary, for both)

    // Deprecated since 2.26
    'Clearbricks' => 'Dotclear\Helper\Clearbricks',
]);
