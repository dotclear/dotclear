<?php
/**
 * @brief blogroll, a plugin for Dotclear 2
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
    function ($favs) {
        $favs->register('blogroll', [
            'title'       => __('Blogroll'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.blogroll'),
            'small-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
            'permissions' => 'usage,contentadmin',
        ]);
    }
);
dcCore::app()->addBehavior(
    'adminUsersActionsHeaders',
    fn () => dcPage::jsModuleLoad('blogroll/js/_users_actions.js')
);

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Blogroll'),
    dcCore::app()->adminurl->get('admin.plugin.blogroll'),
    [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.blogroll')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
);

dcCore::app()->auth->setPermissionType('blogroll', __('manage blogroll'));

require __DIR__ . '/_widgets.php';
