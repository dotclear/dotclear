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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Html;

/**
 * @brief   The module install process.
 * @ingroup simpleMenu
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // Menu par défaut
        $blog_url = Html::stripHostURL(App::blog()->url());

        $menu = new Menu([
            new MenuItem(
                'Home',
                'Recent posts',
                $blog_url
            ),
            new MenuItem(
                'Archive',
                'Archives',
                $blog_url . App::url()->getURLFor('archive')
            ),
        ]);

        App::blog()->settings()->get(My::WORKSPACE)->put(
            My::SETTING_MENU,
            $menu->getArray(),
            App::blogWorkspace()::NS_ARRAY,
            'Simple menu',
            false,
            true
        );

        App::blog()->settings()->get(My::WORKSPACE)->put(
            My::SETTING_ACTIVE,
            true,
            App::blogWorkspace()::NS_BOOL,
            'Active',
            false,
            true
        );

        return true;
    }
}
