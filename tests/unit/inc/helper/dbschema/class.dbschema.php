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

//require_once CLEARBRICKS_PATH . '/dbschema/class.dbschema.php';

class dbSchema extends atoum
{
    private $prefix = 'dc_';
    private $index  = 0;

    private function getConnection($driver)
    {
        $controller              = new \atoum\atoum\mock\controller();
        $controller->__construct = function () {};

        $class_name                  = sprintf('\mock\%sConnection', $driver);
        $con                         = new $class_name($driver, $controller);
        $this->calling($con)->driver = $driver;

        return $con;
    }

    public function testQueryForCreateTable($driver, $query)
    {
        $con = $this->getConnection($driver);

        $table_name = $this->prefix . 'blog';
        $fields     = ['status' => ['type' => 'smallint', 'len' => 0, 'null' => false, 'default' => -2]];

        $this
            ->if($schema = \dbSchema::init($con))
            ->and($schema->createTable($table_name, $fields))
            ->then()
            ->mock($con)->call('execute')
            ->withIdenticalArguments($query)
            ->once();
    }

    public function testQueryForRetrieveFields($driver, $query)
    {
        $con = $this->getConnection($driver);

        $table_name = $this->prefix . 'blog';

        $this
            ->if($schema = \dbSchema::init($con))
            ->and($schema->getColumns($table_name))
            ->then()
            ->mock($con)->call('select')
            ->withIdenticalArguments($query)
            ->once();
    }

    /*
     * providers
     **/
    protected function testQueryForCreateTableDataProvider()
    {
        $query['pgsql'] = sprintf('CREATE TABLE "%sblog" (' . "\n", $this->prefix);
        $query['pgsql'] .= 'status smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['pgsql'] .= ')';

        $query['mysqli'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $query['mysqli'] .= '`status` smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['mysqli'] .= ') ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin ';

        $query['mysqlimb4'] = sprintf('CREATE TABLE `%sblog` (' . "\n", $this->prefix);
        $query['mysqlimb4'] .= '`status` smallint NOT NULL DEFAULT -2 ' . "\n";
        $query['mysqlimb4'] .= ') ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        return [
            ['pgsql', $query['pgsql']],
            ['mysqli', $query['mysqli']],
            ['mysqlimb4', $query['mysqlimb4']],
        ];
    }

    protected function testQueryForRetrieveFieldsDataProvider()
    {
        $query['pgsql'] = sprintf("SELECT column_name, udt_name, character_maximum_length, is_nullable, column_default FROM information_schema.columns WHERE table_name = '%sblog' ", $this->prefix);

        $query['mysqli'] = sprintf('SHOW COLUMNS FROM `%sblog`', $this->prefix);

        $query['mysqlimb4'] = $query['mysqli'];

        return [
            ['pgsql', $query['pgsql']],
            ['mysqli', $query['mysqli']],
            ['mysqlimb4', $query['mysqlimb4']],
        ];
    }
}
