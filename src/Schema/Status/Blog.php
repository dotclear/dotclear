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

use Dotclear\App;
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
	            (new Status(self::ONLINE , 'online', __('online'), 'images/published.svg')),
	            (new Status(self::OFFLINE, 'offline', __('offline'), 'images/unpublished.svg')),
	            (new Status(self::REMOVED, 'removed', __('removed'), 'images/trash.svg')),
	            (new Status(self::UNDEFINED, 'undefined', __('undefined'), 'images/trash.svg')),
	        ]
	    );
	}
}