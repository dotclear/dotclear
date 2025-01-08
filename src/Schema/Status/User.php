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
        parent::__construct(
            column: 'user_status',
            threshold: self::DISABLED,
            statuses: [
                (new Status(self::ENABLED, 'enabled', __('Enabled'), __('Enabled (>1)'), 'images/published.svg')),
                (new Status(self::DISABLED, 'disabled', __('Disabled'), __('Disabled (>1)'), 'images/unpublished.svg')),
            ]
        );
    }
}
