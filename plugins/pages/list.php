<?php
/**
 * @brief pages, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_CONTEXT_ADMIN')) {return;}
dcPage::check('pages,contentadmin');

/* Getting pages
-------------------------------------------------------- */
$params = array(
    'post_type' => 'page'
);

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 30;

if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
    $nb_per_page = (integer) $_GET['nb'];
}

$params['limit']      = array((($page - 1) * $nb_per_page), $nb_per_page);
$params['no_content'] = true;
$params['order']      = 'post_position ASC, post_title ASC';

try {
    $pages     = $core->blog->getPosts($params);
    $counter   = $core->blog->getPosts($params, true);
    $post_list = new adminPagesList($core, $pages, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

# Actions combo box

$pages_actions_page = new dcPagesActionsPage($core, 'plugin.php', array('p' => 'pages'));

if (!$pages_actions_page->process()) {

# --BEHAVIOR-- adminPagesActionsCombo
    $core->callBehavior('adminPagesActionsCombo', array(&$combo_action));

/* Display
-------------------------------------------------------- */
    ?>
<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <?php
echo
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsLoad(dcPage::getPF('pages/js/list.js')) .
    '<script type="text/javascript">' . "\n" .
    dcPage::jsVar('dotclear.msg.confirm_delete_posts', __("Are you sure you want to delete selected pages?")) .
        '</script>';

    ?>
</head>

<body>
<?php
echo dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Pages')                         => ''
        )) . dcPage::notices();

    if (!empty($_GET['upd'])) {
        dcPage::success(__('Selected pages have been successfully updated.'));
    } elseif (!empty($_GET['del'])) {
        dcPage::success(__('Selected pages have been successfully deleted.'));
    } elseif (!empty($_GET['reo'])) {
        dcPage::success(__('Selected pages have been successfully reordered.'));
    }
    echo
    '<p class="top-add"><a class="button add" href="' . $p_url . '&amp;act=page">' . __('New page') . '</a></p>';

    if (!$core->error->flag()) {
        # Show pages
        $post_list->display($page, $nb_per_page,
            '<form action="' . $core->adminurl->get('admin.plugin') . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected pages action:') . '</label> ' .
            form::combo('action', $pages_actions_page->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
            form::hidden(array('post_type'), 'page') .
            form::hidden(array('p'), 'pages') .
            form::hidden(array('act'), 'list') .
            $core->formNonce() .
            '</p></div>' .
            '<p class="clear form-note hidden-if-js">' .
            __('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.') . '</p>' .
            '<p class="clear form-note hidden-if-no-js">' .
            __('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.') . '</p>' .
            '<p><input type="submit" value="' . __('Save pages order') . '" name="reorder" class="clear" /></p>' .
            '</form>');
    }
    dcPage::helpBlock('pages');
    ?>
</body>
</html>
<?php
}
?>
