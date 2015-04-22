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

namespace tests\unit;

use atoum;

require_once __DIR__.'/../../bootstrap.php';
$f = str_replace('\\','/',__FILE__);
require_once(str_replace('tests/unit/','',$f));

class dcCore extends atoum
{
    private $prefix = 'dc_';

	private function getConnection($driver) {
		$controller = new \atoum\mock\controller();
		$controller->__construct = function() {};

		$class_name = sprintf('\mock\%sConnection',$driver);
		$con = new $class_name($driver,$controller);
		$this->calling($con)->driver = $driver;
		$this->calling($con)->escape = function($s) { // just for order, so don't care
            return $s;
        };
		$this->calling($con)->select = function($sql) {
            return new \staticRecord(array(),array());
        };

		return $con;
	}

    public function testGetUsers($driver) {
        $con = $this->getConnection($driver);

        $controller = new \atoum\mock\controller();
        $controller->__construct = function() {};

        $query = 'SELECT U.user_id,user_super,user_status,user_pwd,user_change_pwd,user_name,user_firstname,user_displayname,user_email,user_url,user_desc, user_lang,user_tz, user_post_status,user_options, count(P.post_id) AS nb_post FROM user U LEFT JOIN post P ON U.user_id = P.user_id WHERE NULL IS NULL GROUP BY U.user_id,user_super,user_status,user_pwd,user_change_pwd,user_name,user_firstname,user_displayname,user_email,user_url,user_desc, user_lang,user_tz,user_post_status,user_options ORDER BY U.user_id ASC ';

        $core = new \mock\dcCore(null,null,null,null,null,null,null,$controller);
        $core->con = $con;
        $this
			->if($core->getUsers())
            ->then()
				  ->mock($con)->call('select')
				  ->withIdenticalArguments($query)
				  ->once();
    }

    public function testGetUsersWithParams($driver,$params,$query) {
        $con = $this->getConnection($driver);

        $controller = new \atoum\mock\controller();
        $controller->__construct = function() {};

        $core = new \mock\dcCore(null,null,null,null,null,null,null,$controller);
        $core->con = $con;

        $this
			->if($core->getUsers($params))
            ->then()
				  ->mock($con)->call('select')
				  ->withIdenticalArguments($query)
				  ->once();
    }

    /*
     * DataProviders
    **/
    protected function testGetUsersDataProvider() {
        $query = array();

        return array(
			array('pgsql'),
			array('sqlite'),
			array('mysql'),
			array('mysqli'),
		);
    }

    protected function testGetUsersWithParamsDataProvider() {
        $base_query = 'SELECT U.user_id,user_super,user_status,user_pwd,user_change_pwd,user_name,user_firstname,user_displayname,user_email,user_url,user_desc, user_lang,user_tz, user_post_status,user_options, count(P.post_id) AS nb_post FROM user U LEFT JOIN post P ON U.user_id = P.user_id WHERE NULL IS NULL GROUP BY U.user_id,user_super,user_status,user_pwd,user_change_pwd,user_name,user_firstname,user_displayname,user_email,user_url,user_desc, user_lang,user_tz,user_post_status,user_options ORDER BY ';

        return array(
			array('pgsql',array('order' => 'user_id asc'),$base_query.'U.user_id asc '),
			array('pgsql',array('order' => 'U.user_id asc'),$base_query.'U.user_id asc '),
			array('mysql',array('order' => 'user_id asc'),$base_query.'U.user_id asc '),
			array('mysql',array('order' => 'U.user_id asc'),$base_query.'U.user_id asc '),
			array('mysqli',array('order' => 'user_id asc'),$base_query.'U.user_id asc '),
			array('mysqli',array('order' => 'U.user_id asc'),$base_query.'U.user_id asc '),
			array('sqlite',array('order' => 'user_id asc'),$base_query.'U.user_id asc '),
			array('sqlite',array('order' => 'U.user_id asc'),$base_query.'U.user_id asc '),

            array('pgsql',array('order' => 'nb_post desc'),$base_query.'P.nb_post desc '),
			array('pgsql',array('order' => 'P.nb_post desc'),$base_query.'P.nb_post desc '),
			array('mysql',array('order' => 'nb_post desc'),$base_query.'P.nb_post desc '),
			array('mysql',array('order' => 'P.nb_post desc'),$base_query.'P.nb_post desc '),
			array('mysqli',array('order' => 'nb_post desc'),$base_query.'P.nb_post desc '),
			array('mysqli',array('order' => 'P.nb_post desc'),$base_query.'P.nb_post desc '),
			array('sqlite',array('order' => 'nb_post desc'),$base_query.'P.nb_post desc '),
			array('sqlite',array('order' => 'P.nb_post desc'),$base_query.'P.nb_post desc '),
		);
    }
}