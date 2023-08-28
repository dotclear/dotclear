<?php
/**
 * @since 2.27 Before as admin/preferences.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use ArrayObject;
use dcAuth;
use dcCore;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Helper;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\UserPref;
use Dotclear\Core\Core;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text;
use Exception;
use form;

class UserPreferences extends Process
{
    public static function init(): bool
    {
        Page::check(dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_USAGE,
            dcAuth::PERMISSION_CONTENT_ADMIN,
        ]));

        Core::backend()->page_title = __('My preferences');

        Core::backend()->user_name        = dcCore::app()->auth->getInfo('user_name');
        Core::backend()->user_firstname   = dcCore::app()->auth->getInfo('user_firstname');
        Core::backend()->user_displayname = dcCore::app()->auth->getInfo('user_displayname');
        Core::backend()->user_email       = dcCore::app()->auth->getInfo('user_email');
        Core::backend()->user_url         = dcCore::app()->auth->getInfo('user_url');
        Core::backend()->user_lang        = dcCore::app()->auth->getInfo('user_lang');
        Core::backend()->user_tz          = dcCore::app()->auth->getInfo('user_tz');
        Core::backend()->user_post_status = dcCore::app()->auth->getInfo('user_post_status');

        $user_options = dcCore::app()->auth->getOptions();
        if (empty($user_options['editor']) || !is_array($user_options['editor'])) {
            $user_options['editor'] = [];
        }

        Core::backend()->user_profile_mails = dcCore::app()->auth->user_prefs->profile->mails;
        Core::backend()->user_profile_urls  = dcCore::app()->auth->user_prefs->profile->urls;

        Core::backend()->user_dm_doclinks   = dcCore::app()->auth->user_prefs->dashboard->doclinks;
        Core::backend()->user_dm_dcnews     = dcCore::app()->auth->user_prefs->dashboard->dcnews;
        Core::backend()->user_dm_quickentry = dcCore::app()->auth->user_prefs->dashboard->quickentry;
        Core::backend()->user_dm_nofavicons = dcCore::app()->auth->user_prefs->dashboard->nofavicons;
        Core::backend()->user_dm_nodcupdate = false;
        if (dcCore::app()->auth->isSuperAdmin()) {
            Core::backend()->user_dm_nodcupdate = dcCore::app()->auth->user_prefs->dashboard->nodcupdate;
        }

        Core::backend()->user_acc_nodragdrop = dcCore::app()->auth->user_prefs->accessibility->nodragdrop;

        Core::backend()->user_ui_theme            = dcCore::app()->auth->user_prefs->interface->theme;
        Core::backend()->user_ui_enhanceduploader = dcCore::app()->auth->user_prefs->interface->enhanceduploader;
        Core::backend()->user_ui_blank_preview    = dcCore::app()->auth->user_prefs->interface->blank_preview;
        Core::backend()->user_ui_hidemoreinfo     = dcCore::app()->auth->user_prefs->interface->hidemoreinfo;
        Core::backend()->user_ui_hidehelpbutton   = dcCore::app()->auth->user_prefs->interface->hidehelpbutton;
        Core::backend()->user_ui_showajaxloader   = dcCore::app()->auth->user_prefs->interface->showajaxloader;
        Core::backend()->user_ui_htmlfontsize     = dcCore::app()->auth->user_prefs->interface->htmlfontsize;
        Core::backend()->user_ui_systemfont       = dcCore::app()->auth->user_prefs->interface->systemfont;
        Core::backend()->user_ui_hide_std_favicon = false;
        if (dcCore::app()->auth->isSuperAdmin()) {
            Core::backend()->user_ui_hide_std_favicon = dcCore::app()->auth->user_prefs->interface->hide_std_favicon;
        }
        Core::backend()->user_ui_nofavmenu          = dcCore::app()->auth->user_prefs->interface->nofavmenu;
        Core::backend()->user_ui_media_nb_last_dirs = dcCore::app()->auth->user_prefs->interface->media_nb_last_dirs;
        Core::backend()->user_ui_nocheckadblocker   = dcCore::app()->auth->user_prefs->interface->nocheckadblocker;

        Core::backend()->default_tab = !empty($_GET['tab']) ? Html::escapeHTML($_GET['tab']) : 'user-profile';

        if (!empty($_GET['append']) || !empty($_GET['removed']) || !empty($_GET['neworder']) || !empty($_GET['replaced']) || !empty($_POST['appendaction']) || !empty($_POST['removeaction']) || !empty($_GET['db-updated']) || !empty($_POST['resetorder'])) {
            Core::backend()->default_tab = 'user-favorites';
        } elseif (!empty($_GET['updated'])) {
            Core::backend()->default_tab = 'user-options';
        }
        if ((Core::backend()->default_tab != 'user-profile') && (Core::backend()->default_tab != 'user-options') && (Core::backend()->default_tab != 'user-favorites')) {
            Core::backend()->default_tab = 'user-profile';
        }

        // Format by editors
        $formaters         = Core::formater()->getFormaters();
        $format_by_editors = [];
        foreach ($formaters as $editor => $formats) {
            foreach ($formats as $format) {
                $format_by_editors[$format][$editor] = $editor;
            }
        }
        $available_formats = ['' => ''];
        foreach (array_keys($format_by_editors) as $format) {
            $available_formats[Core::formater()->getFormaterName($format)] = $format;
            if (!isset($user_options['editor'][$format])) {
                $user_options['editor'][$format] = '';
            }
        }
        Core::backend()->user_options      = $user_options;
        Core::backend()->format_by_editors = $format_by_editors;
        Core::backend()->available_formats = $available_formats;
        Core::backend()->status_combo      = Combos::getPostStatusescombo();

        // Themes
        Core::backend()->theme_combo = [
            __('Light')     => 'light',
            __('Dark')      => 'dark',
            __('Automatic') => '',
        ];

        // Body base font size (37.5% = 6px, 50% = 8px, 62.5% = 10px, 75% = 12px, 87.5% = 14px)
        Core::backend()->htmlfontsize_combo = [
            __('Smallest') => '37.5%',
            __('Smaller')  => '50%',
            __('Default')  => '62.5%',
            __('Larger')   => '75%',
            __('Largest')  => '87.5%',
        ];
        // Ensure Font size is set to default is empty
        if (Core::backend()->user_ui_htmlfontsize == '') {
            Core::backend()->user_ui_htmlfontsize = '62.5%';
        }

        // Language codes
        Core::backend()->lang_combo = Combos::getAdminLangsCombo();

        // Get 3rd parts HTML editor flags
        $rte = [
            'blog_descr' => [true, __('Blog description (in blog parameters)')],
            'cat_descr'  => [true, __('Category description')],
        ];
        $rte = new ArrayObject($rte);
        # --BEHAVIOR-- adminRteFlagsV2 -- ArrayObject
        Core::behavior()->callBehavior('adminRteFlagsV2', $rte);
        // Load user settings
        $rte_flags = @dcCore::app()->auth->user_prefs->interface->rte_flags;
        if (is_array($rte_flags)) {
            foreach ($rte_flags as $fk => $fv) {
                if (isset($rte[$fk])) {
                    $rte[$fk][0] = $fv;
                }
            }
        }
        Core::backend()->rte = $rte;

        // Get default colums (admin lists)
        Core::backend()->cols = UserPref::getUserColumns();

        // Get default sortby, order, nbperpage (admin lists)
        Core::backend()->sorts = UserPref::getUserFilters();

        Core::backend()->order_combo = [
            __('Descending') => 'desc',
            __('Ascending')  => 'asc',
        ];
        // All filters
        Core::backend()->auto_filter = dcCore::app()->auth->user_prefs->interface->auto_filter;

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Update user

            try {
                $pwd_check = !empty($_POST['cur_pwd']) && dcCore::app()->auth->checkPassword($_POST['cur_pwd']);

                if (dcCore::app()->auth->allowPassChange() && !$pwd_check && Core::backend()->user_email != $_POST['user_email']) {
                    throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                }

                $cur = Core::con()->openCursor(Core::con()->prefix() . dcAuth::USER_TABLE_NAME);

                $cur->user_name        = Core::backend()->user_name = $_POST['user_name'];
                $cur->user_firstname   = Core::backend()->user_firstname = $_POST['user_firstname'];
                $cur->user_displayname = Core::backend()->user_displayname = $_POST['user_displayname'];
                $cur->user_email       = Core::backend()->user_email = $_POST['user_email'];
                $cur->user_url         = Core::backend()->user_url = $_POST['user_url'];
                $cur->user_lang        = Core::backend()->user_lang = $_POST['user_lang'];
                $cur->user_tz          = Core::backend()->user_tz = $_POST['user_tz'];

                $cur->user_options = new ArrayObject(Core::backend()->user_options);

                if (dcCore::app()->auth->allowPassChange() && !empty($_POST['new_pwd'])) {
                    if (!$pwd_check) {
                        throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                    }

                    if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                        throw new Exception(__("Passwords don't match"));
                    }

                    $cur->user_pwd = $_POST['new_pwd'];
                }

                # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                Core::behavior()->callBehavior('adminBeforeUserProfileUpdate', $cur, dcCore::app()->auth->userID());

                // Update user
                Core::users()->updUser(dcCore::app()->auth->userID(), $cur);

                // Update profile
                // Sanitize list of secondary mails and urls if any
                $mails = $urls = '';
                if (!empty($_POST['user_profile_mails'])) {
                    $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                }
                if (!empty($_POST['user_profile_urls'])) {
                    $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                }
                dcCore::app()->auth->user_prefs->profile->put('mails', $mails, 'string');
                dcCore::app()->auth->user_prefs->profile->put('urls', $urls, 'string');

                # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                Core::behavior()->callBehavior('adminAfterUserProfileUpdate', $cur, dcCore::app()->auth->userID());

                Notices::addSuccessNotice(__('Personal information has been successfully updated.'));

                Core::backend()->url->redirect('admin.user.preferences');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (isset($_POST['user_options_submit'])) {
            // Update user options

            try {
                // Prepare user options

                $user_options              = Core::backend()->user_options;
                $user_options['edit_size'] = (int) $_POST['user_edit_size'];
                if ($user_options['edit_size'] < 1) {
                    $user_options['edit_size'] = 10;
                }
                $user_options['post_format']    = $_POST['user_post_format'];
                $user_options['editor']         = $_POST['user_editor'];
                $user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
                $user_options['toolbar_bottom'] = !empty($_POST['user_toolbar_bottom']);

                Core::backend()->user_options = $user_options;

                $cur = Core::con()->openCursor(Core::con()->prefix() . dcAuth::USER_TABLE_NAME);

                $cur->user_name        = Core::backend()->user_name;
                $cur->user_firstname   = Core::backend()->user_firstname;
                $cur->user_displayname = Core::backend()->user_displayname;
                $cur->user_email       = Core::backend()->user_email;
                $cur->user_url         = Core::backend()->user_url;
                $cur->user_lang        = Core::backend()->user_lang;
                $cur->user_tz          = Core::backend()->user_tz;

                $cur->user_post_status = Core::backend()->user_post_status = $_POST['user_post_status'];

                $cur->user_options = new ArrayObject(Core::backend()->user_options);

                # --BEHAVIOR-- adminBeforeUserOptionsUpdate -- Cursor, string
                Core::behavior()->callBehavior('adminBeforeUserOptionsUpdate', $cur, dcCore::app()->auth->userID());

                // Update user prefs
                dcCore::app()->auth->user_prefs->accessibility->put('nodragdrop', !empty($_POST['user_acc_nodragdrop']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('theme', $_POST['user_ui_theme'], 'string');
                dcCore::app()->auth->user_prefs->interface->put('enhanceduploader', !empty($_POST['user_ui_enhanceduploader']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('blank_preview', !empty($_POST['user_ui_blank_preview']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('hidemoreinfo', !empty($_POST['user_ui_hidemoreinfo']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('hidehelpbutton', !empty($_POST['user_ui_hidehelpbutton']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('showajaxloader', !empty($_POST['user_ui_showajaxloader']), 'boolean');
                dcCore::app()->auth->user_prefs->interface->put('htmlfontsize', $_POST['user_ui_htmlfontsize'], 'string');
                dcCore::app()->auth->user_prefs->interface->put('systemfont', !empty($_POST['user_ui_systemfont']), 'boolean');
                if (dcCore::app()->auth->isSuperAdmin()) {
                    # Applied to all users
                    dcCore::app()->auth->user_prefs->interface->put('hide_std_favicon', !empty($_POST['user_ui_hide_std_favicon']), 'boolean', null, true, true);
                }
                dcCore::app()->auth->user_prefs->interface->put('media_nb_last_dirs', (int) $_POST['user_ui_media_nb_last_dirs'], 'integer');
                dcCore::app()->auth->user_prefs->interface->put('media_last_dirs', [], 'array', null, false);
                dcCore::app()->auth->user_prefs->interface->put('media_fav_dirs', [], 'array', null, false);
                dcCore::app()->auth->user_prefs->interface->put('nocheckadblocker', !empty($_POST['user_ui_nocheckadblocker']), 'boolean');

                // Update user columns (lists)
                $cu = [];
                foreach (Core::backend()->cols as $col_type => $cols_list) {
                    $ct = [];
                    foreach (array_keys($cols_list[1]) as $col_name) {
                        $ct[$col_name] = isset($_POST['cols_' . $col_type]) && in_array($col_name, $_POST['cols_' . $col_type], true) ? true : false;
                    }
                    if (count($ct)) {
                        if (isset($_POST['cols_' . $col_type])) {
                            // Sort resulting list
                            $order = array_values($_POST['cols_' . $col_type]);
                            $order = array_unique(array_merge($order, array_keys($ct)));
                            uksort($ct, fn ($key1, $key2) => array_search($key1, $order) <=> array_search($key2, $order));
                        }
                        $cu[$col_type] = $ct;
                    }
                }
                dcCore::app()->auth->user_prefs->interface->put('cols', $cu, 'array');

                // Update user lists options
                $su = [];
                foreach (Core::backend()->sorts as $sort_type => $sort_data) {
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

                // Update user HTML editor flags
                $rf = [];
                foreach (Core::backend()->rte as $rk => $rv) {
                    $rf[$rk] = isset($_POST['rte_flags']) && in_array($rk, $_POST['rte_flags'], true) ? true : false;
                }
                dcCore::app()->auth->user_prefs->interface->put('rte_flags', $rf, 'array');

                // Update user
                Core::users()->updUser(dcCore::app()->auth->userID(), $cur);

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- Cursor, string
                Core::behavior()->callBehavior('adminAfterUserOptionsUpdate', $cur, dcCore::app()->auth->userID());

                Notices::addSuccessNotice(__('Personal options has been successfully updated.'));
                Core::backend()->url->redirect('admin.user.preferences', [], '#user-options');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (isset($_POST['db-options'])) {
            // Dashboard options

            try {
                # --BEHAVIOR-- adminBeforeUserOptionsUpdate -- string
                Core::behavior()->callBehavior('adminBeforeDashboardOptionsUpdate', dcCore::app()->auth->userID());

                // Update user prefs
                dcCore::app()->auth->user_prefs->dashboard->put('doclinks', !empty($_POST['user_dm_doclinks']), 'boolean');
                dcCore::app()->auth->user_prefs->dashboard->put('dcnews', !empty($_POST['user_dm_dcnews']), 'boolean');
                dcCore::app()->auth->user_prefs->dashboard->put('quickentry', !empty($_POST['user_dm_quickentry']), 'boolean');
                dcCore::app()->auth->user_prefs->dashboard->put('nofavicons', empty($_POST['user_dm_nofavicons']), 'boolean');
                if (dcCore::app()->auth->isSuperAdmin()) {
                    dcCore::app()->auth->user_prefs->dashboard->put('nodcupdate', !empty($_POST['user_dm_nodcupdate']), 'boolean');
                }
                dcCore::app()->auth->user_prefs->interface->put('nofavmenu', empty($_POST['user_ui_nofavmenu']), 'boolean');

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- string
                Core::behavior()->callBehavior('adminAfterDashboardOptionsUpdate', dcCore::app()->auth->userID());

                Notices::addSuccessNotice(__('Dashboard options has been successfully updated.'));
                Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['appendaction'])) {
            // Add selected favorites

            try {
                if (empty($_POST['append'])) {
                    throw new Exception(__('No favorite selected'));
                }
                $user_favs = Core::backend()->favs->getFavoriteIDs(false);
                foreach ($_POST['append'] as $v) {
                    if (Core::backend()->favs->exists($v)) {
                        $user_favs[] = $v;
                    }
                }
                Core::backend()->favs->setFavoriteIDs($user_favs, false);

                if (!dcCore::app()->error->flag()) {
                    Notices::addSuccessNotice(__('Favorites have been successfully added.'));
                    Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        if (!empty($_POST['removeaction'])) {
            // Delete selected favorites

            try {
                if (empty($_POST['remove'])) {
                    throw new Exception(__('No favorite selected'));
                }
                $user_fav_ids = [];
                foreach (Core::backend()->favs->getFavoriteIDs(false) as $v) {
                    $user_fav_ids[$v] = true;
                }
                foreach ($_POST['remove'] as $v) {
                    if (isset($user_fav_ids[$v])) {
                        unset($user_fav_ids[$v]);
                    }
                }
                Core::backend()->favs->setFavoriteIDs(array_keys($user_fav_ids), false);
                if (!dcCore::app()->error->flag()) {
                    Notices::addSuccessNotice(__('Favorites have been successfully removed.'));
                    Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        // Prepare order favs (see below)

        if (empty($_POST['favs_order']) && !empty($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['favs_order'])) {
            $order = explode(',', $_POST['favs_order']);
        } else {
            $order = [];
        }

        if (!empty($_POST['saveorder']) && !empty($order)) {
            // Order favs

            foreach ($order as $k => $v) {
                if (!Core::backend()->favs->exists($v)) {
                    unset($order[$k]);
                }
            }
            Core::backend()->favs->setFavoriteIDs($order, false);
            if (!dcCore::app()->error->flag()) {
                Notices::addSuccessNotice(__('Favorites have been successfully updated.'));
                Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['replace']) && dcCore::app()->auth->isSuperAdmin()) {
            // Replace default favorites by current set (super admin only)

            $user_favs = Core::backend()->favs->getFavoriteIDs(false);
            Core::backend()->favs->setFavoriteIDs($user_favs, true);

            if (!dcCore::app()->error->flag()) {
                Notices::addSuccessNotice(__('Default favorites have been successfully updated.'));
                Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['resetorder'])) {
            // Reset dashboard items order

            dcCore::app()->auth->user_prefs->dashboard->drop('main_order');
            dcCore::app()->auth->user_prefs->dashboard->drop('boxes_order');
            dcCore::app()->auth->user_prefs->dashboard->drop('boxes_items_order');
            dcCore::app()->auth->user_prefs->dashboard->drop('boxes_contents_order');

            if (!dcCore::app()->error->flag()) {
                Notices::addSuccessNotice(__('Dashboard items order have been successfully reset.'));
                Core::backend()->url->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            Core::backend()->page_title,
            (Core::backend()->user_acc_nodragdrop ? '' : Page::jsLoad('js/_preferences-dragdrop.js')) .
            Page::jsLoad('js/jquery/jquery-ui.custom.js') .
            Page::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('js/pwstrength.js') .
            Page::jsLoad('js/_preferences.js') .
            Page::jsPageTabs(Core::backend()->default_tab) .
            Page::jsConfirmClose('user-form', 'opts-forms', 'favs-form', 'db-forms') .
            Page::jsAdsBlockCheck() .

            # --BEHAVIOR-- adminPreferencesHeaders --
            Core::behavior()->callBehavior('adminPreferencesHeaders'),
            Page::breadcrumb(
                [
                    Html::escapeHTML(dcCore::app()->auth->userID()) => '',
                    Core::backend()->page_title                => '',
                ]
            )
        );

        // User profile
        echo '<div class="multi-part" id="user-profile" title="' . __('My profile') . '">' .

        '<h3>' . __('My profile') . '</h3>' .
        '<form action="' . Core::backend()->url->get('admin.user.preferences') . '" method="post" id="user-form">' .

        '<p><label for="user_name">' . __('Last Name:') . '</label>' .
        form::field('user_name', 20, 255, [
            'default'      => Html::escapeHTML(Core::backend()->user_name),
            'autocomplete' => 'family-name',
        ]) .
        '</p>' .

        '<p><label for="user_firstname">' . __('First Name:') . '</label>' .
        form::field('user_firstname', 20, 255, [
            'default'      => Html::escapeHTML(Core::backend()->user_firstname),
            'autocomplete' => 'given-name',
        ]) .
        '</p>' .

        '<p><label for="user_displayname">' . __('Display name:') . '</label>' .
        form::field('user_displayname', 20, 255, [
            'default'      => Html::escapeHTML(Core::backend()->user_displayname),
            'autocomplete' => 'nickname',
        ]) .
        '</p>' .

        '<p><label for="user_email">' . __('Email:') . '</label>' .
        form::email('user_email', [
            'default'      => Html::escapeHTML(Core::backend()->user_email),
            'autocomplete' => 'email',
        ]) .
        '</p>' .

        '<p><label for="user_profile_mails">' . __('Alternate emails (comma separated list):') . '</label>' .
        form::field('user_profile_mails', 50, 255, [
            'default' => Html::escapeHTML(Core::backend()->user_profile_mails),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_emails">' . __('Invalid emails will be automatically removed from list.') . '</p>' .

        '<p><label for="user_url">' . __('URL:') . '</label>' .
        form::url('user_url', [
            'size'         => 30,
            'default'      => Html::escapeHTML(Core::backend()->user_url),
            'autocomplete' => 'url',
        ]) .
        '</p>' .

        '<p><label for="user_profile_urls">' . __('Alternate URLs (comma separated list):') . '</label>' .
        form::field('user_profile_urls', 50, 255, [
            'default' => Html::escapeHTML(Core::backend()->user_profile_urls),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_urls">' . __('Invalid URLs will be automatically removed from list.') . '</p>' .

        '<p><label for="user_lang">' . __('Language for my interface:') . '</label>' .
        form::combo('user_lang', Core::backend()->lang_combo, Core::backend()->user_lang, 'l10n') . '</p>' .

        '<p><label for="user_tz">' . __('My timezone:') . '</label>' .
        form::combo('user_tz', Date::getZones(true, true), Core::backend()->user_tz) . '</p>';

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
        '<p class="clear vertical-separator">' .
        Core::nonce()->getFormNonce() .
        '<input type="submit" accesskey="s" value="' . __('Update my profile') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        // User options : some from actual user profile, dashboard modules, ...

        '<div class="multi-part" id="user-options" title="' . __('My options') . '">' .

        '<form action="' . Core::backend()->url->get('admin.user.preferences') . '#user-options" method="post" id="opts-forms">' .
        '<h3>' . __('My options') . '</h3>' .

        '<div class="fieldset">' .
        '<h4 id="user_options_interface">' . __('Interface') . '</h4>' .

        '<p><label for="user_ui_theme" class="classic">' . __('Theme:') . '</label>' . ' ' .
        form::combo('user_ui_theme', Core::backend()->theme_combo, Core::backend()->user_ui_theme) . '</p>' .

        '<p><label for="user_ui_enhanceduploader" class="classic">' .
        form::checkbox('user_ui_enhanceduploader', 1, Core::backend()->user_ui_enhanceduploader) . ' ' .
        __('Activate enhanced uploader in media manager') . '</label></p>' .

        '<p><label for="user_ui_blank_preview" class="classic">' .
        form::checkbox('user_ui_blank_preview', 1, Core::backend()->user_ui_blank_preview) . ' ' .
        __('Preview the entry being edited in a blank window or tab (depending on your browser settings).') . '</label></p>' .

        '<p><label for="user_acc_nodragdrop" class="classic">' .
        form::checkbox('user_acc_nodragdrop', 1, Core::backend()->user_acc_nodragdrop, '', '', false, 'aria-describedby="user_acc_nodragdrop_help"') . ' ' .
        __('Disable javascript powered drag and drop for ordering items') . '</label></p>' .
        '<p class="clear form-note" id="user_acc_nodragdrop_help">' . __('If checked, numeric fields will allow to type the elements\' ordering number.') . '</p>' .

        '<p><label for="user_ui_hidemoreinfo" class="classic">' .
        form::checkbox('user_ui_hidemoreinfo', 1, Core::backend()->user_ui_hidemoreinfo) . ' ' .
        __('Hide all secondary information and notes') . '</label></p>' .

        '<p><label for="user_ui_hidehelpbutton" class="classic">' .
        form::checkbox('user_ui_hidehelpbutton', 1, Core::backend()->user_ui_hidehelpbutton) . ' ' .
        __('Hide help button') . '</label></p>' .

        '<p><label for="user_ui_showajaxloader" class="classic">' .
        form::checkbox('user_ui_showajaxloader', 1, Core::backend()->user_ui_showajaxloader) . ' ' .
        __('Show asynchronous requests indicator') . '</label></p>' .

        '<p><label for="user_ui_htmlfontsize" class="classic">' . __('Font size:') . '</label>' . ' ' .
        form::combo('user_ui_htmlfontsize', Core::backend()->htmlfontsize_combo, Core::backend()->user_ui_htmlfontsize) . '</p>' .

        '<p><label for="user_ui_systemfont" class="classic">' .
        form::checkbox('user_ui_systemfont', 1, Core::backend()->user_ui_systemfont) . ' ' .
        __('Use operating system font') . '</label></p>' .

        '<p><label for="user_ui_media_nb_last_dirs" class="classic">' . __('Number of recent folders proposed in media manager:') . '</label> ' .
        form::number('user_ui_media_nb_last_dirs', 0, 999, (string) Core::backend()->user_ui_media_nb_last_dirs, '', '', false, 'aria-describedby="user_ui_media_nb_last_dirs_help"') . '</p>' .
        '<p class="clear form-note" id="user_ui_media_nb_last_dirs_help">' . __('Leave empty to ignore, displayed only if Javascript is enabled in your browser.') . '</p>';

        if (dcCore::app()->auth->isSuperAdmin()) {
            echo
            '<p><label for="user_ui_hide_std_favicon" class="classic">' .
            form::checkbox('user_ui_hide_std_favicon', 1, Core::backend()->user_ui_hide_std_favicon, '', '', false, 'aria-describedby="user_ui_hide_std_favicon_help"') . ' ' .
            __('Do not use standard favicon') . '</label> ' .
            '<span class="clear form-note warn" id="user_ui_hide_std_favicon_help">' . __('This will be applied for all users') . '.</span>' .
            '</p>'; //Opera sucks;
        }

        echo
        '<p><label for="user_ui_nocheckadblocker" class="classic">' .
        form::checkbox('user_ui_nocheckadblocker', 1, Core::backend()->user_ui_nocheckadblocker, '', '', false, 'aria-describedby="user_ui_nocheckadblocker_help"') . ' ' .
        __('Disable Ad-blocker check') . '</label></p>' .
        '<p class="clear form-note" id="user_ui_nocheckadblocker_help">' . __('Some ad-blockers (Ghostery, Adblock plus, uBloc origin, …) may interfere with some feature as inserting link or media in entries with CKEditor; in this case you should disable it for this Dotclear installation (backend only). Note that Dotclear do not add ads ot trackers in the backend.') . '</p>' .

        '</div>' .

        '<fieldset id="user_options_columns">' .
        '<legend>' . __('Optional columns displayed in lists') . '</legend>';
        $odd = true;
        foreach (Core::backend()->cols as $col_type => $col_list) {
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

        '<div class="fieldset">' .
        '<h4 id="user_options_lists">' . __('Options for lists') . '</h4>' .
        '<p><label for="user_ui_auto_filter" class="classic">' .
        form::checkbox('user_ui_auto_filter', 1, Core::backend()->auto_filter) . ' ' .
        __('Apply filters on the fly') . '</label></p>';

        $odd = true;
        foreach (Core::backend()->sorts as $sort_type => $sort_data) {
            if ($odd) {
                echo
                '<hr />';
            }
            echo
            '<div class="two-boxes ' . ($odd ? 'odd' : 'even') . '">' .
            '<h5>' . $sort_data[0] . '</h5>';
            if (null !== $sort_data[1]) {
                echo
                '<p class="field"><label for="sorts_' . $sort_type . '_sortby">' . __('Order by:') . '</label> ' .
                form::combo('sorts_' . $sort_type . '_sortby', $sort_data[1], $sort_data[2]) . '</p>';
            }
            if (null !== $sort_data[3]) {
                echo
                '<p class="field"><label for="sorts_' . $sort_type . '_order">' . __('Sort:') . '</label> ' .
                form::combo('sorts_' . $sort_type . '_order', Core::backend()->order_combo, $sort_data[3]) . '</p>';
            }
            if (is_array($sort_data[4])) {
                echo
                '<p><span class="label ib">' . __('Show') . '</span> <label for="sorts_' . $sort_type . '_nb" class="classic">' .
                form::number('sorts_' . $sort_type . '_nb', 0, 999, (string) $sort_data[4][1]) . ' ' .
                $sort_data[4][0] . '</label></p>';
            }
            echo
            '</div>';
            $odd = !$odd;
        }
        echo
        '</div>' .

        '<div class="fieldset">' .
        '<h4 id="user_options_edition">' . __('Edition') . '</h4>' .

        '<div class="two-boxes odd">';
        foreach (Core::backend()->format_by_editors as $format => $editors) {
            echo
            '<p class="field"><label for="user_editor_' . $format . '">' . sprintf(__('Preferred editor for %s:'), Core::formater()->getFormaterName($format)) . '</label>' .
            form::combo(
                ['user_editor[' . $format . ']', 'user_editor_' . $format],
                array_merge([__('Choose an editor') => ''], $editors),
                Core::backend()->user_options['editor'][$format]
            ) .
            '</p>';
        }
        echo
        '<p class="field"><label for="user_post_format">' . __('Preferred format:') . '</label>' .
        form::combo('user_post_format', Core::backend()->available_formats, Core::backend()->user_options['post_format']) . '</p>' .

        '<p class="field"><label for="user_post_status">' . __('Default entry status:') . '</label>' .
        form::combo('user_post_status', Core::backend()->status_combo, Core::backend()->user_post_status) . '</p>' .

        '<p class="field"><label for="user_edit_size">' . __('Entry edit field height:') . '</label>' .
        form::number('user_edit_size', 10, 999, (string) Core::backend()->user_options['edit_size']) . '</p>' .

        '<p><label for="user_wysiwyg" class="classic">' .
        form::checkbox('user_wysiwyg', 1, Core::backend()->user_options['enable_wysiwyg']) . ' ' .
        __('Enable WYSIWYG mode') . '</label></p>' .

        '<p><label for="user_toolbar_bottom" class="classic">' .
        form::checkbox('user_toolbar_bottom', 1, Core::backend()->user_options['toolbar_bottom']) . ' ' .
        __('Display editor\'s toolbar at bottom of textarea (if possible)') . '</label></p>' .

        '</div>';

        echo
        '<div class="two-boxes even">' .
        '<h5>' . __('Use HTML editor for:') . '</h5>';
        foreach (Core::backend()->rte as $rk => $rv) {
            echo
            '<p><label for="rte_' . $rk . '" class="classic">' .
            form::checkbox(['rte_flags[]', 'rte_' . $rk], $rk, $rv[0]) . $rv[1] . '</label>';
        }
        echo
        '</div>' .
        '</div>' . // fieldset

        '<h4 class="pretty-title">' . __('Other options') . '</h4>';

        # --BEHAVIOR-- adminPreferencesForm --
        Core::behavior()->callBehavior('adminPreferencesFormV2');

        echo
        '<p class="clear vertical-separator">' .
        Core::nonce()->getFormNonce() .
        '<input type="submit" name="user_options_submit" accesskey="s" value="' . __('Save my options') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .
        '</div>' .

        // My dashboard
        '<div class="multi-part" id="user-favorites" title="' . __('My dashboard') . '">' .
        '<h3>' . __('My dashboard') . '</h3>' .

        // Favorites
        '<form action="' . Core::backend()->url->get('admin.user.preferences') . '" method="post" id="favs-form" class="two-boxes odd">' .
        '<div id="my-favs" class="fieldset"><h4>' . __('My favorites') . '</h4>';

        $count    = 0;
        $user_fav = Core::backend()->favs->getFavoriteIDs(false);
        foreach ($user_fav as $id) {
            if ($fav = Core::backend()->favs->getFavorite($id)) {
                // User favorites only
                if ($count == 0) {
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
                '<li id="fu-' . $id . '">' . '<label for="fuk-' . $id . '">' . $icon .
                form::number(['order[' . $id . ']'], [
                    'min'        => 1,
                    'max'        => is_countable($user_fav) ? count($user_fav) : 0, // @phpstan-ignore-line
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
            echo
            '</ul>';
        }

        if ($count > 0) {
            echo
            '<div class="clear">' .
            '<p>' . form::hidden('favs_order', '') .
            Core::nonce()->getFormNonce() .
            '<input type="submit" name="saveorder" value="' . __('Save order') . '" /> ' .

            '<input type="submit" class="delete" name="removeaction" ' .
            'value="' . __('Delete selected favorites') . '" ' .
            'onclick="return window.confirm(\'' . Html::escapeJS(
                __('Are you sure you want to remove selected favorites?')
            ) . '\');" /></p>' .

            (dcCore::app()->auth->isSuperAdmin() ?
                '<div class="info">' .
                '<p>' . __('If you are a super administrator, you may define this set of favorites to be used by default on all blogs of this installation.') . '</p>' .
                '<p><input class="reset action" type="submit" name="replace" value="' . __('Define as default favorites') . '" />' . '</p>' .
                '</div>' :
                '') .

            '</div>';
        } else {
            echo
            '<p>' . __('Currently no personal favorites.') . '</p>';
        }

        $avail_fav       = Core::backend()->favs->getFavorites(Core::backend()->favs->getAvailableFavoritesIDs());
        $default_fav_ids = [];
        foreach (Core::backend()->favs->getFavoriteIDs(true) as $v) {
            $default_fav_ids[$v] = true;
        }
        echo
        '</div>'; // /box my-fav

        echo '<div class="fieldset" id="available-favs">';
        // Available favorites
        echo
        '<h5 class="pretty-title">' . __('Other available favorites') . '</h5>';
        $count = 0;
        uasort($avail_fav, fn ($a, $b) => strcoll(
            strtolower(Text::removeDiacritics($a['title'])),
            strtolower(Text::removeDiacritics($b['title']))
        ));

        foreach ($avail_fav as $k => $v) {
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
            (isset($default_fav_ids[$k]) ? ' <span class="default-fav"><img src="images/selected.png" alt="' . __('(default favorite)') . '" /></span>' : '') .
            '</li>';
        }
        if ($count > 0) {
            echo
            '</ul>';
        }

        echo
        '<p>' .
        Core::nonce()->getFormNonce() .
        '<input type="submit" name="appendaction" value="' . __('Add to my favorites') . '" /></p>' .
        '</div>' . // /available favorites

        '</form>' .

        // Dashboard items
        '<form action="' . Core::backend()->url->get('admin.user.preferences') . '" method="post" id="db-forms" class="two-boxes even">' .

        '<div class="fieldset">' .
        '<h4>' . __('Menu') . '</h4>' .
        '<p><label for="user_ui_nofavmenu" class="classic">' .
        form::checkbox('user_ui_nofavmenu', 1, !Core::backend()->user_ui_nofavmenu) . ' ' .
        __('Display favorites at the top of the menu') . '</label></p></div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Dashboard icons') . '</h4>' .
        '<p><label for="user_dm_nofavicons" class="classic">' .
        form::checkbox('user_dm_nofavicons', 1, !Core::backend()->user_dm_nofavicons) . ' ' .
        __('Display dashboard icons') . '</label></p>' .
        '</div>' .

        '<div class="fieldset">' .
        '<h4>' . __('Dashboard modules') . '</h4>' .

        '<p><label for="user_dm_doclinks" class="classic">' .
        form::checkbox('user_dm_doclinks', 1, Core::backend()->user_dm_doclinks) . ' ' .
        __('Display documentation links') . '</label></p>' .

        '<p><label for="user_dm_dcnews" class="classic">' .
        form::checkbox('user_dm_dcnews', 1, Core::backend()->user_dm_dcnews) . ' ' .
        __('Display Dotclear news') . '</label></p>' .

        '<p><label for="user_dm_quickentry" class="classic">' .
        form::checkbox('user_dm_quickentry', 1, Core::backend()->user_dm_quickentry) . ' ' .
        __('Display quick entry form') . '</label></p>';

        if (dcCore::app()->auth->isSuperAdmin()) {
            echo
            '<p><label for="user_dm_nodcupdate" class="classic">' .
            form::checkbox('user_dm_nodcupdate', 1, Core::backend()->user_dm_nodcupdate) . ' ' .
            __('Do not display Dotclear updates') . '</label></p>';
        }

        echo
        '</div>';

        # --BEHAVIOR-- adminDashboardOptionsForm --
        Core::behavior()->callBehavior('adminDashboardOptionsFormV2');

        echo
        '<p>' .
        form::hidden('db-options', '-') .
        Core::nonce()->getFormNonce() .
        '<input type="submit" accesskey="s" value="' . __('Save my dashboard options') . '" />' .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        '</p>' .
        '</form>' .

        // Dashboard items order (reset)
        '<form action="' . Core::backend()->url->get('admin.user.preferences') . '" method="post" id="order-reset" class="two-boxes even">' .
        '<div class="fieldset"><h4>' . __('Dashboard items order') . '</h4>' .
        '<p>' .
        Core::nonce()->getFormNonce() .
        '<input type="submit" name="resetorder" value="' . __('Reset dashboard items order') . '" /></p>' .
        '</div>' .
        '</form>' .
        '</div>'; // /multipart-user-favorites

        Page::helpBlock('core_user_pref');
        Page::close();
    }
}
