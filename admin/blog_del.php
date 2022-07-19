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

$blog_id   = '';
$blog_name = '';

$rs = null;

if (!empty($_POST['blog_id'])) {
    try {
        $rs = dcCore::app()->getBlog($_POST['blog_id']);
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }

    if ($rs->isEmpty()) {
        dcCore::app()->error->add(__('No such blog ID'));
    } else {
        $blog_id   = $rs->blog_id;
        $blog_name = $rs->blog_name;
    }
}

# Delete the blog
if (!dcCore::app()->error->flag() && $blog_id && !empty($_POST['del'])) {
    if (!dcCore::app()->auth->checkPassword($_POST['pwd'])) {
        dcCore::app()->error->add(__('Password verification failed'));
    } else {
        try {
            dcCore::app()->delBlog($blog_id);
            dcPage::addSuccessNotice(sprintf(__('Blog "%s" successfully deleted'), html::escapeHTML($blog_name)));

            dcCore::app()->adminurl->redirect('admin.blogs');
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }
}

dcPage::open(
    __('Delete a blog'),
    '',
    dcPage::breadcrumb(
        [
            __('System')        => '',
            __('Blogs')         => dcCore::app()->adminurl->get('admin.blogs'),
            __('Delete a blog') => '',
        ]
    )
);

if (!dcCore::app()->error->flag()) {
    echo
    '<div class="warning-msg"><p><strong>' . __('Warning') . '</strong></p>' .
    '<p>' . sprintf(
        __('You are about to delete the blog %s. Every entry, comment and category will be deleted.'),
        '<strong>' . $blog_id . ' (' . $blog_name . ')</strong>'
    ) . '</p></div>' .
    '<p>' . __('Please give your password to confirm the blog deletion.') . '</p>';

    echo
    '<form action="' . dcCore::app()->adminurl->get('admin.blog.del') . '" method="post">' .
    '<div>' . dcCore::app()->formNonce() . '</div>' .
    '<p><label for="pwd">' . __('Your password:') . '</label> ' .
    form::password('pwd', 20, 255, ['autocomplete' => 'current-password']) . '</p>' .
    '<p><input type="submit" class="delete" name="del" value="' . __('Delete this blog') . '" />' .
    ' <input type="button" value="' . __('Cancel') . '" class="go-back reset hidden-if-no-js" />' .
    form::hidden('blog_id', $blog_id) . '</p>' .
        '</form>';
}

dcPage::close();
