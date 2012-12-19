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

# If we have a session cookie, go to index.php
if (isset($_SESSION['sess_user_id'])) {
	http::redirect('index.php');
}

# Loading locales for detected language
# That's a tricky hack but it works ;)
$dlang = http::getAcceptLanguage();
$dlang = ($dlang == '' ? 'en' : $dlang);
if ($dlang != 'en' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$dlang)) {
	l10n::set(dirname(__FILE__).'/../locales/'.$dlang.'/main');
}

# Auto upgrade
if (empty($_GET) && empty($_POST)) {
	require dirname(__FILE__).'/../inc/dbschema/upgrade.php';
	try {
		if (($changes = dotclearUpgrade($core)) !== false) {
			$_ctx->setAlert(__('Dotclear has been upgraded.').'<!-- '.$changes.' -->');
		}
	}
	catch (Exception $e) {
		$_ctx->addError($e->getMessage());
	}
}

/**
Actions for authentication on admin pages
*/
class adminPageAuth
{
	# Send new password from recover email
	public static function send($akey)
	{
		global $core, $_ctx;
		
		$_ctx->akey = true;
		
		try	{
			$recover_res = $core->auth->recoverUserPassword($akey);
			
			$subject = mb_encode_mimeheader('DotClear '.__('Your new password'),'UTF-8','B');
			$message =
			__('Username:').' '.$recover_res['user_id']."\n".
			__('Password:').' '.$recover_res['new_pass']."\n\n".
			preg_replace('/\?(.*)$/','',http::getHost().$_SERVER['REQUEST_URI']);
			
			$headers[] = 'From: dotclear@'.$_SERVER['HTTP_HOST'];
			$headers[] = 'Content-Type: text/plain; charset=UTF-8;';
			
			mail::sendMail($recover_res['user_email'],$subject,$message,$headers);
			$_ctx->setAlert(__('Your new password is in your mailbox.'));
		}
		catch (Exception $e) {
			$_ctx->addError($e->getMessage());
		}
	}
	
	# Authentication process
	public static function process($form,$user_id,$user_pwd,$user_key=null)
	{
		global $core, $_ctx;
		
		# We check the user
		$check_user = $core->auth->checkUser($user_id,$user_pwd,$user_key) === true;
		
		$cookie_admin = http::browserUID(DC_MASTER_KEY.$user_id.
			crypt::hmac(DC_MASTER_KEY,$user_pwd)).bin2hex(pack('a32',$user_id));
		
		if ($check_user && $core->auth->mustChangePassword())
		{
			$form->login_data = join('/',array(
				base64_encode($user_id),
				$cookie_admin,
				$form->user_remember == '' ? '0' : '1'
			));
			
			if (!$core->auth->allowPassChange()) {
				$_ctx->addError(__('You have to change your password before you can login.'));
			} else {
				$_ctx->addError(__('In order to login, you have to change your password now.'));
				$_ctx->change_pwd = true;
			}
		}
		elseif ($check_user && $form->safe_mode != '' && !$core->auth->isSuperAdmin()) 
		{
			$_ctx->addError(__('Safe Mode can only be used for super administrators.'));
		}
		elseif ($check_user)
		{
			$core->session->start();
			$_SESSION['sess_user_id'] = $user_id;
			$_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);
			
			if ($form->blog != '') {
				$_SESSION['sess_blog_id'] = $form->blog;
			}
			
			if ($form->safe_mode != '' && $core->auth->isSuperAdmin()) {
				$_SESSION['sess_safe_mode'] = true;
			}
			
			if ($form->user_remember != '') {
				setcookie('dc_admin',$cookie_admin,strtotime('+15 days'),'','',DC_ADMIN_SSL);
			}
			
			http::redirect('index.php');
		}
		else
		{
			if (isset($_COOKIE['dc_admin'])) {
				unset($_COOKIE['dc_admin']);
				setcookie('dc_admin',false,-600,'','',DC_ADMIN_SSL);
			}
			$_ctx->addError(__('Wrong username or password'));
		}
	}
	
	# Login form action
	public static function login($form)
	{
		global $_ctx;
		
		if ($form->user_id != '' && $form->user_pwd != '') {
			self::process($form,$form->user_id,$form->user_pwd);
		}
		
		# Send post values to form
		$form->user_id = $form->user_id;
	}

	# Recover password form action
	public static function recover($form)
	{
		global $core, $_ctx;
		
		if ($form->user_id == '' || $form->user_email == '') {
			return;
		}
		
		$user_id = $form->user_id;
		$user_email = $form->user_email;
		$page_url = http::getHost().$_SERVER['REQUEST_URI'];
		
		try {
			$recover_key = $core->auth->setRecoverKey($user_id,$user_email);
			
			$subject = mail::B64Header('DotClear '.__('Password reset'));
			$message =
			__('Someone has requested to reset the password for the following site and username.')."\n\n".
			$page_url."\n".__('Username:').' '.$user_id."\n\n".
			__('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.')."\n".
			$page_url.'?akey='.$recover_key;
			
			$headers[] = 'From: '.(defined('DC_ADMIN_MAILFROM') && DC_ADMIN_MAILFROM ? DC_ADMIN_MAILFROM : 'dotclear@local');
			$headers[] = 'Content-Type: text/plain; charset=UTF-8;';
			
			mail::sendMail($user_email,$subject,$message,$headers);
			$_ctx->setAlert(sprintf(__('The e-mail was sent successfully to %s.'),$user_email));
		}
		catch (Exception $e) {
			$_ctx->addError($e->getMessage());
		}
		
		# Send post values to form
		$form->user_id = $form->user_id;
		$form->user_email = $form->user_email;
	}

	# Change password form action
	public static function change($form)
	{
		global $core, $_ctx;
		
		if ($form->login_data) {
			return;
		}
		$_ctx->change_pwd = true;
		
		$new_pwd = (string) $form->new_pwd;
		$new_pwd_c = (string) $form->new_pwd_c;
		
		try {
			$tmp_data = explode('/',$form->login_data);
			if (count($tmp_data) != 3) {
				throw new Exception();
			}
			$data = array(
				'user_id'=>base64_decode($tmp_data[0]),
				'cookie_admin'=>$tmp_data[1],
				'user_remember'=>$tmp_data[2]=='1'
			);
			if ($data['user_id'] === false) {
				throw new Exception();
			}
			
			# Check login informations
			$check_user = false;
			if (isset($data['cookie_admin']) && strlen($data['cookie_admin']) == 104)
			{
				$user_id = substr($data['cookie_admin'],40);
				$user_id = @unpack('a32',@pack('H*',$user_id));
				if (is_array($user_id))
				{
					$user_id = $user_id[1];
					$user_key = substr($data['cookie_admin'],0,40);
					$check_user = $core->auth->checkUser($user_id,null,$user_key) === true;
				}
			}
			
			if (!$core->auth->allowPassChange() || !$check_user) {
				$_ctx->change_pwd = false;
				throw new Exception();
			}
			
			if ($new_pwd != $new_pwd_c) {
				throw new Exception(__("Passwords don't match"));
			}
			
			if ($core->auth->checkUser($user_id,$new_pwd) === true) {
				throw new Exception(__("You didn't change your password."));
			}
			
			$cur = $core->con->openCursor($core->prefix.'user');
			$cur->user_change_pwd = 0;
			$cur->user_pwd = $new_pwd;
			$core->updUser($core->auth->userID(),$cur);
			
			$core->session->start();
			$_SESSION['sess_user_id'] = $user_id;
			$_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);
			
			if ($data['user_remember']) {
				setcookie('dc_admin',$data['cookie_admin'],strtotime('+15 days'),'','',DC_ADMIN_SSL);
			}
			
			http::redirect('index.php');
		}
		catch (Exception $e) {
			$_ctx->addError($e->getMessage());
		}
		
		# Send post values to form
		$form->login_data = $form->login_data;
	}
}

# Form fields
$form = new dcForm($core,'auth','auth.php');
$form
	->addField(
		new dcFieldText('user_id','',array(
			"label" => __('Username:'))))
	->addField(
		new dcFieldPassword('user_pwd','',array(
			"label" => __('Password:'))))
	->addField(
		new dcFieldText('user_email','',array(
			"label" => __('Email:'))))
	->addField(
		new dcFieldPassword('new_pwd','',array(
			"label" => __('New password:'))))
	->addField(
		new dcFieldPassword('new_pwd_c','',array(
			"label" => __('Confirm password:'))))
	->addField(
		new dcFieldCheckbox ('user_remenber',1,array(
			"label" => __('Remember my ID on this computer'))))
	->addField(
		new dcFieldSubmit('auth_login',__('log in'),array(
			'action' => array('adminPageAuth','login'))))
	->addField(
		new dcFieldSubmit('auth_recover',__('recover'),array(
			'action' => array('adminPageAuth','recover'))))
	->addField(
		new dcFieldSubmit('auth_change',__('change'),array(
			'action' => array('adminPageAuth','change'))))
	->addField(
		new dcFieldHidden ('safe_mode','0'))
	->addField(
		new dcFieldHidden ('recover','0'))
	->addField(
		new dcFieldHidden ('login_data',''))
	->addField(
		new dcFieldHidden ('blog',''));

# Context variables
$_ctx->allow_pass_change = $core->auth->allowPassChange();
$_ctx->change_pwd = $core->auth->allowPassChange() && $form->new_pwd != '' && $form->new_pwd_c != '' && $form->login_data != '';
$_ctx->recover = $form->recover = $core->auth->allowPassChange() && !empty($_REQUEST['recover']);
$_ctx->setSafeMode(!empty($_REQUEST['safe_mode'])); 
$form->safe_mode = !empty($_REQUEST['safe_mode']);
$_ctx->akey = false;

# If we have no POST login informations and have COOKIE login informations, go throug auth process
if ($form->user_id == '' && $form->user_pwd == '' 
 && isset($_COOKIE['dc_admin']) && strlen($_COOKIE['dc_admin']) == 104) {

	# If we have a remember cookie, go through auth process with user_key
	$user_id = substr($_COOKIE['dc_admin'],40);
	$user_id = @unpack('a32',@pack('H*',$user_id));
	
	if (is_array($user_id)) {
		$user_id = $user_id[1];
		$user_key = substr($_COOKIE['dc_admin'],0,40);
		$user_pwd = '';
		
		adminPageAuth::process($form,$user_id,$user_pwd,$user_key);
	}
}
# If we have an akey, go throug send password process
elseif ($core->auth->allowPassChange() && !empty($_GET['akey'])) {
	adminPageAuth::send($_GET['akey']);
}

if (isset($_GET['user'])) {
	$form->user_id = $_GET['user'];
}

$form->setup();

$core->tpl->display('auth.html.twig');
?>