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

$post_id = '';
$post_dt = '';
$post_format = $core->auth->getOption('post_format');
$post_url = '';
$post_lang = $core->auth->getInfo('user_lang');
$post_title = '';
$post_excerpt = '';
$post_excerpt_xhtml = '';
$post_content = '';
$post_content_xhtml = '';
$post_notes = '';
$post_status = $core->auth->getInfo('user_post_status');
$post_selected = false;

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
	$post_status = -2;
}

# Status combo
foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}
$img_status_pattern = '<img class="img_select_option" alt="%1$s" title="%1$s" src="images/%2$s" />';

# Formaters combo
foreach ($core->getFormaters() as $v) {
	$formaters_combo[$v] = $v;
}

# Languages combo
$langs = $core->blog->getLangs(array('order'=>'asc'));
$all_langs = l10n::getISOcodes(0,1);
$lang_combo = array('' => '', __('Most used') => array(), __('Available') => l10n::getISOcodes(1,1));
foreach($langs as $l) {
	if (isset($all_langs[$l->post_lang])) {
		$lang_combo[__('Most used')][$all_langs[$l->post_lang]] = $l->post_lang;
		unset($lang_combo[__('Available')][$all_langs[$l->post_lang]]);
	} else {
		$lang_combo[__('Most used')][$l->post_lang] = $l->post_lang;
	}
}
unset($all_langs);
unset($langs);

# Validation flag
$bad_dt = false;

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
		$post_id = $post->post_id;
		$post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
		$post_format = $post->post_format;
		$post_url = $post->post_url;
		$post_lang = $post->post_lang;
		$post_title = $post->post_title;
		$post_excerpt = $post->post_excerpt;
		$post_excerpt_xhtml = $post->post_excerpt_xhtml;
		$post_content = $post->post_content;
		$post_content_xhtml = $post->post_content_xhtml;
		$post_notes = $post->post_notes;
		$post_status = $post->post_status;
		$post_selected = (boolean) $post->post_selected;
		
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
		
	}
}

# Format excerpt and content
if (!empty($_POST) && $can_edit_post)
{
	$post_format = $_POST['post_format'];
	$post_excerpt = $_POST['post_excerpt'];
	$post_content = $_POST['post_content'];
	
	$post_title = $_POST['post_title'];
	
	if (isset($_POST['post_status'])) {
		$post_status = (integer) $_POST['post_status'];
	}
	
	if (empty($_POST['post_dt'])) {
		$post_dt = '';
	} else {
		try
		{
			$post_dt = strtotime($_POST['post_dt']);
			if ($post_dt == false || $post_dt == -1) {
				$bad_dt = true;
				throw new Exception(__('Invalid publication date'));
			}
			$post_dt = date('Y-m-d H:i',$post_dt);
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
	}
	
	$post_selected = !empty($_POST['post_selected']);
	$post_lang = $_POST['post_lang'];
	
	$post_notes = $_POST['post_notes'];
	
	if (isset($_POST['post_url'])) {
		$post_url = $_POST['post_url'];
	}
	
	$core->blog->setPostContent(
		$post_id,$post_format,$post_lang,
		$post_excerpt,$post_excerpt_xhtml,$post_content,$post_content_xhtml
	);
}

# Delete post
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

# Create or update post
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_post && !$bad_dt)
{
	$cur = $core->con->openCursor($core->prefix.'post');
	
	$cur->post_title = $post_title;
	$cur->post_dt = $post_dt ? date('Y-m-d H:i:00',strtotime($post_dt)) : '';
	$cur->post_format = $post_format;
	$cur->post_lang = $post_lang;
	$cur->post_title = $post_title;
	$cur->post_excerpt = $post_excerpt;
	$cur->post_excerpt_xhtml = $post_excerpt_xhtml;
	$cur->post_content = $post_content;
	$cur->post_content_xhtml = $post_content_xhtml;
	$cur->post_notes = $post_notes;
	$cur->post_status = $post_status;
	$cur->post_selected = (integer) $post_selected;
	
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

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_post) {
	$default_tab = '';
}

dcPage::open($page_title.' - '.__('Entries'),
	dcPage::jsDatePicker().
	dcPage::jsModal().
	dcPage::jsMetaEditor().
	dcPage::jsLoad('js/_post.js').
	dcPage::jsConfirmClose('entry-form').
	# --BEHAVIOR-- adminPostHeaders
	$core->callBehavior('adminPostHeaders').
	dcPage::jsPageTabs($default_tab).
	$next_headlink."\n".$prev_headlink
);

if (!empty($_GET['upd'])) {
	dcPage::message(__('Entry has been successfully updated.'));
}
elseif (!empty($_GET['crea'])) {
	dcPage::message(__('Entry has been successfully created.'));
}

# XHTML conversion
if (!empty($_GET['xconv']))
{
	$post_excerpt = $post_excerpt_xhtml;
	$post_content = $post_content_xhtml;
	$post_format = 'xhtml';
	
	dcPage::message(__('Don\'t forget to validate your XHTML conversion by saving your post.'));
}

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.'<a href="posts.php">'.__('Entries').'</a> &rsaquo; <span class="page-title">'.$page_title;
if ($post_id) {
	switch ($post_status) {
		case 1:
			$img_status = sprintf($img_status_pattern,__('published'),'check-on.png');
			break;
		case 0:
			$img_status = sprintf($img_status_pattern,__('unpublished'),'check-off.png');
			break;
		case -1:
			$img_status = sprintf($img_status_pattern,__('scheduled'),'scheduled.png');
			break;
		case -2:
			$img_status = sprintf($img_status_pattern,__('pending'),'check-wrn.png');
			break;
		default:
			$img_status = '';
	}
	echo ' &ldquo;'.$post_title.'&rdquo;'.' '.$img_status;
}
echo	'</span></h2>';

if ($post_id && $post->post_status == 1) {
	echo '<p><a href="'.$post->getURL().'" onclick="window.open(this.href);return false;" title="'.$post_title.' ('.__('new window').')'.'">'.__('Go to this entry on the site').' <img src="images/outgoing-blue.png" alt="" /></a></p>';
}
if ($post_id)
{
	echo '<p>';
	if ($prev_link) { echo $prev_link; }
	if ($next_link && $prev_link) { echo ' - '; }
	if ($next_link) { echo $next_link; }
	
	# --BEHAVIOR-- adminPostNavLinks
	$core->callBehavior('adminPostNavLinks',isset($post) ? $post : null);
	
	echo '</p>';
}

# Exit if we cannot view page
if (!$can_view_page) {
	dcPage::helpBlock('core_post');
	dcPage::close();
	exit;
}

/* Post form if we can edit post
-------------------------------------------------------- */
if ($can_edit_post)
{
	echo '<div class="multi-part" title="'.__('Edit entry').'" id="edit-entry">';
	echo '<form action="post.php" method="post" id="entry-form">';
	echo '<div id="entry-wrapper">';
	echo '<div id="entry-content"><div class="constrained">';
	
	echo
	'<p class="col"><label class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').
	form::field('post_title',20,255,html::escapeHTML($post_title),'maximal').
	'</label></p>'.
	
	'<p class="area" id="excerpt-area"><label for="post_excerpt">'.__('Excerpt:').'</label> '.
	form::textarea('post_excerpt',50,5,html::escapeHTML($post_excerpt)).
	'</p>'.
	
	'<p class="area"><label class="required" '.
	'for="post_content"><abbr title="'.__('Required field').'">*</abbr> '.__('Content:').'</label> '.
	form::textarea('post_content',50,$core->auth->getOption('edit_size'),html::escapeHTML($post_content)).
	'</p>'.
	
	'<p class="area" id="notes-area"><label for="post_notes">'.__('Notes:').'</label>'.
	form::textarea('post_notes',50,5,html::escapeHTML($post_notes)).
	'</p>';
	
	# --BEHAVIOR-- adminPostForm
	$core->callBehavior('adminPostForm',isset($post) ? $post : null);
	
	echo
	'<p>'.
	($post_id ? form::hidden('id',$post_id) : '').
	'<input type="submit" value="'.__('Save').' (s)" '.
	'accesskey="s" name="save" /> ';
	if ($post_id) {
		$preview_url =
		$core->blog->url.$core->url->getURLFor('preview',$core->auth->userID().'/'.
		http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
		'/'.$post->post_url);
		echo '<a id="post-preview" href="'.$preview_url.'" class="button">'.__('Preview').'</a> ';
	}
	echo
	($can_delete ? '<input type="submit" class="delete" value="'.__('Delete').'" name="delete" />' : '').
	$core->formNonce().
	'</p>';
	
	echo '</div></div>';		// End #entry-content
	echo '</div>';		// End #entry-wrapper

	echo '<div id="entry-sidebar">';
	
	echo
	'<p><label for="post_status">'.__('Entry status:').
	form::combo('post_status',$status_combo,$post_status,'','',!$can_publish).
	'</label></p>'.
	
	'<p><label for="post_dt">'.__('Published on:').
	form::field('post_dt',16,16,$post_dt,($bad_dt ? 'invalid' : '')).
	'</label></p>'.
	
	'<p><label for="post_format">'.__('Text formating:').
	form::combo('post_format',$formaters_combo,$post_format).
	'</label>'.
	'</p>'.
	'<p>'.($post_id && $post_format != 'xhtml' ? '<a id="convert-xhtml" class="button" href="post.php?id='.$post_id.'&amp;xconv=1">'.__('Convert to XHTML').'</a>' : '').'</p>'.
	
	'<p><label for="post_selected" class="classic">'.form::checkbox('post_selected',1,$post_selected).' '.
	__('Selected entry').'</label></p>'.
	
	'<p><label for="post_lang">'.__('Entry lang:').
	form::combo('post_lang',$lang_combo,$post_lang).
	'</label></p>'.
	
	'<div class="lockable">'.
	'<p><label for="post_url">'.__('Basename:').
	form::field('post_url',10,255,html::escapeHTML($post_url),'maximal').
	'</label></p>'.
	'<p class="form-note warn">'.
	__('Warning: If you set the URL manually, it may conflict with another entry.').
	'</p>'.
	'</div>';
	
	# --BEHAVIOR-- adminPostFormSidebar
	$core->callBehavior('adminPostFormSidebar',isset($post) ? $post : null);
	
	echo '</div>';		// End #entry-sidebar

	echo '</form>';
	
	# --BEHAVIOR-- adminPostForm
	$core->callBehavior('adminPostAfterForm',isset($post) ? $post : null);
	
	echo '</div>';
	

}

dcPage::helpBlock('core_post','core_wiki');
dcPage::close();
?>