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

require dirname(__FILE__).'/../inc/prepend.php';

if (isset($_SERVER['PATH_INFO'])) {
	$blog_id = trim($_SERVER['PATH_INFO']);
	$blog_id = preg_replace('#^/#','',$blog_id);
} elseif (!empty($_GET['b'])) {
	$blog_id = $_GET['b'];
}

if (empty($blog_id)) {
	header('Content-Type: text/plain');
	http::head(412);
	echo 'No blog ID given';
	exit;
}

# Loading plugins
$core->plugins->loadModules(DC_PLUGINS_ROOT);

# Start XML-RPC server
$server = new dcXmlRpc($core,$blog_id);
$server->serve();
?>