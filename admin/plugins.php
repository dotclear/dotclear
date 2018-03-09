<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('admin');

# -- Page helper --
$list = new adminModulesList(
    $core->plugins,
    DC_PLUGINS_ROOT,
    $core->blog->settings->system->store_plugin_url
);

adminModulesList::$allow_multi_install = (boolean) DC_ALLOW_MULTI_MODULES;
adminModulesList::$distributed_modules = explode(',', DC_DISTRIB_PLUGINS);

if ($core->plugins->disableDepModules($core->adminurl->get('admin.plugins', array()))) {
    exit;
}

# -- Display module configuration page --
if ($list->setConfiguration()) {

    # Get content before page headers
    include $list->includeConfiguration();

    # Gather content
    $list->getConfiguration();

    # Display page
    dcPage::open(__('Plugins management'),

        # --BEHAVIOR-- pluginsToolsHeaders
        $core->callBehavior('pluginsToolsHeaders', $core, true),

        dcPage::breadcrumb(
            array(
                html::escapeHTML($core->blog->name)                                  => '',
                __('Plugins management')                                             => $list->getURL('', false),
                '<span class="page-title">' . __('Plugin configuration') . '</span>' => ''
            ))
    );

    # Display previously gathered content
    $list->displayConfiguration();

    dcPage::helpBlock('core_plugins_conf');
    dcPage::close();

    # Stop reading code here
    return;
}

# -- Execute actions --
try {
    $list->doActions();
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# -- Plugin install --
$plugins_install = null;
if (!$core->error->flag()) {
    $plugins_install = $core->plugins->installModules();
}

# -- Page header --
dcPage::open(__('Plugins management'),
    dcPage::jsLoad('js/_plugins.js') .
    dcPage::jsPageTabs() .

    # --BEHAVIOR-- pluginsToolsHeaders
    $core->callBehavior('pluginsToolsHeaders', $core, false),

    dcPage::breadcrumb(
        array(
            __('System')             => '',
            __('Plugins management') => ''
        ))
);

# -- Plugins install messages --
if (!empty($plugins_install['success'])) {
    echo
    '<div class="static-msg">' . __('Following plugins have been installed:') . '<ul>';

    foreach ($plugins_install['success'] as $k => $v) {
        echo
            '<li>' . $k . '</li>';
    }

    echo
        '</ul></div>';
}
if (!empty($plugins_install['failure'])) {
    echo
    '<div class="error">' . __('Following plugins have not been installed:') . '<ul>';

    foreach ($plugins_install['failure'] as $k => $v) {
        echo
            '<li>' . $k . ' (' . $v . ')</li>';
    }

    echo
        '</ul></div>';
}

# -- Display modules lists --
if ($core->auth->isSuperAdmin()) {

    # Updated modules from repo
    $modules = $list->store->get(true);
    if (!empty($modules)) {
        echo
        '<div class="multi-part" id="update" title="' . html::escapeHTML(__('Update plugins')) . '">' .
        '<h3>' . html::escapeHTML(__('Update plugins')) . '</h3>' .
        '<p>' . sprintf(
            __('There is one plugin to update available from repository.', 'There are %s plugins to update available from repository.', count($modules)),
            count($modules)
        ) . '</p>';

        $list
            ->setList('plugin-update')
            ->setTab('update')
            ->setModules($modules)
            ->displayModules(
                /*cols */array('checkbox', 'icon', 'name', 'version', 'current_version', 'desc'),
                /* actions */array('update')
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __("Visit %s repository, the resources center for Dotclear."),
            '<a href="http://plugins.dotaddict.org/dc2/">Dotaddict</a>'
        ) .
            '</p>' .

            '</div>';
    }
}

echo
'<div class="multi-part" id="plugins" title="' . __('Installed plugins') . '">';

# Activated modules
$modules = $list->modules->getModules();
if (!empty($modules)) {

    echo
    '<h3>' . ($core->auth->isSuperAdmin() ? __('Activated plugins') : __('Installed plugins')) . '</h3>' .
    '<p class="more-info">' . __('You can configure and manage installed plugins from this list.') . '</p>';

    $list
        ->setList('plugin-activate')
        ->setTab('plugins')
        ->setModules($modules)
        ->displayModules(
            /* cols */array('expander', 'icon', 'name', 'version', 'desc', 'distrib', 'deps'),
            /* actions */array('deactivate', 'delete', 'behavior')
        );
}

# Deactivated modules
if ($core->auth->isSuperAdmin()) {
    $modules = $list->modules->getDisabledModules();
    if (!empty($modules)) {
        echo
        '<h3>' . __('Deactivated plugins') . '</h3>' .
        '<p class="more-info">' . __('Deactivated plugins are installed but not usable. You can activate them from here.') . '</p>';

        $list
            ->setList('plugin-deactivate')
            ->setTab('plugins')
            ->setModules($modules)
            ->displayModules(
                /* cols */array('expander', 'icon', 'name', 'version', 'desc', 'distrib'),
                /* actions */array('activate', 'delete')
            );
    }
}

echo
    '</div>';

if ($core->auth->isSuperAdmin() && $list->isWritablePath()) {

    # New modules from repo
    $search  = $list->getSearch();
    $modules = $search ? $list->store->search($search) : $list->store->get();

    if (!empty($search) || !empty($modules)) {
        echo
        '<div class="multi-part" id="new" title="' . __('Add plugins') . '">' .
        '<h3>' . __('Add plugins from repository') . '</h3>';

        $list
            ->setList('plugin-new')
            ->setTab('new')
            ->setModules($modules)
            ->displaySearch()
            ->displayIndex()
            ->displayModules(
                /* cols */array('expander', 'name', 'score', 'version', 'desc', 'deps'),
                /* actions */array('install'),
                /* nav limit */true
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __("Visit %s repository, the resources center for Dotclear."),
            '<a href="http://plugins.dotaddict.org/dc2/">Dotaddict</a>'
        ) .
            '</p>' .

            '</div>';
    }

    # Add a new plugin
    echo
    '<div class="multi-part" id="addplugin" title="' . __('Install or upgrade manually') . '">' .
    '<h3>' . __('Add plugins from a package') . '</h3>' .
    '<p class="more-info">' . __('You can install plugins by uploading or downloading zip files.') . '</p>';

    $list->displayManualForm();

    echo
        '</div>';
}

# --BEHAVIOR-- pluginsToolsTabs
$core->callBehavior('pluginsToolsTabs', $core);

# -- Notice for super admin --
if ($core->auth->isSuperAdmin() && !$list->isWritablePath()) {
    echo
    '<p class="warning">' . __('Some functions are disabled, please give write access to your plugins directory to enable them.') . '</p>';
}

dcPage::helpBlock('core_plugins');
dcPage::close();
