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

namespace tests\unit;

require_once __DIR__ . '/../../../bootstrap.php';

require_once CLEARBRICKS_PATH . '/common/lib.l10n.php';
require_once CLEARBRICKS_PATH . '/common/lib.files.php';
require_once CLEARBRICKS_PATH . '/common/lib.text.php';

define('TEST_DIRECTORY', realpath(
    __DIR__ . '/../fixtures/files'
));

use atoum;

/*
 * Test common/lib.files.php
 */
class files extends atoum
{
    protected function cleanTemp()
    {
        // Look for test*, temp*, void* directories and files in TEST_DIRECTORY and destroys them
        $items = \files::scandir(TEST_DIRECTORY);
        if (is_array($items)) {
            foreach ($items as $value) {
                if (in_array(substr($value, 0, 4), ['test', 'temp', 'void'])) {
                    $name = TEST_DIRECTORY . DIRECTORY_SEPARATOR . $value;
                    if (is_dir($name)) {
                        \files::deltree($name);
                    } else {
                        @unlink($name);
                    }
                }
            }
        }
    }
    public function setUp()
    {
        $counter = 0;
        $this->cleanTemp();
        // Check if everything is clean (as OS may have a filesystem cache for dir list)
        while (($items = \files::scandir(TEST_DIRECTORY, true)) !== ['.', '..', '02-two.txt', '1-one.txt', '30-three.txt']) {
            $counter++;
            if ($counter < 10) {
                // Wait 1 second, then clean again
                var_dump($items);
                sleep(1);
                $this->cleanTemp();
            } else {
                // Can't do more then let's go
                break;
            }
        }
    }
    public function tearDown()
    {
        $this->cleanTemp();
    }

    /**
     * Scan a directory. For that we use the /../fixtures/files which contains
     * know files
     */
    public function testScanDir()
    {
        // Normal (sorted)
        $this
            ->array(\files::scandir(TEST_DIRECTORY))
            ->isIdenticalTo(['.', '..', '02-two.txt', '1-one.txt', '30-three.txt']);
        // Not sorted
        $this
            ->array(\files::scandir(TEST_DIRECTORY, false))
            ->containsValues(['.', '..', '1-one.txt', '02-two.txt', '30-three.txt']);

        // DOn't exists
        $this
            ->exception(function () {
                \files::scandir('thisdirectorydontexists');
            });
    }

    /**
     * Test the extension
     */
    public function testExtension()
    {
        $this
            ->string(\files::getExtension('fichier.txt'))
            ->isEqualTo('txt');

        $this
            ->string(\files::getExtension('fichier'))
            ->isEqualTo('');
    }

    /**
     * Test the mime type with two well know mimetype
     * Normally if a file type is unknow it must have a application/octet-stream mimetype
     * javascript files might have an application/x-javascript mimetype regarding
     * W3C spec.
     * See http://en.wikipedia.org/wiki/Internet_media_type for all mimetypes
     */
    public function testGetMimeType()
    {
        $this
            ->string(\files::getMimeType('fichier.txt'))
            ->isEqualTo('text/plain');

        $this
            ->string(\files::getMimeType('fichier.css'))
            ->isEqualTo('text/css');

        $this
            ->string(\files::getMimeType('fichier.js'))
            ->isEqualTo('application/javascript');

        // FIXME: SHould be application/octet-stream (default for unknow)
        // See http://www.rfc-editor.org/rfc/rfc2046.txt section 4.
        // This test don't pass
        $this
            ->string(\files::getMimeType('fichier.dummy'))
            ->isEqualTo('application/octet-stream');
    }

    /**
     * There's a lot of mimetypes. Only test if mimetypes array is not empty
     */
    public function testMimeTypes()
    {
        $this
            ->array(\files::mimeTypes())
            ->isNotEmpty();
    }

    /**
     * Try to register a new mimetype: test/test which don't exists
     */
    public function testRegisterMimeType()
    {
        \files::registerMimeTypes(['text/test']);
        $this
            ->array(\files::mimeTypes())
            ->contains('text/test');
    }

    /**
     * Test if a file is deletable. Under windows every file is deletable
     * TODO: Do it under an Unix/Unix-like system
     */
    public function testFileIsDeletable()
    {
        $tmpname = tempnam(TEST_DIRECTORY, 'testfile_1.txt');
        $file    = fopen($tmpname, 'w+');
        $this
            ->boolean(\files::isDeletable($tmpname))
            ->isTrue();
        fclose($file);
        unlink($tmpname);
    }

    /**
     * Test if a directory is deletable
     * TODO: Do it under Unix/Unix-like system
     */
    public function testDirIsDeletable()
    {
        $dirname = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'testdirectory_2';
        mkdir($dirname);
        $this
            ->boolean(\files::isDeletable($dirname))
            ->isTrue();
        rmdir($dirname);

        // Test with a non existing dir
        $this
            ->boolean(\files::isDeletable($dirname))
            ->isFalse();
    }

    /**
     * Create a directories structure and delete it
     */
    public function testDeltree()
    {
        $dirstructure = join(DIRECTORY_SEPARATOR, [TEST_DIRECTORY, 'temp_3', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, 0700, true);
        touch($dirstructure . DIRECTORY_SEPARATOR . 'file.txt');
        $this
            ->boolean(\files::deltree(join(DIRECTORY_SEPARATOR, [TEST_DIRECTORY, 'temp_3'])))
            ->isTrue();

        $this
            ->boolean(is_dir(TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'temp_3'))
            ->isFalse();
    }

    /**
     * There's a know bug on windows system with filemtime,
     * so this test might fail within this system
     */
    public function testTouch()
    {
        $file_name = tempnam(TEST_DIRECTORY, 'testfile_4.txt');
        $fts       = filemtime($file_name);
        // Must keep at least one second of difference
        sleep(1);
        \files::touch($file_name);
        clearstatcache(); // stats are cached, clear them!
        $sts = filemtime($file_name);
        $this
            ->integer($sts)
            ->isGreaterThan($fts);
        unlink($file_name);
    }

    /**
     * Make a single directory
     */
    public function testMakeDir()
    {
        // Test no parent
        $dirPath = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'testdirectory_5';
        \files::makeDir($dirPath);
        $this
            ->boolean(is_dir($dirPath))
            ->isTrue();
        \files::deltree($dirPath);

        // Test with void name
        $this
            ->variable(\files::makeDir(''))
            ->isNull();

        // test with already existing dir
        $this
            ->variable(\files::makeDir(TEST_DIRECTORY))
            ->isNull();
    }

    /**
     * Make a directory structure
     */
    public function testMakeDirWithParent()
    {
        // Test multitple parent
        $dirPath = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'temp_6/is/a/test/directory/';
        \files::makeDir($dirPath, true);
        $path = '';
        foreach ([TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'temp_6', 'is', 'a', 'test', 'directory'] as $p) {
            $path .= $p . DIRECTORY_SEPARATOR;
            $this->boolean(is_dir($path));
        }
        \files::deltree(TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'temp_6');
    }

    /**
     * Try to create an forbidden directory
     * Under windows try to create a reserved directory
     * Under Unix/Unix-like sytem try to create a directory at root dir
     */
    public function testMakeDirImpossible()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            $dir = 'COM1'; // Windows system forbid that name
        } else {
            $dir = '/dummy'; // On Unix system can't create a directory at root
        }

        $this->exception(function () use ($dir) {
            \files::makeDir($dir);
        });
    }

    public function testInheritChmod()
    {
        $dirName    = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'testdir_7';
        $sonDirName = $dirName . DIRECTORY_SEPARATOR . 'anotherDir';
        mkdir($dirName, 0777);
        mkdir($sonDirName);
        $parentPerms = fileperms($dirName);
        \files::inheritChmod($sonDirName);
        $sonPerms = fileperms($sonDirName);
        $this
            ->boolean($sonPerms === $parentPerms)
            ->isTrue();
        \files::deltree($dirName);

        // Test again witha dir mode set
        \files::$dir_mode = 0770;
        mkdir($dirName, 0777);
        mkdir($sonDirName);
        $parentPerms = fileperms($dirName);
        \files::inheritChmod($sonDirName);
        $sonPerms = fileperms($sonDirName);
        $this
            ->boolean($sonPerms === $parentPerms)
            ->isFalse();
        $this
            ->integer($sonPerms)
            ->isEqualTo(16888); // Aka 0770
        \files::deltree($dirName);
    }

    public function testPutContent()
    {
        $content  = 'A Content';
        $filename = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'testfile_8.txt';
        @unlink($filename);
        \files::putContent($filename, $content);
        $this
            ->string(file_get_contents($filename))
            ->isEqualTo($content);
        unlink($filename);
    }

    public function testPutContentException()
    {
        // Test exceptions
        $content  = 'A Content';
        $filename = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'testfile_9.txt';
        @unlink($filename);
        \files::putContent($filename, $content);
        $this
            ->exception(function () use ($filename) {
                chmod($filename, 0400); // Read only
                \files::putContent($filename, 'unwritable');
            })
            ->hasMessage('File is not writable.');
        chmod($filename, 0700);
        unlink($filename);
    }

    public function testSize()
    {
        $this
            ->string(\files::size(512))
            ->isEqualTo('512 B');

        $this
            ->string(\files::size(1024))
            ->isEqualTo('1 KB');

        $this
            ->string(\files::size(1024 + 1024 + 1))
            ->isEqualTo('2 KB');

        $this
            ->string(\files::size(1024 * 1024))
            ->isEqualTo('1 MB');

        $this
            ->string(\files::size(1024 * 1024 * 1024))
            ->isEqualTo('1 GB');

        $this
            ->string(\files::size(1024 * 1024 * 1024 * 3))
            ->isEqualTo('3 GB');

        $this
            ->string(\files::size(1024 * 1024 * 1024 * 1024))
            ->isEqualTo('1 TB');
    }

    public function testStr2Bytes()
    {
        $this
            ->float(\files::str2bytes('512B'))
            ->isEqualTo((float) 512);

        $this
            ->float(\files::str2bytes('512 B'))
            ->isEqualTo((float) 512);

        $this
            ->float(\files::str2bytes('1k'))
            ->isEqualTo((float) 1024);

        $this
            ->float(\files::str2bytes('1M'))
            ->isEqualTo((float) 1024 * 1024);
        // Max int limit reached, we have a float here
        $this
            ->float(\files::str2bytes('2G'))
            ->isEqualTo((float) 2 * 1024 * 1024 * 1024);
    }

    /**
     * Test uploadStatus
     *
     * This must fail until files::uploadStatus don't handle UPLOAD_ERR_EXTENSION
     */
    public function testUploadStatus()
    {
        // Create a false $_FILES global without error
        $file = [
            'name'     => 'test.jpg',
            'size'     => ini_get('post_max_size'),
            'tmp_name' => 'temptestname.jpg',
            'error'    => UPLOAD_ERR_OK,
            'type'     => 'image/jpeg',
        ];

        $this
            ->boolean(\files::uploadStatus($file))
            ->isTrue();

        // Simulate error
        $file['error'] = UPLOAD_ERR_INI_SIZE;
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_PARTIAL;
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_NO_TMP_DIR; // Since PHP 5.0.3
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_NO_FILE;
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_CANT_WRITE;
        $this->exception(function () use ($file) {\files::uploadStatus($file);});

        // This part might fail
        if (version_compare(phpversion(), '5.2.0', '>')) {
            $file['error'] = UPLOAD_ERR_EXTENSION; // Since PHP 5.2
            $this->exception(function () use ($file) {\files::uploadStatus($file);});
        }
    }

    public function testGetDirList()
    {
        \files::getDirList(TEST_DIRECTORY, $arr);
        $this
            ->array($arr)
            ->isNotEmpty()
            ->hasKeys(['files', 'dirs']);

        $this
            ->array($arr['files'])
            ->isNotEmpty();

        $this
            ->array($arr['dirs'])
            ->isNotEmpty();

        $this
            ->exception(function () {
                \files::getDirList(TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'void', $arr);
            })
            ->hasMessage(sprintf('%s is not a directory.', TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'void'));

        // Deep structure read
        $dirstructure = join(DIRECTORY_SEPARATOR, [TEST_DIRECTORY, 'temp_10', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, 0700, true);
        \files::getDirList(join(DIRECTORY_SEPARATOR, [TEST_DIRECTORY, 'temp_10']), $arr);
        $this
            ->array($arr['dirs'])
            ->isNotEmpty();
        \files::deltree(join(DIRECTORY_SEPARATOR, [TEST_DIRECTORY, 'temp_10']));

        // Unreadable dir
        $dirname = TEST_DIRECTORY . DIRECTORY_SEPARATOR . 'void_11';
        mkdir($dirname);
        $this
            ->exception(function () use ($dirname) {
                chmod($dirname, 0200);
                \files::getDirList($dirname, $arr);
            })
            ->hasMessage('Unable to open directory.');
        chmod($dirname, 0700);
        \files::deltree($dirname);
    }

    public function testTidyFilename()
    {
        $this
            ->string(\files::tidyFileName('a test file.txt'))
            ->isEqualTo('a_test_file.txt');
    }
}

class path extends atoum
{
    public function testRealUnstrict()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \path::real(__DIR__ . '/../fixtures/files', false)))
                ->isEqualTo(TEST_DIRECTORY);
            $this
                ->string(str_replace('/', '\\', \path::real('tests/unit/fixtures/files', false)))
                ->isEqualTo('/tests/unit/fixtures/files');
            $this
                ->string(str_replace('/', '\\', \path::real('tests/./unit/fixtures/files', false)))
                ->isEqualTo('/tests/unit/fixtures/files');
        } else {
            $this
                ->string(\path::real(__DIR__ . '/../fixtures/files', false))
                ->isEqualTo(TEST_DIRECTORY);
            $this
                ->string(\path::real('tests/unit/fixtures/files', false))
                ->isEqualTo('/tests/unit/fixtures/files');
            $this
                ->string(\path::real('tests/./unit/fixtures/files', false))
                ->isEqualTo('/tests/unit/fixtures/files');
        }
    }

    public function testRealStrict()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \path::real(__DIR__ . '/../fixtures/files', true)))
                ->isEqualTo(TEST_DIRECTORY);
        } else {
            $this
                ->string(\path::real(__DIR__ . '/../fixtures/files', true))
                ->isEqualTo(TEST_DIRECTORY);
        }
    }

    public function testClean()
    {
        $this
            ->string(\path::clean('..' . DIRECTORY_SEPARATOR . 'testDirectory'))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory');

        $this
            ->string(\path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory');

        $this
            ->string(\path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory');

        $this
            ->string(\path::clean(DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR . '..'))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory');
    }

    public function testInfo()
    {
        $info = \path::info(TEST_DIRECTORY . DIRECTORY_SEPARATOR . '1-one.txt');
        $this
            ->array($info)
            ->isNotEmpty()
            ->hasKeys(['dirname', 'basename', 'extension', 'base']);

        $this
            ->string($info['dirname'])
            ->isEqualTo(TEST_DIRECTORY);

        $this
            ->string($info['basename'])
            ->isEqualTo('1-one.txt');

        $this
            ->string($info['extension'])
            ->isEqualTo('txt');

        $this
            ->string($info['base'])
            ->string('1-one');
    }

    public function testFullFromRoot()
    {
        $this
            ->string(\path::fullFromRoot('/test', '/'))
            ->isEqualTo('/test');

        $this
            ->string(\path::fullFromRoot('test/string', '/home/sweethome'))
            ->isEqualTo('/home/sweethome/test/string');
    }
}
