<?php
/**
 * @brief widgets, a plugin for Dotclear 2
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
        $favs->register('widgets', [
            'title'      => __('Presentation widgets'),
            'url'        => dcCore::app()->adminurl->get('admin.plugin.widgets'),
            'small-icon' => [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
            'large-icon' => [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
        ]);
    }
);
dcCore::app()->addBehavior('adminRteFlagsV2', function ($rte) {
    $rte['widgets_text'] = [true, __('Widget\'s textareas')];
});

dcCore::app()->menu[dcAdmin::MENU_BLOG]->addItem(
    __('Presentation widgets'),
    dcCore::app()->adminurl->get('admin.plugin.widgets'),
    [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
    preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.widgets')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    dcCore::app()->auth->check('admin', dcCore::app()->blog->id)
);
