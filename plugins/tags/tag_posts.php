<?php
/**
 * @brief tags, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}

$tag = isset($_REQUEST['tag']) ? $_REQUEST['tag'] : '';

$this_url = $p_url . '&amp;m=tag_posts&amp;tag=' . rawurlencode($tag);

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;

# Rename a tag
if (isset($_POST['new_tag_id'])) {
    $new_id = dcMeta::sanitizeMetaID($_POST['new_tag_id']);
    try {
        if ($core->meta->updateMeta($tag, $new_id, 'tag')) {
            dcPage::addSuccessNotice(__('Tag has been successfully renamed'));
            http::redirect($p_url . '&m=tag_posts&tag=' . $new_id);
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# Delete a tag
if (!empty($_POST['delete']) && $core->auth->check('publish,contentadmin', $core->blog->id)) {
    try {
        $core->meta->delMeta($tag, 'tag');
        dcPage::addSuccessNotice(__('Tag has been successfully removed'));
        http::redirect($p_url . '&m=tags');
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

$params               = array();
$params['limit']      = array((($page - 1) * $nb_per_page), $nb_per_page);
$params['no_content'] = true;

$params['meta_id']   = $tag;
$params['meta_type'] = 'tag';
$params['post_type'] = '';

# Get posts
try {
    $posts     = $core->meta->getPostsByMeta($params);
    $counter   = $core->meta->getPostsByMeta($params, true);
    $post_list = new adminPostList($core, $posts, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

$posts_actions_page = new dcPostsActionsPage($core, 'plugin.php', array('p' => 'tags', 'm' => 'tag_posts', 'tag' => $tag));

if ($posts_actions_page->process()) {
    return;
}

?>
<html>
<head>
    <title><?php echo __('Tags'); ?></title>
    <?php echo dcPage::cssLoad(dcPage::getPF('tags/style.css')); ?>
    <?php echo dcPage::jsLoad('js/_posts_list.js'); ?>
    <script type="text/javascript">
        dotclear.msg.confirm_tag_delete = '<?php echo html::escapeJS(sprintf(__('Are you sure you want to remove tag: “%s”?'), html::escapeHTML($tag))) ?>';
        $(function() {
            $('#tag_delete').submit(function() {
                return window.confirm(dotclear.msg.confirm_tag_delete);
            });
        });
    </script>
    <?php echo dcPage::jsConfirmClose('tag_rename'); ?>
</head>
<body>

<?php
echo dcPage::breadcrumb(
    array(
        html::escapeHTML($core->blog->name)                         => '',
        __('Tags')                                                  => $p_url . '&amp;m=tags',
        __('Tag') . ' &ldquo;' . html::escapeHTML($tag) . '&rdquo;' => ''
    )) .
dcPage::notices();
?>

<?php
echo '<p><a class="back" href="' . $p_url . '&amp;m=tags">' . __('Back to tags list') . '</a></p>';

if (!$core->error->flag()) {
    if (!$posts->isEmpty()) {
        echo
        '<div class="tag-actions vertical-separator">' .
        '<h3>' . html::escapeHTML($tag) . '</h3>' .
        '<form action="' . $this_url . '" method="post" id="tag_rename">' .
        '<p><label for="new_tag_id" class="classic">' . __('Rename') . '</label> ' .
        form::field('new_tag_id', 20, 255, html::escapeHTML($tag)) .
        '<input type="submit" value="' . __('OK') . '" />' .
        $core->formNonce() .
            '</p></form>';
        # Remove tag
        if (!$posts->isEmpty() && $core->auth->check('contentadmin', $core->blog->id)) {
            echo
            '<form id="tag_delete" action="' . $this_url . '" method="post">' .
            '<p><input type="submit" class="delete" name="delete" value="' . __('Delete this tag') . '" />' .
            $core->formNonce() .
                '</p></form>';
        }
        echo '</div>';
    }

    # Show posts
    echo '<h4 class="vertical-separator pretty-title">' . sprintf(__('List of entries with the tag “%s”'), html::escapeHTML($tag)) . '</h4>';
    $post_list->display($page, $nb_per_page,
        '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post" id="form-entries">' .

        '%s' .

        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .

        '<p class="col right"><label for="action" class="classic">' . __('Selected entries action:') . '</label> ' .
        form::combo('action', $posts_actions_page->getCombo()) .
        '<input id="do-action" type="submit" value="' . __('OK') . '" /></p>' .
        form::hidden('post_type', '') .
        form::hidden('p', 'tags') .
        form::hidden('m', 'tag_posts') .
        form::hidden('tag', $tag) .
        $core->formNonce() .
        '</div>' .
        '</form>');
}
dcPage::helpBlock('tag_posts');
?>
</body>
</html>
