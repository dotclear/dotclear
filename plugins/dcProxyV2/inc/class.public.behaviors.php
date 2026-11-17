<?php

declare(strict_types=1);

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
        return App::behavior()->callBehavior('publicAfterContentFilter', null, $tag, $args);
    }

    public static function publicAfterDocument(): mixed
    {
        return App::behavior()->callBehavior('publicAfterDocument', null);
    }

    public static function publicBeforeContentFilter(mixed $tag, mixed $args): mixed
    {
        return App::behavior()->callBehavior('publicBeforeContentFilter', null, $tag, $args);
    }

    public static function publicBeforeDocument(): mixed
    {
        return App::behavior()->callBehavior('publicBeforeDocument', null);
    }

    public static function publicBeforeReceiveTrackback(mixed $args): mixed
    {
        return App::behavior()->callBehavior('publicBeforeReceiveTrackback', null, $args);
    }

    public static function publicContentFilter(mixed $tag, mixed $args, mixed $filter): mixed
    {
        return App::behavior()->callBehavior('publicContentFilter', null, $tag, $args, $filter);
    }

    public static function publicPrepend(): mixed
    {
        return App::behavior()->callBehavior('publicPrepend', null);
    }

    public static function templateAfterBlock(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateAfterBlock', null, $current_tag, $attr);
    }

    public static function templateAfterValue(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateAfterValue', null, $current_tag, $attr);
    }

    public static function templateBeforeBlock(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateBeforeBlock', null, $current_tag, $attr);
    }

    public static function templateBeforeValue(mixed $current_tag, mixed $attr): mixed
    {
        return App::behavior()->callBehavior('templateBeforeValue', null, $current_tag, $attr);
    }

    public static function templateInsideBlock(mixed $current_tag, mixed $attr, mixed $array_content): mixed
    {
        return App::behavior()->callBehavior('templateInsideBlock', null, $current_tag, $attr, $array_content);
    }

    public static function tplAfterData(mixed $_r): mixed
    {
        return App::behavior()->callBehavior('tplAfterData', null, $_r);
    }

    public static function tplBeforeData(): mixed
    {
        return App::behavior()->callBehavior('tplBeforeData', null);
    }
}
