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

$cat_id = '';
$cat_title = '';
$cat_url = '';
$cat_desc = '';
$cat_position = '';

# Getting existing category
if (!empty($_REQUEST['id']))
{
	try {
		$rs = $core->blog->getCategory($_REQUEST['id']);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
	
	if (!$core->error->flag() && !$rs->isEmpty())
	{
		$cat_id = (integer) $rs->cat_id;
		$cat_title = $rs->cat_title;
		$cat_url = $rs->cat_url;
		$cat_desc = $rs->cat_desc;
	}
	unset($rs);
	
	# Getting hierarchy information
	$parents = $core->blog->getCategoryParents($cat_id);
	$rs = $core->blog->getCategoryParent($cat_id);
	$cat_parent = $rs->isEmpty() ? 0 : (integer) $rs->cat_id;
	unset($rs);
	
	# Allowed parents list
	$children = $core->blog->getCategories(array('post_type'=>'post','start'=>$cat_id));
	$allowed_parents = array(__('Top level')=>0);
	
	$p = array();
	while ($children->fetch()) {
		$p[$children->cat_id] = 1;
	}
	
	$rs = $core->blog->getCategories(array('post_type'=>'post'));
	while ($rs->fetch()) {
		if (!isset($p[$rs->cat_id])) {
			$allowed_parents[] = new formSelectOption(
				str_repeat('&nbsp;&nbsp;',$rs->level-1).($rs->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($rs->cat_title),
				$rs->cat_id
			);
		}
	}
	unset($rs);
	
	# Allowed siblings list
	$siblings = array();
	$rs = $core->blog->getCategoryFirstChildren($cat_parent);
	while ($rs->fetch()) {
		if ($rs->cat_id != $cat_id) {
			$siblings[html::escapeHTML($rs->cat_title)] = $rs->cat_id;
		}
	}
	unset($rs);
}

# Changing parent
if ($cat_id && isset($_POST['cat_parent']))
{
	$new_parent = (integer) $_POST['cat_parent'];
	if ($cat_parent != $new_parent)
	{
		try {
			$core->blog->setCategoryParent($cat_id,$new_parent);
			http::redirect('categories.php?moved=1');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}

# Changing sibling
if ($cat_id && isset($_POST['cat_sibling']))
{
	try {
		$core->blog->setCategoryPosition($cat_id,(integer) $_POST['cat_sibling'],$_POST['cat_move']);
		http::redirect('categories.php?moved=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Create or update a category
if (isset($_POST['cat_title']))
{
	$cur = $core->con->openCursor($core->prefix.'category');
	
	$cur->cat_title = $cat_title = $_POST['cat_title'];
	
	if (isset($_POST['cat_desc'])) {
		$cur->cat_desc = $cat_desc = $_POST['cat_desc'];
	}
	
	if (isset($_POST['cat_url'])) {
		$cur->cat_url = $cat_url = $_POST['cat_url'];
	} else {
		$cur->cat_url = $cat_url;
	}
	
	try
	{
		# Update category
		if ($cat_id)
		{
			# --BEHAVIOR-- adminBeforeCategoryUpdate
			$core->callBehavior('adminBeforeCategoryUpdate',$cur,$cat_id);
			
			$core->blog->updCategory($_POST['id'],$cur);
			
			# --BEHAVIOR-- adminAfterCategoryUpdate
			$core->callBehavior('adminAfterCategoryUpdate',$cur,$cat_id);
			
			http::redirect('category.php?id='.$_POST['id'].'&upd=1');
		}
		# Create category
		else
		{
			# --BEHAVIOR-- adminBeforeCategoryCreate
			$core->callBehavior('adminBeforeCategoryCreate',$cur);
			
			$id = $core->blog->addCategory($cur,(integer) $_POST['new_cat_parent']);
			
			# --BEHAVIOR-- adminAfterCategoryCreate
			$core->callBehavior('adminAfterCategoryCreate',$cur,$id);
			
			http::redirect('categories.php?add=1');
		}
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}


$title = $cat_id ? html::escapeHTML($cat_title) : __('New category');

dcPage::open($title,
	dcPage::jsConfirmClose('category-form').
	dcPage::jsToolBar().
	dcPage::jsLoad('js/_category.js')
);

if (!empty($_GET['upd'])) {
	echo '<p class="message">'.__('Category has been successfully updated.').'</p>';
}

echo
'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <a href="categories.php">'.
__('Categories').'</a> &rsaquo; ';

if ($cat_id)
{
	while($parents->fetch()) {
		echo '<a href="category.php?id='.$parents->cat_id.'">'.html::escapeHTML($parents->cat_title).'</a>';
		echo " &rsaquo; ";
	}
}

echo '<span class="page-title">'.$title.'</span></h2>';

echo
'<form action="category.php" method="post" id="category-form">'.
'<fieldset><legend>'.__('Category information').'</legend>'.
'<p><label class="required" for="cat_title"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').' '.
form::field('cat_title',40,255,html::escapeHTML($cat_title)).
'</label></p>';
if (!$cat_id)
{
	$rs = $core->blog->getCategories(array('post_type'=>'post'));
	echo
	'<p><label for="new_cat_parent">'.__('Parent:').' '.
	'<select id="new_cat_parent" name="new_cat_parent" >'.
	'<option value="0">'.__('Top level').'</option>';
	while ($rs->fetch()) {
		echo '<option value="'.$rs->cat_id.'" '.(!empty($_POST['new_cat_parent']) && $_POST['new_cat_parent'] == $rs->cat_id ? 'selected="selected"' : '').'>'.
		str_repeat('&nbsp;&nbsp;',$rs->level).html::escapeHTML($rs->cat_title).'</option>';
	}
	echo
	'</select></label></p>';
	unset($rs);
}
echo
'<div class="lockable">'.
'<p><label for="cat_url">'.__('URL:').' '.form::field('cat_url',40,255,html::escapeHTML($cat_url)).
'</label></p>'.
'<p class="form-note warn" id="note-cat-url">'.
__('Warning: If you set the URL manually, it may conflict with another category.').'</p>'.
'</div>'.

'<p class="area"><label for="cat_desc">'.__('Description:').'</label> '.
form::textarea('cat_desc',50,8,html::escapeHTML($cat_desc)).
'</p>'.

'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
($cat_id ? form::hidden('id',$cat_id) : '').
$core->formNonce().
'</p>'.
'</fieldset>'.
'</form>';

if ($cat_id)
{
	echo
	'<h3>'.__('Move this category').'</h3>'.
	'<div class="two-cols">'.
	'<div class="col">'.
	
	'<form action="category.php" method="post">'.
	'<fieldset><legend>'.__('Category parent').'</legend>'.
	'<p><label for="cat_parent" class="classic">'.__('Parent:').' '.
	form::combo('cat_parent',$allowed_parents,$cat_parent).'</label></p>'.
	'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
	form::hidden(array('id'),$cat_id).$core->formNonce().'</p>'.
	'</fieldset>'.
	'</form>'.
	'</div>';
	
	if (count($siblings) > 0) {
		echo
		'<div class="col">'.
		'<form action="category.php" method="post">'.
		'<fieldset><legend>'.__('Category sibling').'</legend>'.
		'<p><label class="classic" for="cat_sibling">'.__('Move current category').'</label> '.
		form::combo('cat_move',array(__('before')=>'before',__('after')=>'after'),'','','',false,'title="'.__('position: ').'"').' '.
		form::combo('cat_sibling',$siblings).'</p>'.
		'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
		form::hidden(array('id'),$cat_id).$core->formNonce().'</p>'.
		'</fieldset>'.
		'</form>'.
		'</div>';
	}
	
	echo '</div>';
}

dcPage::helpBlock('core_categories');
dcPage::close();
?>