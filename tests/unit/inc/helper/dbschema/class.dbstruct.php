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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Clearbricks; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA    02111-1307    USA
#
# ***** END LICENSE BLOCK *****

namespace tests\unit;

use atoum;

require_once __DIR__ . '/../../../bootstrap.php';

//require_once CLEARBRICKS_PATH . '/dbschema/class.dbstruct.php';

class dbStruct extends atoum
{
    private $prefix = 'dc_';

    private function getConnection($driver)
    {
        $controller              = new \atoum\atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    public function testMustEscapeNameInCreateTable($driver, $query)
    {
        $con = $this->getConnection($driver);

        /*
                $s = new \dbStruct($con, $this->prefix);
                $s->blog->blog_id('varchar', 32, false);

                $tables = $s->getTables();
                $tname  = $this->prefix . 'blog';

                $this
                    ->if($schema = \dbSchema::init($con))
                    ->and($schema->createTable($tname, $tables[$tname]->getFields()))
                    ->then()
                    ->mock($con)->call('execute')
                    ->withIdenticalArguments($query)
                    ->once();
        */
    }

    /*
     * providers
     **/
    protected function testMustEscapeNameInCreateTableDataProvider()
    {
        $create_query['mysqli'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $create_query['mysqli'] .= '`blog_id` varchar(32) NOT NULL ' . "\n";
        $create_query['mysqli'] .= ') ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ';

        $create_query['mysqlimb4'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $create_query['mysqlimb4'] .= '`blog_id` varchar(32) NOT NULL ' . "\n";
        $create_query['mysqlimb4'] .= ') ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        $create_query['pgsql'] = sprintf('CREATE TABLE "%sblog" (' . "\n", $this->prefix);
        $create_query['pgsql'] .= 'blog_id varchar(32) NOT NULL ' . "\n" . ')';

        return [
            ['pgsql', $create_query['pgsql']],
            ['mysqli', $create_query['mysqli']],
            ['mysqlimb4', $create_query['mysqlimb4']],
        ];
    }
}
