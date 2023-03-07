<?php

# ***** BEGIN LICENSE BLOCK *****
# This file is part of Clearbricks.
# Copyright (c) 2003-2013 Olivier Meunier & Association Dotclear
# All rights reserved.
#
# Clearbricks is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Clearbricks is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#
# ***** END LICENSE BLOCK *****

define('CLEARBRICKS_PATH', __DIR__ . '/../../inc/helper');

// Composer Autoloader

require_once __DIR__ . '/../../vendor/autoload.php';

// Dotclear Autoloader

require_once __DIR__ . '/../../src/Autoloader.php';

$autoloader = new Autoloader('', '', true);
$autoloader->addNamespace('Dotclear', implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'src']));

// Clearbricks Autoloader (deprecated)

$__autoload = [];

$__autoload['dbStruct']        = CLEARBRICKS_PATH . '/dbschema/class.dbstruct.php';
$__autoload['dbSchema']        = CLEARBRICKS_PATH . '/dbschema/class.dbschema.php';
$__autoload['mysqliSchema']    = CLEARBRICKS_PATH . '/dbschema/class.mysqli.dbschema.php';
$__autoload['mysqlimb4Schema'] = CLEARBRICKS_PATH . '/dbschema/class.mysqlimb4.dbschema.php';
$__autoload['pgsqlSchema']     = CLEARBRICKS_PATH . '/dbschema/class.pgsql.dbschema.php';
$__autoload['sqliteSchema']    = CLEARBRICKS_PATH . '/dbschema/class.sqlite.dbschema.php';

$__autoload['dbLayer']             = CLEARBRICKS_PATH . '/dblayer/dblayer.php';
$__autoload['mysqliConnection']    = CLEARBRICKS_PATH . '/dblayer/class.mysqli.php';
$__autoload['mysqlimb4Connection'] = CLEARBRICKS_PATH . '/dblayer/class.mysqlimb4.php';
$__autoload['pgsqlConnection']     = CLEARBRICKS_PATH . '/dblayer/class.pgsql.php';
$__autoload['sqliteConnection']    = CLEARBRICKS_PATH . '/dblayer/class.sqlite.php';

function cb_autoload($name)
{
    global $__autoload;

    if (isset($__autoload[$name])) {
        require_once $__autoload[$name];
    }
}
spl_autoload_register('cb_autoload');
