<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

dcPage::checkSuper();

/* Actions
-------------------------------------------------------- */
$combo_action = [
    __('Set permissions') => 'blogs',
    __('Delete')          => 'deleteuser',
];

# --BEHAVIOR-- adminUsersActionsCombo
dcCore::app()->callBehavior('adminUsersActionsCombo', [& $combo_action]);

/* Filters
-------------------------------------------------------- */
$user_filter = new adminUserFilter();

# get list params
$params = $user_filter->params();

# lexical sort
$sortby_lex = [
    // key in sorty_combo (see above) => field in SQL request
    'user_id'          => 'U.user_id',
    'user_name'        => 'user_name',
    'user_firstname'   => 'user_firstname',
    'user_displayname' => 'user_displayname', ];

# --BEHAVIOR-- adminUsersSortbyLexCombo
dcCore::app()->callBehavior('adminUsersSortbyLexCombo', [& $sortby_lex]);

$params['order'] = (array_key_exists($user_filter->sortby, $sortby_lex) ?
    dcCore::app()->con->lexFields($sortby_lex[$user_filter->sortby]) :
    $user_filter->sortby) . ' ' . $user_filter->order;

/* List
-------------------------------------------------------- */
$user_list = null;

try {
    # --BEHAVIOR-- adminGetUsers
    $params = new ArrayObject($params);
    dcCore::app()->callBehavior('adminGetUsers', $params);

    $rs       = dcCore::app()->getUsers($params);
    $counter  = dcCore::app()->getUsers($params, true);
    $rsStatic = $rs->toStatic();
    if ($user_filter->sortby != 'nb_post') {
        // Sort user list using lexical order if necessary
        $rsStatic->extend('rsExtUser');
        $rsStatic = $rsStatic->toExtStatic();
        $rsStatic->lexicalSort($user_filter->sortby, $user_filter->order);
    }
    $user_list = new adminUserList($rsStatic, $counter->f(0));
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(
    __('Users'),
    dcPage::jsLoad('js/_users.js') . $user_filter->js(),
    dcPage::breadcrumb(
        [
            __('System') => '',
            __('Users')  => '',
        ]
    )
);

if (!dcCore::app()->error->flag()) {
    if (!empty($_GET['del'])) {
        dcPage::message(__('User has been successfully removed.'));
    }
    if (!empty($_GET['upd'])) {
        dcPage::message(__('The permissions have been successfully updated.'));
    }

    echo '<p class="top-add"><strong><a class="button add" href="' . dcCore::app()->adminurl->get('admin.user') . '">' . __('New user') . '</a></strong></p>';

    $user_filter->display('admin.users');

    # Show users
    $user_list->display(
        $user_filter->page,
        $user_filter->nb,
        '<form action="' . dcCore::app()->adminurl->get('admin.user.actions') . '" method="post" id="form-users">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' .
        __('Selected users action:') . ' ' .
        form::combo('action', $combo_action) .
        '</label> ' .
        '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
        dcCore::app()->adminurl->getHiddenFormFields('admin.users', $user_filter->values(true)) .
        dcCore::app()->formNonce() .
        '</p>' .
        '</div>' .
        '</form>',
        $user_filter->show()
    );
}
dcPage::helpBlock('core_users');
dcPage::close();
