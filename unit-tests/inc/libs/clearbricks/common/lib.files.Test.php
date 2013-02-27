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
require_once($sync_test_path.'/lib.files.php');

// Unit test class
class fileTest extends PHPUnit_Framework_TestCase
{
	// Fixture

	public function setUp() {}
	public function tearDown() {}

	// Unit test methods

	public function test_scandir() {
		// Test with an existing dir
		$d = files::scandir(dirname(__FILE__));
		$this->assertTrue(count($d) > 1);
		// Test optional order
		$this->assertTrue(files::scandir(dirname(__FILE__),true) == rsort($d));
		// Test with an unexisting dir
		try {
			$this->assertCount(0,files::scandir(dirname(__FILE__).'-undefined'));
		} catch (exception $e) {
			$this->assertTrue($e->getMessage() == __('Unable to open directory.'));
		}
	}

	public function test_getExtension() {
		$this->assertTrue(files::getExtension(__FILE__) == 'php');
	}

	public function test_getMimeType() {
		// Test with a known extension
		$this->assertTrue(files::getMimeType(__FILE__.'.pdf') == 'application/pdf');
		// Test with an unknown extension
		$this->assertTrue(files::getMimeType(__FILE__) == 'text/plain');
	}

	public function test_mimeTypes() {
		$this->assertNotEmpty($t=files::mimeTypes());
		return $t;
	}

	/**
	 * @depends test_mimeTypes
	 */
	public function test_registerMimeTypes(array $t) {
		// Register an unknown mime type
		files::registerMimeTypes(array('php' => 'text/plain'));
		$this->assertTrue(count(files::mimeTypes()) == count($t)+1);
		// Register an known mime type
		files::registerMimeTypes(array('pdf' => 'application/pdf'));
		$this->assertTrue(count(files::mimeTypes()) == count($t)+1);
	}

	public function test_isDeletable() {
		// Test with a file
		$this->assertTrue(files::isDeletable(__FILE__));
		// Test with a full directory
		$this->assertFalse(files::isDeletable(dirname(__FILE__)));
		// Test with an unknown file
		$this->assertNull(files::isDeletable(__FILE__.'-undefined'));
	}

	public function test_makeDir() {
		// Try to create an empty simple dir
		$d = dirname(__FILE__).'/simple-dir';
		files::makeDir($d);
		$this->assertTrue(is_dir($d));
		// Try to create an empty deeper dir
		$dd = $d.'/try/deeper';
		try {
			files::makeDir($dd);
		} catch (exception $e) {
			$this->assertTrue($e->getMessage() == __('Unable to create directory.'));
		}
		$this->assertFalse(is_dir($dd));
		// Try to create an empty deeper dir, including creation of intermediary dir
		files::makeDir($dd,true);
		$this->assertTrue(is_dir($dd));
		return $d;
	}

	/**
	 * @depends test_makeDir
	 */
	public function test_deltree($d) {
		// Delete an existing tree
		$this->assertTrue(files::deltree($d));
		$this->assertFalse(is_dir($d));
		// Try to delete an unexisting tree
		try {
			files::deltree($d);
		} catch (exception $e) {
			return;
		}
		$this->fail(__('Expected exception not thrown.'));
	}

	public function test_touch() {
		$f = tempnam(dirname(__FILE__),'dc_');
		$t = time();
		files::touch($f);
		$this->assertTrue($t <= filemtime($f));
		unlink($f);
		// Try to touch an unexisting file
		files::touch($f);
		$this->assertFalse(file_exists($f));
	}

	public function test_inheritChmod() {
		$f = tempnam(dirname(__FILE__),'dc_');
		files::inheritChmod($f);
		$this->assertTrue((fileperms($f) & 0x0FFF) == (fileperms(dirname($f)) & 0x0FFF));
		unlink($f);
	}

	public function test_putContent() {
		$f = tempnam(dirname(__FILE__),'dc_');
		$f_content = 'Lorem ipsum';
		files::putContent($f,$f_content);
		$this->assertTrue(file_get_contents($f) == $f_content);
		unlink($f);
		// Try with a read-only file and unexisting file
		$f = tempnam(dirname(__FILE__),'dc_');
		chmod($f,0444);
		try {
			files::putContent($f,$f_content);
			unlink($f);
			files::putContent($f,$f_content);
		} catch (exception $e) {
			unlink($f);
			return;
		}
		$this->fail(__('Expected exception not thrown.'));
	}

	public function test_size() {
		$this->assertTrue(files::size(1024*42) == '42 KB');
	}

	public function test_str2bytes() {
		$this->assertTrue(files::str2bytes('42K') == (1024*42));
		$this->assertNotInternalType('integer',files::str2bytes('42X'));
	}

	public function test_uploadStatus() {
		$f = array();
		try {
			files::uploadStatus($f);
		} catch (exception $e) {
			$this->assertTrue($e->getMessage() == __('Not an uploaded file.'));
		}
		$f['error'] = UPLOAD_ERR_OK;
		$this->assertTrue(files::uploadStatus($f));
		$f['error'] = 'another unknown status';
		$this->assertTrue(files::uploadStatus($f));
		try {
			$f['error'] = UPLOAD_ERR_INI_SIZE;
			files::uploadStatus($f);
		} catch (exception $e) {
			return;
		}
		$this->fail(__('Expected exception not thrown.'));
	}

	public function test_getDirList() {
		$c = files::getDirList(dirname(__FILE__).'/../');
		$this->assertNotEmpty($c);
		$this->assertCount(2,$c);
		$this->assertNotEmpty($c['dirs']);
		$this->assertNotEmpty($c['files']);
	}

	public function test_tidyFileName() {
		$this->assertTrue(files::tidyFileName('géù=fiel.txt') == 'geu_fiel.txt');
	}
}

// Unit test class
class pathTest extends PHPUnit_Framework_TestCase
{
	// Fixture

	public function setUp() {}
	public function tearDown() {}

	// Unit test methods

	public function test_real() {
		$p = dirname(__FILE__).'/../common/lib.files.Test.php';
		$this->assertTrue(path::real($p) == __FILE__);
		$p .= '-undefined';
		$this->assertFalse(path::real($p));
		// Non strict test
		$this->assertTrue(path::real($p,false) == __FILE__.'-undefined');
	}

	public function test_clean() {
		$p = dirname(__FILE__).'/..///common/lib.files.Test.php///';
		$this->assertTrue(path::clean($p) == dirname(__FILE__).'/common/lib.files.Test.php');
	}

	public function test_info() {
		$i = path::info(__FILE__);
		$this->assertNotEmpty($i);
		$this->assertCount(4,$i);
		$this->assertTrue($i['dirname'] == dirname(__FILE__));
		$this->assertTrue($i['basename'] == 'lib.files.Test.php');
		$this->assertTrue($i['extension'] == 'php');
		$this->assertTrue($i['base'] == 'lib.files.Test');
	}

	public function test_fullFromRoot() {
		$this->assertTrue(path::fullFromRoot(NAME_UNIT_TEST,ROOT_UNIT_TEST) == ROOT_UNIT_TEST.'/'.NAME_UNIT_TEST);
		$this->assertTrue(path::fullFromRoot('/'.NAME_UNIT_TEST,ROOT_UNIT_TEST) == '/'.NAME_UNIT_TEST);
	}
}
?>