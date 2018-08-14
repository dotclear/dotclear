<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$page_title = __('My preferences');

$user_name        = $core->auth->getInfo('user_name');
$user_firstname   = $core->auth->getInfo('user_firstname');
$user_displayname = $core->auth->getInfo('user_displayname');
$user_email       = $core->auth->getInfo('user_email');
$user_url         = $core->auth->getInfo('user_url');
$user_lang        = $core->auth->getInfo('user_lang');
$user_tz          = $core->auth->getInfo('user_tz');
$user_post_status = $core->auth->getInfo('user_post_status');

$user_options = $core->auth->getOptions();
if (empty($user_options['editor']) || !is_array($user_options['editor'])) {
    $user_options['editor'] = array();
}

$core->auth->user_prefs->addWorkspace('dashboard');
$user_dm_doclinks   = $core->auth->user_prefs->dashboard->doclinks;
$user_dm_dcnews     = $core->auth->user_prefs->dashboard->dcnews;
$user_dm_quickentry = $core->auth->user_prefs->dashboard->quickentry;
$user_dm_nofavicons = $core->auth->user_prefs->dashboard->nofavicons;
if ($core->auth->isSuperAdmin()) {
    $user_dm_nodcupdate = $core->auth->user_prefs->dashboard->nodcupdate;
}

$core->auth->user_prefs->addWorkspace('accessibility');
$user_acc_nodragdrop = $core->auth->user_prefs->accessibility->nodragdrop;

$core->auth->user_prefs->addWorkspace('interface');
$user_ui_darkmode         = $core->auth->user_prefs->interface->darkmode;
$user_ui_enhanceduploader = $core->auth->user_prefs->interface->enhanceduploader;
$user_ui_hidemoreinfo     = $core->auth->user_prefs->interface->hidemoreinfo;
$user_ui_hidehelpbutton   = $core->auth->user_prefs->interface->hidehelpbutton;
$user_ui_showajaxloader   = $core->auth->user_prefs->interface->showajaxloader;
$user_ui_htmlfontsize     = $core->auth->user_prefs->interface->htmlfontsize;
$user_ui_dynfontsize      = $core->auth->user_prefs->interface->dynfontsize;
if ($core->auth->isSuperAdmin()) {
    $user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
}
$user_ui_iconset            = @$core->auth->user_prefs->interface->iconset;
$user_ui_nofavmenu          = $core->auth->user_prefs->interface->nofavmenu;
$user_ui_media_by_page      = ($core->auth->user_prefs->interface->media_by_page ?: 30);
$user_ui_media_nb_last_dirs = $core->auth->user_prefs->interface->media_nb_last_dirs;

$default_tab = !empty($_GET['tab']) ? html::escapeHTML($_GET['tab']) : 'user-profile';

if (!empty($_GET['append']) || !empty($_GET['removed']) || !empty($_GET['neworder']) ||
    !empty($_GET['replaced']) || !empty($_POST['appendaction']) || !empty($_POST['removeaction']) ||
    !empty($_GET['db-updated'])) {
    $default_tab = 'user-favorites';
} elseif (!empty($_GET['updated'])) {
    $default_tab = 'user-options';
}
if (($default_tab != 'user-profile') && ($default_tab != 'user-options') && ($default_tab != 'user-favorites')) {
    $default_tab = 'user-profile';
}

# Editors combo
$editors_combo = dcAdminCombos::getEditorsCombo();
$editors       = array_keys($editors_combo);

# Format by editors
$formaters         = $core->getFormaters();
$format_by_editors = array();
foreach ($formaters as $editor => $formats) {
    foreach ($formats as $format) {
        $format_by_editors[$format][$editor] = $editor;
    }
}
$available_formats = array('' => '');
foreach (array_keys($format_by_editors) as $format) {
    $available_formats[$format] = $format;
    if (!isset($user_options['editor'][$format])) {
        $user_options['editor'][$format] = '';
    }
}
$status_combo = dcAdminCombos::getPostStatusescombo();

$iconsets_combo = array(__('Default') => '');
$iconsets_root  = dirname(__FILE__) . '/images/iconset/';
if (is_dir($iconsets_root) && is_readable($iconsets_root)) {
    if (($d = @dir($iconsets_root)) !== false) {
        while (($entry = $d->read()) !== false) {
            if ($entry != '.' && $entry != '..' && substr($entry, 0, 1) != '.' && is_dir($iconsets_root . '/' . $entry)) {
                $iconsets_combo[$entry] = $entry;
            }
        }
    }
}

# Body base font size (37.5% = 6px, 50% = 8px, 62.5% = 10px, 75% = 12px, 87.5% = 14px)
$htmlfontsize_combo = array(
    __('Smallest') => '37.5%',
    __('Smaller')  => '50%',
    __('Default')  => '62.5%',
    __('Larger')   => '75%',
    __('Largest')  => '87,5%'
);
# Ensure Font size is set to default is empty
if ($user_ui_htmlfontsize == '') {
    $user_ui_htmlfontsize = '62.5%';
}

# Language codes
$lang_combo = dcAdminCombos::getAdminLangsCombo();

# Get 3rd parts xhtml editor flags
$rte = array(
    'blog_descr' => array(true, __('Blog description (in blog parameters)')),
    'cat_descr'  => array(true, __('Category description'))
);
$rte = new ArrayObject($rte);
$core->callBehavior('adminRteFlags', $core, $rte);
# Load user settings
$rte_flags = @$core->auth->user_prefs->interface->rte_flags;
if (is_array($rte_flags)) {
    foreach ($rte_flags as $fk => $fv) {
        if (isset($rte[$fk])) {
            $rte[$fk][0] = $fv;
        }
    }
}

# Get default colums (admin lists)
$cols = array(
    'posts' => array(__('Posts'), array(
        'date'       => array(true, __('Date')),
        'category'   => array(true, __('Category')),
        'author'     => array(true, __('Author')),
        'comments'   => array(true, __('Comments')),
        'trackbacks' => array(true, __('Trackbacks'))
    ))
);
$cols = new arrayObject($cols);
$core->callBehavior('adminColumnsLists', $core, $cols);
# Load user settings
$cols_user = @$core->auth->user_prefs->interface->cols;
if (is_array($cols_user)) {
    foreach ($cols_user as $ct => $cv) {
        foreach ($cv as $cn => $cd) {
            if (isset($cols[$ct][1][$cn])) {
                $cols[$ct][1][$cn][0] = $cd;
            }
        }
    }
}

# Add or update user
if (isset($_POST['user_name'])) {
    try
    {
        $pwd_check = !empty($_POST['cur_pwd']) && $core->auth->checkPassword($_POST['cur_pwd']);

        if ($core->auth->allowPassChange() && !$pwd_check && $user_email != $_POST['user_email']) {
            throw new Exception(__('If you want to change your email or password you must provide your current password.'));
        }

        $cur = $core->con->openCursor($core->prefix . 'user');

        $cur->user_name        = $user_name        = $_POST['user_name'];
        $cur->user_firstname   = $user_firstname   = $_POST['user_firstname'];
        $cur->user_displayname = $user_displayname = $_POST['user_displayname'];
        $cur->user_email       = $user_email       = $_POST['user_email'];
        $cur->user_url         = $user_url         = $_POST['user_url'];
        $cur->user_lang        = $user_lang        = $_POST['user_lang'];
        $cur->user_tz          = $user_tz          = $_POST['user_tz'];

        $cur->user_options = new ArrayObject($user_options);

        if ($core->auth->allowPassChange() && !empty($_POST['new_pwd'])) {
            if (!$pwd_check) {
                throw new Exception(__('If you want to change your email or password you must provide your current password.'));
            }

            if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                throw new Exception(__("Passwords don't match"));
            }

            $cur->user_pwd = $_POST['new_pwd'];
        }

        # --BEHAVIOR-- adminBeforeUserUpdate
        $core->callBehavior('adminBeforeUserProfileUpdate', $cur, $core->auth->userID());

        # Udate user
        $core->updUser($core->auth->userID(), $cur);

        # --BEHAVIOR-- adminAfterUserUpdate
        $core->callBehavior('adminAfterUserProfileUpdate', $cur, $core->auth->userID());

        dcPage::addSuccessNotice(__('Personal information has been successfully updated.'));

        $core->adminurl->redirect("admin.user.preferences");
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Update user options
if (isset($_POST['user_editor'])) {
    try
    {
        $cur = $core->con->openCursor($core->prefix . 'user');

        $cur->user_name        = $user_name;
        $cur->user_firstname   = $user_firstname;
        $cur->user_displayname = $user_displayname;
        $cur->user_email       = $user_email;
        $cur->user_url         = $user_url;
        $cur->user_lang        = $user_lang;
        $cur->user_tz          = $user_tz;

        $cur->user_post_status = $user_post_status = $_POST['user_post_status'];

        $user_options['edit_size'] = (integer) $_POST['user_edit_size'];
        if ($user_options['edit_size'] < 1) {
            $user_options['edit_size'] = 10;
        }
        $user_options['post_format']    = $_POST['user_post_format'];
        $user_options['editor']         = $_POST['user_editor'];
        $user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
        $user_options['toolbar_bottom'] = !empty($_POST['user_toolbar_bottom']);

        $cur->user_options = new ArrayObject($user_options);

        # --BEHAVIOR-- adminBeforeUserOptionsUpdate
        $core->callBehavior('adminBeforeUserOptionsUpdate', $cur, $core->auth->userID());

        # Update user prefs
        $core->auth->user_prefs->accessibility->put('nodragdrop', !empty($_POST['user_acc_nodragdrop']), 'boolean');
        $core->auth->user_prefs->interface->put('darkmode', !empty($_POST['user_ui_darkmode']), 'boolean');
        $core->auth->user_prefs->interface->put('enhanceduploader', !empty($_POST['user_ui_enhanceduploader']), 'boolean');
        $core->auth->user_prefs->interface->put('hidemoreinfo', !empty($_POST['user_ui_hidemoreinfo']), 'boolean');
        $core->auth->user_prefs->interface->put('hidehelpbutton', !empty($_POST['user_ui_hidehelpbutton']), 'boolean');
        $core->auth->user_prefs->interface->put('showajaxloader', !empty($_POST['user_ui_showajaxloader']), 'boolean');
        $core->auth->user_prefs->interface->put('htmlfontsize', $_POST['user_ui_htmlfontsize'], 'string');
        $core->auth->user_prefs->interface->put('dynfontsize', !empty($_POST['user_ui_dynfontsize']), 'boolean');
        if ($core->auth->isSuperAdmin()) {
            # Applied to all users
            $core->auth->user_prefs->interface->put('hide_std_favicon', !empty($_POST['user_ui_hide_std_favicon']), 'boolean', null, true, true);
        }
        $core->auth->user_prefs->interface->put('media_by_page', (integer) $_POST['user_ui_media_by_page'], 'integer');
        $core->auth->user_prefs->interface->put('media_nb_last_dirs', (integer) $_POST['user_ui_media_nb_last_dirs'], 'integer');
        $core->auth->user_prefs->interface->put('media_last_dirs', array(), 'array', null, false);
        $core->auth->user_prefs->interface->put('media_fav_dirs', array(), 'array', null, false);

        # Update user columns (lists)
        $cu = array();
        foreach ($cols as $col_type => $cols_list) {
            $ct = array();
            foreach ($cols_list[1] as $col_name => $col_data) {
                $ct[$col_name] = isset($_POST['cols_' . $col_type]) && in_array($col_name, $_POST['cols_' . $col_type], true) ? true : false;
            }
            if (count($ct)) {
                $cu[$col_type] = $ct;
            }
        }
        $core->auth->user_prefs->interface->put('cols', $cu, 'array');

        # Update user xhtml editor flags
        $rf = array();
        foreach ($rte as $rk => $rv) {
            $rf[$rk] = isset($_POST['rte_flags']) && in_array($rk, $_POST['rte_flags'], true) ? true : false;
        }
        $core->auth->user_prefs->interface->put('rte_flags', $rf, 'array');

        # Update user
        $core->updUser($core->auth->userID(), $cur);

        # --BEHAVIOR-- adminAfterUserOptionsUpdate
        $core->callBehavior('adminAfterUserOptionsUpdate', $cur, $core->auth->userID());

        dcPage::addSuccessNotice(__('Personal options has been successfully updated.'));
        $core->adminurl->redirect("admin.user.preferences", array(), '#user-options');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Dashboard options
if (isset($_POST['db-options'])) {
    try
    {
        # --BEHAVIOR-- adminBeforeUserOptionsUpdate
        $core->callBehavior('adminBeforeDashboardOptionsUpdate', $core->auth->userID());

        # Update user prefs
        $core->auth->user_prefs->dashboard->put('doclinks', !empty($_POST['user_dm_doclinks']), 'boolean');
        $core->auth->user_prefs->dashboard->put('dcnews', !empty($_POST['user_dm_dcnews']), 'boolean');
        $core->auth->user_prefs->dashboard->put('quickentry', !empty($_POST['user_dm_quickentry']), 'boolean');
        $core->auth->user_prefs->dashboard->put('nofavicons', empty($_POST['user_dm_nofavicons']), 'boolean');
        if ($core->auth->isSuperAdmin()) {
            $core->auth->user_prefs->dashboard->put('nodcupdate', !empty($_POST['user_dm_nodcupdate']), 'boolean');
        }
        $core->auth->user_prefs->interface->put('iconset', (!empty($_POST['user_ui_iconset']) ? $_POST['user_ui_iconset'] : ''));
        $core->auth->user_prefs->interface->put('nofavmenu', empty($_POST['user_ui_nofavmenu']), 'boolean');

        # --BEHAVIOR-- adminAfterUserOptionsUpdate
        $core->callBehavior('adminAfterDashboardOptionsUpdate', $core->auth->userID());

        dcPage::addSuccessNotice(__('Dashboard options has been successfully updated.'));
        $core->adminurl->redirect("admin.user.preferences", array(), '#user-favorites');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Add selected favorites
if (!empty($_POST['appendaction'])) {
    try {
        if (empty($_POST['append'])) {
            throw new Exception(__('No favorite selected'));
        }
        $user_favs = $core->favs->getFavoriteIDs(false);
        foreach ($_POST['append'] as $k => $v) {
            if ($core->favs->exists($v)) {
                $user_favs[] = $v;
            }
        }
        $core->favs->setFavoriteIDs($user_favs, false);

        if (!$core->error->flag()) {
            dcPage::addSuccessNotice(__('Favorites have been successfully added.'));
            $core->adminurl->redirect("admin.user.preferences", array(), '#user-favorites');
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Delete selected favorites
if (!empty($_POST['removeaction'])) {
    try {
        if (empty($_POST['remove'])) {
            throw new Exception(__('No favorite selected'));
        }
        $user_fav_ids = array();
        foreach ($core->favs->getFavoriteIDs(false) as $v) {
            $user_fav_ids[$v] = true;
        }
        foreach ($_POST['remove'] as $v) {
            if (isset($user_fav_ids[$v])) {
                unset($user_fav_ids[$v]);
            }
        }
        $core->favs->setFavoriteIDs(array_keys($user_fav_ids), false);
        if (!$core->error->flag()) {
            dcPage::addSuccessNotice(__('Favorites have been successfully removed.'));
            $core->adminurl->redirect("admin.user.preferences", array(), '#user-favorites');
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Order favs
$order = array();
if (empty($_POST['favs_order']) && !empty($_POST['order'])) {
    $order = $_POST['order'];
    asort($order);
    $order = array_keys($order);
} elseif (!empty($_POST['favs_order'])) {
    $order = explode(',', $_POST['favs_order']);
}

if (!empty($_POST['saveorder']) && !empty($order)) {
    foreach ($order as $k => $v) {
        if (!$core->favs->exists($v)) {
            unset($order[$k]);
        }
    }
    $core->favs->setFavoriteIDs($order, false);
    if (!$core->error->flag()) {
        dcPage::addSuccessNotice(__('Favorites have been successfully updated.'));
        $core->adminurl->redirect("admin.user.preferences", array(), '#user-favorites');
    }
}

# Replace default favorites by current set (super admin only)
if (!empty($_POST['replace']) && $core->auth->isSuperAdmin()) {
    $user_favs = $core->favs->getFavoriteIDs(false);
    $core->favs->setFavoriteIDs($user_favs, true);

    if (!$core->error->flag()) {
        dcPage::addSuccessNotice(__('Default favorites have been successfully updated.'));
        $core->adminurl->redirect("admin.user.preferences", array(), '#user-favorites');
    }
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open($page_title,
    dcPage::jsLoad('js/_preferences.js') .
    ($user_acc_nodragdrop ? '' : dcPage::jsLoad('js/_preferences-dragdrop.js')) .
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsLoad('js/jquery/jquery.pwstrength.js') .
    '<script type="text/javascript">' . "\n" .
    "\$(function() {\n" .
    "   \$('#new_pwd').pwstrength({texts: ['" .
    sprintf(__('Password strength: %s'), __('very weak')) . "', '" .
    sprintf(__('Password strength: %s'), __('weak')) . "', '" .
    sprintf(__('Password strength: %s'), __('mediocre')) . "', '" .
    sprintf(__('Password strength: %s'), __('strong')) . "', '" .
    sprintf(__('Password strength: %s'), __('very strong')) . "']});\n" .
    "});\n" .
    "</script>\n" .
    dcPage::jsPageTabs($default_tab) .
    dcPage::jsConfirmClose('user-form', 'opts-forms', 'favs-form') .

    # --BEHAVIOR-- adminPreferencesHeaders
    $core->callBehavior('adminPreferencesHeaders'),

    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->auth->userID()) => '',
            $page_title                             => ''
        ))
);

# User profile
echo '<div class="multi-part" id="user-profile" title="' . __('My profile') . '">';

echo
'<h3>' . __('My profile') . '</h3>' .
'<form action="' . $core->adminurl->get("admin.user.preferences") . '" method="post" id="user-form">' .

'<p><label for="user_name">' . __('Last Name:') . '</label>' .
form::field('user_name', 20, 255, array(
    'default'      => html::escapeHTML($user_name),
    'autocomplete' => 'family-name'
)) .
'</p>' .

'<p><label for="user_firstname">' . __('First Name:') . '</label>' .
form::field('user_firstname', 20, 255, array(
    'default'      => html::escapeHTML($user_firstname),
    'autocomplete' => 'given-name'
)) .
'</p>' .

'<p><label for="user_displayname">' . __('Display name:') . '</label>' .
form::field('user_displayname', 20, 255, array(
    'default'      => html::escapeHTML($user_displayname),
    'autocomplete' => 'nickname'
)) .
'</p>' .

'<p><label for="user_email">' . __('Email:') . '</label>' .
form::email('user_email', array(
    'default'      => html::escapeHTML($user_email),
    'autocomplete' => 'email'
)) .
'</p>' .

'<p><label for="user_url">' . __('URL:') . '</label>' .
form::url('user_url', array(
    'size'         => 30,
    'default'      => html::escapeHTML($user_url),
    'autocomplete' => 'url'
)) .
'</p>' .

'<p><label for="user_lang">' . __('Language for my interface:') . '</label>' .
form::combo('user_lang', $lang_combo, $user_lang, 'l10n') . '</p>' .

'<p><label for="user_tz">' . __('My timezone:') . '</label>' .
form::combo('user_tz', dt::getZones(true, true), $user_tz) . '</p>';

if ($core->auth->allowPassChange()) {
    echo
    '<h4 class="vertical-separator pretty-title">' . __('Change my password') . '</h4>' .

    '<div class="pw-table">' .
    '<p class="pw-cell"><label for="new_pwd">' . __('New password:') . '</label>' .
    form::password('new_pwd', 20, 255,
        array(
            'extra_html'   => 'data-indicator="pwindicator"',
            'autocomplete' => 'new-password')
    ) . '</p>' .
    '<div id="pwindicator">' .
    '    <div class="bar"></div>' .
    '    <p class="label no-margin"></p>' .
    '</div>' .
    '</div>' .

    '<p><label for="new_pwd_c">' . __('Confirm new password:') . '</label>' .
    form::password('new_pwd_c', 20, 255,
        array(
            'autocomplete' => 'new-password')
    ) . '</p>' .

    '<p><label for="cur_pwd">' . __('Your current password:') . '</label>' .
    form::password('cur_pwd', 20, 255,
        array(
            'autocomplete' => 'current-password')
    ) . '</p>' .
    '<p class="form-note warn">' .
    __('If you have changed your email or password you must provide your current password to save these modifications.') .
        '</p>';
}

echo
'<p class="clear vertical-separator">' .
$core->formNonce() .
'<input type="submit" accesskey="s" value="' . __('Update my profile') . '" /></p>' .
    '</form>' .

    '</div>';

# User options : some from actual user profile, dashboard modules, ...
echo '<div class="multi-part" id="user-options" title="' . __('My options') . '">';

echo
'<form action="' . $core->adminurl->get("admin.user.preferences") . '#user-options" method="post" id="opts-forms">' .
'<h3>' . __('My options') . '</h3>';

echo
'<div class="fieldset">' .
'<h4 id="user_options_interface">' . __('Interface') . '</h4>' .

'<p><label for="user_ui_darkmode" class="classic">' .
form::checkbox('user_ui_darkmode', 1, $user_ui_darkmode) . ' ' .
__('Activate dark mode') . '</label></p>' .

'<p><label for="user_ui_enhanceduploader" class="classic">' .
form::checkbox('user_ui_enhanceduploader', 1, $user_ui_enhanceduploader) . ' ' .
__('Activate enhanced uploader in media manager') . '</label></p>' .

'<p><label for="user_acc_nodragdrop" class="classic">' .
form::checkbox('user_acc_nodragdrop', 1, $user_acc_nodragdrop) . ' ' .
__('Disable javascript powered drag and drop for ordering items') . '</label></p>' .
'<p class="clear form-note">' . __('If checked, numeric fields will allow to type the elements\' ordering number.') . '</p>' .

'<p><label for="user_ui_hidemoreinfo" class="classic">' .
form::checkbox('user_ui_hidemoreinfo', 1, $user_ui_hidemoreinfo) . ' ' .
__('Hide all secondary information and notes') . '</label></p>' .

'<p><label for="user_ui_hidehelpbutton" class="classic">' .
form::checkbox('user_ui_hidehelpbutton', 1, $user_ui_hidehelpbutton) . ' ' .
__('Hide help button') . '</label></p>' .

'<p><label for="user_ui_showajaxloader" class="classic">' .
form::checkbox('user_ui_showajaxloader', 1, $user_ui_showajaxloader) . ' ' .
__('Show asynchronous requests indicator') . '</label></p>' .

'<p><label for="user_ui_htmlfontsize" class="classic">' . __('Font size:') . '</label>' . ' ' .
form::combo('user_ui_htmlfontsize', $htmlfontsize_combo, $user_ui_htmlfontsize) . '</p>' .

'<p><label for="user_ui_dynfontsize" class="classic">' .
form::checkbox('user_ui_dynfontsize', 1, $user_ui_dynfontsize) . ' ' .
__('Activate adpative font size') . '</label></p>' .
'<p class="clear form-note">' . __('If checked, font size will vary depending on viewport size (from 12px to 16px with default font size selected).') . '</p>';

echo
'<p><label for="user_ui_media_by_page" class="classic">' . __('Number of elements displayed per page in media manager:') . '</label> ' .
form::number('user_ui_media_by_page', 0, 999, (integer) $user_ui_media_by_page) . '</p>';

echo
'<p><label for="user_ui_media_nb_last_dirs" class="classic">' . __('Number of recent folders proposed in media manager:') . '</label> ' .
form::number('user_ui_media_nb_last_dirs', 0, 999, (integer) $user_ui_media_nb_last_dirs) . '</p>' .
'<p class="clear form-note">' . __('Leave empty to ignore, displayed only if Javascript is enabled in your browser.') . '</p>';

if ($core->auth->isSuperAdmin()) {
    echo
    '<p><label for="user_ui_hide_std_favicon" class="classic">' .
    form::checkbox('user_ui_hide_std_favicon', 1, $user_ui_hide_std_favicon) . ' ' .
    __('Do not use standard favicon') . '</label> ' .
    '<span class="clear form-note warn">' . __('This will be applied for all users') . '.</span>' .
        '</p>'; //Opera sucks;
}

echo
    '</div>';

echo
'<div class="fieldset">' .
'<h4 id="user_options_columns">' . __('Optional columns displayed in lists') . '</h4>';
$odd = true;
foreach ($cols as $col_type => $col_list) {
    echo '<div class="two-boxes ' . ($odd ? 'odd' : 'even') . '">';
    echo '<h5>' . $col_list[0] . '</h5>';
    foreach ($col_list[1] as $col_name => $col_data) {
        echo
        '<p><label for="cols_' . $col_type . '-' . $col_name . '" class="classic">' .
        form::checkbox(array('cols_' . $col_type . '[]', 'cols_' . $col_type . '-' . $col_name), $col_name, $col_data[0]) . $col_data[1] . '</label>';
    }
    echo '</div>';
    $odd = !$odd;
}
echo '</div>';

echo
'<div class="fieldset">' .
'<h4 id="user_options_edition">' . __('Edition') . '</h4>';

echo '<div class="two-boxes odd">';
foreach ($format_by_editors as $format => $editors) {
    echo
    '<p class="field"><label for="user_editor_' . $format . '">' . sprintf(__('Preferred editor for %s:'), $format) . '</label>' .
    form::combo(
        array('user_editor[' . $format . ']', 'user_editor_' . $format),
        array_merge(array(__('Choose an editor') => ''), $editors),
        $user_options['editor'][$format]
    ) . '</p>';
}
echo
'<p class="field"><label for="user_post_format">' . __('Preferred format:') . '</label>' .
form::combo('user_post_format', $available_formats, $user_options['post_format']) . '</p>';

echo
'<p class="field"><label for="user_post_status">' . __('Default entry status:') . '</label>' .
form::combo('user_post_status', $status_combo, $user_post_status) . '</p>' .

'<p class="field"><label for="user_edit_size">' . __('Entry edit field height:') . '</label>' .
form::number('user_edit_size', 10, 999, (integer) $user_options['edit_size']) . '</p>' .

'<p><label for="user_wysiwyg" class="classic">' .
form::checkbox('user_wysiwyg', 1, $user_options['enable_wysiwyg']) . ' ' .
__('Enable WYSIWYG mode') . '</label></p>' .

'<p><label for="user_toolbar_bottom" class="classic">' .
form::checkbox('user_toolbar_bottom', 1, $user_options['toolbar_bottom']) . ' ' .
__('Display editor\'s toolbar at bottom of textarea (if possible)') . '</label></p>' .

    '</div>';

echo '<div class="two-boxes even">';
echo '<h5>' . __('Use xhtml editor for:') . '</h5>';
foreach ($rte as $rk => $rv) {
    echo
    '<p><label for="rte_' . $rk . '" class="classic">' .
    form::checkbox(array('rte_flags[]', 'rte_' . $rk), $rk, $rv[0]) . $rv[1] . '</label>';
}
echo '</div>';

echo '</div>'; // fieldset

echo
'<h4 class="pretty-title">' . __('Other options') . '</h4>';

# --BEHAVIOR-- adminPreferencesForm
$core->callBehavior('adminPreferencesForm', $core);

echo
'<p class="clear vertical-separator">' .
$core->formNonce() .
'<input type="submit" accesskey="s" value="' . __('Save my options') . '" /></p>' .
    '</form>';

echo '</div>';

# My dashboard
echo '<div class="multi-part" id="user-favorites" title="' . __('My dashboard') . '">';
$ws = $core->auth->user_prefs->addWorkspace('favorites');
echo '<h3>' . __('My dashboard') . '</h3>';

echo '<form action="' . $core->adminurl->get("admin.user.preferences") . '" method="post" id="favs-form" class="two-boxes odd">';

echo '<div id="my-favs" class="fieldset"><h4>' . __('My favorites') . '</h4>';

$count    = 0;
$user_fav = $core->favs->getFavoriteIDs(false);
foreach ($user_fav as $id) {
    $fav = $core->favs->getFavorite($id);
    if ($fav != false) {
        // User favorites only
        if ($count == 0) {
            echo '<ul class="fav-list">';
        }

        $count++;
        echo '<li id="fu-' . $id . '">' . '<label for="fuk-' . $id . '">' .
        '<img src="' . dc_admin_icon_url($fav['small-icon']) . '" alt="" /> ' . '<span class="zoom"><img src="' . dc_admin_icon_url($fav['large-icon']) . '" alt="" /></span>' .
        form::field(array('order[' . $id . ']'), 2, 3, array(
            'default'    => $count,
            'class'      => 'position',
            'extra_html' => 'title="' . sprintf(__('position of %s'), $fav['title']) . '"'
        )) .
        form::hidden(array('dynorder[]', 'dynorder-' . $id . ''), $id) .
        form::checkbox(array('remove[]', 'fuk-' . $id), $id) . __($fav['title']) . '</label>' .
            '</li>';
    }
}
if ($count > 0) {
    echo '</ul>';
}

if ($count > 0) {
    echo
    '<div class="clear">' .
    '<p>' . form::hidden('favs_order', '') .
    $core->formNonce() .
    '<input type="submit" name="saveorder" value="' . __('Save order') . '" /> ' .

    '<input type="submit" class="delete" name="removeaction" ' .
    'value="' . __('Delete selected favorites') . '" ' .
    'onclick="return window.confirm(\'' . html::escapeJS(
        __('Are you sure you want to remove selected favorites?')) . '\');" /></p>' .

        ($core->auth->isSuperAdmin() ?
        '<div class="info">' .
        '<p>' . __('If you are a super administrator, you may define this set of favorites to be used by default on all blogs of this installation.') . '</p>' .
        '<p><input class="reset" type="submit" name="replace" value="' . __('Define as default favorites') . '" />' . '</p>' .
        '</div>'
        :
        '') .

        '</div>';
} else {
    echo
    '<p>' . __('Currently no personal favorites.') . '</p>';
}

$avail_fav       = $core->favs->getFavorites($core->favs->getAvailableFavoritesIDs());
$default_fav_ids = array();
foreach ($core->favs->getFavoriteIDs(true) as $v) {
    $default_fav_ids[$v] = true;
}
echo '</div>'; # /box my-fav

echo '<div class="fieldset" id="available-favs">';
# Available favorites
echo '<h5 class="pretty-title">' . __('Other available favorites') . '</h5>';
$count = 0;
uasort($avail_fav, function ($a, $b) {
    return strcoll(
        strtolower(dcUtils::removeDiacritics($a['title'])),
        strtolower(dcUtils::removeDiacritics($b['title'])));
});

foreach ($avail_fav as $k => $v) {
    if (in_array($k, $user_fav)) {
        unset($avail_fav[$k]);
    }
}
foreach ($avail_fav as $k => $fav) {
    if ($count == 0) {
        echo '<ul class="fav-list">';
    }

    $count++;
    echo '<li id="fa-' . $k . '">' . '<label for="fak-' . $k . '">' .
    '<img src="' . dc_admin_icon_url($fav['small-icon']) . '" alt="" /> ' .
    '<span class="zoom"><img src="' . dc_admin_icon_url($fav['large-icon']) . '" alt="" /></span>' .
    form::checkbox(array('append[]', 'fak-' . $k), $k) .
        $fav['title'] . '</label>' .
        (isset($default_fav_ids[$k]) ? ' <span class="default-fav"><img src="images/selected.png" alt="' . __('(default favorite)') . '" /></span>' : '') .
        '</li>';
}
if ($count > 0) {
    echo '</ul>';
}

echo
'<p>' .
$core->formNonce() .
'<input type="submit" name="appendaction" value="' . __('Add to my favorites') . '" /></p>';
echo '</div>'; # /available favorites

echo '</form>';

echo
'<form action="' . $core->adminurl->get("admin.user.preferences") . '" method="post" id="db-forms" class="two-boxes even">' .

'<div class="fieldset">' .
'<h4>' . __('Menu') . '</h4>' .
'<p><label for="user_ui_nofavmenu" class="classic">' .
form::checkbox('user_ui_nofavmenu', 1, !$user_ui_nofavmenu) . ' ' .
__('Display favorites at the top of the menu') . '</label></p></div>';

echo
'<div class="fieldset">' .
'<h4>' . __('Dashboard icons') . '</h4>' .
'<p><label for="user_dm_nofavicons" class="classic">' .
form::checkbox('user_dm_nofavicons', 1, !$user_dm_nofavicons) . ' ' .
__('Display dashboard icons') . '</label></p>';

if (count($iconsets_combo) > 1) {
    echo
    '<p><label for="user_ui_iconset" class="classic">' . __('Iconset:') . '</label> ' .
    form::combo('user_ui_iconset', $iconsets_combo, $user_ui_iconset) . '</p>';
} else {
    echo '<p class="hidden">' . form::hidden('user_ui_iconset', '') . '</p>';
}
echo
    '</div>';

echo
'<div class="fieldset">' .
'<h4>' . __('Dashboard modules') . '</h4>' .

'<p><label for="user_dm_doclinks" class="classic">' .
form::checkbox('user_dm_doclinks', 1, $user_dm_doclinks) . ' ' .
__('Display documentation links') . '</label></p>' .

'<p><label for="user_dm_dcnews" class="classic">' .
form::checkbox('user_dm_dcnews', 1, $user_dm_dcnews) . ' ' .
__('Display Dotclear news') . '</label></p>' .

'<p><label for="user_dm_quickentry" class="classic">' .
form::checkbox('user_dm_quickentry', 1, $user_dm_quickentry) . ' ' .
__('Display quick entry form') . '</label></p>';

if ($core->auth->isSuperAdmin()) {
    echo
    '<p><label for="user_dm_nodcupdate" class="classic">' .
    form::checkbox('user_dm_nodcupdate', 1, $user_dm_nodcupdate) . ' ' .
    __('Do not display Dotclear updates') . '</label></p>';
}

echo '</div>';

# --BEHAVIOR-- adminDashboardOptionsForm
$core->callBehavior('adminDashboardOptionsForm', $core);

echo
'<p>' .
form::hidden('db-options', '-') .
$core->formNonce() .
'<input type="submit" accesskey="s" value="' . __('Save my dashboard options') . '" /></p>' .
    '</form>';

echo '</div>'; # /multipart-user-favorites

dcPage::helpBlock('core_user_pref');
dcPage::close();
