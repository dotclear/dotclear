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

use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Behat\Event\SuiteEvent;
use Behat\Behat\Event\ScenarioEvent;
use Behat\Gherkin\Node\TableNode;

include_once __DIR__.'/autoload.php';

class DbContext extends RawMinkContext
{
    private static $conf_loaded = false;
    private static $con = null;
    public static $prefix = 'dc_';

    public function __construct($parameters) {
        $this->parameters = $parameters;
    }

    /**
     * @Given /^a user:$/
     */
    public function aUser(TableNode $table) {
        foreach ($table->getHash() as $user) {
            $this->last_id = self::addUser($user);
        }
    }

    /*
    /* ORM methods
    **/
    /**
     * @BeforeSuite
     */
    public static function prepareDB(SuiteEvent $event) {
        $parameters = $event->getContextParameters();

        if (empty($parameters['config_file']) || !is_readable($parameters['config_file'])) {
            throw new Exception(sprintf('Config file %s does not exist or not readable', $parameters['config_file']));
        }
        if (empty($parameters['sql_init_file'])  || !is_readable($parameters['sql_init_file'])) {
            throw new Exception(sprintf('sql init file %s does not exist or not readable', $parameters['sql_init_file']));
        }
        self::getConnection($parameters);
        self::executeSqlFile($parameters['sql_init_file']);
    }

    /**
     * @AfterScenario
     */
    public static function cleanDB(ScenarioEvent $event) {
        $parameters = $event->getContext()->parameters;

        if (empty($parameters['config_file']) || !is_readable($parameters['config_file'])) {
            throw new Exception(sprintf('Config file %s does not exist or not readable', $parameters['config_file']));
        }
        self::getConnection($parameters);

        if (empty($parameters['sql_cleanup_file']) && !is_readable($parameters['sql_cleanup_file'])) {
            throw new Exception(sprintf('sql cleanup file %s does not exist or not readable', $parameters['sql_cleanup_file']));
        }
        self::executeSqlFile($parameters['sql_cleanup_file']);
   }

    private function addUser(array $params) {
        self::getConnection($this->parameters);
        if (empty($params['username']) || empty($params['password'])) {
            throw new Exception('Username and Password for user are mandatory'."\n");
        }
        $strReq = 'SELECT count(1) FROM '.self::$prefix.'user';
        $strReq .= ' WHERE user_id = \''.self::$con->escape($params['username']).'\'';
        if ((int) self::$con->select($strReq)->f(0)==0) {
            $user = self::$con->openCursor(self::$prefix . 'user');
            $user->user_id = $params['username'];
			$user->user_pwd = \crypt::hmac(DC_MASTER_KEY,$params['password']);
            $user->user_super = 1;
            $user->insert();
        }
    }

    /**
     *  Create a database connexion if none exists
     */
    private static function getConnection($parameters) {
        if (!self::$conf_loaded) {
            // @TODO : find a better way to include conf without define DC_RC_PATH
            define('DC_RC_PATH', $parameters['config_file']);

            include($parameters['config_file']);
            self::$conf_loaded = true;
            self::$prefix = DC_DBPREFIX;

            self::$con = \dbLayer::init(DC_DBDRIVER,DC_DBHOST,DC_DBNAME,DC_DBUSER,DC_DBPASSWORD,DC_DBPERSIST);
        }
    }

    private static function executeSqlFile($file) {
        $queries = file($file);
        if (!empty($queries)) {
            try {
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        self::$con->execute($query);
                    }
                }
            } catch (\Exception $e) {
                // @TODO : make something ; exception thrown "database schema has changed (17)" ???
            }
        }
    }
}
