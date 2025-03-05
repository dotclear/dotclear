<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
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

    /**
     * Gets a post type icon URI.
     */
    public function icon(string $type): string
    {
        return $this->get($type)->icon();
    }

    /**
     * Gets a post type dark icon URI.
     */
    public function iconDark(string $type): string
    {
        return $this->get($type)->iconDark();
    }

    /**
     * Get post type admin image.
     */
    public function image(string $type, bool $with_text = false): Text|Img|Set
    {
        if ($this->exists($type)) {
            $item      = $this->get($type);
            $icon      = $item->icon();
            $icon_dark = $item->iconDark();
            if ($icon_dark !== '') {
                // Two icons, one for each mode (light and dark)
                $imgs = (new Set())
                    ->items([
                        (new Img($icon))
                            ->alt(Html::escapeHTML(__($item->get('label'))))
                            ->title(Html::escapeHTML(__($item->get('label'))))
                            ->class(['mark', 'mark-' . $type, 'light-only']),
                        (new Img($icon_dark))
                            ->alt(Html::escapeHTML(__($item->get('label'))))
                            ->title(Html::escapeHTML(__($item->get('label'))))
                            ->class(['mark', 'mark-' . $type, 'dark-only']),
                    ]);

                return $with_text ?
                    (new Text(null, $imgs->render() . ' ' . Html::escapeHTML(__($item->get('label'))))) :
                    $imgs;
            }
            // Only one icon for both mode (light and dark)
            $img = (new Img($icon))
                ->alt(Html::escapeHTML(__($item->get('label'))))
                ->title(Html::escapeHTML(__($item->get('label'))))
                ->class(['mark', 'mark-' . $type]);

            return $with_text ?
                (new Text(null, $img->render() . ' ' . Html::escapeHTML(__($item->get('label'))))) :
                $img;
        }

        return $with_text ? (new Text(null, '')) : (new Img(''));
    }

    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        return $this->get($type)->publicUrl($post_url, $escaped);
    }

    public function setPostType(
        string $type,
        string $admin_url,
        string $public_url,
        string $label = '',
        string $list_admin_url = '',
        string $icon = '',
        string $dark_icon = '',
    ): void {
        $this->set(new PostType($type, $admin_url, $public_url, $label, $list_admin_url, $icon, $dark_icon));
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
