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

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Pings'),
    dcCore::app()->adminurl->get('admin.plugin.pings'),
    [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.pings')) . '/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->isSuperAdmin()
);

dcCore::app()->addBehaviors([
    'adminPostHeaders'          => [pingsAdminBehaviors::class, 'pingJS'],
    'adminPostFormItems'        => [pingsAdminBehaviors::class, 'pingsFormItems'],
    'adminAfterPostCreate'      => [pingsAdminBehaviors::class, 'doPings'],
    'adminAfterPostUpdate'      => [pingsAdminBehaviors::class, 'doPings'],

    'adminDashboardFavoritesV2' => function (dcFavorites $favs) {
        $favs->register('pings', [
            'title'      => __('Pings'),
            'url'        => dcCore::app()->adminurl->get('admin.plugin.pings'),
            'small-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
            'large-icon' => [dcPage::getPF('pings/icon.svg'), dcPage::getPF('pings/icon-dark.svg')],
        ]);
    },
    'adminPageHelpBlock'        => function (ArrayObject $blocks) {
        if (array_search('core_post', $blocks->getArrayCopy(), true) !== false) {
            $blocks->append('pings_post');
        }
    },
]);
