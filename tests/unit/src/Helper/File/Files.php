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
    public const WRITE_ONLY                    = 0200;
    public const READ_ONLY                     = 0400;
    public const READ_WRITE_EXECUTE            = 0700;
    public const READ_WRITE_EXECUTE_USER_GROUP = 0770;
    public const FULL_ACCESS                   = 0777;

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
            @mkdir($this->testDirectory);
        }
        if (is_dir($this->testDirectory)) {
            // Copy files in it
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '02-two.txt']));
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '1-one.txt']));
            touch(implode(DIRECTORY_SEPARATOR, [$this->testDirectory, '30-three.txt']));

            sleep(1);
            clearstatcache(true, $this->testDirectory);
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

    protected function cleanTemp()
    {
        if (!$this->testDirectory) {
            $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));
        }

        // Look for test*, temp*, void* directories and files in $this->testDirectory and destroys them
        $items = \Dotclear\Helper\File\Files::scandir($this->testDirectory);
        if (is_array($items)) {
            foreach ($items as $value) {
                if (in_array(substr($value, 0, 4), ['test', 'temp', 'void'])) {
                    $name = $this->testDirectory . DIRECTORY_SEPARATOR . $value;
                    if (is_dir($name)) {
                        \Dotclear\Helper\File\Files::deltree($name);
                    } else {
                        @unlink($name);
                    }
                }
            }
        }
        sleep(1);
        clearstatcache(true, $this->testDirectory);
    }

    public function beforeTestMethod(string $method)
    {
        $counter = 0;
        $this->cleanTemp();
        // Check if everything is clean (as OS may have a filesystem cache for dir list)
        while (($items = \Dotclear\Helper\File\Files::scandir($this->testDirectory, true)) !== ['.', '..', '02-two.txt', '1-one.txt', '30-three.txt']) {
            $counter++;
            if ($counter < 10) {
                // Wait 1 second, then clean again
                // var_dump($items);
                sleep(1);
                clearstatcache(true, $this->testDirectory);
                $this->cleanTemp();
            } else {
                // Can't do more then let's go
                break;
            }
        }
    }

    public function afterTestMethod(string $method)
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
            ->array(\Dotclear\Helper\File\Files::scandir($this->testDirectory))
            ->isIdenticalTo(['.', '..', '02-two.txt', '1-one.txt', '30-three.txt'])
        ;

        // Not sorted
        $this
            ->array(\Dotclear\Helper\File\Files::scandir($this->testDirectory, false))
            ->containsValues(['.', '..', '1-one.txt', '02-two.txt', '30-three.txt'])
        ;

        // Don't exists
        $this
            ->exception(function () {
                \Dotclear\Helper\File\Files::scandir('thisdirectorydontexists');
            })
        ;
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
        $tmpname = tempnam($this->testDirectory, 'testFileIsDeletable');
        $file    = fopen($tmpname, 'w+');

        $this
            ->boolean(\Dotclear\Helper\File\Files::isDeletable($tmpname))
            ->isTrue()
        ;

        fclose($file);

        sleep(1);
        clearstatcache(true, $tmpname);
    }

    /**
     * Test if a directory is deletable
     */
    public function testDirIsDeletable()
    {
        $dirname = $this->testDirectory . DIRECTORY_SEPARATOR . 'testDirIsDeletable';
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
    }

    /**
     * Test if a directory is deletable
     * TODO: Do it under Unix/Unix-like system
     */
    public function testDirIsNotDeletable()
    {
        $dirname = $this->testDirectory . DIRECTORY_SEPARATOR . 'testDirIsNotDeletable';

        // Test with a non existing dir
        $this
            ->boolean(\Dotclear\Helper\File\Files::isDeletable($dirname))
            ->isFalse()
        ;
    }

    /**
     * Create a directories structure and delete it
     */
    public function testDeltree()
    {
        $dirstructure = implode(DIRECTORY_SEPARATOR, [$this->testDirectory, 'testDeltree', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        touch($dirstructure . DIRECTORY_SEPARATOR . 'file.txt');
        $del = \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$this->testDirectory, 'testDeltree']));

        sleep(1);
        clearstatcache(true, $dirstructure);

        $this
            ->boolean($del)
            ->isTrue()
            ->boolean(is_dir($this->testDirectory . DIRECTORY_SEPARATOR . 'testDeltree'))
            ->isFalse()
        ;
    }

    /**
     * There's a know bug on windows system with filemtime,
     * so this test might fail within this system
     */
    public function testTouch()
    {
        $file_name = tempnam($this->testDirectory, 'testTouch');
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

        unlink($file_name);
    }

    /**
     * Make a single directory
     */
    public function testMakeDir()
    {
        // Test no parent
        $dirPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'testMakeDir';
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
            ->variable(\Dotclear\Helper\File\Files::makeDir($this->testDirectory))
            ->isNull()
        ;
    }

    /**
     * Make a directory structure
     */
    public function testMakeDirWithParent()
    {
        // Test multitple parent
        $dirPath = $this->testDirectory . DIRECTORY_SEPARATOR . 'testMakeDirWithParent/is/a/test/directory/';
        \Dotclear\Helper\File\Files::makeDir($dirPath, true);

        sleep(2);
        clearstatcache(true, $dirPath);

        $path = '';
        foreach ([$this->testDirectory . DIRECTORY_SEPARATOR . 'testMakeDirWithParent', 'is', 'a', 'test', 'directory'] as $p) {
            $path .= $p . DIRECTORY_SEPARATOR;
            $this->
                boolean(is_dir($path))
            ;
        }
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
        $dirName    = $this->testDirectory . DIRECTORY_SEPARATOR . 'testInheritChmod';
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
    }

    public function testInheritChmodDirMode()
    {
        $dirName    = $this->testDirectory . DIRECTORY_SEPARATOR . 'testInheritChmodDirMode';
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
    }

    public function testPutContent()
    {
        $content  = 'A Content';
        $filename = $this->testDirectory . DIRECTORY_SEPARATOR . 'testPutContent.txt';
        \Dotclear\Helper\File\Files::putContent($filename, $content);

        sleep(2);
        clearstatcache(true, $filename);

        $this
            ->string(file_get_contents($filename))
            ->isEqualTo($content)
        ;
    }

    public function testPutContentException()
    {
        // Test exceptions
        $content  = 'A Content';
        $filename = $this->testDirectory . DIRECTORY_SEPARATOR . 'testPutContentException.txt';
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
        \Dotclear\Helper\File\Files::getDirList($this->testDirectory, $arr);

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
            ->exception(function () {
                \Dotclear\Helper\File\Files::getDirList($this->testDirectory . DIRECTORY_SEPARATOR . 'void', $arr);
            })
            ->hasMessage(sprintf('%s is not a directory.', $this->testDirectory . DIRECTORY_SEPARATOR . 'void'));

        // Deep structure read
        $dirstructure = join(DIRECTORY_SEPARATOR, [$this->testDirectory, 'testGetDirList', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        \Dotclear\Helper\File\Files::getDirList(join(DIRECTORY_SEPARATOR, [$this->testDirectory, 'testGetDirList']), $arr);

        $this
            ->array($arr['dirs'])
            ->isNotEmpty()
        ;

        \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$this->testDirectory, 'testGetDirList']));

        // Unreadable dir
        $dirname = $this->testDirectory . DIRECTORY_SEPARATOR . 'testGetDirListVoid';
        mkdir($dirname);

        $this
            ->exception(function () use ($dirname) {
                chmod($dirname, self::WRITE_ONLY);
                \Dotclear\Helper\File\Files::getDirList($dirname, $arr);
            })
            ->hasMessage('Unable to open directory.')
        ;

        chmod($dirname, self::READ_WRITE_EXECUTE);
    }

    public function testTidyFilename()
    {
        $this
            ->string(\Dotclear\Helper\File\Files::tidyFileName('a test file.txt'))
            ->isEqualTo('a_test_file.txt')
        ;
    }
}
