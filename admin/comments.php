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

$show_ip = $core->auth->check('contentadmin', $core->blog->id);

if (!empty($_POST['delete_all_spam'])) {
    try {
        $core->blog->delJunkComments();
        $_SESSION['comments_del_spam'] = true;
        $core->adminurl->redirect('admin.comments');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Creating filter combo boxes
# Filter form we'll put in html_block
$status_combo = array_merge(
    ['-' => ''],
    dcAdminCombos::getCommentStatusesCombo()
);

$type_combo = [
    '-'             => '',
    __('Comment')   => 'co',
    __('Trackback') => 'tb'
];

$sortby_combo = [
    __('Date')        => 'comment_dt',
    __('Entry title') => 'post_title',
    __('Entry date')  => 'post_dt',
    __('Author')      => 'comment_author',
    __('Status')      => 'comment_status'
];
# --BEHAVIOR-- adminCommentsSortbyCombo
$core->callBehavior('adminCommentsSortbyCombo', [& $sortby_combo]);

$sortby_lex = [
    // key in sorty_combo (see above) => field in SQL request
    'post_title'          => 'post_title',
    'comment_author'      => 'comment_author',
    'comment_spam_filter' => 'comment_spam_filter'];

# --BEHAVIOR-- adminCommentsSortbyLexCombo
$core->callBehavior('adminCommentsSortbyLexCombo', [& $sortby_lex]);

$order_combo = [
    __('Descending') => 'desc',
    __('Ascending')  => 'asc'
];

/* Get comments
-------------------------------------------------------- */
$core->auth->user_prefs->addWorkspace('interface');
$default_sortby = $core->auth->user_prefs->interface->comments_sortby ?: 'comment_dt';
$default_order  = $core->auth->user_prefs->interface->comments_order ?: 'desc';
$nb_per_page    = $core->auth->user_prefs->interface->nb_comments_per_page ?: 30;

# Filters
$author = $_GET['author'] ?? '';
$status = $_GET['status'] ?? '';
$type   = !empty($_GET['type']) ? $_GET['type'] : '';
$ip     = !empty($_GET['ip']) ? $_GET['ip'] : '';
$email  = !empty($_GET['email']) ? $_GET['email'] : '';
$site   = !empty($_GET['site']) ? $_GET['site'] : '';
# Options
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : $default_sortby;
$order  = !empty($_GET['order']) ? $_GET['order'] : $default_order;

$with_spam = $author || $status || $type || $sortby != $default_sortby || $order != $default_order || $ip || $email || $site;

$show_filters = false;

$page = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    if ($nb_per_page != (integer) $_GET['nb']) {
        $show_filters = true;
    }
    $nb_per_page = (integer) $_GET['nb'];
}

$params['limit']      = [(($page - 1) * $nb_per_page), $nb_per_page];
$params['no_content'] = true;

# Author filter
if ($author !== '') {
    $params['q_author'] = $author;
    $show_filters       = true;
} else {
    $author = '';
}

# - Type filter
if ($type == 'tb' || $type == 'co') {
    $params['comment_trackback'] = ($type == 'tb');
    $show_filters                = true;
} else {
    $type = '';
}

# - Status filter
if ($status !== '' && in_array($status, $status_combo)) {
    $params['comment_status'] = $status;
    $show_filters             = true;
} elseif (!$with_spam) {
    $params['comment_status_not'] = -2;
    $status                       = '';
} else {
    $status = '';
}

if ($show_ip) {
    # - IP filter
    if ($ip) {
        $params['comment_ip'] = $ip;
        $show_filters         = true;
    }
}

# - email filter
if ($email) {
    $params['comment_email'] = $email;
    $show_filters            = true;
}

# - site filter
if ($site) {
    $params['comment_site'] = $site;
    $show_filters           = true;
}

// Add some sort order if spams displayed
if ($with_spam || ($status == -2)) {
    if ($show_ip) {
        $sortby_combo[__('IP')] = 'comment_ip';
    }
    $sortby_combo[__('Spam filter')] = 'comment_spam_filter';
}

# Sortby and order filter
if (!in_array($sortby, $sortby_combo)) {
    $sortby = $default_sortby;
}
if (!in_array($order, $order_combo)) {
    $order = $default_order;
}
$params['order'] = (array_key_exists($sortby, $sortby_lex) ? $core->con->lexFields($sortby_lex[$sortby]) : $sortby) . ' ' . $order;
if ($sortby != $default_sortby || $order != $default_order) {
    $show_filters = true;
}

# Actions combo box
$combo_action = [];
$default      = '';
if ($core->auth->check('delete,contentadmin', $core->blog->id) && $status == -2) {
    $default = 'delete';
}

$comments_actions_page = new dcCommentsActionsPage($core, $core->adminurl->get('admin.comments'));

if ($comments_actions_page->process()) {
    return;
}

/* Get comments
-------------------------------------------------------- */
$comment_list = null;

try {
    $comments     = $core->blog->getComments($params);
    $counter      = $core->blog->getComments($params, true);
    $comment_list = new adminCommentList($core, $comments, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(__('Comments and trackbacks'),
    dcPage::jsLoad('js/_comments.js') . dcPage::jsFilterControl($show_filters),
    dcPage::breadcrumb(
        [
            html::escapeHTML($core->blog->name) => '',
            __('Comments and trackbacks')       => ''
        ])
);
if (!empty($_GET['upd'])) {
    dcPage::success(__('Selected comments have been successfully updated.'));
} elseif (!empty($_GET['del'])) {
    dcPage::success(__('Selected comments have been successfully deleted.'));
}

if (!$core->error->flag()) {
    if (isset($_SESSION['comments_del_spam'])) {
        dcPage::message(__('Spam comments have been successfully deleted.'));
        unset($_SESSION['comments_del_spam']);
    }

    $spam_count = $core->blog->getComments(['comment_status' => -2], true)->f(0);
    if ($spam_count > 0) {
        echo
        '<form action="' . $core->adminurl->get('admin.comments') . '" method="post" class="fieldset">';

        if (!$with_spam || ($status != -2)) {
            if ($spam_count == 1) {
                echo '<p>' . sprintf(__('You have one spam comment.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                '<a href="' . $core->adminurl->get('admin.comments', ['status' => -2]) . '">' . __('Show it.') . '</a></p>';
            } elseif ($spam_count > 1) {
                echo '<p>' . sprintf(__('You have %s spam comments.'), '<strong>' . $spam_count . '</strong>') . ' ' .
                '<a href="' . $core->adminurl->get('admin.comments', ['status' => -2]) . '">' . __('Show them.') . '</a></p>';
            }
        }

        echo
        '<p>' .
        $core->formNonce() .
        '<input name="delete_all_spam" class="delete" type="submit" value="' . __('Delete all spams') . '" /></p>';

        # --BEHAVIOR-- adminCommentsSpamForm
        $core->callBehavior('adminCommentsSpamForm', $core);

        echo '</form>';
    }

    echo
    '<form action="' . $core->adminurl->get('admin.comments') . '" method="get" id="filters-form">' .
    '<h3 class="out-of-screen-if-js">' . __('Show filters and display options') . '</h3>' .

    '<div class="table">' .
    '<div class="cell">' .
    '<h4>' . __('Filters') . '</h4>' .
    '<p><label for="type" class="ib">' . __('Type:') . '</label> ' .
    form::combo('type', $type_combo, $type) . '</p> ' .
    '<p><label for="status" class="ib">' . __('Status:') . '</label> ' .
    form::combo('status', $status_combo, $status) . '</p>' .
    '</div>' .

    '<div class="cell filters-sibling-cell">' .
    '<p><label for="author" class="ib">' . __('Author:') . '</label> ' .
    form::field('author', 20, 255, html::escapeHTML($author)) . '</p>';

    if ($show_ip) {
        echo
        '<p><label for="ip" class="ib">' . __('IP address:') . '</label> ' .
        form::field('ip', 20, 39, html::escapeHTML($ip)) . '</p>';
    }

    echo
    '<p><label for="email" class="ib">' . __('Email:') . '</label> ' .
    form::field('email', 20, 255, html::escapeHTML($email)) . '</p>' .
    '<p><label for="site" class="ib">' . __('Web site:') . '</label> ' .
    form::field('site', 20, 255, html::escapeHTML($site)) . '</p>' .
    '</div>' .

    '<div class="cell filters-options">' .
    '<h4>' . __('Display options') . '</h4>' .
    '<p><label for="sortby" class="ib">' . __('Order by:') . '</label> ' .
    form::combo('sortby', $sortby_combo, $sortby) . '</p>' .
    '<p><label for="order" class="ib">' . __('Sort:') . '</label> ' .
    form::combo('order', $order_combo, $order) . '</p>' .
    '<p><span class="label ib">' . __('Show') . '</span> <label for="nb" class="classic">' .
    form::number('nb', 0, 999, $nb_per_page) . ' ' .
    __('comments per page') . '</label></p>' .

    form::hidden('filters-options-id', 'comments') .
    '<p class="hidden-if-no-js"><a href="#" id="filter-options-save">' . __('Save current options') . '</a></p>' .

    '</div>' .
    '</div>' .

    '<p><input type="submit" value="' . __('Apply filters and display options') . '" />' .
    '<br class="clear" /></p>' . //Opera sucks
    '</form>';

    # Show comments
    $comment_list->display($page, $nb_per_page,
        '<form action="' . $core->adminurl->get('admin.comments') . '" method="post" id="form-comments">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' . __('Selected comments action:') . '</label> ' .
        form::combo('action', $comments_actions_page->getCombo(),
            ['default' => $default, 'extra_html' => 'title="' . __('Actions') . '"']) .
        $core->formNonce() .
        '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
        form::hidden(['type'], $type) .
        form::hidden(['sortby'], $sortby) .
        form::hidden(['order'], $order) .
        form::hidden(['author'], html::escapeHTML(preg_replace('/%/', '%%', $author))) .
        form::hidden(['status'], $status) .
        form::hidden(['ip'], preg_replace('/%/', '%%', $ip)) .
        form::hidden(['page'], $page) .
        form::hidden(['nb'], $nb_per_page) .
        form::hidden(['email'], html::escapeHTML(preg_replace('/%/', '%%', $email))) .
        form::hidden(['site'], html::escapeHTML(preg_replace('/%/', '%%', $site))) .
        '</div>' .

        '</form>',
        $show_filters,
        ($with_spam || ($status == -2)),
        $show_ip
    );
}

dcPage::helpBlock('core_comments');
dcPage::close();
