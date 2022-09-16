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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}
dcPage::check(dcCore::app()->auth->makePermissions([
    dcPages::PERMISSION_PAGES,
    dcAuth::PERMISSION_CONTENT_ADMIN,
]));

/* Getting pages
-------------------------------------------------------- */
$params = [
    'post_type' => 'page',
];

$page        = !empty($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$nb_per_page = adminUserPref::getUserFilters('pages', 'nb');

if (!empty($_GET['nb']) && (int) $_GET['nb'] > 0) {
    $nb_per_page = (int) $_GET['nb'];
}

$params['limit']      = [(($page - 1) * $nb_per_page), $nb_per_page];
$params['no_content'] = true;
$params['order']      = 'post_position ASC, post_title ASC';

$post_list = null;

try {
    $pages     = dcCore::app()->blog->getPosts($params);
    $counter   = dcCore::app()->blog->getPosts($params, true);
    $post_list = new adminPagesList($pages, $counter->f(0));
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());
}

# Actions combo box

$pages_actions_page = new dcPagesActions('plugin.php', ['p' => 'pages']);

if (!$pages_actions_page->process()) {

    /* Display
    -------------------------------------------------------- */ ?>
<html>
<head>
  <title><?php echo __('Pages'); ?></title>
  <?php
echo
    dcPage::jsLoad('js/jquery/jquery-ui.custom.js') .
    dcPage::jsLoad('js/jquery/jquery.ui.touch-punch.js') .
    dcPage::jsJson('pages_list', ['confirm_delete_posts' => __('Are you sure you want to delete selected pages?')]) .
    dcPage::jsModuleLoad('pages/js/list.js')
    ?>
</head>

<body>
<?php
echo dcPage::breadcrumb(
        [
            html::escapeHTML(dcCore::app()->blog->name) => '',
            __('Pages')                                 => '',
        ]
    ) . dcPage::notices();

    if (!empty($_GET['upd'])) {
        dcPage::success(__('Selected pages have been successfully updated.'));
    } elseif (!empty($_GET['del'])) {
        dcPage::success(__('Selected pages have been successfully deleted.'));
    } elseif (!empty($_GET['reo'])) {
        dcPage::success(__('Selected pages have been successfully reordered.'));
    }
    echo
    '<p class="top-add"><a class="button add" href="' . $p_url . '&amp;act=page">' . __('New page') . '</a></p>';

    if (!dcCore::app()->error->flag()) {
        # Show pages
        $post_list->display(
            $page,
            $nb_per_page,
            '<form action="' . dcCore::app()->adminurl->get('admin.plugin') . '" method="post" id="form-entries">' .

            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right"><label for="action" class="classic">' . __('Selected pages action:') . '</label> ' .
            form::combo('action', $pages_actions_page->getCombo()) .
            '<input id="do-action" type="submit" value="' . __('ok') . '" />' .
            form::hidden(['post_type'], 'page') .
            form::hidden(['p'], 'pages') .
            form::hidden(['act'], 'list') .
            dcCore::app()->formNonce() .
            '</p></div>' .
            '<p class="clear form-note hidden-if-js">' .
            __('To rearrange pages order, change number at the begining of the line, then click on “Save pages order” button.') . '</p>' .
            '<p class="clear form-note hidden-if-no-js">' .
            __('To rearrange pages order, move items by drag and drop, then click on “Save pages order” button.') . '</p>' .
            '<p><input type="submit" value="' . __('Save pages order') . '" name="reorder" class="clear" /></p>' .
            '</form>'
        );
    }
    dcPage::helpBlock('pages'); ?>
</body>
</html>
<?php
}
?>
