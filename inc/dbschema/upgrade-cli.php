#!/usr/bin/env php
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
if (!defined('DC_RC_PATH')) { return; }

try
{
	if (isset($_SERVER['argv'][1])) {
		$dc_conf = $_SERVER['argv'][1];
	} elseif (isset($_SERVER['DC_RC_PATH'])) {
		$dc_conf = realpath($_SERVER['DC_RC_PATH']);
	} else {
		$dc_conf = dirname(__FILE__).'/../config.php';
	}
	
	if (!is_file($dc_conf)) {
		throw new Exception(sprintf('%s is not a file',$dc_conf));
	}
	
	$_SERVER['DC_RC_PATH'] = $dc_conf;
	unset($dc_conf);
	
	require dirname(__FILE__).'/../prepend.php';
	require dirname(__FILE__).'/upgrade.php';
	
	echo "Starting upgrade process\n";
	$core->con->begin();
	try {
		$changes = dotclearUpgrade(&$core);
	} catch (Exception $e) {
		$core->con->rollback();
		throw $e;
	}
	$core->con->commit();
	echo 'Upgrade process successfully completed ('.$changes."). \n";
	exit(0);
}
catch (Exception $e)
{
	echo $e->getMessage()."\n";
	exit(1);
}
?>