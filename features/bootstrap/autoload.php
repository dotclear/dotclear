<?php
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

define('CLEARBRICKS_PATH',__DIR__.'/../../inc/libs/clearbricks');

require_once __DIR__.'/../../vendor/autoload.php';

$__autoload = array();
$__autoload['dbStruct'] = CLEARBRICKS_PATH.'/dbschema/class.dbstruct.php';
$__autoload['dbSchema'] = CLEARBRICKS_PATH.'/dbschema/class.dbschema.php';
$__autoload['pgsqlSchema'] = CLEARBRICKS_PATH.'/dbschema/class.pgsql.dbschema.php';
$__autoload['mysqlSchema'] = CLEARBRICKS_PATH.'/dbschema/class.mysql.dbschema.php';
$__autoload['mysqliSchema'] = CLEARBRICKS_PATH.'/dbschema/class.mysqli.dbschema.php';
$__autoload['dbLayer'] = CLEARBRICKS_PATH.'/dblayer/dblayer.php';
$__autoload['mysqlConnection'] = CLEARBRICKS_PATH.'/dblayer/class.mysql.php';
$__autoload['mysqliConnection'] = CLEARBRICKS_PATH.'/dblayer/class.mysqli.php';
$__autoload['pgsqlConnection'] = CLEARBRICKS_PATH.'/dblayer/class.pgsql.php';
$__autoload['crypt'] = CLEARBRICKS_PATH.'/common/lib.crypt.php';


$loader = new \Composer\Autoload\ClassLoader();
$loader->addClassMap($__autoload);
$loader->register();
