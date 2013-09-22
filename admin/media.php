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

/* HTML page
-------------------------------------------------------- */
require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('media,media_admin');

$post_id = !empty($_GET['post_id']) ? (integer) $_GET['post_id'] : null;
if ($post_id) {
	$post = $core->blog->getPosts(array('post_id'=>$post_id,'post_type'=>''));
	if ($post->isEmpty()) {
		$post_id = null;
	}
	$post_title = $post->post_title;
	$post_type = $post->post_type;
	unset($post);
}
$d = isset($_REQUEST['d']) ? $_REQUEST['d'] : null;
$dir = null;

$page = !empty($_GET['page']) ? max(1,(integer) $_GET['page']) : 1;
$nb_per_page =  30;

# We are on home not comming from media manager
if ($d === null && isset($_SESSION['media_manager_dir'])) {
	# We get session information
	$d = $_SESSION['media_manager_dir'];
}

if (!isset($_GET['page']) && isset($_SESSION['media_manager_page'])) {
	$page = $_SESSION['media_manager_page'];
}

# We set session information about directory and page
if ($d) {
	$_SESSION['media_manager_dir'] = $d;
} else {
	unset($_SESSION['media_manager_dir']);
}
if ($page != 1) {
	$_SESSION['media_manager_page'] = $page;
} else {
	unset($_SESSION['media_manager_page']);
}

# Sort combo
$sort_combo = array(
	__('By names, in ascending order') => 'name-asc',
	__('By names, in descending order') => 'name-desc',
	__('By dates, in ascending order') => 'date-asc',
	__('By dates, in descending order') => 'date-desc'
	);

if (!empty($_GET['file_sort']) && in_array($_GET['file_sort'],$sort_combo)) {
	$_SESSION['media_file_sort'] = $_GET['file_sort'];
}
$file_sort = !empty($_SESSION['media_file_sort']) ? $_SESSION['media_file_sort'] : null;

$popup = (integer) !empty($_GET['popup']);

$page_url = 'media.php?popup='.$popup.'&post_id='.$post_id;

if ($popup) {
	$open_f = array('dcPage','openPopup');
	$close_f = array('dcPage','closePopup');
} else {
	$open_f = array('dcPage','open');
	$close_f = create_function('',"dcPage::helpBlock('core_media'); dcPage::close();");
}

$core_media_writable = false;
try {
	$core->media = new dcMedia($core);
	if ($file_sort) {
		$core->media->setFileSort($file_sort);
	}
	$core->media->chdir($d);
	$core->media->getDir();
	$core_media_writable = $core->media->writable();
	$dir =& $core->media->dir;
	if  (!$core_media_writable) {
//		throw new Exception('you do not have sufficient permissions to write to this folder: ');
	}
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

# Zip download
if (!empty($_GET['zipdl']) && $core->auth->check('media_admin',$core->blog->id))
{
	try
	{
		@set_time_limit(300);
		$fp = fopen('php://output','wb');
		$zip = new fileZip($fp);
		$zip->addExclusion('#(^|/).(.*?)_(m|s|sq|t).jpg$#');
		$zip->addDirectory($core->media->root.'/'.$d,'',true);
		
		header('Content-Disposition: attachment;filename='.($d ? $d : 'media').'.zip');
		header('Content-Type: application/x-zip');
		$zip->write();
		unset($zip);
		exit;
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# New directory
if ($dir && !empty($_POST['newdir']))
{
	try {
		$core->media->makeDir($_POST['newdir']);
		http::redirect($page_url.'&d='.rawurlencode($d).'&mkdok=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Adding a file
if ($dir && !empty($_FILES['upfile'])) {
  // only one file per request : @see option singleFileUploads in admin/js/jsUpload/jquery.fileupload
	$upfile = array('name' => $_FILES['upfile']['name'][0],
		'type' => $_FILES['upfile']['type'][0],
		'tmp_name' => $_FILES['upfile']['tmp_name'][0],
		'error' => $_FILES['upfile']['error'][0],
		'size' => $_FILES['upfile']['size'][0]
		);

	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
		header('Content-type: application/json');
		$message = array();

		try {
			files::uploadStatus($upfile);
			$new_file_id = $core->media->uploadFile($upfile['tmp_name'], $upfile['name']);

			$message['files'][] = array('name' => $upfile['name'],
				'size' => $upfile['size'],
				'html' => mediaItemLine($core->media->getFile($new_file_id), 1)
				);
		} catch (Exception $e) {
			$message['files'][] = array('name' => $upfile['name'],
				'size' => $upfile['size'],
				'error' => $e->getMessage()
				);
		}
		echo json_encode($message);
		exit();
	} else {
		try {
			files::uploadStatus($upfile);

			$f_title = (isset($_POST['upfiletitle']) ? $_POST['upfiletitle'] : '');
			$f_private = (isset($_POST['upfilepriv']) ? $_POST['upfilepriv'] : false);

			$core->media->uploadFile($upfile['tmp_name'], $upfile['name'], $f_title, $f_private);
			http::redirect($page_url.'&d='.rawurlencode($d).'&upok=1');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
}

# Removing item
if ($dir && !empty($_POST['rmyes']) && !empty($_POST['remove']))
{
	$_POST['remove'] = rawurldecode($_POST['remove']);
	
	try {
		$core->media->removeItem($_POST['remove']);
		http::redirect($page_url.'&d='.rawurlencode($d).'&rmfok=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Rebuild directory
if ($dir && $core->auth->isSuperAdmin() && !empty($_POST['rebuild']))
{
	try {
		$core->media->rebuild($d);
		http::redirect($page_url.'&d='.rawurlencode($d).'&rebuildok=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# DISPLAY confirm page for rmdir & rmfile
if ($dir && !empty($_GET['remove']) && empty($_GET['noconfirm']))
{
	call_user_func($open_f,__('Media manager'),'',
		dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				__('Media manager') => '',
				'<span class="page-title">'.__('confirm removal').'</span>' => ''
			),!$popup)
	);
	
	echo
	'<form action="'.html::escapeURL($page_url).'" method="post">'.
	'<p>'.sprintf(__('Are you sure you want to remove %s?'),
		html::escapeHTML($_GET['remove'])).'</p>'.
	'<p><input type="submit" value="'.__('Cancel').'" /> '.
	' &nbsp; <input type="submit" name="rmyes" value="'.__('Yes').'" />'.
	form::hidden('d',$d).
	$core->formNonce().
	form::hidden('remove',html::escapeHTML($_GET['remove'])).'</p>'.
	'</form>';
	
	call_user_func($close_f);
	exit;
}

/* DISPLAY Main page
-------------------------------------------------------- */
$core->auth->user_prefs->addWorkspace('interface');
$user_ui_enhanceduploader = $core->auth->user_prefs->interface->enhanceduploader;

if (!isset($core->media)) {
	$breadcrumb = dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Media manager').'</span>' => ''
		),!$popup);
} else {
	$breadcrumb_media = $core->media->breadCrumb(html::escapeURL($page_url).'&amp;d=%s','<span class="page-title">%s</span>');
	if ($breadcrumb_media == '') {
		$breadcrumb = dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				'<span class="page-title">'.__('Media manager').'</span>' => ''
			),!$popup);
	} else {
		$breadcrumb = dcPage::breadcrumb(
			array(
				html::escapeHTML($core->blog->name) => '',
				__('Media manager') => html::escapeURL($page_url.'&d='),
				$breadcrumb_media => ''
			),!$popup);
	}
}

call_user_func($open_f,__('Media manager'),
	dcPage::jsLoad('js/_media.js').
	($core_media_writable ? dcPage::jsUpload(array('d='.$d)) : ''),
	$breadcrumb
	);

if (!$core_media_writable) {
	dcPage::warning(__('You do not have sufficient permissions to write to this folder.'));
}

if (!empty($_GET['mkdok'])) {
	dcPage::success(__('Directory has been successfully created.'));
}

if (!empty($_GET['upok'])) {
	dcPage::success(__('Files have been successfully uploaded.'));
}

if (!empty($_GET['rmfok'])) {
	dcPage::success(__('File has been successfully removed.'));
}

if (!empty($_GET['rmdok'])) {
	dcPage::success(__('Directory has been successfully removed.'));
}

if (!empty($_GET['rebuildok'])) {
	dcPage::success(__('Directory has been successfully rebuilt.'));
}

if (!empty($_GET['unzipok'])) {
	dcPage::success(__('Zip file has been successfully extracted.'));
}

if (!$dir) {
	call_user_func($close_f);
	exit;
}

if ($post_id) {
	echo '<p class="form-note info">'.sprintf(__('Choose a file to attach to entry %s by clicking on %s.'),
		'<a href="'.$core->getPostAdminURL($post_type,$post_id).'">'.html::escapeHTML($post_title).'</a>',
		'<img src="images/plus.png" alt="'.__('Attach this file to entry').'" />').'</p>';
}
if ($popup) {
	echo '<div class="info"><p>'.sprintf(__('Choose a file to insert into entry by clicking on %s.'),
		'<img src="images/plus.png" alt="'.__('Attach this file to entry').'" />').'</p></div>';
}


$items = array_values(array_merge($dir['dirs'],$dir['files']));
echo '<div class="media-list">';
if (count($items) == 0)
{
	echo 
	'<p>'.__('No file.').'</p>'.
	'<div class="files-group"></div>'; // need for jsUpload to append new media 
}
else
{
	$pager = new dcPager($page,count($items),$nb_per_page,10);
	
	echo
	'<form action="media.php" method="get">'.
	'<p><label for="file_sort" class="classic">'.__('Sort files:').'</label> '.
	form::combo('file_sort',$sort_combo,$file_sort).
	form::hidden(array('popup'),$popup).
	form::hidden(array('post_id'),$post_id).
	'<input type="submit" value="'.__('Sort').'" /></p>'.
	'</form>'.
	$pager->getLinks();

	$dgroup = '';
	$fgroup = '';
	for ($i=$pager->index_start, $j=0; $i<=$pager->index_end; $i++,$j++)
	{
		if ($items[$i]->d) {
			$dgroup .= mediaItemLine($items[$i],$j);
		} else {
			$fgroup .= mediaItemLine($items[$i],$j);
		}
	}
	echo 
		($dgroup != '' ? '<div class="folders-group">'.$dgroup.'</div>' : '').
		'<div class="files-group">'.$fgroup.'</div>';
	
	echo $pager->getLinks();
}
if (!isset($pager)) {
	echo
	'<p class="clear"></p>';
}
echo
'</div>';

echo
'<div class="clearfix"><p class="info">'.sprintf(__('Current settings for medias and images are defined in %s'),
'<a href="blog_pref.php#medias-settings">'.__('Blog parameters').'</a>').'</p></div>';

if ($core_media_writable || $core_media_archivable) {
	echo
	'<div class="vertical-separator">'.
	'<h3 class="out-of-screen-if-js">'.sprintf(__('In %s:'),($d == '' ? '“'.__('Media manager').'”' : '“'.$d.'”')).'</h3>';	
}

$core_media_archivable = $core->auth->check('media_admin',$core->blog->id) && 
	!(count($items) == 0 || (count($items) == 1 && $items[0]->parent));

if ($core_media_writable || $core_media_archivable) {
	echo 
	'<div class="two-boxes odd">';

	# Create directory
	if ($core_media_writable)
	{
		echo
		'<form action="'.html::escapeURL($page_url).'" method="post" class="fieldset">'.
		'<div id="new-dir-f">'.
		'<h4 class="pretty-title">'.__('Create new directory').'</h4>'.
		$core->formNonce().
		'<p><label for="newdir">'.__('Directory Name:').'</label>'.
		form::field(array('newdir','newdir'),35,255).'</p>'.
		'<p><input type="submit" value="'.__('Create').'" />'.
		form::hidden(array('d'),html::escapeHTML($d)).'</p>'.
		'</div>'.
		'</form>';
	}

	# Get zip directory
	if ($core_media_archivable)
	{
		echo
		'<div class="fieldset">'.
		'<h4 class="pretty-title">'.sprintf(__('Backup content of %s'),($d == '' ? '“'.__('Media manager').'”' : '“'.$d.'”')).'</h4>'.
		'<p><a class="button submit" href="'.html::escapeURL($page_url).'&amp;zipdl=1">'.
		__('Download zip file').'</a></p>'.
		'</div>';
	}

	echo 
	'</div>';
}

if ($core_media_writable)
{
	echo
	'<div class="two-boxes fieldset">';	
	if ($user_ui_enhanceduploader) {
		echo
		'<div class="enhanced_uploader">';
	} else {
		echo
		'<div>';
	}

	echo
	'<h4>'.__('Add files').'</h4>'.
	'<p>'.__('Please take care to publish media that you own and that are not protected by copyright.').'</p>'.
	'<form id="fileupload" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data" aria-disabled="false">'.
	'<p>'.form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
	$core->formNonce().'</p>'.
	'<div class="fileupload-ctrl"><p class="queue-message"></p><ul class="files"></ul></div>';

	echo
	'<div class="fileupload-buttonbar clear">';

	echo
	'<p><label for="upfile">'.'<span class="add-label one-file">'.__('Choose file').'</span>'.'</label>'.
	'<button class="button choose_files">'.__('Choose files').'</button>'.
	'<input type="file" id="upfile" name="upfile[]"'.($user_ui_enhanceduploader?' multiple="mutiple"':'').' data-url="'.html::escapeURL($page_url).'" /></p>';

	echo
	'<p class="max-sizer form-note">&nbsp;'.__('Maximum file size allowed:').' '.files::size(DC_MAX_UPLOAD_SIZE).'</p>';

	echo
	'<p class="one-file"><label for="upfiletitle">'.__('Title:').'</label>'.form::field(array('upfiletitle','upfiletitle'),35,255).'</p>'.
	'<p class="one-file"><label for="upfilepriv" class="classic">'.__('Private').'</label> '.
	form::checkbox(array('upfilepriv','upfilepriv'),1).'</p>';

	if (!$user_ui_enhanceduploader) {
		echo
		'<p class="one-file form-help info">'.__('To send several files at the same time, you can activate the enhanced uploader in').
		' <a href="preferences.php?tab=user-options">'.__('My preferences').'</a></p>';
	}

	echo
	'<p class="clear"><button class="button clean">'.__('Refresh').'</button>'.
	'<input class="button cancel one-file" type="reset" value="'.__('Clear all').'"/>'.
	'<input class="button start" type="submit" value="'.__('Upload').'"/></p>'.
	'</div>';

	echo
	'<p style="clear:both;">'.form::hidden(array('d'),$d).'</p>'.
	'</form>'.
	'</div>'.
	'</div>';
}

# Empty remove form (for javascript actions)
echo
'<form id="media-remove-hide" action="'.html::escapeURL($page_url).'" method="post" class="hidden">'.
'<div>'.
form::hidden('rmyes',1).form::hidden('d',html::escapeHTML($d)).
form::hidden('remove','').
$core->formNonce().
'</div>'.
'</form>';

if ($core_media_writable || $core_media_archivable) {
	echo 
	'</div>';
}

call_user_func($close_f);

/* ----------------------------------------------------- */
function mediaItemLine($f,$i)
{
	global $core, $page_url, $popup, $post_id;
	
	$fname = $f->basename;
	
	$class = 'media-item media-col-'.($i%2);
	
	if ($f->d) {
		$link = html::escapeURL($page_url).'&amp;d='.html::sanitizeURL($f->relname);
		if ($f->parent) {
			$fname = '..';
			$class .= ' media-folder-up';
		} else {
			$class .= ' media-folder';
		}
	} else {
		$link =
		'media_item.php?id='.$f->media_id.'&amp;popup='.$popup.'&amp;post_id='.$post_id;
	}
	
	$res =
	'<div class="'.$class.'"><p><a class="media-icon media-link" href="'.$link.'">'.
	'<img src="'.$f->media_icon.'" alt="" />'.$fname.'</a></p>';

	$lst = '';
	
	if (!$f->d) {
		$lst .=
		'<li>'.$f->media_title.'</li>'.
		'<li>'.
		$f->media_dtstr.' - '.
		files::size($f->size).' - '.
		'<a href="'.$f->file_url.'">'.__('open').'</a>'.
		'</li>';
	}
	
	$act = '';
	
	if ($post_id && !$f->d) {
		$act .= 
		'<form action="post_media.php" method="post">'.
		'<input type="image" src="images/plus.png" alt="'.__('Attach this file to entry').'" '.
		'title="'.__('Attach this file to entry').'" /> '.
		form::hidden('media_id',$f->media_id).
		form::hidden('post_id',$post_id).
		form::hidden('attach',1).
		$core->formNonce().
		'</form>';
	}
	
	if ($popup && !$f->d) {
		$act .= '<a href="'.$link.'"><img src="images/plus.png" alt="'.__('Insert this file into entry').'" '.
		'title="'.__('Insert this file into entry').'" /></a> ';
	}
	
	if ($f->del) {
		$act .= '<a class="media-remove" '.
		'href="'.html::escapeURL($page_url).'&amp;d='.
		rawurlencode($GLOBALS['d']).'&amp;remove='.rawurlencode($f->basename).'">'.
		'<img src="images/trash.png" alt="'.__('Delete').'" title="'.__('delete').'" /></a>';
	}
	
	$lst .= ($act != '' ? '<li class="media-action">&nbsp;'.$act.'</li>' : '');
	
	if ($f->type == 'audio/mpeg3') {
		$lst .= '<li>'.dcMedia::mp3player($f->file_url,'index.php?pf=player_mp3.swf').'</li>';
	}
	
	$res .=	($lst != '' ? '<ul>'.$lst.'</ul>' : '');

	$res .= '</div>';
	
	return $res;
}
?>
