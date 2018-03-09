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

$q     = !empty($_REQUEST['q']) ? $_REQUEST['q'] : (!empty($_REQUEST['qx']) ? $_REQUEST['qx'] : null);
$qtype = !empty($_REQUEST['qtype']) ? $_REQUEST['qtype'] : 'p';
if ($qtype != 'c' && $qtype != 'p') {
    $qtype = 'p';
}

$starting_scripts = '';

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;

if ($q) {
    $q = html::escapeHTML($q);

    $params = array();

    # Get posts
    if ($qtype == 'p') {
        $starting_scripts .= dcPage::jsLoad('js/_posts_list.js');

        $params['search']     = $q;
        $params['limit']      = array((($page - 1) * $nb_per_page), $nb_per_page);
        $params['no_content'] = true;
        $params['order']      = 'post_dt DESC';

        try {
            $posts     = $core->blog->getPosts($params);
            $counter   = $core->blog->getPosts($params, true);
            $post_list = new adminPostList($core, $posts, $counter->f(0));
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
    # Get comments
    elseif ($qtype == 'c') {
        $starting_scripts .= dcPage::jsLoad('js/_comments.js');

        $params['search']     = $q;
        $params['limit']      = array((($page - 1) * $nb_per_page), $nb_per_page);
        $params['no_content'] = true;
        $params['order']      = 'comment_dt DESC';

        try {
            $comments     = $core->blog->getComments($params);
            $counter      = $core->blog->getComments($params, true);
            $comment_list = new adminCommentList($core, $comments, $counter->f(0));
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

if ($qtype == 'p') {
    $posts_actions_page = new dcPostsActionsPage($core, $core->adminurl->get("admin.search"), array('q' => $q, 'qtype' => $qtype));

    if ($posts_actions_page->process()) {
        return;
    }
} else {
    $comments_actions_page = new dcCommentsActionsPage($core, $core->adminurl->get("admin.search"), array('q' => $q, 'qtype' => $qtype));

    if ($comments_actions_page->process()) {
        return;
    }
}

dcPage::open(__('Search'), $starting_scripts,
    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Search')                        => ''
        ))
);

echo
'<form action="' . $core->adminurl->get("admin.search") . '" method="get" role="search">' .
'<div class="fieldset"><h3>' . __('Search options') . '</h3>' .
'<p><label for="q">' . __('Query:') . ' </label>' . form::field('q', 30, 255, $q) . '</p>' .
'<p><label for="qtype1" class="classic">' . form::radio(array('qtype', 'qtype1'), 'p', $qtype == 'p') . ' ' . __('Search in entries') . '</label> ' .
'<label for="qtype2" class="classic">' . form::radio(array('qtype', 'qtype2'), 'c', $qtype == 'c') . ' ' . __('Search in comments') . '</label></p>' .
'<p><input type="submit" value="' . __('Search') . '" /></p>' .
    '</div>' .
    '</form>';

if ($q && !$core->error->flag()) {
    $redir = html::escapeHTML($_SERVER['REQUEST_URI']);

    # Show posts
    if ($qtype == 'p') {

        if ($counter->f(0) > 0) {
            printf('<h3>' .
                ($counter->f(0) == 1 ? __('%d entry found') : __('%d entries found')) .
                '</h3>', $counter->f(0));
        }

        $post_list->display($page, $nb_per_page,
            '<form action="' . $core->adminurl->get("admin.search") . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action1" class="classic">' . __('Selected entries action:') . '</label> ' .
            form::combo(array('action', 'action1'), $posts_actions_page->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            $core->formNonce() .
            $posts_actions_page->getHiddenFields() .
            '</div>' .
            '</form>'
        );
    }
    # Show posts
    elseif ($qtype == 'c') {
        # Actions combo box

        if ($counter->f(0) > 0) {
            printf('<h3>' .
                ($counter->f(0) == 1 ? __('%d comment found') : __('%d comments found')) .
                '</h3>', $counter->f(0));
        }

        $comment_list->display($page, $nb_per_page,
            '<form action="' . $core->adminurl->get("admin.search") . '" method="post" id="form-comments">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action2" class="classic">' . __('Selected comments action:') . '</label> ' .
            form::combo(array('action', 'action2'), $comments_actions_page->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" /></p>' .
            $core->formNonce() .
            $comments_actions_page->getHiddenFields() .
            '</div>' .
            '</form>'
        );
    }
}

dcPage::helpBlock('core_search');
dcPage::close();
