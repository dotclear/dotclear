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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/user.php
 *
 * @todo switch Helper/Html/Form/...
 */
class User extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        App::backend()->page_title = __('New user');

        App::backend()->user_id          = '';
        App::backend()->user_super       = '';
        App::backend()->user_status      = '';
        App::backend()->user_change_pwd  = '';
        App::backend()->user_name        = '';
        App::backend()->user_firstname   = '';
        App::backend()->user_displayname = '';
        App::backend()->user_email       = '';
        App::backend()->user_url         = '';
        App::backend()->user_lang        = App::auth()->getInfo('user_lang');
        App::backend()->user_tz          = App::auth()->getInfo('user_tz');
        App::backend()->user_post_status = App::status()->post()::PENDING;

        App::backend()->user_options = App::users()->userDefaults();

        App::backend()->user_profile_mails = '';
        App::backend()->user_profile_urls  = '';

        # Formaters combo
        App::backend()->formaters_combo = Combos::getFormatersCombo();

        # Posts status combo !
        App::backend()->status_combo = App::status()->post()->combo();

        # Language codes
        App::backend()->lang_combo = Combos::getAdminLangsCombo();

        # Get user if we have an ID
        if (!empty($_REQUEST['id'])) {
            try {
                App::backend()->rs = App::users()->getUser($_REQUEST['id']);

                App::backend()->user_id          = App::backend()->rs->user_id;
                App::backend()->user_super       = App::backend()->rs->user_super;
                App::backend()->user_status      = App::backend()->rs->user_status;
                App::backend()->user_change_pwd  = App::backend()->rs->user_change_pwd;
                App::backend()->user_name        = App::backend()->rs->user_name;
                App::backend()->user_firstname   = App::backend()->rs->user_firstname;
                App::backend()->user_displayname = App::backend()->rs->user_displayname;
                App::backend()->user_email       = App::backend()->rs->user_email;
                App::backend()->user_url         = App::backend()->rs->user_url;
                App::backend()->user_lang        = App::backend()->rs->user_lang;
                App::backend()->user_tz          = App::backend()->rs->user_tz;
                App::backend()->user_post_status = App::backend()->rs->user_post_status;

                App::backend()->user_options = array_merge(App::backend()->user_options, App::backend()->rs->options());

                $user_prefs = App::userPreferences()->createFromUser(App::backend()->user_id, 'profile');

                App::backend()->user_profile_mails = $user_prefs->profile->mails;
                App::backend()->user_profile_urls  = $user_prefs->profile->urls;

                App::backend()->page_title = App::backend()->user_id;
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Add or update user

            try {
                if (empty($_POST['your_pwd']) || !App::auth()->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $cur = App::auth()->openUserCursor();

                $cur->user_id          = $_POST['user_id'];
                $cur->user_super       = App::backend()->user_super = empty($_POST['user_super']) ? 0 : 1;
                $cur->user_status      = App::backend()->user_status = App::status()->user()->level((int) $_POST['user_status']);
                $cur->user_name        = App::backend()->user_name = Html::escapeHTML($_POST['user_name']);
                $cur->user_firstname   = App::backend()->user_firstname = Html::escapeHTML($_POST['user_firstname']);
                $cur->user_displayname = App::backend()->user_displayname = Html::escapeHTML($_POST['user_displayname']);
                $cur->user_email       = App::backend()->user_email = Html::escapeHTML($_POST['user_email']);
                $cur->user_url         = App::backend()->user_url = Html::escapeHTML($_POST['user_url']);
                $cur->user_lang        = App::backend()->user_lang = Html::escapeHTML($_POST['user_lang']);
                $cur->user_tz          = App::backend()->user_tz = Html::escapeHTML($_POST['user_tz']);
                $cur->user_post_status = App::backend()->user_post_status = (int) $_POST['user_post_status'];

                if (App::backend()->user_id && $cur->user_id == App::auth()->userID() && App::auth()->isSuperAdmin()) {
                    // force super_user to true if current user
                    $cur->user_super = App::backend()->user_super = true;
                }
                if (App::backend()->user_id && $cur->user_id == App::auth()->userID()) {
                    // force user_status to 1 if current user
                    $cur->user_status = App::backend()->user_status = true;
                }
                if (App::auth()->allowPassChange()) {
                    $cur->user_change_pwd = empty($_POST['user_change_pwd']) ? 0 : 1;
                }

                if (!empty($_POST['new_pwd'])) {
                    if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                        throw new Exception(__("Passwords don't match"));
                    }
                    $cur->user_pwd = $_POST['new_pwd'];
                }

                $user_options = App::backend()->user_options;

                $user_options['post_format'] = Html::escapeHTML($_POST['user_post_format']);
                $user_options['edit_size']   = (int) $_POST['user_edit_size'];

                if ($user_options['edit_size'] < 1) {
                    $user_options['edit_size'] = 10;
                }

                App::backend()->user_options = $user_options;

                $cur->user_options = new ArrayObject(App::backend()->user_options);

                if (App::backend()->user_id) {
                    // Update user

                    # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminBeforeUserUpdate', $cur, App::backend()->user_id);

                    $new_id = App::users()->updUser(App::backend()->user_id, $cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }

                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserUpdate', $cur, $new_id);

                    if (App::backend()->user_id == App::auth()->userID() && App::backend()->user_id != $new_id) {
                        App::session()->destroy();
                    }

                    Notices::addSuccessNotice(__('User has been successfully updated.'));
                    App::backend()->url()->redirect('admin.user', ['id' => $new_id]);
                } else {
                    // Add user

                    if (App::users()->getUsers(['user_id' => $cur->user_id], true)->f(0) > 0) {
                        throw new Exception(sprintf(__('User "%s" already exists.'), Html::escapeHTML($cur->user_id)));
                    }

                    # --BEHAVIOR-- adminBeforeUserCreate -- Cursor
                    App::behavior()->callBehavior('adminBeforeUserCreate', $cur);

                    $new_id = App::users()->addUser($cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', (string) $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }
                    $user_prefs = App::userPreferences()->createFromUser($new_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserCreate -- Cursor, string
                    App::behavior()->callBehavior('adminAfterUserCreate', $cur, $new_id);

                    Notices::addSuccessNotice(__('User has been successfully created.'));

                    if (!$cur->user_super) {
                        Notices::addWarningNotice(__('User has no permission, he will not be able to login yet. See below to add some.'));
                    }
                    if (App::status()->user()->isRestricted((int) $cur->user_status)) {
                        Notices::addWarningNotice(__('User is disabled, he will not be able to login yet.'));
                    }
                    if (!empty($_POST['saveplus'])) {
                        App::backend()->url()->redirect('admin.user');
                    } else {
                        App::backend()->url()->redirect('admin.user', ['id' => $new_id]);
                    }
                }
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            App::backend()->page_title,
            Page::jsConfirmClose('user-form') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('js/pwstrength.js') .
            Page::jsLoad('js/_user.js') .
            # --BEHAVIOR-- adminUserHeaders --
            App::behavior()->callBehavior('adminUserHeaders'),
            Page::breadcrumb(
                [
                    __('System')               => '',
                    __('Users')                => App::backend()->url()->get('admin.users'),
                    App::backend()->page_title => '',
                ]
            )
        );

        if (!empty($_GET['upd'])) {
            Notices::success(__('User has been successfully updated.'));
        }

        if (!empty($_GET['add'])) {
            Notices::success(__('User has been successfully created.'));
        }

        echo
        '<form action="' . App::backend()->url()->get('admin.user') . '" method="post" id="user-form">' .
        '<div class="two-cols">' .

        '<div class="col">' .
        '<h3>' . __('User profile') . '</h3>' .
        '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>' .

        '<p><label for="user_id" class="required"><span>*</span> ' . __('User ID:') . '</label> ' .
        form::field('user_id', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_id),
            'extra_html'   => 'required placeholder="' . __('Login') . '" aria-describedby="user_id_help user_id_warning"',
            'autocomplete' => 'username',
        ]) .
        '</p>' .
        '<p class="form-note info" id="user_id_help">' . __('At least 2 characters using letters, numbers or symbols.') . '</p>';

        if (App::backend()->user_id == App::auth()->userID()) {
            echo
            '<p class="warning" id="user_id_warning">' . __('Warning:') . ' ' .
            __('If you change your username, you will have to log in again.') . '</p>';
        }

        echo
        '<p>' .
        '<label for="new_pwd" ' .
        (App::backend()->user_id != '' ? '' : 'class="required"') . '>' .
        (App::backend()->user_id != '' ? '' : '<span>*</span> ') .
        (App::backend()->user_id != '' ? __('New password:') : __('Password:')) . '</label>' .
        form::password(
            'new_pwd',
            20,
            255,
            [
                'class'        => 'pw-strength',
                'extra_html'   => (App::backend()->user_id != '' ? '' : 'aria-describedby="new_pwd_help" required placeholder="' . __('Password') . '"'),
                'autocomplete' => 'new-password', ]
        ) .
        '</p>' .
        '<p class="form-note info" id="new_pwd_help">' . __('Password must contain at least 6 characters.') . '</p>' .

        '<p><label for="new_pwd_c" ' . (App::backend()->user_id != '' ? '' : 'class="required"') . '>' .
        (App::backend()->user_id != '' ? '' : '<span>*</span> ') . __('Confirm password:') . '</label> ' .
        form::password(
            'new_pwd_c',
            20,
            255,
            [
                'extra_html'   => (App::backend()->user_id != '' ? '' : 'required placeholder="' . __('Password') . '"'),
                'autocomplete' => 'new-password', ]
        ) .
        '</p>';

        if (App::auth()->allowPassChange()) {
            echo
            '<p><label for="user_change_pwd" class="classic">' .
            form::checkbox('user_change_pwd', '1', App::backend()->user_change_pwd) . ' ' .
            __('Password change required to connect') . '</label></p>';
        }

        $super_disabled  = App::backend()->user_super && App::backend()->user_id == App::auth()->userID();
        $status_disabled = App::backend()->user_id                               == App::auth()->userID();

        echo
        '<p><label for="user_super" class="classic">' .
        form::checkbox(
            ($super_disabled ? 'user_super_off' : 'user_super'),
            '1',
            [
                'checked'  => App::backend()->user_super,
                'disabled' => $super_disabled,
            ]
        ) .
        ' ' . __('Super administrator') . '</label></p>' .
        ($super_disabled ? form::hidden(['user_super'], App::backend()->user_super) : '') .

        (App::backend()->user_id != App::auth()->userID() ?
            '<p><label for="user_status">' . __('Status:') . '</label> ' .
            form::combo('user_status', App::status()->user()->combo(), App::backend()->user_status) .
            '</p>' :
            form::hidden(['user_status'], App::backend()->user_status)
        ) .

        '<p><label for="user_name">' . __('Last Name:') . '</label> ' .
        form::field('user_name', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_name),
            'autocomplete' => 'family-name',
        ]) .
        '</p>' .

        '<p><label for="user_firstname">' . __('First Name:') . '</label> ' .
        form::field('user_firstname', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_firstname),
            'autocomplete' => 'given-name',
        ]) .
        '</p>' .

        '<p><label for="user_displayname">' . __('Display name:') . '</label> ' .
        form::field('user_displayname', 20, 255, [
            'default'      => Html::escapeHTML(App::backend()->user_displayname),
            'autocomplete' => 'nickname',
        ]) .
        '</p>' .

        '<p><label for="user_email">' . __('Email:') . '</label> ' .
        form::email('user_email', [
            'default'      => Html::escapeHTML(App::backend()->user_email),
            'extra_html'   => 'aria-describedby="user_email_help"',
            'autocomplete' => 'email',
        ]) .
        '</p>' .
        '<p class="form-note" id="user_email_help">' . __('Mandatory for password recovering procedure.') . '</p>' .

        '<p><label for="user_profile_mails">' . __('Alternate emails (comma separated list):') . '</label>' .
        form::field('user_profile_mails', 50, 255, [
            'default' => Html::escapeHTML(App::backend()->user_profile_mails),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_emails">' . __('Invalid emails will be automatically removed from list.') . '</p>' .

        '<p><label for="user_url">' . __('URL:') . '</label> ' .
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

        '</div>' .

        '<div class="col">' .
        '<h3>' . __('Options') . '</h3>' .
        '<h4>' . __('Interface') . '</h4>' .
        '<p><label for="user_lang">' . __('Language:') . '</label> ' .
        form::combo('user_lang', App::backend()->lang_combo, App::backend()->user_lang) .
        '</p>' .

        '<p><label for="user_tz">' . __('Timezone:') . '</label> ' .
        form::combo('user_tz', Date::getZones(true, true), App::backend()->user_tz) .
        '</p>' .

        '<h4>' . __('Edition') . '</h4>' .
        '<p><label for="user_post_format">' . __('Preferred format:') . '</label> ' .
        form::combo('user_post_format', App::backend()->formaters_combo, App::backend()->user_options['post_format']) .
        '</p>' .

        '<p><label for="user_post_status">' . __('Default entry status:') . '</label> ' .
        form::combo('user_post_status', App::backend()->status_combo, App::backend()->user_post_status) .
        '</p>' .

        '<p><label for="user_edit_size">' . __('Entry edit field height:') . '</label> ' .
        form::number('user_edit_size', 10, 999, (string) App::backend()->user_options['edit_size']) .
        '</p>';

        # --BEHAVIOR-- adminUserForm -- MetaRecord|null
        App::behavior()->callBehavior('adminUserForm', App::backend()->rs ?? null);

        echo
        '</div>' .
        '</div>';

        echo
        '<p class="clear vertical-separator"><label for="your_pwd" class="required">' .
        '<span>*</span> ' . __('Your password:') . '</label>' .
        form::password(
            'your_pwd',
            20,
            255,
            [
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password',
            ]
        ) . '</p>' .
        '<p class="form-buttons clear"><input type="submit" name="save" accesskey="s" value="' . __('Save') . '">' .
        (App::backend()->user_id != '' ? '' : ' <input type="submit" name="saveplus" value="' . __('Save and create another') . '">') .
        (App::backend()->user_id != '' ? form::hidden('id', App::backend()->user_id) : '') .
        ' <input type="button" value="' . __('Back') . '" class="go-back reset hidden-if-no-js">' .
        App::nonce()->getFormNonce() .
        '</p>' .

        '</form>';

        if (App::backend()->user_id) {
            echo
            '<div class="clear fieldset">' .
            '<h3>' . __('Permissions') . '</h3>';

            if (!App::backend()->user_super) {
                echo
                '<form action="' . App::backend()->url()->get('admin.user.actions') . '" method="post">' .
                '<p><input type="submit" value="' . __('Add new permissions') . '">' .
                form::hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => App::backend()->user_id])) .
                form::hidden(['action'], 'blogs') .
                form::hidden(['users[]'], App::backend()->user_id) .
                App::nonce()->getFormNonce() .
                '</p>' .
                '</form>';

                $permissions = App::users()->getUserPermissions(App::backend()->user_id);
                $perm_types  = App::auth()->getPermissionsTypes();

                if ($permissions === []) {
                    echo
                    '<p>' . __('No permissions so far.') . '</p>';
                } else {
                    foreach ($permissions as $k => $v) {
                        if ((is_countable($v['p']) ? count($v['p']) : 0) > 0) {
                            echo
                            '<form action="' . App::backend()->url()->get('admin.user.actions') . '" method="post" class="perm-block">' .
                            '<p class="blog-perm">' . __('Blog:') . ' <a href="' .
                            App::backend()->url()->get('admin.blog', ['id' => Html::escapeHTML($k)]) . '">' .
                            Html::escapeHTML($v['name']) . '</a> (' . Html::escapeHTML($k) . ')</p>';   // @phpstan-ignore-line

                            echo
                            '<ul class="ul-perm">';
                            foreach ($v['p'] as $p => $V) { // @phpstan-ignore-line
                                if (isset($perm_types[$p])) {
                                    echo
                                    '<li>' . __($perm_types[$p]) . '</li>';
                                }
                            }
                            echo
                            '</ul>' .
                            '<p class="add-perm"><input type="submit" class="reset" value="' . __('Change permissions') . '">' .
                            form::hidden(['redir'], App::backend()->url()->get('admin.user', ['id' => App::backend()->user_id])) .
                            form::hidden(['action'], 'perms') .
                            form::hidden(['users[]'], App::backend()->user_id) .
                            form::hidden(['blogs[]'], $k) .
                            App::nonce()->getFormNonce() .
                            '</p>' .
                            '</form>';
                        }
                    }
                }
            } else {
                echo
                '<p>' . sprintf(__('%s is super admin (all rights on all blogs).'), '<strong>' . App::backend()->user_id . '</strong>') . '</p>';
            }
            echo
            '</div>';

            // Informations (direct links)
            echo
            '<div class="clear fieldset">' .
            '<h3>' . __('Direct links') . '</h3>' .
            '<p><a href="' . App::backend()->url()->get(
                'admin.posts',
                ['user_id' => App::backend()->user_id]
            ) . '">' . __('List of posts') . '</a></p>';

            if (App::backend()->user_email || App::backend()->user_url) {
                echo
                '<p><a href="' . App::backend()->url()->get(
                    'admin.comments',
                    [
                        'email' => App::backend()->user_email,
                        'site'  => App::backend()->user_url,
                    ]
                ) . '">' . __('List of comments') . '</a></p>';
            }

            echo
            '</div>';
        }

        Page::helpBlock('core_user');
        Page::close();
    }
}
