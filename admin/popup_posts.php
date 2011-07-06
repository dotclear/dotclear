<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$q = !empty($_GET['q']) ? $_GET['q'] : null;

$post_list = new adminPostMiniList($core);

$params = array();
$params['no_content'] = true;

# - Limit, sortby and order filter
$params = $post_list->applyFilters($params);

if ($q) {
	$params['search'] = $q;
}

dcPage::openPopup(__('Add a link to an entry'),
	dcPage::jsLoad('js/_posts_list.js').
	dcPage::jsLoad('js/jsToolBar/popup_posts.js'));

echo '<h2>'.__('Add a link to an entry').'</h2>';

echo '<form action="popup_posts.php" method="get">'.
'<p><label for="q" class="classic">'.__('Search entry:').' '.form::field('q',30,255,html::escapeHTML($q)).'</label> '.
' <input type="submit" value="'.__('ok').'" /></p>'.
'</form>';

try {
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list->setItems($posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

echo '<div id="form-entries">'; # I know it's not a form but we just need the ID
$post_list->display();
echo '</div>';

echo '<p><a class="button" href="#" id="link-insert-cancel">'.__('cancel').'</a></p>';

dcPage::closePopup();
?>