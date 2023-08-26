<?php
/**
 * Posts types handler.
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

class PostTypes
{
    /** @var    array<string,PostType>  The post types stack */
    private array $stack;

    /**
     * Check if post type exists.
     *
     * @param   string  The post type
     *
     * @return  bool    Ture if it exists
     */
    public function exists(string $type): bool
    {
        return isset($this->stack[$type]);
    }

    /**
     * Set post type.
     *
     * If post type exists, it wil be overwritten
     *
     * @param   PostType    $post_type  The post type descriptor
     *
     * @return  PostTypes   This instance
     */
    public function set(PostType $post_type): PostTypes
    {
        if ('' !== $post_type->type) {
            $this->stack[$post_type->type] = $post_type;
        }

        return $this;
    }

    /**
     * Get a post type.
     *
     * @param   string  $type   The post type
     *
     * @return  PostType    The post type descriptor
     */
    public function get(string $type): PostType
    {
        if (!isset($this->stack[$type])) {
            $type = 'post';
        }

        return $this->stack[$type] ?? new PostType('', '', '', 'unknown');
    }

    /**
     * Get the posts types.
     *
     * @return  array<string,PostType>
     */
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Gets the post admin URL, the old way.
     *
     * @param   string                  $type       The type
     * @param   mixed                   $post_id    The post identifier
     * @param   bool                    $escaped    Escape the URL
     * @param   array<string,mixed>     $params     The query string parameters (associative array)
     *
     * @return  string  The post admin URL.
     */
    public function getPostAdminURL(string $type, $post_id, bool $escaped = true, array $params = []): string
    {
        return $this->get($type)->adminUrl($post_id, $escaped, $params);
    }

    /**
     * Gets the post public URL, the old way.
     *
     * @param   string  $type   The type
     * @param   string  $post_url   The post URL
     * @param   bool    $escaped    Escape the URL
     *
     * @return  string  The post public URL.
     */
    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        return $this->get($type)->publicUrl($post_url, $escaped);
    }

    /**
     * Sets the post type, the odl way.
     *
     * @param   string  $type           The type
     * @param   string  $admin_url      The admin URL
     * @param   string  $public_url     The public URL
     * @param   string  $label          The label
     */
    public function setPostType(string $type, string $admin_url, string $public_url, string $label = ''): void
    {
        $this->set(new PostType(
            type:       $type,
            admin_url:  $admin_url,
            public_url: $public_url,
            label:      $label,
        ));
    }

    /**
     * Gets the post types, the old school way.
     *
     * @return  array<string,array<string,string>>  The post types.
     */
    public function getPostTypes(): array
    {
        $res = [];

        foreach ($this->stack as $desc) {
            $res[$desc->type] = [
                'admin_url'  => $desc->admin_url,
                'public_url' => $desc->public_url,
                'label'      => $desc->label,
            ];
        }

        return $res;
    }
}
