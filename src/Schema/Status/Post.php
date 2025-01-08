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
 * @brief       Post statuses handler.
 *
 * @since       2.33
 */
class Post extends Statuses
{
    public const PUBLISHED   = 1;
    public const UNPUBLISHED = 0;
    public const SCHEDULED   = -1;
    public const PENDING     = -2;

    public function __construct()
    {
        parent::__construct(
            column: 'post_status',
            threshold: self::UNPUBLISHED,
            statuses: [
                (new Status(self::PUBLISHED, 'published', __('Published'), __('Published (>1)'), 'images/published.svg')),
                (new Status(self::UNPUBLISHED, 'unpublished', __('Unpublished'), __('Unpublished (>1)'), 'images/unpublished.svg')),
                (new Status(self::SCHEDULED, 'scheduled', __('Scheduled'), __('Scheduled (>1)'), 'images/scheduled.svg')),
                (new Status(self::PENDING, 'pending', __('Pending'), __('Pending (>1)'), 'images/pending.svg')),
            ]
        );
    }
}
