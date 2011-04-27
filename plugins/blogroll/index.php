<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2010 Olivier Meunier & Association Dotclear
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
  <title>Blogroll</title>
  <?php echo dcPage::jsToolMan(); ?>
  <?php echo dcPage::jsConfirmClose('links-form','add-link-form','add-category-form'); ?>
  <script type="text/javascript">
  //<![CDATA[
  
  var dragsort = ToolMan.dragsort();
  $(function() {
  	dragsort.makeTableSortable($("#links-list").get(0),
  	dotclear.sortable.setHandle,dotclear.sortable.saveOrder);
	
	$('.checkboxes-helpers').each(function() {
		dotclear.checkboxesHelpers(this);
	});
  });
  
  dotclear.sortable = {
	  setHandle: function(item) {
		var handle = $(item).find('td.handle').get(0);
		while (handle.firstChild) {
			handle.removeChild(handle.firstChild);
		}
		
		item.toolManDragGroup.setHandle(handle);
		handle.className = handle.className+' handler';
	  },
	  
	  saveOrder: function(item) {
		var group = item.toolManDragGroup;
		var order = document.getElementById('links_order');
		group.register('dragend', function() {
			order.value = '';
			items = item.parentNode.getElementsByTagName('tr');
			
			for (var i=0; i<items.length; i++) {
				order.value += items[i].id.substr(2)+',';
			}
		});
	  }
  };
  //]]>
  </script>
  <?php echo dcPage::jsPageTabs($default_tab); ?>
</head>

<body>
<h2><?php echo html::escapeHTML($core->blog->name); ?> &rsaquo; Blogroll</h2>

<?php
if (!empty($_GET['neworder'])) {
	echo '<p class="message">'.__('Items order has been successfully updated').'</p>';
}

if (!empty($_GET['removed'])) {
		echo '<p class="message">'.__('Items have been successfully removed.').'</p>';
}

if (!empty($_GET['addlink'])) {
		echo '<p class="message">'.__('Link has been successfully created.').'</p>';
}

if (!empty($_GET['addcat'])) {
		echo '<p class="message">'.__('category has been successfully created.').'</p>';
}

if (!empty($_GET['importlinks'])) {
		echo '<p class="message">'.__('links have been successfully imported.').'</p>';
}
?>

<div class="multi-part" title="<?php echo __('Blogroll'); ?>">
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
	'<td class="handle minimal">'.form::field(array('order['.$rs->link_id.']'),2,5,$position).'</td>'.
	'<td class="minimal">'.form::checkbox(array('remove[]'),$rs->link_id).'</td>';
	
	
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
<?php
	if (!$rs->isEmpty()) {
		echo
		'<div class="two-cols">'.
		'<p class="col">'.form::hidden('links_order','').
		form::hidden(array('p'),'blogroll').
		$core->formNonce().
		'<input type="submit" name="saveorder" value="'.__('Save order').'"></p>'.
		
		'<p class="col right"><input type="submit" class="delete" name="removeaction"'.
		'value="'.__('Delete selected links').'" '.
		'onclick="return window.confirm(\''.html::escapeJS(
			__('Are you sure you want to delete selected links?')).'\');" /></p>'.
		'</div>';
	} else {
		echo
		'<div><p>'.__('The link list is empty.').'</p></div>';
	}
?>
</form>
</div>

<?php
echo
'<div class="multi-part clear" id="add-link" title="'.__('Add a link').'">'.
'<form action="plugin.php" method="post" id="add-link-form">'.
'<fieldset class="two-cols"><legend>'.__('Add a new link').'</legend>'.
'<p class="col"><label class="required" title="'.__('Required field').'">'.__('Title:').' '.
form::field('link_title',30,255,$link_title,'',2).
'</label></p>'.

'<p class="col"><label class="required" title="'.__('Required field').'">'.__('URL:').' '.
form::field('link_href',30,255,$link_href,'',3).
'</label></p>'.

'<p class="col"><label>'.__('Description:').' '.
form::field('link_desc',30,255,$link_desc,'',4).
'</label></p>'.

'<p class="col"><label>'.__('Language:').' '.
form::field('link_lang',5,5,$link_lang,'',5).
'</label></p>'.
'<p>'.form::hidden(array('p'),'blogroll').
$core->formNonce().
'<input type="submit" name="add_link" value="'.__('save').'" tabindex="6" /></p>'.
'</fieldset>'.
'</form>'.
'</div>';

echo
'<div class="multi-part" id="add-cat" title="'.__('Add a category').'">'.
'<form action="plugin.php" method="post" id="add-category-form">'.
'<fieldset><legend>'.__('Add a new category').'</legend>'.
'<p><label class=" classic required" title="'.__('Required field').'">'.__('Title:').' '.
form::field('cat_title',30,255,$cat_title,'',7).'</label> '.
form::hidden(array('p'),'blogroll').
$core->formNonce().
'<input type="submit" name="add_cat" value="'.__('save').'" tabindex="8" /></p>'.
'</fieldset>'.
'</form>'.
'</div>';

echo
'<div class="multi-part" id="import-links" title="'.__('Import links').'">';
if (!isset($imported)) {
	echo
	'<form action="plugin.php" method="post" id="import-links-form" enctype="multipart/form-data">'.
	'<fieldset><legend>'.__('Import links').'</legend>'.
	'<p><label class=" classic required" title="'.__('Required field').'">'.__('OPML or XBEL File:').' '.
	'<input type="file" name="links_file" /></label></p>'.
	'<p>'.form::hidden(array('p'),'blogroll').
	$core->formNonce().
	'<input type="submit" name="import_links" value="'.__('import').'" tabindex="10" /></p>'.
	'</fieldset>'.
	'</form>';
}
else {
	echo
	'<form action="plugin.php" method="post" id="import-links-form">'.
	'<fieldset><legend>'.__('Import links').'</legend>';
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
		'<input type="submit" name="cancel_import" value="'.__('cancel').'" tabindex="10" />&nbsp;'.
		'<input type="submit" name="import_links_do" value="'.__('import').'" tabindex="11" /></p>'.
		'</div>';
	}
	echo
	'</fieldset>'.
	'</form>';
}
echo '</div>';
?>

</body>
</html>