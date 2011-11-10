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

//*== DC_DEBUG ==
ini_set('display_errors',true);
error_reporting(E_ALL | E_STRICT);
define('DC_DEBUG',true);
//*/

if (!defined('DC_DEBUG')) {
	define('DC_DEBUG',false);
}

/* ------------------------------------------------------------------------------------------- */
#  ClearBricks, DotClear classes auto-loader
if (@is_dir('/usr/lib/clearbricks')) {
	define('CLEARBRICKS_PATH','/usr/lib/clearbricks');
} elseif (is_dir(dirname(__FILE__).'/libs/clearbricks')) {
	define('CLEARBRICKS_PATH',dirname(__FILE__).'/libs/clearbricks');
} elseif (isset($_SERVER['CLEARBRICKS_PATH']) && is_dir($_SERVER['CLEARBRICKS_PATH'])) {
	define('CLEARBRICKS_PATH',$_SERVER['CLEARBRICKS_PATH']);
}

if (!defined('CLEARBRICKS_PATH') || !is_dir(CLEARBRICKS_PATH)) {
	exit('No clearbricks path defined');
}

require CLEARBRICKS_PATH.'/_common.php';
$__autoload['dcCore']				= dirname(__FILE__).'/core/class.dc.core.php';
$__autoload['dcAuth']				= dirname(__FILE__).'/core/class.dc.auth.php';
$__autoload['dcBlog']				= dirname(__FILE__).'/core/class.dc.blog.php';
$__autoload['dcCategories']			= dirname(__FILE__).'/core/class.dc.categories.php';
$__autoload['dcError']				= dirname(__FILE__).'/core/class.dc.error.php';
$__autoload['dcGenericMeta']		= dirname(__FILE__).'/core/class.dc.genmeta.php';
$__autoload['dcMeta']				= dirname(__FILE__).'/core/class.dc.meta.php';
$__autoload['dcUserMeta']				= dirname(__FILE__).'/core/class.dc.usermeta.php';
$__autoload['dcMedia']				= dirname(__FILE__).'/core/class.dc.media.php';
$__autoload['dcModules']				= dirname(__FILE__).'/core/class.dc.modules.php';
$__autoload['dcThemes']				= dirname(__FILE__).'/core/class.dc.themes.php';
$__autoload['dcRestServer']			= dirname(__FILE__).'/core/class.dc.rest.php';
$__autoload['dcNamespace']			= dirname(__FILE__).'/core/class.dc.namespace.php';
$__autoload['dcSettings']			= dirname(__FILE__).'/core/class.dc.settings.php';
$__autoload['dcTrackback']			= dirname(__FILE__).'/core/class.dc.trackback.php';
$__autoload['dcUpdate']				= dirname(__FILE__).'/core/class.dc.update.php';
$__autoload['dcUtils']				= dirname(__FILE__).'/core/class.dc.utils.php';
$__autoload['dcXmlRpc']				= dirname(__FILE__).'/core/class.dc.xmlrpc.php';
$__autoload['dcLog']				= dirname(__FILE__).'/core/class.dc.log.php';
$__autoload['dcWorkspace']			= dirname(__FILE__).'/core/class.dc.workspace.php';
$__autoload['dcPrefs']				= dirname(__FILE__).'/core/class.dc.prefs.php';

$__autoload['rsExtPost']				= dirname(__FILE__).'/core/class.dc.rs.extensions.php';
$__autoload['rsExtComment']			= dirname(__FILE__).'/core/class.dc.rs.extensions.php';
$__autoload['rsExtDates']			= dirname(__FILE__).'/core/class.dc.rs.extensions.php';
$__autoload['rsExtUser']				= dirname(__FILE__).'/core/class.dc.rs.extensions.php';

$__autoload['dcMenu']				= dirname(__FILE__).'/admin/class.dc.menu.php';
$__autoload['dcPage']				= dirname(__FILE__).'/admin/lib.dc.page.php';
$__autoload['adminGenericList']		= dirname(__FILE__).'/admin/lib.pager.php';
$__autoload['adminItemsList']			= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminPostList']			= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminPostMiniList']		= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminCommentList']		= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminUserList']			= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminBlogList']			= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['adminBlogPermissionsList']	= dirname(__FILE__).'/admin/class.dc.list.php';
$__autoload['dcFilterSet']			= dirname(__FILE__).'/admin/class.dc.filter.php';
$__autoload['dcFilter']				= dirname(__FILE__).'/admin/class.dc.filter.php';
$__autoload['textFilter']				= dirname(__FILE__).'/admin/class.dc.filter.php';
$__autoload['comboFilter']				= dirname(__FILE__).'/admin/class.dc.filter.php';

$__autoload['dcTemplate']			= dirname(__FILE__).'/public/class.dc.template.php';
$__autoload['context']				= dirname(__FILE__).'/public/lib.tpl.context.php';
$__autoload['dcUrlHandlers']			= dirname(__FILE__).'/public/lib.urlhandlers.php';

# Clearbricks extensions
html::$absolute_regs[] = '/(<param\s+name="movie"\s+value=")(.*?)(")/msu';
html::$absolute_regs[] = '/(<param\s+name="FlashVars"\s+value=".*?(?:mp3|flv)=)(.*?)(&|")/msu';
/* ------------------------------------------------------------------------------------------- */


mb_internal_encoding('UTF-8');

# Setting timezone
dt::setTZ('UTC');

# CLI_MODE, boolean constant that tell if we are in CLI mode
define('CLI_MODE',PHP_SAPI == 'cli');

# Disallow every special wrapper
if (function_exists('stream_wrapper_unregister'))
{
	foreach (array('http','https','ftp','ftps','ssh2.shell','ssh2.exec',
	'ssh2.tunnel','ssh2.sftp','ssh2.scp','ogg','expect') as $p) {
		@stream_wrapper_unregister($p);
	}
}

if (isset($_SERVER['DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['DC_RC_PATH']);
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['REDIRECT_DC_RC_PATH']);
} else {
	define('DC_RC_PATH',dirname(__FILE__).'/config.php');
}

if (!is_file(DC_RC_PATH))
{
	if (strpos($_SERVER['SCRIPT_FILENAME'],'/admin') === false) {
		$path = 'admin/install/wizard.php';
	} else {
		$path = strpos($_SERVER['PHP_SELF'],'/install') === false ? 'install/wizard.php' : 'wizard.php';
	}
	http::redirect($path);
}

require DC_RC_PATH;

# Constants
define('DC_ROOT',path::real(dirname(__FILE__).'/..'));
define('DC_VERSION','2.3.1');
define('DC_DIGESTS',dirname(__FILE__).'/digests');
define('DC_L10N_ROOT',dirname(__FILE__).'/../locales');
define('DC_L10N_UPDATE_URL','http://services.dotclear.net/dc2.l10n/?version=%s');

if (!defined('DC_VENDOR_NAME')) {
	define('DC_VENDOR_NAME','Dotclear');
}

if (!defined('DC_XMLRPC_URL')) {
	define('DC_XMLRPC_URL','%1$sxmlrpc/%2$s');
}

if (!defined('DC_ADMIN_SSL')) {
	define('DC_ADMIN_SSL',false);
}

if (defined('DC_FORCE_SCHEME_443') && DC_FORCE_SCHEME_443) {
	http::$https_scheme_on_443 = true;
}

if (!defined('DC_DBPERSIST')) {
	define('DC_DBPERSIST',false);
}

if (!defined('DC_UPDATE_URL')) {
	define('DC_UPDATE_URL','http://download.dotclear.org/versions.xml');
}

if (!defined('DC_UPDATE_VERSION')) {
	define('DC_UPDATE_VERSION','stable');
}

l10n::init();

try {
	$core = new dcCore(DC_DBDRIVER,DC_DBHOST,DC_DBNAME,DC_DBUSER,DC_DBPASSWORD,DC_DBPREFIX,DC_DBPERSIST);
} catch (Exception $e) {
	init_prepend_l10n();
	__error(__('Unable to connect to database')
		,$e->getCode() == 0 ?
		sprintf(__('<p>This either means that the username and password information in '.
		'your <strong>config.php</strong> file is incorrect or we can\'t contact '.
		'the database server at "<em>%s</em>". This could mean your '.
		'host\'s database server is down.</p> '.
		'<ul><li>Are you sure you have the correct username and password?</li>'.
		'<li>Are you sure that you have typed the correct hostname?</li>'.
		'<li>Are you sure that the database server is running?</li></ul>'.
		'<p>If you\'re unsure what these terms mean you should probably contact '.
		'your host. If you still need help you can always visit the '.
		'<a href="http://forum.dotclear.net/">Dotclear Support Forums</a>.</p>').
		(DC_DEBUG ?
			__('The following error was encountered while trying to read the database:').'</p><ul><li>'.$e->getMessage().'</li></ul>' :	'')
		,(DC_DBHOST != '' ? DC_DBHOST : 'localhost')
		)
		: ''
		,20);
}

# If we have some __top_behaviors, we load them
if (isset($__top_behaviors) && is_array($__top_behaviors))
{
	foreach ($__top_behaviors as $b) {
		$core->addBehavior($b[0],$b[1]);
	}
	unset($b);
}

http::trimRequest();
try {
	http::unsetGlobals();
} catch (Exception $e) {
	header('Content-Type: text/plain');
	echo $e->getMessage();
	exit;
}

$core->url->registerDefault(array('dcUrlHandlers','home'));
$core->url->registerError(array('dcUrlHandlers','default404'));
$core->url->register('lang','','^([a-zA-Z]{2}(?:-[a-z]{2})?(?:/page/[0-9]+)?)$',array('dcUrlHandlers','lang'));
$core->url->register('post','post','^post/(.+)$',array('dcUrlHandlers','post'));
$core->url->register('preview','preview','^preview/(.+)$',array('dcUrlHandlers','preview'));
$core->url->register('category','category','^category/(.+)$',array('dcUrlHandlers','category'));
$core->url->register('archive','archive','^archive(/.+)?$',array('dcUrlHandlers','archive'));

$core->url->register('feed','feed','^feed/(.+)$',array('dcUrlHandlers','feed'));
$core->url->register('trackback','trackback','^trackback/(.+)$',array('dcUrlHandlers','trackback'));
$core->url->register('rsd','rsd','^rsd$',array('dcUrlHandlers','rsd'));
$core->url->register('xmlrpc','xmlrpc','^xmlrpc/(.+)$',array('dcUrlHandlers','xmlrpc'));

$core->setPostType('post','post.php?id=%d',$core->url->getBase('post').'/%s');

# Store upload_max_filesize in bytes
$u_max_size = files::str2bytes(ini_get('upload_max_filesize'));
$p_max_size = files::str2bytes(ini_get('post_max_size'));
if ($p_max_size < $u_max_size) {
	$u_max_size = $p_max_size;
}
define('DC_MAX_UPLOAD_SIZE',$u_max_size);
unset($u_max_size); unset($p_max_size);

# Shutdown
register_shutdown_function('__shutdown');

function __shutdown()
{
	global $__shutdown;
	if (is_array($__shutdown)) {
		foreach ($__shutdown as $f) {
			if (is_callable($f)) {
				call_user_func($f);
			}
		}
	}
	# Explicitly close session before DB connection
	try {
		if (session_id()) {
			session_write_close();
		}
	} catch (Exception $e) {}
	$GLOBALS['core']->con->close();
}

function __error($summary,$message,$code=0)
{
	# Error codes
	# 10 : no config file
	# 20 : database issue
	# 30 : blog is not defined
	# 40 : template files creation
	# 50 : no default theme
	# 60 : template processing error
	# 70 : blog is offline
	
	if (CLI_MODE)
	{
		trigger_error($summary,E_USER_ERROR);
		exit(1);
	}
	else
	{
		if (defined('DC_ERRORFILE') && is_file(DC_ERRORFILE)) {
			include DC_ERRORFILE;
		} else {
			include dirname(__FILE__).'/core_error.php';
		}
		exit;
	}
}

function init_prepend_l10n()
{
	# Loading locales for detected language
	$dlang = http::getAcceptLanguages();
	foreach($dlang as $l)
	{
		if ($l == 'en' || l10n::set(dirname(__FILE__).'/../locales/'.$l.'/main') !== false) {
			break;
		}
	}
}
?>