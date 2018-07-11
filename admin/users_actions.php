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

$users = array();
if (!empty($_POST['users']) && is_array($_POST['users'])) {
    foreach ($_POST['users'] as $u) {
        if ($core->userExists($u)) {
            $users[] = $u;
        }
    }
}

$blogs = array();
if (!empty($_POST['blogs']) && is_array($_POST['blogs'])) {
    foreach ($_POST['blogs'] as $b) {
        if ($core->blogExists($b)) {
            $blogs[] = $b;
        }
    }
}

/* Actions
-------------------------------------------------------- */
if (!empty($_POST['action']) && !empty($_POST['users'])) {
    $action = $_POST['action'];

    if (isset($_POST['redir']) && strpos($_POST['redir'], '://') === false) {
        $redir = $_POST['redir'];
    } else {
        $redir = $core->adminurl->get("admin.users", array(
            'q'      => $_POST['q'],
            'sortby' => $_POST['sortby'],
            'order'  => $_POST['order'],
            'page'   => $_POST['page'],
            'nb'     => $_POST['nb']
        ));
    }

    if (empty($users)) {
        $core->error->add(__('No blog or user given.'));
    }

    # --BEHAVIOR-- adminUsersActions
    $core->callBehavior('adminUsersActions', $core, $users, $blogs, $action, $redir);

    # Delete users
    if ($action == 'deleteuser' && !empty($users)) {
        foreach ($users as $u) {
            try
            {
                if ($u == $core->auth->userID()) {
                    throw new Exception(__('You cannot delete yourself.'));
                }

                # --BEHAVIOR-- adminBeforeUserDelete
                $core->callBehavior('adminBeforeUserDelete', $u);

                $core->delUser($u);
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }
        if (!$core->error->flag()) {
            dcPage::addSuccessNotice(__('User has been successfully deleted.'));
            http::redirect($redir);
        }
    }

    # Update users perms
    if ($action == 'updateperm' && !empty($users) && !empty($blogs)) {
        try
        {
            if (empty($_POST['your_pwd']) || !$core->auth->checkPassword($_POST['your_pwd'])) {
                throw new Exception(__('Password verification failed'));
            }

            foreach ($users as $u) {
                foreach ($blogs as $b) {
                    $set_perms = array();

                    if (!empty($_POST['perm'][$b])) {
                        foreach ($_POST['perm'][$b] as $perm_id => $v) {
                            if ($v) {
                                $set_perms[$perm_id] = true;
                            }
                        }
                    }

                    $core->setUserBlogPermissions($u, $b, $set_perms, true);
                }
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
        if (!$core->error->flag()) {
            dcPage::addSuccessNotice(__('User has been successfully updated.'));
            http::redirect($redir);
        }
    }
}

/* DISPLAY
-------------------------------------------------------- */
if (!empty($users) && empty($blogs) && $action == 'blogs') {
    $breadcrumb = dcPage::breadcrumb(
        array(
            __('System')      => '',
            __('Users')       => $core->adminurl->get("admin.users"),
            __('Permissions') => ''
        ));
} else {
    $breadcrumb = dcPage::breadcrumb(
        array(
            __('System')  => '',
            __('Users')   => $core->adminurl->get("admin.users"),
            __('Actions') => ''
        ));
}

dcPage::open(
    __('Users'),
    dcPage::jsLoad('js/_users_actions.js') .
    # --BEHAVIOR-- adminUsersActionsHeaders
    $core->callBehavior('adminUsersActionsHeaders'),
    $breadcrumb
);

if (!isset($action)) {
    dcPage::close();
    exit;
}

$hidden_fields = '';
foreach ($users as $u) {
    $hidden_fields .= form::hidden(array('users[]'), $u);
}

if (isset($_POST['redir']) && strpos($_POST['redir'], '://') === false) {
    $hidden_fields .= form::hidden(array('redir'), html::escapeURL($_POST['redir']));
} else {
    $hidden_fields .=
    form::hidden(array('q'), html::escapeHTML($_POST['q'])) .
    form::hidden(array('sortby'), $_POST['sortby']) .
    form::hidden(array('order'), $_POST['order']) .
    form::hidden(array('page'), $_POST['page']) .
    form::hidden(array('nb'), $_POST['nb']);
}

echo '<p><a class="back" href="' . html::escapeURL($redir) . '">' . __('Back to user profile') . '</a></p>';

# --BEHAVIOR-- adminUsersActionsContent
$core->callBehavior('adminUsersActionsContent', $core, $action, $hidden_fields);

# Blog list where to set permissions
if (!empty($users) && empty($blogs) && $action == 'blogs') {
    try {
        $rs      = $core->getBlogs();
        $nb_blog = $rs->count();
    } catch (Exception $e) {}

    foreach ($users as $u) {
        $user_list[] = '<a href="' . $core->adminurl->get("admin.user", array('id' => $u)) . '">' . $u . '</a>';
    }

    echo
    '<p>' . sprintf(
        __('Choose one or more blogs to which you want to give permissions to users %s.'),
        implode(', ', $user_list)
    ) . '</p>';

    if ($nb_blog == 0) {
        echo '<p><strong>' . __('No blog') . '</strong></p>';
    } else {
        echo
        '<form action="' . $core->adminurl->get("admin.user.actions") . '" method="post" id="form-blogs">' .
        '<div class="table-outer clear">' .
        '<table><tr>' .
        '<th class="nowrap" colspan="2">' . __('Blog ID') . '</th>' .
        '<th class="nowrap">' . __('Blog name') . '</th>' .
        '<th class="nowrap">' . __('URL') . '</th>' .
        '<th class="nowrap">' . __('Entries') . '</th>' .
        '<th class="nowrap">' . __('Status') . '</th>' .
            '</tr>';

        while ($rs->fetch()) {
            $img_status = $rs->blog_status == 1 ? 'check-on' : ($rs->blog_status == 0 ? 'check-off' : 'check-wrn');
            $txt_status = $core->getBlogStatus($rs->blog_status);
            $img_status = sprintf('<img src="images/%1$s.png" alt="%2$s" title="%2$s" />', $img_status, $txt_status);

            echo
            '<tr class="line">' .
            '<td class="nowrap">' .
            form::checkbox(array('blogs[]'), $rs->blog_id,
                array(
                    'extra_html' => 'title="' . __('select') . ' ' . $rs->blog_id . '"'
                )) .
            '</td>' .
            '<td class="nowrap">' . $rs->blog_id . '</td>' .
            '<td class="maximal">' . html::escapeHTML($rs->blog_name) . '</td>' .
            '<td class="nowrap"><a class="outgoing" href="' . html::escapeHTML($rs->blog_url) . '">' . html::escapeHTML($rs->blog_url) .
            ' <img src="images/outgoing-link.svg" alt="" /></a></td>' .
            '<td class="nowrap">' . $core->countBlogPosts($rs->blog_id) . '</td>' .
                '<td class="status">' . $img_status . '</td>' .
                '</tr>';
        }

        echo
        '</table></div>' .
        '<p class="checkboxes-helpers"></p>' .
        '<p><input id="do-action" type="submit" value="' . __('Set permissions') . '" />' .
        $hidden_fields .
        form::hidden(array('action'), 'perms') .
        $core->formNonce() . '</p>' .
            '</form>';
    }
}
# Permissions list for each selected blogs
elseif (!empty($blogs) && !empty($users) && $action == 'perms') {
    $user_perm = array();
    if (count($users) == 1) {
        $user_perm = $core->getUserPermissions($users[0]);
    }

    foreach ($users as $u) {
        $user_list[] = '<a href="' . $core->adminurl->get("admin.user", array('id' => $u)) . '">' . $u . '</a>';
    }

    echo
    '<p>' . sprintf(
        __('You are about to change permissions on the following blogs for users %s.'),
        implode(', ', $user_list)
    ) . '</p>' .
    '<form id="permissions-form" action="' . $core->adminurl->get("admin.user.actions") . '" method="post">';

    foreach ($blogs as $b) {
        echo '<h3>' . ('Blog:') . ' <a href="' . $core->adminurl->get("admin.blog", array('id' => html::escapeHTML($b))) . '">' . html::escapeHTML($b) . '</a>' .
        form::hidden(array('blogs[]'), $b) . '</h3>';
        $unknown_perms = $user_perm;
        foreach ($core->auth->getPermissionsTypes() as $perm_id => $perm) {
            $checked = false;

            if (count($users) == 1) {
                $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
            }
            if (isset($unknown_perms[$b]['p'][$perm_id])) {
                unset($unknown_perms[$b]['p'][$perm_id]);
            }

            echo
            '<p><label for="perm' . html::escapeHTML($b) . html::escapeHTML($perm_id) . '" class="classic">' .
            form::checkbox(array('perm[' . html::escapeHTML($b) . '][' . html::escapeHTML($perm_id) . ']', 'perm' . html::escapeHTML($b) . html::escapeHTML($perm_id)),
                1, $checked) . ' ' .
            __($perm) . '</label></p>';
        }
        if (isset($unknown_perms[$b])) {

            foreach ($unknown_perms[$b]['p'] as $perm_id => $v) {
                $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
                echo
                '<p><label for="perm' . html::escapeHTML($b) . html::escapeHTML($perm_id) . '" class="classic">' .
                form::checkbox(
                    array('perm[' . html::escapeHTML($b) . '][' . html::escapeHTML($perm_id) . ']',
                        'perm' . html::escapeHTML($b) . html::escapeHTML($perm_id)),
                    1, $checked) . ' ' .
                sprintf(__('[%s] (unreferenced permission)'), $perm_id) . '</label></p>';
            }
        }
    }

    echo
    '<p class="checkboxes-helpers"></p>' .
    '<div class="fieldset">' .
    '<h3>' . __('Validate permissions') . '</h3>' .
    '<p><label for="your_pwd" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label>' .
    form::password('your_pwd', 20, 255,
        array(
            'extra_html'   => 'required placeholder="' . __('Password') . '"',
            'autocomplete' => 'current-password'
        )
    ) . '</p>' .
    '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
    $hidden_fields .
    form::hidden(array('action'), 'updateperm') .
    $core->formNonce() . '</p>' .
        '</div>' .
        '</form>';
}

dcPage::helpBlock('core_users');
dcPage::close();
