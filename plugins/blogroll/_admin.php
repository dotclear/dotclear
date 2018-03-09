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

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$core->addBehavior('adminDashboardIcons', 'blogroll_dashboard');
$core->addBehavior('adminDashboardFavorites', 'blogroll_dashboard_favorites');
$core->addBehavior('adminUsersActionsHeaders', 'blogroll_users_actions_headers');

function blogroll_dashboard($core, $icons)
{
    $icons['blogroll'] = new ArrayObject(array(
        __('Blogroll'),
        $core->adminurl->get('admin.plugin.blogroll'),
        dcPage::getPF('blogroll/icon.png')
    ));
}
function blogroll_dashboard_favorites($core, $favs)
{
    $favs->register('blogroll', array(
        'title'       => __('Blogroll'),
        'url'         => $core->adminurl->get('admin.plugin.blogroll'),
        'small-icon'  => dcPage::getPF('blogroll/icon-small.png'),
        'large-icon'  => dcPage::getPF('blogroll/icon.png'),
        'permissions' => 'usage,contentadmin'
    ));
}
function blogroll_users_actions_headers()
{
    global $core;

    return dcPage::jsLoad(dcPage::getPF('blogroll/js/_users_actions.js'));
}

$_menu['Blog']->addItem(__('Blogroll'),
    $core->adminurl->get('admin.plugin.blogroll'),
    dcPage::getPF('blogroll/icon-small.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.blogroll')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id));

$core->auth->setPermissionType('blogroll', __('manage blogroll'));

require dirname(__FILE__) . '/_widgets.php';
