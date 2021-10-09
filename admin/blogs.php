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

/* Actions
-------------------------------------------------------- */
$blogs_actions_page = null;
if ($core->auth->isSuperAdmin()) {
    $blogs_actions_page = new dcBlogsActionsPage($core, $core->adminurl->get('admin.blogs'));
    if ($blogs_actions_page->process()) {
        return;
    }
}

/* Filters
-------------------------------------------------------- */
$blog_filter = new adminBlogFilter($core);

# get list params
$params = $blog_filter->params();

/* List
-------------------------------------------------------- */
$blog_list = null;

try {
    # --BEHAVIOR-- adminGetBlogs
    $params = new ArrayObject($params);
    $core->callBehavior('adminGetBlogs', $params);

    $counter  = $core->getBlogs($params, true);
    $rs       = $core->getBlogs($params);
    $nb_blog  = $counter->f(0);
    $rsStatic = $rs->toStatic();
    if (($blog_filter->sortby != 'blog_upddt') && ($blog_filter->sortby != 'blog_status')) {
        // Sort blog list using lexical order if necessary
        $rsStatic->extend('rsExtUser');
        $rsStatic = $rsStatic->toExtStatic();
        $rsStatic->lexicalSort(($blog_filter->sortby == 'UPPER(blog_name)' ? 'blog_name' : 'blog_id'), $blog_filter->order);
    }
    $blog_list = new adminBlogList($core, $rs, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

/* DISPLAY
-------------------------------------------------------- */

dcPage::open(__('List of blogs'),
    dcPage::jsLoad('js/_blogs.js') . $blog_filter->js(),
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

    $blog_filter->display('admin.blogs');

    # Show blogs
    $blog_list->display($blog_filter->page, $blog_filter->nb,
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

            $core->adminurl->getHiddenFormFields('admin.blogs', $blog_filter->values(true)) .
            '</form>' : ''),
        $blog_filter->show()
    );
}

dcPage::helpBlock('core_blogs');
dcPage::close();
