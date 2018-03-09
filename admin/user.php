<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::checkSuper();

$page_title = __('New user');

$user_id          = '';
$user_super       = '';
$user_pwd         = '';
$user_change_pwd  = '';
$user_name        = '';
$user_firstname   = '';
$user_displayname = '';
$user_email       = '';
$user_url         = '';
$user_lang        = $core->auth->getInfo('user_lang');
$user_tz          = $core->auth->getInfo('user_tz');
$user_post_status = '';

$user_options = $core->userDefaults();

# Formaters combo
$formaters_combo = dcAdminCombos::getFormatersCombo();

$status_combo = dcAdminCombos::getPostStatusesCombo();

# Language codes
$lang_combo = dcAdminCombos::getAdminLangsCombo();

# Get user if we have an ID
if (!empty($_REQUEST['id'])) {
    try {
        $rs = $core->getUser($_REQUEST['id']);

        $user_id          = $rs->user_id;
        $user_super       = $rs->user_super;
        $user_pwd         = $rs->user_pwd;
        $user_change_pwd  = $rs->user_change_pwd;
        $user_name        = $rs->user_name;
        $user_firstname   = $rs->user_firstname;
        $user_displayname = $rs->user_displayname;
        $user_email       = $rs->user_email;
        $user_url         = $rs->user_url;
        $user_lang        = $rs->user_lang;
        $user_tz          = $rs->user_tz;
        $user_post_status = $rs->user_post_status;

        $user_options = array_merge($user_options, $rs->options());

        $page_title = $user_id;
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Add or update user
if (isset($_POST['user_name'])) {
    try
    {
        if (empty($_POST['your_pwd']) || !$core->auth->checkPassword($_POST['your_pwd'])) {
            throw new Exception(__('Password verification failed'));
        }

        $cur = $core->con->openCursor($core->prefix . 'user');

        $cur->user_id          = $_POST['user_id'];
        $cur->user_super       = $user_super       = !empty($_POST['user_super']) ? 1 : 0;
        $cur->user_name        = $user_name        = html::escapeHTML($_POST['user_name']);
        $cur->user_firstname   = $user_firstname   = html::escapeHTML($_POST['user_firstname']);
        $cur->user_displayname = $user_displayname = html::escapeHTML($_POST['user_displayname']);
        $cur->user_email       = $user_email       = html::escapeHTML($_POST['user_email']);
        $cur->user_url         = $user_url         = html::escapeHTML($_POST['user_url']);
        $cur->user_lang        = $user_lang        = html::escapeHTML($_POST['user_lang']);
        $cur->user_tz          = $user_tz          = html::escapeHTML($_POST['user_tz']);
        $cur->user_post_status = $user_post_status = html::escapeHTML($_POST['user_post_status']);

        if ($user_id && $cur->user_id == $core->auth->userID() && $core->auth->isSuperAdmin()) {
            // force super_user to true if current user
            $cur->user_super = $user_super = true;
        }
        if ($core->auth->allowPassChange()) {
            $cur->user_change_pwd = !empty($_POST['user_change_pwd']) ? 1 : 0;
        }

        if (!empty($_POST['new_pwd'])) {
            if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                throw new Exception(__("Passwords don't match"));
            } else {
                $cur->user_pwd = $_POST['new_pwd'];
            }
        }

        $user_options['post_format'] = html::escapeHTML($_POST['user_post_format']);
        $user_options['edit_size']   = (integer) $_POST['user_edit_size'];

        if ($user_options['edit_size'] < 1) {
            $user_options['edit_size'] = 10;
        }

        $cur->user_options = new ArrayObject($user_options);

        # Udate user
        if ($user_id) {
            # --BEHAVIOR-- adminBeforeUserUpdate
            $core->callBehavior('adminBeforeUserUpdate', $cur, $user_id);

            $new_id = $core->updUser($user_id, $cur);

            # --BEHAVIOR-- adminAfterUserUpdate
            $core->callBehavior('adminAfterUserUpdate', $cur, $new_id);

            if ($user_id == $core->auth->userID() &&
                $user_id != $new_id) {
                $core->session->destroy();
            }

            dcPage::addSuccessNotice(__('User has been successfully updated.'));
            $core->adminurl->redirect("admin.user", array('id' => $new_id));
        }
        # Add user
        else {
            if ($core->getUsers(array('user_id' => $cur->user_id), true)->f(0) > 0) {
                throw new Exception(sprintf(__('User "%s" already exists.'), html::escapeHTML($cur->user_id)));
            }

            # --BEHAVIOR-- adminBeforeUserCreate
            $core->callBehavior('adminBeforeUserCreate', $cur);

            $new_id = $core->addUser($cur);

            # --BEHAVIOR-- adminAfterUserCreate
            $core->callBehavior('adminAfterUserCreate', $cur, $new_id);

            dcPage::addSuccessNotice(__('User has been successfully created.'));
            if (!empty($_POST['saveplus'])) {
                $core->adminurl->redirect("admin.user");
            } else {
                $core->adminurl->redirect("admin.user", array('id' => $new_id));
            }
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open($page_title,
    dcPage::jsConfirmClose('user-form') .
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

    # --BEHAVIOR-- adminUserHeaders
    $core->callBehavior('adminUserHeaders'),

    dcPage::breadcrumb(
        array(
            __('System') => '',
            __('Users')  => $core->adminurl->get("admin.users"),
            $page_title  => ''
        ))
);

if (!empty($_GET['upd'])) {
    dcPage::success(__('User has been successfully updated.'));
}

if (!empty($_GET['add'])) {
    dcPage::success(__('User has been successfully created.'));
}

echo
'<form action="' . $core->adminurl->get("admin.user") . '" method="post" id="user-form">' .
'<div class="two-cols">' .

'<div class="col">' .
'<h3>' . __('User profile') . '</h3>' .

'<p><label for="user_id" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('User ID:') . '</label> ' .
form::field('user_id', 20, 255, array(
    'default'      => html::escapeHTML($user_id),
    'extra_html'   => 'required placeholder="' . __('Login') . '"',
    'autocomplete' => 'username'
)) .
'</p>' .
'<p class="form-note info">' . __('At least 2 characters using letters, numbers or symbols.') . '</p>';

if ($user_id == $core->auth->userID()) {
    echo
    '<p class="warning">' . __('Warning:') . ' ' .
    __('If you change your username, you will have to log in again.') . '</p>';
}

echo
'<div class="pw-table">' .
'<p class="pw-cell">' .
'<label for="new_pwd" ' . ($user_id != '' ? '' : 'class="required"') . '>' .
($user_id != '' ? '' : '<abbr title="' . __('Required field') . '">*</abbr> ') .
($user_id != '' ? __('New password:') : __('Password:')) . '</label>' .
form::password('new_pwd', 20, 255,
    array(
        'extra_html'   => 'data-indicator="pwindicator"' .
        ($user_id != '' ? '' : ' required placeholder="' . __('Password') . '"'),
        'autocomplete' => 'new-password')
) .
'</p>' .
'<div id="pwindicator">' .
'    <div class="bar"></div>' .
'    <p class="label no-margin"></p>' .
'</div>' .
'</div>' .
'<p class="form-note info">' . __('Password must contain at least 6 characters.') . '</p>' .

'<p><label for="new_pwd_c" ' . ($user_id != '' ? '' : 'class="required"') . '>' .
($user_id != '' ? '' : '<abbr title="' . __('Required field') . '">*</abbr> ') . __('Confirm password:') . '</label> ' .
form::password('new_pwd_c', 20, 255,
    array(
        'extra_html'   => ($user_id != '' ? '' : 'required placeholder="' . __('Password') . '"'),
        'autocomplete' => 'new-password')) .
    '</p>';

if ($core->auth->allowPassChange()) {
    echo
    '<p><label for="user_change_pwd" class="classic">' .
    form::checkbox('user_change_pwd', '1', $user_change_pwd) . ' ' .
    __('Password change required to connect') . '</label></p>';
}

$super_disabled = $user_super && $user_id == $core->auth->userID();

echo
'<p><label for="user_super" class="classic">' .
form::checkbox(($super_disabled ? 'user_super_off' : 'user_super'), '1',
    array(
        'checked'  => $user_super,
        'disabled' => $super_disabled
    )) .
' ' . __('Super administrator') . '</label></p>' .
($super_disabled ? form::hidden(array('user_super'), $user_super) : '') .

'<p><label for="user_name">' . __('Last Name:') . '</label> ' .
form::field('user_name', 20, 255, array(
    'default'      => html::escapeHTML($user_name),
    'autocomplete' => 'family-name'
)) .
'</p>' .

'<p><label for="user_firstname">' . __('First Name:') . '</label> ' .
form::field('user_firstname', 20, 255, array(
    'default'      => html::escapeHTML($user_firstname),
    'autocomplete' => 'given-name'
)) .
'</p>' .

'<p><label for="user_displayname">' . __('Display name:') . '</label> ' .
form::field('user_displayname', 20, 255, array(
    'default'      => html::escapeHTML($user_displayname),
    'autocomplete' => 'nickname'
)) .
'</p>' .

'<p><label for="user_email">' . __('Email:') . '</label> ' .
form::email('user_email', array(
    'default'      => html::escapeHTML($user_email),
    'autocomplete' => 'email'
)) .
'</p>' .
'<p class="form-note">' . __('Mandatory for password recovering procedure.') . '</p>' .

'<p><label for="user_url">' . __('URL:') . '</label> ' .
form::url('user_url', array(
    'size'         => 30,
    'default'      => html::escapeHTML($user_url),
    'autocomplete' => 'url'
)) .
'</p>' .
'</div>' .

'<div class="col">' .
'<h3>' . __('Options') . '</h3>' .
'<h4>' . __('Interface') . '</h4>' .
'<p><label for="user_lang">' . __('Language:') . '</label> ' .
form::combo('user_lang', $lang_combo, $user_lang, 'l10n') .
'</p>' .

'<p><label for="user_tz">' . __('Timezone:') . '</label> ' .
form::combo('user_tz', dt::getZones(true, true), $user_tz) .
'</p>' .

'<h4>' . __('Edition') . '</h4>' .
'<p><label for="user_post_format">' . __('Preferred format:') . '</label> ' .
form::combo('user_post_format', $formaters_combo, $user_options['post_format']) .
'</p>' .

'<p><label for="user_post_status">' . __('Default entry status:') . '</label> ' .
form::combo('user_post_status', $status_combo, $user_post_status) .
'</p>' .

'<p><label for="user_edit_size">' . __('Entry edit field height:') . '</label> ' .
form::number('user_edit_size', 10, 999, (integer) $user_options['edit_size']) .
    '</p>';

# --BEHAVIOR-- adminUserForm
$core->callBehavior('adminUserForm', isset($rs) ? $rs : null);

echo
    '</div>' .
    '</div>';

echo
'<p class="clear vertical-separator"><label for="your_pwd" class="required">' .
'<abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label>' .
form::password('your_pwd', 20, 255,
    array(
        'extra_html'   => 'required placeholder="' . __('Password') . '"',
        'autocomplete' => 'current-password'
    )
) . '</p>' .
'<p class="clear"><input type="submit" name="save" accesskey="s" value="' . __('Save') . '" />' .
($user_id != '' ? '' : ' <input type="submit" name="saveplus" value="' . __('Save and create another') . '" />') .
($user_id != '' ? form::hidden('id', $user_id) : '') .
$core->formNonce() .
    '</p>' .

    '</form>';

if ($user_id) {
    echo '<div class="clear fieldset">' .
    '<h3>' . __('Permissions') . '</h3>';

    if (!$user_super) {
        echo
        '<form action="' . $core->adminurl->get("admin.user.actions") . '" method="post">' .
        '<p><input type="submit" value="' . __('Add new permissions') . '" />' .
        form::hidden(array('redir'), $core->adminurl->get("admin.user", array('id' => $user_id))) .
        form::hidden(array('action'), 'blogs') .
        form::hidden(array('users[]'), $user_id) .
        $core->formNonce() .
            '</p>' .
            '</form>';

        $permissions = $core->getUserPermissions($user_id);
        $perm_types  = $core->auth->getPermissionsTypes();

        if (count($permissions) == 0) {
            echo '<p>' . __('No permissions so far.') . '</p>';
        } else {
            foreach ($permissions as $k => $v) {
                if (count($v['p']) > 0) {
                    echo
                    '<form action="' . $core->adminurl->get("admin.user.actions") . '" method="post" class="perm-block">' .
                    '<p class="blog-perm">' . __('Blog:') . ' <a href="' .
                    $core->adminurl->get("admin.blog", array('id' => html::escapeHTML($k))) . '">' .
                    html::escapeHTML($v['name']) . '</a> (' . html::escapeHTML($k) . ')</p>';

                    echo '<ul class="ul-perm">';
                    foreach ($v['p'] as $p => $V) {
                        if (isset($perm_types[$p])) {
                            echo '<li>' . __($perm_types[$p]) . '</li>';
                        }
                    }
                    echo
                    '</ul>' .
                    '<p class="add-perm"><input type="submit" class="reset" value="' . __('Change permissions') . '" />' .
                    form::hidden(array('redir'), $core->adminurl->get("admin.user", array('id' => $user_id))) .
                    form::hidden(array('action'), 'perms') .
                    form::hidden(array('users[]'), $user_id) .
                    form::hidden(array('blogs[]'), $k) .
                    $core->formNonce() .
                        '</p>' .
                        '</form>';
                }
            }
        }

    } else {
        echo '<p>' . sprintf(__('%s is super admin (all rights on all blogs).'), '<strong>' . $user_id . '</strong>') . '</p>';
    }
    echo '</div>';
}

dcPage::helpBlock('core_user');
dcPage::close();
