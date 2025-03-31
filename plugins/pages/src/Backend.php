<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\pages;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\PostType;
use Dotclear\Core\Process;

/**
 * @brief   The module backend process.
 * @ingroup pages
 */
class Backend extends Process
{
    public static function init(): bool
    {
        // Dead but useful code (for l10n)
        __('Pages');
        __('Serve entries as simple web pages');

        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $icon  = $icon_dark = '';
        $icons = My::icons('np');
        if ($icons !== []) {
            $icon      = $icons[0];
            $icon_dark = $icons[1] ?? $icons[0];
        }

        App::auth()->setPermissionType(Pages::PERMISSION_PAGES, __('manage pages'));

        App::postTypes()->set(new PostType(
            'page',
            urldecode(My::manageUrl(['p' => 'pages', 'act' => 'page', 'id' => '%d'], '&')),
            App::url()->getURLFor('pages', '%s'),
            'Pages',
            urldecode(My::manageUrl(['p' => 'pages', 'act' => 'list'], '&')),   // Admin URL for list of pages
            $icon,
            $icon_dark,
        ));

        My::addBackendMenuItem(App::backend()->menus()::MENU_BLOG);

        App::behavior()->addBehaviors([
            'adminColumnsListsV2' => function (ArrayObject $cols): string {
                $cols['pages'] = [My::name(), [
                    'date'       => [true, __('Date')],
                    'author'     => [true, __('Author')],
                    'comments'   => [true, __('Comments')],
                    'trackbacks' => [true, __('Trackbacks')],
                ]];

                return '';
            },
            'adminFiltersListsV2' => function (ArrayObject $sorts): string {
                $sorts['pages'] = [
                    My::name(),
                    null,
                    null,
                    null,
                    [__('pages per page'), 30],
                ];

                return '';
            },
            'adminDashboardFavoritesV2' => function (Favorites $favs): string {
                $favs->register(My::id(), [
                    'title'       => My::name(),
                    'url'         => My::manageUrl(),
                    'small-icon'  => My::icons(),
                    'large-icon'  => My::icons(),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                        Pages::PERMISSION_PAGES,
                    ]),
                    'dashboard_cb' => function (ArrayObject $icon): void {
                        /**
                         * @var        ArrayObject<string, mixed>
                         */
                        $params              = new ArrayObject();
                        $params['post_type'] = 'page';
                        $page_count          = App::blog()->getPosts($params, true)->f(0);
                        if ($page_count > 0) {
                            $str_pages     = ($page_count > 1) ? __('%d pages') : __('%d page');
                            $icon['title'] = sprintf($str_pages, $page_count);
                        }
                    },
                    'active_cb' => fn (string $request, array $params): bool => isset($params['p']) && $params['p'] === My::id() && !isset($params['act']),
                ]);
                $favs->register('newpage', [
                    'title'       => __('New page'),
                    'url'         => My::manageUrl(['act' => 'page']),
                    'small-icon'  => My::icons('np'),
                    'large-icon'  => My::icons('np'),
                    'permissions' => App::auth()->makePermissions([
                        App::auth()::PERMISSION_CONTENT_ADMIN,
                        Pages::PERMISSION_PAGES,
                    ]),
                    'active_cb' => fn (string $request, array $params): bool => isset($params['p']) && $params['p'] === My::id() && isset($params['act']) && $params['act'] == 'page' && !isset($params['id']),
                ]);

                return '';
            },
            'adminUsersActionsHeaders' => fn (): string => My::jsLoad('_users_actions'),
            'initWidgets'              => Widgets::initWidgets(...),
            'initDefaultWidgets'       => Widgets::initDefaultWidgets(...),
        ]);

        return true;
    }
}
