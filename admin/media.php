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

/* Upload backend
-------------------------------------------------------- */
if (!empty($_POST['swfupload']))
{
	header('content-type: text/plain');
	try
	{
		if (empty($_POST['sess_id']) || empty($_POST['sess_uid'])) {
			throw new Exception('No credentials.');
		}
		define('DC_AUTH_SESS_ID',$_POST['sess_id']);
		define('DC_AUTH_SESS_UID',$_POST['sess_uid']);
		
		require dirname(__FILE__).'/../inc/admin/prepend.php';
		
		if (!$core->auth->check('media,media_admin',$core->blog->id)) {
			throw new Exception('Permission denied.');
		}
		
		$d = isset($_POST['d']) ? $_POST['d'] : null;
		$core->media = new dcMedia($core);
		$core->media->chdir($d);
		$core->media->getDir();
		$dir =& $core->media->dir;
		
		if (empty($_FILES['Filedata'])) {
			throw new Exception('No file to upload.');
		}
		
		files::uploadStatus($_FILES['Filedata']);
		$core->media->uploadFile($_FILES['Filedata']['tmp_name'],$_FILES['Filedata']['name']);
		
		echo 'ok';
	}
	catch (Exception $e) {
		echo __('Error:').' '.__($e->getMessage());
	}
	exit;
}


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

$page = !empty($_GET['page']) ? $_GET['page'] : 1;
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
		throw new Exception('you do not have sufficient permissions to write to this folder: ');
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
if ($dir && !empty($_FILES['upfile']))
{
	try
	{
		files::uploadStatus($_FILES['upfile']);
		
		$f_title = (isset($_POST['upfiletitle']) ? $_POST['upfiletitle'] : '');
		$f_private = (isset($_POST['upfilepriv']) ? $_POST['upfilepriv'] : false);
		
		$core->media->uploadFile($_FILES['upfile']['tmp_name'],$_FILES['upfile']['name'],$f_title,$f_private);
		http::redirect($page_url.'&d='.rawurlencode($d).'&upok=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
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
if ($dir && !empty($_GET['remove']))
{
	call_user_func($open_f,__('Media manager'));
	
	echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.__('Media manager').' &rsaquo; <span class="page-title">'.__('confirm removal').'</span></h2>';
	
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

call_user_func($open_f,__('Media manager'),
	'<script type="text/javascript">'."\n".
	"//<![CDATA["."\n".
	dcPage::jsVar('dotclear.candyUpload_force_init',$user_ui_enhanceduploader)."\n".
	"//]]>".
	"</script>".
	dcPage::jsLoad('js/_media.js').
	(($user_ui_enhanceduploader && $core_media_writable) ? dcPage::jsCandyUpload(array('d='.$d)) : '')
	);

if (!empty($_GET['mkdok'])) {
	echo '<p class="message">'.__('Directory has been successfully created.').'</p>';
}

if (!empty($_GET['upok'])) {
	echo '<p class="message">'.__('Files have been successfully uploaded.').'</p>';
}

if (!empty($_GET['rmfok'])) {
	echo '<p class="message">'.__('File has been successfully removed.').'</p>';
}

if (!empty($_GET['rmdok'])) {
	echo '<p class="message">'.__('Directory has been successfully removed.').'</p>';
}

if (!empty($_GET['rebuildok'])) {
	echo '<p class="message">'.__('Directory has been successfully rebuilt.').'</p>';
}

if (!empty($_GET['unzipok'])) {
	echo '<p class="message">'.__('Zip file has been successfully extracted.').'</p>';
}

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; ';
if (!isset($core->media)) {
	echo '<span class="page-title">'.__('Media manager').'</span></h2>';
} else {
	$breadcrumb = $core->media->breadCrumb(html::escapeURL($page_url).'&amp;d=%s','<span class="page-title">%s</span>');
	if ($breadcrumb == '') {
		echo '<span class="page-title">'.__('Media manager').'</span></h2>';
	} else {
		echo '<a href="'.html::escapeURL($page_url.'&d=').'">'.__('Media manager').'</a>'.' / '.$breadcrumb.'</h2>';
	}
}

if (!$dir) {
	call_user_func($close_f);
	exit;
}

if ($post_id) {
	echo '<p><strong>'.sprintf(__('Choose a file to attach to entry %s by clicking on %s.'),
	'<a href="'.$core->getPostAdminURL($post_type,$post_id).'">'.html::escapeHTML($post_title).'</a>',
	'<img src="images/plus.png" alt="'.__('Attach this file to entry').'" />').'</strong></p>';
}
if ($popup) {
	echo '<p><strong>'.sprintf(__('Choose a file to insert into entry by clicking on %s.'),
	'<img src="images/plus.png" alt="'.__('Attach this file to entry').'" />').'</strong></p>';
}


$items = array_values(array_merge($dir['dirs'],$dir['files']));
if (count($items) == 0)
{
	echo '<p><strong>'.__('No file.').'</strong></p>';
}
else
{
	$pager = new pager($page,count($items),$nb_per_page,10);
	$pager->html_prev = __($pager->html_prev);
	$pager->html_next = __($pager->html_next);
	
	echo
	'<form action="media.php" method="get">'.
	'<p><label for="file_sort" class="classic">'.__('Sort files:').' '.
	form::combo('file_sort',$sort_combo,$file_sort).'</label>'.
	form::hidden(array('popup'),$popup).
	form::hidden(array('post_id'),$post_id).
	'<input type="submit" value="'.__('Sort').'" /></p>'.
	'</form>'.
	
	'<div class="media-list">'.
	'<p>'.__('Page(s)').' : '.$pager->getLinks().'</p>';
	
	for ($i=$pager->index_start, $j=0; $i<=$pager->index_end; $i++, $j++)
	{
		echo mediaItemLine($items[$i],$j);
	}
	
	echo
	'<p class="clear">'.__('Page(s)').' : '.$pager->getLinks().'</p>'.
	'</div>';
}

if ($core_media_writable)
{
	echo '<div class="two-cols">';
	
	echo
	'<div class="col">'.
	'<fieldset id="add-file-f"><legend>'.__('Add files').'</legend>'.
	'<p>'.__('Please take care to publish media that you own and that are not protected by copyright.').'</p>'.
	'<form id="media-upload" class="clear" action="'.html::escapeURL($page_url).'" method="post" enctype="multipart/form-data">'.
	'<div>'.form::hidden(array('MAX_FILE_SIZE'),DC_MAX_UPLOAD_SIZE).
	$core->formNonce().'</div>'.
	'<p><label for="upfile">'.__('Choose a file:').
	' ('.sprintf(__('Maximum size %s'),files::size(DC_MAX_UPLOAD_SIZE)).')'.
	'<input type="file" id="upfile" name="upfile" size="20" />'.
	'</label></p>'.
	'<p><label for="upfiletitle">'.__('Title:').form::field(array('upfiletitle','upfiletitle'),35,255).'</label></p>'.
	'<p><label for="upfilepriv" class="classic">'.form::checkbox(array('upfilepriv','upfilepriv'),1).' '.
	__('Private').'</label></p>'.
	'<p class="form-help info">'.__('To send several files at the same time, you can activate the enhanced uploader in').
	'<a href="preferences.php?tab=user-options"> '.__('My preferences').'</a></p>'.
	'<p><input type="submit" value="'.__('Send').'" />'.
	form::hidden(array('d'),$d).'</p>'.
	'</fieldset>'.
	'</form>'.
	'</div>';
	
	echo
	'<div class="col">'.
	'<form class="clear" action="'.html::escapeURL($page_url).'" method="post">'.
	'<fieldset id="new-dir-f">'.
	'<legend>'.__('New directory').'</legend>'.
	$core->formNonce().
	'<p><label for="newdir">'.__('Directory Name:').
	form::field(array('newdir','newdir'),35,255).'</label></p>'.
	'<p><input type="submit" value="'.__('Create').'" />'.
	form::hidden(array('d'),html::escapeHTML($d)).'</p>'.
	'</fieldset>'.
	'</form></div>';
	
	echo '</div>';
}

# Empty remove form (for javascript actions)
echo
'<form id="media-remove-hide" action="'.html::escapeURL($page_url).'" method="post"><div class="clear">'.
form::hidden('rmyes',1).form::hidden('d',html::escapeHTML($d)).
form::hidden('remove','').
$core->formNonce().
'</div></form>';

# Get zip directory
if ($core->auth->check('media_admin',$core->blog->id) && 
	!(count($items) == 0 || (count($items) == 1 && $items[0]->parent)))
{
	echo
	'<p class="zip-dl"><a href="'.html::escapeURL($page_url).'&amp;zipdl=1">'.
	__('Download this directory as a zip file').'</a></p>';
}

call_user_func($close_f);

/* ----------------------------------------------------- */
function mediaItemLine($f,$i)
{
	global $core, $page_url, $popup, $post_id;
	
	$fname = $f->basename;
	
	if ($f->d) {
		$link = html::escapeURL($page_url).'&amp;d='.html::sanitizeURL($f->relname);
		if ($f->parent) {
			$fname = '..';
		}
	} else {
		$link =
		'media_item.php?id='.$f->media_id.'&amp;popup='.$popup.'&amp;post_id='.$post_id;
	}
	
	$class = 'media-item media-col-'.($i%2);
	
	$res =
	'<div class="'.$class.'"><a class="media-icon media-link" href="'.$link.'">'.
	'<img src="'.$f->media_icon.'" alt="" /></a>'.
	'<ul>'.
	'<li><a class="media-link" href="'.$link.'">'.$fname.'</a></li>';
	
	if (!$f->d) {
		$res .=
		'<li>'.$f->media_title.'</li>'.
		'<li>'.
		$f->media_dtstr.' - '.
		files::size($f->size).' - '.
		'<a href="'.$f->file_url.'">'.__('open').'</a>'.
		'</li>';
	}
	
	$res .= '<li class="media-action">&nbsp;';
	
	if ($post_id && !$f->d) {
		$res .= '<form action="post_media.php" method="post">'.
		'<input type="image" src="images/plus.png" alt="'.__('Attach this file to entry').'" '.
		'title="'.__('Attach this file to entry').'" /> '.
		form::hidden('media_id',$f->media_id).
		form::hidden('post_id',$post_id).
		form::hidden('attach',1).
		$core->formNonce().
		'</form>';
	}
	
	if ($popup && !$f->d) {
		$res .= '<a href="'.$link.'"><img src="images/plus.png" alt="'.__('Insert this file into entry').'" '.
		'title="'.__('Insert this file into entry').'" /></a> ';
	}
	
	if ($f->del) {
		$res .= '<a class="media-remove" '.
		'href="'.html::escapeURL($page_url).'&amp;d='.
		rawurlencode($GLOBALS['d']).'&amp;remove='.rawurlencode($f->basename).'">'.
		'<img src="images/trash.png" alt="'.__('Delete').'" title="'.__('delete').'" /></a>';
	}
	
	$res .= '</li>';
	
	if ($f->type == 'audio/mpeg3') {
		$res .= '<li>'.dcMedia::mp3player($f->file_url,'index.php?pf=player_mp3.swf').'</li>';
	}
	
	$res .= '</ul></div>';
	
	return $res;
}
?>