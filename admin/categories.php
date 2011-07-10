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

dcPage::check('categories');

# Remove a category
if (!empty($_POST['del_cat']))
{
	try
	{
		$c = $core->blog->getCategory((integer) $_POST['del_cat']);
		if ($c->isEmpty()) {
			throw new Exception(__('This category does not exist.'));
		}
		unset($c);
		$core->blog->delCategory($_POST['del_cat']);
		http::redirect('categories.php?del=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
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

/* Display
-------------------------------------------------------- */
dcPage::open(__('Categories'),
	dcPage::jsToolMan()."\n".
	dcPage::jsLoad('js/_categories.js')
);

if (!empty($_GET['add'])) {
	echo '<p class="message">'.__('The category has been successfully created.').'</p>';
}
if (!empty($_GET['del'])) {
		echo '<p class="message">'.__('The category has been successfully removed.').'</p>';
}
if (!empty($_GET['reord'])) {
		echo '<p class="message">'.__('Categories have been successfully reordered.').'</p>';
}
if (!empty($_GET['moved'])) {
		echo '<p class="message">'.__('The category has been successfully moved.').'</p>';
}

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.__('Categories').'</span></h2>';

$rs = $core->blog->getCategories(array('post_type'=>'post'));

echo
'<div class="two-cols">'.
'<div class="col">';
if ($rs->isEmpty())
{
	echo '<p>'.__('No category yet.').'</p>';
}
else
{
	echo
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
		'<p><strong><a href="category.php?id='.$rs->cat_id.'">'.html::escapeHTML($rs->cat_title).'</a></strong>'.
		' (<a href="posts.php?cat_id='.$rs->cat_id.'">'.
		sprintf(($rs->nb_post > 1 ? __('%d entries') : __('%d entry') ),$rs->nb_post).'</a>'.
		', '.__('total:').' '.$rs->nb_total.')</p>'.
		'<p>'.__('URL:').' '.html::escapeHTML($rs->cat_url).'</p>';
		
		$level = $rs->level;
	}
	
	if ($ref_level - $level < 0) {
		echo str_repeat('</li></ul>',-($ref_level - $level));
	}
	echo '</div>';
}
echo '</div>';

echo '<div class="col">'.

'<form action="category.php" method="post">'.
'<fieldset><legend>'.__('Add a new category').'</legend>'.
'<p><label class="required" for="cat_title"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').' '.
form::field('cat_title',30,255).'</label></p>'.
'<p><label for="new_cat_parent">'.__('Parent:').' '.
'<select id="new_cat_parent" name="new_cat_parent">'.
'<option value="0">'.__('Top level').'</option>';
while ($rs->fetch()) {
	echo '<option value="'.$rs->cat_id.'">'.
		str_repeat('&nbsp;&nbsp;',$rs->level-1).($rs->level-1 == 0 ? '' : '&bull; ').
		html::escapeHTML($rs->cat_title).'</option>';
}
echo
'</select></label></p>'.
'<p><input type="submit" value="'.__('Create').'" /></p>'.
$core->formNonce().
'</fieldset>'.
'</form>';

if (!$rs->isEmpty())
{
	$deletable = array();
	$l = $rs->level;
	$full_name = array($rs->cat_title);
	while ($rs->fetch())
	{
		if ($rs->level < $l) {
			$full_name = array();
		} elseif ($rs->level == $l) {
			array_pop($full_name);
		}
		$full_name[] = html::escapeHTML($rs->cat_title);
		if ($rs->nb_post == 0) {
			$deletable[implode(' / ',$full_name)] = $rs->cat_id;
		}
		$l = $rs->level;
	}
	
	if (count($deletable) > 0)
	{
		echo
		'<form action="categories.php" method="post" id="delete-category">'.
		'<fieldset><legend>'.__('Remove a category').'</legend>'.
		'<p><label for="del_cat">'.__('Choose a category to remove:').' '.
		form::combo('del_cat',$deletable).'</label></p> '.
		'<p><input type="submit" value="'.__('Delete').'" class="delete" /></p>'.
		$core->formNonce().
		'</fieldset>'.
		'</form>';
	}
	
	echo
	'<form action="categories.php" method="post" id="reset-order">'.
	'<fieldset><legend>'.__('Reorder categories').'</legend>'.
	'<p>'.__('This will relocate all categories on the top level').'</p> '.
	'<p><input type="submit" value="'.__('Reorder').'" /></p>'.
	form::hidden(array('reset'),1).
	$core->formNonce().
	'</fieldset>'.
	'</form>';
}
echo '</div>';
echo '</div>';

dcPage::helpBlock('core_categories');
dcPage::close();
?>