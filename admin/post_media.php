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

$post_id   = !empty($_REQUEST['post_id']) ? (integer) $_REQUEST['post_id'] : null;
$media_id  = !empty($_REQUEST['media_id']) ? (integer) $_REQUEST['media_id'] : null;
$link_type = !empty($_REQUEST['link_type']) ? $_REQUEST['link_type'] : null;

if (!$post_id) {
    exit;
}
$rs = $core->blog->getPosts(array('post_id' => $post_id, 'post_type' => ''));
if ($rs->isEmpty()) {
    exit;
}

try {
    if ($post_id && $media_id && !empty($_REQUEST['attach'])) {
        $core->media = new dcMedia($core);
        $core->media->addPostMedia($post_id, $media_id, $link_type);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-type: application/json');
            echo json_encode(array('url' => $core->getPostAdminURL($rs->post_type, $post_id, false)));
            exit();
        } else {
            http::redirect($core->getPostAdminURL($rs->post_type, $post_id, false));
        }
    }

    $core->media = new dcMedia($core);
    $f           = $core->media->getPostMedia($post_id, $media_id, $link_type);
    if (empty($f)) {
        $post_id = $media_id = null;
        throw new Exception(__('This attachment does not exist'));
    }
    $f = $f[0];
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Remove a media from en
if (($post_id && $media_id) || $core->error->flag()) {
    if (!empty($_POST['remove'])) {
        $core->media->removePostMedia($post_id, $media_id, $link_type);

        dcPage::addSuccessNotice(__('Attachment has been successfully removed.'));
        http::redirect($core->getPostAdminURL($rs->post_type, $post_id, false));
    } elseif (isset($_POST['post_id'])) {
        http::redirect($core->getPostAdminURL($rs->post_type, $post_id, false));
    }

    if (!empty($_GET['remove'])) {
        dcPage::open(__('Remove attachment'));

        echo '<h2>' . __('Attachment') . ' &rsaquo; <span class="page-title">' . __('confirm removal') . '</span></h2>';

        echo
        '<form action="' . $core->adminurl->get("admin.post.media") . '" method="post">' .
        '<p>' . __('Are you sure you want to remove this attachment?') . '</p>' .
        '<p><input type="submit" class="reset" value="' . __('Cancel') . '" /> ' .
        ' &nbsp; <input type="submit" class="delete" name="remove" value="' . __('Yes') . '" />' .
        form::hidden('post_id', $post_id) .
        form::hidden('media_id', $media_id) .
        $core->formNonce() . '</p>' .
            '</form>';

        dcPage::close();
        exit;
    }
}
