<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\ContentInterface;

/**
 * @brief   Content handler
 *
 * @since   2.40
 */
class Content implements ContentInterface
{
    /**
     * Construct a new instance
     *
     * @param string $format  Content format (wiki, html, markdown, text, ...) -- readonly
     * @param string $content Content
     */
    public function __construct(
        protected readonly string $format,
        protected string $content
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
