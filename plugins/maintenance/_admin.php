<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
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

// Sidebar menu
$_menu['Plugins']->addItem(
    __('Maintenance'),
    $core->adminurl->get('admin.plugin.maintenance'),
    dcPage::getPF('maintenance/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.maintenance')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->check('admin', $core->blog->id)
);

// Admin behaviors
$core->addBehavior('dcMaintenanceInit', ['dcMaintenanceAdmin', 'dcMaintenanceInit']);
$core->addBehavior('adminDashboardFavorites', ['dcMaintenanceAdmin', 'adminDashboardFavorites']);
$core->addBehavior('adminDashboardContents', ['dcMaintenanceAdmin', 'adminDashboardItems']);
$core->addBehavior('adminDashboardOptionsForm', ['dcMaintenanceAdmin', 'adminDashboardOptionsForm']);
$core->addBehavior('adminAfterDashboardOptionsUpdate', ['dcMaintenanceAdmin', 'adminAfterDashboardOptionsUpdate']);
$core->addBehavior('adminPageHelpBlock', ['dcMaintenanceAdmin', 'adminPageHelpBlock']);
$core->addBehavior('pluginsToolsHeaders', ['dcMaintenanceAdmin', 'pluginsToolsHeaders']);

/**
@ingroup PLUGIN_MAINTENANCE
@nosubgrouping
@brief Maintenance plugin admin class.

Group of methods used on behaviors.
 */
class dcMaintenanceAdmin
{
    /**
     * Register default tasks.
     *
     * @param      dcMaintenance  $maintenance  dcMaintenance instance
     */
    public static function dcMaintenanceInit(dcMaintenance $maintenance)
    {
        $maintenance
            ->addTab('maintenance', __('Servicing'), ['summary' => __('Tools to maintain the performance of your blogs.')])
            ->addTab('backup', __('Backup'), ['summary' => __('Tools to back up your content.')])
            ->addTab('dev', __('Development'), ['summary' => __('Tools to assist in development of plugins, themes and core.')])

            ->addGroup('optimize', __('Optimize'))
            ->addGroup('index', __('Count and index'))
            ->addGroup('purge', __('Purge'))
            ->addGroup('other', __('Other'))
            ->addGroup('zipblog', __('Current blog'))
            ->addGroup('zipfull', __('All blogs'))

            ->addGroup('l10n', __('Translations'), ['summary' => __('Maintain translations')])

            ->addTask('dcMaintenanceCache')
            ->addTask('dcMaintenanceCSP')
            ->addTask('dcMaintenanceIndexposts')
            ->addTask('dcMaintenanceIndexcomments')
            ->addTask('dcMaintenanceCountcomments')
            ->addTask('dcMaintenanceSynchpostsmeta')
            ->addTask('dcMaintenanceLogs')
            ->addTask('dcMaintenanceVacuum')
            ->addTask('dcMaintenanceZipmedia')
            ->addTask('dcMaintenanceZiptheme')
        ;
    }

    /**
     * Favorites
     *
     * @param      dcCore        $core   dcCore instance
     * @param      dcFavorites   $favs   favs
     */
    public static function adminDashboardFavorites(dcCore $core, dcFavorites $favs)
    {
        $favs->register('maintenance', [
            'title'        => __('Maintenance'),
            'url'          => $core->adminurl->get('admin.plugin.maintenance'),
            'small-icon'   => dcPage::getPF('maintenance/icon.png'),
            'large-icon'   => dcPage::getPF('maintenance/icon-big.png'),
            'permissions'  => 'admin',
            'active_cb'    => ['dcMaintenanceAdmin', 'adminDashboardFavoritesActive'],
            'dashboard_cb' => ['dcMaintenanceAdmin', 'adminDashboardFavoritesCallback']
        ]);
    }

    /**
     * Is maintenance plugin active
     *
     * @param      string  $request  The request
     * @param      array   $params   The parameters
     *
     * @return     bool    true if maintenance plugin is active else false
     */
    public static function adminDashboardFavoritesActive($request, $params)
    {
        return $request == 'plugin.php' && isset($params['p']) && $params['p'] == 'maintenance';
    }

    /**
     * Favorites hack.
     *
     * This updates maintenance fav icon text
     * if there are tasks required maintenance.
     *
     * @param      dcCore       $core   The core
     * @param      arrayObject  $fav    The fav
     */
    public static function adminDashboardFavoritesCallback(dcCore $core, $fav)
    {
        // Check user option
        $core->auth->user_prefs->addWorkspace('maintenance');
        if (!$core->auth->user_prefs->maintenance->dashboard_icon) {
            return;
        }

        // Check expired tasks
        $maintenance = new dcMaintenance($core);
        $count       = 0;
        foreach ($maintenance->getTasks() as $t) {
            if ($t->expired() !== false) {
                $count++;
            }
        }

        if (!$count) {
            return;
        }

        $fav['title'] .= '<br />' . sprintf(__('One task to execute', '%s tasks to execute', $count), $count);
        $fav['large-icon'] = dcPage::getPF('maintenance/icon-big-update.png');
    }

    /**
     * Dashboard items stack.
     *
     * @param      dcCore       $core   The core
     * @param      arrayObject  $items  The items
     */
    public static function adminDashboardItems(dcCore $core, $items)
    {
        $core->auth->user_prefs->addWorkspace('maintenance');
        if (!$core->auth->user_prefs->maintenance->dashboard_item) {
            return;
        }

        $maintenance = new dcMaintenance($core);

        $lines = [];
        foreach ($maintenance->getTasks() as $t) {
            $ts = $t->expired();
            if ($ts === false) {
                continue;
            }

            $lines[] = '<li title="' . ($ts === null ?
                __('This task has never been executed.')
                :
                sprintf(__('Last execution of this task was on %s.'),
                    dt::dt2str($core->blog->settings->system->date_format, $ts) . ' ' .
                    dt::dt2str($core->blog->settings->system->time_format, $ts)
                )
            ) . '">' . $t->task() . '</li>';
        }

        if (empty($lines)) {
            return;
        }

        $items[] = new ArrayObject([
            '<div id="maintenance-expired" class="box small">' .
            '<h3><img src="' . dcPage::getPF('maintenance/icon-small.png') . '" alt="" /> ' . __('Maintenance') . '</h3>' .
            '<p class="warning no-margin">' . sprintf(__('There is a task to execute.', 'There are %s tasks to execute.', count($lines)), count($lines)) . '</p>' .
            '<ul>' . implode('', $lines) . '</ul>' .
            '<p><a href="' . $core->adminurl->get('admin.plugin.maintenance') . '">' . __('Manage tasks') . '</a></p>' .
            '</div>'
        ]);
    }

    /**
     * User preferences form.
     *
     * This add options for superadmin user
     * to show or not expired taks.
     *
     * @param      dcCore  $core   The core
     */
    public static function adminDashboardOptionsForm(dcCore $core)
    {
        $core->auth->user_prefs->addWorkspace('maintenance');

        echo
        '<div class="fieldset">' .
        '<h4>' . __('Maintenance') . '</h4>' .

        '<p><label for="maintenance_dashboard_icon" class="classic">' .
        form::checkbox('maintenance_dashboard_icon', 1, $core->auth->user_prefs->maintenance->dashboard_icon) .
        __('Display overdue tasks counter on maintenance dashboard icon') . '</label></p>' .

        '<p><label for="maintenance_dashboard_item" class="classic">' .
        form::checkbox('maintenance_dashboard_item', 1, $core->auth->user_prefs->maintenance->dashboard_item) .
        __('Display overdue tasks list on dashboard items') . '</label></p>' .

            '</div>';
    }

    /**
     * User preferences update.
     *
     * @param      string  $user_id  The user identifier
     */
    public static function adminAfterDashboardOptionsUpdate($user_id = null)
    {
        global $core;

        if (is_null($user_id)) {
            return;
        }

        $core->auth->user_prefs->addWorkspace('maintenance');
        $core->auth->user_prefs->maintenance->put('dashboard_icon', !empty($_POST['maintenance_dashboard_icon']), 'boolean');
        $core->auth->user_prefs->maintenance->put('dashboard_item', !empty($_POST['maintenance_dashboard_item']), 'boolean');
    }

    /**
     * Build a well sorted help for tasks.
     *
     * This method is not so good if used with lot of tranlsations
     * as it grows memory usage and translations files size,
     * it is better to use help ressource files
     * but keep it for exemple of how to use behavior adminPageHelpBlock.
     * Cheers, JC
     *
     * @param      arrayObject  $blocks  The blocks
     */
    public static function adminPageHelpBlock($blocks)
    {
        $found = false;
        foreach ($blocks as $block) {
            if ($block == 'maintenancetasks') {
                $found = true;

                break;
            }
        }
        if (!$found) {
            return;
        }

        $maintenance = new dcMaintenance($GLOBALS['core']);

        $res_tab = '';
        foreach ($maintenance->getTabs() as $tab_obj) {
            $res_group = '';
            foreach ($maintenance->getGroups() as $group_obj) {
                $res_task = '';
                foreach ($maintenance->getTasks() as $t) {
                    if ($t->group() != $group_obj->id()
                        || $t->tab() != $tab_obj->id()) {
                        continue;
                    }
                    if (($desc = $t->description()) != '') {
                        $res_task .= '<dt>' . $t->task() . '</dt>' .
                            '<dd>' . $desc . '</dd>';
                    }
                }
                if (!empty($res_task)) {
                    $desc = $group_obj->description ?: $group_obj->summary;

                    $res_group .= '<h5>' . $group_obj->name() . '</h5>' .
                        ($desc ? '<p>' . $desc . '</p>' : '') .
                        '<dl>' . $res_task . '</dl>';
                }
            }
            if (!empty($res_group)) {
                $desc = $tab_obj->description ?: $tab_obj->summary;

                $res_tab .= '<h4>' . $tab_obj->name() . '</h4>' .
                    ($desc ? '<p>' . $desc . '</p>' : '') .
                    $res_group;
            }
        }
        if (!empty($res_tab)) {
            $res          = new ArrayObject();
            $res->content = $res_tab;   // @phpstan-ignore-line
            $blocks[]     = $res;
        }
    }

    /**
     * Add javascript for plugin configuration.
     *
     * @param      dcCore  $core    The core
     * @param      string  $module  The module
     *
     * @return     mixed
     */
    public static function pluginsToolsHeaders(dcCore $core, $module)
    {
        if ($module == 'maintenance') {
            return dcPage::jsLoad(dcPage::getPF('maintenance/js/settings.js'));
        }
    }
}
