<?php
# -- BEGIN LICENSE BLOCK ---------------------------------------
#
# This file is part of Dotclear 2.
#
# Copyright (c) 2003-2014 Olivier Meunier & Association Dotclear
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK -----------------------------------------

require_once __DIR__ . '/../../vendor/autoload.php';

define('CLEARBRICKS_PATH', __DIR__ . '/../../inc/libs/clearbricks');

$__autoload                     = [];
$__autoload['dbLayer']          = CLEARBRICKS_PATH . '/dblayer/dblayer.php';
$__autoload['staticRecord']     = CLEARBRICKS_PATH . '/dblayer/dblayer.php';
$__autoload['sqliteConnection'] = CLEARBRICKS_PATH . '/dblayer/class.sqlite.php';
$__autoload['mysqliConnection'] = CLEARBRICKS_PATH . '/dblayer/class.mysqli.php';
$__autoload['pgsqlConnection']  = CLEARBRICKS_PATH . '/dblayer/class.pgsql.php';
$__autoload['record']           = CLEARBRICKS_PATH . '/dblayer/dblayer.php';
$__autoload['template']         = CLEARBRICKS_PATH . '/template/class.template.php';
$__autoload['path']             = CLEARBRICKS_PATH . '/common/lib.files.php';

$__autoload['dcCore']      = __DIR__ . '/../../inc/core/class.dc.core.php';
$__autoload['dcNamespace'] = __DIR__ . '/../../inc/core/class.dc.namespace.php';

$loader = new \Composer\Autoload\ClassLoader();
$loader->addClassMap($__autoload);
$loader->register();

function __($s)
{
    return $s;
}
