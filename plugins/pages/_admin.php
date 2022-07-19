<?php
/**
 * @brief pages, a plugin for Dotclear 2
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

dcCore::app()->addBehavior('adminColumnsLists', function (dcCore $core, $cols) {
    // Set optional columns in pages lists
    $cols['pages'] = [__('Pages'), [
        'date'       => [true, __('Date')],
        'author'     => [true, __('Author')],
        'comments'   => [true, __('Comments')],
        'trackbacks' => [true, __('Trackbacks')],
    ]];
});

dcCore::app()->addBehavior('adminFiltersLists', function (dcCore $core, $sorts) {
    $sorts['pages'] = [
        __('Pages'),
        null,
        null,
        null,
        [__('entries per page'), 30],
    ];
});

dcCore::app()->addBehavior('adminDashboardFavorites', function (dcCore $core, $favs) {
    $favs->register('pages', [
        'title'        => __('Pages'),
        'url'          => dcCore::app()->adminurl->get('admin.plugin.pages'),
        'small-icon'   => [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
        'large-icon'   => [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
        'permissions'  => 'contentadmin,pages',
        'dashboard_cb' => ['pagesDashboard', 'pagesDashboardCB'],
        'active_cb'    => ['pagesDashboard', 'pagesActiveCB'],
    ]);
    $favs->register('newpage', [
        'title'       => __('New page'),
        'url'         => dcCore::app()->adminurl->get('admin.plugin.pages', ['act' => 'page']),
        'small-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
        'large-icon'  => [dcPage::getPF('pages/icon-np.svg'), dcPage::getPF('pages/icon-np-dark.svg')],
        'permissions' => 'contentadmin,pages',
        'active_cb'   => ['pagesDashboard', 'newPageActiveCB'],
    ]);
});

dcCore::app()->addBehavior(
    'adminUsersActionsHeaders',
    fn () => dcPage::jsLoad('index.php?pf=pages/js/_users_actions.js')
);

class pagesDashboard
{
    public static function pagesDashboardCB(dcCore $core, $v)
    {
        $params              = new ArrayObject();
        $params['post_type'] = 'page';
        $page_count          = dcCore::app()->blog->getPosts($params, true)->f(0);
        if ($page_count > 0) {
            $str_pages  = ($page_count > 1) ? __('%d pages') : __('%d page');
            $v['title'] = sprintf($str_pages, $page_count);
        }
    }

    public static function pagesActiveCB($request, $params)
    {
        return ($request                                                               == 'plugin.php') && isset($params['p']) && $params['p']                                                               == 'pages'
                                                                                                        && !(isset($params['act'])                                                               && $params['act'] == 'page');
    }

    public static function newPageActiveCB($request, $params)
    {
        return ($request                                                             == 'plugin.php') && isset($params['p']) && $params['p']                                                             == 'pages'
                                                                                                      && isset($params['act'])                                                             && $params['act'] == 'page';
    }
}

$_menu['Blog']->addItem(
    __('Pages'),
    dcCore::app()->adminurl->get('admin.plugin.pages'),
    [dcPage::getPF('pages/icon.svg'), dcPage::getPF('pages/icon-dark.svg')],
    preg_match('/plugin.php(.*)$/', $_SERVER['REQUEST_URI']) && !empty($_REQUEST['p']) && $_REQUEST['p'] == 'pages',
    dcCore::app()->auth->check('contentadmin,pages', dcCore::app()->blog->id)
);

dcCore::app()->auth->setPermissionType('pages', __('manage pages'));

require __DIR__ . '/_widgets.php';
