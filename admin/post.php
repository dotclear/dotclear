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
		$categories_combo[] = new formSelectOption(
			str_repeat('&nbsp;&nbsp;',$categories->level-1).($categories->level-1 == 0 ? '' : '&bull; ').html::escapeHTML($categories->cat_title),
			$categories->cat_id
		);
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
		new dcFieldSubmit('save',__('Save'),array()))
	->addField(
		new dcFieldSubmit('delete',__('Delete'),array()))
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
		$form->post_id = $post->post_id;
		$form->cat_id = $post->cat_id;
		$form->post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
		$form->post_format = $post->post_format;
		$form->post_password = $post->post_password;
		$form->post_url = $post->post_url;
		$form->post_lang = $post->post_lang;
		$form->post_title = $post->post_title;
		$form->post_excerpt = $post->post_excerpt;
		$post_excerpt_xhtml = $post->post_excerpt_xhtml;
		$form->post_content = $post->post_content;
		$post_content_xhtml = $post->post_content_xhtml;
		$form->post_notes = $post->post_notes;
		$form->post_status = $post->post_status;
		$form->post_selected = (boolean) $post->post_selected;
		$form->post_open_comment = (boolean) $post->post_open_comment;
		$form->post_open_tb = (boolean) $post->post_open_tb;
		
		$page_title = __('Edit entry');
		
		$can_edit_post = $post->isEditable();
		$can_delete= $post->isDeletable();
		
		$next_rs = $core->blog->getNextPost($post,1);
		$prev_rs = $core->blog->getNextPost($post,-1);
		
		if ($next_rs !== null) {
			$next_link = sprintf($post_link,$next_rs->post_id,
				html::escapeHTML($next_rs->post_title),__('next entry').'&nbsp;&#187;');
			$next_headlink = sprintf($post_headlink,'next',
				html::escapeHTML($next_rs->post_title),$next_rs->post_id);
		}
		
		if ($prev_rs !== null) {
			$prev_link = sprintf($post_link,$prev_rs->post_id,
				html::escapeHTML($prev_rs->post_title),'&#171;&nbsp;'.__('previous entry'));
			$prev_headlink = sprintf($post_headlink,'previous',
				html::escapeHTML($prev_rs->post_title),$prev_rs->post_id);
		}
		
		try {
			$core->media = new dcMedia($core);
		} catch (Exception $e) {}
	}
}

# Format excerpt and content
if (!empty($_POST) && $can_edit_post)
{
	$post_format = $_POST['post_format'];
	$post_excerpt = $_POST['post_excerpt'];
	$post_content = $_POST['post_content'];
	
	$post_title = $_POST['post_title'];
	
	$cat_id = (integer) $_POST['cat_id'];
	
	if (isset($_POST['post_status'])) {
		$post_status = (integer) $_POST['post_status'];
	}
	
	if (empty($_POST['post_dt'])) {
		$post_dt = '';
	} else {
		$post_dt = strtotime($_POST['post_dt']);
		$post_dt = date('Y-m-d H:i',$post_dt);
	}
	
	$post_open_comment = !empty($_POST['post_open_comment']);
	$post_open_tb = !empty($_POST['post_open_tb']);
	$post_selected = !empty($_POST['post_selected']);
	$post_lang = $_POST['post_lang'];
	$post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
	
	$post_notes = $_POST['post_notes'];
	
	if (isset($_POST['post_url'])) {
		$post_url = $_POST['post_url'];
	}
	
	$core->blog->setPostContent(
		$post_id,$post_format,$post_lang,
		$post_excerpt,$post_excerpt_xhtml,$post_content,$post_content_xhtml
	);
}

# Create or update post
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_post)
{
	$cur = $core->con->openCursor($core->prefix.'post');
	
	$cur->post_title = $post_title;
	$cur->cat_id = ($cat_id ? $cat_id : null);
	$cur->post_dt = $post_dt ? date('Y-m-d H:i:00',strtotime($post_dt)) : '';
	$cur->post_format = $post_format;
	$cur->post_password = $post_password;
	$cur->post_lang = $post_lang;
	$cur->post_title = $post_title;
	$cur->post_excerpt = $post_excerpt;
	$cur->post_excerpt_xhtml = $post_excerpt_xhtml;
	$cur->post_content = $post_content;
	$cur->post_content_xhtml = $post_content_xhtml;
	$cur->post_notes = $post_notes;
	$cur->post_status = $post_status;
	$cur->post_selected = (integer) $post_selected;
	$cur->post_open_comment = (integer) $post_open_comment;
	$cur->post_open_tb = (integer) $post_open_tb;
	
	if (isset($_POST['post_url'])) {
		$cur->post_url = $post_url;
	}
	
	# Update post
	if ($post_id)
	{
		try
		{
			# --BEHAVIOR-- adminBeforePostUpdate
			$core->callBehavior('adminBeforePostUpdate',$cur,$post_id);
			
			$core->blog->updPost($post_id,$cur);
			
			# --BEHAVIOR-- adminAfterPostUpdate
			$core->callBehavior('adminAfterPostUpdate',$cur,$post_id);
			
			http::redirect('post.php?id='.$post_id.'&upd=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	else
	{
		$cur->user_id = $core->auth->userID();
		
		try
		{
			# --BEHAVIOR-- adminBeforePostCreate
			$core->callBehavior('adminBeforePostCreate',$cur);
			
			$return_id = $core->blog->addPost($cur);
			
			# --BEHAVIOR-- adminAfterPostCreate
			$core->callBehavior('adminAfterPostCreate',$cur,$return_id);
			
			http::redirect('post.php?id='.$return_id.'&crea=1');
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
}

if (!empty($_POST['delete']) && $can_delete)
{
	try {
		# --BEHAVIOR-- adminBeforePostDelete
		$core->callBehavior('adminBeforePostDelete',$post_id);
		$core->blog->delPost($post_id);
		http::redirect('posts.php');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_post) {
	$default_tab = '';
}
if (!empty($_GET['co'])) {
	$default_tab = 'comments';
}

$core->page->getContext()
	->jsDatePicker()
	->jsToolBar()
	->jsModal()
	->jsMetaEditor()
	->jsLoad('js/_post.js')
	->jsPageTabs($default_tab)
	->jsConfirmClose('entry-form','comment-form');

echo $core->page->render('post.html.twig',array(
	'edit_size'=> $core->auth->getOption('edit_size')));
?>