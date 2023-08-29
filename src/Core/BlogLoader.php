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

class BlogLoader
{
    /** @var    null|dcBlog     The current loaded blog instance */
    private ?dcBlog $blog = null;

    /**
     * Get current blog.
     *
     * @return null|dcBlog
     */
    public function getBlog(): ?dcBlog
    {
        return $this->blog;
    }

    /**
     * Set the blog to use.
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog(string $id): void
    {
        $this->blog = new dcBlog($id);

        // deprecated since 2.28, use Core::blogLoader()->setBlog() instead
        dcCore::app()->blog = $this->blog;
    }

    /**
     * Unset blog property.
     */
    public function unsetBlog(): void
    {
        $this->blog = null;

        // deprecated since 2.28, use Core::blogLoader()->unsetBlog() instead
        dcCore::app()->blog = null;
    }
}