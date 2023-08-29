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

use Dotclear\Core\Core;
use Dotclear\Core\Process;

class Prepend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        Core::url()->register('tag', 'tag', '^tag/(.+)$', FrontendUrl::tag(...));
        Core::url()->register('tags', 'tags', '^tags$', FrontendUrl::tags(...));
        Core::url()->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', FrontendUrl::tagFeed(...));

        Core::behavior()->addBehavior('coreInitWikiPost', BackendBehaviors::coreInitWikiPost(...));

        return true;
    }
}
