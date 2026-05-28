<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\simpleMenu;

use Dotclear\App;

/**
 * @brief   The simple menu main object.
 * @ingroup simpleMenu
 *
 * This class should be used to cope with loading and saving to blog settings
 *
 * Note that it's compatible with Dotclear 2.38 and previous
 *
 * @phpstan-import-type TSimpleMenuItem from MenuItem
 */
class SimpleMenu
{
    public function __construct(
        protected Menu $menu
    ) {
    }

    public function menu(): Menu
    {
        return $this->menu;
    }

    /**
     * Get an array of menu items
     *
     * May be used to store menu in settings
     *
     * @return array<int, TSimpleMenuItem>
     */
    public function getArray(): array
    {
        return $this->menu->getArray();
    }

    /**
     * Save menu in blog settings
     *
     * @param  string $workspace    Name of workspace
     * @param  string $setting_name Name of setting
     */
    public function save(string $workspace, string $setting_name): void
    {
        App::blog()->settings()->get($workspace)->put($setting_name, $this->menu->getArray(), App::blogWorkspace()::NS_ARRAY);
    }

    /**
     * Load menu from blog settings
     *
     * @param  string $workspace    Name of workspace
     * @param  string $setting_name Name of setting
     */
    public static function load(string $workspace, string $setting_name): self
    {
        $menu = new Menu();

        $setting = App::blog()->settings()->get($workspace)->get($setting_name);
        if (is_array($setting)) {
            foreach ($setting as $item) {
                if (is_array($item)) {
                    $menu->add(MenuItem::load($item));
                }
            }
        }

        return new self($menu);
    }
}
