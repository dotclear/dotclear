<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\StatusInterface;
use Dotclear\Schema\Status\Blog;
use Dotclear\Schema\Status\Comment;
use Dotclear\Schema\Status\Post;
use Dotclear\Schema\Status\User;

/**
 * @brief   Dotclear lists statuses handler.
 *
 * @since   2.33
 */
class Status implements StatusInterface
{
    protected Blog $blog;
    protected Comment $comment;
    protected Post $post;
    protected User $user;

    public function blog(): Blog
    {
        return $this->blog ?? $this->blog = new Blog();
    }

    public function comment(): Comment
    {
        return $this->comment ?? $this->comment = new Comment();
    }

    public function post(): Post
    {
        return $this->post ?? $this->post = new Post();
    }

    public function user(): User
    {
        return $this->user ?? $this->user = new User();
    }
}
