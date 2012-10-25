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
$cat_id = '';
$post_dt = '';
$post_format = $core->auth->getOption('post_format');
$post_password = '';
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
$post_open_comment = $core->blog->settings->system->allow_comments;
$post_open_tb = $core->blog->settings->system->allow_trackbacks;

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
	$status_combo[$v] = (string) $k;
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
		$cat_id = $post->cat_id;
		$post_dt = date('Y-m-d H:i',strtotime($post->post_dt));
		$post_format = $post->post_format;
		$post_password = $post->post_password;
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
		$post_open_comment = (boolean) $post->post_open_comment;
		$post_open_tb = (boolean) $post->post_open_tb;
		
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

dcPage::open($page_title.' - '.__('Entries'),
	dcPage::jsDatePicker().
	dcPage::jsToolBar().
	dcPage::jsModal().
	dcPage::jsMetaEditor().
	dcPage::jsLoad('js/_post.js').
	dcPage::jsConfirmClose('entry-form','comment-form').
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
elseif (!empty($_GET['attached'])) {
	dcPage::message(__('File has been successfully attached.'));
}
elseif (!empty($_GET['rmattach'])) {
	dcPage::message(__('Attachment has been successfully removed.'));
}

if (!empty($_GET['creaco'])) {
	dcPage::message(__('Comment has been successfully created.'));
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
		echo ' &ldquo;'.$post_title.'&rdquo;';
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
	'<p><label for="cat_id">'.__('Category:').
	form::combo('cat_id',$categories_combo,$cat_id,'maximal').
	'</label></p>'.
	
	'<p><label for="post_status">'.__('Entry status:').
	form::combo('post_status',$status_combo,$post_status,'','',!$can_publish).
	'</label></p>'.
	
	'<p><label for="post_dt">'.__('Published on:').
	form::field('post_dt',16,16,$post_dt).
	'</label></p>'.
	
	'<p><label for="post_format">'.__('Text formating:').
	form::combo('post_format',$formaters_combo,$post_format).
	'</label>'.
	'</p>'.
	'<p>'.($post_id && $post_format != 'xhtml' ? '<a id="convert-xhtml" class="button" href="post.php?id='.$post_id.'&amp;xconv=1">'.__('Convert to XHTML').'</a>' : '').'</p>'.
	
	'<p><label for="post_open_comment" class="classic">'.form::checkbox('post_open_comment',1,$post_open_comment).' '.
	__('Accept comments').'</label></p>'.
	'<p><label for="post_open_tb" class="classic">'.form::checkbox('post_open_tb',1,$post_open_tb).' '.
	__('Accept trackbacks').'</label></p>'.
	'<p><label for="post_selected" class="classic">'.form::checkbox('post_selected',1,$post_selected).' '.
	__('Selected entry').'</label></p>'.
	
	'<p><label for="post_lang">'.__('Entry lang:').
	form::combo('post_lang',$lang_combo,$post_lang).
	'</label></p>'.
	
	'<p><label for="post_password">'.__('Entry password:').
	form::field('post_password',10,32,html::escapeHTML($post_password),'maximal').
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
	
	if ($post_id && $post->post_status == 1) {
		echo '<p><a href="trackbacks.php?id='.$post_id.'" class="multi-part">'.
		__('Ping blogs').'</a></p>';
	}
	
}


/* Comments and trackbacks
-------------------------------------------------------- */
if ($post_id)
{
	$params = array('post_id' => $post_id, 'order' => 'comment_dt ASC');
	
	$comments = $core->blog->getComments(array_merge($params,array('comment_trackback'=>0)));
	$trackbacks = $core->blog->getComments(array_merge($params,array('comment_trackback'=>1)));
	
	# Actions combo box
	$combo_action = array();
	if ($can_edit_post && $core->auth->check('publish,contentadmin',$core->blog->id))
	{
		$combo_action[__('publish')] = 'publish';
		$combo_action[__('unpublish')] = 'unpublish';
		$combo_action[__('mark as pending')] = 'pending';
		$combo_action[__('mark as junk')] = 'junk';
	}
	
	if ($can_edit_post && $core->auth->check('delete,contentadmin',$core->blog->id))
	{
		$combo_action[__('Delete')] = 'delete';
	}
	
	# --BEHAVIOR-- adminCommentsActionsCombo
	$core->callBehavior('adminCommentsActionsCombo',array(&$combo_action));
	
	$has_action = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());
	
	echo
	'<div id="comments" class="multi-part" title="'.__('Comments').'">';
	
	if ($has_action) {
		echo '<form action="comments_actions.php" id="form-comments" method="post">';
	}
	
	echo '<h3>'.__('Trackbacks').'</h3>';
	
	if (!$trackbacks->isEmpty()) {
		showComments($trackbacks,$has_action,true);
	} else {
		echo '<p>'.__('No trackback').'</p>';
	}
	
	echo '<h3>'.__('Comments').'</h3>';
	if (!$comments->isEmpty()) {
		showComments($comments,$has_action);
	} else {
		echo '<p>'.__('No comment').'</p>';
	}
	
	if ($has_action) {
		echo
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		
		'<p class="col right"><label for="action" class="classic">'.__('Selected comments action:').'</label> '.
		form::combo('action',$combo_action).
		form::hidden('redir','post.php?id='.$post_id.'&amp;co=1').
		$core->formNonce().
		'<input type="submit" value="'.__('ok').'" /></p>'.
		'</div>'.
		'</form>';
	}
	
	echo '</div>';
}

/* Add a comment
-------------------------------------------------------- */
if ($post_id)
{
	echo
	'<div class="multi-part" id="add-comment" title="'.__('Add a comment').'">'.
	'<h3>'.__('Add a comment').'</h3>'.
	
	'<form action="comment.php" method="post" id="comment-form">'.
	'<div class="constrained">'.
	'<p><label for="comment_author" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Name:').
	form::field('comment_author',30,255,html::escapeHTML($core->auth->getInfo('user_cn'))).
	'</label></p>'.
	
	'<p><label for="comment_email">'.__('Email:').
	form::field('comment_email',30,255,html::escapeHTML($core->auth->getInfo('user_email'))).
	'</label></p>'.
	
	'<p><label for="comment_site">'.__('Web site:').
	form::field('comment_site',30,255,html::escapeHTML($core->auth->getInfo('user_url'))).
	'</label></p>'.
	
	'<p class="area"><label for="comment_content" class="required"><abbr title="'.__('Required field').'">*</abbr> '.
	__('Comment:').'</label> '.
	form::textarea('comment_content',50,8,html::escapeHTML('')).
	'</p>'.
	
	'<p>'.form::hidden('post_id',$post_id).
	$core->formNonce().
	'<input type="submit" name="add" value="'.__('Save').'" /></p>'.
	'</div>'.
	'</form>'.
	'</div>';
}


# Show comments or trackbacks
function showComments($rs,$has_action,$tb=false)
{
	echo
	'<table class="comments-list"><tr>'.
	'<th colspan="2">'.__('Author').'</th>'.
	'<th>'.__('Date').'</th>'.
	'<th class="nowrap">'.__('IP address').'</th>'.
	'<th>'.__('Status').'</th>'.
	'<th>&nbsp;</th>'.
	'</tr>';
	
	while($rs->fetch())
	{
		$comment_url = 'comment.php?id='.$rs->comment_id;
		
		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($rs->comment_status) {
			case 1:
				$img_status = sprintf($img,__('published'),'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img,__('unpublished'),'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img,__('pending'),'check-wrn.png');
				break;
			case -2:
				$img_status = sprintf($img,__('junk'),'junk.png');
				break;
		}
		
		echo
		'<tr class="line'.($rs->comment_status != 1 ? ' offline' : '').'"'.
		' id="c'.$rs->comment_id.'">'.
		
		'<td class="nowrap">'.
		($has_action ? form::checkbox(array('comments[]'),$rs->comment_id,'','','',0,'title="'.($tb ? __('select this trackback') : __('select this comment')).'"') : '').'</td>'.
		'<td class="maximal">'.html::escapeHTML($rs->comment_author).'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->comment_dt).'</td>'.
		'<td class="nowrap"><a href="comments.php?ip='.$rs->comment_ip.'">'.$rs->comment_ip.'</a></td>'.
		'<td class="nowrap status">'.$img_status.'</td>'.
		'<td class="nowrap status"><a href="'.$comment_url.'">'.
		'<img src="images/edit-mini.png" alt="" title="'.__('Edit this comment').'" /></a></td>'.
		
		'</tr>';
	}
	
	echo '</table>';
}

dcPage::helpBlock('core_post','core_wiki');
dcPage::close();
?>