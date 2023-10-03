<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
use Dotclear\App;

/**
 * @brief   The module frontend behaviors aliases handler.
 * @ingroup dcProxyV2
 */
class dcProxyV2PublicBehaviors
{
    // Count : 14

    public static function publicAfterContentFilter(mixed $tag, mixed $args): mixed
    {
        return App::behavior()->callBehavior('publicAfterContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicAfterDocument(): mixed
    {
        return App::behavior()->callBehavior('publicAfterDocument', dcCore::app());
    }
    public static function publicBeforeContentFilter(mixed $tag, mixed $args): mixed
    {
        return App::behavior()->callBehavior('publicBeforeContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicBeforeDocument(): mixed
    {
        return App::behavior()->callBehavior('publicBeforeDocument', dcCore::app());
    }
    public static function publicBeforeReceiveTrackback(mixed $args): mixed
    {
        return App::behavior()->callBehavior('publicBeforeReceiveTrackback', dcCore::app(), $args);
    }
    public static function publicContentFilter(mixed $tag, mixed $args, mixed $filter): mixed
    {
        return App::behavior()->callBehavior('publicContentFilter', dcCore::app(), $tag, $args, $filter);
    }
    public static function publicPrepend(): mixed
    {
        return App::behavior()->callBehavior('publicPrepend', dcCore::app());
    }

    public static function templateAfterBlock(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateAfterBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateAfterValue(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateAfterValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeBlock(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateBeforeBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeValue(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateBeforeValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateInsideBlock(mixed $current_tag, mixed $attr, mixed $array_content): mixed
    {
        return App::behavior()->callBehavior('templateInsideBlock', dcCore::app(), $current_tag, $attr, $array_content);
    }
    public static function tplAfterData(mixed $_r): mixed
    {
        return App::behavior()->callBehavior('tplAfterData', dcCore::app(), $_r);
    }
    public static function tplBeforeData(): mixed
    {
        return App::behavior()->callBehavior('tplBeforeData', dcCore::app());
    }
}
