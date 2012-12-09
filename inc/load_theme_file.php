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

// TODO: Fix loose of relative path in CSS

# Check if config file exists
if (isset($_SERVER['DC_RC_PATH'])) {
	$zc_conf_file = $_SERVER['DC_RC_PATH'];
} elseif (isset($_SERVER['REDIRECT_DC_RC_PATH'])) {
	$zc_conf_file = $_SERVER['REDIRECT_DC_RC_PATH'];
} else {
	$zc_conf_file = dirname(__FILE__).'/config.php';
}

if (!is_file($zc_conf_file)) {
	trigger_error('Unable to open config file',E_USER_ERROR);
	exit;
}
unset($zc_conf_file);

# Start Dotclear core (required to load template engine and find paths)
require_once dirname(__FILE__).'/prepend.php';

# No request
if (empty($_GET['tf'])) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

# Add default templates path
if (defined('DC_CONTEXT_ADMIN')) {

	# Set admin context
	$_ctx = new dcAdminContext($core);
	$core->tpl->addExtension($_ctx);
	$core->tpl->getLoader()->addPath(dirname(__FILE__).'/admin/default-templates');
	
	// TODO: Find a better way to add plugins templates paths
	# --BEHAVIOR-- adminPrepend
	$core->callBehavior('adminPrepend',$core,$_ctx);
}
else {
	// TODO: dcPublicContext ...
	//$core->tpl->getLoader()->addPath(dirname(__FILE__).'/public/default-templates');
}

# Clean up requested filename
$f = path::clean($_GET['tf']);

# Find templates paths then plugins paths
$paths = $core->tpl->getLoader()->getPaths();
rsort($paths); // read default-templates last
/*
// TODO: Find a better way to add plugins templates paths
$plugins_paths = array_reverse(explode(PATH_SEPARATOR,DC_PLUGINS_ROOT));
$paths = array_merge($paths,$plugins_paths);
//*/

# Check all paths to see if file exists
foreach ($paths as $path) {
	$file = path::real($path.'/'.$f);
	
	if ($file !== false) {
		break;
	}
}
unset($paths);

# Can't find requested file
if ($file === false || !is_file($file) || !is_readable($file)) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

# Limit type of files to serve
$allow_types = array('png','jpg','jpeg','gif','css','js','swf');

# File extension is not allowed
if (!in_array(files::getExtension($file),$allow_types)) {
	header('Content-Type: text/plain');
	http::head(404,'Not Found');
	exit;
}

# Display file
http::$cache_max_age = 7200;
http::cache(array_merge(array($file),get_included_files()));
header('Content-Type: '.files::getMimeType($file));

# Temporary hack css files to regain relative path
if (files::getExtension($file) == 'css') {
	$content = preg_replace(
		'/url\((\'|)([^:].*?)(\'|)\)/msi',
		'url($1?tf='.dirname($f).'/$2$3)',
		file_get_contents($file)
	);
	header('Content-Length: '.strlen($content));
	echo $content;
}
else {
	header('Content-Length: '.filesize($file));
	readfile($file);
}
exit;
?>