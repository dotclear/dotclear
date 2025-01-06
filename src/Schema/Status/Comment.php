<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Schema\Status;

use Dotclear\App;
use Dotclear\Helper\Stack\Status;
use Dotclear\Helper\Stack\Statuses;

/**
 * @brief       Comment statuses handler.
 *
 * @since       2.33
 * @package     Dotclear
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
class Comment extends Statuses
{
	public const PUBLISHED = 1;
	public const UNPUBLISHED = 0;
	public const PENDING = -1;
	public const JUNK = -2;

	public function __construct()
	{
		parent::__construct(
			column: 'comment_status',
			threshold: self::UNPUBLISHED,
			statuses: [
	            (new Status(self::PUBLISHED, 'published', __('Published'), 'images/published.svg')),
	            (new Status(self::UNPUBLISHED, 'unpublished', __('Unpublished'), 'images/unpublished.svg')),
	            (new Status(self::PENDING, 'pending', __('Pending'), 'images/pending.svg')),
	            (new Status(self::JUNK, 'junk', __('Junk'), 'images/junk.svg')),
	        ]
	    );
	}
}