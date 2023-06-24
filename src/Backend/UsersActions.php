<?php
/**
 * @since 2.27 Before as admin/users_actions.php
 *
 * @todo Move to dcActions
 *
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Backend;

use dcBlog;
use dcCore;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

class UsersActions extends Process
{
    public static function init(): bool
    {
        Page::checkSuper();

        $users = [];
        if (!empty($_POST['users']) && is_array($_POST['users'])) {
            foreach ($_POST['users'] as $u) {
                if (dcCore::app()->userExists($u)) {
                    $users[] = $u;
                }
            }
        }
        dcCore::app()->admin->users = $users;

        $blogs = [];
        if (!empty($_POST['blogs']) && is_array($_POST['blogs'])) {
            foreach ($_POST['blogs'] as $b) {
                if (dcCore::app()->blogExists($b)) {
                    $blogs[] = $b;
                }
            }
        }
        dcCore::app()->admin->blogs = $blogs;

        return (static::$init = true);
    }

    public static function process(): bool
    {
        dcCore::app()->admin->action = null;
        dcCore::app()->admin->redir  = null;

        if (!empty($_POST['action']) && !empty($_POST['users'])) {
            dcCore::app()->admin->action = $_POST['action'];

            if (isset($_POST['redir']) && strpos($_POST['redir'], '://') === false) {
                dcCore::app()->admin->redir = $_POST['redir'];
            } else {
                dcCore::app()->admin->redir = dcCore::app()->adminurl->get('admin.users', [
                    'q'      => $_POST['q']      ?? '',
                    'sortby' => $_POST['sortby'] ?? '',
                    'order'  => $_POST['order']  ?? '',
                    'page'   => $_POST['page']   ?? '',
                    'nb'     => $_POST['nb']     ?? '',
                ]);
            }

            if (empty(dcCore::app()->admin->users)) {
                dcCore::app()->error->add(__('No blog or user given.'));
            }

            # --BEHAVIOR-- adminUsersActions -- array<int,string>, array<int,string>, string, string
            dcCore::app()->callBehavior('adminUsersActions', dcCore::app()->admin->users, dcCore::app()->admin->blogs, dcCore::app()->admin->action, dcCore::app()->admin->redir);

            if (dcCore::app()->admin->action == 'deleteuser' && !empty(dcCore::app()->admin->users)) {
                // Delete users
                foreach (dcCore::app()->admin->users as $u) {
                    try {
                        if ($u == dcCore::app()->auth->userID()) {
                            throw new Exception(__('You cannot delete yourself.'));
                        }

                        # --BEHAVIOR-- adminBeforeUserDelete -- string
                        dcCore::app()->callBehavior('adminBeforeUserDelete', $u);

                        dcCore::app()->delUser($u);
                    } catch (Exception $e) {
                        dcCore::app()->error->add($e->getMessage());
                    }
                }
                if (!dcCore::app()->error->flag()) {
                    Page::addSuccessNotice(__('User has been successfully deleted.'));
                    Http::redirect(dcCore::app()->admin->redir);
                }
            }

            if (dcCore::app()->admin->action == 'updateperm' && !empty(dcCore::app()->admin->users) && !empty(dcCore::app()->admin->blogs)) {
                // Update users perms
                try {
                    if (empty($_POST['your_pwd']) || !dcCore::app()->auth->checkPassword($_POST['your_pwd'])) {
                        throw new Exception(__('Password verification failed'));
                    }

                    foreach (dcCore::app()->admin->users as $u) {
                        foreach (dcCore::app()->admin->blogs as $b) {
                            $set_perms = [];

                            if (!empty($_POST['perm'][$b])) {
                                foreach ($_POST['perm'][$b] as $perm_id => $v) {
                                    if ($v) {
                                        $set_perms[$perm_id] = true;
                                    }
                                }
                            }

                            dcCore::app()->setUserBlogPermissions($u, $b, $set_perms, true);
                        }
                    }
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                }
                if (!dcCore::app()->error->flag()) {
                    Page::addSuccessNotice(__('User has been successfully updated.'));
                    Http::redirect(dcCore::app()->admin->redir);
                }
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!empty(dcCore::app()->admin->users) && empty(dcCore::app()->admin->blogs) && dcCore::app()->admin->action == 'blogs') {
            $breadcrumb = Page::breadcrumb(
                [
                    __('System')      => '',
                    __('Users')       => dcCore::app()->adminurl->get('admin.users'),
                    __('Permissions') => '',
                ]
            );
        } else {
            $breadcrumb = Page::breadcrumb(
                [
                    __('System')  => '',
                    __('Users')   => dcCore::app()->adminurl->get('admin.users'),
                    __('Actions') => '',
                ]
            );
        }

        Page::open(
            __('Users'),
            Page::jsLoad('js/_users_actions.js') .
            # --BEHAVIOR-- adminUsersActionsHeaders --
            dcCore::app()->callBehavior('adminUsersActionsHeaders'),
            $breadcrumb
        );

        if (!isset(dcCore::app()->admin->action)) {
            Page::close();
            exit;
        }

        $hidden_fields = '';
        foreach (dcCore::app()->admin->users as $u) {
            $hidden_fields .= form::hidden(['users[]'], $u);
        }

        if (isset($_POST['redir']) && strpos($_POST['redir'], '://') === false) {
            $hidden_fields .= form::hidden(['redir'], Html::escapeURL($_POST['redir']));
        } else {
            $hidden_fields .= form::hidden(['q'], Html::escapeHTML($_POST['q'] ?? '')) .
                form::hidden(['sortby'], $_POST['sortby'] ?? '') .
                form::hidden(['order'], $_POST['order'] ?? '') .
                form::hidden(['page'], $_POST['page'] ?? '') .
                form::hidden(['nb'], $_POST['nb'] ?? '');
        }

        echo
        '<p><a class="back" href="' . Html::escapeURL(dcCore::app()->admin->redir) . '">' . __('Back to user profile') . '</a></p>';

        # --BEHAVIOR-- adminUsersActionsContent -- string, string
        dcCore::app()->callBehavior('adminUsersActionsContentV2', dcCore::app()->admin->action, $hidden_fields);

        if (!empty(dcCore::app()->admin->users) && empty(dcCore::app()->admin->blogs) && dcCore::app()->admin->action == 'blogs') {
            // Blog list where to set permissions

            $rs      = null;
            $nb_blog = 0;

            try {
                $rs      = dcCore::app()->getBlogs();
                $nb_blog = $rs->count();
            } catch (Exception $e) {
                // Ignore exceptions
            }

            $user_list = [];
            foreach (dcCore::app()->admin->users as $u) {
                $user_list[] = '<a href="' . dcCore::app()->adminurl->get('admin.user', ['id' => $u]) . '">' . $u . '</a>';
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
                '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post" id="form-blogs">' .
                '<div class="table-outer clear">' .
                '<table><tr>' .
                '<th class="nowrap" colspan="2">' . __('Blog ID') . '</th>' .
                '<th class="nowrap">' . __('Blog name') . '</th>' .
                '<th class="nowrap">' . __('URL') . '</th>' .
                '<th class="nowrap">' . __('Entries') . '</th>' .
                '<th class="nowrap">' . __('Status') . '</th>' .
                '</tr>';

                while ($rs->fetch()) {
                    $img_status = $rs->blog_status == dcBlog::BLOG_ONLINE ? 'check-on' : ($rs->blog_status == dcBlog::BLOG_OFFLINE ? 'check-off' : 'check-wrn');
                    $txt_status = dcCore::app()->getBlogStatus(is_numeric($rs->blog_status) ? (int) $rs->blog_status : dcBlog::BLOG_ONLINE);
                    $img_status = sprintf('<img src="images/%1$s.png" alt="%2$s" title="%2$s" />', $img_status, $txt_status);

                    echo
                    '<tr class="line">' .
                    '<td class="nowrap">' .
                    form::checkbox(
                        ['blogs[]'],
                        $rs->blog_id,
                        [
                            'extra_html' => 'title="' . __('select') . ' ' . $rs->blog_id . '"',
                        ]
                    ) .
                    '</td>' .
                    '<td class="nowrap">' . $rs->blog_id . '</td>' .
                    '<td class="maximal">' . Html::escapeHTML($rs->blog_name) . '</td>' .
                    '<td class="nowrap"><a class="outgoing" href="' . Html::escapeHTML($rs->blog_url) . '">' . Html::escapeHTML($rs->blog_url) .
                    ' <img src="images/outgoing-link.svg" alt="" /></a></td>' .
                    '<td class="nowrap">' . dcCore::app()->countBlogPosts($rs->blog_id) . '</td>' .
                    '<td class="status">' . $img_status . '</td>' .
                    '</tr>';
                }

                echo
                '</table></div>' .
                '<p class="checkboxes-helpers"></p>' .
                '<p><input id="do-action" type="submit" value="' . __('Set permissions') . '" />' .
                $hidden_fields .
                form::hidden(['action'], 'perms') .
                dcCore::app()->formNonce() . '</p>' .
                '</form>';
            }
        } elseif (!empty(dcCore::app()->admin->blogs) && !empty(dcCore::app()->admin->users) && dcCore::app()->admin->action == 'perms') {
            // Permissions list for each selected blogs

            $user_perm = [];
            if ((is_countable(dcCore::app()->admin->users) ? count(dcCore::app()->admin->users) : 0) == 1) {
                $user_perm = dcCore::app()->getUserPermissions(dcCore::app()->admin->users[0]);
            }

            $user_list = [];
            foreach (dcCore::app()->admin->users as $u) {
                $user_list[] = '<a href="' . dcCore::app()->adminurl->get('admin.user', ['id' => $u]) . '">' . $u . '</a>';
            }

            echo
            '<p>' . sprintf(
                __('You are about to change permissions on the following blogs for users %s.'),
                implode(', ', $user_list)
            ) . '</p>' .
            '<form id="permissions-form" action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post">';

            foreach (dcCore::app()->admin->blogs as $b) {
                echo
                '<h3>' . ('Blog:') . ' <a href="' . dcCore::app()->adminurl->get('admin.blog', ['id' => Html::escapeHTML($b)]) . '">' . Html::escapeHTML($b) . '</a>' .
                form::hidden(['blogs[]'], $b) . '</h3>';
                $unknown_perms = $user_perm;
                foreach (dcCore::app()->auth->getPermissionsTypes() as $perm_id => $perm) {
                    $checked = false;

                    if ((is_countable(dcCore::app()->admin->users) ? count(dcCore::app()->admin->users) : 0) == 1) {
                        $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
                    }
                    if (isset($unknown_perms[$b]['p'][$perm_id])) {
                        unset($unknown_perms[$b]['p'][$perm_id]);
                    }

                    echo
                    '<p><label for="perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id) . '" class="classic">' .
                    form::checkbox(
                        ['perm[' . Html::escapeHTML($b) . '][' . Html::escapeHTML($perm_id) . ']', 'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id)],
                        1,
                        $checked
                    ) . ' ' .
                    __($perm) . '</label></p>';
                }
                if (isset($unknown_perms[$b])) {
                    foreach ($unknown_perms[$b]['p'] as $perm_id => $v) {
                        $checked = isset($user_perm[$b]['p'][$perm_id]) && $user_perm[$b]['p'][$perm_id];
                        echo
                        '<p><label for="perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id) . '" class="classic">' .
                        form::checkbox(
                            ['perm[' . Html::escapeHTML($b) . '][' . Html::escapeHTML($perm_id) . ']',
                                'perm' . Html::escapeHTML($b) . Html::escapeHTML($perm_id), ],
                            1,
                            $checked
                        ) . ' ' .
                        sprintf(__('[%s] (unreferenced permission)'), $perm_id) . '</label></p>';
                    }
                }
            }

            echo
            '<p class="checkboxes-helpers"></p>' .
            '<div class="fieldset">' .
            '<h3>' . __('Validate permissions') . '</h3>' .
            '<p><label for="your_pwd" class="required"><abbr title="' . __('Required field') . '">*</abbr> ' . __('Your password:') . '</label>' .
            form::password(
                'your_pwd',
                20,
                255,
                [
                    'extra_html'   => 'required placeholder="' . __('Password') . '"',
                    'autocomplete' => 'current-password',
                ]
            ) . '</p>' .
            '<p><input type="submit" accesskey="s" value="' . __('Save') . '" />' .
            $hidden_fields .
            form::hidden(['action'], 'updateperm') .
            dcCore::app()->formNonce() . '</p>' .
            '</div>' .
            '</form>';
        }

        Page::helpBlock('core_users');
        Page::close();
    }
}
