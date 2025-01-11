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
 * @brief       Comment statuses handler.
 *
 * @since       2.33
 */
class Comment extends Statuses
{
    public const PUBLISHED   = 1;
    public const UNPUBLISHED = 0;
    public const PENDING     = -1;
    public const JUNK        = -2;

    public function __construct()
    {
        // Translated names (singular, plural), for xgettext -> .pot
        __('Published', 'Published (>1)');
        __('Unpublished', 'Unpublished (>1)');
        __('Pending', 'Pending (>1)');
        __('Junk', 'Junk (>1)');

        parent::__construct(
            column: 'comment_status',
            threshold: self::UNPUBLISHED,
            statuses: [
                (new Status(self::PUBLISHED, 'published', 'Published', 'Published (>1)', 'images/published.svg')),
                (new Status(self::UNPUBLISHED, 'unpublished', 'Unpublished', 'Unpublished (>1)', 'images/unpublished.svg')),
                (new Status(self::PENDING, 'pending', 'Pending', 'Pending (>1)', 'images/pending.svg')),
                (new Status(self::JUNK, 'junk', 'Junk', 'Junk (>1)', 'images/junk.svg', 'images/junk-dark.svg')),
            ]
        );
    }
}
