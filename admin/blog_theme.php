<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcPage::check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_ADMIN,
]));

# -- Loading themes --
dcCore::app()->themes = new dcThemes(dcCore::app());
dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);

# -- Page helper --
$list = new adminThemesList(
    dcCore::app()->themes,
    dcCore::app()->blog->themes_path,
    dcCore::app()->blog->settings->system->store_theme_url,
    !empty($_GET['nocache'])
);
adminThemesList::$distributed_modules = explode(',', DC_DISTRIB_THEMES);

if (dcCore::app()->themes->disableDepModules(dcCore::app()->adminurl->get('admin.blog.theme', []))) {
    exit;
}

# -- Theme screenshot --
if (!empty($_GET['shot'])) {
    $f = path::real(
        empty($_GET['src']) ?
        dcCore::app()->blog->themes_path . '/' . $_GET['shot'] . '/screenshot.jpg' :
        dcCore::app()->blog->themes_path . '/' . $_GET['shot'] . '/' . path::clean($_GET['src'])
    );

    if (!file_exists($f)) {
        $f = __DIR__ . '/images/noscreenshot.png';
    }

    http::cache(array_merge([$f], get_included_files()));

    header('Content-Type: ' . files::getMimeType($f));
    header('Content-Length: ' . filesize($f));
    readfile($f);

    exit;
}

# -- Display module configuration page --
if ($list->setConfiguration(dcCore::app()->blog->settings->system->theme)) {

    # Get content before page headers
    include $list->includeConfiguration();

    # Gather content
    $list->getConfiguration();

    # Display page
    dcPage::open(
        __('Blog appearance'),
        dcPage::jsPageTabs() .

        # --BEHAVIOR-- themesToolsHeaders
        dcCore::app()->callBehavior('themesToolsHeadersV2', true),
        dcPage::breadcrumb(
            [
                html::escapeHTML(dcCore::app()->blog->name)                         => '',
                __('Blog appearance')                                               => $list->getURL('', false),
                '<span class="page-title">' . __('Theme configuration') . '</span>' => '',
            ]
        )
    );

    # Display previously gathered content
    $list->displayConfiguration();

    dcPage::helpBlock('core_blog_theme_conf');
    dcPage::close();

    # Stop reading code here
    return;
}

# -- Execute actions --
try {
    $list->doActions();
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

# -- Page header --
dcPage::open(
    __('Themes management'),
    dcPage::jsLoad('js/_blog_theme.js') .
    dcPage::jsPageTabs() .

    # --BEHAVIOR-- themesToolsHeaders
    dcCore::app()->callBehavior('themesToolsHeadersV2', false),
    dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name)                     => '',
            '<span class="page-title">' . __('Blog appearance') . '</span>' => '',
        ]
    )
);

# -- Display modules lists --
if (dcCore::app()->auth->isSuperAdmin()) {
    if (!dcCore::app()->error->flag() && !empty($_GET['nocache'])) {
        dcPage::success(__('Manual checking of themes update done successfully.'));
    }

    # Updated modules from repo
    $modules = $list->store->get(true);
    if (!empty($modules)) {
        echo
        '<div class="multi-part" id="update" title="' . html::escapeHTML(__('Update themes')) . '">' .
        '<h3>' . html::escapeHTML(__('Update themes')) . '</h3>' .
        '<p>' . sprintf(
            __('There is one theme to update available from repository.', 'There are %s themes to update available from repository.', count($modules)),
            count($modules)
        ) . '</p>';

        $list
            ->setList('theme-update')
            ->setTab('themes')
            ->setModules($modules)
            ->displayModules(
                /*cols */
                ['checkbox', 'name', 'sshot', 'desc', 'author', 'version', 'current_version', 'repository', 'parent'],
                /* actions */
                ['update', 'delete']
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __('Visit %s repository, the resources center for Dotclear.'),
            '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
        ) .
            '</p>' .

            '</div>';
    } else {
        echo
        '<form action="' . $list->getURL('', false) . '" method="get">' .
        '<p><input type="hidden" name="nocache" value="1" />' .
        '<input type="submit" value="' . __('Force checking update of themes') . '" /></p>' .
            '</form>';
    }
}

# Activated modules
$modules = $list->modules->getModules();
if (!empty($modules)) {
    echo
    '<div class="multi-part" id="themes" title="' . __('Installed themes') . '">' .
    '<h3>' . __('Installed themes') . '</h3>' .
    '<p class="more-info">' . __('You can configure and manage installed themes from this list.') . '</p>';

    $list
        ->setList('theme-activate')
        ->setTab('themes')
        ->setModules($modules)
        ->displayModules(
            /* cols */
            ['sshot', 'distrib', 'name', 'config', 'desc', 'author', 'version', 'parent'],
            /* actions */
            ['select', 'behavior', 'deactivate', 'clone', 'delete']
        );

    echo
        '</div>';
}

# Deactivated modules
$modules = $list->modules->getDisabledModules();
if (!empty($modules)) {
    echo
    '<div class="multi-part" id="deactivate" title="' . __('Deactivated themes') . '">' .
    '<h3>' . __('Deactivated themes') . '</h3>' .
    '<p class="more-info">' . __('Deactivated themes are installed but not usable. You can activate them from here.') . '</p>';

    $list
        ->setList('theme-deactivate')
        ->setTab('themes')
        ->setModules($modules)
        ->displayModules(
            /* cols */
            ['sshot', 'name', 'distrib', 'desc', 'author', 'version'],
            /* actions */
            ['activate', 'delete']
        );

    echo
        '</div>';
}

if (dcCore::app()->auth->isSuperAdmin() && $list->isWritablePath()) {

    # New modules from repo
    $search  = $list->getSearch();
    $modules = $search ? $list->store->search($search) : $list->store->get();

    if (!empty($search) || !empty($modules)) {
        echo
        '<div class="multi-part" id="new" title="' . __('Add themes') . '">' .
        '<h3>' . __('Add themes from repository') . '</h3>';

        $list
            ->setList('theme-new')
            ->setTab('new')
            ->setModules($modules)
            ->displaySearch()
            ->displayIndex()
            ->displayModules(
                /* cols */
                ['expander', 'sshot', 'name', 'score', 'config', 'desc', 'author', 'version', 'parent', 'details', 'support'],
                /* actions */
                ['install'],
                /* nav limit */
                true
            );

        echo
        '<p class="info vertical-separator">' . sprintf(
            __('Visit %s repository, the resources center for Dotclear.'),
            '<a href="https://themes.dotaddict.org/galerie-dc2/">Dotaddict</a>'
        ) .
            '</p>' .

            '</div>';
    }

    # Add a new plugin
    echo
    '<div class="multi-part" id="addtheme" title="' . __('Install or upgrade manually') . '">' .
    '<h3>' . __('Add themes from a package') . '</h3>' .
    '<p class="more-info">' . __('You can install themes by uploading or downloading zip files.') . '</p>';

    $list->displayManualForm();

    echo
        '</div>';
}

# --BEHAVIOR-- themesToolsTabs
dcCore::app()->callBehavior('themesToolsTabsV2');

# -- Notice for super admin --
if (dcCore::app()->auth->isSuperAdmin() && !$list->isWritablePath()) {
    echo
    '<p class="warning">' . __('Some functions are disabled, please give write access to your themes directory to enable them.') . '</p>';
}

dcPage::helpBlock('core_blog_theme');
dcPage::close();
