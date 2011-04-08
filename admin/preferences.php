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

$page_title = __('User preferences');

$user_name = $core->auth->getInfo('user_name');
$user_firstname = $core->auth->getInfo('user_firstname');
$user_displayname = $core->auth->getInfo('user_displayname');
$user_email = $core->auth->getInfo('user_email');
$user_url = $core->auth->getInfo('user_url');
$user_lang = $core->auth->getInfo('user_lang');
$user_tz = $core->auth->getInfo('user_tz');
$user_post_status = $core->auth->getInfo('user_post_status');

$user_options = $core->auth->getOptions();

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
		$cur->user_post_status = $user_post_status = $_POST['user_post_status'];
		
		$user_options['edit_size'] = (integer) $_POST['user_edit_size'];
		if ($user_options['edit_size'] < 1) {
			$user_options['edit_size'] = 10;
		}
		$user_options['post_format'] = $_POST['user_post_format'];
		$user_options['enable_wysiwyg'] = !empty($_POST['user_wysiwyg']);
		
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
		$core->callBehavior('adminBeforeUserUpdate',$cur,$core->auth->userID());
		
		# Udate user
		$core->updUser($core->auth->userID(),$cur);
		
		# --BEHAVIOR-- adminAfterUserUpdate
		$core->callBehavior('adminAfterUserUpdate',$cur,$core->auth->userID());
		
		http::redirect('preferences.php?upd=1');
	}
	catch (Exception $e)
	{
		$core->error->add($e->getMessage());
	}
}


/* DISPLAY
-------------------------------------------------------- */
dcPage::open($page_title,
	dcPage::jsLoad('js/_preferences.js').
	dcPage::jsConfirmClose('user-form').
	
	# --BEHAVIOR-- adminPreferencesHeaders
	$core->callBehavior('adminPreferencesHeaders')
);

if (!empty($_GET['upd'])) {
		echo '<p class="message">'.__('Personal information has been successfully updated.').'</p>';
}

echo '<h2>'.$page_title.'</h2>';


echo
'<form action="preferences.php" method="post" id="user-form">'.
'<fieldset><legend>'.__('User preferences').'</legend>'.
'<div class="two-cols">'.
'<div class="col">'.
'<p><label>'.__('Last Name:').
form::field('user_name',20,255,html::escapeHTML($user_name),'',2).'</label></p>'.

'<p><label>'.__('First Name:').
form::field('user_firstname',20,255,html::escapeHTML($user_firstname),'',3).'</label></p>'.

'<p><label>'.__('Display name:').
form::field('user_displayname',20,255,html::escapeHTML($user_displayname),'',4).'</label></p>'.

'<p><label>'.__('Email:').
form::field('user_email',20,255,html::escapeHTML($user_email),'',5).'</label></p>'.

'<p><label>'.__('URL:').
form::field('user_url',30,255,html::escapeHTML($user_url),'',6).'</label></p>'.

'</div>'.

'<div class="col">'.

'<p><label>'.__('Preferred format:').
form::combo('user_post_format',$formaters_combo,$user_options['post_format'],'',7).'</label></p>'.

'<p><label>'.__('Default entry status:').
form::combo('user_post_status',$status_combo,$user_post_status,'',8).'</label></p>'.

'<p><label>'.__('Entry edit field height:').
form::field('user_edit_size',5,4,(integer) $user_options['edit_size'],'',9).'</label></p>'.

'<p><label>'.__('User language:').
form::combo('user_lang',$lang_combo,$user_lang,'l10n',10).'</label></p>'.

'<p><label>'.__('User timezone:').
form::combo('user_tz',dt::getZones(true,true),$user_tz,'',11).'</label></p>'.

'<p><label class="classic">'.
form::checkbox('user_wysiwyg',1,$user_options['enable_wysiwyg'],'',12).' '.
__('Enable WYSIWYG mode').'</label></p>'.
'</div>'.
'</div>'.
'<br class="clear" />'. //Opera sucks
'</fieldset>';

# --BEHAVIOR-- adminPreferencesForm
$core->callBehavior('adminPreferencesForm',$core);

if ($core->auth->allowPassChange())
{
	echo
	'<fieldset>'.
	'<legend>'.__('Change your password').'</legend>'.
	
	'<p><label>'.__('New password:').
	form::password('new_pwd',20,255,'','',30).'</label></p>'.
	
	'<p><label>'.__('Confirm password:').
	form::password('new_pwd_c',20,255,'','',31).'</label></p>'.
	'</fieldset>'.
	
	'<fieldset>'.
	'<p>'.__('If you want to change your email or password you must provide your current password.').'</p>'.
	'<p><label>'.__('Your password:').
	form::password('cur_pwd',20,255,'','',32).'</label></p>'.
	'</fieldset>';
}

echo
'<p class="clear">'.
$core->formNonce().
'<input type="submit" accesskey="s" value="'.__('Save').'" tabindex="33" /></p>'.
'</form>';

dcPage::helpBlock('core_user_pref');
dcPage::close();
?>