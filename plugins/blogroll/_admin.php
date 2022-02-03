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

$core->addBehavior(
    'adminDashboardFavorites',
    function ($core, $favs) {
        $favs->register('blogroll', [
            'title'       => __('Blogroll'),
            'url'         => $core->adminurl->get('admin.plugin.blogroll'),
            'small-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
            'large-icon'  => [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
            'permissions' => 'usage,contentadmin',
        ]);
    }
);
$core->addBehavior(
    'adminUsersActionsHeaders',
    fn () => dcPage::jsLoad(dcPage::getPF('blogroll/js/_users_actions.js'))
);

$_menu['Blog']->addItem(
    __('Blogroll'),
    $core->adminurl->get('admin.plugin.blogroll'),
    [dcPage::getPF('blogroll/icon.svg'), dcPage::getPF('blogroll/icon-dark.svg')],
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.blogroll')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id)
);

$core->auth->setPermissionType('blogroll', __('manage blogroll'));

require __DIR__ . '/_widgets.php';
