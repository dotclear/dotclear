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
use dcCore;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Backend\Utility;
use Dotclear\Core\Process;

class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Pages') . __('Serve entries as simple web pages');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        dcCore::app()->auth->setPermissionType(My::PERMISSION_PAGES, __('manage pages'));

        My::addBackendMenuItem(Utility::MENU_BLOG);

        dcCore::app()->addBehaviors([
            'adminColumnsListsV2' => function (ArrayObject $cols) {
                $cols['pages'] = [My::name(), [
                    'date'       => [true, __('Date')],
                    'author'     => [true, __('Author')],
                    'comments'   => [true, __('Comments')],
                    'trackbacks' => [true, __('Trackbacks')],
                ]];
            },
            'adminFiltersListsV2' => function (ArrayObject $sorts) {
                $sorts['pages'] = [
                    My::name(),
                    null,
                    null,
                    null,
                    [__('entries per page'), 30],
                ];
            },
            'adminDashboardFavoritesV2' => function (Favorites $favs) {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                        My::PERMISSION_PAGES,
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
                    'active_cb' => fn (string $request, array $params): bool => isset($params['p']) && $params['p'] == My::id() && !(isset($params['act']) && $params['act'] == 'page'),
                ]);
                $favs->register('newpage', [
                    'title'       => __('New page'),
                    'url'         => My::manageUrl(['act' => 'page']),
                    'small-icon'  => My::icons('np'),
                    'large-icon'  => My::icons('np'),
                    'permissions' => dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                        My::PERMISSION_PAGES,
                    ]),
                    'active_cb' => fn (string $request, array $params): bool => isset($params['p']) && $params['p'] == My::id() && isset($params['act']) && $params['act'] == 'page',
                ]);
            },
            'adminUsersActionsHeaders' => fn () => My::jsLoad('_users_actions.js'),
            'initWidgets'              => [Widgets::class, 'initWidgets'],
            'initDefaultWidgets'       => [Widgets::class, 'initDefaultWidgets'],
        ]);

        return true;
    }
}
