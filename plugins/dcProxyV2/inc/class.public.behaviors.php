<?php
/**
 * @brief dcProxyV2, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

// Public behaviours

class dcProxyV2PublicBehaviors
{
    // Count : 14

    public static function publicAfterContentFilter($tag, $args)
    {
        return dcCore::app()->callBehavior('publicAfterContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicAfterDocument()
    {
        return dcCore::app()->callBehavior('publicAfterDocument', dcCore::app());
    }
    public static function publicBeforeContentFilter($tag, $args)
    {
        return dcCore::app()->callBehavior('publicBeforeContentFilter', dcCore::app(), $tag, $args);
    }
    public static function publicBeforeDocument()
    {
        return dcCore::app()->callBehavior('publicBeforeDocument', dcCore::app());
    }
    public static function publicBeforeReceiveTrackback($args)
    {
        return dcCore::app()->callBehavior('publicBeforeReceiveTrackback', dcCore::app(), $args);
    }
    public static function publicContentFilter($tag, $args, $filter)
    {
        return dcCore::app()->callBehavior('publicContentFilter', dcCore::app(), $tag, $args, $filter);
    }
    public static function publicPrepend()
    {
        return dcCore::app()->callBehavior('publicPrepend', dcCore::app());
    }

    public static function templateAfterBlock($current_tag, $attr)
    {
        return dcCore::app()->callBehavior('templateAfterBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateAfterValue($current_tag, $attr)
    {
        return dcCore::app()->callBehavior('templateAfterValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeBlock($current_tag, $attr)
    {
        return dcCore::app()->callBehavior('templateBeforeBlock', dcCore::app(), $current_tag, $attr);
    }
    public static function templateBeforeValue($current_tag, $attr)
    {
        return dcCore::app()->callBehavior('templateBeforeValue', dcCore::app(), $current_tag, $attr);
    }
    public static function templateInsideBlock($current_tag, $attr, $array_content)
    {
        return dcCore::app()->callBehavior('templateInsideBlock', dcCore::app(), $current_tag, $attr, $array_content);
    }
    public static function tplAfterData($_r)
    {
        return dcCore::app()->callBehavior('tplAfterData', dcCore::app(), $_r);
    }
    public static function tplBeforeData()
    {
        return dcCore::app()->callBehavior('tplBeforeData', dcCore::app());
    }
}
