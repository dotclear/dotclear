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
    dcAuth::PERMISSION_USAGE,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]));

$page_title = __('My preferences');

$user_name        = dcCore::app()->auth->getInfo('user_name');
$user_firstname   = dcCore::app()->auth->getInfo('user_firstname');
$user_displayname = dcCore::app()->auth->getInfo('user_displayname');
$user_email       = dcCore::app()->auth->getInfo('user_email');
$user_url         = dcCore::app()->auth->getInfo('user_url');
$user_lang        = dcCore::app()->auth->getInfo('user_lang');
$user_tz          = dcCore::app()->auth->getInfo('user_tz');
$user_post_status = dcCore::app()->auth->getInfo('user_post_status');

$user_options = dcCore::app()->auth->getOptions();
if (empty($user_options['editor']) || !is_array($user_options['editor'])) {
    $user_options['editor'] = [];
}

dcCore::app()->auth->user_prefs->addWorkspace('profile');
$user_profile_mails = dcCore::app()->auth->user_prefs->profile->mails;
$user_profile_urls  = dcCore::app()->auth->user_prefs->profile->urls;

dcCore::app()->auth->user_prefs->addWorkspace('dashboard');
$user_dm_doclinks   = dcCore::app()->auth->user_prefs->dashboard->doclinks;
$user_dm_dcnews     = dcCore::app()->auth->user_prefs->dashboard->dcnews;
$user_dm_quickentry = dcCore::app()->auth->user_prefs->dashboard->quickentry;
$user_dm_nofavicons = dcCore::app()->auth->user_prefs->dashboard->nofavicons;
$user_dm_nodcupdate = false;
if (dcCore::app()->auth->isSuperAdmin()) {
    $user_dm_nodcupdate = dcCore::app()->auth->user_prefs->dashboard->nodcupdate;
}

dcCore::app()->auth->user_prefs->addWorkspace('accessibility');
$user_acc_nodragdrop = dcCore::app()->auth->user_prefs->accessibility->nodragdrop;

dcCore::app()->auth->user_prefs->addWorkspace('interface');
$user_ui_theme            = dcCore::app()->auth->user_prefs->interface->theme;
$user_ui_enhanceduploader = dcCore::app()->auth->user_prefs->interface->enhanceduploader;
$user_ui_blank_preview    = dcCore::app()->auth->user_prefs->interface->blank_preview;
$user_ui_hidemoreinfo     = dcCore::app()->auth->user_prefs->interface->hidemoreinfo;
$user_ui_hidehelpbutton   = dcCore::app()->auth->user_prefs->interface->hidehelpbutton;
$user_ui_showajaxloader   = dcCore::app()->auth->user_prefs->interface->showajaxloader;
$user_ui_htmlfontsize     = dcCore::app()->auth->user_prefs->interface->htmlfontsize;
$user_ui_hide_std_favicon = false;
if (dcCore::app()->auth->isSuperAdmin()) {
    $user_ui_hide_std_favicon = dcCore::app()->auth->user_prefs->interface->hide_std_favicon;
}
$user_ui_nofavmenu          = dcCore::app()->auth->user_prefs->interface->nofavmenu;
$user_ui_media_nb_last_dirs = dcCore::app()->auth->user_prefs->interface->media_nb_last_dirs;
$user_ui_nocheckadblocker   = dcCore::app()->auth->user_prefs->interface->nocheckadblocker;

dcCore::app()->admin->default_tab = !empty($_GET['tab']) ? html::escapeHTML($_GET['tab']) : 'user-profile';

if (!empty($_GET['append']) || !empty($_GET['removed']) || !empty($_GET['neworder']) || !empty($_GET['replaced']) || !empty($_POST['appendaction']) || !empty($_POST['removeaction']) || !empty($_GET['db-updated']) || !empty($_POST['resetorder'])) {
    dcCore::app()->admin->default_tab = 'user-favorites';
} elseif (!empty($_GET['updated'])) {
    dcCore::app()->admin->default_tab = 'user-options';
}
if ((dcCore::app()->admin->default_tab != 'user-profile') && (dcCore::app()->admin->default_tab != 'user-options') && (dcCore::app()->admin->default_tab != 'user-favorites')) {
    dcCore::app()->admin->default_tab = 'user-profile';
}

# Editors combo
$editors_combo = dcAdminCombos::getEditorsCombo();
$editors       = array_keys($editors_combo);

# Format by editors
$formaters         = dcCore::app()->getFormaters();
$format_by_editors = [];
foreach ($formaters as $editor => $formats) {
    foreach ($formats as $format) {
        $format_by_editors[$format][$editor] = $editor;
    }
}
$available_formats = ['' => ''];
foreach (array_keys($format_by_editors) as $format) {
    $available_formats[$format] = $format;
    if (!isset($user_options['editor'][$format])) {
        $user_options['editor'][$format] = '';
    }
}
$status_combo = dcAdminCombos::getPostStatusescombo();

# Themes
$theme_combo = [
    __('Light')     => 'light',
    __('Dark')      => 'dark',
    __('Automatic') => '',
];

# Body base font size (37.5% = 6px, 50% = 8px, 62.5% = 10px, 75% = 12px, 87.5% = 14px)
$htmlfontsize_combo = [
    __('Smallest') => '37.5%',
    __('Smaller')  => '50%',
    __('Default')  => '62.5%',
    __('Larger')   => '75%',
    __('Largest')  => '87.5%',
];
# Ensure Font size is set to default is empty
if ($user_ui_htmlfontsize == '') {
    $user_ui_htmlfontsize = '62.5%';
}

# Language codes
$lang_combo = dcAdminCombos::getAdminLangsCombo();

# Get 3rd parts xhtml editor flags
$rte = [
    'blog_descr' => [true, __('Blog description (in blog parameters)')],
    'cat_descr'  => [true, __('Category description')],
];
$rte = new ArrayObject($rte);
dcCore::app()->callBehavior('adminRteFlagsV2', $rte);
# Load user settings
$rte_flags = @dcCore::app()->auth->user_prefs->interface->rte_flags;
if (is_array($rte_flags)) {
    foreach ($rte_flags as $fk => $fv) {
        if (isset($rte[$fk])) {
            $rte[$fk][0] = $fv;
        }
    }
}

# Get default colums (admin lists)
$cols = adminUserPref::getUserColumns();

# Get default sortby, order, nbperpage (admin lists)
$sorts = adminUserPref::getUserFilters();

$order_combo = [
    __('Descending') => 'desc',
    __('Ascending')  => 'asc',
];
// All filters
$auto_filter = dcCore::app()->auth->user_prefs->interface->auto_filter;

# Add or update user
if (isset($_POST['user_name'])) {
    try {
        $pwd_check = !empty($_POST['cur_pwd']) && dcCore::app()->auth->checkPassword($_POST['cur_pwd']);

        if (dcCore::app()->auth->allowPassChange() && !$pwd_check && $user_email != $_POST['user_email']) {
            throw new Exception(__('If you want to change your email or password you must provide your current password.'));
        }

        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME);

        $cur->user_name        = $user_name        = $_POST['user_name'];
        $cur->user_firstname   = $user_firstname   = $_POST['user_firstname'];
        $cur->user_displayname = $user_displayname = $_POST['user_displayname'];
        $cur->user_email       = $user_email       = $_POST['user_email'];
        $cur->user_url         = $user_url         = $_POST['user_url'];
        $cur->user_lang        = $user_lang        = $_POST['user_lang'];
        $cur->user_tz          = $user_tz          = $_POST['user_tz'];

        $cur->user_options = new ArrayObject($user_options);

        if (dcCore::app()->auth->allowPassChange() && !empty($_POST['new_pwd'])) {
            if (!$pwd_check) {
                throw new Exception(__('If you want to change your email or password you must provide your current password.'));
            }

            if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                throw new Exception(__("Passwords don't match"));
            }

            $cur->user_pwd = $_POST['new_pwd'];
        }

        # --BEHAVIOR-- adminBeforeUserUpdate
        dcCore::app()->callBehavior('adminBeforeUserProfileUpdate', $cur, dcCore::app()->auth->userID());

        # Udate user
        dcCore::app()->updUser(dcCore::app()->auth->userID(), $cur);

        # Update profile
        # Sanitize list of secondary mails and urls if any
        $mails = $urls = '';
        if (!empty($_POST['user_profile_mails'])) {
            $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
        }
        if (!empty($_POST['user_profile_urls'])) {
            $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
        }
        dcCore::app()->auth->user_prefs->profile->put('mails', $mails, 'string');
        dcCore::app()->auth->user_prefs->profile->put('urls', $urls, 'string');

        # --BEHAVIOR-- adminAfterUserUpdate
        dcCore::app()->callBehavior('adminAfterUserProfileUpdate', $cur, dcCore::app()->auth->userID());

        dcPage::addSuccessNotice(__('Personal information has been successfully updated.'));

        dcCore::app()->adminurl->redirect('admin.user.preferences');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Update user options
if (isset($_POST['user_options_submit'])) {
    try {
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME);

        $cur->user_name        = $user_name;
        $cur->user_firstname   = $user_firstname;
        $cur->user_displayname = $user_displayname;
        $cur->user_email       = $user_email;
        $cur->user_url         = $user_url;
        $cur->user_lang        = $user_lang;
        $cur->user_tz          = $user_tz;

        $cur->user_post_status = $user_post_status = $_POST['user_post_status'];

        $user_options['edit_size'] = (int) $_POST['user_edit_size'];
        if ($user_options['edit_size'] < 1) {
            $user_options['edit_size'] = 10;
        }
        $user_options['post_format']    = $_POST['user_post_format'];
        $user_options['editor']         = $_POST['user_editor'];
        $user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
        $user_options['toolbar_bottom'] = !empty($_POST['user_toolbar_bottom']);

        $cur->user_options = new ArrayObject($user_options);

        # --BEHAVIOR-- adminBeforeUserOptionsUpdate
        dcCore::app()->callBehavior('adminBeforeUserOptionsUpdate', $cur, dcCore::app()->auth->userID());

        # Update user prefs
        dcCore::app()->auth->user_prefs->accessibility->put('nodragdrop', !empty($_POST['user_acc_nodragdrop']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('theme', $_POST['user_ui_theme'], 'string');
        dcCore::app()->auth->user_prefs->interface->put('enhanceduploader', !empty($_POST['user_ui_enhanceduploader']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('blank_preview', !empty($_POST['user_ui_blank_preview']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('hidemoreinfo', !empty($_POST['user_ui_hidemoreinfo']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('hidehelpbutton', !empty($_POST['user_ui_hidehelpbutton']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('showajaxloader', !empty($_POST['user_ui_showajaxloader']), 'boolean');
        dcCore::app()->auth->user_prefs->interface->put('htmlfontsize', $_POST['user_ui_htmlfontsize'], 'string');
        if (dcCore::app()->auth->isSuperAdmin()) {
            # Applied to all users
            dcCore::app()->auth->user_prefs->interface->put('hide_std_favicon', !empty($_POST['user_ui_hide_std_favicon']), 'boolean', null, true, true);
        }
        dcCore::app()->auth->user_prefs->interface->put('media_nb_last_dirs', (int) $_POST['user_ui_media_nb_last_dirs'], 'integer');
        dcCore::app()->auth->user_prefs->interface->put('media_last_dirs', [], 'array', null, false);
        dcCore::app()->auth->user_prefs->interface->put('media_fav_dirs', [], 'array', null, false);
        dcCore::app()->auth->user_prefs->interface->put('nocheckadblocker', !empty($_POST['user_ui_nocheckadblocker']), 'boolean');

        # Update user columns (lists)
        $cu = [];
        foreach ($cols as $col_type => $cols_list) {
            $ct = [];
            foreach ($cols_list[1] as $col_name => $col_data) {
                $ct[$col_name] = isset($_POST['cols_' . $col_type]) && in_array($col_name, $_POST['cols_' . $col_type], true) ? true : false;
            }
            if (count($ct)) {
                $cu[$col_type] = $ct;
            }
        }
        dcCore::app()->auth->user_prefs->interface->put('cols', $cu, 'array');

        # Update user lists options
        $su = [];
        foreach ($sorts as $sort_type => $sort_data) {
            if (null !== $sort_data[1]) {
                $k = 'sorts_' . $sort_type . '_sortby';

                $su[$sort_type][0] = isset($_POST[$k]) && in_array($_POST[$k], $sort_data[1]) ? $_POST[$k] : $sort_data[2];
            }
            if (null !== $sort_data[3]) {
                $k = 'sorts_' . $sort_type . '_order';

                $su[$sort_type][1] = isset($_POST[$k]) && in_array($_POST[$k], ['asc', 'desc']) ? $_POST[$k] : $sort_data[3];
            }
            if (null !== $sort_data[4]) {
                $k = 'sorts_' . $sort_type . '_nb';

                $su[$sort_type][2] = isset($_POST[$k]) ? abs((int) $_POST[$k]) : $sort_data[4][1];
            }
        }
        dcCore::app()->auth->user_prefs->interface->put('sorts', $su, 'array');
        // All filters
        dcCore::app()->auth->user_prefs->interface->put('auto_filter', !empty($_POST['user_ui_auto_filter']), 'boolean');

        # Update user xhtml editor flags
        $rf = [];
        foreach ($rte as $rk => $rv) {
            $rf[$rk] = isset($_POST['rte_flags']) && in_array($rk, $_POST['rte_flags'], true) ? true : false;
        }
        dcCore::app()->auth->user_prefs->interface->put('rte_flags', $rf, 'array');

        # Update user
        dcCore::app()->updUser(dcCore::app()->auth->userID(), $cur);

        # --BEHAVIOR-- adminAfterUserOptionsUpdate
        dcCore::app()->callBehavior('adminAfterUserOptionsUpdate', $cur, dcCore::app()->auth->userID());

        dcPage::addSuccessNotice(__('Personal options has been successfully updated.'));
        dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-options');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Dashboard options
if (isset($_POST['db-options'])) {
    try {
        # --BEHAVIOR-- adminBeforeUserOptionsUpdate
        dcCore::app()->callBehavior('adminBeforeDashboardOptionsUpdate', dcCore::app()->auth->userID());

        # Update user prefs
        dcCore::app()->auth->user_prefs->dashboard->put('doclinks', !empty($_POST['user_dm_doclinks']), 'boolean');
        dcCore::app()->auth->user_prefs->dashboard->put('dcnews', !empty($_POST['user_dm_dcnews']), 'boolean');
        dcCore::app()->auth->user_prefs->dashboard->put('quickentry', !empty($_POST['user_dm_quickentry']), 'boolean');
        dcCore::app()->auth->user_prefs->dashboard->put('nofavicons', empty($_POST['user_dm_nofavicons']), 'boolean');
        if (dcCore::app()->auth->isSuperAdmin()) {
            dcCore::app()->auth->user_prefs->dashboard->put('nodcupdate', !empty($_POST['user_dm_nodcupdate']), 'boolean');
        }
        dcCore::app()->auth->user_prefs->interface->put('nofavmenu', empty($_POST['user_ui_nofavmenu']), 'boolean');

        # --BEHAVIOR-- adminAfterUserOptionsUpdate
        dcCore::app()->callBehavior('adminAfterDashboardOptionsUpdate', dcCore::app()->auth->userID());

        dcPage::addSuccessNotice(__('Dashboard options has been successfully updated.'));
        dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Add selected favorites
if (!empty($_POST['appendaction'])) {
    try {
        if (empty($_POST['append'])) {
            throw new Exception(__('No favorite selected'));
        }
        $user_favs = dcCore::app()->favs->getFavoriteIDs(false);
        foreach ($_POST['append'] as $k => $v) {
            if (dcCore::app()->favs->exists($v)) {
                $user_favs[] = $v;
            }
        }
        dcCore::app()->favs->setFavoriteIDs($user_favs, false);

        if (!dcCore::app()->error->flag()) {
            dcPage::addSuccessNotice(__('Favorites have been successfully added.'));
            dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Delete selected favorites
if (!empty($_POST['removeaction'])) {
    try {
        if (empty($_POST['remove'])) {
            throw new Exception(__('No favorite selected'));
        }
        $user_fav_ids = [];
        foreach (dcCore::app()->favs->getFavoriteIDs(false) as $v) {
            $user_fav_ids[$v] = true;
        }
        foreach ($_POST['remove'] as $v) {
            if (isset($user_fav_ids[$v])) {
                unset($user_fav_ids[$v]);
            }
        }
        dcCore::app()->favs->setFavoriteIDs(array_keys($user_fav_ids), false);
        if (!dcCore::app()->error->flag()) {
            dcPage::addSuccessNotice(__('Favorites have been successfully removed.'));
            dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
        }
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# Order favs
$order = [];
if (empty($_POST['favs_order']) && !empty($_POST['order'])) {
    $order = $_POST['order'];
    asort($order);
    $order = array_keys($order);
} elseif (!empty($_POST['favs_order'])) {
    $order = explode(',', $_POST['favs_order']);
}

if (!empty($_POST['saveorder']) && !empty($order)) {
    foreach ($order as $k => $v) {
        if (!dcCore::app()->favs->exists($v)) {
            unset($order[$k]);
        }
    }
    dcCore::app()->favs->setFavoriteIDs($order, false);
    if (!dcCore::app()->error->flag()) {
        dcPage::addSuccessNotice(__('Favorites have been successfully updated.'));
        dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
    }
}

# Replace default favorites by current set (super admin only)
if (!empty($_POST['replace']) && dcCore::app()->auth->isSuperAdmin()) {
    $user_favs = dcCore::app()->favs->getFavoriteIDs(false);
    dcCore::app()->favs->setFavoriteIDs($user_favs, true);

    if (!dcCore::app()->error->flag()) {
        dcPage::addSuccessNotice(__('Default favorites have been successfully updated.'));
        dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
    }
}

# Reset dashboard items order
if (!empty($_POST['resetorder'])) {
    dcCore::app()->auth->user_prefs->dashboard->drop('main_order');
    dcCore::app()->auth->user_prefs->dashboard->drop('boxes_order');
    dcCore::app()->auth->user_prefs->dashboard->drop('boxes_items_order');
    dcCore::app()->auth->user_prefs->dashboard->drop('boxes_contents_order');

    if (!dcCore::app()->error->flag()) {
        dcPage::addSuccessNotice(__('Dashboard items order have been successfully reset.'));
        dcCore::app()->adminurl->redirect('admin.user.preferences', [], '#user-favorites');
    }
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open(
    $page_title,
    ($user_acc_nodragdrop ? '' : dcPage::jsLoad('js/_preferences-dragdrop.js')) .
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsJson('pwstrength', [
        'min' => sprintf(__('Password strength: %s'), __('weak')),
        'avg' => sprintf(__('Password strength: %s'), __('medium')),
        'max' => sprintf(__('Password strength: %s'), __('strong')),
    ]) .
    dcPage::jsLoad('js/pwstrength.js') .
    dcPage::jsLoad('js/_preferences.js') .
    dcPage::jsPageTabs(dcCore::app()->admin->default_tab) .
    dcPage::jsConfirmClose('user-form', 'opts-forms', 'favs-form', 'db-forms') .

    # --BEHAVIOR-- adminPreferencesHeaders
    dcCore::app()->callBehavior('adminPreferencesHeaders'),
    dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->auth->userID()) => '',
            $page_title                                     => '',
        ]
    )
);

# User profile
echo '<div class="multi-part" id="user-profile" title="' . __('My profile') . '">';

echo
'<h3>' . __('My profile') . '</h3>' .
'<form action="' . dcCore::app()->adminurl->get('admin.user.preferences') . '" method="post" id="user-form">' .

'<p><label for="user_name">' . __('Last Name:') . '</label>' .
form::field('user_name', 20, 255, [
    'default'      => html::escapeHTML($user_name),
    'autocomplete' => 'family-name',
]) .
'</p>' .

'<p><label for="user_firstname">' . __('First Name:') . '</label>' .
form::field('user_firstname', 20, 255, [
    'default'      => html::escapeHTML($user_firstname),
    'autocomplete' => 'given-name',
]) .
'</p>' .

'<p><label for="user_displayname">' . __('Display name:') . '</label>' .
form::field('user_displayname', 20, 255, [
    'default'      => html::escapeHTML($user_displayname),
    'autocomplete' => 'nickname',
]) .
'</p>' .

'<p><label for="user_email">' . __('Email:') . '</label>' .
form::email('user_email', [
    'default'      => html::escapeHTML($user_email),
    'autocomplete' => 'email',
]) .
'</p>' .

'<p><label for="user_profile_mails">' . __('Alternate emails (comma separated list):') . '</label>' .
form::field('user_profile_mails', 50, 255, [
    'default' => html::escapeHTML($user_profile_mails),
]) .
'</p>' .
'<p class="form-note info" id="sanitize_emails">' . __('Invalid emails will be automatically removed from list.') . '</p>' .

'<p><label for="user_url">' . __('URL:') . '</label>' .
form::url('user_url', [
    'size'         => 30,
    'default'      => html::escapeHTML($user_url),
    'autocomplete' => 'url',
]) .
'</p>' .

'<p><label for="user_profile_urls">' . __('Alternate URLs (comma separated list):') . '</label>' .
form::field('user_profile_urls', 50, 255, [
    'default' => html::escapeHTML($user_profile_urls),
]) .
'</p>' .
'<p class="form-note info" id="sanitize_urls">' . __('Invalid URLs will be automatically removed from list.') . '</p>' .

'<p><label for="user_lang">' . __('Language for my interface:') . '</label>' .
form::combo('user_lang', $lang_combo, $user_lang, 'l10n') . '</p>' .

'<p><label for="user_tz">' . __('My timezone:') . '</label>' .
form::combo('user_tz', dt::getZones(true, true), $user_tz) . '</p>';

if (dcCore::app()->auth->allowPassChange()) {
    echo
    '<h4 class="vertical-separator pretty-title">' . __('Change my password') . '</h4>' .

    '<p><label for="new_pwd">' . __('New password:') . '</label>' .
    form::password(
        'new_pwd',
        20,
        255,
        [
            'class'        => 'pw-strength',
            'autocomplete' => 'new-password', ]
    ) .
    '</p>' .

    '<p><label for="new_pwd_c">' . __('Confirm new password:') . '</label>' .
    form::password(
        'new_pwd_c',
        20,
        255,
        [
            'autocomplete' => 'new-password', ]
    ) . '</p>' .

    '<p><label for="cur_pwd">' . __('Your current password:') . '</label>' .
    form::password(
        'cur_pwd',
        20,
        255,
        [
            'autocomplete' => 'current-password',
            'extra_html'   => 'aria-describedby="cur_pwd_help"',
        ]
    ) . '</p>' .
    '<p class="form-note warn" id="cur_pwd_help">' .
    __('If you have changed your email or password you must provide your current password to save these modifications.') .
        '</p>';
}

echo
'<p class="clear vertical-separator">' .
dcCore::app()->formNonce() .
'<input type="submit" accesskey="s" value="' . __('Update my profile') . '" />' .
' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
    '</p>' .
    '</form>' .

    '</div>';

# User options : some from actual user profile, dashboard modules, ...
echo '<div class="multi-part" id="user-options" title="' . __('My options') . '">';

echo
'<form action="' . dcCore::app()->adminurl->get('admin.user.preferences') . '#user-options" method="post" id="opts-forms">' .
'<h3>' . __('My options') . '</h3>';

echo
'<div class="fieldset">' .
'<h4 id="user_options_interface">' . __('Interface') . '</h4>' .

'<p><label for="user_ui_theme" class="classic">' . __('Theme:') . '</label>' . ' ' .
form::combo('user_ui_theme', $theme_combo, $user_ui_theme) . '</p>' .

'<p><label for="user_ui_enhanceduploader" class="classic">' .
form::checkbox('user_ui_enhanceduploader', 1, $user_ui_enhanceduploader) . ' ' .
__('Activate enhanced uploader in media manager') . '</label></p>' .

'<p><label for="user_ui_blank_preview" class="classic">' .
form::checkbox('user_ui_blank_preview', 1, $user_ui_blank_preview) . ' ' .
__('Preview the entry being edited in a blank window or tab (depending on your browser settings).') . '</label></p>' .

'<p><label for="user_acc_nodragdrop" class="classic">' .
form::checkbox('user_acc_nodragdrop', 1, $user_acc_nodragdrop, '', '', false, 'aria-describedby="user_acc_nodragdrop_help"') . ' ' .
__('Disable javascript powered drag and drop for ordering items') . '</label></p>' .
'<p class="clear form-note" id="user_acc_nodragdrop_help">' . __('If checked, numeric fields will allow to type the elements\' ordering number.') . '</p>' .

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
form::combo('user_ui_htmlfontsize', $htmlfontsize_combo, $user_ui_htmlfontsize) . '</p>';

echo
'<p><label for="user_ui_media_nb_last_dirs" class="classic">' . __('Number of recent folders proposed in media manager:') . '</label> ' .
form::number('user_ui_media_nb_last_dirs', 0, 999, $user_ui_media_nb_last_dirs, '', '', false, 'aria-describedby="user_ui_media_nb_last_dirs_help"') . '</p>' .
'<p class="clear form-note" id="user_ui_media_nb_last_dirs_help">' . __('Leave empty to ignore, displayed only if Javascript is enabled in your browser.') . '</p>';

if (dcCore::app()->auth->isSuperAdmin()) {
    echo
    '<p><label for="user_ui_hide_std_favicon" class="classic">' .
    form::checkbox('user_ui_hide_std_favicon', 1, $user_ui_hide_std_favicon, '', '', false, 'aria-describedby="user_ui_hide_std_favicon_help"') . ' ' .
    __('Do not use standard favicon') . '</label> ' .
    '<span class="clear form-note warn" id="user_ui_hide_std_favicon_help">' . __('This will be applied for all users') . '.</span>' .
        '</p>'; //Opera sucks;
}

echo
'<p><label for="user_ui_nocheckadblocker" class="classic">' .
form::checkbox('user_ui_nocheckadblocker', 1, $user_ui_nocheckadblocker, '', '', false, 'aria-describedby="user_ui_nocheckadblocker_help"') . ' ' .
__('Disable Ad-blocker check') . '</label></p>' .
'<p class="clear form-note" id="user_ui_nocheckadblocker_help">' . __('Some ad-blockers (Ghostery, Adblock plus, uBloc origin, …) may interfere with some feature as inserting link or media in entries with CKEditor; in this case you should disable it for this Dotclear installation (backend only). Note that Dotclear do not add ads ot trackers in the backend.') . '</p>';

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
        form::checkbox(['cols_' . $col_type . '[]', 'cols_' . $col_type . '-' . $col_name], $col_name, $col_data[0]) . $col_data[1] . '</label>';
    }
    echo '</div>';
    $odd = !$odd;
}
echo '</div>';

echo
'<div class="fieldset">' .
'<h4 id="user_options_lists">' . __('Options for lists') . '</h4>' .
'<p><label for="user_ui_auto_filter" class="classic">' .
form::checkbox('user_ui_auto_filter', 1, $auto_filter) . ' ' .
__('Apply filters on the fly') . '</label></p>';

$odd = true;
foreach ($sorts as $sort_type => $sort_data) {
    if ($odd) {
        echo '<hr />';
    }
    echo '<div class="two-boxes ' . ($odd ? 'odd' : 'even') . '">';
    echo '<h5>' . $sort_data[0] . '</h5>';
    if (null !== $sort_data[1]) {
        echo
        '<p class="field"><label for="sorts_' . $sort_type . '_sortby">' . __('Order by:') . '</label> ' .
        form::combo('sorts_' . $sort_type . '_sortby', $sort_data[1], $sort_data[2]) . '</p>';
    }
    if (null !== $sort_data[3]) {
        echo
        '<p class="field"><label for="sorts_' . $sort_type . '_order">' . __('Sort:') . '</label> ' .
        form::combo('sorts_' . $sort_type . '_order', $order_combo, $sort_data[3]) . '</p>';
    }
    if (is_array($sort_data[4])) {
        echo
        '<p><span class="label ib">' . __('Show') . '</span> <label for="sorts_' . $sort_type . '_nb" class="classic">' .
        form::number('sorts_' . $sort_type . '_nb', 0, 999, $sort_data[4][1]) . ' ' .
        $sort_data[4][0] . '</label></p>';
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
        ['user_editor[' . $format . ']', 'user_editor_' . $format],
        array_merge([__('Choose an editor') => ''], $editors),
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
form::number('user_edit_size', 10, 999, $user_options['edit_size']) . '</p>' .

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
    form::checkbox(['rte_flags[]', 'rte_' . $rk], $rk, $rv[0]) . $rv[1] . '</label>';
}
echo '</div>';

echo '</div>'; // fieldset

echo
'<h4 class="pretty-title">' . __('Other options') . '</h4>';

# --BEHAVIOR-- adminPreferencesForm
dcCore::app()->callBehavior('adminPreferencesFormV2');

echo
'<p class="clear vertical-separator">' .
dcCore::app()->formNonce() .
'<input type="submit" name="user_options_submit" accesskey="s" value="' . __('Save my options') . '" />' .
' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
    '</p>' .
    '</form>';

echo '</div>';

# My dashboard
echo '<div class="multi-part" id="user-favorites" title="' . __('My dashboard') . '">';
$ws = dcCore::app()->auth->user_prefs->addWorkspace('favorites');
echo '<h3>' . __('My dashboard') . '</h3>';

# Favorites
echo '<form action="' . dcCore::app()->adminurl->get('admin.user.preferences') . '" method="post" id="favs-form" class="two-boxes odd">';

echo '<div id="my-favs" class="fieldset"><h4>' . __('My favorites') . '</h4>';

$count    = 0;
$user_fav = dcCore::app()->favs->getFavoriteIDs(false);
foreach ($user_fav as $id) {
    if ($fav = dcCore::app()->favs->getFavorite($id)) {
        // User favorites only
        if ($count == 0) {
            echo '<ul class="fav-list">';
        }

        $count++;

        $icon = dcAdminHelper::adminIcon($fav['small-icon']);
        $zoom = dcAdminHelper::adminIcon($fav['large-icon'], false);
        if ($zoom !== '') {
            $icon .= ' <span class="zoom">' . $zoom . '</span>';
        }
        echo '<li id="fu-' . $id . '">' . '<label for="fuk-' . $id . '">' . $icon .
        form::number(['order[' . $id . ']'], [
            'min'        => 1,
            'max'        => count($user_fav),
            'default'    => $count,
            'class'      => 'position',
            'extra_html' => 'title="' . sprintf(__('position of %s'), $fav['title']) . '"',
        ]) .
        form::hidden(['dynorder[]', 'dynorder-' . $id . ''], $id) .
        form::checkbox(['remove[]', 'fuk-' . $id], $id) . __($fav['title']) . '</label>' .
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
    dcCore::app()->formNonce() .
    '<input type="submit" name="saveorder" value="' . __('Save order') . '" /> ' .

    '<input type="submit" class="delete" name="removeaction" ' .
    'value="' . __('Delete selected favorites') . '" ' .
    'onclick="return window.confirm(\'' . html::escapeJS(
        __('Are you sure you want to remove selected favorites?')
    ) . '\');" /></p>' .

        (dcCore::app()->auth->isSuperAdmin() ?
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

$avail_fav       = dcCore::app()->favs->getFavorites(dcCore::app()->favs->getAvailableFavoritesIDs());
$default_fav_ids = [];
foreach (dcCore::app()->favs->getFavoriteIDs(true) as $v) {
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
        strtolower(dcUtils::removeDiacritics($b['title']))
    );
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
    $icon = dcAdminHelper::adminIcon($fav['small-icon']);
    $zoom = dcAdminHelper::adminIcon($fav['large-icon'], false);
    if ($zoom !== '') {
        $icon .= ' <span class="zoom">' . $zoom . '</span>';
    }
    echo '<li id="fa-' . $k . '">' . '<label for="fak-' . $k . '">' . $icon .
    form::checkbox(['append[]', 'fak-' . $k], $k) .
        $fav['title'] . '</label>' .
        (isset($default_fav_ids[$k]) ? ' <span class="default-fav"><img src="images/selected.png" alt="' . __('(default favorite)') . '" /></span>' : '') .
        '</li>';
}
if ($count > 0) {
    echo '</ul>';
}

echo
'<p>' .
dcCore::app()->formNonce() .
'<input type="submit" name="appendaction" value="' . __('Add to my favorites') . '" /></p>';
echo '</div>'; # /available favorites

echo '</form>';

# Dashboard items
echo
'<form action="' . dcCore::app()->adminurl->get('admin.user.preferences') . '" method="post" id="db-forms" class="two-boxes even">' .

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

if (dcCore::app()->auth->isSuperAdmin()) {
    echo
    '<p><label for="user_dm_nodcupdate" class="classic">' .
    form::checkbox('user_dm_nodcupdate', 1, $user_dm_nodcupdate) . ' ' .
    __('Do not display Dotclear updates') . '</label></p>';
}

echo '</div>';

# --BEHAVIOR-- adminDashboardOptionsForm
dcCore::app()->callBehavior('adminDashboardOptionsFormV2');

echo
'<p>' .
form::hidden('db-options', '-') .
dcCore::app()->formNonce() .
'<input type="submit" accesskey="s" value="' . __('Save my dashboard options') . '" />' .
' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
    '</p>' .
    '</form>';

# Dashboard items order (reset)
echo '<form action="' . dcCore::app()->adminurl->get('admin.user.preferences') . '" method="post" id="order-reset" class="two-boxes even">';
echo '<div class="fieldset"><h4>' . __('Dashboard items order') . '</h4>';
echo
'<p>' .
dcCore::app()->formNonce() .
'<input type="submit" name="resetorder" value="' . __('Reset dashboard items order') . '" /></p>';
echo '</div>';
echo '</form>';

echo '</div>'; # /multipart-user-favorites

dcPage::helpBlock('core_user_pref');
dcPage::close();
