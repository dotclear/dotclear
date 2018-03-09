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

$blog_id   = '';
$blog_name = '';

if (!empty($_POST['blog_id'])) {
    try {
        $rs = $core->getBlog($_POST['blog_id']);
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    if ($rs->isEmpty()) {
        $core->error->add(__('No such blog ID'));
    } else {
        $blog_id   = $rs->blog_id;
        $blog_name = $rs->blog_name;
    }
}

# Delete the blog
if (!$core->error->flag() && $blog_id && !empty($_POST['del'])) {
    if (!$core->auth->checkPassword($_POST['pwd'])) {
        $core->error->add(__('Password verification failed'));
    } else {
        try {
            $core->delBlog($blog_id);
            dcPage::addSuccessNotice(sprintf(__('Blog "%s" successfully deleted'), html::escapeHTML($blog_name)));

            $core->adminurl->redirect("admin.blogs");
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

dcPage::open(__('Delete a blog'), '',
    dcPage::breadcrumb(
        array(
            __('System')        => '',
            __('Blogs')         => $core->adminurl->get("admin.blogs"),
            __('Delete a blog') => ''
        ))
);

if (!$core->error->flag()) {
    echo
    '<div class="warning-msg"><p><strong>' . __('Warning') . '</strong></p>' .
    '<p>' . sprintf(__('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
        '<strong>' . $blog_id . ' (' . $blog_name . ')</strong>') . '</p></div>' .
    '<p>' . __('Please give your password to confirm the blog deletion.') . '</p>';

    echo
    '<form action="' . $core->adminurl->get("admin.blog.del") . '" method="post">' .
    '<div>' . $core->formNonce() . '</div>' .
    '<p><label for="pwd">' . __('Your password:') . '</label> ' .
    form::password('pwd', 20, 255, array('autocomplete' => 'current-password')) . '</p>' .
    '<p><input type="submit" class="delete" name="del" value="' . __('Delete this blog') . '" />' .
    form::hidden('blog_id', $blog_id) . '</p>' .
        '</form>';
}

dcPage::close();
