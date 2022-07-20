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
    'adminDashboardFavorites',
    function (dcCore $core, $favs) {
        $favs->register('simpleMenu', [
            'title'       => __('Simple menu'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
            'small-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
            'large-icon'  => dcPage::getPF('simpleMenu/icon.svg'),
            'permissions' => 'usage,contentadmin',
        ]);
    }
);

dcCore::app()->menu['Blog']->addItem(
    __('Simple menu'),
    dcCore::app()->adminurl->get('admin.plugin.simpleMenu'),
    dcPage::getPF('simpleMenu/icon.svg'),
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.simpleMenu')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
);

require __DIR__ . '/_widgets.php';
