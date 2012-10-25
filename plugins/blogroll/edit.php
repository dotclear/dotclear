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

$id = $_REQUEST['id'];

try {
	$rs = $blogroll->getLink($id);
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}

if (!$core->error->flag() && $rs->isEmpty()) {
	$core->error->add(__('No such link or title'));
} else {
	$link_title = $rs->link_title;
	$link_href = $rs->link_href;
	$link_desc = $rs->link_desc;
	$link_lang = $rs->link_lang;
	$link_xfn = $rs->link_xfn;
}

# Update a link
if (isset($rs) && !$rs->is_cat && !empty($_POST['edit_link']))
{
	$link_title = $_POST['link_title'];
	$link_href = $_POST['link_href'];
	$link_desc = $_POST['link_desc'];
	$link_lang = $_POST['link_lang'];
	
	$link_xfn = '';
		
	if (!empty($_POST['identity']))
	{
		$link_xfn .= $_POST['identity'];
	}
	else
	{
		if(!empty($_POST['friendship']))	{
			$link_xfn .= ' '.$_POST['friendship'];
		}
		if(!empty($_POST['physical'])) {
			$link_xfn .= ' met';
		}
		if(!empty($_POST['professional'])) {
			$link_xfn .= ' '.implode(' ',$_POST['professional']);
		}
		if(!empty($_POST['geographical'])) {
			$link_xfn .= ' '.$_POST['geographical'];
		}
		if(!empty($_POST['family'])) {
			$link_xfn .= ' '.$_POST['family'];
		}
		if(!empty($_POST['romantic'])) {
			$link_xfn .= ' '.implode(' ',$_POST['romantic']);
		}
	}
	
	try {
		$blogroll->updateLink($id,$link_title,$link_href,$link_desc,$link_lang,trim($link_xfn));
		http::redirect($p_url.'&edit=1&id='.$id.'&upd=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}


# Update a category
if (isset($rs) && $rs->is_cat && !empty($_POST['edit_cat']))
{
	$link_desc = $_POST['link_desc'];
	
	try {
		$blogroll->updateCategory($id,$link_desc);
		http::redirect($p_url.'&edit=1&id='.$id.'&upd=1');
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

?>
<html>
<head>
  <title>Blogroll</title>
</head>

<body>
<?php echo '<p><a href="'.$p_url.'">'.__('Return to blogroll').'</a></p>'; ?>

<?php
if (isset($rs) && $rs->is_cat)
{
	if (!empty($_GET['upd'])) {
		dcPage::message(__('Category has been successfully updated'));
	}
	
	echo
	'<form action="'.$p_url.'" method="post">'.
	'<fieldset><legend>'.__('Edit category').'</legend>'.
	
	'<p><label for="link_desc" class="required classic"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').' '.
	form::field('link_desc',30,255,html::escapeHTML($link_desc)).'</label> '.
	
	form::hidden('edit',1).
	form::hidden('id',$id).
	$core->formNonce().
	'<input type="submit" name="edit_cat" value="'.__('Save').'"/></p>'.
	'</fieldset>'.
	'</form>';
}
if (isset($rs) && !$rs->is_cat)
{
	if (!empty($_GET['upd'])) {
		dcPage::message(__('Link has been successfully updated'));
	}
	
	echo
	'<form action="plugin.php" method="post">'.
	'<fieldset class="two-cols"><legend>'.__('Edit link').'</legend>'.
	
	'<p class="col"><label for="link_title" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Title:').' '.
	form::field('link_title',30,255,html::escapeHTML($link_title)).'</label></p>'.
	
	'<p class="col"><label for="link_href" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('URL:').' '.
	form::field('link_href',30,255,html::escapeHTML($link_href)).'</label></p>'.
	
	'<p class="col"><label for="link_desc">'.__('Description:').' '.
	form::field('link_desc',30,255,html::escapeHTML($link_desc)).'</label></p>'.
	
	'<p class="col"><label for="link_lang">'.__('Language:').' '.
	form::field('link_lang',5,5,html::escapeHTML($link_lang)).'</label></p>'.
	
	'<p>'.form::hidden('p','blogroll').
	form::hidden('edit',1).
	form::hidden('id',$id).
	$core->formNonce().
	'<input type="submit" name="edit_link" value="'.__('Save').'"/></p>'.
	'</fieldset>'.
	
	
	# XFN nightmare
	'<fieldset><legend>'.__('XFN').'</legend>'.
	'<table class="noborder">'.
	
	'<tr>'.
	'<th>'.__('_xfn_Me').'</th>'.
	'<td><p>'.'<label class="classic">'.
	form::checkbox(array('identity'), 'me', ($link_xfn == 'me')).' '.
	__('_xfn_Another link for myself').'</label></p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Friendship').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::radio(array('friendship'),'contact',
	strpos($link_xfn,'contact') !== false).__('_xfn_Contact').'</label> '.
	'<label class="classic">'.form::radio(array('friendship'),'acquaintance',
	strpos($link_xfn,'acquaintance') !== false).__('_xfn_Acquaintance').'</label> '.
	'<label class="classic">'.form::radio(array('friendship'),'friend',
	strpos($link_xfn,'friend') !== false).__('_xfn_Friend').'</label> '.
	'<label class="classic">'.form::radio(array('friendship'),'').__('None').'</label>'.
	'</p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Physical').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::checkbox(array('physical'),'met',
	strpos($link_xfn,'met') !== false).__('_xfn_Met').'</label>'.
	'</p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Professional').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::checkbox(array('professional[]'),'co-worker',
	strpos($link_xfn,'co-worker') !== false).__('_xfn_Co-worker').'</label> '.
	'<label class="classic">'.form::checkbox(array('professional[]'),'colleague',
	strpos($link_xfn,'colleague') !== false).__('_xfn_Colleague').'</label>'.
	'</p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Geographical').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::radio(array('geographical'),'co-resident',
	strpos($link_xfn,'co-resident') !== false).__('_xfn_Co-resident').'</label> '.
	'<label class="classic">'.form::radio(array('geographical'),'neighbor',
	strpos($link_xfn,'neighbor') !== false).__('_xfn_Neighbor').'</label> '.
	'<label class="classic">'.form::radio(array('geographical'),'').__('None').'</label>'.
	'</p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Family').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::radio(array('family'),'child',
	strpos($link_xfn,'child') !== false).__('_xfn_Child').'</label> '.
	'<label class="classic">'.form::radio(array('family'),'parent',
	strpos($link_xfn,'parent') !== false).__('_xfn_Parent').'</label> '.
	'<label class="classic">'.form::radio(array('family'),'sibling',
	strpos($link_xfn, 'sibling') !== false).__('_xfn_Sibling').'</label> '.
	'<label class="classic">'.form::radio(array('family'),'spouse',
	strpos($link_xfn, 'spouse') !== false).__('_xfn_Spouse').'</label> '.
	'<label class="classic">'.form::radio(array('family'),'kin',
	strpos($link_xfn, 'kin') !== false).__('_xfn_Kin').'</label> '.
	'<label class="classic">'.form::radio(array('family'),'').__('None').'</label>'.
	'</p></td>'.
	'</tr>'.
	
	'<tr>'.
	'<th>'.__('_xfn_Romantic').'</th>'.
	'<td><p>'.
	'<label class="classic">'.form::checkbox(array('romantic[]'),'muse',
	strpos($link_xfn,'muse') !== false).__('_xfn_Muse').'</label> '.
	'<label class="classic">'.form::checkbox(array('romantic[]'),'crush',
	strpos($link_xfn,'crush') !== false).__('_xfn_Crush').'</label> '.
	'<label class="classic">'.form::checkbox(array('romantic[]'),'date',
	strpos($link_xfn,'date') !== false).__('_xfn_Date').'</label> '.
	'<label class="classic">'.form::checkbox(array('romantic[]'),'sweetheart',
	strpos($link_xfn,'sweetheart') !== false).__('_xfn_Sweetheart').'</label> '.
	'</p></td>'.
	'</tr>'.
	'</table>'.
	
	'</fieldset>'.
	
	'</form>';
}
?>
</body>
</html>