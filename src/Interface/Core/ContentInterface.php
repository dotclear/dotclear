<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Content interface.
 *
 * Used for string content management (post content, excerpt, comment, …)
 *
 * @since   2.40
 */
interface ContentInterface
{
    /**
     * Get content format
     */
    public function getFormat(): string;

    /**
     * Get content
     */
    public function getContent(): string;

    /**
     * Set content
     */
    public function setContent(string $content): void;
}
