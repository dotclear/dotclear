<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * Post type descriptor interface.
 */
interface PostTypeInterface
{
    /**
     * Get a post type property
     *
     * @param   string  $property   The property key
     *
     * @return  string  The property value or empty string if not exists
     */
    public function get(string $property): string;

    /**
     * Gets the post admin url.
     *
     * @param   int|string              $post_id    The post identifier
     * @param   bool                    $escaped    Escape the URL
     * @param   array<string,mixed>     $params     The query string parameters (associative array)
     *
     * @return  string  The post admin url.
     */
    public function adminUrl(int|string $post_id, bool $escaped = true, array $params = []): string;

    /**
     * Gets the post public url.
     *
     * @param   string  $post_url   The post url
     * @param   bool    $escaped    Escape the URL
     *
     * @return  string  The post public url.
     */
    public function publicUrl(string $post_url, bool $escaped = true): string;

    /**
     * Get post type properties as array.
     *
     * @return  array<string,string> The post type properties
     */
    public function dump(): array;
}
