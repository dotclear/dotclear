<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @var dcCore $core
 */
if (!empty($_GET['pf'])) {
    require dirname(__FILE__) . '/../inc/load_plugin_file.php';
    exit;
}

if (!empty($_GET['vf'])) {
    require dirname(__FILE__) . '/../inc/load_var_file.php';
    exit;
}

require dirname(__FILE__) . '/../inc/admin/prepend.php';

if (!empty($_GET['default_blog'])) {
    try {
        $core->setUserDefaultBlog($core->auth->userID(), $core->blog->id);
        $core->adminurl->redirect('admin.home');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

dcPage::check('usage,contentadmin', true);

if ($core->plugins->disableDepModules($core->adminurl->get('admin.home', []))) {
    exit;
}

# Logout
if (!empty($_GET['logout'])) {
    $core->session->destroy();
    if (isset($_COOKIE['dc_admin'])) {
        unset($_COOKIE['dc_admin']);
        setcookie('dc_admin', '', -600, '', '', DC_ADMIN_SSL);
    }
    $core->adminurl->redirect('admin.auth');
    exit;
}

# Plugin install
$plugins_install = $core->plugins->installModules();

# Check dashboard module prefs
$ws = $core->auth->user_prefs->addWorkspace('dashboard');
if (!$core->auth->user_prefs->dashboard->prefExists('doclinks')) {
    if (!$core->auth->user_prefs->dashboard->prefExists('doclinks', true)) {
        $core->auth->user_prefs->dashboard->put('doclinks', true, 'boolean', '', null, true);
    }
    $core->auth->user_prefs->dashboard->put('doclinks', true, 'boolean');
}
if (!$core->auth->user_prefs->dashboard->prefExists('dcnews')) {
    if (!$core->auth->user_prefs->dashboard->prefExists('dcnews', true)) {
        $core->auth->user_prefs->dashboard->put('dcnews', true, 'boolean', '', null, true);
    }
    $core->auth->user_prefs->dashboard->put('dcnews', true, 'boolean');
}
if (!$core->auth->user_prefs->dashboard->prefExists('quickentry')) {
    if (!$core->auth->user_prefs->dashboard->prefExists('quickentry', true)) {
        $core->auth->user_prefs->dashboard->put('quickentry', false, 'boolean', '', null, true);
    }
    $core->auth->user_prefs->dashboard->put('quickentry', false, 'boolean');
}
if (!$core->auth->user_prefs->dashboard->prefExists('nodcupdate')) {
    if (!$core->auth->user_prefs->dashboard->prefExists('nodcupdate', true)) {
        $core->auth->user_prefs->dashboard->put('nodcupdate', false, 'boolean', '', null, true);
    }
    $core->auth->user_prefs->dashboard->put('nodcupdate', false, 'boolean');
}

// Handle folded/unfolded sections in admin from user preferences
$ws = $core->auth->user_prefs->addWorkspace('toggles');
if (!$core->auth->user_prefs->toggles->prefExists('unfolded_sections')) {
    $core->auth->user_prefs->toggles->put('unfolded_sections', '', 'string', 'Folded sections in admin', null, true);
}

# Dashboard icons
$__dashboard_icons = new ArrayObject();

$favs = $core->favs->getUserFavorites();
$core->favs->appendDashboardIcons($__dashboard_icons);

# Latest news for dashboard
$__dashboard_items = new ArrayObject([new ArrayObject(), new ArrayObject()]);

$dashboardItem = 0;

# Documentation links
if ($core->auth->user_prefs->dashboard->doclinks) {
    if (!empty($__resources['doc'])) {
        $doc_links = '<div class="box small dc-box" id="doc-and-support"><h3>' . __('Documentation and support') . '</h3><ul>';

        foreach ($__resources['doc'] as $k => $v) {
            $doc_links .= '<li><a class="outgoing" href="' . $v . '" title="' . $k . '">' . $k .
                ' <img src="images/outgoing-link.svg" alt="" /></a></li>';
        }

        $doc_links .= '</ul></div>';
        $__dashboard_items[$dashboardItem][] = $doc_links;
        $dashboardItem++;
    }
}

$core->callBehavior('adminDashboardItems', $core, $__dashboard_items);

# Dashboard content
$__dashboard_contents = new ArrayObject([new ArrayObject, new ArrayObject]);
$core->callBehavior('adminDashboardContents', $core, $__dashboard_contents);

# Editor stuff
$admin_post_behavior = '';
if ($core->auth->user_prefs->dashboard->quickentry) {
    if ($core->auth->check('usage,contentadmin', $core->blog->id)) {
        $post_format = $core->auth->getOption('post_format');
        $post_editor = $core->auth->getOption('editor');
        if ($post_editor && !empty($post_editor[$post_format])) {
            // context is not post because of tags not available
            $admin_post_behavior = $core->callBehavior('adminPostEditor', $post_editor[$post_format], 'quickentry', ['#post_content'], $post_format);
        }
    }
}

# Dashboard drag'n'drop switch for its elements
$core->auth->user_prefs->addWorkspace('accessibility');
$dragndrop      = '';
$dragndrop_head = '';
$dragndrop_msg  = [
    'dragndrop_off' => __('Dashboard area\'s drag and drop is disabled'),
    'dragndrop_on'  => __('Dashboard area\'s drag and drop is enabled')
];
if (!$core->auth->user_prefs->accessibility->nodragdrop) {
    $dragndrop_head = dcPage::jsJson('dotclear_dragndrop', $dragndrop_msg);
    $dragndrop      = '<input type="checkbox" id="dragndrop" class="sr-only" title="' . $dragndrop_msg['dragndrop_off'] . '" />' .
        '<label for="dragndrop">' .
        '<svg aria-hidden="true" focusable="false" class="dragndrop-svg">' .
        '<use xlink:href="images/dragndrop.svg#mask"></use>' .
        '</svg>' .
        '<span id="dragndrop-label" class="sr-only">' . $dragndrop_msg['dragndrop_off'] . '</span>' .
        '</label>';
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(__('Dashboard'),
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsLoad('js/_index.js') .
    $dragndrop_head .
    $admin_post_behavior .
    # --BEHAVIOR-- adminDashboardHeaders
    $core->callBehavior('adminDashboardHeaders'),
    dcPage::breadcrumb(
        [
            __('Dashboard') . ' : ' . html::escapeHTML($core->blog->name) => ''
        ],
        ['home_link' => false]
    )
);

if ($core->auth->getInfo('user_default_blog') != $core->blog->id && $core->auth->getBlogCount() > 1) {
    echo
    '<p><a href="' . $core->adminurl->get('admin.home', ['default_blog' => 1]) . '" class="button">' . __('Make this blog my default blog') . '</a></p>';
}

if ($core->blog->status == 0) {
    echo '<p class="static-msg">' . __('This blog is offline') . '.</p>';
} elseif ($core->blog->status == -1) {
    echo '<p class="static-msg">' . __('This blog is removed') . '.</p>';
}

if (!defined('DC_ADMIN_URL') || !DC_ADMIN_URL) {    // @phpstan-ignore-line
    echo
    '<p class="static-msg">' .
    sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_URL') .
    ' ' . __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.') .
        '</p>';
}

if (!defined('DC_ADMIN_MAILFROM') || !DC_ADMIN_MAILFROM) {
    echo
    '<p class="static-msg">' .
    sprintf(__('%s is not defined, you should edit your configuration file.'), 'DC_ADMIN_MAILFROM') .
    ' ' . __('See <a href="https://dotclear.org/documentation/2.0/admin/config">documentation</a> for more information.') .
        '</p>';
}

$err = [];

# Check cache directory
if ($core->auth->isSuperAdmin()) {
    if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
        $err[] = '<p>' . __('The cache directory does not exist or is not writable. You must create this directory with sufficient rights and affect this location to "DC_TPL_CACHE" in inc/config.php file.') . '</p>';
    }
} else {
    if (!is_dir(DC_TPL_CACHE) || !is_writable(DC_TPL_CACHE)) {
        $err[] = '<p>' . __('The cache directory does not exist or is not writable. You should contact your administrator.') . '</p>';
    }
}

# Check public directory
if ($core->auth->isSuperAdmin()) {
    if (!is_dir($core->blog->public_path) || !is_writable($core->blog->public_path)) {
        $err[] = '<p>' . __('There is no writable directory /public/ at the location set in about:config "public_path". You must create this directory with sufficient rights (or change this setting).') . '</p>';
    }
} else {
    if (!is_dir($core->blog->public_path) || !is_writable($core->blog->public_path)) {
        $err[] = '<p>' . __('There is no writable root directory for the media manager. You should contact your administrator.') . '</p>';
    }
}

# Error list
if (count($err) > 0) {
    echo '<div class="error"><p><strong>' . __('Error:') . '</strong></p>' .
    '<ul><li>' . implode('</li><li>', $err) . '</li></ul></div>';
}

# Plugins install messages
if (!empty($plugins_install['success'])) {
    echo '<div class="success">' . __('Following plugins have been installed:') . '<ul>';
    $list = new adminModulesList($core->plugins, DC_PLUGINS_ROOT, $core->blog->settings->system->store_plugin_url);
    foreach ($plugins_install['success'] as $k => $v) {
        $info = implode(' - ', $list->getSettingsUrls($core, $k, true));
        echo '<li>' . $k . ($info !== '' ? ' → ' . $info : '') . '</li>';
    }
    echo '</ul></div>';
}
if (!empty($plugins_install['failure'])) {
    echo '<div class="error">' . __('Following plugins have not been installed:') . '<ul>';
    foreach ($plugins_install['failure'] as $k => $v) {
        echo '<li>' . $k . ' (' . $v . ')</li>';
    }
    echo '</ul></div>';
}
# Errors modules notifications
if ($core->auth->isSuperAdmin()) {
    $list = $core->plugins->getErrors();
    if (!empty($list)) {
        echo
        '<div class="error" id="module-errors" class="error"><p>' . __('Errors have occured with following plugins:') . '</p> ' .
        '<ul><li>' . implode("</li>\n<li>", $list) . '</li></ul></div>';
    }
}

# Get current main orders
$main_order = $core->auth->user_prefs->dashboard->main_order;
$main_order = ($main_order != '' ? explode(',', $main_order) : []);

# Get current boxes orders
$boxes_order = $core->auth->user_prefs->dashboard->boxes_order;
$boxes_order = ($boxes_order != '' ? explode(',', $boxes_order) : []);

# Get current boxes items orders
$boxes_items_order = $core->auth->user_prefs->dashboard->boxes_items_order;
$boxes_items_order = ($boxes_items_order != '' ? explode(',', $boxes_items_order) : []);

# Get current boxes contents orders
$boxes_contents_order = $core->auth->user_prefs->dashboard->boxes_contents_order;
$boxes_contents_order = ($boxes_contents_order != '' ? explode(',', $boxes_contents_order) : []);

$composeItems = function ($list, $blocks, $flat = false) {
    $ret   = [];
    $items = [];

    if ($flat) {
        $items = $blocks;
    } else {
        foreach ($blocks as $i) {
            foreach ($i as $v) {
                $items[] = $v;
            }
        }
    }

    # First loop to find ordered indexes
    $order = [];
    $index = 0;
    foreach ($items as $v) {
        if (preg_match('/<div.*?id="([^"].*?)".*?>/ms', $v, $match)) {
            $id       = $match[1];
            $position = array_search($id, $list, true);
            if ($position !== false) {
                $order[$position] = $index;
            }
        }
        $index++;
    }

    # Second loop to combine ordered items
    $index = 0;
    foreach ($items as $v) {
        $position = array_search($index, $order, true);
        if ($position !== false) {
            $ret[$position] = $v;
        }
        $index++;
    }
    # Reorder items on their position (key)
    ksort($ret);

    # Third loop to combine unordered items
    $index = 0;
    foreach ($items as $v) {
        $position = array_search($index, $order, true);
        if ($position === false) {
            $ret[count($ret)] = $v;
        }
        $index++;
    }

    return join('', $ret);
};

# Compose dashboard items (doc, …)
$dashboardItems = $composeItems($boxes_items_order, $__dashboard_items);
# Compose dashboard contents (plugin's modules)
$dashboardContents = $composeItems($boxes_contents_order, $__dashboard_contents);

$__dashboard_boxes = [];
if ($dashboardItems != '') {
    $__dashboard_boxes[] = '<div class="db-items" id="db-items">' . $dashboardItems . '</div>';
}
if ($dashboardContents != '') {
    $__dashboard_boxes[] = '<div class="db-contents" id="db-contents">' . $dashboardContents . '</div>';
}
$dashboardBoxes = $composeItems($boxes_order, $__dashboard_boxes, true);

# Compose main area
$__dashboard_main = [];
if (!$core->auth->user_prefs->dashboard->nofavicons) {
    # Dashboard icons
    $dashboardIcons = '<div id="icons">';
    foreach ($__dashboard_icons as $i) {
        $dashboardIcons .= '<p><a href="' . $i[1] . '"><img src="' . dc_admin_icon_url($i[2]) . '" alt="" />' .
            '<br /><span class="db-icon-title">' . $i[0] . '</span></a></p>';
    }
    $dashboardIcons .= '</div>';
    $__dashboard_main[] = $dashboardIcons;
}
if ($core->auth->user_prefs->dashboard->quickentry) {
    if ($core->auth->check('usage,contentadmin', $core->blog->id)) {
        # Getting categories
        $categories_combo = dcAdminCombos::getCategoriesCombo(
            $core->blog->getCategories([])
        );

        $dashboardQuickEntry = '<div id="quick">' .
        '<h3>' . __('Quick post') . sprintf(' &rsaquo; %s', $core->auth->getOption('post_format')) . '</h3>' .
        '<form id="quick-entry" action="' . $core->adminurl->get('admin.post') . '" method="post" class="fieldset">' .
        '<h4>' . __('New post') . '</h4>' .
        '<p class="col"><label for="post_title" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Title:') . '</label>' .
        form::field('post_title', 20, 255, [
            'class'      => 'maximal',
            'extra_html' => 'required placeholder="' . __('Title') . '"'
        ]) .
        '</p>' .
        '<div class="area"><label class="required" ' .
        'for="post_content"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Content:') . '</label> ' .
        form::textarea('post_content', 50, 10, ['extra_html' => 'required placeholder="' . __('Content') . '"']) .
        '</div>' .
        '<p><label for="cat_id" class="classic">' . __('Category:') . '</label> ' .
        form::combo('cat_id', $categories_combo) . '</p>' .
        ($core->auth->check('categories', $core->blog->id)
            ? '<div>' .
            '<p id="new_cat" class="q-cat">' . __('Add a new category') . '</p>' .
            '<p class="q-cat"><label for="new_cat_title">' . __('Title:') . '</label> ' .
            form::field('new_cat_title', 30, 255) . '</p>' .
            '<p class="q-cat"><label for="new_cat_parent">' . __('Parent:') . '</label> ' .
            form::combo('new_cat_parent', $categories_combo) .
            '</p>' .
            '<p class="form-note info clear">' . __('This category will be created when you will save your post.') . '</p>' .
            '</div>'
            : '') .
        '<p><input type="submit" value="' . __('Save') . '" name="save" /> ' .
        ($core->auth->check('publish', $core->blog->id)
            ? '<input type="hidden" value="' . __('Save and publish') . '" name="save-publish" />'
            : '') .
        $core->formNonce() .
        form::hidden('post_status', -2) .
        form::hidden('post_format', $core->auth->getOption('post_format')) .
        form::hidden('post_excerpt', '') .
        form::hidden('post_lang', $core->auth->getInfo('user_lang')) .
        form::hidden('post_notes', '') .
            '</p>' .
            '</form>' .
            '</div>';
        $__dashboard_main[] = $dashboardQuickEntry;
    }
}
if ($dashboardBoxes != '') {
    $__dashboard_main[] = '<div id="dashboard-boxes">' . $dashboardBoxes . '</div>';
}
$dashboardMain = $composeItems($main_order, $__dashboard_main, true);

echo $dragndrop . '<div id="dashboard-main">' . $dashboardMain . '</div>';

dcPage::helpBlock('core_dashboard');
dcPage::close();
