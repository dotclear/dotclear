<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @var dcCore $core
 */
require dirname(__FILE__) . '/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

# Filters
$status_combo = array_merge(
    ['-' => ''],
    dcAdminCombos::getBlogStatusesCombo()
);

$sortby_combo = [
    __('Last update') => 'blog_upddt',
    __('Blog name')   => 'UPPER(blog_name)',
    __('Blog ID')     => 'B.blog_id',
    __('Status')      => 'blog_status'
];
# --BEHAVIOR-- adminBlogsSortbyCombo
$core->callBehavior('adminBlogsSortbyCombo', [& $sortby_combo]);

$order_combo = [
    __('Descending') => 'desc',
    __('Ascending')  => 'asc'
];

# Actions

$blogs_actions_page = null;
if ($core->auth->isSuperAdmin()) {
    $blogs_actions_page = new dcBlogsActionsPage($core, $core->adminurl->get('admin.blogs'));
    if ($blogs_actions_page->process()) {
        return;
    }
}

$core->auth->user_prefs->addWorkspace('interface');
$default_sortby = $core->auth->user_prefs->interface->blogs_sortby ?: 'blog_upddt';
$default_order  = $core->auth->user_prefs->interface->blogs_order ?: 'desc';
$nb_per_page    = $core->auth->user_prefs->interface->nb_blogs_per_page ?: 30;

# Requests
$q      = !empty($_GET['q']) ? $_GET['q'] : '';
$status = $_GET['status'] ?? '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : $default_sortby;
$order  = !empty($_GET['order']) ? $_GET['order'] : $default_order;

$show_filters = false;

$page = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;

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
if (!in_array($sortby, $sortby_combo, true)) {
    $sortby = $default_sortby;
}
if (!in_array($order, $order_combo, true)) {
    $order = $default_order;
}
$params['order'] = $sortby . ' ' . $order;
if ($sortby != $default_sortby || $order != $default_order) {
    $show_filters = true;
}

$params['limit'] = [(($page - 1) * $nb_per_page), $nb_per_page];

$blog_list = null;

try {
    # --BEHAVIOR-- adminGetBlogs
    $params = new ArrayObject($params);
    $core->callBehavior('adminGetBlogs', $params);

    $counter  = $core->getBlogs($params, true);
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
        [
            __('System')        => '',
            __('List of blogs') => ''
        ])
);

if (!$core->error->flag()) {
    if ($core->auth->isSuperAdmin()) {
        echo '<p class="top-add"><a class="button add" href="' . $core->adminurl->get('admin.blog') . '">' . __('Create a new blog') . '</a></p>';
    }

    echo
    '<form action="' . $core->adminurl->get('admin.blogs') . '" method="get" id="filters-form">' .
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

    form::hidden('filters-options-id', 'blogs') .
    '<p class="hidden-if-no-js"><a href="#" id="filter-options-save">' . __('Save current options') . '</a></p>' .

    '</div>' .
    '</div>' .

    '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
    '<br class="clear" /></p>' . //Opera sucks
    '</form>';

    # Show blogs
    $blog_list->display($page, $nb_per_page,
        ($core->auth->isSuperAdmin() ?
            '<form action="' . $core->adminurl->get('admin.blogs') . '" method="post" id="form-blogs">' : '') .

        '%s' .

        ($core->auth->isSuperAdmin() ?
            '<div class="two-cols clearfix">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected blogs action:') . '</label> ' .
            form::combo('action', $blogs_actions_page->getCombo(),
                ['class' => 'online', 'extra_html' => 'title="' . __('Actions') . '"']) .
            $core->formNonce() .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            '</div>' .

            '<p><label for="pwd" class="classic">' . __('Please give your password to confirm blog(s) deletion:') . '</label> ' .
            form::password('pwd', 20, 255, ['autocomplete' => 'current-password']) . '</p>' .

            form::hidden(['sortby'], $sortby) .
            form::hidden(['order'], $order) .
            form::hidden(['status'], $status) .
            form::hidden(['page'], $page) .
            form::hidden(['nb'], $nb_per_page) .

            '</form>' : ''),
        $show_filters
    );
}

dcPage::helpBlock('core_blogs');
dcPage::close();
