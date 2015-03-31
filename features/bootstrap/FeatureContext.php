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

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\TableNode;

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     * Every scenario gets its own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters) {
        $this->parameters = $parameters;

        $this->useContext('db', new DbContext($parameters));
    }

    /**
     * @Given /^I am logged in as "([^"]*)" with password "([^"]*)"$/
     */
    public function iAmLoggedInAsWithPassword($username, $password, $remember=false) {
        $this->fillField('Username', $username);
        $this->fillField('Password', $password);
        if ($remember) {
            $this->checkOption('user_remember');
        }
        $this->pressButton('log in');
    }

    /**
     * @Given /^I am logged in as "([^"]*)" with password "([^"]*)" with remember me$/
     */
    public function iAmLoggedInAsWithPasswordWithRememberMe($username, $password) {
        $session_name = $this->getSubcontext('db')->getSessionName($this->parameters);

        $this->iAmLoggedInAsWithPassword($username, $password, true);
        $this->getMink()->assertSession()->cookieExists($session_name);
        $this->getMink()->assertSession()->cookieExists('dc_admin');
    }

    /**
     * @When /^I restart my browser$/
     */
    public function iRestartMyBrowser() {
        $session_name = $this->getSubcontext('db')->getSessionName($this->parameters);
        $this->getMink()->assertSession()->cookieExists($session_name);
    }
}
