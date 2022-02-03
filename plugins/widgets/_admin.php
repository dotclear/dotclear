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

$core->addBehavior(
    'adminDashboardFavorites',
    function ($core, $favs) {
        $favs->register('widgets', [
            'title'      => __('Presentation widgets'),
            'url'        => $core->adminurl->get('admin.plugin.widgets'),
            'small-icon' => [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
            'large-icon' => [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
        ]);
    }
);
$core->addBehavior('adminRteFlags', function ($core, $rte) {
    $rte['widgets_text'] = [true, __('Widget\'s textareas')];
});

$_menu['Blog']->addItem(
    __('Presentation widgets'),
    $core->adminurl->get('admin.plugin.widgets'),
    [dcPage::getPF('widgets/icon.svg'), dcPage::getPF('widgets/icon-dark.svg')],
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.widgets')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id)
);
