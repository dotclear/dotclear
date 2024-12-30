<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

use Dotclear\Helper\Html\WikiToHtml;

/**
 * @brief   Wiki and HTML filter handler interface.
 *
 * @since   2.28
 */
interface FilterInterface
{
    /**
     * Load working blog on filter instance
     *
     * @param   BlogInterface       $blog       The blog instance
     */
    public function loadFromBlog(BlogInterface $blog): FilterInterface;

    /// @name WikiToHtml methods
    //@{
    /**
     * Get wiki instance
     *
     * @return  null|WikiToHtml    The wiki Instance
     */
    public function wiki(): ?WikiToHtml;

    /**
     * Returns a transformed string with WikiToHtml.
     *
     * @param   string  $str    The string
     */
    public function wikiTransform(string $str): string;

    /**
     * Inits <var>wiki</var> property for blog post.
     */
    public function initWikiPost(): void;

    /**
     * Inits <var>wiki</var> property for simple blog comment (basic syntax).
     */
    public function initWikiSimpleComment(): void;

    /**
     * Inits <var>wiki</var> property for blog comment.
     */
    public function initWikiComment(): void;

    /**
     * Get info about a post:id wiki macro
     *
     * @param   string  $url        The post url
     * @param   string  $content    The content
     *
     * @return  array<string,string>
     */
    public function wikiPostLink(string $url, string $content): array;
    //@}

    /// @name HTML Filter methods
    //@{
    /**
     * Calls HTML filter to drop bad tags and produce valid HTML output (if
     * tidy extension is present). If <b>enable_html_filter</b> blog setting is
     * false, returns not filtered string.
     *
     * @param   string  $str    The string
     */
    public function HTMLfilter(string $str): string;
    //@}
}
