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
            (new Descriptor(App::blog()::BLOG_ONLINE , 'online', __('online'))),
            (new Descriptor(App::blog()::BLOG_OFFLINE, 'offline', __('offline'))),
            (new Descriptor(App::blog()::BLOG_REMOVED, 'removed', __('removed'))),
            (new Descriptor(App::blog()::BLOG_UNDEFINED, 'undefined', __('removed'))),
        ]);

        $this->post = new Statuses('post_status', [
            (new Descriptor(App::blog()::POST_PENDING , 'pending', __('Pending'))),
            (new Descriptor(App::blog()::POST_SCHEDULED, 'scheduled', __('Scheduled'))),
            (new Descriptor(App::blog()::POST_UNPUBLISHED, 'unpublished', __('Unpublished'))),
            (new Descriptor(App::blog()::POST_PUBLISHED, 'pusblished', __('Published'))),
        ]);

        $this->comment = new Statuses('comment_status', [
            (new Descriptor(App::blog()::COMMENT_JUNK , 'junk', __('Junk'))),
            (new Descriptor(App::blog()::COMMENT_PENDING, 'pending', __('Pending'))),
            (new Descriptor(App::blog()::COMMENT_UNPUBLISHED, 'unpublished', __('Unpublished'))),
            (new Descriptor(App::blog()::COMMENT_PUBLISHED, 'pusblished', __('Published'))),
        ]);

        $this->user = new Statuses('user_status', [
            (new Descriptor(0, 'disabled', __('disabled'))),
            (new Descriptor(1, 'enabled', __('enabled'))),
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