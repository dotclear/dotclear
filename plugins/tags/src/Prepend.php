<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use dcCore;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->url->register('tag', 'tag', '^tag/(.+)$', [FrontendUrl::class, 'tag']);
        dcCore::app()->url->register('tags', 'tags', '^tags$', [FrontendUrl::class, 'tags']);
        dcCore::app()->url->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', [FrontendUrl::class, 'tagFeed']);

        dcCore::app()->addBehavior('coreInitWikiPost', [BackendBehaviors::class, 'coreInitWikiPost']);

        return true;
    }
}
