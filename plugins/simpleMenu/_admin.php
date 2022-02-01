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

$core->addBehavior('adminDashboardIcons', 'simpleMenu_dashboard');
$core->addBehavior('adminDashboardFavorites', 'simpleMenu_dashboard_favs');
function simpleMenu_dashboard($core, $icons)
{
    $icons['simpleMenu'] = new ArrayObject([__('Simple menu'),
        $core->adminurl->get('admin.plugin.simpleMenu'),
        dcPage::getPF('simpleMenu/icon.svg'), ]);
}
function simpleMenu_dashboard_favs($core, $favs)
{
    $favs->register('simpleMenu', [
        'title'       => __('Simple menu'),
        'url'         => $core->adminurl->get('admin.plugin.simpleMenu'),
        'small-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
        'large-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
        'permissions' => 'usage,contentadmin',
    ]);
}

$_menu['Blog']->addItem(
    __('Simple menu'),
    $core->adminurl->get('admin.plugin.simpleMenu'),
    dcPage::getPF('simpleMenu/icon.svg'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.simpleMenu')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('usage,contentadmin', $core->blog->id)
);

require __DIR__ . '/_widgets.php';
