<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2011 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$q = !empty($_GET['q']) ? $_GET['q'] : null;

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  10;

$post_types = $core->getPostTypes();
foreach ($post_types as $k => $v) {
 	$type_combo[__($k)] = (string) $k;
}
$type = !empty($_POST['type']) ? $_POST['type'] : null;
if (!$type && $q) {
	// Cope with search form
	$type = !empty($_GET['type']) ? $_GET['type'] : null;
}
if (!in_array($type, $type_combo)) {
	$type = null;
}

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['order'] = 'post_dt DESC';

if ($q) {
	$params['search'] = $q;
}

if ($type) {
	$params['post_type'] = $type;
}

dcPage::openPopup(__('Add a link to an entry'),
	dcPage::jsLoad('js/_posts_list.js').
	dcPage::jsLoad('js/jsToolBar/popup_posts.js'));

echo '<h2 class="page-title">'.__('Add a link to an entry').'</h2>';

echo '<form action="popup_posts.php" method="post">'.
	'<p><label for"type" class="classic">'.__('Entry type:').' '.form::combo('type',$type_combo,$type).'</label></p>'.
	$core->formNonce().
	'<noscript><div><input type="submit" value="'.__('Ok').'" /></div></noscript>'.
	'</form>';

echo '<form action="popup_posts.php" method="get">'.
	'<p><label for="q" class="classic">'.__('Search entry:').' '.form::field('q',30,255,html::escapeHTML($q)).'</label> '.
	' <input type="submit" value="'.__('Search').'" /></p>'.
	form::hidden('type',html::escapeHTML($type)).
	'</form>';

try {
	$posts = $core->blog->getPosts($params);
	$counter = $core->blog->getPosts($params,true);
	$post_list = new adminPostMiniList($core,$posts,$counter->f(0));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

echo '<div id="form-entries">'; # I know it's not a form but we just need the ID
$post_list->display($page,$nb_per_page);
echo '</div>';

echo '<p><a class="button" href="#" id="link-insert-cancel">'.__('cancel').'</a></p>';

dcPage::closePopup();
?>