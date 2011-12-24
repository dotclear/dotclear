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
if (!defined('DC_CONTEXT_ADMIN')) { return; }
dcPage::check('pages,contentadmin');

$redir_url = $p_url.'&act=page';

$post_id = '';
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
$post_position = 0;
$post_open_comment = false;
$post_open_tb = false;

$post_media = array();

$page_title = __('New page');

$can_view_page = true;
$can_edit_page = $core->auth->check('pages,usage',$core->blog->id);
$can_publish = $core->auth->check('pages,publish,contentadmin',$core->blog->id);
$can_delete = false;

$post_headlink = '<link rel="%s" title="%s" href="'.html::escapeURL($redir_url).'&amp;id=%s" />';
$post_link = '<a href="'.html::escapeURL($redir_url).'&amp;id=%s" title="%s">%s</a>';

$next_link = $prev_link = $next_headlink = $prev_headlink = null;

# If user can't publish
if (!$can_publish) {
	$post_status = -2;
}

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


# Get page informations
if (!empty($_REQUEST['id']))
{
	$params['post_type'] = 'page';
	$params['post_id'] = $_REQUEST['id'];
	
	$post = $core->blog->getPosts($params);
	
	if ($post->isEmpty())
	{
		$core->error->add(__('This page does not exist.'));
		$can_view_page = false;
	}
	else
	{
		$post_id = $post->post_id;
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
		$post_position = (integer) $post->post_position;
		$post_open_comment = (boolean) $post->post_open_comment;
		$post_open_tb = (boolean) $post->post_open_tb;
		
		$page_title = __('Edit page');
		
		$can_edit_page = $post->isEditable();
		$can_delete= $post->isDeletable();
		
		$next_rs = $core->blog->getNextPost($post,1);
		$prev_rs = $core->blog->getNextPost($post,-1);
		
		if ($next_rs !== null) {
			$next_link = sprintf($post_link,$next_rs->post_id,
				html::escapeHTML($next_rs->post_title),__('next page').'&nbsp;&#187;');
			$next_headlink = sprintf($post_headlink,'next',
				html::escapeHTML($next_rs->post_title),$next_rs->post_id);
		}
		
		if ($prev_rs !== null) {
			$prev_link = sprintf($post_link,$prev_rs->post_id,
				html::escapeHTML($prev_rs->post_title),'&#171;&nbsp;'.__('previous page'));
			$prev_headlink = sprintf($post_headlink,'previous',
				html::escapeHTML($prev_rs->post_title),$prev_rs->post_id);
		}
		
		try {
			$core->media = new dcMedia($core);
			$post_media = $core->media->getPostMedia($post_id);
		} catch (Exception $e) {}
	}
}

# Format content
if (!empty($_POST) && $can_edit_page)
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
		$post_dt = strtotime($_POST['post_dt']);
		$post_dt = date('Y-m-d H:i',$post_dt);
	}
	
	$post_open_comment = !empty($_POST['post_open_comment']);
	$post_open_tb = !empty($_POST['post_open_tb']);
	$post_lang = $_POST['post_lang'];
	$post_password = !empty($_POST['post_password']) ? $_POST['post_password'] : null;
	$post_position = (integer) $_POST['post_position'];
	
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
if (!empty($_POST) && !empty($_POST['save']) && $can_edit_page)
{
	$cur = $core->con->openCursor($core->prefix.'post');
	
	# Magic tweak :)
	$core->blog->settings->system->post_url_format = $page_url_format;
	
	$cur->post_type = 'page';
	$cur->post_title = $post_title;
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
	$cur->post_position = $post_position;
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
			# --BEHAVIOR-- adminBeforePageUpdate
			$core->callBehavior('adminBeforePageUpdate',$cur,$post_id);
			
			$core->blog->updPost($post_id,$cur);
			
			# --BEHAVIOR-- adminAfterPageUpdate
			$core->callBehavior('adminAfterPageUpdate',$cur,$post_id);
			
			http::redirect($redir_url.'&id='.$post_id.'&upd=1');
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
			# --BEHAVIOR-- adminBeforePageCreate
			$core->callBehavior('adminBeforePageCreate',$cur);
			
			$return_id = $core->blog->addPost($cur);
			
			# --BEHAVIOR-- adminAfterPageCreate
			$core->callBehavior('adminAfterPageCreate',$cur,$return_id);
			
			http::redirect($redir_url.'&id='.$return_id.'&crea=1');
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
		# --BEHAVIOR-- adminBeforePageDelete
		$core->callBehavior('adminBeforePageDelete',$post_id);
		$core->blog->delPost($post_id);
		http::redirect($p_url);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

/* DISPLAY
-------------------------------------------------------- */
$default_tab = 'edit-entry';
if (!$can_edit_page) {
	$default_tab = '';
}
if (!empty($_GET['co'])) {
	$default_tab = 'comments';
}

?>
<html>
<head>
  <title><?php echo $page_title.' - '.__('Pages'); ?></title>
  <script type="text/javascript">
  //<![CDATA[
  <?php echo dcPage::jsVar('dotclear.msg.confirm_delete_post',__("Are you sure you want to delete this page?")); ?>
  //]]>
  </script>
  <?php echo
  dcPage::jsDatePicker().
  dcPage::jsToolBar().
  dcPage::jsModal().
  dcPage::jsLoad('js/_post.js').
  dcPage::jsConfirmClose('entry-form','comment-form').
  # --BEHAVIOR-- adminPageHeaders
  $core->callBehavior('adminPageHeaders').
  dcPage::jsPageTabs($default_tab).
  $next_headlink."\n".$prev_headlink;
  ?>
</head>

<body>

<?php

if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Page has been successfully updated.').'</p>';
}
elseif (!empty($_GET['crea'])) {
		echo '<p class="message">'.__('Page has been successfully created.').'</p>';
}
elseif (!empty($_GET['attached'])) {
	echo '<p class="message">'.__('File has been successfully attached.').'</p>';
}
elseif (!empty($_GET['rmattach'])) {
	echo '<p class="message">'.__('Attachment has been successfully removed.').'</p>';
}

# XHTML conversion
if (!empty($_GET['xconv']))
{
	$post_excerpt = $post_excerpt_xhtml;
	$post_content = $post_content_xhtml;
	$post_format = 'xhtml';
	
	echo '<p class="message">'.__('Don\'t forget to validate your XHTML conversion by saving your post.').'</p>';
}

echo '<h2>'.html::escapeHTML($core->blog->name).
' &rsaquo; <a href="'.$p_url.'">'.__('Pages').'</a> &rsaquo; <span class="page-title">'.$page_title; 
	if ($post_id) {
		echo ' &ldquo;'.$post_title.'&rdquo;';
	}
echo	'</span></h2>';

if ($post_id && $post->post_status == 1) {
	echo '<p><a href="'.$post->getURL().'" onclick="window.open(this.href);return false;" title="'.$post_title.' ('.__('new window').')'.'">'.__('Go to this page on the site').' <img src="images/outgoing-blue.png" alt="" /></a></p>';
}

echo '';

if ($post_id)
{
	echo '<p>';
	if ($prev_link) { echo $prev_link; }
	if ($next_link && $prev_link) { echo ' - '; }
	if ($next_link) { echo $next_link; }
	
	# --BEHAVIOR-- adminPageNavLinks 
	$core->callBehavior('adminPageNavLinks',isset($post) ? $post : null);
	
	echo '</p>';
}

# Exit if we cannot view page
if (!$can_view_page) {
	echo '</body></html>';
	return;
}


/* Post form if we can edit post
-------------------------------------------------------- */
if ($can_edit_page)
{
	echo '<div class="multi-part" title="'.__('Edit page').'" id="edit-entry">';
	echo '<form action="'.html::escapeURL($redir_url).'" method="post" id="entry-form">';

	echo '<div id="entry-wrapper">';
	echo '<div id="entry-content"><div class="constrained">';
	
	echo
	'<p class="col"><label for="post_title" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').
	form::field('post_title',20,255,html::escapeHTML($post_title),'maximal').
	'</label></p>'.
	
	'<p class="area" id="excerpt-area"><label for="post_excerpt">'.__('Excerpt:').'</label> '.
	form::textarea('post_excerpt',50,5,html::escapeHTML($post_excerpt)).
	'</p>'.
	
	'<p class="area"><label class="required" '.
	'for="post_content"><abbr title="'.__('Required field').'">*</abbr> '.__('Content:').'</label> '.
	form::textarea('post_content',50,$core->auth->getOption('edit_size'),html::escapeHTML($post_content)).
	'</p>';
	
	echo
	'<p class="area" id="notes-area"><label for="post_notes">'.__('Notes:').'</label>'.
	form::textarea('post_notes',50,5,html::escapeHTML($post_notes)).
	'</p>';

	# --BEHAVIOR-- adminPageForm
	$core->callBehavior('adminPageForm',isset($post) ? $post : null);
	
	echo
	'<p>'.
	($post_id ? form::hidden('id',$post_id) : '').
	'<input type="submit" value="'.__('Save').' (s)" '.
	'accesskey="s" name="save" /> ';

	if ($post_id) {
		$preview_url = $core->blog->url.
		$core->url->getURLFor('pagespreview',
		$core->auth->userID().'/'.
		http::browserUID(DC_MASTER_KEY.$core->auth->userID().$core->auth->getInfo('user_pwd')).
		'/'.$post->post_url);
		echo '<a id="post-preview" href="'.$preview_url.'" class="button">'.__('Preview').'</a>';
	}
	
	echo
	($can_delete ? '<input type="submit" class="delete" value="'.__('Delete').'" name="delete" />' : '').
	$core->formNonce().
	'</p>';
	
	echo '</div></div>';		// End #entry-content
	echo '</div>';		// End #entry-wrapper

	echo '<div id="entry-sidebar">';
	
	echo
	'<p><label for="post_status">'.__('Page status:').
	form::combo('post_status',$status_combo,$post_status,'','',!$can_publish).
	'</label></p>'.
	
	'<p><label for="post_dt">'.__('Published on:').
	form::field('post_dt',16,16,$post_dt).'</label></p>'.
	
	'<p><label for="post_format">'.__('Text formating:').
	form::combo('post_format',$formaters_combo,$post_format).
	($post_id && $post_format != 'xhtml' ? '<a href="'.html::escapeURL($redir_url).'&amp;id='.$post_id.'&amp;xconv=1">'.__('Convert to XHTML').'</a>' : '').
	'</label></p>'.
	
	'<p><label for="post_open_comment" class="classic">'.form::checkbox('post_open_comment',1,$post_open_comment).' '.
	__('Accept comments').'</label></p>'.
	'<p><label for="post_open_tb" class="classic">'.form::checkbox('post_open_tb',1,$post_open_tb).' '.
	__('Accept trackbacks').'</label></p>'.
	
	'<p><label for="post_position" class="classic">'.__('Page position:').' '.
	form::field('post_position',3,3,(string) $post_position).
	'</label></p>'.
	
	'<p><label for="post_lang">'.__('Page lang:').
	form::combo('post_lang',$lang_combo,$post_lang).'</label></p>'.
	
	'<p><label for="post_password">'.__('Page password:').
	form::field('post_password',10,32,html::escapeHTML($post_password),'maximal').
	'</label></p>'.
	
	'<div class="lockable">'.
	'<p><label for="post_url">'.__('Basename:').
	form::field('post_url',10,255,html::escapeHTML($post_url),'maximal').
	'</label></p>'.
	'<p class="form-note warn">'.
	__('Warning: If you set the URL manually, it may conflict with another page.').
	'</p>'.
	'</div>';
	
	if ($post_id)
	{
		echo
		'<h3 class="clear">'.__('Attachments').'</h3>';
		foreach ($post_media as $f)
		{
			$ftitle = $f->media_title;
			if (strlen($ftitle) > 18) {
				$ftitle = substr($ftitle,0,16).'...';
			}
			echo
			'<div class="media-item">'.
			'<a class="media-icon" href="media_item.php?id='.$f->media_id.'">'.
			'<img src="'.$f->media_icon.'" alt="" title="'.$f->basename.'" /></a>'.
			'<ul>'.
			'<li><a class="media-link" href="media_item.php?id='.$f->media_id.'"'.
			'title="'.$f->basename.'">'.$ftitle.'</a></li>'.
			'<li>'.$f->media_dtstr.'</li>'.
			'<li>'.files::size($f->size).' - '.
			'<a href="'.$f->file_url.'">'.__('open').'</a>'.'</li>'.
			
			'<li class="media-action"><a class="attachment-remove" id="attachment-'.$f->media_id.'" '.
			'href="post_media.php?post_id='.$post_id.'&amp;media_id='.$f->media_id.'&amp;remove=1">'.
			'<img src="images/check-off.png" alt="'.__('remove').'" /></a>'.
			'</li>'.
			
			'</ul>'.
			'</div>';
		}
		unset($f);
		
		if (empty($post_media)) {
			echo '<p>'.__('No attachment.').'</p>';
		}
		echo '<p><a href="media.php?post_id='.$post_id.'">'.__('Add files to this page').'</a></p>';
	}
	
	# --BEHAVIOR-- adminPageFormSidebar
	$core->callBehavior('adminPageFormSidebar',isset($post) ? $post : null);
	
	echo '</div>';		// End #entry-sidebar
	
	echo '</form>';
	echo '</div>';
	
	if ($post_id && !empty($post_media))
	{
		echo
		'<form action="post_media.php" id="attachment-remove-hide" method="post">'.
		'<div>'.form::hidden(array('post_id'),$post_id).
		form::hidden(array('media_id'),'').
		form::hidden(array('remove'),1).
		$core->formNonce().'</div></form>';
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
	if ($can_edit_page && $core->auth->check('publish,contentadmin',$core->blog->id))
	{
		$combo_action[__('publish')] = 'publish';
		$combo_action[__('unpublish')] = 'unpublish';
		$combo_action[__('mark as pending')] = 'pending';
		$combo_action[__('mark as junk')] = 'junk';
	}
	
	if ($can_edit_page && $core->auth->check('delete,contentadmin',$core->blog->id))
	{
		$combo_action[__('delete')] = 'delete';
	}
	
	$has_action = !empty($combo_action) && (!$trackbacks->isEmpty() || !$comments->isEmpty());
	
	echo
	'<div id="comments" class="multi-part" title="'.__('Comments').'">';
	
	if ($has_action) {
		echo '<form action="comments_actions.php" method="post">';
	}
	
	echo '<h3>'.__('Trackbacks').'</h3>';
	
	if (!$trackbacks->isEmpty()) {
		showComments($trackbacks,$has_action);
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
		
		'<p class="col right"><label for="action">'.__('Selected comments action:').'</label> '.
		form::combo('action',$combo_action).
		form::hidden('redir',html::escapeURL($redir_url).'&amp;id='.$post_id.'&amp;co=1').
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
	
	'<p class="area"><label for="comment_content" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Comment:').'</label> '.
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
function showComments($rs,$has_action)
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
		($has_action ? form::checkbox(array('comments[]'),$rs->comment_id,'','','',0,'title="'.__('select this comment').'"') : '').'</td>'.
		'<td class="maximal">'.$rs->comment_author.'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->comment_dt).'</td>'.
		'<td class="nowrap"><a href="comments.php?ip='.$rs->comment_ip.'">'.$rs->comment_ip.'</a></td>'.
		'<td class="nowrap status">'.$img_status.'</td>'.
		'<td class="nowrap status"><a href="'.$comment_url.'">'.
		'<img src="images/edit-mini.png" alt="" title="'.__('Edit this comment').'" /></a></td>'.
		
		'</tr>';
	}
	
	echo '</table>';
}
dcPage::helpBlock('page');
?>
</body>
</html>