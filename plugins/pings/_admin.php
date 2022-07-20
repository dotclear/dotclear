<?php
/**
 * @brief pings, a plugin for Dotclear 2
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

dcCore::app()->menu['Blog']->addItem(
    __('Pings'),
    dcCore::app()->adminurl->get('admin.plugin.pings'),
    [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.pings')) . '/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);

$__autoload['pingsAdminBehaviors'] = __DIR__ . '/lib.pings.php';

dcCore::app()->addBehavior('adminPostHeaders', ['pingsAdminBehaviors', 'pingJS']);
dcCore::app()->addBehavior('adminPostFormItems', ['pingsAdminBehaviors', 'pingsFormItems']);
dcCore::app()->addBehavior('adminAfterPostCreate', ['pingsAdminBehaviors', 'doPings']);
dcCore::app()->addBehavior('adminAfterPostUpdate', ['pingsAdminBehaviors', 'doPings']);

dcCore::app()->addBehavior(
    'adminDashboardFavorites',
    function (dcCore $core, $favs) {
        $favs->register('pings', [
            'title'      => __('Pings'),
            'url'        => dcCore::app()->adminurl->get('admin.plugin.pings'),
            'small-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
            'large-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
        ]);
    }
);

dcCore::app()->addBehavior('adminPageHelpBlock', function ($blocks) {
    $found = false;
    foreach ($blocks as $block) {
        if ($block == 'core_post') {
            $found = true;

            break;
        }
    }
    if (!$found) {
        return;
    }
    $blocks[] = 'pings_post';
});
