<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\App;
use Dotclear\Helper\Stack\Status as Descriptor;
use Dotclear\Helper\Stack\Statuses;
use Dotclear\Interface\Core\StatusInterface;

/**
 * @brief   List statuses handler.
 *
 * @since   2.33
 */
class Status implements StatusInterface
{
	protected Statuses $blog;
	protected Statuses $comment;
	protected Statuses $post;
	protected Statuses $user;

	/**
	 * Set default statuses.
	 */
	public function __construct()
	{
        $this->blog = new Statuses('blog_status', [
            (new Descriptor(1 , 'online', __('online'), 'images/published.svg')),
            (new Descriptor(0, 'offline', __('offline'), 'images/unpublished.svg')),
            (new Descriptor(-1, 'removed', __('removed'), 'images/trash.svg')),
            (new Descriptor(-2, 'undefined', __('undefined'), 'images/trash.svg')),
        ]);

        $this->post = new Statuses('post_status', [
            (new Descriptor(1, 'published', __('Published'), 'images/published.svg')),
            (new Descriptor(0, 'unpublished', __('Unpublished'), 'images/unpublished.svg')),
            (new Descriptor(-1, 'scheduled', __('Scheduled'), 'images/scheduled.svg')),
            (new Descriptor(-2, 'pending', __('Pending'), 'images/pending.svg')),
        ]);

        $this->comment = new Statuses('comment_status', [
            (new Descriptor(1, 'published', __('Published'), 'images/published.svg')),
            (new Descriptor(0, 'unpublished', __('Unpublished'), 'images/unpublished.svg')),
            (new Descriptor(-1, 'pending', __('Pending'), 'images/pending.svg')),
            (new Descriptor(-2, 'junk', __('Junk'), 'images/junk.svg')),
        ]);

        $this->user = new Statuses('user_status', [
            (new Descriptor(1, 'enabled', __('enabled'), 'images/published.svg')),
            (new Descriptor(0, 'disabled', __('disabled'), 'images/unpublished.svg')),
        ]);
	}

	public function blog(): Statuses
	{
		return $this->blog;
	}

	public function comment(): Statuses
	{
		return $this->comment;
	}

	public function post(): Statuses
	{
		return $this->post;
	}

	public function user(): Statuses
	{
		return $this->user;
	}
}