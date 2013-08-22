<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('categories');

# Remove a categories
if (!empty($_POST['categories'])) {
	$error = false;
	foreach ($_POST['categories'] as $cat_id) {
		# Check if category to delete exists
		$c = $core->blog->getCategory((integer) $cat_id);
		if ($c->isEmpty()) {
			continue;
		}
		unset($c);

		try {
			# Delete category
			$core->blog->delCategory($cat_id);
		} catch (Exception $e) {
			$error = true;
		}
	}
	if (!$error) {
		http::redirect('categories.php?del='.count($_POST['categories']));
	}
}

# Reset order
if (!empty($_POST['reset']))
{
	try
	{
		$core->blog->resetCategoriesOrder();
		http::redirect('categories.php?reord=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Actions combo box
$combo_action = array();
if ($core->auth->check('categories',$core->blog->id)) {
	$combo_action[__('Delete')] = 'delete';
}

# --BEHAVIOR-- adminCategoriesActionsCombo
$core->callBehavior('adminCategoriesActionsCombo',array(&$combo_action));


/* Display
-------------------------------------------------------- */
dcPage::open(__('Categories'),
	dcPage::jsToolMan()."\n".
	dcPage::jsLoad('js/_categories.js'),
	dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Categories').'</span>' => ''
		))
);

if (!empty($_GET['add'])) {
	dcPage::message(__('The category has been successfully created.'));
}
if (!empty($_GET['del'])) {
  dcPage::message(__('The category has been successfully removed.',
		     'The categories have been successfully removed.',
		     (int) $_GET['del']
		     )
		  );
}
if (!empty($_GET['reord'])) {
	dcPage::message(__('Categories have been successfully reordered.'));
}
if (!empty($_GET['moved'])) {
	dcPage::message(__('The category has been successfully moved.'));
}

$rs = $core->blog->getCategories(array('post_type'=>'post'));

$categories_combo = array();
if (!$rs->isEmpty())
{
	while ($rs->fetch()) {
		$catparents_combo[] = $categories_combo[] = new formSelectOption(
			str_repeat('&nbsp;&nbsp;',$rs->level-1).($rs->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($rs->cat_title),
			$rs->cat_id
		);
	}
}

echo
'<p class="top-add"><a class="button add" href="category.php">'.__('New category').'</a></p>';

echo
'<div class="col">';
if ($rs->isEmpty())
{
	echo '<p>'.__('No category yet.').'</p>';
}
else
{
	echo
	'<form action="categories.php" method="post" id="form-categories">'.
	'<h3>'.__('Categories list').'</h3>'.
	'<div id="categories">';

	$ref_level = $level = $rs->level-1;
	while ($rs->fetch())
	{
		$attr = 'id="cat'.$rs->cat_id.'"';
		if ($rs->nb_total == 0) {
			$attr .= ' class="deletable"';
		}

		if ($rs->level > $level) {
			echo str_repeat('<ul><li '.$attr.'>',$rs->level - $level);
		} elseif ($rs->level < $level) {
			echo str_repeat('</li></ul>',-($rs->level - $level));
		}

		if ($rs->level <= $level) {
			echo '</li><li '.$attr.'>';
		}

		echo
		'<p>';
		if ($rs->nb_total == 0) {
			echo form::checkbox(array('categories[]'),$rs->cat_id);
		}

		echo
		'<strong><a href="category.php?id='.$rs->cat_id.'">'.html::escapeHTML($rs->cat_title).'</a></strong>'.
		' (<a href="posts.php?cat_id='.$rs->cat_id.'">'.
		sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry') ),$rs->nb_post).'</a>'.
		', '.__('total:').' '.$rs->nb_total.')</p>'.
		'<p>'.__('URL:').' '.html::escapeHTML($rs->cat_url).'</p>';

		$level = $rs->level;
	}

	if ($ref_level - $level < 0) {
		echo str_repeat('</li></ul>',-($ref_level - $level));
	}
	echo
	'</div>';

	if (count($combo_action)>0) {
		 echo
		 '<div class="two-cols">'.
		 '<p class="col checkboxes-helpers"></p>'.
		 '<p class="col right"><label for="action" class="classic">'.__('Selected categories action:').'</label> '.
		 form::combo('action',$combo_action).
		 $core->formNonce().
		 '<input type="submit" value="'.__('ok').'" /></p>'.
		 '</div>'.
		 '</form>';
	}

	echo
	'<div class="col clear">'.
	'<form action="categories.php" method="post" id="reset-order">'.
	'<h3>'.__('Reorder categories').'</h3>'.
	'<p>'.__('This will relocate all categories on the top level').'</p> '.
	'<p><input class="reset" type="submit" value="'.__('Reorder').'" />'.
	form::hidden(array('reset'),1).
	$core->formNonce().'</p>'.
	'</form>'.
	'</div>';
}

echo '</div>';

dcPage::helpBlock('core_categories');
dcPage::close();
?>
