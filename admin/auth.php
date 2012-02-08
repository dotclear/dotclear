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
if (isset($_SESSION['sess_user_id']))
{
	http::redirect('index.php');
}

# Loading locales for detected language
# That's a tricky hack but it works ;)
$dlang = http::getAcceptLanguage();
$dlang = ($dlang == '' ? 'en' : $dlang);
if ($dlang != 'en' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/',$dlang))
{
	l10n::set(dirname(__FILE__).'/../locales/'.$dlang.'/main');
}

$page_url = http::getHost().$_SERVER['REQUEST_URI'];

$change_pwd = $core->auth->allowPassChange() && isset($_POST['new_pwd']) && isset($_POST['new_pwd_c']) && isset($_POST['login_data']);
$login_data = !empty($_POST['login_data']) ? html::escapeHTML($_POST['login_data']) : null;
$recover = $core->auth->allowPassChange() && !empty($_REQUEST['recover']);
$safe_mode = !empty($_REQUEST['safe_mode']);
$akey = $core->auth->allowPassChange() && !empty($_GET['akey']) ? $_GET['akey'] : null;
$user_id = $user_pwd = $user_key = $user_email = null;
$err = $msg = null;

# Auto upgrade
if (empty($_GET) && empty($_POST)) {
	require dirname(__FILE__).'/../inc/dbschema/upgrade.php';
	try {
		if (($changes = dotclearUpgrade($core)) !== false) {
			$msg = __('Dotclear has been upgraded.').'<!-- '.$changes.' -->';
		}
	} catch (Exception $e) {
		$err = $e->getMessage();
	}
}

# If we have POST login informations, go throug auth process
if (!empty($_POST['user_id']) && !empty($_POST['user_pwd']))
{
	$user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
	$user_pwd = !empty($_POST['user_pwd']) ? $_POST['user_pwd'] : null;
}
# If we have COOKIE login informations, go throug auth process
elseif (isset($_COOKIE['dc_admin']) && strlen($_COOKIE['dc_admin']) == 104)
{
	# If we have a remember cookie, go through auth process with user_key
	$user_id = substr($_COOKIE['dc_admin'],40);
	$user_id = @unpack('a32',@pack('H*',$user_id));
	if (is_array($user_id))
	{
		$user_id = $user_id[1];
		$user_key = substr($_COOKIE['dc_admin'],0,40);
		$user_pwd = null;
	}
	else
	{
		$user_id = null;
	}
}

# Recover password
if ($recover && !empty($_POST['user_id']) && !empty($_POST['user_email']))
{
	$user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
	$user_email = !empty($_POST['user_email']) ? $_POST['user_email'] : '';
	try
	{
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
		$msg = sprintf(__('The e-mail was sent successfully to %s.'),$user_email);
	}
	catch (Exception $e)
	{
		$err = $e->getMessage();
	}
}
# Send new password
elseif ($akey)
{
	try
	{
		$recover_res = $core->auth->recoverUserPassword($akey);
		
		$subject = mb_encode_mimeheader('DotClear '.__('Your new password'),'UTF-8','B');
		$message =
		__('Username:').' '.$recover_res['user_id']."\n".
		__('Password:').' '.$recover_res['new_pass']."\n\n".
		preg_replace('/\?(.*)$/','',$page_url);
		
		$headers[] = 'From: dotclear@'.$_SERVER['HTTP_HOST'];
		$headers[] = 'Content-Type: text/plain; charset=UTF-8;';
		
		mail::sendMail($recover_res['user_email'],$subject,$message,$headers);
		$msg = __('Your new password is in your mailbox.');
	}
	catch (Exception $e)
	{
		$err = $e->getMessage();
	}
}
# Change password and retry to log
elseif ($change_pwd)
{
	try
	{
		$tmp_data = explode('/',$_POST['login_data']);
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
			$change_pwd = false;
			throw new Exception();
		}
		
		if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
			throw new Exception(__("Passwords don't match"));
		}
		
		if ($core->auth->checkUser($user_id,$_POST['new_pwd']) === true) {
			throw new Exception(__("You didn't change your password."));
		}
		
		$cur = $core->con->openCursor($core->prefix.'user');
		$cur->user_change_pwd = 0;
		$cur->user_pwd = $_POST['new_pwd'];
		$core->updUser($core->auth->userID(),$cur);
		
		$core->session->start();
		$_SESSION['sess_user_id'] = $user_id;
		$_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);
		
		if ($data['user_remember'])
		{
			setcookie('dc_admin',$data['cookie_admin'],strtotime('+15 days'),'','',DC_ADMIN_SSL);
		}
		
		http::redirect('index.php');
	}
	catch (Exception $e)
	{
		$err = $e->getMessage();
	}
}
# Try to log
elseif ($user_id !== null && ($user_pwd !== null || $user_key !== null))
{
	# We check the user
	$check_user = $core->auth->checkUser($user_id,$user_pwd,$user_key) === true;
	
	$cookie_admin = http::browserUID(DC_MASTER_KEY.$user_id.
		crypt::hmac(DC_MASTER_KEY,$user_pwd)).bin2hex(pack('a32',$user_id));
	
	if ($check_user && $core->auth->mustChangePassword())
	{
		$login_data = join('/',array(
			base64_encode($user_id),
			$cookie_admin,
			empty($_POST['user_remember'])?'0':'1'
		));
		
		if (!$core->auth->allowPassChange()) {
			$err = __('You have to change your password before you can login.');
		} else {
			$err = __('In order to login, you have to change your password now.');
			$change_pwd = true;
		}
	}
	elseif ($check_user && !empty($_POST['safe_mode']) && !$core->auth->isSuperAdmin()) 
	{
		$err = __('Safe Mode can only be used for super administrators.');
	}
	elseif ($check_user)
	{
		$core->session->start();
		$_SESSION['sess_user_id'] = $user_id;
		$_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);
		
		if (!empty($_POST['blog'])) {
			$_SESSION['sess_blog_id'] = $_POST['blog'];
		}
		
		if (!empty($_POST['safe_mode']) && $core->auth->isSuperAdmin()) {
			$_SESSION['sess_safe_mode'] = true;
		}
		
		if (!empty($_POST['user_remember'])) {
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
		$err = __('Wrong username or password');
	}
}

if (isset($_GET['user'])) {
	$user_id = $_GET['user'];
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
xml:lang="<?php echo $dlang; ?>" lang="<?php echo $dlang; ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Language" content="<?php echo $dlang; ?>" />
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />
  <meta name="GOOGLEBOT" content="NOSNIPPET" />
  <title><?php echo html::escapeHTML(DC_VENDOR_NAME); ?></title>
  
<?php
echo dcPage::jsLoadIE7();
echo dcPage::jsCommon();
?>
  
	<link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />
	 
  <?php
  # --BEHAVIOR-- loginPageHTMLHead
  $core->callBehavior('loginPageHTMLHead');
  ?>
  
  <script type="text/javascript">
  //<![CDATA[
  $(window).load(function() {
    var uid = $('input[name=user_id]');
    var upw = $('input[name=user_pwd]');
    uid.focus();
    
    if (upw.length == 0) { return; }
    
    if ($.browser.mozilla) {
      uid.keypress(processKey);
    } else {
      uid.keydown(processKey);
    }
    function processKey(evt) {
      if (evt.keyCode == 13 && upw.val() == '') {
         upw.focus();
	    return false;
      }
	 return true;
    };
    $.cookie('dc_admin_test_cookie',true);
    if ($.cookie('dc_admin_test_cookie')) {
      $('#cookie_help').hide();
      $.cookie('dc_admin_test_cookie', '', {'expires': -1});
    } else {
      $('#cookie_help').show();
    }
    $('#issue #more').toggleWithLegend($('#issue').children().not('#more'));
  });
  //]]>
  </script>
</head>

<body id="dotclear-admin" class="auth">

<form action="auth.php" method="post" id="login-screen">
<h1><?php echo html::escapeHTML(DC_VENDOR_NAME); ?></h1>

<?php
if ($err) {
	echo '<div class="error">'.$err.'</div>';
}
if ($msg) {
	echo '<p class="message">'.$msg.'</p>';
}

if ($akey)
{
	echo '<p><a href="auth.php">'.__('Back to login screen').'</a></p>';
}
elseif ($recover)
{
	echo
	'<fieldset><legend>'.__('Request a new password').'</legend>'.
	'<p><label for="user_id">'.__('Username:').'</label> '.
	form::field(array('user_id','user_id'),20,32,html::escapeHTML($user_id)).'</p>'.
	
	'<p><label for="user_email">'.__('Email:').'</label> '.
	form::field(array('user_email','user_email'),20,255,html::escapeHTML($user_email)).'</p>'.
	
	'<p><input type="submit" value="'.__('recover').'" />'.
	form::hidden(array('recover'),1).'</p>'.
	'</fieldset>'.
	
	'<div id="issue">'.
	'<p><a href="auth.php">'.__('Back to login screen').'</a></p></div>';
}
elseif ($change_pwd)
{
	echo
	'<fieldset><legend>'.__('Change your password').'</legend>'.
	'<p><label for="new_pwd">'.__('New password:').'</label> '.
	form::password(array('new_pwd','new_pwd'),20,255).'</p>'.
	
	'<p><label for="new_pwd_c">'.__('Confirm password:').'</label> '.
	form::password(array('new_pwd_c','new_pwd_c'),20,255).'</p>'.
	'</fielset>'.
	
	'<p><input type="submit" value="'.__('change').'" />'.
	form::hidden('login_data',$login_data).'</p>';
}
else
{
	if (is_callable(array($core->auth,'authForm')))
	{
		echo $core->auth->authForm($user_id);
	}
	else
	{
		if ($safe_mode) {
			echo '<fieldset>';
			echo '<legend>'.__('Safe mode login').'</legend>';
			echo 
				'<p class="form-note info">'.
				__('This mode allows you to login without activating any of your plugins. This may be useful to solve compatibility problems').'&nbsp;<br />'.
				__('Disable or delete any plugin suspected to cause trouble, then log out and log back in normally.').
				'</p>';
		}
		else {
			echo '<div class="fieldset">';
		}

		echo
		'<p><label for="user_id">'.__('Username:').'</label> '.
		form::field(array('user_id','user_id'),20,32,html::escapeHTML($user_id)).'</p>'.
		
		'<p><label for="user_pwd">'.__('Password:').'</label> '.
		form::password(array('user_pwd','user_pwd'),20,255).'</p>'.
		
		'<p>'.
		form::checkbox(array('user_remember','user_remember'),1).
		'<label for="user_remember" class="classic">'.
		__('Remember my ID on this computer').'</label></p>'.
		
		'<p><input type="submit" value="'.__('log in').'" /></p>';
		
		if (!empty($_REQUEST['blog'])) {
			echo form::hidden('blog',html::escapeHTML($_REQUEST['blog']));
		}
		if($safe_mode) {
			echo 
			form::hidden('safe_mode',1).
			'</fieldset>';
		}
		else {
			echo '</div>';
		}
		echo
		'<p id="cookie_help" class="error">'.__('You must accept cookies in order to use the private area.').'</p>';

		echo '<div id="issue">';
		
		if ($safe_mode) {
			echo
			'<p><a href="auth.php" id="normal_mode_link">'.__('Get back to normal authentication').'</a></p>';
		} else {
			echo '<p id="more"><strong>'.__('Connection issue?').'</strong></p>';
			if ($core->auth->allowPassChange()) {
				echo '<p><a href="auth.php?recover=1">'.__('I forgot my password').'</a></p>';
			}
			echo '<p><a href="auth.php?safe_mode=1" id="safe_mode_link">'.__('I want to log in in safe mode').'</a></p>';
		}
		
		echo '</div>';
	}
}
?>
</form>
</body>
</html>
