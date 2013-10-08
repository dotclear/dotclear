#!/usr/bin/env php
<?php
$license_block = <<<EOF
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
EOF;

require dirname(__FILE__).'/../inc/libs/clearbricks/common/lib.l10n.php';

$path = (!empty($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : getcwd();
$path = realpath($path);

$cmd = 'find '.$path.' -type f -name \'*.po\'';
exec($cmd,$eres,$ret);

$res = array();

foreach ($eres as $f)
{
	$dest = dirname($f).'/'.basename($f,'.po').'.lang.php';
	echo "l10n file ".$dest.": ";
	
	if (l10n::generatePhpFileFromPo(dirname($f).'/'.basename($f,'.po'),$license_block)) {
		echo 'OK';
	} else {
		echo 'FAILED';
	}
	echo "\n";
}
?>