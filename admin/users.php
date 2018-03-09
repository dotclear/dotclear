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

# Creating filter combo boxes
$sortby_combo = array(
    __('Username')          => 'user_id',
    __('Last Name')         => 'user_name',
    __('First Name')        => 'user_firstname',
    __('Display name')      => 'user_displayname',
    __('Number of entries') => 'nb_post'
);

$sortby_lex = array(
    // key in sorty_combo (see above) => field in SQL request
    'user_id'          => 'U.user_id',
    'user_name'        => 'user_name',
    'user_firstname'   => 'user_firstname',
    'user_displayname' => 'user_displayname');

$order_combo = array(
    __('Descending') => 'desc',
    __('Ascending')  => 'asc'
);

# Actions combo box
$combo_action = array(
    __('Set permissions') => 'blogs',
    __('Delete')          => 'deleteuser'
);

# --BEHAVIOR-- adminUsersActionsCombo
$core->callBehavior('adminUsersActionsCombo', array(&$combo_action));

$show_filters = false;

#?Get users
$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != (integer) $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

$q      = !empty($_GET['q']) ? $_GET['q'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'user_id';
$order  = !empty($_GET['order']) ? $_GET['order'] : 'asc';

$params['limit'] = array((($page - 1) * $nb_per_page), $nb_per_page);

# - Search filter
if ($q) {
    $params['q']  = $q;
    $show_filters = true;
}

# - Sortby and order filter
if ($sortby !== '' && in_array($sortby, $sortby_combo, true)) {
    if (array_key_exists($sortby, $sortby_lex)) {
        $params['order'] = $core->con->lexFields($sortby_lex[$sortby]);
    } else {
        $params['order'] = $sortby;
    }
    if ($order !== '' && in_array($order, $order_combo, true)) {
        $params['order'] .= ' ' . $order;
    } else {
        $order = 'asc';
    }
} else {
    $sortby = 'user_id';
    $order  = 'asc';
}
if ($sortby != 'user_id' || $order != 'asc') {
    $show_filters = true;
}

# Get users
try {
    $rs       = $core->getUsers($params);
    $counter  = $core->getUsers($params, 1);
    $rsStatic = $rs->toStatic();
    if ($sortby != 'nb_post') {
        // Sort user list using lexical order if necessary
        $rsStatic->extend('rsExtUser');
        $rsStatic = $rsStatic->toExtStatic();
        $rsStatic->lexicalSort($sortby, $order);
    }
    $user_list = new adminUserList($core, $rsStatic, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(__('Users'),
    dcPage::jsLoad('js/_users.js') . dcPage::jsFilterControl($show_filters),
    dcPage::breadcrumb(
        array(
            __('System') => '',
            __('Users')  => ''
        ))
);

if (!$core->error->flag()) {
    if (!empty($_GET['del'])) {
        dcPage::message(__('User has been successfully removed.'));
    }
    if (!empty($_GET['upd'])) {
        dcPage::message(__('The permissions have been successfully updated.'));
    }

    echo
    '<p class="top-add"><strong><a class="button add" href="' . $core->adminurl->get("admin.user") . '">' . __('New user') . '</a></strong></p>' .
    '<form action="' . $core->adminurl->get("admin.users") . '" method="get" id="filters-form">' .
    '<h3 class="out-of-screen-if-js">' . __('Show filters and display options') . '</h3>' .

    '<div class="table">' .
    '<div class="cell">' .
    '<h4>' . __('Filters') . '</h4>' .
    '<p><label for="q" class="ib">' . __('Search:') . '</label> ' .
    form::field('q', 20, 255, html::escapeHTML($q)) . '</p>' .
    '</div>' .

    '<div class="cell filters-options">' .
    '<h4>' . __('Display options') . '</h4>' .
    '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
    form::combo('sortby', $sortby_combo, $sortby) . '</p> ' .
    '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
    form::combo('order', $order_combo, $order) . '</p>' .
    '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
    form::number('nb', 0, 999, $nb_per_page) . ' ' . __('users per page') . '</label></p> ' .
    '</div>' .
    '</div>' .

    '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
    '<br class="clear" /></p>' . //Opera sucks
    '</form>';

    # Show users
    $user_list->display($page, $nb_per_page,
        '<form action="' . $core->adminurl->get("admin.user.actions") . '" method="post" id="form-users">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' .
        __('Selected users action:') . ' ' .
        form::combo('action', $combo_action) .
        '</label> ' .
        '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
        form::hidden(array('q'), html::escapeHTML($q)) .
        form::hidden(array('sortby'), $sortby) .
        form::hidden(array('order'), $order) .
        form::hidden(array('page'), $page) .
        form::hidden(array('nb'), $nb_per_page) .
        $core->formNonce() .
        '</p>' .
        '</div>' .
        '</form>',
        $show_filters
    );
}
dcPage::helpBlock('core_users');
dcPage::close();
