<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

# -- Page helper --
$list = new adminModulesList(
    dcCore::app()->plugins,
    DC_PLUGINS_ROOT,
    dcCore::app()->blog->settings->system->store_plugin_url,
    !empty($_GET['nocache'])
);

adminModulesList::$allow_multi_install = (bool) DC_ALLOW_MULTI_MODULES;
adminModulesList::$distributed_modules = explode(',', DC_DISTRIB_PLUGINS);

if (dcCore::app()->plugins->disableDepModules(dcCore::app()->adminurl->get('admin.plugins', []))) {
    exit;
}

# -- Display module configuration page --
if ($list->setConfiguration()) {

    # Get content before page headers
    include $list->includeConfiguration();

    # Gather content
    $list->getConfiguration();

    # Display page
    dcPage::open(
        __('Plugins management'),

        # --BEHAVIOR-- pluginsToolsHeaders
        dcCore::app()->callBehavior('pluginsToolsHeadersV2', true),
        dcPage::breadcrumb(
            [
                html::escapeHTML(dcCore::app()->blog->name)                          => '',
                __('Plugins management')                                             => $list->getURL('', false),
                '<span class="page-title">' . __('Plugin configuration') . '</span>' => '',
            ]
        )
    );

    # Display previously gathered content
    $list->displayConfiguration();

    dcPage::helpBlock('core_plugins_conf');
    dcPage::close();

    # Stop reading code here
    return;
}

dcPage::checkSuper();

# -- Execute actions --
try {
    $list->doActions();
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

# -- Plugin install --
$plugins_install = null;
if (!dcCore::app()->error->flag()) {
    $plugins_install = dcCore::app()->plugins->installModules();
}

# -- Page header --
dcPage::open(
    __('Plugins management'),
    dcPage::jsLoad('js/_plugins.js') .
    dcPage::jsPageTabs() .

    # --BEHAVIOR-- pluginsToolsHeaders
    dcCore::app()->callBehavior('pluginsToolsHeadersV2', false),
    dcPage::breadcrumb(
        [
            __('System')             => '',
            __('Plugins management') => '',
        ]
    )
);

# -- Plugins install messages --
if (!empty($plugins_install['success'])) {
    echo
    '<div class="static-msg">' . __('Following plugins have been installed:') . '<ul>';

    foreach ($plugins_install['success'] as $k => $v) {
        $info = implode(' - ', $list->getSettingsUrls(dcCore::app(), $k, true));
        echo
            '<li>' . $k . ($info !== '' ? ' → ' . $info : '') . '</li>';
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
if (dcCore::app()->auth->isSuperAdmin()) {
    if (!dcCore::app()->error->flag() && !empty($_GET['nocache'])) {
        dcPage::success(__('Manual checking of plugins update done successfully.'));
    }

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
                /*cols */
                ['checkbox', 'icon', 'name', 'version', 'repository', 'current_version', 'desc'],
                /* actions */
                ['update']
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __('Visit %s repository, the resources center for Dotclear.'),
            '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
        ) .
            '</p>' .

            '</div>';
    } else {
        echo
        '<form action="' . $list->getURL('', false) . '" method="get">' .
        '<p><input type="hidden" name="nocache" value="1" />' .
        '<input type="submit" value="' . __('Force checking update of plugins') . '" /></p>' .
            '</form>';
    }
}

echo
'<div class="multi-part" id="plugins" title="' . __('Installed plugins') . '">';

# Activated modules
$modules = $list->modules->getModules();
if (!empty($modules)) {
    echo
    '<h3>' . (dcCore::app()->auth->isSuperAdmin() ? __('Activated plugins') : __('Installed plugins')) . '</h3>' .
    '<p class="more-info">' . __('You can configure and manage installed plugins from this list.') . '</p>';

    $list
        ->setList('plugin-activate')
        ->setTab('plugins')
        ->setModules($modules)
        ->displayModules(
            /* cols */
            ['expander', 'icon', 'name', 'version', 'desc', 'distrib', 'deps'],
            /* actions */
            ['deactivate', 'delete', 'behavior']
        );
}

# Deactivated modules
if (dcCore::app()->auth->isSuperAdmin()) {
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
                /* cols */
                ['expander', 'icon', 'name', 'version', 'desc', 'distrib'],
                /* actions */
                ['activate', 'delete']
            );
    }
}

echo
    '</div>';

if (dcCore::app()->auth->isSuperAdmin() && $list->isWritablePath()) {

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
                /* cols */
                ['expander', 'name', 'score', 'version', 'desc', 'deps'],
                /* actions */
                ['install'],
                /* nav limit */
                true
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __('Visit %s repository, the resources center for Dotclear.'),
            '<a href="https://plugins.dotaddict.org/dc2/">Dotaddict</a>'
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
dcCore::app()->callBehavior('pluginsToolsTabsV2');

# -- Notice for super admin --
if (dcCore::app()->auth->isSuperAdmin() && !$list->isWritablePath()) {
    echo
    '<p class="warning">' . __('Some functions are disabled, please give write access to your plugins directory to enable them.') . '</p>';
}

dcPage::helpBlock('core_plugins');
dcPage::close();
