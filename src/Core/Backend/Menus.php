<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Utility class for admin menu stack.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use dcCore;

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
     * @param 	string 	$section 	The menu section
     * @param 	string 	$menu 		The menu instance
     */
    public function prependSection(string $section, Menu $menu): void
    {
        $stack = $this->getArrayCopy();
        $stack = [$section => $menu] + $stack;
        $this->exchangeArray($stack);
    }

    /**
     * Set default menu titles and items.
     */
    public function setDefaultItems(): void
    {
        $this->offsetSet(self::MENU_BLOG, new Menu('blog-menu', __('Blog')));
        $this->offsetSet(self::MENU_SYSTEM, new Menu('system-menu', __('System settings')));
        $this->offsetSet(self::MENU_PLUGINS, new Menu('plugins-menu', __('Plugins')));

        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog)) {
            return;
        }

        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Blog appearance'),
            'admin.blog.theme',
            ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'BlogTheme'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Blog settings'),
            'admin.blog.pref',
            ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'BlogPref'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Media manager'),
            'admin.media',
            ['images/menu/media.svg', 'images/menu/media-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_MEDIA,
                dcCore::app()->auth::PERMISSION_MEDIA_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'Media'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Categories'),
            'admin.categories',
            ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CATEGORIES,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'Categories'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Search'),
            'admin.search',
            ['images/menu/search.svg','images/menu/search-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'Search'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Comments'),
            'admin.comments',
            ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'Comments'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('Posts'),
            'admin.posts',
            ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id),
            false,
            false,
            'Posts'
        );
        Helper::addMenuItem(
            self::MENU_BLOG,
            __('New post'),
            'admin.post',
            ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id),
            true,
            true,
            'NewPost'
        );

        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('My preferences'),
            'admin.user.preferences',
            ['images/menu/user-pref.svg', 'images/menu/user-pref.svg'],
            true,
            false,
            false,
            'UserPref'
        );
        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('Update'),
            'admin.update',
            ['images/menu/update.svg', 'images/menu/update-dark.svg'],
            dcCore::app()->auth->isSuperAdmin() && is_readable(DC_DIGESTS),
            false,
            false,
            'Update'
        );
        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('Languages'),
            'admin.langs',
            ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
            dcCore::app()->auth->isSuperAdmin(),
            false,
            false,
            'Langs'
        );
        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('Plugins management'),
            'admin.plugins',
            ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
            dcCore::app()->auth->isSuperAdmin(),
            false,
            false,
            'Plugins'
        );
        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('Users'),
            'admin.users',
            'images/menu/users.svg',
            dcCore::app()->auth->isSuperAdmin(),
            false,
            false,
            'Users'
        );
        Helper::addMenuItem(
            self::MENU_SYSTEM,
            __('Blogs'),
            'admin.blogs',
            ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
            dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(
                dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_USAGE,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]),
                dcCore::app()->blog->id
            ) && dcCore::app()->auth->getBlogCount() > 1,
            false,
            false,
            'Blogs'
        );
    }
}
