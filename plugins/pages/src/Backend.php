<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use dcAdmin;
use dcAuth;
use dcCore;
use dcFavorites;
use dcNsProcess;
use dcPage;
use initPages;

class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN');

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->auth->setPermissionType(initPages::PERMISSION_PAGES, __('manage pages'));

        dcCore::app()->addBehaviors([
            'adminColumnsListsV2'       => function (ArrayObject $cols) {
                $cols['pages'] = [__('Pages'), [
                    'date'       => [true, __('Date')],
                    'author'     => [true, __('Author')],
                    'comments'   => [true, __('Comments')],
                    'trackbacks' => [true, __('Trackbacks')],
                ]];
            },
            'adminFiltersListsV2'       => function (ArrayObject $sorts) {
                $sorts['pages'] = [
                    __('Pages'),
                    null,
                    null,
                    null,
                    [__('entries per page'), 30],
                ];
            },
            'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
                $favs->register('pages', [
                    'title'       => __('Pages'),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.pages'),
                    'small-icon'  => [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
                    'large-icon'  => [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcAuth::PERMISSION_CONTENT_ADMIN,
                        initPages::PERMISSION_PAGES,
                    ]),
                    'dashboard_cb' => function (ArrayObject $icon) {
                        $params              = new ArrayObject();
                        $params['post_type'] = 'page';
                        $page_count          = dcCore::app()->blog->getPosts($params, true)->f(0);
                        if ($page_count > 0) {
                            $str_pages     = ($page_count > 1) ? __('%d pages') : __('%d page');
                            $icon['title'] = sprintf($str_pages, $page_count);
                        }
                    },
                    'active_cb'    => function (string $request, array $params): bool {
                        return ($request == 'plugin.php') && isset($params['p']) && $params['p'] == 'pages' && !(isset($params['act']) && $params['act'] == 'page');
                    },
                ]);
                $favs->register('newpage', [
                    'title'       => __('New page'),
                    'url'         => dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page']),
                    'small-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
                    'large-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcAuth::PERMISSION_CONTENT_ADMIN,
                        initPages::PERMISSION_PAGES,
                    ]),
                    'active_cb' => function (string $request, array $params): bool {
                        return ($request == 'plugin.php') && isset($params['p']) && $params['p'] == 'pages' && isset($params['act']) && $params['act'] == 'page';
                    },
                ]);
            },
            'adminUsersActionsHeaders'  => fn () => dcPage::jsLoad('index.php?pf=pages/js/_users_actions.js'),
            'initWidgets'               => [Widgets::class, 'initWidgets'],
            'initDefaultWidgets'        => [Widgets::class, 'initDefaultWidgets'],
        ]);

        dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
            __('Pages'),
            dcCore::app()->adminurl->get('admin.plugin.pages'),
            [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
            preg_match('/plugin.php(.*)$/', $_SERVER['REQUEST_URI']) && !empty($_REQUEST['p']) && $_REQUEST['p'] == 'pages',
            dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id)
        );

        return true;
    }
}
