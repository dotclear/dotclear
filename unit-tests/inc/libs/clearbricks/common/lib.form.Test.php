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
require_once($sync_test_path.'/lib.form.php');

// Unit test class
class formTest extends PHPUnit_Framework_TestCase
{
	// Fixture

	public function setUp() {}
	public function tearDown() {}

	// Unit test methods

	public function test_combo() {
		$nid = 'ctrl-combo';
		$data = array(
			'first label' => 1,
			'second label' => 2
			);
		$res = form::combo($nid,$data);
		$this->assertXmlStringEqualsXmlFile(dirname(__FILE__).'/lib.form.Test.combo.1.xml',$res);
		$nid = array('ctrl-name','ctrl-id');
		$data = array(
			'first label' => 1,
			'second label' => 2,
			'first group' => array('third label' => 3),
			'second group' => array('fourth label' => 4,'fifth label' => 5));
		$res = form::combo($nid,$data,2,'ctrl-class',13,true,'aria-required="true"');
		$this->assertXmlStringEqualsXmlFile(dirname(__FILE__).'/lib.form.Test.combo.2.xml',$res);
	}

	public function test_radio() {
		
	}
}

// Unit test class
class formSelectOptionTest extends PHPUnit_Framework_TestCase
{
	// Fixture
	protected $c;

	public function setUp() {
		$this->c = new formSelectOption('option-label',13,'ctrl-class',' rel="author"');
	}
	public function tearDown() {}

	// Unit test methods
	public function test_render() {
		$this->assertXmlStringEqualsXmlFile(dirname(__FILE__).'/lib.form.Test.render.1.xml',$this->c->render(true));
	}
}
?>