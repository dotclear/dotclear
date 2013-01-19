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
define('CLEARBRICKS_PATH',ROOT_UNIT_TEST.'/inc/libs/clearbricks');
require_once(CLEARBRICKS_PATH.'/common/lib.date.php');
require_once($sync_test_path.'/_common.php');

// Unit test class
class clearbricksTest extends PHPUnit_Framework_TestCase
{
	public function setUp() {}
	public function tearDown() {}
   
	public function test_clearbricks() {

		$this->assertTrue(defined('CLEARBRICKS_VERSION'));
	}
}
?>