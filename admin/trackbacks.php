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

# Check if post exists and is online
$id = !empty($_REQUEST['id']) ? (integer) $_REQUEST['id'] : null;

$post = null;
$tb_excerpt = $tb_urls = '';
$auto_link = '';
$can_view_page = true;

# Check if post exists
if ($id !== null)
{
	$params['post_id'] = $id;
	$params['post_status'] = 1;
	$post = $core->blog->getPosts($params);
	
	if ($post->isEmpty()) {
		$core->error->add(__('This entry does not exist or is not published'));
		$can_view_page = false;
	} else {
		$TB = new dcTrackback($core);
		$tb_excerpt = $post->post_excerpt_xhtml.' '.$post->post_content_xhtml;
		$post_title = $post->post_title;
		$post_url = $post->getURL();
	}
}
else
{
	$core->error->add(__('This entry does not exist.'));
	$can_view_page = false;
}

# Change excerpt
if (!empty($_POST['tb_excerpt'])) {
	$tb_excerpt = $_POST['tb_excerpt'];
}

# Sanitize excerpt
$tb_excerpt = html::clean($tb_excerpt);
$tb_excerpt = html::decodeEntities($tb_excerpt);
$tb_excerpt = text::cutString(html::escapeHTML($tb_excerpt),255);
$tb_excerpt = preg_replace('/\s+/ms',' ',$tb_excerpt);

# Send pings
if ($post && !$post->isEmpty() && !empty($_POST['tb_urls']))
{
	$tb_urls = $_POST['tb_urls'];
	$tb_urls = str_replace("\r",'',$tb_urls);
	
	$post_title = html::escapeHTML(trim(html::clean($post_title)));
	
	foreach (explode("\n",$tb_urls) as $tb_url)
	{
		try {
			$TB->ping($tb_url,$id,$post_title,$tb_excerpt,$post_url);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	
	if (!$core->error->flag()) {
		http::redirect('trackbacks.php?id='.$id.'&sent=1');
	}
}

$page_title = __('Ping blogs');

/* DISPLAY
-------------------------------------------------------- */
dcPage::open($page_title,dcPage::jsLoad('js/_trackbacks.js'));

# Exit if we cannot view page
if (!$can_view_page) {
	dcPage::close();
	exit;
}

if (!empty($_GET['sent'])) {
		echo '<p class="message">'.__('All pings sent.').'</p>';
}

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.$page_title.'</span></h2>';

echo '<p><a class="back" href="'.$core->getPostAdminURL($post->post_type,$id).'">'.
	sprintf(__('Back to "%s"'),html::escapeHTML($post->post_title)).'</a></p>';

echo
'<h3 id="entry-preview-title">'.
html::escapeHTML($post->post_title).'</h3>'.
'<div class="frame-shrink" id="entry-preview">'.
($post->post_excerpt_xhtml ? $post->post_excerpt_xhtml.'<hr />' : '').
$post->post_content_xhtml.
'</div>';

if (!empty($_GET['auto'])) {
	flush();
	$tb_urls = implode("\n",$TB->discover($post->post_excerpt_xhtml.' '.$post->post_content_xhtml));
} else {
	$auto_link = '<strong><a class="button" href="trackbacks.php?id='.$id.'&amp;auto=1">'.
	__('Auto discover ping URLs').'</a></strong>';
}

echo
'<h3>'.__('Ping blogs').'</h3>'.
'<form action="trackbacks.php" id="trackback-form" method="post">'.
'<p><label for="tb_urls" class="area">'.__('URLs to ping:').
form::textarea('tb_urls',60,5,$tb_urls).
'</label></p>'.

'<p><label for="tb_excerpt" class="area">'.__('Send excerpt:').
form::textarea('tb_excerpt',60,3,$tb_excerpt).'</label></p>'.

'<p>'.form::hidden('id',$id).
$core->formNonce().
'<input type="submit" value="'.__('Ping blogs').'" />&nbsp;&nbsp;'.
$auto_link.'</p>'.
'</form>';

$pings = $TB->getPostPings($id);

if (!$pings->isEmpty())
{
	echo '<h3>'.__('Previously sent pings').'</h3>';
	
	echo '<ul class="nice">';
	while ($pings->fetch()) {
		echo
		'<li>'.dt::dt2str(__('%Y-%m-%d %H:%M'),$pings->ping_dt).' - '.
		$pings->ping_url.'</li>';
	}
	echo '</ul>';
}

dcPage::close();
?>