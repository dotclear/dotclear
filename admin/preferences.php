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

require dirname(__FILE__).'/../inc/admin/prepend.php';

dcPage::check('usage,contentadmin');

$page_title = __('My preferences');

$user_name = $core->auth->getInfo('user_name');
$user_firstname = $core->auth->getInfo('user_firstname');
$user_displayname = $core->auth->getInfo('user_displayname');
$user_email = $core->auth->getInfo('user_email');
$user_url = $core->auth->getInfo('user_url');
$user_lang = $core->auth->getInfo('user_lang');
$user_tz = $core->auth->getInfo('user_tz');
$user_post_status = $core->auth->getInfo('user_post_status');

$user_options = $core->auth->getOptions();

$core->auth->user_prefs->addWorkspace('dashboard');
$user_dm_doclinks = $core->auth->user_prefs->dashboard->doclinks;
$user_dm_dcnews = $core->auth->user_prefs->dashboard->dcnews;
$user_dm_quickentry = $core->auth->user_prefs->dashboard->quickentry;

$default_tab = 'user-profile';

if (!empty($_GET['append']) || !empty($_GET['removed']) || !empty($_GET['neworder']) || !empty($_GET['replaced'])) {
	$default_tab = 'user-favorites';
} elseif (!empty($_GET['updated'])) {
	$default_tab = 'user-options';
}

foreach ($core->getFormaters() as $v) {
	$formaters_combo[$v] = $v;
}

foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = $k;
}

# Language codes
$langs = l10n::getISOcodes(1,1);
foreach ($langs as $k => $v) {
	$lang_avail = $v == 'en' || is_dir(DC_L10N_ROOT.'/'.$v);
	$lang_combo[] = new formSelectOption($k,$v,$lang_avail ? 'avail10n' : '');
}

# Add or update user
if (isset($_POST['user_name']))
{
	try
	{
		$pwd_check = !empty($_POST['cur_pwd']) && $core->auth->checkPassword(crypt::hmac(DC_MASTER_KEY,$_POST['cur_pwd']));
		
		if ($core->auth->allowPassChange() && !$pwd_check && $user_email != $_POST['user_email']) {
			throw new Exception(__('If you want to change your email or password you must provide your current password.'));
		}
		
		$cur = $core->con->openCursor($core->prefix.'user');
		
		$cur->user_name = $user_name = $_POST['user_name'];
		$cur->user_firstname = $user_firstname = $_POST['user_firstname'];
		$cur->user_displayname = $user_displayname = $_POST['user_displayname'];
		$cur->user_email = $user_email = $_POST['user_email'];
		$cur->user_url = $user_url = $_POST['user_url'];
		$cur->user_lang = $user_lang = $_POST['user_lang'];
		$cur->user_tz = $user_tz = $_POST['user_tz'];

		$cur->user_options = new ArrayObject($user_options);
		
		if ($core->auth->allowPassChange() && !empty($_POST['new_pwd']))
		{
			if (!$pwd_check) {
				throw new Exception(__('If you want to change your email or password you must provide your current password.'));
			}
			
			if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
				throw new Exception(__("Passwords don't match"));
			}
			
			$cur->user_pwd = $_POST['new_pwd'];
		}
		
		# --BEHAVIOR-- adminBeforeUserUpdate
		$core->callBehavior('adminBeforeUserProfileUpdate',$cur,$core->auth->userID());
		
		# Udate user
		$core->updUser($core->auth->userID(),$cur);
		
		# --BEHAVIOR-- adminAfterUserUpdate
		$core->callBehavior('adminAfterUserProfileUpdate',$cur,$core->auth->userID());
		
		http::redirect('preferences.php?upd=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Update user options
if (isset($_POST['user_post_format'])) {
	try
	{
		$cur = $core->con->openCursor($core->prefix.'user');
		
		$cur->user_name = $user_name;
		$cur->user_firstname = $user_firstname;
		$cur->user_displayname = $user_displayname;
		$cur->user_email = $user_email;
		$cur->user_url = $user_url;
		$cur->user_lang = $user_lang;
		$cur->user_tz = $user_tz;

		$cur->user_post_status = $user_post_status = $_POST['user_post_status'];
		
		$user_options['edit_size'] = (integer) $_POST['user_edit_size'];
		if ($user_options['edit_size'] < 1) {
			$user_options['edit_size'] = 10;
		}
		$user_options['post_format'] = $_POST['user_post_format'];
		$user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
		
		$cur->user_options = new ArrayObject($user_options);
		
		# --BEHAVIOR-- adminBeforeUserUpdate
		$core->callBehavior('adminBeforeUserUpdate',$cur,$core->auth->userID());
		
		# Update user prefs
		$core->auth->user_prefs->dashboard->put('doclinks',!empty($_POST['user_dm_doclinks']),'boolean');
		$core->auth->user_prefs->dashboard->put('dcnews',!empty($_POST['user_dm_dcnews']),'boolean');
		$core->auth->user_prefs->dashboard->put('quickentry',!empty($_POST['user_dm_quickentry']),'boolean');
		
		# Udate user
		$core->updUser($core->auth->userID(),$cur);
		
		# --BEHAVIOR-- adminAfterUserUpdate
		$core->callBehavior('adminAfterUserUpdate',$cur,$core->auth->userID());
		
		http::redirect('preferences.php?updated=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}

# Add selected favorites
if (!empty($_POST['appendaction']) && !empty($_POST['append'])) {
	$ws = $core->auth->user_prefs->addWorkspace('favorites');
	$user_favs = $ws->DumpLocalPrefs();
	$count = count($user_favs);
	foreach ($_POST['append'] as $k => $v)
	{
		try {
			$found = false;
			foreach ($user_favs as $f) {
				$f = unserialize($f['value']);
				if ($f['name'] == $v) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$uid = sprintf("u%03s",$count);
				$fav = array('name' => $_fav[$v][0],'title' => $_fav[$v][1],'url' => $_fav[$v][2],'small-icon' => $_fav[$v][3],
					'large-icon' => $_fav[$v][4],'permissions' => $_fav[$v][5],'id' => $_fav[$v][6],'class' => $_fav[$v][7]);
				$core->auth->user_prefs->favorites->put($uid,serialize($fav),'string');
				$count++;
			}
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			break;
		}
	}
	
	if (!$core->error->flag()) {
		http::redirect('preferences.php?append=1');
	}
}

# Delete selected favorites
if (!empty($_POST['removeaction']) && !empty($_POST['remove'])) {
	$ws = $core->auth->user_prefs->addWorkspace('favorites');
	foreach ($_POST['remove'] as $k => $v)
	{
		try {
			$core->auth->user_prefs->favorites->drop($v);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
			break;
		}
	}
	// Update pref_id values
	try {
		$user_favs = $ws->DumpLocalPrefs();
		$core->auth->user_prefs->favorites->dropAll();
		$count = 0;
		foreach ($user_favs as $k => $v)
		{
			$uid = sprintf("u%03s",$count);
			$f = unserialize($v['value']);
			$fav = array('name' => $f['name'],'title' => $f['title'],'url' => $f['url'],'small-icon' => $f['small-icon'],
				'large-icon' => $f['large-icon'],'permissions' => $f['permissions'],'id' => $f['id'],'class' => $f['class']);
			$core->auth->user_prefs->favorites->put($uid,serialize($fav),'string');
			$count++;
		}
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
	
	if (!$core->error->flag()) {
		http::redirect('preferences.php?removed=1');
	}
}

# Order favs
$order = array();
if (empty($_POST['favs_order']) && !empty($_POST['order'])) {
	$order = $_POST['order'];
	asort($order);
	$order = array_keys($order);
} elseif (!empty($_POST['favs_order'])) {
	$order = explode(',',$_POST['favs_order']);
}

if (!empty($_POST['saveorder']) && !empty($order))
{
	try {
		$ws = $core->auth->user_prefs->addWorkspace('favorites');
		$user_favs = $ws->DumpLocalPrefs();
		$core->auth->user_prefs->favorites->dropAll();
		$count = 0;
		foreach ($order as $i => $k) {
			$uid = sprintf("u%03s",$count);
			$f = unserialize($user_favs[$k]['value']);
			$fav = array('name' => $f['name'],'title' => $f['title'],'url' => $f['url'],'small-icon' => $f['small-icon'],
				'large-icon' => $f['large-icon'],'permissions' => $f['permissions'],'id' => $f['id'],'class' => $f['class']);
			$core->auth->user_prefs->favorites->put($uid,serialize($fav),'string');
			$count++;
		}
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}

	if (!$core->error->flag()) {
		http::redirect('preferences.php?&neworder=1');
	}
}

# Replace default favorites by current set (super admin only)
if (!empty($_POST['replace']) && $core->auth->isSuperAdmin()) {
	try {
		$ws = $core->auth->user_prefs->addWorkspace('favorites');
		$user_favs = $ws->DumpLocalPrefs();
		$core->auth->user_prefs->favorites->dropAll(true);
		$count = 0;
		foreach ($user_favs as $k => $v)
		{
			$uid = sprintf("g%03s",$count);
			$f = unserialize($v['value']);
			$fav = array('name' => $f['name'],'title' => $f['title'],'url' => $f['url'],'small-icon' => $f['small-icon'],
				'large-icon' => $f['large-icon'],'permissions' => $f['permissions'],'id' => $f['id'],'class' => $f['class']);
			$core->auth->user_prefs->favorites->put($uid,serialize($fav),'string',null,null,true);
			$count++;
		}
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}

	if (!$core->error->flag()) {
		http::redirect('preferences.php?&replaced=1');
	}
}

/* DISPLAY
-------------------------------------------------------- */
dcPage::open($page_title,
	dcPage::jsLoad('js/_preferences.js').
	dcPage::jsLoad('js/jquery/jquery-ui-1.8.12.custom.min.js').
	dcPage::jsPageTabs($default_tab).
	dcPage::jsConfirmClose('user-form').
	
	# --BEHAVIOR-- adminPreferencesHeaders
	$core->callBehavior('adminPreferencesHeaders')
);

if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Personal information has been successfully updated.').'</p>';
}
if (!empty($_GET['updated'])) {
		echo '<p class="message">'.__('Personal options has been successfully updated.').'</p>';
}
if (!empty($_GET['append'])) {
		echo '<p class="message">'.__('Favorites have been successfully added.').'</p>';
}
if (!empty($_GET['neworder'])) {
	echo '<p class="message">'.__('Favorites have been successfully updated.').'</p>';
}
if (!empty($_GET['removed'])) {
		echo '<p class="message">'.__('Favorites have been successfully removed.').'</p>';
}
if (!empty($_GET['replaced'])) {
		echo '<p class="message">'.__('Default favorites have been successfully updated.').'</p>';
}

echo '<h2>'.$page_title.'</h2>';

# User profile
echo '<div class="multi-part" id="user-profile" title="'.__('My profile').'">';

echo
'<form action="preferences.php" method="post" id="user-form">'.
'<fieldset><legend>'.__('My profile').'</legend>'.
'<div class="two-cols">'.
'<div class="col">'.
'<p><label for="user_name">'.__('Last Name:').
form::field('user_name',20,255,html::escapeHTML($user_name),'',2).'</label></p>'.

'<p><label for="user_firstname">'.__('First Name:').
form::field('user_firstname',20,255,html::escapeHTML($user_firstname),'',3).'</label></p>'.

'<p><label for="user_displayname">'.__('Display name:').
form::field('user_displayname',20,255,html::escapeHTML($user_displayname),'',4).'</label></p>'.

'<p><label for="user_email">'.__('Email:').
form::field('user_email',20,255,html::escapeHTML($user_email),'',5).'</label></p>'.

'<p><label for="user_url">'.__('URL:').
form::field('user_url',30,255,html::escapeHTML($user_url),'',6).'</label></p>'.

'</div>'.

'<div class="col">'.

'<p><label for="user_lang">'.__('User language:').
form::combo('user_lang',$lang_combo,$user_lang,'l10n',10).'</label></p>'.

'<p><label for="user_tz">'.__('User timezone:').
form::combo('user_tz',dt::getZones(true,true),$user_tz,'',11).'</label></p>'.

'</div>'.
'</div>'.
'<br class="clear" />'. //Opera sucks
'</fieldset>';

if ($core->auth->allowPassChange())
{
	echo
	'<fieldset>'.
	'<legend>'.__('Change your password').'</legend>'.
	
	'<p><label for="new_pwd">'.__('New password:').
	form::password('new_pwd',20,255,'','',30).'</label></p>'.
	
	'<p><label for="new_pwd_c">'.__('Confirm password:').
	form::password('new_pwd_c',20,255,'','',31).'</label></p>'.
	'</fieldset>'.
	
	'<fieldset>'.
	'<p>'.__('If you want to change your email or password you must provide your current password.').'</p>'.
	'<p><label for="cur_pwd">'.__('Your password:').
	form::password('cur_pwd',20,255,'','',32).'</label></p>'.
	'</fieldset>';
}

echo
'<p class="clear">'.
$core->formNonce().
'<input type="submit" accesskey="s" value="'.__('Save').'" tabindex="33" /></p>'.
'</form>';

echo '</div>';

# User options : some from actual user profile, dashboard modules, ...
echo '<div class="multi-part" id="user-options" title="'.__('My options').'">';

echo
'<form action="preferences.php" method="post" id="opts-forms">'.
'<fieldset><legend>'.__('My options').'</legend>'.

'<p><label for="user_post_format">'.__('Preferred format:').
form::combo('user_post_format',$formaters_combo,$user_options['post_format'],'',7).'</label></p>'.

'<p><label for="user_post_status">'.__('Default entry status:').
form::combo('user_post_status',$status_combo,$user_post_status,'',8).'</label></p>'.

'<p><label for="user_edit_size">'.__('Entry edit field height:').
form::field('user_edit_size',5,4,(integer) $user_options['edit_size'],'',9).'</label></p>'.

'<p><label for="user_wysiwyg" class="classic">'.
form::checkbox('user_wysiwyg',1,$user_options['enable_wysiwyg'],'',12).' '.
__('Enable WYSIWYG mode').'</label></p>'.
'<br class="clear" />'. //Opera sucks
'</fieldset>';

echo
'<fieldset><legend>'.__('Dashboard modules').'</legend>'.

'<p><label for="user_dm_doclinks" class="classic">'.
form::checkbox('user_dm_doclinks',1,$user_dm_doclinks,'',13).' '.
__('Display documentation links').'</label></p>'.

'<p><label for="user_dm_dcnews" class="classic">'.
form::checkbox('user_dm_dcnews',1,$user_dm_dcnews,'',14).' '.
__('Display Dotclear news').'</label></p>'.

'<p><label for="user_dm_quickentry" class="classic">'.
form::checkbox('user_dm_quickentry',1,$user_dm_quickentry,'',15).' '.
__('Display quick entry form').'</label></p>'.

'<br class="clear" />'. //Opera sucks
'</fieldset>';

# --BEHAVIOR-- adminPreferencesForm
$core->callBehavior('adminPreferencesForm',$core);

echo
'<p class="clear">'.
$core->formNonce().
'<input type="submit" accesskey="s" value="'.__('Save').'" tabindex="33" /></p>'.
'</form>';

echo '</div>';

# User favorites
echo '<div class="multi-part" id="user-favorites" title="'.__('My favorites').'">';
$ws = $core->auth->user_prefs->addWorkspace('favorites');
echo '<form action="preferences.php" method="post" id="favs-form">';
echo '<div class="two-cols">';
echo '<div class="col70">';
echo '<fieldset id="my-favs"><legend>'.__('My favorites').'</legend>';

$count = 0;
foreach ($ws->dumpPrefs() as $k => $v) {
	// User favorites only
	if (!$v['global']) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			if ($count == 0) echo '<ul>';
			$count++;
			echo '<li id="fu-'.$k.'">'.
				'<img src="'.$fav['large-icon'].'" alt="" /> '.
				form::field(array('order['.$k.']'),2,3,$count,'position','',false,'title="'.sprintf(__('position of %s'),$fav['title']).'"').
				form::hidden(array('dynorder[]','dynorder-'.$k.''),$k).
				'<label for="fuk-'.$k.'">'.form::checkbox(array('remove[]','fuk-'.$k),$k).$fav['title'].'</label>'.
				'</li>';
		}
	}
}
if ($count > 0) echo '</ul>';
if ($count > 0) {
	echo
	'<div class="clear">'.
	'<p>'.form::hidden('favs_order','').
	$core->formNonce().
	'<input type="submit" name="saveorder" value="'.__('Save order').'" /> '.

	'<input type="submit" class="delete" name="removeaction" '.
	'value="'.__('Delete selected favorites').'" '.
	'onclick="return window.confirm(\''.html::escapeJS(
		__('Are you sure you want to remove selected favorites?')).'\');" /></p>'.

	($core->auth->isSuperAdmin() ? 
		'<hr />'.
		'<p>'.__('If you are a super administrator, you may define this set of favorites to be used by default on all blogs of this installation:').'</p>'.
		'<p><input class="reset" type="submit" name="replace" value="'.__('Define as default favorites').'" />' : 
		'').
		'</p>'.
	'</div>';
} else {
	echo
	'<p>'.__('Currently no personal favorites.').'</p>';
}

echo '</fieldset>';

echo '<div id="default-favs"><h3>'.__('Default favorites').'</h3>';
echo '<p class="form-note clear">'.__('Those favorites are displayed when My Favorites list is empty.').'</p>';
$count = 0;
foreach ($ws->dumpPrefs() as $k => $v) {
	// Global favorites only
	if ($v['global']) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			if ($count == 0) echo '<ul class="fav-list">';
			$count++;
			echo '<li id="fd-'.$k.'">'.
			'<img src="'.$fav['small-icon'].'" alt="" /> '.$fav['title'].'</li>';
		}
	}
}	
if ($count > 0) echo '</ul>';
echo '</div>';
echo '</div>';
echo '<div class="col30" id="available-favs">';
# Available favorites
echo '<fieldset><legend>'.__('Available favorites').'</legend>';
$count = 0;
$array = $_fav;
function cmp($a,$b) {
    if ($a[1] == $b[1]) {
        return 0;
    }
    return ($a[1] < $b[1]) ? -1 : 1;
}
$array->uasort('cmp');
foreach ($array as $k => $fav) {
	if (($fav[5] == '*') || $core->auth->check($fav[5],$core->blog->id)) {
		if ($count == 0) echo '<ul class="fav-list">';
		$count++;
		echo '<li id="fa-'.$fav[0].'">'.'<label for="fak-'.$fav[0].'">'.
			form::checkbox(array('append[]','fak-'.$fav[0]),$k).
			'<img src="'.$fav[3].'" alt="" /> '.'<span class="zoom"><img src="'.$fav[4].'" alt="" /></span>'.$fav[1].
			'</label>'.'</li>';
	}
}	
if ($count > 0) echo '</ul>';
echo
'<p>'.
$core->formNonce().
'<input type="submit" name="appendaction" value="'.__('Add to my favorites').'" /></p>';
echo '</fieldset>';
echo '</div>';
echo '</div>'; # Two-cols
echo '</form>';
echo '</div>'; # user-favorites

dcPage::helpBlock('core_user_pref');
dcPage::close();
?>
