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

$q         = !empty($_GET['q']) ? $_GET['q'] : null;
$plugin_id = !empty($_GET['plugin_id']) ? html::sanitizeURL($_GET['plugin_id']) : '';

$page        = !empty($_GET['page']) ? max(1, (integer) $_GET['page']) : 1;
$nb_per_page = 10;

$type = !empty($_GET['type']) ? $_GET['type'] : null;

$post_types = $core->getPostTypes();
foreach ($post_types as $k => $v) {
    $type_combo[__($k)] = (string) $k;
}
if (!in_array($type, $type_combo)) {
    $type = null;
}

$params               = array();
$params['limit']      = array((($page - 1) * $nb_per_page), $nb_per_page);
$params['no_content'] = true;
$params['order']      = 'post_dt DESC';

if ($q) {
    $params['search'] = $q;
}

if ($type) {
    $params['post_type'] = $type;
}

dcPage::openPopup(__('Add a link to an entry'),
    dcPage::jsLoad('js/_posts_list.js') .
    dcPage::jsLoad('js/_popup_posts.js') .
    $core->callBehavior('adminPopupPosts', $plugin_id));

echo '<h2 class="page-title">' . __('Add a link to an entry') . '</h2>';

echo '<form action="' . $core->adminurl->get('admin.popup_posts') . '" method="get">' .
'<p><label for="type" class="classic">' . __('Entry type:') . '</label> ' . form::combo('type', $type_combo, $type) . '' .
'<noscript><div><input type="submit" value="' . __('Ok') . '" /></div></noscript>' .
form::hidden('plugin_id', html::escapeHTML($plugin_id)) . '</p>' .
    '</form>';

echo '<form action="' . $core->adminurl->get('admin.popup_posts') . '" method="get">' .
'<p><label for="q" class="classic">' . __('Search entry:') . '</label> ' . form::field('q', 30, 255, html::escapeHTML($q)) .
' <input type="submit" value="' . __('Search') . '" />' .
form::hidden('plugin_id', html::escapeHTML($plugin_id)) .
form::hidden('type', html::escapeHTML($type)) .
    '</p></form>';

try {
    $posts     = $core->blog->getPosts($params);
    $counter   = $core->blog->getPosts($params, true);
    $post_list = new adminPostMiniList($core, $posts, $counter->f(0));
} catch (Exception $e) {
    $core->error->add($e->getMessage());
}

echo '<div id="form-entries">'; # I know it's not a form but we just need the ID
$post_list->display($page, $nb_per_page);
echo '</div>';

echo '<p><button type="button" id="link-insert-cancel">' . __('cancel') . '</button></p>';

dcPage::closePopup();
