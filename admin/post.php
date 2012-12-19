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

function savePost($form) {
	global $_ctx;
	$_ctx->setMessage('save');

}

function deletePost($form) {
	print_r($form); exit;
}

$page_title = __('New entry');

$can_view_page = true;
$can_edit_post = $core->auth->check('usage,contentadmin',$core->blog->id);
$can_publish = $core->auth->check('publish,contentadmin',$core->blog->id);
$can_delete = false;

$post_headlink = '<link rel="%s" title="%s" href="post.php?id=%s" />';
$post_link = '<a href="post.php?id=%s" title="%s">%s</a>';

$next_link = $prev_link = $next_headlink = $prev_headlink = null;

# If user can't publish
if (!$can_publish) {
	$form->post_status = -2;
}

# Getting categories
$categories_combo = array('&nbsp;' => '');
try {
	$categories = $core->blog->getCategories(array('post_type'=>'post'));
	while ($categories->fetch()) {
		$categories_combo[$categories->cat_id] = 
			str_repeat('&nbsp;&nbsp;',$categories->level-1).
			($categories->level-1 == 0 ? '' : '&bull; ').
			html::escapeHTML($categories->cat_title);
	}
} catch (Exception $e) { }

# Status combo
foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$k] = $v;
}

# Formaters combo
foreach ($core->getFormaters() as $v) {
	$formaters_combo[$v] = $v;
}

# Languages combo
$rs = $core->blog->getLangs(array('order'=>'asc'));
$all_langs = l10n::getISOcodes(0,1);
$lang_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1,1));
while ($rs->fetch()) {
	if (isset($all_langs[$rs->post_lang])) {
		$lang_combo[__('Most used')][$all_langs[$rs->post_lang]] = $rs->post_lang;
		unset($lang_combo[__('Available')][$all_langs[$rs->post_lang]]);
	} else {
		$lang_combo[__('Most used')][$rs->post_lang] = $rs->post_lang;
	}
}
unset($all_langs);
unset($rs);

$form = new dcForm($core,'post','post.php');
$form
	->addField(
		new dcFieldText('post_title','', array(
			'size'		=> 20,
			'required'	=> true,
			'label'		=> __('Title'))))
	->addField(
		new dcFieldTextArea('post_excerpt','', array(
			'cols'		=> 50,
			'rows'		=> 5,
			'label'		=> __("Excerpt:"))))
	->addField(
		new dcFieldTextArea('post_content','', array(
			'required'	=> true,
			'label'		=> __("Content:"))))
	->addField(
		new dcFieldTextArea('post_notes','', array(
			'label'		=> __("Notes"))))
	->addField(
		new dcFieldSubmit('save',__('Save'),array(
			'action' => 'savePost')))
	->addField(
		new dcFieldSubmit('delete',__('Delete'),array(
			'action' => 'deletePost')))
	->addField(
		new dcFieldCombo('post_status',$core->auth->getInfo('user_post_status'),$status_combo,array(
			'disabled' => !$can_publish,
			'label' => __('Entry status:'))))
	->addField(
		new dcFieldCombo('cat_id','',$categories_combo,array(
			"label" => __('Category:'))))
	->addField(
		new dcFieldText('post_dt','',array(
			"label" => __('Published on:'))))
	->addField(
		new dcFieldCombo('post_format',$core->auth->getOption('post_format'),$formaters_combo,array(
			"label" => __('Text formating:'))))
	->addField(
		new dcFieldCheckbox ('post_open_comment',$core->blog->settings->system->allow_comments,array(
			"label" => __('Accept comments'))))
	->addField(
		new dcFieldCheckbox ('post_open_tb',$core->blog->settings->system->allow_trackbacks,array(
			"label" => __('Accept trackbacks'))))
	->addField(
		new dcFieldCheckbox ('post_selected',false,array(
			"label" => __('Selected entry'))))
	->addField(
		new dcFieldCombo ('post_lang',$core->auth->getInfo('user_lang'),$lang_combo, array(
			"label" => __('Entry lang:'))))
	->addField(
		new dcFieldHidden ('id',''))
;
# Get entry informations
if (!empty($_REQUEST['id']))
{
	$params['post_id'] = $_REQUEST['id'];
	
	$post = $core->blog->getPosts($params);
	
	if ($post->isEmpty())
	{
		$core->error->add(__('This entry does not exist.'));
		$can_view_page = false;
	}
	else
	{
		$form->id = $post->post_id;
		$form->cat_id = $post->cat_id;
		$form->post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
		$form->post_format = $post->post_format;
		$form->post_password = $post->post_password;
		$form->post_url = $post->post_url;
		$form->post_lang = $post->post_lang;
		$form->post_title = $post->post_title;
		$form->post_excerpt = $post->post_excerpt;
		$form->post_excerpt_xhtml = $post->post_excerpt_xhtml;
		$form->post_content = $post->post_content;
		$form->post_content_xhtml = $post->post_content_xhtml;
		$form->post_notes = $post->post_notes;
		$form->post_status = $post->post_status;
		$form->post_selected = (boolean) $post->post_selected;
		$form->post_open_comment = (boolean) $post->post_open_comment;
		$form->post_open_tb = (boolean) $post->post_open_tb;
		$form->can_edit_post = $post->isEditable();
		$form->can_delete= $post->isDeletable();
		
	}
}

$form->setup();

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_post) {
	$default_tab = '';
}
if (!empty($_GET['co'])) {
	$default_tab = 'comments';
}
$_ctx->default_tab = $default_tab;
$_ctx->setPageTitle($page_title);

$core->tpl->display('post.html.twig');
?>