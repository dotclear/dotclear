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
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Pings'),
            dcCore::app()->adminurl->get('admin.plugin.pings'),
            [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.pings')) . '/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        dcCore::app()->addBehaviors([
            'adminPostHeaders'     => fn () => dcPage::jsModuleLoad('pings/js/post.js'),
            'adminPostFormItems'   => [BackendBehaviors::class, 'pingsFormItems'],
            'adminAfterPostCreate' => [BackendBehaviors::class, 'doPings'],
            'adminAfterPostUpdate' => [BackendBehaviors::class, 'doPings'],

            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('pings', [
                    'title'      => __('Pings'),
                    'url'        => dcCore::app()->adminurl->get('admin.plugin.pings'),
                    'small-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
                    'large-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
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
