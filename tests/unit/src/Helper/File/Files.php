<?php

/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\File;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;
use atoum\atoum\mock\stream;
use Exception;

/**
 * @tags Files
 * @engine isolate
 */
class Files extends atoum
{
    // Atoum VFS stream
    public const ATOUM_STREAM = 'atoum:\\\\';

    // Test folder
    public const TEST_FOLDER = 'dotclear_temp_files';

    // Permissions
    public const WRITE_ONLY                    = 0o200;
    public const READ_ONLY                     = 0o400;
    public const READ_WRITE_EXECUTE            = 0o700;
    public const READ_WRITE_EXECUTE_USER_GROUP = 0o770;
    public const FULL_ACCESS                   = 0o777;

    private string $testDirectory;
    private string $providerDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->providerDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));
        $this->testDirectory     = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::TEST_FOLDER]);

        $this
            ->dump($this->providerDirectory)
            ->dump($this->testDirectory)
        ;
    }

    public function setUp()
    {
        // Create a temporary test folder and copy provided files in it
        if (!is_dir($this->testDirectory)) {
            mkdir($this->testDirectory);
        }
        if (is_dir($this->testDirectory)) {
            // Copy files in it
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '02-two.txt']));
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '1-one.txt']));
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '30-three.txt']));

            sleep(1);
            clearstatcache(true, $this->testDirectory);
        } else {
            throw new Exception(sprintf('Unable to create %s temporary directory', $this->testDirectory));
            exit;
        }
    }

    public function tearDown()
    {
        // Remove the temporary test folder including content
        if (is_dir($this->testDirectory)) {
            \Dotclear\Helper\File\Files::deltree($this->testDirectory);
            sleep(2);
            clearstatcache(true, $this->testDirectory);
        }
    }

    public function getTempDir(): string
    {
        $dir = implode(DIRECTORY_SEPARATOR, [$this->testDirectory, uniqid('atoum_')]);
        mkdir($dir);
        if (!is_dir($dir)) {
            throw new Exception(sprintf('Unable to create temporary directory %s', $dir));
        }

        // Create 3 files in it
        touch(implode(DIRECTORY_SEPARATOR, [$dir, '02-two.txt']));
        touch(implode(DIRECTORY_SEPARATOR, [$dir, '1-one.txt']));
        touch(implode(DIRECTORY_SEPARATOR, [$dir, '30-three.txt']));

        return $dir;
    }

    public function delTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            \Dotclear\Helper\File\Files::deltree($dir);
        }
    }

    public function testLock()
    {
        $dir = $this->getTempDir();

        $this
            ->string(\Dotclear\Helper\File\Files::lock('unknown.file'))
            ->isEqualTo('')
            ->string(\Dotclear\Helper\File\Files::getlastLockError())
            ->isEqualTo('Can\'t create file')
        ;

        $this
            ->string(\Dotclear\Helper\File\Files::lock($dir))
            ->isEqualTo('')
            ->string(\Dotclear\Helper\File\Files::getlastLockError())
            ->isEqualTo('Can\'t lock a directory')
        ;

        $file = implode(DIRECTORY_SEPARATOR, [$dir, '1-one.txt']);

        $this
            ->string(\Dotclear\Helper\File\Files::lock($file))
            ->isEqualTo($file)
            ->variable(\Dotclear\Helper\File\Files::lock($file))
            ->isEqualTo(null)
            ->given(\Dotclear\Helper\File\Files::unlock($file))
            ->then()
            ->string(\Dotclear\Helper\File\Files::lock($file))
            ->isEqualTo($file)
        ;

        $file_lock = implode(DIRECTORY_SEPARATOR, [$this->providerDirectory, 'watchdog.lock']);
        if (file_exists($file_lock)) {
            unlink($file_lock);
        }

        $lock = \Dotclear\Helper\File\Files::lock($file_lock, true);

        $this
            ->string($lock)
            ->isEqualTo($file_lock)
            ->boolean(file_exists($file_lock))
            ->isTrue()
            ->given(\Dotclear\Helper\File\Files::unlock($lock))
        ;

        sleep(2);
        clearstatcache(true, $lock);

        $this
            ->boolean(file_exists($lock))
            ->isFalse()
        ;

        $this->delTempDir($dir);
    }

    /**
     * Scan a directory. For that we use the /../fixtures/files which contains
     * know files
     */
    public function testScanDir()
    {
        $dir = $this->getTempDir();

        // Normal (sorted)
        $this
            ->array(\Dotclear\Helper\File\Files::scandir($dir))
            ->isIdenticalTo(['.', '..', '02-two.txt', '1-one.txt', '30-three.txt'])
        ;

        // Not sorted
        $this
            ->array(\Dotclear\Helper\File\Files::scandir($dir, false))
            ->containsValues(['.', '..', '1-one.txt', '02-two.txt', '30-three.txt'])
        ;

        // Don't exists
        $this
            ->exception(function () {
                \Dotclear\Helper\File\Files::scandir('thisdirectorydontexists');
            })
        ;

        $this->delTempDir($dir);
    }

    /**
     * Test the extension
     */
    public function testExtension()
    {
        $this
            ->string(\Dotclear\Helper\File\Files::getExtension('fichier.txt'))
            ->isEqualTo('txt')
            ->string(\Dotclear\Helper\File\Files::getExtension('fichier'))
            ->isEqualTo('')
        ;
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
            ->string(\Dotclear\Helper\File\Files::getMimeType('fichier.txt'))
            ->isEqualTo('text/plain')
            ->string(\Dotclear\Helper\File\Files::getMimeType('fichier.css'))
            ->isEqualTo('text/css')
            ->string(\Dotclear\Helper\File\Files::getMimeType('fichier.js'))
            ->isEqualTo('text/javascript')
        ;

        $this
            ->string(\Dotclear\Helper\File\Files::getMimeType('fichier.dummy'))
            ->isEqualTo('application/octet-stream')
        ;
    }

    /**
     * There's a lot of mimetypes. Only test if mimetypes array is not empty
     */
    public function testMimeTypes()
    {
        $this
            ->array(\Dotclear\Helper\File\Files::mimeTypes())
            ->isNotEmpty()
        ;
    }

    /**
     * Try to register a new mimetype: test/test which don't exists
     */
    public function testRegisterMimeType()
    {
        \Dotclear\Helper\File\Files::registerMimeTypes(['text/test']);

        $this
            ->array(\Dotclear\Helper\File\Files::mimeTypes())
            ->contains('text/test')
        ;
    }

    /**
     * Test if a file is deletable. Under windows every file is deletable
     * TODO: Do it under an Unix/Unix-like system
     */
    public function testFileIsDeletable()
    {
        $dir = $this->getTempDir();

        $tmpname = tempnam($dir, 'testFileIsDeletable');
        $file    = fopen($tmpname, 'w+');

        $this
            ->boolean(\Dotclear\Helper\File\Files::isDeletable($tmpname))
            ->isTrue()
        ;

        fclose($file);

        $this->delTempDir($dir);
    }

    /**
     * Test if a directory is deletable
     */
    public function testDirIsDeletable()
    {
        $dir = $this->getTempDir();

        $dirname = $dir . DIRECTORY_SEPARATOR . 'testDirIsDeletable';
        mkdir($dirname, self::READ_WRITE_EXECUTE_USER_GROUP);

        sleep(2);
        clearstatcache(true, $dirname);

        $ret = \Dotclear\Helper\File\Files::isDeletable($dirname);

        //        $dirname   = 'testDirIsDeletable';
        //        $directory = stream::get($dirname);
        //        $directory->mkdir($dirname, self::READ_WRITE_EXECUTE_USER_GROUP);

        //        $ret = \Dotclear\Helper\File\Files::isDeletable(self::ATOUM_STREAM . $dirname);

        $this
            ->boolean($ret)
            ->isTrue()
        ;

        $this->delTempDir($dir);
    }

    /**
     * Test if a directory is deletable
     * TODO: Do it under Unix/Unix-like system
     */
    public function testDirIsNotDeletable()
    {
        $dir = $this->getTempDir();

        $dirname = $dir . DIRECTORY_SEPARATOR . 'testDirIsNotDeletable';

        // Test with a non existing dir
        $this
            ->boolean(\Dotclear\Helper\File\Files::isDeletable($dirname))
            ->isFalse()
        ;

        $this->delTempDir($dir);
    }

    /**
     * Create a directories structure and delete it
     */
    public function testDeltree()
    {
        $dir = $this->getTempDir();

        $dirstructure = implode(DIRECTORY_SEPARATOR, [$dir, 'testDeltree', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        touch($dirstructure . DIRECTORY_SEPARATOR . 'file.txt');
        $del = \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$dir, 'testDeltree']));

        sleep(1);
        clearstatcache(true, $dirstructure);

        $this
            ->boolean($del)
            ->isTrue()
            ->boolean(is_dir($this->testDirectory . DIRECTORY_SEPARATOR . 'testDeltree'))
            ->isFalse()
        ;

        $this->delTempDir($dir);
    }

    /**
     * There's a know bug on windows system with filemtime,
     * so this test might fail within this system
     */
    public function testTouch()
    {
        $dir = $this->getTempDir();

        $file_name = tempnam($dir, 'testTouch');
        $fts       = filemtime($file_name);

        // Must keep at least one second of difference
        sleep(3);
        clearstatcache(true, $file_name);

        \Dotclear\Helper\File\Files::touch($file_name);

        sleep(2);
        clearstatcache();

        $sts = filemtime($file_name);

        $this
            ->integer($sts)
            ->isGreaterThan($fts)
        ;

        $this->delTempDir($dir);
    }

    /**
     * Make a single directory
     */
    public function testMakeDir()
    {
        $dir = $this->getTempDir();

        // Test no parent
        $dirPath = $dir . DIRECTORY_SEPARATOR . 'testMakeDir';
        \Dotclear\Helper\File\Files::makeDir($dirPath);

        sleep(2);
        clearstatcache(true, $dirPath);

        $this
            ->boolean(is_dir($dirPath))
            ->isTrue()
        ;

        \Dotclear\Helper\File\Files::deltree($dirPath);

        sleep(2);
        clearstatcache(true, $dirPath);

        // Test with void name
        $this
            ->variable(\Dotclear\Helper\File\Files::makeDir(''))
            ->isNull()
        ;

        // test with already existing dir
        $this
            ->variable(\Dotclear\Helper\File\Files::makeDir($dir))
            ->isNull()
        ;

        $this->delTempDir($dir);
    }

    /**
     * Make a directory structure
     */
    public function testMakeDirWithParent()
    {
        $dir = $this->getTempDir();

        // Test multitple parent
        $dirPath = $dir . DIRECTORY_SEPARATOR . 'testMakeDirWithParent/is/a/test/directory/';
        \Dotclear\Helper\File\Files::makeDir($dirPath, true);

        sleep(2);
        clearstatcache(true, $dirPath);

        $path = '';
        foreach ([$dir . DIRECTORY_SEPARATOR . 'testMakeDirWithParent', 'is', 'a', 'test', 'directory'] as $p) {
            $path .= $p . DIRECTORY_SEPARATOR;
            $this->
                boolean(is_dir($path))
            ;
        }

        $this->delTempDir($dir);
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
            \Dotclear\Helper\File\Files::makeDir($dir);
        });
    }

    public function testInheritChmod()
    {
        $dir = $this->getTempDir();

        $dirName    = $dir . DIRECTORY_SEPARATOR . 'testInheritChmod';
        $sonDirName = $dirName . DIRECTORY_SEPARATOR . 'anotherDir';

        mkdir($dirName, self::FULL_ACCESS);
        mkdir($sonDirName);
        $parentPerms = fileperms($dirName);
        \Dotclear\Helper\File\Files::inheritChmod($sonDirName);
        $sonPerms = fileperms($sonDirName);

        $this
            ->boolean($sonPerms === $parentPerms)
            ->isTrue()
        ;

        $this->delTempDir($dir);
    }

    public function testInheritChmodDirMode()
    {
        $dir = $this->getTempDir();

        $dirName    = $dir . DIRECTORY_SEPARATOR . 'testInheritChmodDirMode';
        $sonDirName = $dirName . DIRECTORY_SEPARATOR . 'anotherDir';

        \Dotclear\Helper\File\Files::$dir_mode = self::READ_WRITE_EXECUTE_USER_GROUP;
        mkdir($dirName, self::FULL_ACCESS);
        mkdir($sonDirName);
        $parentPerms = fileperms($dirName);
        \Dotclear\Helper\File\Files::inheritChmod($sonDirName);
        $sonPerms = fileperms($sonDirName);

        $this
            ->boolean($sonPerms === $parentPerms)
            ->isFalse()
            ->integer($sonPerms)
            ->isEqualTo(16888) // Aka self::READ_WRITE_EXECUTE_USER_GROUP
        ;

        $this->delTempDir($dir);
    }

    public function testPutContent()
    {
        $dir = $this->getTempDir();

        $content  = 'A Content';
        $filename = $dir . DIRECTORY_SEPARATOR . 'testPutContent.txt';
        \Dotclear\Helper\File\Files::putContent($filename, $content);

        sleep(2);
        clearstatcache(true, $filename);

        $this
            ->string(file_get_contents($filename))
            ->isEqualTo($content)
        ;

        $this->delTempDir($dir);
    }

    public function testPutContentException()
    {
        $dir = $this->getTempDir();

        // Test exceptions
        $content  = 'A Content';
        $filename = $dir . DIRECTORY_SEPARATOR . 'testPutContentException.txt';
        @unlink($filename);
        \Dotclear\Helper\File\Files::putContent($filename, $content);

        sleep(2);
        clearstatcache(true, $filename);

        $this
            ->exception(function () use ($filename) {
                chmod($filename, self::READ_ONLY); // Read only

                sleep(2);
                clearstatcache(true, $filename);

                \Dotclear\Helper\File\Files::putContent($filename, 'unwritable');
            })
            ->hasMessage('File is not writable.')
        ;

        chmod($filename, self::READ_WRITE_EXECUTE);
        unlink($filename);

        $this->delTempDir($dir);
    }

    public function testSize()
    {
        $this
            ->string(\Dotclear\Helper\File\Files::size(512))
            ->isEqualTo('512 B')
            ->string(\Dotclear\Helper\File\Files::size(1024))
            ->isEqualTo('1 KB')
            ->string(\Dotclear\Helper\File\Files::size(1024 + 1024 + 1))
            ->isEqualTo('2 KB')
            ->string(\Dotclear\Helper\File\Files::size(1024 * 1024))
            ->isEqualTo('1 MB')
            ->string(\Dotclear\Helper\File\Files::size(1024 * 1024 * 1024))
            ->isEqualTo('1 GB')
            ->string(\Dotclear\Helper\File\Files::size(1024 * 1024 * 1024 * 3))
            ->isEqualTo('3 GB')
            ->string(\Dotclear\Helper\File\Files::size(1024 * 1024 * 1024 * 1024))
            ->isEqualTo('1 TB')
        ;
    }

    public function testStr2Bytes()
    {
        $this
            ->float(\Dotclear\Helper\File\Files::str2bytes('512B'))
            ->isEqualTo((float) 512)
            ->float(\Dotclear\Helper\File\Files::str2bytes('512 B'))
            ->isEqualTo((float) 512)
            ->float(\Dotclear\Helper\File\Files::str2bytes('1k'))
            ->isEqualTo((float) 1024)
            ->float(\Dotclear\Helper\File\Files::str2bytes('1M'))
            ->isEqualTo((float) 1024 * 1024)
            ->float(\Dotclear\Helper\File\Files::str2bytes('2G'))
            ->isEqualTo((float) 2 * 1024 * 1024 * 1024)
        ;
    }

    /**
     * Test uploadStatus
     *
     * This must fail until Files::uploadStatus don't handle UPLOAD_ERR_EXTENSION
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
            ->boolean(\Dotclear\Helper\File\Files::uploadStatus($file))
            ->isTrue();

        // Simulate error
        $file['error'] = UPLOAD_ERR_INI_SIZE;
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_PARTIAL;
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_NO_TMP_DIR; // Since PHP 5.0.3
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_NO_FILE;
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        $file['error'] = UPLOAD_ERR_CANT_WRITE;
        $this->exception(function () use ($file) {\Dotclear\Helper\File\Files::uploadStatus($file);});

        // This part might fail
        if (version_compare(phpversion(), '5.2.0', '>')) {
            $file['error'] = UPLOAD_ERR_EXTENSION; // Since PHP 5.2
            $this->exception(function () use ($file) {
                \Dotclear\Helper\File\Files::uploadStatus($file);
            });
        }
    }

    public function testGetDirList()
    {
        $dir = $this->getTempDir();

        $arr = [];
        \Dotclear\Helper\File\Files::getDirList($dir, $arr);

        $this
            ->array($arr)
            ->isNotEmpty()
            ->hasKeys(['files', 'dirs'])
            ->array($arr['files'])
            ->isNotEmpty()
            ->array($arr['dirs'])
            ->isNotEmpty()
        ;

        $this
            ->exception(function () use ($dir) {
                \Dotclear\Helper\File\Files::getDirList($dir . DIRECTORY_SEPARATOR . 'void', $arr);
            })
            ->hasMessage(sprintf('%s is not a directory.', $dir . DIRECTORY_SEPARATOR . 'void'));

        // Deep structure read
        $dirstructure = join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        \Dotclear\Helper\File\Files::getDirList(join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList']), $arr);

        $this
            ->array($arr['dirs'])
            ->isNotEmpty()
        ;

        \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList']));

        // Unreadable dir
        $dirname = $dir . DIRECTORY_SEPARATOR . 'testGetDirListVoid';
        mkdir($dirname);

        $this
            ->exception(function () use ($dirname) {
                chmod($dirname, self::WRITE_ONLY);
                \Dotclear\Helper\File\Files::getDirList($dirname, $arr);
            })
            ->hasMessage('Unable to open directory.')
        ;

        chmod($dirname, self::READ_WRITE_EXECUTE);

        $this->delTempDir($dir);
    }

    public function testTidyFilename()
    {
        $this
            ->string(\Dotclear\Helper\File\Files::tidyFileName('a test file.txt'))
            ->isEqualTo('a_test_file.txt')
        ;
    }
}
