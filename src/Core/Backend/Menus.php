<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use Dotclear\App;

/**
 * Utility class for admin menu stack.
 *
 * @extends ArrayObject<string, Menu>
 */
class Menus extends ArrayObject
{
    // Menu sections
    public const MENU_FAVORITES = 'Favorites';
    public const MENU_BLOG      = 'Blog';
    public const MENU_SYSTEM    = 'System';
    public const MENU_PLUGINS   = 'Plugins';

    /**
     * Prepend menu group.
     *
     * @param   string  $section    The menu section
     * @param   Menu    $menu       The menu instance
     */
    public function prependSection(string $section, Menu $menu): void
    {
        $stack = $this->getArrayCopy();
        $stack = [$section => $menu] + $stack;
        $this->exchangeArray($stack);
    }

    /**
     * Adds a menu item.
     *
     * @param      string  $section   The section
     * @param      string  $desc      The item description
     * @param      string  $adminurl  The URL scheme
     * @param      mixed   $icon      The icon(s)
     * @param      mixed   $perm      The permission(s)
     * @param      bool    $pinned    Is pinned at begining
     * @param      bool    $strict    Strict URL scheme or allow query string parameters
     * @param      string  $id        The menu item id
     */
    public function addItem(string $section, string $desc, string $adminurl, $icon, $perm, bool $pinned = false, bool $strict = false, ?string $id = null): void
    {
        if (!App::task()->checkContext('BACKEND') || !$this->offsetExists($section)) {
            return;
        }

        $url     = App::backend()->url()->get($adminurl);
        $pattern = '@' . preg_quote($url) . ($strict ? '' : '(&.*)?') . '$@';
        $this->offsetGet($section)?->prependItem(
            $desc,
            $url,
            $icon,
            preg_match($pattern, (string) $_SERVER['REQUEST_URI']),
            $perm,
            $id,
            null,
            $pinned
        );
    }

    /**
     * Set default menu titles and items.
     */
    public function setDefaultItems(): void
    {
        // nullsafe and context
        if (!App::task()->checkContext('BACKEND') || !App::blog()->isDefined()) {
            return;
        }

        // add menu sections
        $this->offsetSet(self::MENU_BLOG, new Menu('blog-menu', __('Blog')));
        $this->offsetSet(self::MENU_SYSTEM, new Menu('system-menu', __('System settings')));
        $this->offsetSet(self::MENU_PLUGINS, new Menu('plugins-menu', __('Plugins')));

        // add menu items
        $this->addItem(
            self::MENU_BLOG,
            __('Blog appearance'),
            'admin.blog.theme',
            ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'BlogTheme'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Blog settings'),
            'admin.blog.pref',
            ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'BlogPref'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Media manager'),
            'admin.media',
            ['images/menu/media.svg', 'images/menu/media-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_MEDIA,
                App::auth()::PERMISSION_MEDIA_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'Media'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Categories'),
            'admin.categories',
            ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_CATEGORIES,
            ]), App::blog()->id()),
            false,
            false,
            'Categories'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Search'),
            'admin.search',
            ['images/menu/search.svg','images/menu/search-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'Search'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Comments'),
            'admin.comments',
            ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'Comments'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('Posts'),
            'admin.posts',
            ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),
            false,
            false,
            'Posts'
        );
        $this->addItem(
            self::MENU_BLOG,
            __('New post'),
            'admin.post',
            ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
            App::auth()->check(App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]), App::blog()->id()),
            true,
            true,
            'NewPost'
        );

        $this->addItem(
            self::MENU_SYSTEM,
            __('My preferences'),
            'admin.user.preferences',
            ['images/menu/user-pref.svg', 'images/menu/user-pref.svg'],
            true,
            false,
            false,
            'UserPref'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Update'),
            'upgrade.home',
            ['images/menu/update.svg', 'images/menu/update-dark.svg'],
            App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
            false,
            false,
            'Update'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Languages'),
            'admin.langs',
            ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Langs'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Plugins management'),
            'admin.plugins',
            ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Plugins'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Users'),
            'admin.users',
            'images/menu/users.svg',
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Users'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Blogs'),
            'admin.blogs',
            ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
            App::auth()->isSuperAdmin() || App::auth()->check(
                App::auth()->makePermissions([
                    App::auth()::PERMISSION_USAGE,
                    App::auth()::PERMISSION_CONTENT_ADMIN,
                ]),
                App::blog()->id()
            ) && App::auth()->getBlogCount() > 1,
            false,
            false,
            'Blogs'
        );
    }
}
