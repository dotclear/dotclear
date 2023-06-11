<?php
/**
 * @brief pings, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pings;

use ArrayObject;
use dcAdmin;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        return (static::$init = My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        My::addBackendMenuItem(dcAdmin::MENU_BLOG, [], '');

        dcCore::app()->addBehaviors([
            'adminPostHeaders'     => fn () => dcPage::jsModuleLoad(My::id() . '/js/post.js'),
            'adminPostFormItems'   => [BackendBehaviors::class, 'pingsFormItems'],
            'adminAfterPostCreate' => [BackendBehaviors::class, 'doPings'],
            'adminAfterPostUpdate' => [BackendBehaviors::class, 'doPings'],

            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register(My::id(), [
                    'title'      => My::name(),
                    'url'        => My::manageUrl(),
                    'small-icon' => My::icons(),
                    'large-icon' => My::icons(),
                ]);
            },
            'adminPageHelpBlock' => function (ArrayObject $blocks) {
                if (array_search('core_post', $blocks->getArrayCopy(), true) !== false) {
                    $blocks->append('pings_post');
                }
            },
        ]);

        return true;
    }
}
