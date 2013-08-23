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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$blogroll = new dcBlogroll($core->blog);

if (!empty($_REQUEST['edit']) && !empty($_REQUEST['id'])) {
	include dirname(__FILE__).'/edit.php';
	return;
}

$default_tab = '';
$link_title = $link_href = $link_desc = $link_lang = '';
$cat_title = '';

# Import links
if (!empty($_POST['import_links']) && !empty($_FILES['links_file']))
{
	$default_tab = 'import-links';
	
	try
	{
		files::uploadStatus($_FILES['links_file']);
		$ifile = DC_TPL_CACHE.'/'.md5(uniqid());
		if (!move_uploaded_file($_FILES['links_file']['tmp_name'],$ifile)) {
			throw new Exception(__('Unable to move uploaded file.'));
		}
		
		require_once dirname(__FILE__).'/class.dc.importblogroll.php';
		try {
			$imported = dcImportBlogroll::loadFile($ifile);
			@unlink($ifile);
		} catch (Exception $e) {
			@unlink($ifile);
			throw $e;
		}
		
		
		if (empty($imported)) {
			unset($imported);
			throw new Exception(__('Nothing to import'));
		}
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

if (!empty($_POST['import_links_do'])) {
	foreach ($_POST['entries'] as $idx) {
		$link_title = $_POST['title'][$idx];
		$link_href  = $_POST['url'][$idx];
		$link_desc  = $_POST['desc'][$idx];
		try {
			$blogroll->addLink($link_title,$link_href,$link_desc,'');
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			$default_tab = 'import-links';
		}
	}
	http::redirect($p_url.'&importlinks=1');	
}

if (!empty($_POST['cancel_import'])) {
	$core->error->add(__('Import operation cancelled.'));
	$default_tab = 'import-links';	
}

# Add link
if (!empty($_POST['add_link']))
{
	$link_title = $_POST['link_title'];
	$link_href = $_POST['link_href'];
	$link_desc = $_POST['link_desc'];
	$link_lang = $_POST['link_lang'];
	
	try {
		$blogroll->addLink($link_title,$link_href,$link_desc,$link_lang);
		http::redirect($p_url.'&addlink=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
		$default_tab = 'add-link';
	}
}

# Add category
if (!empty($_POST['add_cat']))
{
	$cat_title = $_POST['cat_title'];
	
	try {
		$blogroll->addCategory($cat_title);
		http::redirect($p_url.'&addcat=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
		$default_tab = 'add-cat';
	}
}

# Delete link
if (!empty($_POST['removeaction']) && !empty($_POST['remove'])) {
	foreach ($_POST['remove'] as $k => $v)
	{
		try {
			$blogroll->delItem($v);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			break;
		}
	}
	
	if (!$core->error->flag()) {
		http::redirect($p_url.'&removed=1');
	}
}

# Order links
$order = array();
if (empty($_POST['links_order']) && !empty($_POST['order'])) {
	$order = $_POST['order'];
	asort($order);
	$order = array_keys($order);
} elseif (!empty($_POST['links_order'])) {
	$order = explode(',',$_POST['links_order']);
}

if (!empty($_POST['saveorder']) && !empty($order))
{
	foreach ($order as $pos => $l) {
		$pos = ((integer) $pos)+1;
		
		try {
			$blogroll->updateOrder($l,$pos);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}
	
	if (!$core->error->flag()) {
		http::redirect($p_url.'&neworder=1');
	}
}


# Get links
try {
	$rs = $blogroll->getLinks();
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

?>
<html>
<head>
  <title><?php echo __('Blogroll'); ?></title>
  <?php echo dcPage::jsConfirmClose('links-form','add-link-form','add-category-form'); ?>
  <?php 
	$core->auth->user_prefs->addWorkspace('accessibility');
	if (!$core->auth->user_prefs->accessibility->nodragdrop) {
	echo
		dcPage::jsLoad('js/jquery/jquery-ui.custom.js').
		dcPage::jsLoad('index.php?pf=blogroll/blogroll.js');
	}
  ?>
  <?php echo dcPage::jsPageTabs($default_tab); ?>
</head>

<body>
<?php
	echo dcPage::breadcrumb(
		array(
			html::escapeHTML($core->blog->name) => '',
			'<span class="page-title">'.__('Blogroll').'</span>' => ''
		));
?>

<?php
if (!empty($_GET['neworder'])) {
	dcPage::message(__('Items order has been successfully updated'));
}

if (!empty($_GET['removed'])) {
	dcPage::message(__('Items have been successfully removed.'));
}

if (!empty($_GET['addlink'])) {
	dcPage::message(__('Link has been successfully created.'));
}

if (!empty($_GET['addcat'])) {
	dcPage::message(__('category has been successfully created.'));
}

if (!empty($_GET['importlinks'])) {
	dcPage::message(__('links have been successfully imported.'));
}
?>

<div class="multi-part" title="<?php echo __('Blogroll'); ?>">

<?php if (!$rs->isEmpty()) { ?>

<form action="plugin.php" method="post" id="links-form">
<table class="maximal dragable">
<thead>
<tr>
  <th colspan="3"><?php echo __('Title'); ?></th>
  <th><?php echo __('Description'); ?></th>
  <th><?php echo __('URL'); ?></th>
  <th><?php echo __('Lang'); ?></th>
</tr>
</thead>
<tbody id="links-list">
<?php
while ($rs->fetch())
{
	$position = (string) $rs->index()+1;
	
	echo
	'<tr class="line" id="l_'.$rs->link_id.'">'.
	'<td class="handle minimal">'.form::field(array('order['.$rs->link_id.']'),2,5,$position,'position','',false,'title="'.__('position').'"').'</td>'.
	'<td class="minimal">'.form::checkbox(array('remove[]'),$rs->link_id,'','','',false,'title="'.__('select this link').'"').'</td>';
	
	
	if ($rs->is_cat)
	{
		echo
		'<td colspan="5"><strong><a href="'.$p_url.'&amp;edit=1&amp;id='.$rs->link_id.'">'.
		html::escapeHTML($rs->link_desc).'</a></strong></td>';
	}
	else
	{
		echo
		'<td><a href="'.$p_url.'&amp;edit=1&amp;id='.$rs->link_id.'">'.
		html::escapeHTML($rs->link_title).'</a></td>'.
		'<td>'.html::escapeHTML($rs->link_desc).'</td>'.
		'<td>'.html::escapeHTML($rs->link_href).'</td>'.
		'<td>'.html::escapeHTML($rs->link_lang).'</td>';
	}
	
	echo '</tr>';
}
?>
</tbody>
</table>

<div class="two-cols">
<p class="col">
<?php
	echo 
	form::hidden('links_order','').
	form::hidden(array('p'),'blogroll').
	$core->formNonce();
?>
<input type="submit" name="saveorder" value="<?php echo __('Save order'); ?>" /></p>
<p class="col right"><input type="submit" class="delete" name="removeaction"
	 value="<?php echo __('Delete selected links'); ?>" 
	 onclick="return window.confirm('
	 <?php echo html::escapeJS(__('Are you sure you want to delete selected links?')); ?>');" /></p>
</div>
</form>

<?php
} else {
	echo '<div><p>'.__('The link list is empty.').'</p></div>';
}
?>

</div>

<?php
echo
'<div class="multi-part clear" id="add-link" title="'.__('Add a link').'">'.
'<form action="plugin.php" method="post" id="add-link-form">'.
'<h3>'.__('Add a new link').'</h3>'.
'<p class="col"><label for="link_title" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').'</label> '.
form::field('link_title',30,255,$link_title).
'</p>'.

'<p class="col"><label for="link_href" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('URL:').'</label> '.
form::field('link_href',30,255,$link_href).
'</p>'.

'<p class="col"><label for="link_desc">'.__('Description:').'</label> '.
form::field('link_desc',30,255,$link_desc).
'</p>'.

'<p class="col"><label for="link_lang">'.__('Language:').'</label> '.
form::field('link_lang',5,5,$link_lang).
'</p>'.
'<p>'.form::hidden(array('p'),'blogroll').
$core->formNonce().
'<input type="submit" name="add_link" value="'.__('Save').'" /></p>'.
'</form>'.
'</div>';

echo
'<div class="multi-part" id="add-cat" title="'.__('Add a category').'">'.
'<form action="plugin.php" method="post" id="add-category-form">'.
'<h3>'.__('Add a new category').'</h3>'.
'<p><label for="cat_title" class=" classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').'</label> '.
form::field('cat_title',30,255,$cat_title).' '.
form::hidden(array('p'),'blogroll').
$core->formNonce().
'<input type="submit" name="add_cat" value="'.__('Save').'" /></p>'.
'</form>'.
'</div>';

echo
'<div class="multi-part" id="import-links" title="'.__('Import links').'">';
if (!isset($imported)) {
	echo
	'<form action="plugin.php" method="post" id="import-links-form" enctype="multipart/form-data">'.
	'<h3>'.__('Import links').'</h3>'.
	'<p><label for="links_file" class=" classic required"><abbr title="'.__('Required field').'">*</abbr> '.__('OPML or XBEL File:').'</label> '.
	'<input type="file" id="links_file" name="links_file" /></p>'.
	'<p>'.form::hidden(array('p'),'blogroll').
	$core->formNonce().
	'<input type="submit" name="import_links" value="'.__('Import').'" /></p>'.
	'</form>';
}
else {
	echo
	'<form action="plugin.php" method="post" id="import-links-form">'.
	'<h3>'.__('Import links').'</h3>';
	if (empty($imported)) {
		echo '<p>'.__('Nothing to import').'</p>';
	}
	else {
		echo
		'<table class="clear maximal"><tr>'.
		'<th colspan="2">'.__('Title').'</th>'.
		'<th>'.__('Description').'</th>'.
		'</tr>';
		
		$i = 0;
		foreach ($imported as $entry) {
			$url   = html::escapeHTML($entry->link);
			$title = html::escapeHTML($entry->title);
			$desc  = html::escapeHTML($entry->desc);
			
			echo 
			'<tr><td>'.form::checkbox(array('entries[]'),$i,'','','').'</td>'.
			'<td nowrap><a href="'.$url.'">'.$title.'</a>'.
			'<input type="hidden" name="url['.$i.']" value="'.$url.'" />'.
			'<input type="hidden" name="title['.$i.']" value="'.$title.'" />'.
			'</td>'.
			'<td>'.$desc.
			'<input type="hidden" name="desc['.$i.']" value="'.$desc.'" />'.
			'</td></tr>'."\n";			
			$i++;
		}
		echo
		'</table>'.
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		
		'<p class="col right">'.
		form::hidden(array('p'),'blogroll').
		$core->formNonce().
		'<input type="submit" name="cancel_import" value="'.__('Cancel').'" />&nbsp;'.
		'<input type="submit" name="import_links_do" value="'.__('Import').'" /></p>'.
		'</div>';
	}
	echo
	'</form>';
}
echo '</div>';

dcPage::helpBlock('blogroll');
?>

</body>
</html>