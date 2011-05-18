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

#  ClearBricks and DotClear classes auto-loader
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

if (isset($_SERVER['DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['DC_RC_PATH']);
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
	define('DC_RC_PATH',$_SERVER['REDIRECT_DC_RC_PATH']);
} else {
	define('DC_RC_PATH',dirname(__FILE__).'/config.php');
}

if (!is_file(DC_RC_PATH)) {
	trigger_error('Unable to open config file',E_USER_ERROR);
	exit;
}

require DC_RC_PATH;

if (empty($_GET['pf'])) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

$allow_types = array('png','jpg','jpeg','gif','css','js','swf');

$pf = path::clean($_GET['pf']);

$paths = array_reverse(explode(PATH_SEPARATOR,DC_PLUGINS_ROOT));

# Adding admin/res folder here to load some stuff
$paths[] = dirname(__FILE__).'/swf';

foreach ($paths as $m)
{
	$PF = path::real($m.'/'.$pf);
	
	if ($PF !== false) {
		break;
	}
}
unset($paths);

if ($PF === false || !is_file($PF) || !is_readable($PF)) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

if (!in_array(files::getExtension($PF),$allow_types)) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

http::$cache_max_age = 7200;
http::cache(array_merge(array($PF),get_included_files()));

header('Content-Type: '.files::getMimeType($PF));
header('Content-Length: '.filesize($PF));
readfile($PF);
exit;
?>