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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcCore::app()->auth->setPermissionType(dcPages::PERMISSION_PAGES, __('manage pages'));

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
                dcPages::PERMISSION_PAGES,
            ]),
            'dashboard_cb' => [pagesDashboard::class, 'pagesDashboardCB'],
            'active_cb'    => [pagesDashboard::class, 'pagesActiveCB'],
        ]);
        $favs->register('newpage', [
            'title'       => __('New page'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page']),
            'small-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
            'large-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_CONTENT_ADMIN,
                dcPages::PERMISSION_PAGES,
            ]),
            'active_cb' => [pagesDashboard::class, 'newPageActiveCB'],
        ]);
    },
    'adminUsersActionsHeaders'  => fn () => dcPage::jsLoad('index.php?pf=pages/js/_users_actions.js'),
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

require __DIR__ . '/_widgets.php';
