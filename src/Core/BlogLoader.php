<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use dcCore;
use Dotclear\Interface\Core\BlogInterface;
use Dotclear\Interface\Core\BlogLoaderInterface;

/**
 * @brief   Blog loader.
 *
 * @since   2.28, (un)set blog features have been grouped in this class
 */
class BlogLoader implements BlogLoaderInterface
{
    /**
     * The current loaded blog instance.
     *
     * @var     BlogInterface   $blog
     */
    private BlogInterface $blog;

    public function hasBLog(): bool
    {
        return $this->getBlog()->isDefined();
    }

    public function getBlog(): BlogInterface
    {
        if (!isset($this->blog)) {
            $this->unsetBlog();
        }

        return $this->blog;
    }

    public function setBlog(string $id): void
    {
        $this->blog = new Blog($id);

        // deprecated since 2.28, use App::blogLoader()->setBlog() instead
        dcCore::app()->blog = $this->blog;
    }

    public function unsetBlog(): void
    {
        $this->blog = new Blog();

        // deprecated since 2.28, use App::blogLoader()->unsetBlog() instead
        dcCore::app()->blog = null;
    }
}
