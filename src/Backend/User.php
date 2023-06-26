<?php
/**
 * @since 2.27 Before as admin/user.php
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Backend;

use ArrayObject;
use dcAuth;
use dcBlog;
use dcCore;
use dcPrefs;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Html;
use Exception;
use form;

class User extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        dcCore::app()->admin->page_title = __('New user');

        dcCore::app()->admin->user_id          = '';
        dcCore::app()->admin->user_super       = '';
        dcCore::app()->admin->user_change_pwd  = '';
        dcCore::app()->admin->user_name        = '';
        dcCore::app()->admin->user_firstname   = '';
        dcCore::app()->admin->user_displayname = '';
        dcCore::app()->admin->user_email       = '';
        dcCore::app()->admin->user_url         = '';
        dcCore::app()->admin->user_lang        = dcCore::app()->auth->getInfo('user_lang');
        dcCore::app()->admin->user_tz          = dcCore::app()->auth->getInfo('user_tz');
        dcCore::app()->admin->user_post_status = dcBlog::POST_PENDING; // Pending

        dcCore::app()->admin->user_options = dcCore::app()->userDefaults();

        dcCore::app()->admin->user_profile_mails = '';
        dcCore::app()->admin->user_profile_urls  = '';

        # Formaters combo
        dcCore::app()->admin->formaters_combo = Combos::getFormatersCombo();

        dcCore::app()->admin->status_combo = Combos::getPostStatusesCombo();

        # Language codes
        dcCore::app()->admin->lang_combo = Combos::getAdminLangsCombo();

        # Get user if we have an ID
        if (!empty($_REQUEST['id'])) {
            try {
                dcCore::app()->admin->rs = dcCore::app()->getUser($_REQUEST['id']);

                dcCore::app()->admin->user_id          = dcCore::app()->admin->rs->user_id;
                dcCore::app()->admin->user_super       = dcCore::app()->admin->rs->user_super;
                dcCore::app()->admin->user_change_pwd  = dcCore::app()->admin->rs->user_change_pwd;
                dcCore::app()->admin->user_name        = dcCore::app()->admin->rs->user_name;
                dcCore::app()->admin->user_firstname   = dcCore::app()->admin->rs->user_firstname;
                dcCore::app()->admin->user_displayname = dcCore::app()->admin->rs->user_displayname;
                dcCore::app()->admin->user_email       = dcCore::app()->admin->rs->user_email;
                dcCore::app()->admin->user_url         = dcCore::app()->admin->rs->user_url;
                dcCore::app()->admin->user_lang        = dcCore::app()->admin->rs->user_lang;
                dcCore::app()->admin->user_tz          = dcCore::app()->admin->rs->user_tz;
                dcCore::app()->admin->user_post_status = dcCore::app()->admin->rs->user_post_status;

                dcCore::app()->admin->user_options = array_merge(dcCore::app()->admin->user_options, dcCore::app()->admin->rs->options());

                $user_prefs = new dcPrefs(dcCore::app()->admin->user_id, 'profile');

                dcCore::app()->admin->user_profile_mails = $user_prefs->profile->mails;
                dcCore::app()->admin->user_profile_urls  = $user_prefs->profile->urls;

                dcCore::app()->admin->page_title = dcCore::app()->admin->user_id;
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        if (isset($_POST['user_name'])) {
            // Add or update user

            try {
                if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
                    throw new Exception(__('Password verification failed'));
                }

                $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME);

                $cur->user_id          = $_POST['user_id'];
                $cur->user_super       = dcCore::app()->admin->user_super = !empty($_POST['user_super']) ? 1 : 0;
                $cur->user_name        = dcCore::app()->admin->user_name = Html::escapeHTML($_POST['user_name']);
                $cur->user_firstname   = dcCore::app()->admin->user_firstname = Html::escapeHTML($_POST['user_firstname']);
                $cur->user_displayname = dcCore::app()->admin->user_displayname = Html::escapeHTML($_POST['user_displayname']);
                $cur->user_email       = dcCore::app()->admin->user_email = Html::escapeHTML($_POST['user_email']);
                $cur->user_url         = dcCore::app()->admin->user_url = Html::escapeHTML($_POST['user_url']);
                $cur->user_lang        = dcCore::app()->admin->user_lang = Html::escapeHTML($_POST['user_lang']);
                $cur->user_tz          = dcCore::app()->admin->user_tz = Html::escapeHTML($_POST['user_tz']);
                $cur->user_post_status = dcCore::app()->admin->user_post_status = (int) $_POST['user_post_status'];

                if (dcCore::app()->admin->user_id && $cur->user_id == dcCore::app()->auth->userID() && dcCore::app()->auth->isSuperAdmin()) {
                    // force super_user to true if current user
                    $cur->user_super = dcCore::app()->admin->user_super = true;
                }
                if (dcCore::app()->auth->allowPassChange()) {
                    $cur->user_change_pwd = !empty($_POST['user_change_pwd']) ? 1 : 0;
                }

                if (!empty($_POST['new_pwd'])) {
                    if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                        throw new Exception(__("Passwords don't match"));
                    }
                    $cur->user_pwd = $_POST['new_pwd'];
                }

                dcCore::app()->admin->user_options['post_format'] = Html::escapeHTML($_POST['user_post_format']);
                dcCore::app()->admin->user_options['edit_size']   = (int) $_POST['user_edit_size'];

                if (dcCore::app()->admin->user_options['edit_size'] < 1) {
                    dcCore::app()->admin->user_options['edit_size'] = 10;
                }

                $cur->user_options = new ArrayObject(dcCore::app()->admin->user_options);

                if (dcCore::app()->admin->user_id) {
                    // Update user

                    # --BEHAVIOR-- adminBeforeUserUpdate -- Cursor, string
                    dcCore::app()->callBehavior('adminBeforeUserUpdate', $cur, dcCore::app()->admin->user_id);

                    $new_id = dcCore::app()->updUser(dcCore::app()->admin->user_id, $cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }
                    $user_prefs = new dcPrefs(dcCore::app()->admin->user_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserUpdate -- Cursor, string
                    dcCore::app()->callBehavior('adminAfterUserUpdate', $cur, $new_id);

                    if (dcCore::app()->admin->user_id == dcCore::app()->auth->userID() && dcCore::app()->admin->user_id != $new_id) {
                        dcCore::app()->session->destroy();
                    }

                    Page::addSuccessNotice(__('User has been successfully updated.'));
                    dcCore::app()->adminurl->redirect('admin.user', ['id' => $new_id]);
                } else {
                    // Add user

                    if (dcCore::app()->getUsers(['user_id' => $cur->user_id], true)->f(0) > 0) {
                        throw new Exception(sprintf(__('User "%s" already exists.'), Html::escapeHTML($cur->user_id)));
                    }

                    # --BEHAVIOR-- adminBeforeUserCreate -- Cursor
                    dcCore::app()->callBehavior('adminBeforeUserCreate', $cur);

                    $new_id = dcCore::app()->addUser($cur);

                    // Update profile
                    // Sanitize list of secondary mails and urls if any
                    $mails = $urls = '';
                    if (!empty($_POST['user_profile_mails'])) {
                        $mails = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_mails'])), FILTER_VALIDATE_EMAIL)));
                    }
                    if (!empty($_POST['user_profile_urls'])) {
                        $urls = implode(',', array_filter(filter_var_array(array_map('trim', explode(',', $_POST['user_profile_urls'])), FILTER_VALIDATE_URL)));
                    }
                    $user_prefs = new dcPrefs($new_id, 'profile');
                    $user_prefs->profile->put('mails', $mails, 'string');
                    $user_prefs->profile->put('urls', $urls, 'string');

                    # --BEHAVIOR-- adminAfterUserCreate -- Cursor, string
                    dcCore::app()->callBehavior('adminAfterUserCreate', $cur, $new_id);

                    Page::addSuccessNotice(__('User has been successfully created.'));
                    Page::addWarningNotice(__('User has no permission, he will not be able to login yet. See below to add some.'));
                    if (!empty($_POST['saveplus'])) {
                        dcCore::app()->adminurl->redirect('admin.user');
                    } else {
                        dcCore::app()->adminurl->redirect('admin.user', ['id' => $new_id]);
                    }
                }
            } catch (Exception $e) {
                dcCore::app()->error->add($e->getMessage());
            }
        }

        return true;
    }

    public static function render(): void
    {
        Page::open(
            dcCore::app()->admin->page_title,
            Page::jsConfirmClose('user-form') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('js/pwstrength.js') .
            Page::jsLoad('js/_user.js') .
            # --BEHAVIOR-- adminUserHeaders --
            dcCore::app()->callBehavior('adminUserHeaders'),
            Page::breadcrumb(
                [
                    __('System')                     => '',
                    __('Users')                      => dcCore::app()->adminurl->get('admin.users'),
                    dcCore::app()->admin->page_title => '',
                ]
            )
        );

        if (!empty($_GET['upd'])) {
            Page::success(__('User has been successfully updated.'));
        }

        if (!empty($_GET['add'])) {
            Page::success(__('User has been successfully created.'));
        }

        echo
        '<form action="' . dcCore::app()->adminurl->get('admin.user') . '" method="post" id="user-form">' .
        '<div class="two-cols">' .

        '<div class="col">' .
        '<h3>' . __('User profile') . '</h3>' .

        '<p><label for="user_id" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('User ID:') . '</label> ' .
        form::field('user_id', 20, 255, [
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_id),
            'extra_html'   => 'required placeholder="' . __('Login') . '" aria-describedby="user_id_help user_id_warning"',
            'autocomplete' => 'username',
        ]) .
        '</p>' .
        '<p class="form-note info" id="user_id_help">' . __('At least 2 characters using letters, numbers or symbols.') . '</p>';

        if (dcCore::app()->admin->user_id == dcCore::app()->auth->userID()) {
            echo
            '<p class="warning" id="user_id_warning">' . __('Warning:') . ' ' .
            __('If you change your username, you will have to log in again.') . '</p>';
        }

        echo
        '<p>' .
        '<label for="new_pwd" ' .
        (dcCore::app()->admin->user_id != '' ? '' : 'class="required"') . '>' .
        (dcCore::app()->admin->user_id != '' ? '' : '<abbr title="' . __('Required field') . '">*</abbr> ') .
        (dcCore::app()->admin->user_id != '' ? __('New password:') : __('Password:')) . '</label>' .
        form::password(
            'new_pwd',
            20,
            255,
            [
                'class'        => 'pw-strength',
                'extra_html'   => (dcCore::app()->admin->user_id != '' ? '' : 'aria-describedby="new_pwd_help" required placeholder="' . __('Password') . '"'),
                'autocomplete' => 'new-password', ]
        ) .
        '</p>' .
        '<p class="form-note info" id="new_pwd_help">' . __('Password must contain at least 6 characters.') . '</p>' .

        '<p><label for="new_pwd_c" ' . (dcCore::app()->admin->user_id != '' ? '' : 'class="required"') . '>' .
        (dcCore::app()->admin->user_id != '' ? '' : '<abbr title="' . __('Required field') . '">*</abbr> ') . __('Confirm password:') . '</label> ' .
        form::password(
            'new_pwd_c',
            20,
            255,
            [
                'extra_html'   => (dcCore::app()->admin->user_id != '' ? '' : 'required placeholder="' . __('Password') . '"'),
                'autocomplete' => 'new-password', ]
        ) .
        '</p>';

        if (dcCore::app()->auth->allowPassChange()) {
            echo
            '<p><label for="user_change_pwd" class="classic">' .
            form::checkbox('user_change_pwd', '1', dcCore::app()->admin->user_change_pwd) . ' ' .
            __('Password change required to connect') . '</label></p>';
        }

        $super_disabled = dcCore::app()->admin->user_super && dcCore::app()->admin->user_id == dcCore::app()->auth->userID();

        echo
        '<p><label for="user_super" class="classic">' .
        form::checkbox(
            ($super_disabled ? 'user_super_off' : 'user_super'),
            '1',
            [
                'checked'  => dcCore::app()->admin->user_super,
                'disabled' => $super_disabled,
            ]
        ) .
        ' ' . __('Super administrator') . '</label></p>' .
        ($super_disabled ? form::hidden(['user_super'], dcCore::app()->admin->user_super) : '') .

        '<p><label for="user_name">' . __('Last Name:') . '</label> ' .
        form::field('user_name', 20, 255, [
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_name),
            'autocomplete' => 'family-name',
        ]) .
        '</p>' .

        '<p><label for="user_firstname">' . __('First Name:') . '</label> ' .
        form::field('user_firstname', 20, 255, [
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_firstname),
            'autocomplete' => 'given-name',
        ]) .
        '</p>' .

        '<p><label for="user_displayname">' . __('Display name:') . '</label> ' .
        form::field('user_displayname', 20, 255, [
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_displayname),
            'autocomplete' => 'nickname',
        ]) .
        '</p>' .

        '<p><label for="user_email">' . __('Email:') . '</label> ' .
        form::email('user_email', [
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_email),
            'extra_html'   => 'aria-describedby="user_email_help"',
            'autocomplete' => 'email',
        ]) .
        '</p>' .
        '<p class="form-note" id="user_email_help">' . __('Mandatory for password recovering procedure.') . '</p>' .

        '<p><label for="user_profile_mails">' . __('Alternate emails (comma separated list):') . '</label>' .
        form::field('user_profile_mails', 50, 255, [
            'default' => Html::escapeHTML(dcCore::app()->admin->user_profile_mails),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_emails">' . __('Invalid emails will be automatically removed from list.') . '</p>' .

        '<p><label for="user_url">' . __('URL:') . '</label> ' .
        form::url('user_url', [
            'size'         => 30,
            'default'      => Html::escapeHTML(dcCore::app()->admin->user_url),
            'autocomplete' => 'url',
        ]) .
        '</p>' .

        '<p><label for="user_profile_urls">' . __('Alternate URLs (comma separated list):') . '</label>' .
        form::field('user_profile_urls', 50, 255, [
            'default' => Html::escapeHTML(dcCore::app()->admin->user_profile_urls),
        ]) .
        '</p>' .
        '<p class="form-note info" id="sanitize_urls">' . __('Invalid URLs will be automatically removed from list.') . '</p>' .

        '</div>' .

        '<div class="col">' .
        '<h3>' . __('Options') . '</h3>' .
        '<h4>' . __('Interface') . '</h4>' .
        '<p><label for="user_lang">' . __('Language:') . '</label> ' .
        form::combo('user_lang', dcCore::app()->admin->lang_combo, dcCore::app()->admin->user_lang, 'l10n') .
        '</p>' .

        '<p><label for="user_tz">' . __('Timezone:') . '</label> ' .
        form::combo('user_tz', Date::getZones(true, true), dcCore::app()->admin->user_tz) .
        '</p>' .

        '<h4>' . __('Edition') . '</h4>' .
        '<p><label for="user_post_format">' . __('Preferred format:') . '</label> ' .
        form::combo('user_post_format', dcCore::app()->admin->formaters_combo, dcCore::app()->admin->user_options['post_format']) .
        '</p>' .

        '<p><label for="user_post_status">' . __('Default entry status:') . '</label> ' .
        form::combo('user_post_status', dcCore::app()->admin->status_combo, dcCore::app()->admin->user_post_status) .
        '</p>' .

        '<p><label for="user_edit_size">' . __('Entry edit field height:') . '</label> ' .
        form::number('user_edit_size', 10, 999, (string) dcCore::app()->admin->user_options['edit_size']) .
        '</p>';

        # --BEHAVIOR-- adminUserForm -- MetaRecord|null
        dcCore::app()->callBehavior('adminUserForm', dcCore::app()->admin->rs ?? null);

        echo
        '</div>' .
        '</div>';

        echo
        '<p class="clear vertical-separator"><label for="your_pwd" class="required">' .
        '<abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label>' .
        form::password(
            'your_pwd',
            20,
            255,
            [
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'current-password',
            ]
        ) . '</p>' .
        '<p class="clear"><input type="submit" name="save" accesskey="s" value="' . __('Save') . '" />' .
        (dcCore::app()->admin->user_id != '' ? '' : ' <input type="submit" name="saveplus" value="' . __('Save and create another') . '" />') .
        (dcCore::app()->admin->user_id != '' ? form::hidden('id', dcCore::app()->admin->user_id) : '') .
        ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
        dcCore::app()->formNonce() .
        '</p>' .

        '</form>';

        if (dcCore::app()->admin->user_id) {
            echo
            '<div class="clear fieldset">' .
            '<h3>' . __('Permissions') . '</h3>';

            if (!dcCore::app()->admin->user_super) {
                echo
                '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post">' .
                '<p><input type="submit" value="' . __('Add new permissions') . '" />' .
                form::hidden(['redir'], dcCore::app()->adminurl->get('admin.user', ['id' => dcCore::app()->admin->user_id])) .
                form::hidden(['action'], 'blogs') .
                form::hidden(['users[]'], dcCore::app()->admin->user_id) .
                dcCore::app()->formNonce() .
                '</p>' .
                '</form>';

                $permissions = dcCore::app()->getUserPermissions(dcCore::app()->admin->user_id);
                $perm_types  = dcCore::app()->auth->getPermissionsTypes();

                if ((is_countable($permissions) ? count($permissions) : 0) == 0) {  // @phpstan-ignore-line
                    echo
                    '<p>' . __('No permissions so far.') . '</p>';
                } else {
                    foreach ($permissions as $k => $v) {
                        if ((is_countable($v['p']) ? count($v['p']) : 0) > 0) {
                            echo
                            '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post" class="perm-block">' .
                            '<p class="blog-perm">' . __('Blog:') . ' <a href="' .
                            dcCore::app()->adminurl->get('admin.blog', ['id' => Html::escapeHTML($k)]) . '">' .
                            Html::escapeHTML($v['name']) . '</a> (' . Html::escapeHTML($k) . ')</p>';

                            echo
                            '<ul class="ul-perm">';
                            foreach ($v['p'] as $p => $V) {
                                if (isset($perm_types[$p])) {
                                    echo
                                    '<li>' . __($perm_types[$p]) . '</li>';
                                }
                            }
                            echo
                            '</ul>' .
                            '<p class="add-perm"><input type="submit" class="reset" value="' . __('Change permissions') . '" />' .
                            form::hidden(['redir'], dcCore::app()->adminurl->get('admin.user', ['id' => dcCore::app()->admin->user_id])) .
                            form::hidden(['action'], 'perms') .
                            form::hidden(['users[]'], dcCore::app()->admin->user_id) .
                            form::hidden(['blogs[]'], $k) .
                            dcCore::app()->formNonce() .
                            '</p>' .
                            '</form>';
                        }
                    }
                }
            } else {
                echo
                '<p>' . sprintf(__('%s is super admin (all rights on all blogs).'), '<strong>' . dcCore::app()->admin->user_id . '</strong>') . '</p>';
            }
            echo
            '</div>';

            // Informations (direct links)
            echo
            '<div class="clear fieldset">' .
            '<h3>' . __('Direct links') . '</h3>' .
            '<p><a href="' . dcCore::app()->adminurl->get(
                'admin.posts',
                ['user_id' => dcCore::app()->admin->user_id]
            ) . '">' . __('List of posts') . '</a></p>';

            if (dcCore::app()->admin->user_email || dcCore::app()->admin->user_url) {
                echo
                '<p><a href="' . dcCore::app()->adminurl->get(
                    'admin.comments',
                    [
                        'email' => dcCore::app()->admin->user_email,
                        'site'  => dcCore::app()->admin->user_url,
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
