<?php
$license_block = <<<EOF
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2015 Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------
EOF;

$dc_base = dirname(__FILE__).'/..';
$php_exec = $_SERVER['_'];

$opts = array(
	'http' => array()
);
if (getenv('http_proxy') !== false) {
	$opts['http']['proxy'] = 'tcp://'.getenv('http_proxy');
}
$context = stream_context_create($opts);

if (!file_exists($dc_base.'/composer.phar')) {
	echo "Downloading composer.phar\n";
	$composer_installer = file_get_contents('https://getcomposer.org/composer.phar',false,$context);
	file_put_contents($dc_base.'/composer.phar',$composer_installer);
}
chdir($dc_base);
echo 'Running '.$php_exec.' composer.phar install'."\n";
passthru ($php_exec.' composer.phar install');
