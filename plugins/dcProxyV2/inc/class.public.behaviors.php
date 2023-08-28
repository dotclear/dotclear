<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * Public behaviours
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
use Dotclear\Core\Core;

class dcProxyV2PublicBehaviors
{
    // Count : 14

    public static function publicAfterContentFilter($tag, $args)
    {
        return Core::behavior()->callBehavior('publicAfterContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicAfterDocument()
    {
        return Core::behavior()->callBehavior('publicAfterDocument', dcCore::app());
    }
    public static function publicBeforeContentFilter($tag, $args)
    {
        return Core::behavior()->callBehavior('publicBeforeContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicBeforeDocument()
    {
        return Core::behavior()->callBehavior('publicBeforeDocument', dcCore::app());
    }
    public static function publicBeforeReceiveTrackback($args)
    {
        return Core::behavior()->callBehavior('publicBeforeReceiveTrackback', dcCore::app(), $args);
    }
    public static function publicContentFilter($tag, $args, $filter)
    {
        return Core::behavior()->callBehavior('publicContentFilter', dcCore::app(), $tag, $args, $filter);
    }
    public static function publicPrepend()
    {
        return Core::behavior()->callBehavior('publicPrepend', dcCore::app());
    }

    public static function templateAfterBlock($current_tag, $attr)
    {
        return Core::behavior()->callBehavior('templateAfterBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateAfterValue($current_tag, $attr)
    {
        return Core::behavior()->callBehavior('templateAfterValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeBlock($current_tag, $attr)
    {
        return Core::behavior()->callBehavior('templateBeforeBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeValue($current_tag, $attr)
    {
        return Core::behavior()->callBehavior('templateBeforeValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateInsideBlock($current_tag, $attr, $array_content)
    {
        return Core::behavior()->callBehavior('templateInsideBlock', dcCore::app(), $current_tag, $attr, $array_content);
    }
    public static function tplAfterData($_r)
    {
        return Core::behavior()->callBehavior('tplAfterData', dcCore::app(), $_r);
    }
    public static function tplBeforeData()
    {
        return Core::behavior()->callBehavior('tplBeforeData', dcCore::app());
    }
}
