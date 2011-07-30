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

dcPage::check('media,media_admin');

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
	foreach ($core->media->getRootDirs() as $v) {
		if ($v->w) {
			$dirs_combo['/'.$v->relname] = $v->relname;
		}
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
		http::redirect($page_url.'&id='.$id.'&fupl=1');
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
		http::redirect($page_url.'&id='.$id.'&fupd=1');
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
		http::redirect($page_url.'&id='.$id.'&thumbupd=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Unzip file
if (!empty($_POST['unzip']) && $file->type == 'application/zip' && $file->editable && $core_media_writable)
{
	try {
		$unzip_dir = $core->media->inflateZipFile($file,$_POST['inflate_mode'] == 'new');
		http::redirect($media_page_url.'&d='.$unzip_dir.'&unzipok=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Function to get image title based on meta
function dcGetImageTitle($file,$pattern)
{
	$res = array();
	$pattern = preg_split('/\s*;;\s*/',$pattern);
	$sep = ', ';
	
	foreach ($pattern as $v) {
		if ($v == 'Title') {
			$res[] = $file->media_title;
		} elseif ($file->media_meta->{$v}) {
			$res[] = (string) $file->media_meta->{$v};
		} elseif (preg_match('/^Date\((.+?)\)$/u',$v,$m)) {
			$res[] = dt::str($m[1],$file->media_dt);
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
$starting_scripts = dcPage::jsLoad('js/_media_item.js');
if ($popup) {
	$starting_scripts .=
	dcPage::jsLoad('js/jsToolBar/popup_media.js');
}
call_user_func($open_f,__('Media manager'),
	$starting_scripts.
	dcPage::jsDatePicker().
	dcPage::jsPageTabs()
);

if ($file === null) {
	call_user_func($close_f);
	exit;
}

if (!empty($_GET['fupd']) || !empty($_GET['fupl'])) {
	echo '<p class="message">'.__('File has been successfully updated.').'</p>';
}
if (!empty($_GET['thumbupd'])) {
	echo '<p class="message">'.__('Thumbnails have been successfully updated.').'</p>';
}

echo '<h2><a href="'.html::escapeURL($media_page_url).'">'.__('Media manager').'</a>'.
' / '.$core->media->breadCrumb(html::escapeURL($media_page_url).'&amp;d=%s').
'<span class="page-title">'.$file->basename.'</span></h2>';

# Insertion popup
if ($popup)
{
	$media_desc = $file->media_title;
	
	echo
	'<div id="media-insert" class="multi-part" title="'.__('Insert media item').'">'.
	'<form id="media-insert-form" action="" method="get">';
	
	if ($file->media_type == 'image')
	{
		$media_type = 'image';
		$media_desc = dcGetImageTitle($file,$core->blog->settings->system->media_img_title_pattern);
		if ($media_desc == $file->basename) {
			$media_desc = '';
		}
		
		echo
		'<h3>'.__('Image size:').'</h3> ';
		
		$s_checked = false;
		echo '<p>';
		foreach (array_reverse($file->media_thumb) as $s => $v) {
			$s_checked = ($s == 'm');
			echo '<label class="classic">'.
			form::radio(array('src'),html::escapeHTML($v),$s_checked).' '.
			$core->media->thumb_sizes[$s][2].'</label><br /> ';
		}
		$s_checked = (!isset($file->media_thumb['m']));
		echo '<label class="classic">'.
		form::radio(array('src'),$file->file_url,$s_checked).' '.__('original').'</label><br /> ';
		echo '</p>';
		
		
		echo '<h3>'.__('Image alignment').'</h3>';
		$i_align = array(
			'none' => array(__('None'),1),
			'left' => array(__('Left'),0),
			'right' => array(__('Right'),0),
			'center' => array(__('Center'),0)
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
		'<label for="insert1" class="classic">'.form::radio(array('insertion','insert1'),'simple',true).
		__('As a single image').'</label><br />'.
		'<label for="insert2" class="classic">'.form::radio(array('insertion','insert2'),'link',false).
		__('As a link to original image').'</label>'.
		'</p>';
	}
	elseif ($file->type == 'audio/mpeg3')
	{
		$media_type = 'mp3';
		
		echo '<h3>'.__('MP3 disposition').'</h3>'.
		'<p class="message">'.__("Please note that you cannot insert mp3 files with visual editor.").'</p>';
		
		$i_align = array(
			'none' => array(__('None'),0),
			'left' => array(__('Left'),0),
			'right' => array(__('Right'),0),
			'center' => array(__('Center'),1)
		);
		
		echo '<p>';
		foreach ($i_align as $k => $v) {
			echo '<label for="alignment" class="classic">'.
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
		
		echo
		'<p class="message">'.__("Please note that you cannot insert video files with visual editor.").'</p>';
		
		echo
		'<h3>'.__('Video size').'</h3>'.
		'<p><label for="video_w" class="classic">'.__('Width:').' '.
		form::field('video_w',3,4,400).'  '.
		'<label for="video_h" class="classic">'.__('Height:').' '.
		form::field('video_h',3,4,300).
		'</p>';
		
		echo '<h3>'.__('Video disposition').'</h3>';
		
		$i_align = array(
			'none' => array(__('None'),0),
			'left' => array(__('Left'),0),
			'right' => array(__('Right'),0),
			'center' => array(__('Center'),1)
		);
		
		echo '<p>';
		foreach ($i_align as $k => $v) {
			echo '<label for="alignment" class="classic">'.
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
	'<p><a id="media-insert-cancel" class="button" href="#">'.__('Cancel').'</a> - '.
	'<strong><a id="media-insert-ok" class="button" href="#">'.__('Insert').'</a></strong>'.
	form::hidden(array('type'),html::escapeHTML($media_type)).
	form::hidden(array('title'),html::escapeHTML($file->media_title)).
	form::hidden(array('description'),html::escapeHTML($media_desc)).
	form::hidden(array('url'),$file->file_url).
	'</p>';
	
	echo '</form></div>';
}

echo
'<div class="multi-part" title="'.__('Media details').'" id="media-details-tab">'.
'<p id="media-icon"><img src="'.$file->media_icon.'" alt="" /></p>';

echo
'<div id="media-details">';

if ($file->media_image)
{
	$thumb_size = !empty($_GET['size']) ? $_GET['size'] : 's';
	
	if (!isset($core->media->thumb_sizes[$thumb_size]) && $thumb_size != 'o') {
		$thumb_size = 's';
	}
	
	echo '<p>'.__('Available sizes:').' ';
	foreach (array_reverse($file->media_thumb) as $s => $v)
	{
		$strong_link = ($s == $thumb_size) ? '<strong>%s</strong>' : '%s';
		printf($strong_link,'<a href="'.html::escapeURL($page_url).
		'&amp;id='.$id.'&amp;size='.$s.'">'.$core->media->thumb_sizes[$s][2].'</a> | ');
	}
	echo '<a href="'.html::escapeURL($page_url).'&amp;id='.$id.'&amp;size=o">'.__('original').'</a>';
	echo '</p>';
	
	if (isset($file->media_thumb[$thumb_size])) {
		echo '<p><img src="'.$file->media_thumb[$thumb_size].'" alt="" /></p>';
	} elseif ($thumb_size == 'o') {
		$S = getimagesize($file->file);
		$class = ($S[1] > 500) ? ' class="overheight"' : '';
		unset($S);
		echo '<p id="media-original-image"'.$class.'><img src="'.$file->file_url.'" alt="" /></p>';
	}
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
	'<p><strong><a href="'.html::escapeHTML($page_url).'&amp;id='.$id.'&amp;find_posts=1">'.
	__('Show entries containing this media').'</a></strong></p>';
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
			echo '<li><a href="'.$core->getPostAdminURL($rs->post_type,$rs->post_id).'">'.
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
	
	if (count($file->media_meta) == 0)
	{
		echo '<p>'.__('No detail').'</p>';
	}
	else
	{
		echo '<ul>';
		foreach ($file->media_meta as $k => $v)
		{
			if ((string) $v) {
				echo '<li><strong>'.$k.':</strong> '.html::escapeHTML($v).'</li>';
			}
		}
		echo '</ul>';
	}
}

if ($file->editable && $core_media_writable)
{
	if ($file->media_type == 'image')
	{
		echo
		'<form class="clear" action="'.html::escapeURL($page_url).'" method="post">'.
		'<fieldset><legend>'.__('Update thumbnails').'</legend>'.
		'<p>'.__('This will create or update thumbnails for this image.').'</p>'.
		'<p><input type="submit" name="thumbs" value="'.__('Update thumbnails').'" />'.
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</fieldset></form>';
	}
	
	if ($file->type == 'application/zip')
	{
		$inflate_combo = array(
			__('Extract in a new directory') => 'new',
			__('Extract in current directory') => 'current'
		);
		
		echo
		'<form class="clear" id="file-unzip" action="'.html::escapeURL($page_url).'" method="post">'.
		'<fieldset><legend>'.__('Extract archive').'</legend>'.
		'<ul>'.
		'<li><strong>'.__('Extract in a new directory').'</strong> : '.
		__('This will extract archive in a new directory that should not exist yet.').'</li>'.
		'<li><strong>'.__('Extract in current directory').'</strong> : '.
		__('This will extract archive in current directory and will overwrite existing files or directory.').'</li>'.
		'</ul>'.
		'<p><label for="inflate_mode" class="classic">'.__('Extract mode:').' '.
		form::combo('inflate_mode',$inflate_combo,'new').'</label> '.
		'<input type="submit" name="unzip" value="'.__('Extract').'" />'.
		form::hidden(array('id'),$id).
		$core->formNonce().'</p>'.
		'</fieldset></form>';
	}
	
	echo
	'<form class="clear" action="'.html::escapeURL($page_url).'" method="post">'.
	'<fieldset><legend>'.__('Change media properties').'</legend>'.
	'<p><label for="media_file">'.__('File name:').
	form::field('media_file',30,255,html::escapeHTML($file->basename)).'</label></p>'.
	'<p><label for="media_title">'.__('File title:').
	form::field('media_title',30,255,html::escapeHTML($file->media_title)).'</label></p>'.
	'<p><label for="media_dt">'.__('File date:').
	form::field('media_dt',16,16,html::escapeHTML($file->media_dtstr)).'</label></p>'.
	'<p><label for="media_private" class="classic">'.form::checkbox('media_private',1,$file->media_priv).' '.
	__('Private').'</label></p>'.
	'<p><label for="media_path">'.__('New directory:').
	form::combo('media_path',$dirs_combo,dirname($file->relname)).'</label></p>'.
	'<p><input type="submit" accesskey="s" value="'.__('Save').'" />'.
	form::hidden(array('id'),$id).
	$core->formNonce().'</p>'.
	'</fieldset></form>';
	
	echo
	'<form class="clear" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">'.
	'<fieldset><legend>'.__('Change file').'</legend>'.
	'<div>'.form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).'</div>'.
	'<p><label for="upfile">'.__('Choose a file:').
	' ('.sprintf(__('Maximum size %s'),files::size(DC_MAX_UPLOAD_SIZE)).') '.
	'<input type="file" id="upfile" name="upfile" size="35" />'.
	'</label></p>'.
	'<p><input type="submit" value="'.__('Send').'" />'.
	form::hidden(array('id'),$id).
	$core->formNonce().'</p>'.
	'</fieldset></form>';

	# --BEHAVIOR-- adminMediaItemForm
	$core->callBehavior('adminMediaItemForm',$file);
}

echo
'</div>'.
'</div>';

call_user_func($close_f);
?>