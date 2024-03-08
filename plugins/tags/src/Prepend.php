<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\tags;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief   The module prepend process.
 * @ingroup tags
 */
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

        App::url()->register('tag', 'tag', '^tag/(.+)$', FrontendUrl::tag(...));
        App::url()->register('tags', 'tags', '^tags$', FrontendUrl::tags(...));
        App::url()->register('tag_feed', 'feed/tag', '^feed/tag/(.+)$', FrontendUrl::tagFeed(...));

        App::behavior()->addBehavior('coreInitWikiPost', BackendBehaviors::coreInitWikiPost(...));

        return true;
    }
}
