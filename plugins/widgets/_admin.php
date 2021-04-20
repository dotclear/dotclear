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

$core->addBehavior('adminDashboardFavorites', ['widgetsBehaviors', 'widgets_dashboard_favorites']);
$core->addBehavior('adminRteFlags', ['widgetsBehaviors', 'adminRteFlags']);

$_menu['Blog']->addItem(__('Presentation widgets'),
    $core->adminurl->get('admin.plugin.widgets'),
    dcPage::getPF('widgets/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.widgets')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id));

class widgetsBehaviors
{
    public static function widgets_dashboard_favorites($core, $favs)
    {
        $favs->register('widgets', [
            'title'      => __('Presentation widgets'),
            'url'        => $core->adminurl->get('admin.plugin.widgets'),
            'small-icon' => dcPage::getPF('widgets/icon.png'),
            'large-icon' => dcPage::getPF('widgets/icon-big.png')
        ]);
    }

    public static function adminRteFlags($core, $rte)
    {
        $rte['widgets_text'] = [true, __('Widget\'s textareas')];
    }
}
