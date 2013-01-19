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
require_once($sync_test_path.'/lib.date.php');

// Unit test class
class dtTest extends PHPUnit_Framework_TestCase
{
	// Fixture
	protected $d;

	public function setUp() {
		$this->d = new dt;
		$this->d->setTZ('UTC');
	}
	public function tearDown() {}

	// Unit test methods

	public function test_str() {
		$t = mktime(10, 42, 0, 8, 13, 2003);
		$this->assertTrue($this->d->str('%A %e %B %Y %H:%M:%S',$t) == 'Wednesday 13 August 2003 10:42:00');
	}

	public function test_dt2str() {
		$t = mktime(10, 42, 0, 8, 13, 2003);
		$this->assertTrue($this->d->dt2str('%A %e %B %Y %H:%M:%S',date('D, d M Y H:i:s',$t)) == 'Wednesday 13 August 2003 10:42:00');
	}

	public function test_isoe8601() {
		$t = mktime(10, 42, 0, 8, 13, 2003);
		$this->assertTrue($this->d->iso8601($t) == '2003-08-13T10:42:00+00:00');
	}

	public function test_rfc822() {
		$t = mktime(10, 42, 0, 8, 13, 2003);
		$this->assertTrue($this->d->rfc822($t) == 'Wed, 13 Aug 2003 10:42:00 +0000');
	}

	public function test_setTZ() {
		$this->d->setTZ('Europe/Paris');
		$this->assertTrue($this->d->getTZ() == 'Europe/Paris');
	}

	public function test_getTZ() {
		$this->assertTrue($this->d->getTZ() == 'UTC');
	}

	public function test_getTimeOffset() {
		// Should cope with summer/winter time offset
		$this->assertTrue($this->d->getTimeOffset('Europe/Paris') == 3600 || $this->d->getTimeOffset('Europe/Paris') == 7200);
	}

	public function test_toUTC() {
		$this->assertTrue($this->d->toUTC(time()) == time());
	}

	public function test_addTimeZone() {
		$this->assertTrue($this->d->addTimeZone('UTC') == time());
	}

	public function test_getZones() {
		$this->assertNotEmpty($this->d->getZones(false,true));
	}
}
?>