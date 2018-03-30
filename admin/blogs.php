<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

# Filters
$status_combo = array_merge(
    array('-' => ''),
    dcAdminCombos::getBlogStatusesCombo()
);

$sortby_combo = array(
    __('Last update') => 'blog_upddt',
    __('Blog name')   => 'UPPER(blog_name)',
    __('Blog ID')     => 'B.blog_id',
    __('Status')      => 'blog_status'
);

$order_combo = array(
    __('Descending') => 'desc',
    __('Ascending')  => 'asc'
);

# Actions

if ($core->auth->isSuperAdmin()) {
    $blogs_actions_page = new dcBlogsActionsPage($core, $core->adminurl->get("admin.blogs"));
    if ($blogs_actions_page->process()) {
        return;
    }
}

# Requests
$q      = !empty($_GET['q']) ? $_GET['q'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'blog_upddt';
$order  = !empty($_GET['order']) ? $_GET['order'] : 'desc';

$show_filters = false;

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != (integer) $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

# - Search filter
if ($q) {
    $params['q']  = $q;
    $show_filters = true;
}

# - Status filter
if ($status !== '' && in_array($status, $status_combo, true)) {
    $params['blog_status'] = $status;
    $show_filters          = true;
} else {
    $status = '';
}

# - Sortby and order filter
if ($sortby !== '' && in_array($sortby, $sortby_combo, true)) {
    if ($order !== '' && in_array($order, $order_combo, true)) {
        $params['order'] = $sortby . ' ' . $order;
    } else {
        $order = 'desc';
    }
} else {
    $sortby = 'blog_upddt';
    $order  = 'desc';
}
if ($sortby != 'blog_upddt' || $order != 'desc') {
    $show_filters = true;
}

$params['limit'] = array((($page - 1) * $nb_per_page), $nb_per_page);

try {
    $counter  = $core->getBlogs($params, 1);
    $rs       = $core->getBlogs($params);
    $nb_blog  = $counter->f(0);
    $rsStatic = $rs->toStatic();
    if (($sortby != 'blog_upddt') && ($sortby != 'blog_status')) {
        // Sort blog list using lexical order if necessary
        $rsStatic->extend('rsExtUser');
        $rsStatic = $rsStatic->toExtStatic();
        $rsStatic->lexicalSort(($sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), $order);
    }
    $blog_list = new adminBlogList($core, $rs, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(__('List of blogs'),
    dcPage::jsLoad('js/_blogs.js') . dcPage::jsFilterControl($show_filters),
    dcPage::breadcrumb(
        array(
            __('System')        => '',
            __('List of blogs') => ''
        ))
);

if (!$core->error->flag()) {
    if ($core->auth->isSuperAdmin()) {
        echo '<p class="top-add"><a class="button add" href="' . $core->adminurl->get("admin.blog") . '">' . __('Create a new blog') . '</a></p>';
    }

    echo
    '<form action="' . $core->adminurl->get("admin.blogs") . '" method="get" id="filters-form">' .
    '<h3 class="out-of-screen-if-js">' . __('Show filters and display options') . '</h3>' .

    '<div class="table">' .
    '<div class="cell">' .
    '<h4>' . __('Filters') . '</h4>' .
    '<p><label for="q" class="ib">' . __('Search:') . '</label> ' .
    form::field('q', 20, 255, html::escapeHTML($q)) . '</p>' .
    ($core->auth->isSuperAdmin() ?
        '<p><label for="status" class="ib">' . __('Status:') . '</label> ' .
        form::combo('status', $status_combo, $status) . '</p>' : '') .
    '</div>' .

    '<div class="cell filters-options">' .
    '<h4>' . __('Display options') . '</h4>' .
    '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
    form::combo('sortby', $sortby_combo, html::escapeHTML($sortby)) . '</p>' .
    '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
    form::combo('order', $order_combo, html::escapeHTML($order)) . '</p>' .
    '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
    form::number('nb', 0, 999, $nb_per_page) . ' ' . __('blogs per page') . '</label></p>' .
    '</div>' .
    '</div>' .

    '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
    '<br class="clear" /></p>' . //Opera sucks
    '</form>';

    # Show blogs
    $blog_list->display($page, $nb_per_page,
        ($core->auth->isSuperAdmin() ?
            '<form action="' . $core->adminurl->get("admin.blogs") . '" method="post" id="form-blogs">' : '') .

        '%s' .

        ($core->auth->isSuperAdmin() ?
            '<div class="two-cols clearfix">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected blogs action:') . '</label> ' .
            form::combo('action', $blogs_actions_page->getCombo(),
                array('class' => 'online', 'extra_html' => 'title="' . __('Actions') . '"')) .
            $core->formNonce() .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            '</div>' .

            '<p><label for="pwd" class="classic">' . __('Please give your password to confirm blog(s) deletion:') . '</label> ' .
            form::password('pwd', 20, 255, array('autocomplete' => 'current-password')) . '</p>' .

            form::hidden(array('sortby'), $sortby) .
            form::hidden(array('order'), $order) .
            form::hidden(array('status'), $status) .
            form::hidden(array('page'), $page) .
            form::hidden(array('nb'), $nb_per_page) .

            '</form>' : ''),
        $show_filters
    );
}

dcPage::helpBlock('core_blogs');
dcPage::close();
