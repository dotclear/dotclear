<?php
/**
 * Blog loader.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use dcBlog;
use Dotclear\Interface\Core\BlogLoaderInterface;

class BlogLoader implements BlogLoaderInterface
{
    /** @var    null|dcBlog     The current loaded blog instance */
    private ?dcBlog $blog = null;

    public function hasBLog(): bool
    {
        return !is_null($this->blog);
    }

    public function getBlog(): ?dcBlog
    {
        return $this->blog;
    }

    public function setBlog(string $id): void
    {
        $this->blog = new dcBlog($id);

        // deprecated since 2.28, use App::blogLoader()->setBlog() instead
        dcCore::app()->blog = $this->blog;
    }

    public function unsetBlog(): void
    {
        $this->blog = null;

        // deprecated since 2.28, use App::blogLoader()->unsetBlog() instead
        dcCore::app()->blog = null;
    }
}
