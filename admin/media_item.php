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

dcPage::check('media,media_admin');

$tab = empty($_REQUEST['tab']) ? '' : $_REQUEST['tab'];

$post_id = !empty($_GET['post_id']) ? (integer) $_GET['post_id'] : null;
if ($post_id) {
	$post = $core->blog->getPosts(array('post_id'=>$post_id));
	if ($post->isEmpty()) {
		$post_id = null;
	}
	$post_title = $post->post_title;
	unset($post);
}

$file = null;
$popup = (integer) !empty($_GET['popup']);
$page_url = 'media_item.php?popup='.$popup.'&post_id='.$post_id;
$media_page_url = 'media.php?popup='.$popup.'&post_id='.$post_id;

$id = !empty($_REQUEST['id']) ? (integer) $_REQUEST['id'] : '';

if ($popup) {
	$open_f = array('dcPage','openPopup');
	$close_f = array('dcPage','closePopup');
} else {
	$open_f = array('dcPage','open');
	$close_f = create_function('',"dcPage::helpBlock('core_media'); dcPage::close();");
}

$core_media_writable = false;
try
{
	$core->media = new dcMedia($core);
	
	if ($id) {
		$file = $core->media->getFile($id);
	}
	
	if ($file === null) {
		throw new Exception(__('Not a valid file'));
	}
	
	$core->media->chdir(dirname($file->relname));
	$core_media_writable = $core->media->writable();
	
	# Prepare directories combo box
	$dirs_combo = array();
	foreach ($core->media->getDBDirs() as $v) {
		$dirs_combo['/'.$v] = $v;
	}
	# Add parent and direct childs directories if any
	$core->media->getFSDir();
	foreach ($core->media->dir['dirs'] as $k => $v) {
		$dirs_combo['/'.$v->relname] = $v->relname;
	}
	ksort($dirs_combo);
}
catch (Exception $e)
{
	$core->error->add($e->getMessage());
}

# Upload a new file
if ($file && !empty($_FILES['upfile']) && $file->editable && $core_media_writable)
{
	try {
		files::uploadStatus($_FILES['upfile']);
		$core->media->uploadFile($_FILES['upfile']['tmp_name'],$file->basename,null,false,true);

		dcPage::addSuccessNotice(__('File has been successfully updated.'));
		http::redirect($page_url.'&id='.$id);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Update file
if ($file && !empty($_POST['media_file']) && $file->editable && $core_media_writable)
{
	$newFile = clone $file;
	
	$newFile->basename = $_POST['media_file'];
	
	if ($_POST['media_path']) {
		$newFile->dir = $_POST['media_path'];
		$newFile->relname = $_POST['media_path'].'/'.$newFile->basename;
	} else {
		$newFile->dir = '';
		$newFile->relname = $newFile->basename;
	}
	$newFile->media_title = $_POST['media_title'];
	$newFile->media_dt = strtotime($_POST['media_dt']);
	$newFile->media_dtstr = $_POST['media_dt'];
	$newFile->media_priv = !empty($_POST['media_private']);
	
	try {
		$core->media->updateFile($file,$newFile);

		dcPage::addSuccessNotice(__('File has been successfully updated.'));
		http::redirect($page_url.'&id='.$id.'&tab=media-details-tab');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Update thumbnails
if (!empty($_POST['thumbs']) && $file->media_type == 'image' && $file->editable && $core_media_writable)
{
	try {
		$foo = null;
		$core->media->mediaFireRecreateEvent($file);
		
		dcPage::addSuccessNotice(__('Thumbnails have been successfully updated.'));
		http::redirect($page_url.'&id='.$id.'&tab=media-details-tab');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Unzip file
if (!empty($_POST['unzip']) && $file->type == 'application/zip' && $file->editable && $core_media_writable)
{
	try {
		$unzip_dir = $core->media->inflateZipFile($file,$_POST['inflate_mode'] == 'new');
		
		dcPage::addSuccessNotice(__('Zip file has been successfully extracted.'));
		http::redirect($media_page_url.'&d='.$unzip_dir);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Save media insertion settings for the blog
if (!empty($_POST['save_blog_prefs']))
{
	if (!empty($_POST['pref_src'])) {
		foreach (array_reverse($file->media_thumb) as $s => $v) {
			if ($v == $_POST['pref_src']) {
				$core->blog->settings->system->put('media_img_default_size',$s);
				break;
			}
		}
	}
	if (!empty($_POST['pref_alignment'])) {
		$core->blog->settings->system->put('media_img_default_alignment',$_POST['pref_alignment']);
	}
	if (!empty($_POST['pref_insertion'])) {
		$core->blog->settings->system->put('media_img_default_link',($_POST['pref_insertion'] == 'link'));
	}
	
	dcPage::addSuccessNotice(__('Default media insertion settings have been successfully updated.'));
	http::redirect($page_url.'&id='.$id);
}

# Function to get image title based on meta
function dcGetImageTitle($file,$pattern,$dto_first=false)
{
	$res = array();
	$pattern = preg_split('/\s*;;\s*/',$pattern);
	$sep = ', ';
	
	foreach ($pattern as $v) {
		if ($v == 'Title') {
			if ($file->media_title != '') {
				$res[] = $file->media_title;
			}
		} elseif ($file->media_meta->{$v}) {
			if ((string) $file->media_meta->{$v} != '') {
				$res[] = (string) $file->media_meta->{$v};
			}
		} elseif (preg_match('/^Date\((.+?)\)$/u',$v,$m)) {
			if ($dto_first && ($file->media_meta->DateTimeOriginal != 0)) {
				$res[] = dt::dt2str($m[1],(string) $file->media_meta->DateTimeOriginal);
			} else {
				$res[] = dt::str($m[1],$file->media_dt);
			}
		} elseif (preg_match('/^DateTimeOriginal\((.+?)\)$/u',$v,$m) && $file->media_meta->DateTimeOriginal) {
			$res[] = dt::dt2str($m[1],(string) $file->media_meta->DateTimeOriginal);
		} elseif (preg_match('/^separator\((.*?)\)$/u',$v,$m)) {
			$sep = $m[1];
		}
	}
	return implode($sep,$res);
}

/* DISPLAY Main page
-------------------------------------------------------- */
$starting_scripts = 
	'<script type="text/javascript">'."\n".
	"//<![CDATA["."\n".
	dcPage::jsVar('dotclear.msg.confirm_delete_media',__('Are you sure to delete this media?'))."\n".
	"//]]>".
	"</script>".
	dcPage::jsLoad('js/_media_item.js');
if ($popup) {
	$starting_scripts .=
	dcPage::jsLoad('js/jsToolBar/popup_media.js');
}
call_user_func($open_f,__('Media manager'),
	$starting_scripts.
	dcPage::jsDatePicker().
	($popup ? dcPage::jsPageTabs($tab) : ''),
	dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			__('Media manager') => html::escapeURL($media_page_url).'&amp;d=',
			$core->media->breadCrumb(html::escapeURL($media_page_url).'&amp;d=%s').'<span class="page-title">'.$file->basename.'</span>' => ''
		),
		array(
			'home_link' => !$popup,
			'hl' => false
		)
	)
);

if ($file === null) {
	call_user_func($close_f);
	exit;
}

if (!empty($_GET['fupd']) || !empty($_GET['fupl'])) {
	dcPage::success(__('File has been successfully updated.'));
}
if (!empty($_GET['thumbupd'])) {
	dcPage::success(__('Thumbnails have been successfully updated.'));
}
if (!empty($_GET['blogprefupd'])) {
	dcPage::success(__('Default media insertion settings have been successfully updated.'));
}

# Insertion popup
if ($popup)
{
	$media_desc = $file->media_title;
	
	echo
	'<div id="media-insert" class="multi-part" title="'.__('Insert media item').'">'.
	'<h3>'.__('Insert media item').'</h3>'.
	'<form id="media-insert-form" action="" method="get">';
	
	$media_img_default_size = $core->blog->settings->system->media_img_default_size;
	if ($media_img_default_size == '') {
		$media_img_default_size = 'm';
	}
	$media_img_default_alignment = $core->blog->settings->system->media_img_default_alignment;
	if ($media_img_default_alignment == '') {
		$media_img_default_alignment = 'none';
	}
	$media_img_default_link = (boolean)$core->blog->settings->system->media_img_default_link;

	if ($file->media_type == 'image')
	{
		$media_type = 'image';
		$media_desc = dcGetImageTitle($file,
			$core->blog->settings->system->media_img_title_pattern,
			$core->blog->settings->system->media_img_use_dto_first);
		if ($media_desc == $file->basename) {
			$media_desc = '';
		}

		echo
		'<h3>'.__('Image size:').'</h3> ';
		
		$s_checked = false;
		echo '<p>';
		foreach (array_reverse($file->media_thumb) as $s => $v) {
			$s_checked = ($s == $media_img_default_size);
			echo '<label class="classic">'.
			form::radio(array('src'),html::escapeHTML($v),$s_checked).' '.
			$core->media->thumb_sizes[$s][2].'</label><br /> ';
		}
		$s_checked = (!isset($file->media_thumb[$media_img_default_size]));
		echo '<label class="classic">'.
		form::radio(array('src'),$file->file_url,$s_checked).' '.__('original').'</label><br /> ';
		echo '</p>';
		
		
		echo '<h3>'.__('Image alignment').'</h3>';
		$i_align = array(
			'none' => array(__('None'),($media_img_default_alignment == 'none' ? 1 : 0)),
			'left' => array(__('Left'),($media_img_default_alignment == 'left' ? 1 : 0)),
			'right' => array(__('Right'),($media_img_default_alignment == 'right' ? 1 : 0)),
			'center' => array(__('Center'),($media_img_default_alignment == 'center' ? 1 : 0))
		);
		
		echo '<p>';
		foreach ($i_align as $k => $v) {
			echo '<label class="classic">'.
			form::radio(array('alignment'),$k,$v[1]).' '.$v[0].'</label><br /> ';
		}
		echo '</p>';
		
		echo
		'<h3>'.__('Image insertion').'</h3>'.
		'<p>'.
		'<label for="insert1" class="classic">'.form::radio(array('insertion','insert1'),'simple',!$media_img_default_link).
		__('As a single image').'</label><br />'.
		'<label for="insert2" class="classic">'.form::radio(array('insertion','insert2'),'link',$media_img_default_link).
		__('As a link to the original image').'</label>'.
		'</p>';
	}
	elseif ($file->type == 'audio/mpeg3')
	{
		$media_type = 'mp3';
		
		echo '<h3>'.__('MP3 disposition').'</h3>';
		dcPage::message(__("Please note that you cannot insert mp3 files with visual editor."),false);
		
		$i_align = array(
			'none' => array(__('None'),($media_img_default_alignment == 'none' ? 1 : 0)),
			'left' => array(__('Left'),($media_img_default_alignment == 'left' ? 1 : 0)),
			'right' => array(__('Right'),($media_img_default_alignment == 'right' ? 1 : 0)),
			'center' => array(__('Center'),($media_img_default_alignment == 'center' ? 1 : 0))
		);
		
		echo '<p>';
		foreach ($i_align as $k => $v) {
			echo '<label class="classic">'.
			form::radio(array('alignment'),$k,$v[1]).' '.$v[0].'</label><br /> ';
		}
		
		$public_player_style = unserialize($core->blog->settings->themes->mp3player_style);
		$public_player = dcMedia::mp3player($file->file_url,$core->blog->getQmarkURL().'pf=player_mp3.swf',$public_player_style);
		echo form::hidden('public_player',html::escapeHTML($public_player));
		echo '</p>';
	}
	elseif ($file->type == 'video/x-flv' || $file->type == 'video/mp4' || $file->type == 'video/x-m4v')
	{
		$media_type = 'flv';
		
		dcPage::message(__("Please note that you cannot insert video files with visual editor."),false);
		
		echo
		'<h3>'.__('Video size').'</h3>'.
		'<p><label for="video_w" class="classic">'.__('Width:').'</label> '.
		form::field('video_w',3,4,400).'  '.
		'<label for="video_h" class="classic">'.__('Height:').'</label> '.
		form::field('video_h',3,4,300).
		'</p>';
		
		echo '<h3>'.__('Video disposition').'</h3>';
		
		$i_align = array(
			'none' => array(__('None'),($media_img_default_alignment == 'none' ? 1 : 0)),
			'left' => array(__('Left'),($media_img_default_alignment == 'left' ? 1 : 0)),
			'right' => array(__('Right'),($media_img_default_alignment == 'right' ? 1 : 0)),
			'center' => array(__('Center'),($media_img_default_alignment == 'center' ? 1 : 0))
		);
		
		echo '<p>';
		foreach ($i_align as $k => $v) {
			echo '<label class="classic">'.
			form::radio(array('alignment'),$k,$v[1]).' '.$v[0].'</label><br /> ';
		}
		
		$public_player_style = unserialize($core->blog->settings->themes->flvplayer_style);
		$public_player = dcMedia::flvplayer($file->file_url,$core->blog->getQmarkURL().'pf=player_flv.swf',$public_player_style);
		echo form::hidden('public_player',html::escapeHTML($public_player));
		echo '</p>';
	}
	else
	{
		$media_type = 'default';
		echo '<p>'.__('Media item will be inserted as a link.').'</p>';
	}

	echo
	'<p>'.
	'<a id="media-insert-ok" class="button submit" href="#">'.__('Insert').'</a> '.
	'<a id="media-insert-cancel" class="button" href="#">'.__('Cancel').'</a>'.
	form::hidden(array('type'),html::escapeHTML($media_type)).
	form::hidden(array('title'),html::escapeHTML($file->media_title)).
	form::hidden(array('description'),html::escapeHTML($media_desc)).
	form::hidden(array('url'),$file->file_url).
	'</p>';
	
	echo '</form>';

	if ($media_type != 'default') {
		echo
		'<div class="border-top">'.
		'<form id="save_settings" action="'.html::escapeURL($page_url).'" method="post">'.
		'<p>'.__('Make current settings as default').' '.
		'<input class="reset" type="submit" name="save_blog_prefs" value="'.__('OK').'" />'.
		form::hidden(array('pref_src'),'').
		form::hidden(array('pref_alignment'),'').
		form::hidden(array('pref_insertion'),'').
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</form>'.'</div>';
	}

	echo '</div>';
}

if ($popup) {
	echo
	'<div class="multi-part" title="'.__('Media details').'" id="media-details-tab">';
} else {
	echo '<h3 class="out-of-screen-if-js">'.__('Media details').'</h3>';
}
echo
'<p id="media-icon"><img src="'.$file->media_icon.'?'.time()*rand().'" alt="" /></p>';

echo
'<div id="media-details">'.
'<div class="near-icon">';

if ($file->media_image)
{
	$thumb_size = !empty($_GET['size']) ? $_GET['size'] : 's';
	
	if (!isset($core->media->thumb_sizes[$thumb_size]) && $thumb_size != 'o') {
		$thumb_size = 's';
	}
	
	if (isset($file->media_thumb[$thumb_size])) {
		echo '<p><img src="'.$file->media_thumb[$thumb_size].'?'.time()*rand().'" alt="" /></p>';
	} elseif ($thumb_size == 'o') {
		$S = getimagesize($file->file);
		$class = ($S[1] > 500) ? ' class="overheight"' : '';
		unset($S);
		echo '<p id="media-original-image"'.$class.'><img src="'.$file->file_url.'?'.time()*rand().'" alt="" /></p>';
	}
	
	echo '<p>'.__('Available sizes:').' ';
	foreach (array_reverse($file->media_thumb) as $s => $v)
	{
		$strong_link = ($s == $thumb_size) ? '<strong>%s</strong>' : '%s';
		printf($strong_link,'<a href="'.html::escapeURL($page_url).
		'&amp;id='.$id.'&amp;size='.$s.'&amp;tab=media-details-tab">'.$core->media->thumb_sizes[$s][2].'</a> | ');
	}
	echo '<a href="'.html::escapeURL($page_url).'&amp;id='.$id.'&amp;size=o&amp;tab=media-details-tab">'.__('original').'</a>';
	echo '</p>';
}

if ($file->type == 'audio/mpeg3')
{
	echo dcMedia::mp3player($file->file_url,'index.php?pf=player_mp3.swf');
}

if ($file->type == 'video/x-flv' || $file->type == 'video/mp4' || $file->type == 'video/x-m4v')
{
	echo dcMedia::flvplayer($file->file_url,'index.php?pf=player_flv.swf');
}

echo
'<h3>'.__('Media details').'</h3>'.
'<ul>'.
	'<li><strong>'.__('File owner:').'</strong> '.$file->media_user.'</li>'.
	'<li><strong>'.__('File type:').'</strong> '.$file->type.'</li>'.
	'<li><strong>'.__('File size:').'</strong> '.files::size($file->size).'</li>'.
	'<li><strong>'.__('File URL:').'</strong> <a href="'.$file->file_url.'">'.$file->file_url.'</a></li>'.
'</ul>';

if (empty($_GET['find_posts']))
{
	echo
	'<p><a class="button" href="'.html::escapeHTML($page_url).'&amp;id='.$id.'&amp;find_posts=1&amp;tab=media-details-tab">'.
	__('Show entries containing this media').'</a></p>';
}
else
{
	echo '<h3>'.__('Entries containing this media').'</h3>';
	$params = array(
		'post_type' => '',
		'from' => 'LEFT OUTER JOIN '.$core->prefix.'post_media PM ON P.post_id = PM.post_id ',
		'sql' => 'AND ('.
			'PM.media_id = '.(integer) $id.' '.
			"OR post_content_xhtml LIKE '%".$core->con->escape($file->relname)."%' ".
			"OR post_excerpt_xhtml LIKE '%".$core->con->escape($file->relname)."%' "
	);
	
	if ($file->media_image)
	{ # We look for thumbnails too
		if (preg_match('#^http(s)?://#',$core->blog->settings->system->public_url)) {
			$media_root = $core->blog->settings->system->public_url;
		} else {
			$media_root = $core->blog->host.path::clean($core->blog->settings->system->public_url).'/';
		}
		foreach ($file->media_thumb as $v) {
			$v = preg_replace('/^'.preg_quote($media_root,'/').'/','',$v);
			$params['sql'] .= "OR post_content_xhtml LIKE '%".$core->con->escape($v)."%' ";
			$params['sql'] .= "OR post_excerpt_xhtml LIKE '%".$core->con->escape($v)."%' ";
		}
	}
	
	$params['sql'] .= ') ';
	
	$rs = $core->blog->getPosts($params);
	
	if ($rs->isEmpty())
	{
		echo '<p>'.__('No entry seems contain this media.').'</p>';
	}
	else
	{
		echo '<ul>';
		while ($rs->fetch()) {
			$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
			switch ($rs->post_status) {
				case 1:
					$img_status = sprintf($img,__('published'),'check-on.png');
					break;
				case 0:
					$img_status = sprintf($img,__('unpublished'),'check-off.png');
					break;
				case -1:
					$img_status = sprintf($img,__('scheduled'),'scheduled.png');
					break;
				case -2:
					$img_status = sprintf($img,__('pending'),'check-wrn.png');
					break;
			}
			echo '<li>'.$img_status.' '.'<a href="'.$core->getPostAdminURL($rs->post_type,$rs->post_id).'">'.
				$rs->post_title.'</a>'.
				($rs->post_type != 'post' ? ' ('.html::escapeHTML($rs->post_type).')' : '').
				' - '.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->post_dt).'</li>';
		}
		echo '</ul>';
	}
}

if ($file->type == 'image/jpeg')
{
	echo '<h3>'.__('Image details').'</h3>';
	
	$details = '';
	if (count($file->media_meta) > 0)
	{
		foreach ($file->media_meta as $k => $v)
		{
			if ((string) $v) {
				$details .= '<li><strong>'.$k.':</strong> '.html::escapeHTML($v).'</li>';
			}
		}
	}
	if ($details) {
		echo '<ul>'.$details.'</ul>';
	} else {
		echo '<p>'.__('No detail').'</p>';
	}
}

echo '</div>';

echo '<h3>'.__('Updates and modifications').'</h3>';

if ($file->editable && $core_media_writable)
{
	if ($file->media_type == 'image')
	{
		echo
		'<form class="clear fieldset" action="'.html::escapeURL($page_url).'" method="post">'.
		'<h4>'.__('Update thumbnails').'</h4>'.
		'<p>'.__('This will create or update thumbnails for this image.').'</p>'.
		'<p><input type="submit" name="thumbs" value="'.__('Update thumbnails').'" />'.
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</form>';
	}
	
	if ($file->type == 'application/zip')
	{
		$inflate_combo = array(
			__('Extract in a new directory') => 'new',
			__('Extract in current directory') => 'current'
		);
		
		echo
		'<form class="clear fieldset" id="file-unzip" action="'.html::escapeURL($page_url).'" method="post">'.
		'<h4>'.__('Extract archive').'</h4>'.
		'<ul>'.
		'<li><strong>'.__('Extract in a new directory').'</strong> : '.
		__('This will extract archive in a new directory that should not exist yet.').'</li>'.
		'<li><strong>'.__('Extract in current directory').'</strong> : '.
		__('This will extract archive in current directory and will overwrite existing files or directory.').'</li>'.
		'</ul>'.
		'<p><label for="inflate_mode" class="classic">'.__('Extract mode:').'</label> '.
		form::combo('inflate_mode',$inflate_combo,'new').
		'<input type="submit" name="unzip" value="'.__('Extract').'" />'.
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</form>';
	}
	
	echo
	'<form class="clear fieldset" action="'.html::escapeURL($page_url).'" method="post">'.
	'<h4>'.__('Change media properties').'</h4>'.
	'<p><label for="media_file">'.__('File name:').'</label>'.
	form::field('media_file',30,255,html::escapeHTML($file->basename)).'</p>'.
	'<p><label for="media_title">'.__('File title:').'</label>'.
	form::field('media_title',30,255,html::escapeHTML($file->media_title)).'</p>'.
	'<p><label for="media_dt">'.__('File date:').'</label>'.
	form::field('media_dt',16,16,html::escapeHTML($file->media_dtstr)).'</p>'.
	'<p><label for="media_private" class="classic">'.form::checkbox('media_private',1,$file->media_priv).' '.
	__('Private').'</label></p>'.
	'<p><label for="media_path">'.__('New directory:').'</label>'.
	form::combo('media_path',$dirs_combo,dirname($file->relname)).'</p>'.
	'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
	form::hidden(array('id'),$id).
	$core->formNonce().'</p>'.
	'</form>';
	
	echo
	'<form class="clear fieldset" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">'.
	'<h4>'.__('Change file').'</h4>'.
	'<div>'.form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).'</div>'.
	'<p><label for="upfile">'.__('Choose a file:').
	' ('.sprintf(__('Maximum size %s'),files::size(DC_MAX_UPLOAD_SIZE)).') '.
	'<input type="file" id="upfile" name="upfile" size="35" />'.
	'</label></p>'.
	'<p><input type="submit" value="'.__('Send').'" />'.
	form::hidden(array('id'),$id).
	$core->formNonce().'</p>'.
	'</form>';

	if ($file->del) {
		echo
		'<form id="delete-form" method="post" action="'.html::escapeURL($media_page_url).
		'&amp;d='.rawurlencode(dirname($file->relname)).
		'&amp;remove='.rawurlencode($file->basename).'">'.
		'<p><input name="delete" type="submit" class="delete" value="'.__('Delete this media').'" />'.
		form::hidden('remove',rawurlencode($file->basename)).
		form::hidden('rmyes',1).
		$core->formNonce().'</p>'.
		'</form>';
	}


	# --BEHAVIOR-- adminMediaItemForm
	$core->callBehavior('adminMediaItemForm',$file);
}

echo
'</div>';
if ($popup) {
	echo
	'</div>';
}

call_user_func($close_f);
?>