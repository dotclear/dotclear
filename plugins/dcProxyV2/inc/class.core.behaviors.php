<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

/**
 * @brief   The module core behaviors aliases handler.
 * @ingroup dcProxyV2
 */
class dcProxyV2CoreBehaviors
{
    // Count: 3

    public static function coreBeforeLoadingNsFiles($that, $lang)
    {
        return App::behavior()->callBehavior('coreBeforeLoadingNsFiles', dcCore::app(), $that, $lang);
    }
    public static function coreCommentSearch($table)
    {
        return App::behavior()->callBehavior('coreCommentSearch', dcCore::app(), $table);
    }
    public static function corePostSearch($table)
    {
        return App::behavior()->callBehavior('corePostSearch', dcCore::app(), $table);
    }
}
