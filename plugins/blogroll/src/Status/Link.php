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

namespace Dotclear\Plugin\blogroll\Status;

use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Stack\Statuses;

/**
 * @brief       Blogroll statuses handler.
 *
 * @since       2.35
 */
class Link extends Statuses
{
    public const ONLINE  = 1;
    public const OFFLINE = 0;

    public function __construct()
    {
        // Translated names (singular, plural), for xgettext -> .pot
        __('Online', 'Online (>1)');
        __('Offline', 'Offline (>1)');

        parent::__construct(
            column: 'link_status',
            threshold: self::OFFLINE,
            statuses: [
                (new Status(self::ONLINE, 'online', 'Online', 'Online (>1)', 'images/published.svg')),
                (new Status(self::OFFLINE, 'offline', 'Offline', 'Offline (>1)', 'images/unpublished.svg')),
            ]
        );
    }
}
