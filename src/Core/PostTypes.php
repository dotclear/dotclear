<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\PostTypesInterface;

/**
 * @brief   Posts types handler.
 *
 * @since   2.28, post types features have been grouped in this class
 */
class PostTypes implements PostTypesInterface
{
    /**
     * The post types stack.
     *
     * @var     array<string,PostType>  $stack
     */
    private array $stack;

    public function exists(string $type): bool
    {
        return isset($this->stack[$type]);
    }

    public function __get(string $type): PostType
    {
        return $this->get($type);
    }

    public function get(string $type): PostType
    {
        if ($type !== '' && !isset($this->stack[$type])) {
            $type = 'post';
        }

        return $this->stack[$type] ?? new PostType('', '', '', 'undefined');
    }

    public function set(PostType $descriptor): PostTypesInterface
    {
        if ('' !== $descriptor->get('type')) {
            $this->stack[$descriptor->get('type')] = $descriptor;
        }

        return $this;
    }

    public function dump(): array
    {
        return $this->stack;
    }

    public function getPostAdminURL(string $type, int|string $post_id, bool $escaped = true, array $params = []): string
    {
        return $this->get($type)->adminUrl($post_id, $escaped, $params);
    }

    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        return $this->get($type)->publicUrl($post_url, $escaped);
    }

    public function setPostType(string $type, string $admin_url, string $public_url, string $label = '', string $list_admin_url = ''): void
    {
        $this->set(new PostType($type, $admin_url, $public_url, $label, $list_admin_url));
    }

    public function getPostTypes(): array
    {
        $res = [];

        foreach ($this->stack as $desc) {
            $res[$desc->get('type')] = $desc->dump();
        }

        return $res;
    }
}
