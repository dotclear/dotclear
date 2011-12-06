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

$core->auth->user_prefs->addWorkspace('accessibility');
$user_acc_nodragdrop = $core->auth->user_prefs->accessibility->nodragdrop;

$core->auth->user_prefs->addWorkspace('interface');
$user_ui_enhanceduploader = $core->auth->user_prefs->interface->enhanceduploader;
if ($core->auth->isSuperAdmin()) {
	$user_ui_hide_std_favicon = $core->auth->user_prefs->interface->hide_std_favicon;
}
$user_ui_iconset = @$core->auth->user_prefs->interface->iconset;

$default_tab = !empty($_GET['tab']) ? html::escapeHTML($_GET['tab']) : 'user-profile';

if (!empty($_GET['append']) || !empty($_GET['removed']) || !empty($_GET['neworder']) || 
	!empty($_GET['replaced']) || !empty($_POST['appendaction']) || !empty($_POST['removeaction'])) {
	$default_tab = 'user-favorites';
} elseif (!empty($_GET['updated'])) {
	$default_tab = 'user-options';
}
if (($default_tab != 'user-profile') && ($default_tab != 'user-options') && ($default_tab != 'user-favorites')) {
	$default_tab = 'user-profile';
}

foreach ($core->getFormaters() as $v) {
	$formaters_combo[$v] = $v;
}

foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = $k;
}

$iconsets_combo = array(__('Default') => '');
$iconsets_root = dirname(__FILE__).'/images/iconset/';
if (is_dir($iconsets_root) && is_readable($iconsets_root)) {
	if (($d = @dir($iconsets_root)) !== false) {
		while (($entry = $d->read()) !== false) {
			if ($entry != '.' && $entry != '..' && is_dir($iconsets_root.'/'.$entry)) {
				$iconsets_combo[$entry] = $entry;
			}
		}
	}
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
if (isset($_POST['user_post_format'])) 
{
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
		$core->auth->user_prefs->accessibility->put('nodragdrop',!empty($_POST['user_acc_nodragdrop']),'boolean');
		$core->auth->user_prefs->interface->put('enhanceduploader',!empty($_POST['user_ui_enhanceduploader']),'boolean');
		if ($core->auth->isSuperAdmin()) {
			# Applied to all users
			$core->auth->user_prefs->interface->put('hide_std_favicon',!empty($_POST['user_ui_hide_std_favicon']),'boolean',null,true,true);
		}
		$core->auth->user_prefs->interface->put('iconset',(!empty($_POST['user_ui_iconset']) ? $_POST['user_ui_iconset'] : ''));
		
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
if (!empty($_POST['appendaction'])) 
{
	try {
		if (empty($_POST['append'])) {
			throw new Exception(__('No favorite selected'));
		}

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
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

# Delete selected favorites
if (!empty($_POST['removeaction']))
{
	try {
		if (empty($_POST['remove'])) {
			throw new Exception(__('No favorite selected'));
		}
		
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
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
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
	($user_acc_nodragdrop ? '' : dcPage::jsLoad('js/_preferences-dragdrop.js')).
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

echo '<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; <span class="page-title">'.$page_title.'</span></h2>';

# User profile
echo '<div class="multi-part" id="user-profile" title="'.__('My profile').'">';

echo
'<form action="preferences.php" method="post" id="user-form">'.
'<fieldset><legend>'.__('My profile').'</legend>'.
'<div class="two-cols">'.
'<div class="col">'.
'<p><label for="user_name">'.__('Last Name:').
form::field('user_name',20,255,html::escapeHTML($user_name)).'</label></p>'.

'<p><label for="user_firstname">'.__('First Name:').
form::field('user_firstname',20,255,html::escapeHTML($user_firstname)).'</label></p>'.

'<p><label for="user_displayname">'.__('Display name:').
form::field('user_displayname',20,255,html::escapeHTML($user_displayname)).'</label></p>'.

'<p><label for="user_email">'.__('Email:').
form::field('user_email',20,255,html::escapeHTML($user_email)).'</label></p>'.

'<p><label for="user_url">'.__('URL:').
form::field('user_url',30,255,html::escapeHTML($user_url)).'</label></p>'.

'</div>'.

'<div class="col">'.

'<p><label for="user_lang">'.__('User language:').
form::combo('user_lang',$lang_combo,$user_lang,'l10n').'</label></p>'.

'<p><label for="user_tz">'.__('User timezone:').
form::combo('user_tz',dt::getZones(true,true),$user_tz).'</label></p>'.

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
	form::password('new_pwd',20,255).'</label></p>'.
	
	'<p><label for="new_pwd_c">'.__('Confirm password:').
	form::password('new_pwd_c',20,255).'</label></p>'.
	'</fieldset>'.
	
	'<p>'.__('If you have changed this user email or password you must provide your current password to save these modifications.').'</p>'.
	'<p><label for="cur_pwd">'.__('Your password:').
	form::password('cur_pwd',20,255).'</label></p>';
}

echo
'<p class="clear">'.
$core->formNonce().
'<input type="submit" accesskey="s" value="'.__('Save').'" /></p>'.
'</form>';

echo '</div>';

# User options : some from actual user profile, dashboard modules, ...
echo '<div class="multi-part" id="user-options" title="'.__('My options').'">';

echo
'<form action="preferences.php" method="post" id="opts-forms">'.
'<fieldset><legend>'.__('My options').'</legend>'.

'<p><label for="user_post_format">'.__('Preferred format:').
form::combo('user_post_format',$formaters_combo,$user_options['post_format']).'</label></p>'.

'<p><label for="user_post_status">'.__('Default entry status:').
form::combo('user_post_status',$status_combo,$user_post_status).'</label></p>'.

'<p><label for="user_edit_size">'.__('Entry edit field height:').
form::field('user_edit_size',5,4,(integer) $user_options['edit_size']).'</label></p>'.

'<p><label for="user_wysiwyg" class="classic">'.
form::checkbox('user_wysiwyg',1,$user_options['enable_wysiwyg']).' '.
__('Enable WYSIWYG mode').'</label></p>'.

'<p><label for="user_ui_enhanceduploader" class="classic">'.
form::checkbox('user_ui_enhanceduploader',1,$user_ui_enhanceduploader).' '.
__('Activate enhanced uploader in media manager').'</label></p>';

if (count($iconsets_combo) > 1) {
	echo 
		'<p><label for="user_ui_iconset">'.__('Iconset:').
		form::combo('user_ui_iconset',$iconsets_combo,$user_ui_iconset).'</label></p>';
} else {
	form::hidden('user_ui_iconset','');
}

if ($core->auth->isSuperAdmin()) {
	echo
	'<p><label for="user_ui_hide_std_favicon" class="classic">'.
	form::checkbox('user_ui_hide_std_favicon',1,$user_ui_hide_std_favicon).' '.
	__('Do not use standard favicon').'</label></p>'.
	'<p class="clear form-note info">'.__('This will be applied for all users').'</p>';
}

echo 
'<br class="clear" />'. //Opera sucks
'</fieldset>';

echo
'<fieldset><legend>'.__('Accessibility options').'</legend>'.

'<p><label for="user_acc_nodragdrop" class="classic">'.
form::checkbox('user_acc_nodragdrop',1,$user_acc_nodragdrop).' '.
__('Disable javascript powered drag and drop for ordering items').'</label></p>'.

'<p class="clear form-note info">'.__('Numeric fields will allow to type the elements\' ordering number.').'</p>'.
'</fieldset>';

echo
'<fieldset><legend>'.__('Dashboard modules').'</legend>'.

'<p><label for="user_dm_doclinks" class="classic">'.
form::checkbox('user_dm_doclinks',1,$user_dm_doclinks).' '.
__('Display documentation links').'</label></p>'.

'<p><label for="user_dm_dcnews" class="classic">'.
form::checkbox('user_dm_dcnews',1,$user_dm_dcnews).' '.
__('Display Dotclear news').'</label></p>'.

'<p><label for="user_dm_quickentry" class="classic">'.
form::checkbox('user_dm_quickentry',1,$user_dm_quickentry).' '.
__('Display quick entry form').'</label></p>'.

'<br class="clear" />'. //Opera sucks
'</fieldset>';

# --BEHAVIOR-- adminPreferencesForm
$core->callBehavior('adminPreferencesForm',$core);

echo
'<p class="clear">'.
$core->formNonce().
'<input type="submit" accesskey="s" value="'.__('Save').'" /></p>'.
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
				'<img src="'.dc_admin_icon_url($fav['large-icon']).'" alt="" /> '.
				form::field(array('order['.$k.']'),2,3,$count,'position','',false,'title="'.sprintf(__('position of %s'),$fav['title']).'"').
				form::hidden(array('dynorder[]','dynorder-'.$k.''),$k).
				'<label for="fuk-'.$k.'">'.form::checkbox(array('remove[]','fuk-'.$k),$k).__($fav['title']).'</label>'.
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
echo '<p class="form-note info clear">'.__('Those favorites are displayed when My Favorites list is empty.').'</p>';
$count = 0;
foreach ($ws->dumpPrefs() as $k => $v) {
	// Global favorites only
	if ($v['global']) {
		$fav = unserialize($v['value']);
		if (($fav['permissions'] == '*') || $core->auth->check($fav['permissions'],$core->blog->id)) {
			if ($count == 0) echo '<ul class="fav-list">';
			$count++;
			echo '<li id="fd-'.$k.'">'.
			'<img src="'.dc_admin_icon_url($fav['small-icon']).'" alt="" /> '.__($fav['title']).'</li>';
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
    if (__($a[1]) == __($b[1])) {
        return 0;
    }
    return (__($a[1]) < __($b[1])) ? -1 : 1;
}
$array=$array->getArrayCopy();
uasort($array,'cmp');
foreach ($array as $k => $fav) {
	if (($fav[5] == '*') || $core->auth->check($fav[5],$core->blog->id)) {
		if ($count == 0) echo '<ul class="fav-list">';
		$count++;
		echo '<li id="fa-'.$fav[0].'">'.'<label for="fak-'.$fav[0].'">'.
			form::checkbox(array('append[]','fak-'.$fav[0]),$k).
			'<img src="'.dc_admin_icon_url($fav[3]).'" alt="" /> '.'<span class="zoom"><img src="'.dc_admin_icon_url($fav[4]).'" alt="" /></span>'.
			__($fav[1]).'</label>'.'</li>';
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
