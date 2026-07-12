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
use Dotclear\Core\Backend\Icon;
use Dotclear\Core\Backend\UserPrefFilter;
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
use Dotclear\Helper\Html\Form\Optgroup;
use Dotclear\Helper\Html\Form\Option;
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
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Text as Txt;
use Exception;

/**
 * @since 2.27 Before as admin/preferences.php
 */
class UserPreferences
{
    use TraitProcess;

    /**
     * Columns for various lists
     *
     * @var array<string, array{string, array<string, array{bool, string}>}> $cols
     */
    protected static array $cols;

    /**
     * User options (legacy field in user table, still store some options rather than in user preferences table)
     *
     * @var array{
     *      edit_size: int,
     *      post_format: string,
     *      editor: array<string, string>,
     *      enable_wysiwyg: bool,
     *      toolbar_bottom: bool,
     *      ...
     * }    $user_options
     */
    protected static array $user_options;

    /**
     * Filters (sort, order, nb) for various lists
     *
     * @var UserPrefFilter[] $filters
     */
    protected static array $filters;

    /**
     * Rich text editors for in some contexts
     *
     * [input value => [
     *     activated,
     *     label of context
     * ]]
     *
     * @var array<string, array{bool, string}> $rte
     */
    protected static array $rte;

    protected static string $user_name;
    protected static string $user_firstname;
    protected static string $user_displayname;
    protected static string $user_email;
    protected static string $user_url;
    protected static string $user_lang;
    protected static string $user_tz;
    protected static int $user_post_status;

    protected static string $user_profile_mails;
    protected static string $user_profile_urls;

    protected static bool $user_dm_doclinks;
    protected static bool $user_dm_donate;
    protected static bool $user_dm_dcnews;
    protected static bool $user_dm_quickentry;
    protected static bool $user_dm_denseboxes;
    protected static bool $user_dm_nofavicons;
    protected static bool $user_dm_densefavicons;
    protected static bool $user_dm_nodcupdate;

    protected static bool $user_acc_nodragdrop;

    protected static string $user_ui_theme;
    protected static bool $user_ui_enhanceduploader;
    protected static bool $user_ui_blank_preview;
    protected static bool $user_ui_hidemoreinfo;
    protected static bool $user_ui_hidehelpbutton;
    protected static string $user_ui_htmlfontsize;
    protected static bool $user_ui_dynamicletterspacing;
    protected static bool $user_ui_systemfont;
    protected static bool $user_ui_hide_std_favicon;
    protected static bool $user_ui_nofavmenu;
    protected static bool $user_ui_hidecollapserbtn;
    protected static int $user_ui_media_nb_last_dirs;
    protected static bool $user_ui_nocheckadblocker;
    protected static string $user_ui_quickmenuprefix;
    protected static bool $user_ui_stickymenu;

    protected static int $user_ui_edit_size;
    protected static string $user_ui_post_format;
    protected static bool $user_ui_enable_wysiwyg;
    protected static bool $user_ui_toolbar_bottom;

    /**
     * @var array<string, string> $user_ui_editor
     */
    protected static array $user_ui_editor;

    /**
     * @var array<string, array<string, string>> $format_by_editors
     */
    protected static array $format_by_editors;

    /**
     * @var array<string, string> $available_formats
     */
    protected static array $available_formats;

    protected static bool $auto_filter;

    public static function init(): bool
    {
        App::backend()->page()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_USAGE,
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]));

        // Variable data helpers
        $_Int = fn (mixed $var, int $default = 0): int => $var !== null && is_numeric($val = $var) ? (int) $val : $default;
        $_Str = fn (mixed $var, string $default = ''): string => $var !== null && is_string($val = $var) ? $val : $default;

        // Set oAuth2 redirect URL
        App::backend()->auth()->oauth2(App::config()->adminUrl() . App::backend()->url()->get('admin.user.preferences'));

        if (App::backend()->auth()->otp() !== false) {
            // Set otp user
            App::backend()->auth()->otp()->setUser((string) App::auth()->userID());
        }

        self::$user_name        = $_Str(App::auth()->getInfo('user_name'));
        self::$user_firstname   = $_Str(App::auth()->getInfo('user_firstname'));
        self::$user_displayname = $_Str(App::auth()->getInfo('user_displayname'));
        self::$user_email       = $_Str(App::auth()->getInfo('user_email'));
        self::$user_url         = $_Str(App::auth()->getInfo('user_url'));
        self::$user_lang        = $_Str(App::auth()->getInfo('user_lang'));
        self::$user_tz          = $_Str(App::auth()->getInfo('user_tz'));
        self::$user_post_status = $_Int(App::auth()->getInfo('user_post_status'));

        self::$user_profile_mails = App::auth()->prefs()->get('profile')->getStr('mails', false);
        self::$user_profile_urls  = App::auth()->prefs()->get('profile')->getStr('urls', false);

        self::$user_dm_doclinks      = App::auth()->prefs()->get('dashboard')->getBool('doclinks', false);
        self::$user_dm_donate        = App::auth()->prefs()->get('dashboard')->getBool('donate', false);
        self::$user_dm_dcnews        = App::auth()->prefs()->get('dashboard')->getBool('dcnews', false);
        self::$user_dm_quickentry    = App::auth()->prefs()->get('dashboard')->getBool('quickentry', false);
        self::$user_dm_denseboxes    = App::auth()->prefs()->get('dashboard')->getBool('denseboxes', false);
        self::$user_dm_nofavicons    = App::auth()->prefs()->get('dashboard')->getBool('nofavicons', false);
        self::$user_dm_densefavicons = App::auth()->prefs()->get('dashboard')->getBool('densefavicons', false);
        self::$user_dm_nodcupdate    = App::auth()->isSuperAdmin() && App::auth()->prefs()->get('dashboard')->getBool('nodcupdate', false);

        self::$user_acc_nodragdrop = App::auth()->prefs()->get('accessibility')->getBool('nodragdrop', false);

        self::$user_ui_theme                = App::auth()->prefs()->get('interface')->getStr('theme', false);
        self::$user_ui_enhanceduploader     = App::auth()->prefs()->get('interface')->getBool('enhanceduploader', false);
        self::$user_ui_blank_preview        = App::auth()->prefs()->get('interface')->getBool('blank_preview', false);
        self::$user_ui_hidemoreinfo         = App::auth()->prefs()->get('interface')->getBool('hidemoreinfo', false);
        self::$user_ui_hidehelpbutton       = App::auth()->prefs()->get('interface')->getBool('hidehelpbutton', false);
        self::$user_ui_htmlfontsize         = App::auth()->prefs()->get('interface')->getStr('htmlfontsize', false);
        self::$user_ui_dynamicletterspacing = App::auth()->prefs()->get('interface')->getBool('dynamicletterspacing', false);
        self::$user_ui_systemfont           = App::auth()->prefs()->get('interface')->getBool('systemfont', false);
        self::$user_ui_hide_std_favicon     = App::auth()->isSuperAdmin() && App::auth()->prefs()->get('interface')->getBool('hide_std_favicon', false);
        self::$user_ui_nofavmenu            = App::auth()->prefs()->get('interface')->getBool('nofavmenu', false);
        self::$user_ui_hidecollapserbtn     = App::auth()->prefs()->get('interface')->getBool('hide_collapser_btn', false);
        self::$user_ui_media_nb_last_dirs   = App::auth()->prefs()->get('interface')->getInt('media_nb_last_dirs', false);
        self::$user_ui_nocheckadblocker     = App::auth()->prefs()->get('interface')->getBool('nocheckadblocker', false);
        self::$user_ui_quickmenuprefix      = App::auth()->prefs()->get('interface')->getStr('quickmenuprefix', false);
        self::$user_ui_stickymenu           = App::auth()->prefs()->get('interface')->getBool('stickymenu', false);
        self::$user_ui_edit_size            = App::auth()->prefs()->get('interface')->getInt('edit_size', false);
        self::$user_ui_post_format          = App::auth()->prefs()->get('interface')->getStr('post_format', false);
        self::$user_ui_enable_wysiwyg       = App::auth()->prefs()->get('interface')->getBool('enable_wysiwyg', false);
        self::$user_ui_toolbar_bottom       = App::auth()->prefs()->get('interface')->getBool('toolbar_bottom', false);

        $list   = [];
        $editor = is_array($editor = App::auth()->prefs()->get('interface')->get('editor')) ? $editor : [];
        foreach ($editor as $format => $data) {
            if (is_string($format) && is_string($data)) {
                $list[$format] = $data;
            }
        }
        self::$user_ui_editor = $list;

        // Format by editors
        $formaters         = App::formater()->getFormaters();
        $format_by_editors = [];
        foreach ($formaters as $editor => $formats) {
            $label = is_string($label = App::plugins()->moduleInfo($editor, 'desc')) ? $label : '';
            if ($label === '') {
                $label = __($editor);
            }

            foreach ($formats as $format) {
                $format_by_editors[$format][$label] = $editor;
            }
        }

        $user_options = App::auth()->getOptions();

        $available_formats = ['' => ''];
        foreach (array_keys($format_by_editors) as $format) {
            $available_formats[App::formater()->getFormaterName($format)] = $format;
            if (!isset($user_options['editor'][$format])) {
                // Legacy storage
                $user_options['editor'][$format] = '';
            }
            if (!isset(self::$user_ui_editor[$format])) {
                self::$user_ui_editor[$format] = '';
            }
        }

        self::$user_options      = $user_options;
        self::$format_by_editors = $format_by_editors;
        self::$available_formats = $available_formats;

        // Ensure Font size is set to default is empty
        if (self::$user_ui_htmlfontsize === '') {
            self::$user_ui_htmlfontsize = '62.5%';
        }

        // Get 3rd parts HTML editor flags
        $rte = [
            'blog_descr' => [true, __('Blog description (in blog parameters)')],
            'cat_descr'  => [true, __('Category description')],
        ];
        $rte = new ArrayObject($rte);

        # --BEHAVIOR-- adminRteFlagsV2 -- ArrayObject
        App::behavior()->callBehavior('adminRteFlagsV2', $rte);

        // Load user settings
        $rte_flags = @App::auth()->prefs()->get('interface')->get('rte_flags');
        if (is_array($rte_flags)) {
            foreach ($rte_flags as $fk => $fv) {
                if (isset($rte[$fk])) {
                    $rte[$fk][0] = $fv;
                }
            }
        }
        self::$rte = $rte->getArrayCopy();

        // Get default colums (admin lists)
        self::$cols = App::backend()->userPref()->getAllUserColumns();

        // Get default sortby, order, nbperpage (admin lists)
        self::$filters = App::backend()->userPref()->getUserFilters();

        // All filters
        self::$auto_filter = App::auth()->prefs()->get('interface')->getBool('auto_filter', false);

        // Specific tab
        App::backend()->tab = isset($_REQUEST['tab']) && is_string($tab = $_REQUEST['tab']) ? $tab : '';

        return self::status(true);
    }

    public static function process(): bool
    {
        // Post data helpers
        $_Bool = fn (string $name): bool => !empty($_POST[$name]);
        $_Int  = fn (string $name, int $default = 0): int => isset($_POST[$name]) && is_numeric($val = $_POST[$name]) ? (int) $val : $default;
        $_Str  = fn (string $name, string $default = ''): string => isset($_POST[$name]) && is_string($val = $_POST[$name]) ? $val : $default;

        // otp action
        if (App::backend()->auth()->otp() !== false) {
            $otp_code = $_Str('otp_verify_code');
            if (!empty($_POST['otp_verify_submit']) && $otp_code !== '') {
                // verify code
                if (!App::backend()->auth()->otp()->verifyCode($otp_code)) {
                    App::error()->add(__('Two factors authentication verification failed.'));
                } else {
                    App::backend()->notices()->addSuccessNotice(__('Two factors authentication verification succeeded.'));
                }
            }

            if (!empty($_POST['otp_delete']) || !empty($_POST['otp_regenerate'])) {
                // delete credential
                App::backend()->auth()->otp()->delCredential();
                App::backend()->auth()->otp()->setUser((string) App::auth()->userID()); // reload info

                App::backend()->notices()->addSuccessNotice(__('Two factors authentication secret regenerated.'));
            }
        }

        // webauthn action
        if (App::backend()->auth()->webauthn() !== false && !empty($_POST['webauthn']) && is_array($_POST['webauthn'])) {
            // process webauhtn key deletion
            $webauthn = is_string($webauthn = key($_POST['webauthn'])) ? $webauthn : '';
            App::backend()->auth()->webauthn()->store()->delCredential(base64_decode($webauthn));

            App::backend()->notices()->addSuccessNotice(__('Passkey successfully deleted.'));
            App::backend()->url()->redirect('admin.user.preferences', [], '#user-profile');
        }

        // oauth2 action
        if (App::backend()->auth()->oauth2() !== false) {
            // process oAuth2 client action
            App::backend()->auth()->oauth2()->requestAction((string) App::auth()->userID());
        }

        if (isset($_POST['user_name']) && isset($_POST['user-form-submit'])) {
            // Update user

            try {
                $cur_pwd = $_Str('cur_pwd');

                $pwd_check = $cur_pwd !== '' && App::auth()->checkPassword($cur_pwd);

                if (App::auth()->allowPassChange() && !$pwd_check && self::$user_email !== $_Str('user_email')) {
                    throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                }

                $cur = App::auth()->openUserCursor();
                $cur->user_name = $_Str('user_name');
                self::$user_name = $cur->user_name;
                $cur->user_firstname = $_Str('user_firstname');
                self::$user_firstname = $cur->user_firstname;
                $cur->user_displayname = $_Str('user_displayname');
                self::$user_displayname = $cur->user_displayname;
                $cur->user_email = $_Str('user_email');
                self::$user_email = $cur->user_email;
                $cur->user_url = $_Str('user_url');
                self::$user_url = $cur->user_url;
                $cur->user_lang = $_Str('user_lang');
                self::$user_lang = $cur->user_lang;
                $cur->user_tz = $_Str('user_tz');
                self::$user_tz = $cur->user_tz;

                $cur->user_options = new ArrayObject(self::$user_options);

                if (App::auth()->allowPassChange() && !empty($_POST['new_pwd'])) {
                    if (!$pwd_check) {
                        throw new Exception(__('If you want to change your email or password you must provide your current password.'));
                    }

                    $new_pwd   = $_Str('new_pwd');
                    $new_pwd_c = $_Str('new_pwd_c');
                    if ($new_pwd !== $new_pwd_c) {
                        throw new Exception(__("Passwords don't match"));
                    }

                    $cur->user_pwd = $new_pwd;
                }

                # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                App::behavior()->callBehavior('adminBeforeUserProfileUpdate', $cur, App::auth()->userID());

                // Update user
                App::users()->updUser((string) App::auth()->userID(), $cur);

                // Update profile
                // Sanitize list of secondary mails and urls if any
                $mails              = '';
                $urls               = '';
                $user_profile_mails = $_Str('user_profile_mails');
                if ($user_profile_mails !== '') {
                    $mails = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $user_profile_mails)), FILTER_VALIDATE_EMAIL)));
                }
                $user_profile_urls = $_Str('user_profile_urls');
                if ($user_profile_urls !== '') {
                    $urls = implode(',', array_filter(filter_var_array(array_map(trim(...), explode(',', $user_profile_urls)), FILTER_VALIDATE_URL)));
                }
                App::auth()->prefs()->get('profile')->put('mails', $mails, App::userWorkspace()::WS_STRING);
                App::auth()->prefs()->get('profile')->put('urls', $urls, App::userWorkspace()::WS_STRING);

                # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterUserProfileUpdate', $cur, App::auth()->userID());

                App::backend()->notices()->addSuccessNotice(__('Personal information has been successfully updated.'));

                App::backend()->url()->redirect('admin.user.preferences');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (isset($_POST['user_options_submit'])) {
            // Update user options

            try {
                // Prepare user options

                $editors = [];
                if (isset($_POST['user_editor']) && is_array($_POST['user_editor'])) {
                    foreach ($_POST['user_editor'] as $key => $value) {
                        if (is_string($key) && is_string($value)) {
                            $editors[$key] = $value;
                        }
                    }
                }

                /**
                 * @var array{
                 *      edit_size: int,
                 *      post_format: string,
                 *      editor: array<string, string>,
                 *      enable_wysiwyg: bool,
                 *      toolbar_bottom: bool,
                 *      ...
                 * }    $user_options
                 */
                $user_options = self::$user_options;

                $user_options['edit_size']      = $_Int('user_edit_size');
                $user_options['post_format']    = $_Str('user_post_format');
                $user_options['editor']         = $editors;
                $user_options['enable_wysiwyg'] = $_Bool('user_wysiwyg');
                $user_options['toolbar_bottom'] = $_Bool('user_toolbar_bottom');

                self::$user_options = $user_options;

                $cur = App::auth()->openUserCursor();

                $cur->user_name        = self::$user_name;
                $cur->user_firstname   = self::$user_firstname;
                $cur->user_displayname = self::$user_displayname;
                $cur->user_email       = self::$user_email;
                $cur->user_url         = self::$user_url;
                $cur->user_lang        = self::$user_lang;
                $cur->user_tz          = self::$user_tz;
                $cur->user_post_status = $_Int('user_post_status');
                self::$user_post_status = $cur->user_post_status;

                $cur->user_options = new ArrayObject(self::$user_options);

                # --BEHAVIOR-- adminBeforeUserOptionsUpdate -- Cursor, null|string
                App::behavior()->callBehavior('adminBeforeUserOptionsUpdate', $cur, App::auth()->userID());

                // Update user prefs
                App::auth()->prefs()->get('accessibility')->put('nodragdrop', $_Bool('user_acc_nodragdrop'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('theme', $_Str('user_ui_theme'), App::userWorkspace()::WS_STRING);
                App::auth()->prefs()->get('interface')->put('enhanceduploader', $_Bool('user_ui_enhanceduploader'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('blank_preview', $_Bool('user_ui_blank_preview'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('hidemoreinfo', $_Bool('user_ui_hidemoreinfo'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('hidehelpbutton', $_Bool('user_ui_hidehelpbutton'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('htmlfontsize', $_Str('user_ui_htmlfontsize'), App::userWorkspace()::WS_STRING);
                App::auth()->prefs()->get('interface')->put('dynamicletterspacing', $_Bool('user_ui_dynamicletterspacing'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('systemfont', $_Bool('user_ui_systemfont'), App::userWorkspace()::WS_BOOL);
                if (App::auth()->isSuperAdmin()) {
                    # Applied to all users
                    App::auth()->prefs()->get('interface')->put('hide_std_favicon', $_Bool('user_ui_hide_std_favicon'), App::userWorkspace()::WS_BOOL, null, true, true);
                }
                App::auth()->prefs()->get('interface')->put('media_nb_last_dirs', $_Int('user_ui_media_nb_last_dirs'), App::userWorkspace()::WS_INT);
                App::auth()->prefs()->get('interface')->put('media_last_dirs', [], App::userWorkspace()::WS_ARRAY, null, false);
                App::auth()->prefs()->get('interface')->put('media_fav_dirs', [], App::userWorkspace()::WS_ARRAY, null, false);
                App::auth()->prefs()->get('interface')->put('nocheckadblocker', $_Bool('user_ui_nocheckadblocker'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('quickmenuprefix', $_Str('user_ui_quickmenuprefix'), App::userWorkspace()::WS_STRING);
                App::auth()->prefs()->get('interface')->put('stickymenu', $_Bool('user_ui_stickymenu'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('interface')->put('hide_collapser_btn', $_Bool('user_ui_hidecollapserbtn'), App::userWorkspace()::WS_BOOL);

                App::auth()->prefs()->get('interface')->put('edit_size', $_Int('user_edit_size'), App::userWorkspace()::WS_INT);
                App::auth()->prefs()->get('interface')->put('post_format', $_Str('user_post_format'), App::userWorkspace()::WS_STRING);
                App::auth()->prefs()->get('interface')->put('editor', $editors, App::userWorkspace()::WS_ARRAY);
                App::auth()->prefs()->get('interface')->put('enable_wysiwyg', $_Bool('user_wysiwyg'), App::userWorkspace()::WS_BOOL);

                // Update user columns (lists)

                /**
                 * @var array<string, array<string, bool>>
                 */
                $cu = [];
                foreach (self::$cols as $col_type => $cols_list) {
                    /**
                     * @var array<string, bool>
                     */
                    $ct = [];
                    foreach (array_keys($cols_list[1]) as $col_name) {
                        $ct[$col_name] = isset($_POST['cols_' . $col_type])
                            && is_array($_POST['cols_' . $col_type])
                            && in_array($col_name, $_POST['cols_' . $col_type], true);
                    }

                    if ($ct !== []) {
                        $order = isset($_POST['cols_' . $col_type . '_idx']) && is_array($order = $_POST['cols_' . $col_type . '_idx']) ? $order : [];
                        if ($order !== []) {
                            // Sort resulting list
                            $order = $_POST['cols_' . $col_type . '_idx'];
                            uksort($ct, fn (string $key1, string $key2): int => array_search($key1, $order, true) <=> array_search($key2, $order, true));
                        }

                        $cu[$col_type] = $ct;
                    }
                }

                App::auth()->prefs()->get('interface')->put('cols', $cu, App::userWorkspace()::WS_ARRAY);

                // Update user lists options
                $su = [];
                foreach (self::$filters as $filter) {
                    $type = $filter->getType();

                    $sortby = null;
                    if ($filter->getSortBy() !== null) {
                        $key    = 'sorts_' . $type . '_sortby';
                        $sortby = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : $filter->getSortBy();
                    }

                    $order = null;
                    if ($filter->getOrder() !== null) {
                        $key   = 'sorts_' . $type . '_order';
                        $order = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : $filter->getOrder();
                    }

                    $nb = null;
                    if ($filter->getNb() !== null) {
                        $key = 'sorts_' . $type . '_nb';
                        $nb  = isset($_POST[$key]) && is_numeric($_POST[$key]) ? (int) $_POST[$key] : $filter->getNb();
                    }

                    $su[$type] = [
                        $sortby,
                        $order,
                        $nb,
                    ];
                }

                App::auth()->prefs()->get('interface')->put('sorts', $su, App::userWorkspace()::WS_ARRAY);

                // All filters
                App::auth()->prefs()->get('interface')->put('auto_filter', $_Bool('user_ui_auto_filter'), App::userWorkspace()::WS_BOOL);

                // Update user HTML editor flags
                $rf           = [];
                $rte_contexts = array_keys(self::$rte);
                foreach ($rte_contexts as $context) {
                    $rf[$context] = isset($_POST['rte_flags']) && is_array($_POST['rte_flags']) && in_array($context, $_POST['rte_flags'], true);
                }
                App::auth()->prefs()->get('interface')->put('rte_flags', $rf, App::userWorkspace()::WS_ARRAY);

                // Update user
                App::users()->updUser((string) App::auth()->userID(), $cur);

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- Cursor, string
                App::behavior()->callBehavior('adminAfterUserOptionsUpdate', $cur, App::auth()->userID());

                App::backend()->notices()->addSuccessNotice(__('Personal options has been successfully updated.'));
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
                App::auth()->prefs()->get('dashboard')->put('doclinks', $_Bool('user_dm_doclinks'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('donate', $_Bool('user_dm_donate'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('dcnews', $_Bool('user_dm_dcnews'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('quickentry', $_Bool('user_dm_quickentry'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('denseboxes', $_Bool('user_dm_denseboxes'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('nofavicons', !$_Bool('user_dm_nofavicons'), App::userWorkspace()::WS_BOOL);
                App::auth()->prefs()->get('dashboard')->put('densefavicons', $_Bool('user_dm_densefavicons'), App::userWorkspace()::WS_BOOL);
                if (App::auth()->isSuperAdmin()) {
                    App::auth()->prefs()->get('dashboard')->put('nodcupdate', $_Bool('user_dm_nodcupdate'), App::userWorkspace()::WS_BOOL);
                }
                App::auth()->prefs()->get('interface')->put('nofavmenu', !$_Bool('user_ui_nofavmenu'), App::userWorkspace()::WS_BOOL);

                # --BEHAVIOR-- adminAfterUserOptionsUpdate -- string
                App::behavior()->callBehavior('adminAfterDashboardOptionsUpdate', App::auth()->userID());

                App::backend()->notices()->addSuccessNotice(__('Dashboard options has been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['appendaction'])) {
            // Add selected favorites

            try {
                if (empty($_POST['append']) || !is_array($_POST['append'])) {
                    throw new Exception(__('No favorite selected'));
                }

                $user_favs = App::backend()->favorites()->getFavoriteIDs(false);
                if (is_iterable($_POST['append'])) {
                    foreach ($_POST['append'] as $favorite_id) {
                        if (is_string($favorite_id) && App::backend()->favorites()->exists($favorite_id)) {
                            $user_favs[] = $favorite_id;
                        }
                    }
                }

                App::backend()->favorites()->setFavoriteIDs($user_favs, false);

                if (!App::error()->flag()) {
                    App::backend()->notices()->addSuccessNotice(__('Favorites have been successfully added.'));
                    App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        if (!empty($_POST['removeaction'])) {
            // Delete selected favorites

            try {
                if (empty($_POST['remove']) || !is_array($_POST['remove'])) {
                    throw new Exception(__('No favorite selected'));
                }

                $user_fav_ids = [];
                foreach (App::backend()->favorites()->getFavoriteIDs(false) as $favorite_id) {
                    $user_fav_ids[$favorite_id] = true;
                }

                if (is_iterable($_POST['remove'])) {
                    foreach ($_POST['remove'] as $favorite_id) {
                        if (is_string($favorite_id) && isset($user_fav_ids[$favorite_id])) {
                            unset($user_fav_ids[$favorite_id]);
                        }
                    }
                }
                App::backend()->favorites()->setFavoriteIDs(array_keys($user_fav_ids), false);
                if (!App::error()->flag()) {
                    App::backend()->notices()->addSuccessNotice(__('Favorites have been successfully removed.'));
                    App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // Prepare order favs (see below)

        if (empty($_POST['favs_order']) && !empty($_POST['order']) && is_array($_POST['order'])) {
            $order = $_POST['order'];
            asort($order);
            $order = array_keys($order);
        } elseif (!empty($_POST['favs_order']) && is_string($_POST['favs_order'])) {
            $order = explode(',', $_POST['favs_order']);
        } else {
            $order = [];
        }

        if (!empty($_POST['saveorder']) && $order !== []) {
            // Order favs

            foreach ($order as $k => $favorite_id) {
                if (!App::backend()->favorites()->exists((string) $favorite_id)) {
                    unset($order[$k]);
                }
            }
            App::backend()->favorites()->setFavoriteIDs($order, false);    // @phpstan-ignore argument.type (: $order is array<string>)
            if (!App::error()->flag()) {
                App::backend()->notices()->addSuccessNotice(__('Favorites have been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['replace']) && App::auth()->isSuperAdmin()) {
            // Replace default favorites by current set (super admin only)

            $user_favs = App::backend()->favorites()->getFavoriteIDs(false);
            App::backend()->favorites()->setFavoriteIDs($user_favs, true);

            if (!App::error()->flag()) {
                App::backend()->notices()->addSuccessNotice(__('Default favorites have been successfully updated.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        if (!empty($_POST['resetorder'])) {
            // Reset dashboard items order

            App::auth()->prefs()->get('dashboard')->drop('main_order');
            App::auth()->prefs()->get('dashboard')->drop('boxes_order');
            App::auth()->prefs()->get('dashboard')->drop('boxes_items_order');
            App::auth()->prefs()->get('dashboard')->drop('boxes_contents_order');

            if (!App::error()->flag()) {
                App::backend()->notices()->addSuccessNotice(__('Dashboard items order have been successfully reset.'));
                App::backend()->url()->redirect('admin.user.preferences', [], '#user-favorites');
            }
        }

        return true;
    }

    public static function render(): void
    {
        App::backend()->page()->open(
            __('My preferences'),
            (self::$user_acc_nodragdrop ? '' : App::backend()->page()->jsLoad('js/_preferences-dragdrop.js')) .
            App::backend()->page()->jsLoad('js/jquery/jquery-ui.custom.js') .
            App::backend()->page()->jsLoad('js/jquery/jquery.ui.touch-punch.js') .
            App::backend()->page()->jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            App::backend()->page()->jsLoad('js/pwstrength.js') .
            App::backend()->page()->jsJson('userprefs', [
                'remove'       => __('Are you sure you want to remove selected favorites?'),
                'passkeylabel' => __('Enter a name for this key:'),
            ]) .
            App::backend()->page()->jsLoad('js/_preferences.js') .
            App::backend()->page()->jsPageTabs(App::backend()->tab) .
            App::backend()->page()->jsConfirmClose('user-form', 'opts-forms', 'favs-form', 'db-forms') .
            App::backend()->page()->jsAdsBlockCheck() .

            # --BEHAVIOR-- adminPreferencesHeaders --
            App::behavior()->callBehavior('adminPreferencesHeaders'),
            App::backend()->page()->breadcrumb(
                [
                    Html::escapeHTML(App::auth()->userID()) => '',
                    __('My preferences')                    => '',
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

        // otp (2fa) configuration
        $otp_items = [];
        if (App::backend()->auth()->otp() !== false) {
            if (App::backend()->auth()->otp()->isVerified()) {
                $otp_items = [
                    (new Text('p', __('Your account is registered to two factors authentication.'))),
                    (new Submit(['otp_delete'], __('Disable two factors authentication')))
                        ->class('delete'),
                ];
            } else {
                try {
                    $qr_code_img = App::backend()->auth()->otp()->getQrCodeImageHtml();
                } catch (Exception) {
                    $qr_code_img = (new Text(null, __('Unable to create the QR code image, please use the secret below.')))
                        ->class('warn');
                }
                $otp_items = [
                    (new Text('p', __('Scan this QR code with your authentication application:'))),
                    (new Para())
                        ->items([
                            $qr_code_img,
                        ]),
                    (new Para())
                        ->items([
                            (new Input('otp_secret'))
                                ->size(80)
                                ->maxlength(255)
                                ->value(App::backend()->auth()->otp()->getSecret())
                                ->readonly(true)
                                //->extra('aria-describedby="otp_verify_secret_help')
                                ->label(new Label(__('Or enter this secret into your authentication application:'), Label::OL_TF)),
                            (new Submit('otp_regenerate', __('Regenerate')))
                                ->class('delete'),
                        ]),
                    (new Para())
                        ->items([
                            (new Input('otp_verify_code'))
                                ->size(10)
                                ->maxlength(App::backend()->auth()->otp()->getDigits())
                                ->value('')
                                //->extra('aria-describedby="otp_verify_code_help')
                                ->label(new Label(__('Enter verification code:'), Label::OL_TF)),
                            (new Submit('otp_verify_submit', __('Verify'))),
                        ]),
                    (new Text('p', ''))->class('clear'),
                ];
            }
        }

        // webauthn (passkey) configuration
        $webauthn_items = [];
        if (App::backend()->auth()->webauthn() !== false) {
            $webauthn_creds = App::backend()->auth()->webauthn()->store()->getCredentials('', (string) App::auth()->userID());

            foreach ($webauthn_creds as $webauthn_cred) {
                $webauthn_items[] = (new Li())
                    ->separator(' ')
                    ->items([
                        (new Text('', Html::escapeHTML($webauthn_cred->label() ?: __('unlabeled key'))))
                            ->title(App::backend()->auth()->webauthn()->provider()->getProvider($webauthn_cred->UUID())),
                        (new Text('', sprintf(__('valid on %s'), $webauthn_cred->rpId())))
                            ->title(Date::dt2str(__('%Y-%m-%d %H:%M'), $webauthn_cred->createDate())),
                        (new Submit(['webauthn[' . base64_encode((string) $webauthn_cred->credentialId()) . ']'], __('Delete')))
                            ->class('delete'),
                    ]);
            }

            if ($webauthn_items === []) {
                $webauthn_items[] = (new Li())
                    ->text(__('You have no registered key yet.'));
            }
        }

        // Oauth2 client configuration
        $oauth2_items = [];
        if (App::backend()->auth()->oauth2() !== false) {
            foreach (App::backend()->auth()->oauth2()->services()->getProviders() as $oauth2_service) {
                // Check service
                $service_id = is_string($service_id = $oauth2_service::getId()) ? $service_id : '';
                if ($service_id === '') {
                    continue;
                }

                if (App::backend()->auth()->oauth2()->services()->hasDisabledProvider($service_id)) {
                    continue;
                }

                // Check service
                if (!App::backend()->auth()->oauth2()->store()->hasConsumer($service_id)) {
                    continue;
                }

                // Get auth button
                $oauth2_link = App::backend()->auth()->oauth2()->getActionButton(
                    (string) App::auth()->userID(),
                    $service_id,
                    App::backend()->url()->get('admin.user.preferences') . '#user-profile.user_options_oauth2',
                    true
                );

                if (!is_null($oauth2_link)) {
                    $oauth2_div  = [];
                    $oauth2_user = App::backend()->auth()->oauth2()->store()->getLocalUser($service_id);
                    if ($oauth2_user->isConfigured()) {
                        $oauth2_avatar = is_string($oauth2_avatar = $oauth2_user->get('avatar')) ? $oauth2_avatar : '';
                        if ($oauth2_avatar === '') {
                            $oauth2_avatar = is_string($oauth2_avatar = $oauth2_service::getIcon()) ? $oauth2_avatar : '';
                        }

                        $oauth2_name = is_string($oauth2_name = $oauth2_user->get('displayname')) ? $oauth2_name : '';
                        if ($oauth2_name === '') {
                            $oauth2_name = is_string($oauth2_name = $oauth2_user->get('uid')) ? $oauth2_name : '';
                        }

                        $oauth2_div[] = (new Div())
                            ->items([
                                // user avatar
                                (new Div())
                                    ->class('box')
                                    ->items([
                                        (new Img($oauth2_avatar))
                                            ->class('icon-medium'),
                                    ]),
                                // user name
                                (new Div())
                                    ->class('box')
                                    ->items([
                                        (new Text(null, __('Linked to account:'))),
                                        (new Text('strong', $oauth2_name)),
                                    ]),
                            ]);
                    }

                    $oauth2_items[] = (new Div())
                        ->class(['three-boxes'])
                        ->items([...$oauth2_div, $oauth2_link]);
                }
            }
        }

        $zones = [];
        foreach (Date::getZones(true, true) as $key => $value) {
            $zones[] = (new Optgroup($key))
                ->items(array_map(fn ($key, $val): Option => new Option($key, $val), array_keys($value), array_values($value)));
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
                                    ->value(Html::escapeHTML(self::$user_name))
                                    ->autocomplete('family-name')
                                    ->translate(false)
                                    ->label((new Label(__('Last Name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_firstname'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_firstname))
                                    ->autocomplete('given-name')
                                    ->translate(false)
                                    ->label((new Label(__('First Name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_displayname'))
                                    ->size(20)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_displayname))
                                    ->autocomplete('nickname')
                                    ->translate(false)
                                    ->label((new Label(__('Display name:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Email('user_email'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_email))
                                    ->autocomplete('email')
                                    ->translate(false)
                                    ->label((new Label(__('Email:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_profile_mails'))
                                    ->size(80)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_profile_mails))
                                    ->translate(false)
                                    ->label((new Label(__('Alternate emails (comma separated list):'), Label::OL_TF))),
                            ]),
                        (new Note('sanitize_emails'))
                            ->class(['form-note', 'info'])
                            ->text(__('Invalid emails will be automatically removed from list.')),
                        (new Para())
                            ->items([
                                (new Url('user_url'))
                                    ->size(40)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_url))
                                    ->autocomplete('url')
                                    ->translate(false)
                                    ->label((new Label(__('URL:'), Label::OL_TF))),
                            ]),
                        (new Para())
                            ->items([
                                (new Input('user_profile_urls'))
                                    ->size(80)
                                    ->maxlength(255)
                                    ->value(Html::escapeHTML(self::$user_profile_urls))
                                    ->translate(false)
                                    ->label((new Label(__('Alternate URLs (comma separated list):'), Label::OL_TF))),
                            ]),
                        (new Note('sanitize_urls'))
                            ->class(['form-note', 'info'])
                            ->text(__('Invalid URLs will be automatically removed from list.')),
                        (new Para())
                            ->items([
                                (new Select('user_lang'))
                                    ->items(App::backend()->combos()->getAdminLangsCombo())
                                    ->default(self::$user_lang)
                                    ->translate(false)
                                    ->label((new Label(__('Language for my interface:'), Label::OL_TF))),
                            ]),
                        (new Note())
                            ->class(['form-note', 'info'])
                            ->text(__('Languages other than French and English have been automatically translated. If you spot any incorrect translations, please let us know.')),
                        (new Para())
                            ->items([
                                (new Select('user_tz'))
                                    ->items($zones)
                                    ->default(self::$user_tz)
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

                        // otp
                        App::backend()->auth()->otp() === false ? new None() : (new Fieldset('user_options_otp'))
                            ->legend(new Legend(__('Two factors authentication')))
                            ->separator('')
                            ->items($otp_items),
                        // wenauthn
                        App::backend()->auth()->webauthn() === false ? new None() : (new Fieldset('user_options_webauthn'))
                            ->legend(new Legend(__('Authentication keys')))
                            ->separator('')
                            ->items([
                                (new Ul())
                                    ->items($webauthn_items),
                                (new Para('webauthn_action'))
                                    ->items([
                                        (new Button(['webauthn_button'], __('Register a new key'))),
                                    ])
                                    ->class(['hidden-if-no-js']),
                            ]),
                        // oauth2
                        $oauth2_items === [] ? new None() : (new Fieldset('user_options_oauth2'))
                            ->legend(new Legend(__('Authentication applications')))
                            ->separator('')
                            ->items($oauth2_items),
                    ]),
            ])
        ->render();

        // User options : some from actual user profile, dashboard modules, ...

        // Themes
        $theme_combo = [
            __('Light')     => 'light',
            __('Dark')      => 'dark',
            __('Automatic') => '',
        ];

        // Body base font size (37.5% = 6px, 50% = 8px, 62.5% = 10px, 75% = 12px, 87.5% = 14px)
        $htmlfontsize_combo = [
            __('Smallest') => '37.5%',
            __('Smaller')  => '50%',
            __('Default')  => '62.5%',
            __('Larger')   => '75%',
            __('Largest')  => '87.5%',
        ];

        $odd     = true;
        $columns = [];
        foreach (self::$cols as $col_type => $col_list) {
            $fields = [];
            foreach ($col_list[1] as $col_name => $col_data) {
                $fields[] = (new Div())
                    ->class('cols_sort_handler')
                    ->items([
                        (new Checkbox(['cols_' . $col_type . '[]', 'cols_' . $col_type . '-' . $col_name], $col_data[0]))
                            ->value($col_name)
                            ->label(new Label($col_data[1], Label::IL_FT)),
                        (new Hidden(['cols_' . $col_type . '_idx[]', 'cols_' . $col_type . '-' . $col_name], $col_name)),
                    ]);
            }

            $columns[] = (new Div())
                ->class(['two-boxes', $odd ? 'odd' : 'even'])
                ->items([
                    (new Text('h5', $col_list[0])),
                    ...$fields,
                ]);
            $odd = !$odd;
        }

        $order_combo = [
            new Option(__('Descending'), 'desc'),
            new Option(__('Ascending'), 'asc'),
        ];

        $filters = [];
        foreach (self::$filters as $filter) {
            $type = $filter->getType();

            $filters[] = (new Tr())
                    ->class('line')
                    ->cols([
                        (new Td())
                            ->text($filter->getLabel() ?? ''),
                        (new Td())
                            ->items([
                                $filter->getSortBy() !== null ?
                                    (new Select('sorts_' . $type . '_sortby'))
                                        ->items($filter->getOptions() ?? [])
                                        ->default($filter->getSortBy()) :
                                    (new None()),
                            ]),
                        (new Td())
                            ->items([
                                $filter->getOrder() !== null ?
                                    (new Select('sorts_' . $type . '_order'))
                                        ->items($order_combo)
                                        ->default($filter->getOrder()) :
                                    (new None()),
                            ]),
                        (new Td())
                            ->items([
                                $filter->getNb() !== null ?
                                    (new Number('sorts_' . $type . '_nb', 0, 999, $filter->getNb()))
                                        ->label(new Label($filter->getNbLabel() ?? '', Label::IL_FT)) :
                                    (new None()),
                            ]),
                    ]);
        }

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
                ->rows($filters));

        // List of choosen editor by syntax
        $editors_list = [];
        foreach (self::$format_by_editors as $format => $data) {
            $label          = sprintf(__('Preferred editor for %s:'), (new Strong(App::formater()->getFormaterName($format)))->render());
            $editors_list[] = (new Para())
                ->class('field')
                ->items([
                    (new Select(['user_editor[' . $format . ']', 'user_editor_' . $format]))
                        ->items(array_merge([__('Choose an editor') => ''], $data))
                        ->default(self::$user_ui_editor[$format])
                        ->label(new Label($label, Label::OL_TF)),
                ]);
        }

        // List of contexts (fields) where HTML editor should be use rather than pure text
        $rte_list = [];
        foreach (self::$rte as $context => $data) {
            $rte_list[] = (new Para())
                ->items([
                    (new Checkbox(['rte_flags[]', 'rte_' . $context], $data[0]))
                        ->value($context)
                        ->label(new Label($data[1], Label::IL_FT)),
                ]);
        }

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
                                            ->items($theme_combo)
                                            ->default(self::$user_ui_theme)
                                            ->label(new Label(__('Theme:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_enhanceduploader', self::$user_ui_enhanceduploader))
                                            ->value(1)
                                            ->label(new Label(__('Activate enhanced uploader in media manager'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_blank_preview', self::$user_ui_blank_preview))
                                            ->value(1)
                                            ->label(new Label(__('Preview the entry being edited in a blank window or tab (depending on your browser settings).'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_acc_nodragdrop', self::$user_acc_nodragdrop))
                                            ->value(1)
                                            ->extra('aria-describedby="user_acc_nodragdrop_help"')
                                            ->label(new Label(__('Disable javascript powered drag and drop for ordering items'), Label::IL_FT)),
                                    ]),
                                (new Note('user_acc_nodragdrop_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('If checked, numeric fields will allow to type the elements\' ordering number.')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_hidemoreinfo', self::$user_ui_hidemoreinfo))
                                            ->value(1)
                                            ->label(new Label(__('Hide all secondary information and notes'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_hidehelpbutton', self::$user_ui_hidehelpbutton))
                                            ->value(1)
                                            ->label(new Label(__('Hide help button'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Select('user_ui_htmlfontsize'))
                                            ->items($htmlfontsize_combo)
                                            ->default(self::$user_ui_htmlfontsize)
                                            ->label(new Label(__('Font size:'), Label::IL_TF)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_dynamicletterspacing', self::$user_ui_dynamicletterspacing))
                                            ->value(1)
                                            ->label(new Label(__('Use dynamic letter spacing'), Label::IL_FT)),
                                    ]),
                                (new Note('user_user_ui_dynamicletterspacing_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('If checked, the larger the font size in interface texts, the smaller the space between characters will be, and vice versa.')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_systemfont', self::$user_ui_systemfont))
                                            ->value(1)
                                            ->label(new Label(__('Use operating system font'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Number('user_ui_media_nb_last_dirs', 0, 999, self::$user_ui_media_nb_last_dirs))
                                            ->extra('aria-describedby="user_ui_media_nb_last_dirs_help"')
                                            ->label(new Label(__('Number of recent folders proposed in media manager:'), Label::IL_TF)),
                                    ]),
                                (new Note('user_ui_media_nb_last_dirs_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Set to 0 (zero) to ignore, displayed only if Javascript is enabled in your browser.')),
                                App::auth()->isSuperAdmin() ?
                                    (new Para())
                                        ->items([
                                            (new Checkbox('user_ui_hide_std_favicon', self::$user_ui_hide_std_favicon))
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
                                        (new Checkbox('user_ui_nocheckadblocker', self::$user_ui_nocheckadblocker))
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
                                            ->value(Html::escapeHTML(self::$user_ui_quickmenuprefix))
                                            ->extra('aria-describedby="user_ui_quickmenuprefix_help')
                                            ->label(new Label(__('Quick menu character:'), Label::IL_TF)),
                                    ]),
                                (new Note('user_ui_quickmenuprefix_help'))
                                    ->class(['form-note', 'clear'])
                                    ->text(__('Leave empty to use the default character <kbd>:</kbd>')),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_stickymenu', self::$user_ui_stickymenu))
                                            ->value(1)
                                            ->label(new Label(__('Keep the main menu at the top of the page as much as possible'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_hidecollapserbtn', self::$user_ui_hidecollapserbtn))
                                            ->value(1)
                                            ->label(new Label(__('Hide the menu collapse button'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset('user_options_columns_container'))
                            ->legend((new Legend(__('Optional columns displayed in lists'), 'user_options_columns')))
                            ->fields($columns),
                        (new Fieldset('user_options_lists_container'))
                            ->legend((new Legend(__('Options for lists'), 'user_options_lists')))
                            ->fields([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_ui_auto_filter', self::$auto_filter))
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
                                        (new Set())
                                            ->items($editors_list),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Select('user_post_format'))
                                                    ->items(self::$available_formats)
                                                    ->default(self::$user_ui_post_format)
                                                    ->label(new Label(__('Preferred format:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Select('user_post_status'))
                                                    ->items(App::status()->post()->combo())
                                                    ->default(self::$user_post_status)
                                                    ->label(new Label(__('Default entry status:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->class('field')
                                            ->items([
                                                (new Number('user_edit_size', 10, 999, self::$user_ui_edit_size))
                                                    ->label(new Label(__('Entry edit field height:'), Label::OL_TF)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Checkbox('user_wysiwyg', self::$user_ui_enable_wysiwyg))
                                                    ->value(1)
                                                    ->label(new Label(__('Enable WYSIWYG mode'), Label::IL_FT)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Checkbox('user_toolbar_bottom', self::$user_ui_toolbar_bottom))
                                                    ->value(1)
                                                    ->label(new Label(__('Display editor\'s toolbar at bottom of textarea (if possible)'), Label::IL_FT)),
                                            ]),
                                    ]),
                                (new Div())
                                    ->class(['two-boxes', 'even'])
                                    ->items([
                                        (new Text('h5', __('Use HTML editor for:'))),
                                        (new Set())
                                            ->items($rte_list),
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

                $icon = $fav->menuIcon() instanceof Icon ? $fav->menuIcon()->getComponent()->render() : '';
                if ($icon === '') {
                    // Fallback to legacy icon
                    $icon = $fav->smallIcon() ? App::backend()->helper()->adminIcon($fav->smallIcon()) : $id;
                }

                $zoom = $fav->dashboardIcon() instanceof Icon ? $fav->dashboardIcon()->getComponent('')->render() : '';
                if ($zoom === '') {
                    // Fallback to legacy icon
                    $zoom = $fav->largeIcon() ? App::backend()->helper()->adminIcon($fav->largeIcon(), false) : '';
                }

                if ($zoom !== '') {
                    $icon .= ' ' . (new Span($zoom))->class('zoom')->render();
                }

                $title = $fav->title() ?? $id;

                $user_favorites_items[] = (new Li('fu-' . $id))
                    ->items([
                        (new Number(['order[' . $id . ']'], 1, count($user_fav), $count))
                            ->class('position')
                            ->title(sprintf(__('position of %s'), $title)),
                        (new Hidden(['dynorder[]', 'dynorder-' . $id . ''], (string) $id)),
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
            strtolower(Txt::removeDiacritics((string) $a->title())),
            strtolower(Txt::removeDiacritics((string) $b->title()))
        ));
        foreach (array_keys($avail_fav) as $k) {
            if (in_array($k, $user_fav)) {
                unset($avail_fav[$k]);
            }
        }
        $other_favorites_items = [];
        foreach ($avail_fav as $k => $fav) {
            $count++;

            $icon = $fav->menuIcon() ? $fav->menuIcon()->getComponent()->render() : '';
            if ($icon === '') {
                // Fallback to legacy icon
                $icon = App::backend()->helper()->adminIcon($fav->smallIcon());
            }

            $zoom = $fav->dashboardIcon() ? $fav->dashboardIcon()->getComponent('')->render() : '';
            if ($zoom === '') {
                // Fallback to legacy icon
                $zoom = App::backend()->helper()->adminIcon($fav->largeIcon(), false);
            }

            if ($zoom !== '') {
                $icon .= ' ' . (new Span($zoom))->class('zoom')->render();
            }

            $other_favorites_items[] = (new Li('fa-' . $k))
                ->items([
                    (new Checkbox(['append[]', 'fak-' . $k]))
                        ->value($k)
                        ->label((new Label($fav->title() ?? $k, Label::IL_FT))->prefix($icon)),
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
                                        (new Checkbox('user_ui_nofavmenu', !self::$user_ui_nofavmenu))
                                            ->value(1)
                                            ->label(new Label(__('Display favorites at the top of the menu'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend(new Legend(__('Dashboard icons')))
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_nofavicons', !self::$user_dm_nofavicons))
                                            ->value(1)
                                            ->label(new Label(__('Display dashboard icons'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_densefavicons', self::$user_dm_densefavicons))
                                            ->value(1)
                                            ->label(new Label(__('Use a dense layout of dashboard icons'), Label::IL_FT)),
                                    ]),
                            ]),
                        (new Fieldset())
                            ->legend(new Legend(__('Dashboard modules')))
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_doclinks', self::$user_dm_doclinks))
                                            ->value(1)
                                            ->label(new Label(__('Display documentation links'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_donate', self::$user_dm_donate))
                                            ->value(1)
                                            ->label(new Label(__('Display donate links'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_dcnews', self::$user_dm_dcnews))
                                            ->value(1)
                                            ->label(new Label(__('Display Dotclear news'), Label::IL_FT)),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_quickentry', self::$user_dm_quickentry))
                                            ->value(1)
                                            ->label(new Label(__('Display quick entry form'), Label::IL_FT)),
                                    ]),
                                App::auth()->isSuperAdmin() ?
                                    (new Para())
                                        ->items([
                                            (new Checkbox('user_dm_nodcupdate', self::$user_dm_nodcupdate))
                                                ->value(1)
                                                ->label(new Label(__('Do not display Dotclear updates'), Label::IL_FT))]) :
                                    (new None()),
                                (new Para())
                                    ->items([
                                        (new Checkbox('user_dm_denseboxes', self::$user_dm_denseboxes))
                                            ->value(1)
                                            ->label(new Label(__('Use a dense layout of dashboard boxes'), Label::IL_FT)),
                                    ]),
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

        App::backend()->page()->helpBlock('core_user_pref');
        App::backend()->page()->close();
    }
}
