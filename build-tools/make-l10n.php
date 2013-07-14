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
	
	$l = l10n::getPoFile($f);
	
	$fcontent =
	"<?php\n".
	$license_block.
	"#\n#\n#\n".
	"#        DOT NOT MODIFY THIS FILE !\n\n\n\n\n";
	
	foreach (l10n::getPoFile($f) as $vo => $tr) {
		$vo = str_replace("'","\\'",$vo);
		$tr = str_replace("'","\\'",$tr);
		$fcontent .= '$GLOBALS[\'__l10n\'][\''.$vo.'\'] = \''.$tr.'\';'."\n";
	}
	
	$fcontent .= "?>";
	
	echo $dest.' : ';
	if (($fp = fopen($dest,'w')) !== false) {
		fwrite($fp,$fcontent,strlen($fcontent));
		fclose($fp);
		echo 'OK';
	} else {
		echo 'FAILED';
	}
	echo "\n";
}
?>