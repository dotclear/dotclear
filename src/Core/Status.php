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
use Dotclear\Helper\Stack\Statuses;
use Dotclear\Interface\Core\StatusInterface;
use Dotclear\Schema\Status\Blog;
use Dotclear\Schema\Status\Comment;
use Dotclear\Schema\Status\Post;
use Dotclear\Schema\Status\User;

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

	public function __construct()
	{
        $this->blog    = new Blog();
        $this->post    = new Post();
        $this->comment = new Comment();
        $this->user    = new User();
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