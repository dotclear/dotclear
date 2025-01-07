<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Helper\Stack\Statuses;
use Dotclear\Schema\Status\Blog;
use Dotclear\Schema\Status\Comment;
use Dotclear\Schema\Status\Post;
use Dotclear\Schema\Status\User;

/**
 * @brief   Dotclear lists statuses handler interface.
 *
 * @since   2.33
 */
interface StatusInterface
{
	/**
	 * Blog statuses handler.
	 */
	public function blog(): Blog;

	/**
	 * Comment statuses handler.
	 */
	public function comment(): Comment;

	/**
	 * Post statuses handler.
	 */
	public function post(): Post;

	/**
	 * User statuses handler.
	 */
	public function user(): User;
}