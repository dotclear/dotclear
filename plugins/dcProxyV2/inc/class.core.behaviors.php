<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * Core behaviours
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

class dcProxyV2CoreBehaviors
{
    // Count: 3

    public static function coreBeforeLoadingNsFiles($that, $lang)
    {
        return Core::behavior()->callBehavior('coreBeforeLoadingNsFiles', dcCore::app(), $that, $lang);
    }
    public static function coreCommentSearch($table)
    {
        return Core::behavior()->callBehavior('coreCommentSearch', dcCore::app(), $table);
    }
    public static function corePostSearch($table)
    {
        return Core::behavior()->callBehavior('corePostSearch', dcCore::app(), $table);
    }
}
