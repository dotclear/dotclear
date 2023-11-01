<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Menu;

/**
 * @brief   Utility class for Upgrade menu stack.
 *
 * @extends ArrayObject<string, Menu>
 *
 * @sicne 2.29
 */
class Menus extends ArrayObject
{
    // Menu sections
    public const MENU_SYSTEM = 'System';

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
        if (!App::task()->checkContext('UPGRADE') || !$this->offsetExists($section)) {
            return;
        }

        $url     = App::upgrade()->url()->get($adminurl);
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
        if (!App::task()->checkContext('UPGRADE')) {
            return;
        }

        // add menu sections
        $this->offsetSet(self::MENU_SYSTEM, new Menu('system-menu', ''));

        // add menu items
        $this->addItem(
            self::MENU_SYSTEM,
            __('Update'),
            'upgrade.upgrade',
            ['images/menu/update.svg', 'images/menu/update-dark.svg'],
            App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
            false,
            false,
            'Upgrade'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Incremental'),
            'upgrade.incremental',
            ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
            App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
            false,
            false,
            'Incremental'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Backups'),
            'upgrade.backup',
            ['images/menu/backup.svg', 'images/menu/backup-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Backup'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Languages'),
            'upgrade.langs',
            ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Langs'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Plugins'),
            'upgrade.plugins',
            ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Plugins'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Cache'),
            'upgrade.cache',
            ['images/menu/tools.svg', 'images/menu/tools-dark.svg'],
            App::auth()->isSuperAdmin(),
            false,
            false,
            'Cache'
        );
        $this->addItem(
            self::MENU_SYSTEM,
            __('Digests'),
            'upgrade.digests',
            ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
            App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
            false,
            false,
            'Digests'
        );
    }
}
