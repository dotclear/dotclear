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
use Dotclear\Helper\Html\Form\Button;
use Dotclear\Helper\Html\Form\Capture;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Img;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\None;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Number;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Table;
use Dotclear\Helper\Html\Form\Tbody;
use Dotclear\Helper\Html\Form\Td;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Th;
use Dotclear\Helper\Html\Form\Thead;
use Dotclear\Helper\Html\Form\Tr;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Form\Url;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Text as Txt;
use Exception;

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
        App::backend()->user_ui_stickymenu         = (bool) App::auth()->prefs()->interface->stickymenu;

        // Format by editors
        $formaters         = App::formater()->getFormaters();
        $format_by_editors = [];
        foreach ($formaters as $editor => $formats) {
            $label = __((string) App::plugins()->moduleInfo($editor, 'desc')) ?: __($editor);
            foreach ($formats as $format) {
                $format_by_editors[$format][$label] = $editor;
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
        App::backend()->status_combo      = App::status()->post()->combo();

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
                App::auth()->prefs()->interface->put('stickymenu', $_POST['user_ui_stickymenu'], 'boolean');

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
            Page::jsJson('userprefs', [
                'remove' => __('Are you sure you want to remove selected favorites?'),
            ]) .
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

        $pass_change = (new None());
        if (App::auth()->allowPassChange()) {
            $pass_change = (new Fieldset())
                ->legend(new Legend(__('Change my password')))
                ->items([
                    (new Para())
                        ->items([
                            (new Password('new_pwd'))
                                ->size(20)
                                ->maxlength(255)
                                ->class('pw-strength')
                                ->autocomplete('new-password')
                                ->translate(false)
                                ->label((new Label(__('New password:'), Label::OL_TF))),
                        ]),
                    (new Para())
                        ->items([
                            (new Password('new_pwd_c'))
                                ->size(20)
                                ->maxlength(255)
                                ->autocomplete('new-password')
                                ->translate(false)
                                ->label((new Label(__('Confirm new password:'), Label::OL_TF))),
                        ]),
                    (new Para())
                        ->items([
                            (new Password('cur_pwd'))
                                ->size(20)
                                ->maxlength(255)
                                ->autocomplete('current-password')
                                ->translate(false)
                                ->extra('aria-describedby="cur_pwd_help"')
                                ->label((new Label(__('Your current password:'), Label::OL_TF))),
                        ]),
                    (new Note('cur_pwd_help'))
                        ->class(['form-note', 'warn'])
                        ->text(__('If you have changed your email or password you must provide your current password to save these modifications.')),
                ]);
        }

        echo (new Div('user-profile'))
            ->class('multi-part')
            ->title(__('My profile'))
            ->items([
                (new Text('h3', __('My profile'))),
                (new Form('user-form'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.preferences'))
                    ->fields([
                        (new Para())
                            ->items([
                                (new Input('user_name'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_name))
                                    ->autocomplete('family-name')
                                    ->translate(false)
                                    ->label((new Label(__('Last Name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_firstname'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_firstname))
                                    ->autocomplete('given-name')
                                    ->translate(false)
                                    ->label((new Label(__('First Name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_displayname'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_displayname))
                                    ->autocomplete('nickname')
                                    ->translate(false)
                                    ->label((new Label(__('Display name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Email('user_email'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_email))
                                    ->autocomplete('email')
                                    ->translate(false)
                                    ->label((new Label(__('Email:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_profile_mails'))
                                    ->size(50)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_profile_mails))
                                    ->translate(false)
                                    ->label((new Label(__('Alternate emails (comma separated list):'), Label::OL_TF))),
                            ]),
                        (new Note('sanitize_emails'))
                            ->class(['form-note', 'info'])
                            ->text(__('Invalid emails will be automatically removed from list.')),
                        (new Para())
                            ->items([
                                (new Url('user_url'))
                                    ->size(30)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_url))
                                    ->autocomplete('url')
                                    ->translate(false)
                                    ->label((new Label(__('URL:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_profile_urls'))
                                    ->size(50)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(App::backend()->user_profile_urls))
                                    ->translate(false)
                                    ->label((new Label(__('Alternate URLs (comma separated list):'), Label::OL_TF))),
                            ]),
                        (new Note('sanitize_urls'))
                            ->class(['form-note', 'info'])
                            ->text(__('Invalid URLs will be automatically removed from list.')),
                        (new Para())
                            ->items([
                                (new Select('user_lang'))
                                    ->items(App::backend()->lang_combo)
                                    ->default(App::backend()->user_lang)
                                    ->translate(false)
                                    ->label((new Label(__('Language for my interface:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Select('user_tz'))
                                    ->items(Date::getZones(true, true))
                                    ->default(App::backend()->user_tz)
                                    ->translate(false)
                                    ->label((new Label(__('My timezone:'), Label::OL_TF))),
                            ]),

                        $pass_change,

                        (new Para())
                            ->class(['clear', 'form-buttons'])
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit('user-form-submit', __('Update my profile')))
                                    ->accesskey('s'),
                                (new Button('user-form-back', __('Back')))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js']),
                            ]),
                    ]),
            ])
        ->render();

        // User options : some from actual user profile, dashboard modules, ...

        $odd     = true;
        $columns = [];
        foreach (App::backend()->cols as $col_type => $col_list) {
            $fields = [];
            foreach ($col_list[1] as $col_name => $col_data) {
                $fields[] = (new Checkbox(['cols_' . $col_type . '[]', 'cols_' . $col_type . '-' . $col_name], $col_data[0]))
                    ->value($col_name)
                    ->label(new Label($col_data[1], Label::IL_FT));
            }
            $columns[] = (new Div())
                ->class(['two-boxes', $odd ? 'odd' : 'even'])
                ->items([
                    (new Text('h5', $col_list[0])),
                    ...$fields,
                ]);
            $odd = !$odd;
        }

        $sortingRows = function ($sorts) {
            foreach ($sorts as $sort_type => $sort_data) {
                yield (new Tr())
                    ->cols([
                        (new Td())
                            ->text($sort_data[0]),
                        (new Td())
                            ->items([
                                $sort_data[1] ?
                                    (new Select('sorts_' . $sort_type . '_sortby'))
                                        ->items($sort_data[1])
                                        ->default($sort_data[2]) :
                                    (new None()),
                            ]),
                        (new Td())
                            ->items([
                                $sort_data[3] ?
                                    (new Select('sorts_' . $sort_type . '_order'))
                                        ->items(App::backend()->order_combo)
                                        ->default($sort_data[3]) :
                                    (new None()),
                            ]),
                        (new Td())
                            ->items([
                                is_array($sort_data[4]) ?
                                    (new Number('sorts_' . $sort_type . '_nb', 0, 999, (int) $sort_data[4][1]))
                                        ->label(new Label($sort_data[4][0], Label::IL_FT)) :
                                    (new None()),
                            ]),
                    ]);
            }
        };
        $sorting = (new Table())
            ->class('table-outer')
            ->thead((new Thead())
                ->rows([
                    (new Tr())
                        ->cols([
                            (new Th())
                                ->text(__('List')),
                            (new Th())
                                ->text(__('Order by')),
                            (new Th())
                                ->text(__('Sort')),
                            (new Th())
                                ->text(__('Show')),
                        ]),
                ]))
            ->tbody((new Tbody())
                ->rows([
                    ... $sortingRows(App::backend()->sorts),
                ]));

        // List of choosen editor by syntax
        $editorsByFormat = function ($list) {
            foreach ($list as $format => $editors) {
                $label = sprintf(__('Preferred editor for %s:'), (new Strong(App::formater()->getFormaterName($format)))->render());
                yield (new Para())
                    ->class('field')
                    ->items([
                        (new Select(['user_editor[' . $format . ']', 'user_editor_' . $format]))
                            ->items(array_merge([__('Choose an editor') => ''], $editors))
                            ->default(App::backend()->user_options['editor'][$format])
                            ->label(new Label($label, Label::OL_TF)),
                    ]);
            }
        };

        // List of contexts (fields) where HTML editor should be use rather than pure text
        $editInHtml = function ($list) {
            foreach ($list as $rk => $rv) {
                yield (new Para())
                    ->items([
                        (new Checkbox(['rte_flags[]', 'rte_' . $rk], (bool) $rv[0]))
                            ->value($rk)
                            ->label(new Label($rv[1], Label::IL_FT)),
                    ]);
            }
        };

        echo (new Div('user-options'))
            ->class('multi-part')
            ->title(__('My options'))
            ->items([
                (new Form('opts-forms'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.preferences') . '#user-options')
                    ->fields([
                        (new Text('h3', __('My options'))),
                        (new Fieldset())
                            ->legend((new Legend(__('Interface'), 'user_options_interface')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Select('user_ui_theme'))
                                            ->items(App::backend()->theme_combo)
                                            ->default(App::backend()->user_ui_theme)
                                            ->label(new Label(__('Theme:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_enhanceduploader', App::backend()->user_ui_enhanceduploader))
                                            ->value(1)
                                            ->label(new Label(__('Activate enhanced uploader in media manager'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_blank_preview', App::backend()->user_ui_blank_preview))
                                            ->value(1)
                                            ->label(new Label(__('Preview the entry being edited in a blank window or tab (depending on your browser settings).'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_acc_nodragdrop', App::backend()->user_acc_nodragdrop))
                                            ->value(1)
                                            ->extra('aria-describedby="user_acc_nodragdrop_help"')
                                            ->label(new Label(__('Disable javascript powered drag and drop for ordering items'), Label::IL_FT)),
                                    ]),
                                (new Note('user_acc_nodragdrop_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('If checked, numeric fields will allow to type the elements\' ordering number.')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_hidemoreinfo', App::backend()->user_ui_hidemoreinfo))
                                            ->value(1)
                                            ->label(new Label(__('Hide all secondary information and notes'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_hidehelpbutton', App::backend()->user_ui_hidehelpbutton))
                                            ->value(1)
                                            ->label(new Label(__('Hide help button'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select('user_ui_htmlfontsize'))
                                            ->items(App::backend()->htmlfontsize_combo)
                                            ->default(App::backend()->user_ui_htmlfontsize)
                                            ->label(new Label(__('Font size:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_systemfont', App::backend()->user_ui_systemfont))
                                            ->value(1)
                                            ->label(new Label(__('Use operating system font'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('user_ui_media_nb_last_dirs', 0, 999, (int) App::backend()->user_ui_media_nb_last_dirs))
                                            ->extra('aria-describedby="user_ui_media_nb_last_dirs_help"')
                                            ->label(new Label(__('Number of recent folders proposed in media manager:'), Label::IL_TF)),
                                    ]),
                                (new Note('user_ui_media_nb_last_dirs_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Set to 0 (zero) to ignore, displayed only if Javascript is enabled in your browser.')),
                                App::auth()->isSuperAdmin() ?
                                    (new Para())
                                        ->items([
                                            (new Checkbox('user_ui_hide_std_favicon', App::backend()->user_ui_hide_std_favicon))
                                                ->value(1)
                                                ->extra('aria-describedby="user_ui_hide_std_favicon_help"')
                                                ->label((new Label(__('Do not use standard favicon'), Label::IL_FT))),
                                            (new Span(__('This will be applied for all users')))
                                                ->id('user_ui_hide_std_favicon_help')
                                                ->class(['form-note', 'warn']),
                                        ]) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_nocheckadblocker', App::backend()->user_ui_nocheckadblocker))
                                            ->value(1)
                                            ->extra('aria-describedby="user_ui_nocheckadblocker_help"')
                                            ->label(new Label(__('Disable Ad-blocker check'), Label::IL_FT)),
                                    ]),
                                (new Note('user_ui_nocheckadblocker_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Some ad-blockers (Ghostery, Adblock plus, uBloc origin, …) may interfere with some feature as inserting link or media in entries with CKEditor; in this case you should disable it for this Dotclear installation (backend only). Note that Dotclear do not add ads ot trackers in the backend.')),
                                (new Note('user_ui_nocheckadblocker_more'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Note also that deactivating this detection of ad blockers will not deactivate the installed ad blockers. Dotclear cannot interfere with the operation of browser extensions!')),
                                (new Para())
                                    ->items([
                                        (new Input('user_ui_quickmenuprefix'))
                                            ->size(1)
                                            ->maxlength(1)
                                            ->value(Html::escapeHTML(App::backend()->user_ui_quickmenuprefix))
                                            ->extra('aria-describedby="user_ui_quickmenuprefix_help')
                                            ->label(new Label(__('Quick menu character:'), Label::IL_TF)),
                                    ]),
                                (new Note('user_ui_quickmenuprefix_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Leave empty to use the default character <kbd>:</kbd>')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_stickymenu', App::backend()->user_ui_stickymenu))
                                            ->value(1)
                                            ->label(new Label(__('Keep the main menu at the top of the page as much as possible'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend((new Legend(__('Optional columns displayed in lists'), 'user_options_columns')))
                            ->fields($columns),
                        (new Fieldset('user_options_lists_container'))
                            ->legend((new Legend(__('Options for lists'), 'user_options_lists')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_auto_filter', App::backend()->auto_filter))
                                            ->value(1)
                                            ->label(new Label(__('Apply filters on the fly'), Label::IL_FT)),
                                        $sorting,
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend((new Legend(__('Edition'), 'user_options_edition')))
                            ->fields([
                                (new Div())
                                    ->class(['two-boxes', 'odd'])
                                    ->items([
                                        ... $editorsByFormat(App::backend()->format_by_editors),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Select('user_post_format'))
                                                    ->items(App::backend()->available_formats)
                                                    ->default(App::backend()->user_options['post_format'])
                                                    ->label(new Label(__('Preferred format:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Select('user_post_status'))
                                                    ->items(App::backend()->status_combo)
                                                    ->default(App::backend()->user_post_status)
                                                    ->label(new Label(__('Default entry status:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Number('user_edit_size', 10, 999, (int) App::backend()->user_options['edit_size']))
                                                    ->label(new Label(__('Entry edit field height:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Checkbox('user_wysiwyg', App::backend()->user_options['enable_wysiwyg']))
                                                    ->value(1)
                                                    ->label(new Label(__('Enable WYSIWYG mode'), Label::IL_FT)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Checkbox('user_toolbar_bottom', App::backend()->user_options['toolbar_bottom']))
                                                    ->value(1)
                                                    ->label(new Label(__('Display editor\'s toolbar at bottom of textarea (if possible)'), Label::IL_FT)),
                                            ]),
                                    ]),
                                (new Div())
                                    ->class(['two-boxes', 'even'])
                                    ->items([
                                        (new Text('h5', __('Use HTML editor for:'))),
                                        ... $editInHtml(App::backend()->rte),
                                    ]),
                            ]),
                        (new Text('h4', __('Other options')))
                            ->class('pretty-title'),
                        (new Capture(
                            # --BEHAVIOR-- adminPreferencesForm --
                            App::behavior()->callBehavior(...),
                            ['adminPreferencesFormV2']
                        )),
                        (new Para())
                            ->class(['clear', 'form-buttons'])
                            ->items([
                                App::nonce()->formNonce(),
                                (new Submit('user_options_submit', __('Save my options')))
                                    ->accesskey('s'),
                                (new Button('user_options_back', __('Back')))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js']),
                            ]),
                    ]),
            ])
        ->render();

        // My dashboard

        // Prepare user favorites

        $user_favorites_items = [];
        $count                = 0;
        $user_fav             = App::backend()->favorites()->getFavoriteIDs(false);
        foreach ($user_fav as $id) {
            if ($fav = App::backend()->favorites()->getFavorite($id)) {
                // User favorites only
                $count++;

                $icon = isset($fav['small-icon']) ? Helper::adminIcon($fav['small-icon']) : $id;
                $zoom = isset($fav['large-icon']) ? Helper::adminIcon($fav['large-icon'], false) : '';
                if ($zoom !== '') {
                    $icon .= ' ' . (new Span($zoom))->class('zoom')->render();
                }
                $title                  = $fav['title'] ?? $id;
                $user_favorites_items[] = (new Li('fu-' . $id))
                    ->items([
                        (new Number(['order[' . $id . ']'], 1, count($user_fav), $count))
                            ->class('position')
                            ->title(sprintf(__('position of %s'), $title)),
                        (new Hidden(['dynorder[]', 'dynorder-' . $id . ''], $id)),
                        (new Checkbox(['remove[]', 'fuk-' . $id]))
                            ->value($id)
                            ->label((new Label(__($title), Label::IL_FT))->prefix($icon)),
                    ]);
            }
        }

        if ($count > 0) {
            $user_favorites = (new Set())
                ->items([
                    (new Ul())
                        ->class('fav-list')
                        ->items($user_favorites_items),
                    (new Div())
                        ->class('clear')
                        ->items([
                            (new Para())
                                ->class('form-buttons')
                                ->items([
                                    App::nonce()->formNonce(),
                                    (new Hidden('favs_order', '')),
                                    (new Submit('saveorder', __('Save order'))),
                                    (new Submit('removeaction', __('Delete selected favorites'))),
                                ]),
                            App::auth()->isSuperAdmin() ?
                            (new Div())
                                ->class('info')
                                ->items([
                                    (new Note())
                                        ->text(__('If you are a super administrator, you may define this set of favorites to be used by default on all blogs of this installation.')),
                                    (new Para())
                                        ->items([
                                            (new Submit('replace', __('Define as default favorites')))
                                                ->class(['reset', 'action']),
                                        ]),
                                ]) :
                            (new None()),
                        ]),
                ]);
        } else {
            $user_favorites = (new Note())
                ->text(__('Currently no personal favorites.'));
        }

        // Prepare available favorites

        $avail_fav       = App::backend()->favorites()->getFavorites(App::backend()->favorites()->getAvailableFavoritesIDs());
        $default_fav_ids = [];
        foreach (App::backend()->favorites()->getFavoriteIDs(true) as $v) {
            $default_fav_ids[$v] = true;
        }
        $count = 0;
        uasort($avail_fav, fn ($a, $b): int => strcoll(
            strtolower(Txt::removeDiacritics((string) $a['title'])),
            strtolower(Txt::removeDiacritics((string) $b['title']))
        ));
        foreach (array_keys($avail_fav) as $k) {
            if (in_array($k, $user_fav)) {
                unset($avail_fav[$k]);
            }
        }
        $other_favorites_items = [];
        foreach ($avail_fav as $k => $fav) {
            $count++;
            $icon = Helper::adminIcon($fav['small-icon']);
            $zoom = Helper::adminIcon($fav['large-icon'], false);
            if ($zoom !== '') {
                $icon .= ' ' . (new Span($zoom))->class('zoom')->render();
            }
            $other_favorites_items[] = (new Li('fa-' . $k))
                ->items([
                    (new Checkbox(['append[]', 'fak-' . $k]))
                        ->value($k)
                        ->label((new Label($fav['title'] ?? $k, Label::IL_FT))->prefix($icon)),
                    isset($default_fav_ids[$k]) ?
                        (new Span())
                            ->class('default-fav')
                            ->items([
                                (new Img('images/selected.svg'))
                                    ->class(['mark', 'mark-selected'])
                                    ->alt(__('(default favorite)')),
                            ]) :
                        (new None()),
                ]);
        }
        if ($count > 0) {
            $other_favorites = (new Set())
                ->items([
                    (new Ul())
                        ->class('fav-list')
                        ->items($other_favorites_items),
                    (new Para())
                        ->class('form-buttons')
                        ->items([
                            App::nonce()->formNonce(),
                            (new Submit('appendaction', __('Add to my favorites'))),
                        ]),
                ]);
        } else {
            $other_favorites = (new None());
        }

        echo (new Div('user-favorites'))
            ->class('multi-part')
            ->title(__('My dashboard'))
            ->items([
                (new Text('h3', __('My dashboard'))),
                // Favorites
                (new Form('favs-form'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.preferences'))
                    ->class(['two-boxes', 'odd'])
                    ->fields([
                        (new Fieldset('my-favs'))
                            ->legend(new Legend(__('My favorites')))
                            ->fields([
                                $user_favorites,
                            ]),
                        (new Fieldset('available-favs'))
                            ->legend(new Legend(__('Other available favorites')))
                            ->fields([
                                $other_favorites,
                            ]),
                    ]),
                // Dashboard
                (new Form('db-forms'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.preferences'))
                    ->class(['two-boxes', 'even'])
                    ->fields([
                        (new Fieldset())
                            ->legend(new Legend(__('Menu')))
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_nofavmenu', !App::backend()->user_ui_nofavmenu))
                                            ->value(1)
                                            ->label(new Label(__('Display favorites at the top of the menu'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend(new Legend(__('Dashboard icons')))
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_nofavicons', !App::backend()->user_dm_nofavicons))
                                            ->value(1)
                                            ->label(new Label(__('Display dashboard icons'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend(new Legend(__('Dashboard modules')))
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_doclinks', App::backend()->user_dm_doclinks))
                                            ->value(1)
                                            ->label(new Label(__('Display documentation links'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_donate', App::backend()->user_dm_donate))
                                            ->value(1)
                                            ->label(new Label(__('Display donate links'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_dcnews', App::backend()->user_dm_dcnews))
                                            ->value(1)
                                            ->label(new Label(__('Display Dotclear news'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_quickentry', App::backend()->user_dm_quickentry))
                                            ->value(1)
                                            ->label(new Label(__('Display quick entry form'), Label::IL_FT)),
                                    ]),
                                App::auth()->isSuperAdmin() ?
                                    (new Checkbox('user_dm_nodcupdate', App::backend()->user_dm_nodcupdate))
                                            ->value(1)
                                            ->label(new Label(__('Do not display Dotclear updates'), Label::IL_FT)) :
                                    (new None()),
                            ]),
                        (new Capture(
                            # --BEHAVIOR-- adminDashboardOptionsForm --
                            App::behavior()->callBehavior(...),
                            ['adminDashboardOptionsFormV2']
                        )),
                        (new Para())
                            ->class('form-buttons')
                            ->items([
                                App::nonce()->formNonce(),
                                (new Hidden('db-options', '-')),
                                (new Submit('db-forms-submit', __('Save my dashboard options')))
                                    ->accesskey('s'),
                                (new Button('db-forms-back', __('Back')))
                                    ->class(['go-back', 'reset', 'hidden-if-no-js']),
                            ]),
                    ]),
                // Dashboard items order (reset)
                (new Form('order-reset'))
                    ->method('post')
                    ->action(App::backend()->url()->get('admin.user.preferences'))
                    ->class(['two-boxes', 'even'])
                    ->fields([
                        (new Fieldset())
                            ->legend(new Legend(__('Dashboard items order')))
                            ->fields([
                                App::nonce()->formNonce(),
                                (new Submit('resetorder', __('Reset dashboard items order'))),
                            ]),
                    ]),
            ])
        ->render();

        Page::helpBlock('core_user_pref');
        Page::close();
    }
}
