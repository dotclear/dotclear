<?php
# error reporting
ini_set('display_errors',1);
error_reporting(E_ALL|E_STRICT);

# include PHPUnit
require 'PHPUnit/Autoload.php';

// Paths
if (!defined('NAME_UNIT_TEST')) {
	defined('NAME_UNIT_TEST','unit-tests');
}
$sync_test_path = preg_replace('#/'.NAME_UNIT_TEST.'#','',dirname(__FILE__));

// File to test
//define('CLEARBRICKS_PATH',ROOT_UNIT_TEST.'/inc/libs/clearbricks');
//require_once(CLEARBRICKS_PATH.'/common/lib.date.php');
require_once($sync_test_path.'/lib.crypt.php');

// Unit test class
class cryptTest extends PHPUnit_Framework_TestCase
{
	// Fixture
	public function setUp() {}
	public function tearDown() {}

	// Unit test methods
	public function test_hmac() {
		$this->assertTrue(crypt::hmac('dotclear is da best','lorem impsum','md5') == 'c799ac384bfc6dbff9988764989010c0');
	}

	public function test_createPassword() {
		$this->assertTrue(strlen(crypt::createPassword()) == 8);
	}
}
?>