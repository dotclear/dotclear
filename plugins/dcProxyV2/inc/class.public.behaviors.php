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

    public static function publicAfterContentFilter($tag, $args)
    {
        return App::behavior()->callBehavior('publicAfterContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicAfterDocument()
    {
        return App::behavior()->callBehavior('publicAfterDocument', dcCore::app());
    }
    public static function publicBeforeContentFilter($tag, $args)
    {
        return App::behavior()->callBehavior('publicBeforeContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicBeforeDocument()
    {
        return App::behavior()->callBehavior('publicBeforeDocument', dcCore::app());
    }
    public static function publicBeforeReceiveTrackback($args)
    {
        return App::behavior()->callBehavior('publicBeforeReceiveTrackback', dcCore::app(), $args);
    }
    public static function publicContentFilter($tag, $args, $filter)
    {
        return App::behavior()->callBehavior('publicContentFilter', dcCore::app(), $tag, $args, $filter);
    }
    public static function publicPrepend()
    {
        return App::behavior()->callBehavior('publicPrepend', dcCore::app());
    }

    public static function templateAfterBlock($current_tag, $attr)
    {
        return App::behavior()->callBehavior('templateAfterBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateAfterValue($current_tag, $attr)
    {
        return App::behavior()->callBehavior('templateAfterValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeBlock($current_tag, $attr)
    {
        return App::behavior()->callBehavior('templateBeforeBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeValue($current_tag, $attr)
    {
        return App::behavior()->callBehavior('templateBeforeValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateInsideBlock($current_tag, $attr, $array_content)
    {
        return App::behavior()->callBehavior('templateInsideBlock', dcCore::app(), $current_tag, $attr, $array_content);
    }
    public static function tplAfterData($_r)
    {
        return App::behavior()->callBehavior('tplAfterData', dcCore::app(), $_r);
    }
    public static function tplBeforeData()
    {
        return App::behavior()->callBehavior('tplBeforeData', dcCore::app());
    }
}
