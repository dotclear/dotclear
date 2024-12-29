<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Helper;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/preferences.php
 */
class UserPreferences extends Process
{
    public static function init(): bool
    {
        Page::check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        App::backend()->page_title = __('My preferences');

        App::backend()->user_name        = App::auth()->getInfo('user_name');
        App::backend()->user_firstname   = App::auth()->getInfo('user_firstname');
        App::backend()->user_displayname = App::auth()->getInfo('user_displayname');
        App::backend()->user_email       = App::auth()->getInfo('user_email');
        App::backend()->user_url         = App::auth()->getInfo('user_url');
        App::backend()->user_lang        = App::auth()->getInfo('user_lang');
        App::backend()->user_tz          = App::auth()->getInfo('user_tz');
        App::backend()->user_post_status = App::auth()->getInfo('user_post_status');

        $user_options = App::auth()->getOptions();
        if (empty($user_options['editor']) || !is_array($user_options['editor'])) {
            $user_options['editor'] = [];
        }

        App::backend()->user_profile_mails = App::auth()->prefs()->profile->mails;
        App::backend()->user_profile_urls  = App::auth()->prefs()->profile->urls;

        App::backend()->user_dm_doclinks   = App::auth()->prefs()->dashboard->doclinks;
        App::backend()->user_dm_donate     = App::auth()->prefs()->dashboard->donate;
        App::backend()->user_dm_dcnews     = App::auth()->prefs()->dashboard->dcnews;
        App::backend()->user_dm_quickentry = App::auth()->prefs()->dashboard->quickentry;
        App::backend()->user_dm_nofavicons = App::auth()->prefs()->dashboard->nofavicons;
        App::backend()->user_dm_nodcupdate = false;
        if (App::auth()->isSuperAdmin()) {
            App::backend()->user_dm_nodcupdate = App::auth()->prefs()->dashboard->nodcupdate;
        }

        App::backend()->user_acc_nodragdrop = App::auth()->prefs()->accessibility->nodragdrop;

        App::backend()->user_ui_theme            = App::auth()->prefs()->interface->theme;
        App::backend()->user_ui_enhanceduploader = App::auth()->prefs()->interface->enhanceduploader;
        App::backend()->user_ui_blank_preview    = App::auth()->prefs()->interface->blank_preview;
        App::backend()->user_ui_hidemoreinfo     = App::auth()->prefs()->interface->hidemoreinfo;
        App::backend()->user_ui_hidehelpbutton   = App::auth()->prefs()->interface->hidehelpbutton;
        App::backend()->user_ui_htmlfontsize     = App::auth()->prefs()->interface->htmlfontsize;
        App::backend()->user_ui_systemfont       = App::auth()->prefs()->interface->systemfont;
        App::backend()->user_ui_hide_std_favicon = false;
        if (App::auth()->isSuperAdmin()) {
            App::backend()->user_ui_hide_std_favicon = App::auth()->prefs()->interface->hide_std_favicon;
        }
        App::backend()->user_ui_nofavmenu          = App::auth()->prefs()->interface->nofavmenu;
        App::backend()->user_ui_media_nb_last_dirs = App::auth()->prefs()->interface->media_nb_last_dirs;
        App::backend()->user_ui_nocheckadblocker   = App::auth()->prefs()->interface->nocheckadblocker;
        App::backend()->user_ui_quickmenuprefix    = App::auth()->prefs()->interface->quickmenuprefix;

        // Format by editors
        $formaters         = App::formater()->getFormaters();
        $format_by_editors = [];
        foreach ($formaters as $editor => $formats) {
            foreach ($formats as $format) {
                $format_by_editors[$format][$editor] = $editor;
            }
        }
        $available_formats = ['' => ''];
        foreach (array_keys($format_by_editors) as $format) {
            $available_formats[App::formater()->getFormaterName($format)] = $format;
            if (!isset($user_options['editor'][$format])) {
                $user_options['editor'][$format] = '';
            }
        }
        App::backend()->user_options      = $user_options;
        App::backend()->format_by_editors = $format_by_editors;
        App::backend()->available_formats = $available_formats;
        App::backend()->status_combo      = Combos::getPostStatusescombo();

        // Themes
        App::backend()->theme_combo = [
            __('Light')     => 'light',
            __('Dark')      => 'dark',
            __('Automatic') => '',
        ];

        // Body base font size (37.5% = 6px, 50% = 8px, 62.5% = 10px, 75% = 12px, 87.5% = 14px)
        App::backend()->htmlfontsize_combo = [
            __('Smallest') => '37.5%',
            __('Smaller')  => '50%',
            __('Default')  => '62.5%',
            __('Larger')   => '75%',
            __('Largest')  => '87.5%',
        ];
        // Ensure Font size is set to default is empty
        if (App::backend()->user_ui_htmlfontsize == '') {
            App::backend()->user_ui_htmlfontsize = '62.5%';
        }

        // Language codes
        App::backend()->lang_combo = Combos::getAdminLangsCombo();

        // Get 3rd parts HTML editor flags
        $rte = [
            'blog_descr' => [true, __('Blog description (in blog parameters)')],
            'cat_descr'  => [true, __('Category description')],
        ];
        $rte = new ArrayObject($rte);
        # --BEHAVIOR-- adminRteFlagsV2 -- ArrayObject
        App::behavior()->callBehavior('adminRteFlagsV2', $rte);
        // Load user settings
        $rte_flags = @App::auth()->prefs()->interface->rte_flags;
        if (is_array($rte_flags)) {
            foreach ($rte_flags as $fk => $fv) {
                if (isset($rte[$fk])) {
                    $rte[$fk][0] = $fv;
                }
            }
        }
        App::backend()->rte = $rte;

        // Get default colums (admin lists)
        App::backend()->cols = UserPref::getUserColumns();

        // Get default sortby, order, nbperpage (admin lists)
        App::backend()->sorts = UserPref::getUserFilters();

        App::backend()->order_combo = [
            __('Descending') => 'desc',
            __('Ascending')  => 'asc',
        ];
        // All filters
        App::backend()->auto_filter = App::auth()->prefs()->interface->auto_filter;

        // Specific tab
        App::backend()->tab = empty($_REQUEST['tab']) ? '' : Html::escapeHTML($_REQUEST['tab']);

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Update user

            try {
                $pwd_check = !empty($_POST['cur_pwd']) && App::auth()->checkPassword($_POST['cur_pwd']);

                if (App::auth()->allowPassChange() && !$pwd_check && App::backend()->user_email != $_POST['user_email']) {
                    throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                }

                $cur = App::auth()->openUserCursor();

                $cur->user_name        = App::backend()->user_name = $_POST['user_name'];
                $cur->user_firstname   = App::backend()->user_firstname = $_POST['user_firstname'];
                $cur->user_displayname = App::backend()->user_displayname = $_POST['user_displayname'];
                $cur->user_email       = App::backend()->user_email = $_POST['user_email'];
                $cur->user_url         = App::backend()->user_url = $_POST['user_url'];
                $cur->user_lang        = App::backend()->user_lang = $_POST['user_lang'];
                $cur->user_tz          = App::backend()->user_tz = $_POST['user_tz'];

                $cur->user_options = new ArrayObject(App::backend()->user_options);

                if (App::auth()->allowPassChange() && !empty($_POST['new_pwd'])) {
                    if (!$pwd_check) {
                        throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                    }

                    if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                        throw new Exception(__("Passwords don't match"));
                    }

                    $cur->user_pwd = $_POST['new_pwd'];
                }

                # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                App::behavior()->callBehavior('adminBeforeUserProfileUpdate', $cur, App::auth()->userID());

                // Update user
                App::users()->updUser((string) App::auth()->userID(), $cur);

                // Update profile
                // Sanitize list of secondary mails and urls if any
                $mails = $urls = '';
                if (!empty($_POST['user_profile_mails'])) {
                    $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                }
                if (!empty($_POST['user_profile_urls'])) {
                    $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                }
                App::auth()->prefs()->profile->put('mails', $mails, 'string');
                App::auth()->prefs()->profile->put('urls', $urls, 'string');

                # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterUserProfileUpdate', $cur, App::auth()->userID());

                Notices::addSuccessNotice(__('Personal information has been successfully updated.'));

                App::backend()->url()->redirect('admin.user.preferences');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset($_POST['user_options_submit'])) {
            // Update user options

            try {
                // Prepare user options

                $user_options              = App::backend()->user_options;
                $user_options['edit_size'] = (int) $_POST['user_edit_size'];
                if ($user_options['edit_size'] < 1) {
                    $user_options['edit_size'] = 10;
                }
                $user_options['post_format']    = $_POST['user_post_format'];
                $user_options['editor']         = $_POST['user_editor'];
                $user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
                $user_options['toolbar_bottom'] = !empty($_POST['user_toolbar_bottom']);

                App::backend()->user_options = $user_options;

                $cur = App::auth()->openUserCursor();

                $cur->user_name        = App::backend()->user_name;
                $cur->user_firstname   = App::backend()->user_firstname;
                $cur->user_displayname = App::backend()->user_displayname;
                $cur->user_email       = App::backend()->user_email;
                $cur->user_url         = App::backend()->user_url;
                $cur->user_lang        = App::backend()->user_lang;
                $cur->user_tz          = App::backend()->user_tz;

                $cur->user_post_status = App::backend()->user_post_status = $_POST['user_post_status'];

                $cur->user_options = new ArrayObject(App::backend()->user_options);

                # --BEHAVIOR-- adminBeforeUserOptionsUpdate -- Cursor, null|string
                App::behavior()->callBehavior('adminBeforeUserOptionsUpdate', $cur, App::auth()->userID());

                // Update user prefs
                App::auth()->prefs()->accessibility->put('nodragdrop', !empty($_POST['user_acc_nodragdrop']), 'boolean');
                App::auth()->prefs()->interface->put('theme', $_POST['user_ui_theme'], 'string');
                App::auth()->prefs()->interface->put('enhanceduploader', !empty($_POST['user_ui_enhanceduploader']), 'boolean');
                App::auth()->prefs()->interface->put('blank_preview', !empty($_POST['user_ui_blank_preview']), 'boolean');
                App::auth()->prefs()->interface->put('hidemoreinfo', !empty($_POST['user_ui_hidemoreinfo']), 'boolean');
                App::auth()->prefs()->interface->put('hidehelpbutton', !empty($_POST['user_ui_hidehelpbutton']), 'boolean');
                App::auth()->prefs()->interface->put('htmlfontsize', $_POST['user_ui_htmlfontsize'], 'string');
                App::auth()->prefs()->interface->put('systemfont', !empty($_POST['user_ui_systemfont']), 'boolean');
                if (App::auth()->isSuperAdmin()) {
                    # Applied to all users
                    App::auth()->prefs()->interface->put('hide_std_favicon', !empty($_POST['user_ui_hide_std_favicon']), 'boolean', null, true, true);
                }
                App::auth()->prefs()->interface->put('media_nb_last_dirs', (int) $_POST['user_ui_media_nb_last_dirs'], 'integer');
                App::auth()->prefs()->interface->put('media_last_dirs', [], 'array', null, false);
                App::auth()->prefs()->interface->put('media_fav_dirs', [], 'array', null, false);
                App::auth()->prefs()->interface->put('nocheckadblocker', !empty($_POST['user_ui_nocheckadblocker']), 'boolean');
                App::auth()->prefs()->interface->put('quickmenuprefix', $_POST['user_ui_quickmenuprefix'], 'string');

                // Update user columns (lists)
                $cu = [];
                foreach (App::backend()->cols as $col_type => $cols_list) {
                    $ct = [];
                    foreach (array_keys($cols_list[1]) as $col_name) {
                        $ct[$col_name] = isset($_POST['cols_' . $col_type]) && in_array($col_name, $_POST['cols_' . $col_type], true);
                    }
                    if ($ct !== []) {
                        if (isset($_POST['cols_' . $col_type])) {
                            // Sort resulting list
                            $order = array_values($_POST['cols_' . $col_type]);
                            $order = array_unique(array_merge($order, array_keys($ct)));
                            uksort($ct, fn ($key1, $key2): int => array_search($key1, $order) <=> array_search($key2, $order));
                        }
                        $cu[$col_type] = $ct;
                    }
                }
                App::auth()->prefs()->interface->put('cols', $cu, 'array');

                // Update user lists options
                $su = [];
                foreach (App::backend()->sorts as $sort_type => $sort_data) {
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
                App::auth()->prefs()->interface->put('sorts', $su, 'array');
                // All filters
                App::auth()->prefs()->interface->put('auto_filter', !empty($_POST['user_ui_auto_filter']), 'boolean');

                // Update user HTML editor flags
                $rf = [];
                foreach (App::backend()->rte as $rk => $rv) {
                    $rf[$rk] = isset($_POST['rte_flags']) && in_array($rk, $_POST['rte_flags'], true);
                }
                App::auth()->prefs()->interface->put('rte_flags', $rf, 'array');

                // Update user
                App::users()->updUser((string) App::auth()->userID(), $cur);

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterUserOptionsUpdate', $cur, App::auth()->userID());

                Notices::addSuccessNotice(__('Personal options has been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-options');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset($_POST['db-options'])) {
            // Dashboard options

            try {
                # --BEHAVIOR-- adminBeforeUserOptionsUpdate -- string
                App::behavior()->callBehavior('adminBeforeDashboardOptionsUpdate', App::auth()->userID());

                // Update user prefs
                App::auth()->prefs()->dashboard->put('doclinks', !empty($_POST['user_dm_doclinks']), 'boolean');
                App::auth()->prefs()->dashboard->put('donate', !empty($_POST['user_dm_donate']), 'boolean');
                App::auth()->prefs()->dashboard->put('dcnews', !empty($_POST['user_dm_dcnews']), 'boolean');
                App::auth()->prefs()->dashboard->put('quickentry', !empty($_POST['user_dm_quickentry']), 'boolean');
                App::auth()->prefs()->dashboard->put('nofavicons', empty($_POST['user_dm_nofavicons']), 'boolean');
                if (App::auth()->isSuperAdmin()) {
                    App::auth()->prefs()->dashboard->put('nodcupdate', !empty($_POST['user_dm_nodcupdate']), 'boolean');
                }
                App::auth()->prefs()->interface->put('nofavmenu', empty($_POST['user_ui_nofavmenu']), 'boolean');

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- string
                App::behavior()->callBehavior('adminAfterDashboardOptionsUpdate', App::auth()->userID());

                Notices::addSuccessNotice(__('Dashboard options has been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['appendaction'])) {
            // Add selected favorites

            try {
                if (empty($_POST['append'])) {
                    throw new Exception(__('No favorite selected'));
                }
                $user_favs = App::backend()->favorites()->getFavoriteIDs(false);
                foreach ($_POST['append'] as $v) {
                    if (App::backend()->favorites()->exists($v)) {
                        $user_favs[] = $v;
                    }
                }
                App::backend()->favorites()->setFavoriteIDs($user_favs, false);

                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('Favorites have been successfully added.'));
                    App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['removeaction'])) {
            // Delete selected favorites

            try {
                if (empty($_POST['remove'])) {
                    throw new Exception(__('No favorite selected'));
                }
                $user_fav_ids = [];
                foreach (App::backend()->favorites()->getFavoriteIDs(false) as $v) {
                    $user_fav_ids[$v] = true;
                }
                foreach ($_POST['remove'] as $v) {
                    if (isset($user_fav_ids[$v])) {
                        unset($user_fav_ids[$v]);
                    }
                }
                App::backend()->favorites()->setFavoriteIDs(array_keys($user_fav_ids), false);
                if (!App::error()->flag()) {
                    Notices::addSuccessNotice(__('Favorites have been successfully removed.'));
                    App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Prepare order favs (see below)

        if (empty($_POST['favs_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['favs_order'])) {
            $order = explode(',', (string) $_POST['favs_order']);
        } else {
            $order = [];
        }

        if (!empty($_POST['saveorder']) && $order !== []) {
            // Order favs

            foreach ($order as $k => $v) {
                if (!App::backend()->favorites()->exists((string) $v)) {
                    unset($order[$k]);
                }
            }
            App::backend()->favorites()->setFavoriteIDs($order, false);    // @phpstan-ignore-line : $order is array<string>
            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Favorites have been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['replace']) && App::auth()->isSuperAdmin()) {
            // Replace default favorites by current set (super admin only)

            $user_favs = App::backend()->favorites()->getFavoriteIDs(false);
            App::backend()->favorites()->setFavoriteIDs($user_favs, true);

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Default favorites have been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['resetorder'])) {
            // Reset dashboard items order

            App::auth()->prefs()->dashboard->drop('main_order');
            App::auth()->prefs()->dashboard->drop('boxes_order');
            App::auth()->prefs()->dashboard->drop('boxes_items_order');
            App::auth()->prefs()->dashboard->drop('boxes_contents_order');

            if (!App::error()->flag()) {
                Notices::addSuccessNotice(__('Dashboard items order have been successfully reset.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            App::backend()->page_title,
            (App::backend()->user_acc_nodragdrop ? '' : Page::jsLoad('js/_preferences-dragdrop.js')) .
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('js/pwstrength.js') .
            Page::jsLoad('js/_preferences.js') .
            Page::jsPageTabs(App::backend()->tab) .
            Page::jsConfirmClose('user-form', 'opts-forms', 'favs-form', 'db-forms') .
            Page::jsAdsBlockCheck() .

            # --BEHAVIOR-- adminPreferencesHeaders --
            App::behavior()->callBehavior('adminPreferencesHeaders'),
            Page::breadcrumb(
                [
                    Html::escapeHTML(App::auth()->userID()) => '',
                    App::backend()->page_title              => '',
                ]
            )
        );

        // User profile
        echo '<div class="multi-part" id="user-profile" title="' . __('My profile') . '">' .

        '<h3>' . __('My profile') . '</h3>' .
        '<form action="' . App::backend()->url()->get('admin.user.preferences') . '" method="post" id="user-form">' .

        '<p><label for="user_name">' . __('Last Name:') . '</label>' .
        form::field('user_name', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_name),
            'autocomplete' => 'family-name',
        ]) .
        '</p>' .

        '<p><label for="user_firstname">' . __('First Name:') . '</label>' .
        form::field('user_firstname', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_firstname),
            'autocomplete' => 'given-name',
        ]) .
        '</p>' .

        '<p><label for="user_displayname">' . __('Display name:') . '</label>' .
        form::field('user_displayname', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_displayname),
            'autocomplete' => 'nickname',
        ]) .
        '</p>' .

        '<p><label for="user_email">' . __('Email:') . '</label>' .
        form::email('user_email', [
            'default'      => Html::escapeHTML(App::backend()->user_email),
            'autocomplete' => 'email',
        ]) .
        '</p>' .

        '<p><label for="user_profile_mails">' . __('Alternate emails (comma separated list):') . '</label>' .
        form::field('user_profile_mails', 50, 255, [
            'default' => Html::escapeHTML(App::backend()->user_profile_mails),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_emails">' . __('Invalid emails will be automatically removed from list.') . '</p>' .

        '<p><label for="user_url">' . __('URL:') . '</label>' .
        form::url('user_url', [
            'size'         => 30,
            'default'      => Html::escapeHTML(App::backend()->user_url),
            'autocomplete' => 'url',
        ]) .
        '</p>' .

        '<p><label for="user_profile_urls">' . __('Alternate URLs (comma separated list):') . '</label>' .
        form::field('user_profile_urls', 50, 255, [
            'default' => Html::escapeHTML(App::backend()->user_profile_urls),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_urls">' . __('Invalid URLs will be automatically removed from list.') . '</p>' .

        '<p><label for="user_lang">' . __('Language for my interface:') . '</label>' .
        form::combo('user_lang', App::backend()->lang_combo, App::backend()->user_lang) . '</p>' .

        '<p><label for="user_tz">' . __('My timezone:') . '</label>' .
        form::combo('user_tz', Date::getZones(true, true), App::backend()->user_tz) . '</p>';

        if (App::auth()->allowPassChange()) {
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
            ) . '</p>' .
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
            __('If you have changed your email or password you must provide your current password to save these modifications.') . '</p>';
        }

        echo
        '<p class="clear vertical-separator form-buttons">' .
        App::nonce()->getFormNonce() .
        '<input type="submit" accesskey="s" value="' . __('Update my profile') . '">' .
        ' <input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js">' .
        '</p>' .
        '</form>' .
        '</div>' .

        // User options : some from actual user profile, dashboard modules, ...

        '<div class="multi-part" id="user-options" title="' . __('My options') . '">' .

        '<form action="' . App::backend()->url()->get('admin.user.preferences') . '#user-options" method="post" id="opts-forms">' .
        '<h3>' . __('My options') . '</h3>' .

        '<div class="fieldset">' .
        '<h4 id="user_options_interface">' . __('Interface') . '</h4>' .

        '<p><label for="user_ui_theme" class="classic">' . __('Theme:') . '</label>' . ' ' .
        form::combo('user_ui_theme', App::backend()->theme_combo, App::backend()->user_ui_theme) . '</p>' .

        '<p><label for="user_ui_enhanceduploader" class="classic">' .
        form::checkbox('user_ui_enhanceduploader', 1, App::backend()->user_ui_enhanceduploader) . ' ' .
        __('Activate enhanced uploader in media manager') . '</label></p>' .

        '<p><label for="user_ui_blank_preview" class="classic">' .
        form::checkbox('user_ui_blank_preview', 1, App::backend()->user_ui_blank_preview) . ' ' .
        __('Preview the entry being edited in a blank window or tab (depending on your browser settings).') . '</label></p>' .

        '<p><label for="user_acc_nodragdrop" class="classic">' .
        form::checkbox('user_acc_nodragdrop', 1, App::backend()->user_acc_nodragdrop, '', '', false, 'aria-describedby="user_acc_nodragdrop_help"') . ' ' .
        __('Disable javascript powered drag and drop for ordering items') . '</label></p>' .
        '<p class="clear form-note" id="user_acc_nodragdrop_help">' . __('If checked, numeric fields will allow to type the elements\' ordering number.') . '</p>' .

        '<p><label for="user_ui_hidemoreinfo" class="classic">' .
        form::checkbox('user_ui_hidemoreinfo', 1, App::backend()->user_ui_hidemoreinfo) . ' ' .
        __('Hide all secondary information and notes') . '</label></p>' .

        '<p><label for="user_ui_hidehelpbutton" class="classic">' .
        form::checkbox('user_ui_hidehelpbutton', 1, App::backend()->user_ui_hidehelpbutton) . ' ' .
        __('Hide help button') . '</label></p>' .

        '<p><label for="user_ui_htmlfontsize" class="classic">' . __('Font size:') . '</label>' . ' ' .
        form::combo('user_ui_htmlfontsize', App::backend()->htmlfontsize_combo, App::backend()->user_ui_htmlfontsize) . '</p>' .

        '<p><label for="user_ui_systemfont" class="classic">' .
        form::checkbox('user_ui_systemfont', 1, App::backend()->user_ui_systemfont) . ' ' .
        __('Use operating system font') . '</label></p>' .

        '<p><label for="user_ui_media_nb_last_dirs" class="classic">' . __('Number of recent folders proposed in media manager:') . '</label> ' .
        form::number('user_ui_media_nb_last_dirs', 0, 999, (string) App::backend()->user_ui_media_nb_last_dirs, '', '', false, 'aria-describedby="user_ui_media_nb_last_dirs_help"') . '</p>' .
        '<p class="clear form-note" id="user_ui_media_nb_last_dirs_help">' . __('Leave empty to ignore, displayed only if Javascript is enabled in your browser.') . '</p>';

        if (App::auth()->isSuperAdmin()) {
            echo
            '<p><label for="user_ui_hide_std_favicon" class="classic">' .
            form::checkbox('user_ui_hide_std_favicon', 1, App::backend()->user_ui_hide_std_favicon, '', '', false, 'aria-describedby="user_ui_hide_std_favicon_help"') . ' ' .
            __('Do not use standard favicon') . '</label> ' .
            '<span class="clear form-note warn" id="user_ui_hide_std_favicon_help">' . __('This will be applied for all users') . '.</span>' .
            '</p>';
        }

        echo
        '<p><label for="user_ui_nocheckadblocker" class="classic">' .
        form::checkbox('user_ui_nocheckadblocker', 1, App::backend()->user_ui_nocheckadblocker, '', '', false, 'aria-describedby="user_ui_nocheckadblocker_help"') . ' ' .
        __('Disable Ad-blocker check') . '</label></p>' .
        '<p class="clear form-note" id="user_ui_nocheckadblocker_help">' . __('Some ad-blockers (Ghostery, Adblock plus, uBloc origin, …) may interfere with some feature as inserting link or media in entries with CKEditor; in this case you should disable it for this Dotclear installation (backend only). Note that Dotclear do not add ads ot trackers in the backend.') . '</p>' .
        '<p class="clear form-note" id="user_ui_nocheckadblocker_more">' . __('Note also that deactivating this detection of ad blockers will not deactivate the installed ad blockers. Dotclear cannot interfere with the operation of browser extensions!') . '</p>';

        echo
        '<p><label class="classic">' . __('Quick menu character:') . ' ' .
        form::field('user_ui_quickmenuprefix', 1, 1, [
            'default' => Html::escapeHTML(App::backend()->user_ui_quickmenuprefix),
        ]) . '</label>' .
        '</p>' .
        '<p class="clear form-note" id="user_ui_quickmenuprefix_help">' . __('Leave empty to use the default character <kbd>:</kbd>') . '</p>'
        ;

        echo
        '</div>' .

        '<fieldset id="user_options_columns">' .
        '<legend>' . __('Optional columns displayed in lists') . '</legend>';
        $odd = true;
        foreach (App::backend()->cols as $col_type => $col_list) {
            echo
            '<div class="two-boxes ' . ($odd ? 'odd' : 'even') . '">' .
            '<h5>' . $col_list[0] . '</h5>';
            foreach ($col_list[1] as $col_name => $col_data) {
                echo
                '<label>' .
                form::checkbox(['cols_' . $col_type . '[]', 'cols_' . $col_type . '-' . $col_name], $col_name, $col_data[0]) . $col_data[1] . '</label>';
            }
            echo
            '</div>';
            $odd = !$odd;
        }
        echo
        '</fieldset>' .

        '<div class="fieldset" id="user_options_lists_container">' .
        '<h4 id="user_options_lists">' . __('Options for lists') . '</h4>' .
        '<p><label for="user_ui_auto_filter" class="classic">' .
        form::checkbox('user_ui_auto_filter', 1, App::backend()->auto_filter) . ' ' .
        __('Apply filters on the fly') . '</label></p>';

        echo
        '<table class="table-outer">' .
        '<thead>' .
        '<tr>' .
        '<th>' . __('List') . '</th>' .
        '<th>' . __('Order by') . '</th>' .
        '<th>' . __('Sort') . '</th>' .
        '<th>' . __('Show') . '</th>' .
        '</tr>' .
        '</thead>' .
        '<tbody>';
        foreach (App::backend()->sorts as $sort_type => $sort_data) {
            echo '<tr>';
            echo '<td>' . $sort_data[0] . '</td>';  // List name
            echo '<td>' . ($sort_data[1] ? form::combo('sorts_' . $sort_type . '_sortby', $sort_data[1], $sort_data[2]) : '') . '</td>'; // Order by
            echo '<td>' . ($sort_data[3] ? form::combo('sorts_' . $sort_type . '_order', App::backend()->order_combo, $sort_data[3]) : '') . '</td>'; // Sort by
            echo '<td>' . (is_array($sort_data[4]) ? form::number('sorts_' . $sort_type . '_nb', 0, 999, (string) $sort_data[4][1]) . ' ' .
                $sort_data[4][0] : '') . '</td>';
            echo '</tr>';
        }
        echo
        '</table>';
        echo
        '</div>' .

        '<div class="fieldset">' .
        '<h4 id="user_options_edition">' . __('Edition') . '</h4>' .

        '<div class="two-boxes odd">';
        foreach (App::backend()->format_by_editors as $format => $editors) {
            echo
            '<p class="field"><label for="user_editor_' . $format . '">' . sprintf(__('Preferred editor for %s:'), App::formater()->getFormaterName($format)) . '</label>' .
            form::combo(
                ['user_editor[' . $format . ']', 'user_editor_' . $format],
                array_merge([__('Choose an editor') => ''], $editors),
                App::backend()->user_options['editor'][$format]
            ) .
            '</p>';
        }
        echo
        '<p class="field"><label for="user_post_format">' . __('Preferred format:') . '</label>' .
        form::combo('user_post_format', App::backend()->available_formats, App::backend()->user_options['post_format']) . '</p>' .

        '<p class="field"><label for="user_post_status">' . __('Default entry status:') . '</label>' .
        form::combo('user_post_status', App::backend()->status_combo, App::backend()->user_post_status) . '</p>' .

        '<p class="field"><label for="user_edit_size">' . __('Entry edit field height:') . '</label>' .
        form::number('user_edit_size', 10, 999, (string) App::backend()->user_options['edit_size']) . '</p>' .

        '<p><label for="user_wysiwyg" class="classic">' .
        form::checkbox('user_wysiwyg', 1, App::backend()->user_options['enable_wysiwyg']) . ' ' .
        __('Enable WYSIWYG mode') . '</label></p>' .

        '<p><label for="user_toolbar_bottom" class="classic">' .
        form::checkbox('user_toolbar_bottom', 1, App::backend()->user_options['toolbar_bottom']) . ' ' .
        __('Display editor\'s toolbar at bottom of textarea (if possible)') . '</label></p>' .

        '</div>';

        echo
        '<div class="two-boxes even">' .
        '<h5>' . __('Use HTML editor for:') . '</h5>';
        foreach (App::backend()->rte as $rk => $rv) {
            echo
            '<p><label for="rte_' . $rk . '" class="classic">' .
            form::checkbox(['rte_flags[]', 'rte_' . $rk], $rk, $rv[0]) . $rv[1] . '</label>';
        }
        echo
        '</div>' .
        '</div>' . // fieldset

        '<h4 class="pretty-title">' . __('Other options') . '</h4>';

        # --BEHAVIOR-- adminPreferencesForm --
        App::behavior()->callBehavior('adminPreferencesFormV2');

        echo
        '<p class="clear vertical-separator form-buttons">' .
        App::nonce()->getFormNonce() .
        '<input type="submit" name="user_options_submit" accesskey="s" value="' . __('Save my options') . '">' .
        ' <input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js">' .
        '</p>' .
        '</form>' .
        '</div>' .

        // My dashboard
        '<div class="multi-part" id="user-favorites" title="' . __('My dashboard') . '">' .
        '<h3>' . __('My dashboard') . '</h3>' .

        // Favorites
        '<form action="' . App::backend()->url()->get('admin.user.preferences') . '" method="post" id="favs-form" class="two-boxes odd">' .
        '<div id="my-favs" class="fieldset"><h4>' . __('My favorites') . '</h4>';

        $count    = 0;
        $user_fav = App::backend()->favorites()->getFavoriteIDs(false);
        foreach ($user_fav as $id) {
            if ($fav = App::backend()->favorites()->getFavorite($id)) {
                // User favorites only
                if ($count == 0) {
                    echo
                    '<ul class="fav-list">';
                }

                $count++;

                $icon = isset($fav['small-icon']) ? Helper::adminIcon($fav['small-icon']) : $id;
                $zoom = isset($fav['large-icon']) ? Helper::adminIcon($fav['large-icon'], false) : '';
                if ($zoom !== '') {
                    $icon .= ' <span class="zoom">' . $zoom . '</span>';
                }
                $title = $fav['title'] ?? $id;
                echo
                '<li id="fu-' . $id . '">' . '<label for="fuk-' . $id . '">' . $icon .
                form::number(['order[' . $id . ']'], [
                    'min'        => 1,
                    'max'        => count($user_fav),
                    'default'    => $count,
                    'class'      => 'position',
                    'extra_html' => 'title="' . sprintf(__('position of %s'), $title) . '"',
                ]) .
                form::hidden(['dynorder[]', 'dynorder-' . $id . ''], $id) .
                form::checkbox(['remove[]', 'fuk-' . $id], $id) . __($title) . '</label>' .
                '</li>';
            }
        }
        if ($count > 0) {
            echo
            '</ul>';
        }

        if ($count > 0) {
            echo
            '<div class="clear">' .
            '<p class="form-buttons">' . form::hidden('favs_order', '') .
            App::nonce()->getFormNonce() .
            '<input type="submit" name="saveorder" value="' . __('Save order') . '"> ' .

            '<input type="submit" class="delete" name="removeaction" ' .
            'value="' . __('Delete selected favorites') . '" ' .
            'onclick="return window.confirm(\'' . Html::escapeJS(
                __('Are you sure you want to remove selected favorites?')
            ) . '\');"></p>' .

            (App::auth()->isSuperAdmin() ?
                '<div class="info">' .
                '<p>' . __('If you are a super administrator, you may define this set of favorites to be used by default on all blogs of this installation.') . '</p>' .
                '<p><input class="reset action" type="submit" name="replace" value="' . __('Define as default favorites') . '">' . '</p>' .
                '</div>' :
                '') .

            '</div>';
        } else {
            echo
            '<p>' . __('Currently no personal favorites.') . '</p>';
        }

        $avail_fav       = App::backend()->favorites()->getFavorites(App::backend()->favorites()->getAvailableFavoritesIDs());
        $default_fav_ids = [];
        foreach (App::backend()->favorites()->getFavoriteIDs(true) as $v) {
            $default_fav_ids[$v] = true;
        }
        echo
        '</div>'; // /box my-fav

        echo '<div class="fieldset" id="available-favs">';
        // Available favorites
        echo
        '<h5 class="pretty-title">' . __('Other available favorites') . '</h5>';
        $count = 0;
        uasort($avail_fav, fn ($a, $b): int => strcoll(
            strtolower(Text::removeDiacritics($a['title'])),
            strtolower(Text::removeDiacritics($b['title']))
        ));

        foreach (array_keys($avail_fav) as $k) {
            if (in_array($k, $user_fav)) {
                unset($avail_fav[$k]);
            }
        }
        foreach ($avail_fav as $k => $fav) {
            if ($count === 0) {
                echo
                '<ul class="fav-list">';
            }

            $count++;
            $icon = Helper::adminIcon($fav['small-icon']);
            $zoom = Helper::adminIcon($fav['large-icon'], false);
            if ($zoom !== '') {
                $icon .= ' <span class="zoom">' . $zoom . '</span>';
            }
            echo
            '<li id="fa-' . $k . '">' . '<label for="fak-' . $k . '">' . $icon .
            form::checkbox(['append[]', 'fak-' . $k], $k) .
            $fav['title'] . '</label>' .
            (isset($default_fav_ids[$k]) ? ' <span class="default-fav"><img class="mark mark-selected" src="images/selected.svg" alt="' . __('(default favorite)') . '"></span>' : '') .
            '</li>';
        }
        if ($count > 0) {
            echo
            '</ul>';
        }

        echo
        '<p>' .
        App::nonce()->getFormNonce() .
        '<input type="submit" name="appendaction" value="' . __('Add to my favorites') . '"></p>' .
        '</div>' . // /available favorites

        '</form>' .

        // Dashboard items
        '<form action="' . App::backend()->url()->get('admin.user.preferences') . '" method="post" id="db-forms" class="two-boxes even">' .

        '<div class="fieldset">' .
        '<h4>' . __('Menu') . '</h4>' .
        '<p><label for="user_ui_nofavmenu" class="classic">' .
        form::checkbox('user_ui_nofavmenu', 1, !App::backend()->user_ui_nofavmenu) . ' ' .
        __('Display favorites at the top of the menu') . '</label></p></div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Dashboard icons') . '</h4>' .
        '<p><label for="user_dm_nofavicons" class="classic">' .
        form::checkbox('user_dm_nofavicons', 1, !App::backend()->user_dm_nofavicons) . ' ' .
        __('Display dashboard icons') . '</label></p>' .
        '</div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Dashboard modules') . '</h4>' .

        '<p><label for="user_dm_doclinks" class="classic">' .
        form::checkbox('user_dm_doclinks', 1, App::backend()->user_dm_doclinks) . ' ' .
        __('Display documentation links') . '</label></p>' .

        '<p><label for="user_dm_donate" class="classic">' .
        form::checkbox('user_dm_donate', 1, App::backend()->user_dm_donate) . ' ' .
        __('Display donate links') . '</label></p>' .

        '<p><label for="user_dm_dcnews" class="classic">' .
        form::checkbox('user_dm_dcnews', 1, App::backend()->user_dm_dcnews) . ' ' .
        __('Display Dotclear news') . '</label></p>' .

        '<p><label for="user_dm_quickentry" class="classic">' .
        form::checkbox('user_dm_quickentry', 1, App::backend()->user_dm_quickentry) . ' ' .
        __('Display quick entry form') . '</label></p>';

        if (App::auth()->isSuperAdmin()) {
            echo
            '<p><label for="user_dm_nodcupdate" class="classic">' .
            form::checkbox('user_dm_nodcupdate', 1, App::backend()->user_dm_nodcupdate) . ' ' .
            __('Do not display Dotclear updates') . '</label></p>';
        }

        echo
        '</div>';

        # --BEHAVIOR-- adminDashboardOptionsForm --
        App::behavior()->callBehavior('adminDashboardOptionsFormV2');

        echo
        '<p class="form-buttons">' .
        form::hidden('db-options', '-') .
        App::nonce()->getFormNonce() .
        '<input type="submit" accesskey="s" value="' . __('Save my dashboard options') . '">' .
        ' <input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js">' .
        '</p>' .
        '</form>' .

        // Dashboard items order (reset)
        '<form action="' . App::backend()->url()->get('admin.user.preferences') . '" method="post" id="order-reset" class="two-boxes even">' .
        '<div class="fieldset"><h4>' . __('Dashboard items order') . '</h4>' .
        '<p>' .
        App::nonce()->getFormNonce() .
        '<input type="submit" name="resetorder" value="' . __('Reset dashboard items order') . '"></p>' .
        '</div>' .
        '</form>' .
        '</div>'; // /multipart-user-favorites

        Page::helpBlock('core_user_pref');
        Page::close();
    }
}
