<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Status;

use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Stack\Statuses;

/**
 * @brief       User statuses handler.
 *
 * @since       2.33
 */
class User extends Statuses
{
    public const ENABLED  = 1;
    public const DISABLED = 0;

    public function __construct()
    {
        // Translated names (singular, plural), for xgettext -> .pot
        __('Enabled', 'Enabled (>1)');
        __('Disabled', 'Disabled (>1)');

        parent::__construct(
            column: 'user_status',
            threshold: self::DISABLED,
            statuses: [
                (new Status(self::ENABLED, 'enabled', 'Enabled', 'Enabled (>1)', 'images/published.svg')),
                (new Status(self::DISABLED, 'disabled', 'Disabled', 'Disabled (>1)', 'images/unpublished.svg')),
            ]
        );
    }
}
