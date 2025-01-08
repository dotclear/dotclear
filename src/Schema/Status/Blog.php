<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Schema.Status
 * @brief       Status helpers.
 */

namespace Dotclear\Schema\Status;

use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Stack\Statuses;

/**
 * @brief       Blog statuses handler.
 *
 * @since       2.33
 */
class Blog extends Statuses
{
    public const ONLINE    = 1;
    public const OFFLINE   = 0;
    public const REMOVED   = -1;
    public const UNDEFINED = -2;

    public function __construct()
    {
        parent::__construct(
            column: 'blog_status',
            threshold: self::OFFLINE,
            statuses: [
                (new Status(self::ONLINE, 'online', __('Online'), 'images/published.svg')),
                (new Status(self::OFFLINE, 'offline', __('Offline'), 'images/unpublished.svg')),
                (new Status(self::REMOVED, 'removed', __('Removed'), 'images/pending.svg')),
                (new Status(self::UNDEFINED, 'undefined', __('Undefined'), 'images/check-off.svg', true)),
            ]
        );
    }
}
