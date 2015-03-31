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
    private static $session_name = null;
    public static $prefix = 'dc_';

    public function __construct($parameters) {
        $this->parameters = $parameters;
    }

    public function getSessionName($parameters) {
        if (!self::$conf_loaded) {
            self::getConnection($parameters);
        }

        return self::$session_name;
    }

    /**
     * @Given /^a user:$/
     */
    public function aUser(TableNode $table) {
        foreach ($table->getHash() as $user) {
            $this->last_id = self::addUser($user);
        }
    }

    /**
     * @Given /^a blog:$/
     */
    public function aBlog(TableNode $table) {
        foreach ($table->getHash() as $blog) {
            $this->last_id = self::addBlog($blog);
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
        if (!empty($parameters['user_id_to_not_delete'])) {
            $replace_user_id  = $parameters['user_id_to_not_delete'];
        } else {
            $replace_user_id = null;
        }
        self::executeSqlFile($parameters['sql_cleanup_file'], $replace_user_id);
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

    private function addBlog(array $params) {
        self::getConnection($this->parameters);
        if (empty($params['blog_id']) || empty($params['blog_name']) || empty($params['blog_url'])) {
            throw new Exception('blog_id, blog_name and blog_url for blog are mandatory'."\n");
        }

        $strReq = 'SELECT count(1) FROM '.self::$prefix.'blog';
        $strReq .= ' WHERE blog_id = \''.self::$con->escape($params['blog_id']).'\'';
        if ((int) self::$con->select($strReq)->f(0)==0) {
            $blog = self::$con->openCursor(self::$prefix . 'blog');
            $blog->blog_id = $params['blog_id'];
            $blog->blog_name = $params['blog_name'];
            $blog->blog_url = $params['blog_url'];
            $blog->blog_creadt = date('Y-m-d H:i:s');
            $blog->blog_upddt = date('Y-m-d H:i:s');
            $blog->blog_uid = md5(uniqid());
            $blog->insert();
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
            self::$session_name = DC_SESSION_NAME;

            self::$con = \dbLayer::init(DC_DBDRIVER,DC_DBHOST,DC_DBNAME,DC_DBUSER,DC_DBPASSWORD,DC_DBPERSIST);
        }
    }

    private static function executeSqlFile($file, $replace_user_id=null) {
        $queries = file($file);
        if ($replace_user_id) {
            $queries = str_replace('__USER_ID__', $replace_user_id, $queries);
        }
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
