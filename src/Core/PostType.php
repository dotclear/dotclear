<?php
/**
 * Posts type descriptor.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Html\Html;

class PostType
{
    public readonly string $label;

    /**
     * Constructor sets post type properties.
     *
     * @param   string  $type           The post type
     * @param   string  $admin_url      The backend URL representation
     * @param   string  $public_url     The frontend URL representation
     * @param   string  $label          The post type name (untranslated)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $admin_url,
        public readonly string $public_url,
        string $label = ''
    ) {
        $this->label = $label !== '' ? $label : $type;
    }

    /**
     * Gets the post admin url.
     *
     * @param   int|string              $post_id    The post identifier
     * @param   bool                    $escaped    Escape the URL
     * @param   array<string,mixed>     $params     The query string parameters (associative array)
     *
     * @return  string  The post admin url.
     */
    public function adminUrl(int|string $post_id, bool $escaped = true, array $params = []): string
    {
        $url = sprintf($this->admin_url, $post_id);

        if (!empty($params)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params, '', '&');
        }

        return $escaped ? Html::escapeURL($url) : $url;
    }

    /**
     * Gets the post public url.
     *
     * @param   string  $post_url   The post url
     * @param   bool    $escaped    Escape the URL
     *
     * @return  string  The post public url.
     */
    public function publicUrl(string $post_url, bool $escaped = true): string
    {
        $url = sprintf($this->public_url, $post_url);

        return $escaped ? Html::escapeURL($url) : $url;
    }

    /**
     * Get post type properties as array.
     *
     * @return  array<string,string> The post type properties
     */
    public function dump(): array
    {
        /* @phpstan-ignore-next-line */
        return get_object_vars($this);
    }
}
