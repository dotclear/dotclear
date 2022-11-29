<?php
/**
 * @brief simpleMenu, a plugin for Dotclear 2
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

dcCore::app()->addBehavior(
    'adminDashboardFavoritesV2',
    function (dcFavorites $favs) {
        $favs->register('simpleMenu', [
            'title'       => __('Simple menu'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
            'small-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
            'large-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]),
        ]);
    }
);

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Simple menu'),
    dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
    dcPage::getPF('simpleMenu/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.simpleMenu')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
        dcAuth::PERMISSION_USAGE,
        dcAuth::PERMISSION_CONTENT_ADMIN,
    ]), dcCore::app()->blog->id)
);

require __DIR__ . '/_widgets.php';
