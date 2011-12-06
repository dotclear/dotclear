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

if (isset($_SERVER['DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['DC_RC_PATH']);
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['REDIRECT_DC_RC_PATH']);
} else {
	define('DC_RC_PATH',dirname(__FILE__).'/../../inc/config.php');
}

#  ClearBricks and DotClear classes auto-loader
if (@is_dir('/usr/lib/clearbricks')) {
	define('CLEARBRICKS_PATH','/usr/lib/clearbricks');
} elseif (is_dir(dirname(__FILE__).'/../../inc/libs/clearbricks')) {
	define('CLEARBRICKS_PATH',dirname(__FILE__).'/../../inc/libs/clearbricks');
} elseif (isset($_SERVER['CLEARBRICKS_PATH']) && is_dir($_SERVER['CLEARBRICKS_PATH'])) {
	define('CLEARBRICKS_PATH',$_SERVER['CLEARBRICKS_PATH']);
}

if (!defined('CLEARBRICKS_PATH') || !is_dir(CLEARBRICKS_PATH)) {
	exit('No clearbricks path defined');
}

require CLEARBRICKS_PATH.'/_common.php';

# Loading locales for detected language
$dlang = http::getAcceptLanguage();
if ($dlang != 'en')
{
	l10n::init();
	l10n::set(dirname(__FILE__).'/../../locales/'.$dlang.'/main');
}

if (is_file(DC_RC_PATH)) {
	http::redirect('index.php');
}

$DBDRIVER = !empty($_POST['DBDRIVER']) ? $_POST['DBDRIVER'] : 'mysql';
$DBHOST = !empty($_POST['DBHOST']) ? $_POST['DBHOST'] : '';
$DBNAME = !empty($_POST['DBNAME']) ? $_POST['DBNAME'] : '';
$DBUSER = !empty($_POST['DBUSER']) ? $_POST['DBUSER'] : '';
$DBPASSWORD = !empty($_POST['DBPASSWORD']) ? $_POST['DBPASSWORD'] : '';
$DBPREFIX = !empty($_POST['DBPREFIX']) ? $_POST['DBPREFIX'] : 'dc_';

if (!empty($_POST))
{
	try
	{
		# Tries to connect to database
		try {
			$con = dbLayer::init($DBDRIVER,$DBHOST,$DBNAME,$DBUSER,$DBPASSWORD);
		} catch (Exception $e) {
			throw new Exception('<p>' . __($e->getMessage()) . '</p>');
		}
		
		# Checks system capabilites
		require dirname(__FILE__).'/check.php';
		if (!dcSystemCheck($con,$_e)) {
			$can_install = false;
			throw new Exception('<p>'.__('Dotclear cannot be installed.').'</p><ul><li>'.implode('</li><li>',$_e).'</li></ul>');
		}
		
		# Check if dotclear is already installed
		$schema = dbSchema::init($con);
		if (in_array($DBPREFIX.'version',$schema->getTables())) {
			throw new Exception(__('Dotclear is already installed.'));
		}
		
		# Does config.php.in exist?
		$config_in = dirname(__FILE__).'/../../inc/config.php.in';
		if (!is_file($config_in)) {
			throw new Exception(sprintf(__('File %s does not exist.'),$config_in));
		}
		
		# Can we write config.php
		if (!is_writable(dirname(DC_RC_PATH))) {
			throw new Exception(sprintf(__('Cannot write %s file.'),DC_RC_PATH));
		}
		
		# Creates config.php file
		$full_conf = file_get_contents($config_in);
		
		writeConfigValue('DC_DBDRIVER',$DBDRIVER,$full_conf);
		writeConfigValue('DC_DBHOST',$DBHOST,$full_conf);
		writeConfigValue('DC_DBUSER',$DBUSER,$full_conf);
		writeConfigValue('DC_DBPASSWORD',$DBPASSWORD,$full_conf);
		writeConfigValue('DC_DBNAME',$DBNAME,$full_conf);
		writeConfigValue('DC_DBPREFIX',$DBPREFIX,$full_conf);
		
		$admin_url = preg_replace('%install/wizard.php$%','',$_SERVER['REQUEST_URI']);
		writeConfigValue('DC_ADMIN_URL',http::getHost().$admin_url,$full_conf);
		writeConfigValue('DC_ADMIN_MAILFROM','dotclear@'.$_SERVER['HTTP_HOST'],$full_conf);
		writeConfigValue('DC_MASTER_KEY',md5(uniqid()),$full_conf);
		
		$fp = @fopen(DC_RC_PATH,'wb');
		if ($fp === false) {
			throw new Exception(sprintf(__('Cannot write %s file.'),DC_RC_PATH));
		}
		fwrite($fp,$full_conf);
		fclose($fp);
		chmod(DC_RC_PATH, 0666);
		
		$con->close();
		http::redirect('index.php?wiz=1');
	}
	catch (Exception $e)
	{
		$err = $e->getMessage();
	}
}

function writeConfigValue($name,$val,&$str)
{
	$val = str_replace("'","\'",$val);
	$str = preg_replace('/(\''.$name.'\')(.*?)$/ms','$1,\''.$val.'\');',$str);
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
xml:lang="en" lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <meta http-equiv="Content-Language" content="en" />
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />
  <meta name="GOOGLEBOT" content="NOSNIPPET" />
  <title><?php echo __('Dotclear installation wizard'); ?></title>
	<link rel="stylesheet" href="../style/install.css" type="text/css" media="screen" /> 
</head>

<body id="dotclear-admin" class="install">
<div id="content">
<?php
echo
'<h1>'.__('Dotclear installation wizard').'</h1>'.
'<div id="main">';

if (!empty($err)) {
	echo '<div class="error"><p><strong>'.__('Errors:').'</strong></p>'.$err.'</div>';
} else {
	echo '<h2>'.__('Welcome').'</h2>'.
		'<p>'.__('To complete your Dotclear installation and start writing on your blog, '.
			'we just need to know how to access your database and who you are. '.
			'Just fill this two steps wizard with this information and we will be done.').'</p>'.
		'<p class="message"><strong>'.__('Attention:').'</strong> '.
		__('this wizard may not function on every host. If it does not work for you, '.
			'please refer to <a href="http://dotclear.org/documentation/2.0/admin/install">'.
			'the documentation</a> to learn how to create the <strong>config.php</strong> '.
			'file manually.').'</p>';
}

echo
'<h2>'.__('System information').'</h2>'.

'<p>'.__('Please provide the following information needed to create your configuration file.').'</p>'.

'<form action="wizard.php" method="post">'.
'<p><label class="required" for="DBDRIVER"><abbr title="'.__('Required field').'">*</abbr> '.__('Database type:').' '.
form::combo('DBDRIVER',array('MySQL'=>'mysql','PostgreSQL'=>'pgsql'),$DBDRIVER).'</label></p>'.
'<p><label for="DBHOST">'.__('Database Host Name:').' '.
form::field('DBHOST',30,255,html::escapeHTML($DBHOST)).'</label></p>'.
'<p><label for="DBNAME">'.__('Database Name:').' '.
form::field('DBNAME',30,255,html::escapeHTML($DBNAME)).'</label></p>'.
'<p><label for="DBUSER">'.__('Database User Name:').' '.
form::field('DBUSER',30,255,html::escapeHTML($DBUSER)).'</label></p>'.
'<p><label for="DBPASSWORD">'.__('Database Password:').' '.
form::password('DBPASSWORD',30,255).'</label></p>'.
'<p><label for="DBPREFIX" class="required"><abbr title="'.__('Required field').'">*</abbr> '.__('Database Tables Prefix:').' '.
form::field('DBPREFIX',30,255,html::escapeHTML($DBPREFIX)).'</label></p>'.

'<p><input type="submit" value="'.__('Continue').'" /></p>'.
'</form>';
?>
</div>
</div>
</body>
</html>