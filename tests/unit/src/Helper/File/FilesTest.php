<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File;

use Exception;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
class FilesTest extends TestCase
{
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

    protected function setUp(): void
    {
        $this->providerDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));
        $this->testDirectory     = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::TEST_FOLDER]);

        // Create a temporary test folder and copy provided files in it
        if (!is_dir($this->testDirectory)) {
            mkdir($this->testDirectory);
        }
        if (!is_dir($this->testDirectory)) {
            throw new Exception(sprintf('Unable to create %s temporary directory', $this->testDirectory));
            exit;
        }
    }

    protected function tearDown(): void
    {
        // Remove the temporary test folder including content
        if (is_dir($this->testDirectory)) {
            \Dotclear\Helper\File\Files::deltree($this->testDirectory);
            sleep(2);
            clearstatcache(true, $this->testDirectory);
        }
    }

    protected function getTempDir(): string
    {
        $dir = implode(DIRECTORY_SEPARATOR, [$this->testDirectory, uniqid('phpunit_')]);
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

    protected function delTempDir(string $dir): void
    {
        if (is_dir($dir)) {
            \Dotclear\Helper\File\Files::deltree($dir);
        }
    }

    public function test()
    {
        $this->assertTrue(
            is_dir($this->testDirectory)
        );
    }

    #[Depends('test')]
    public function testLock()
    {
        $dir = $this->getTempDir();

        $this->assertEquals(
            '',
            \Dotclear\Helper\File\Files::lock('unknown.file')
        );
        $this->assertEquals(
            'Can\'t create file',
            \Dotclear\Helper\File\Files::getlastLockError()
        );

        $this->assertEquals(
            '',
            \Dotclear\Helper\File\Files::lock($dir)
        );
        $this->assertEquals(
            'Can\'t lock a directory',
            \Dotclear\Helper\File\Files::getlastLockError()
        );

        $file = implode(DIRECTORY_SEPARATOR, [$dir, '1-one.txt']);

        $this->assertEquals(
            $file,
            \Dotclear\Helper\File\Files::lock($file)
        );
        $this->assertNull(
            \Dotclear\Helper\File\Files::lock($file)
        );

        \Dotclear\Helper\File\Files::unlock($file);

        $this->assertEquals(
            $file,
            \Dotclear\Helper\File\Files::lock($file)
        );

        $file_lock = implode(DIRECTORY_SEPARATOR, [$this->providerDirectory, 'watchdog.lock']);
        if (file_exists($file_lock)) {
            unlink($file_lock);
        }

        $lock = \Dotclear\Helper\File\Files::lock($file_lock, true);

        $this->assertEquals(
            $file_lock,
            $lock
        );
        $this->assertTrue(
            file_exists($file_lock)
        );

        \Dotclear\Helper\File\Files::unlock($lock);

        sleep(2);
        clearstatcache(true, $lock);

        $this->assertFalse(
            file_exists($lock)
        );

        $this->delTempDir($dir);
    }

    /**
     * Scan a directory. For that we use the /../fixtures/files which contains
     * know files
     */
    #[Depends('testLock')]
    public function testScanDir()
    {
        $dir = $this->getTempDir();

        // Normal (sorted)
        $this->assertEquals(
            ['.', '..', '02-two.txt', '1-one.txt', '30-three.txt'],
            \Dotclear\Helper\File\Files::scandir($dir)
        );

        // Not sorted
        $this->assertEquals(
            ['.', '..', '02-two.txt', '30-three.txt', '1-one.txt'],
            \Dotclear\Helper\File\Files::scandir($dir, false)
        );

        // Don't exists
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::scandir('thisdirectorydontexists');

        $this->delTempDir($dir);
    }

    /**
     * Test the extension
     */
    #[Depends('testScanDir')]
    public function testExtension()
    {
        $this->assertEquals(
            'txt',
            \Dotclear\Helper\File\Files::getExtension('fichier.txt')
        );
        $this->assertEquals(
            '',
            \Dotclear\Helper\File\Files::getExtension('fichier')
        );
    }

    /**
     * Test the mime type with two well know mimetype
     * Normally if a file type is unknow it must have a application/octet-stream mimetype
     * javascript files might have an application/x-javascript mimetype regarding
     * W3C spec.
     * See http://en.wikipedia.org/wiki/Internet_media_type for all mimetypes
     */
    #[Depends('testExtension')]
    public function testGetMimeType()
    {
        $this->assertEquals(
            'text/plain',
            \Dotclear\Helper\File\Files::getMimeType('fichier.txt')
        );
        $this->assertEquals(
            'text/css',
            \Dotclear\Helper\File\Files::getMimeType('fichier.css')
        );
        $this->assertEquals(
            'text/javascript',
            \Dotclear\Helper\File\Files::getMimeType('fichier.js')
        );

        $this->assertEquals(
            'application/octet-stream',
            \Dotclear\Helper\File\Files::getMimeType('fichier.dummy')
        );
    }

    /**
     * There's a lot of mimetypes. Only test if mimetypes array is not empty
     */
    #[Depends('testGetMimeType')]
    public function testMimeTypes()
    {
        $this->assertNotEmpty(
            \Dotclear\Helper\File\Files::mimeTypes()
        );
    }

    /**
     * Try to register a new mimetype: test/test which don't exists
     */
    #[Depends('testMimeTypes')]
    public function testRegisterMimeType()
    {
        \Dotclear\Helper\File\Files::registerMimeTypes(['text/test']);

        $this->assertContains(
            'text/test',
            \Dotclear\Helper\File\Files::mimeTypes()
        );
    }

    /**
     * Test if a file is deletable. Under windows every file is deletable
     * TODO: Do it under an Unix/Unix-like system
     */
    #[Depends('testRegisterMimeType')]
    public function testFileIsDeletable()
    {
        $dir = $this->getTempDir();

        $tmpname = tempnam($dir, 'testFileIsDeletable');
        $file    = fopen($tmpname, 'w+');

        $this->assertTrue(
            \Dotclear\Helper\File\Files::isDeletable($tmpname)
        );

        fclose($file);

        $this->delTempDir($dir);
    }

    /**
     * Test if a directory is deletable
     */
    #[Depends('testFileIsDeletable')]
    public function testDirIsDeletable()
    {
        $dir = $this->getTempDir();

        $dirname = $dir . DIRECTORY_SEPARATOR . 'testDirIsDeletable';
        mkdir($dirname, self::READ_WRITE_EXECUTE_USER_GROUP);

        $ret = \Dotclear\Helper\File\Files::isDeletable($dirname);

        $this->assertTrue(
            $ret
        );

        $this->delTempDir($dir);
    }

    /**
     * Test if a directory is deletable
     * TODO: Do it under Unix/Unix-like system
     */
    #[Depends('testDirIsDeletable')]
    public function testDirIsNotDeletable()
    {
        $dir = $this->getTempDir();

        $dirname = $dir . DIRECTORY_SEPARATOR . 'testDirIsNotDeletable';

        // Test with a non existing dir
        $this->assertFalse(
            \Dotclear\Helper\File\Files::isDeletable($dirname)
        );

        $this->delTempDir($dir);
    }

    /**
     * Create a directories structure and delete it
     */
    #[Depends('testDirIsNotDeletable')]
    public function testDeltree()
    {
        $dir = $this->getTempDir();

        $dirstructure = implode(DIRECTORY_SEPARATOR, [$dir, 'testDeltree', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        touch($dirstructure . DIRECTORY_SEPARATOR . 'file.txt');
        $del = \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$dir, 'testDeltree']));

        $this->assertTrue(
            $del
        );
        $this->assertFalse(
            is_dir($this->testDirectory . DIRECTORY_SEPARATOR . 'testDeltree')
        );

        $this->delTempDir($dir);
    }

    /**
     * There's a know bug on windows system with filemtime,
     * so this test might fail within this system
     */
    #[Depends('testDeltree')]
    public function testTouch()
    {
        $dir = $this->getTempDir();

        $file_name = tempnam($dir, 'testTouch');
        $fts       = filemtime($file_name);

        // Must keep at least one second of difference
        sleep(1);
        clearstatcache(true, $file_name);

        \Dotclear\Helper\File\Files::touch($file_name);

        sleep(1);
        clearstatcache();

        $sts = filemtime($file_name);

        $this->assertGreaterThan(
            $fts,
            $sts
        );

        $this->delTempDir($dir);
    }

    /**
     * Make a single directory
     */
    #[Depends('testTouch')]
    public function testMakeDir()
    {
        $dir = $this->getTempDir();

        // Test no parent
        $dirPath = $dir . DIRECTORY_SEPARATOR . 'testMakeDir';
        \Dotclear\Helper\File\Files::makeDir($dirPath);

        $this->assertTrue(
            is_dir($dirPath)
        );

        \Dotclear\Helper\File\Files::deltree($dirPath);

        // Test with void name
        $this->assertNull(
            \Dotclear\Helper\File\Files::makeDir('')
        );

        // test with already existing dir
        $this->assertNull(
            \Dotclear\Helper\File\Files::makeDir($dir)
        );

        $this->delTempDir($dir);
    }

    /**
     * Make a directory structure
     */
    #[Depends('testMakeDir')]
    public function testMakeDirWithParent()
    {
        $dir = $this->getTempDir();

        // Test multitple parent
        $dirPath = $dir . DIRECTORY_SEPARATOR . 'testMakeDirWithParent/is/a/test/directory/';
        \Dotclear\Helper\File\Files::makeDir($dirPath, true);

        $path = '';
        foreach ([$dir . DIRECTORY_SEPARATOR . 'testMakeDirWithParent', 'is', 'a', 'test', 'directory'] as $p) {
            $path .= $p . DIRECTORY_SEPARATOR;
            $this->assertTrue(
                is_dir($path)
            );
        }

        $this->delTempDir($dir);
    }

    /**
     * Try to create an forbidden directory
     * Under windows try to create a reserved directory
     * Under Unix/Unix-like sytem try to create a directory at root dir
     */
    #[Depends('testMakeDirWithParent')]
    public function testMakeDirImpossible()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            $dir = 'COM1'; // Windows system forbid that name
        } else {
            $dir = '/dummy'; // On Unix system can't create a directory at root
        }

        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::makeDir($dir);
    }

    #[Depends('testMakeDirImpossible')]
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

        $this->assertTrue(
            $sonPerms === $parentPerms
        );

        $this->delTempDir($dir);
    }

    #[Depends('testInheritChmod')]
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

        $this->assertFalse(
            $sonPerms === $parentPerms
        );
        $this->assertEquals(
            16888,  // Aka self::READ_WRITE_EXECUTE_USER_GROUP
            $sonPerms
        );

        $this->delTempDir($dir);
    }

    #[Depends('testInheritChmodDirMode')]
    public function testPutContent()
    {
        $dir = $this->getTempDir();

        $content  = 'A Content';
        $filename = $dir . DIRECTORY_SEPARATOR . 'testPutContent.txt';
        \Dotclear\Helper\File\Files::putContent($filename, $content);

        $this->assertEquals(
            $content,
            file_get_contents($filename)
        );

        $this->delTempDir($dir);
    }

    #[Depends('testPutContent')]
    public function testPutContentException()
    {
        $dir = $this->getTempDir();

        // Test exceptions
        $content  = 'A Content';
        $filename = $dir . DIRECTORY_SEPARATOR . 'testPutContentException.txt';
        @unlink($filename);
        \Dotclear\Helper\File\Files::putContent($filename, $content);

        chmod($filename, self::READ_ONLY); // Read only

        sleep(2);
        clearstatcache(true, $filename);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File is not writable.');

        \Dotclear\Helper\File\Files::putContent($filename, 'unwritable');

        chmod($filename, self::READ_WRITE_EXECUTE);

        $this->delTempDir($dir);
    }

    #[Depends('testPutContentException')]
    public function testSize()
    {
        $this->assertEquals(
            '512 B',
            \Dotclear\Helper\File\Files::size(512)
        );
        $this->assertEquals(
            '1 KB',
            \Dotclear\Helper\File\Files::size(1024)
        );
        $this->assertEquals(
            '2 KB',
            \Dotclear\Helper\File\Files::size(1024 + 1024 + 1)
        );
        $this->assertEquals(
            '1 MB',
            \Dotclear\Helper\File\Files::size(1024 * 1024)
        );
        $this->assertEquals(
            '1 GB',
            \Dotclear\Helper\File\Files::size(1024 * 1024 * 1024)
        );
        $this->assertEquals(
            '3 GB',
            \Dotclear\Helper\File\Files::size(1024 * 1024 * 1024 * 3)
        );
        $this->assertEquals(
            '1 TB',
            \Dotclear\Helper\File\Files::size(1024 * 1024 * 1024 * 1024)
        );
    }

    #[Depends('testSize')]
    public function testStr2Bytes()
    {
        $this->assertEquals(
            (float) 512,
            \Dotclear\Helper\File\Files::str2bytes('512B')
        );
        $this->assertEquals(
            (float) 512,
            \Dotclear\Helper\File\Files::str2bytes('512 B')
        );
        $this->assertEquals(
            (float) 1024,
            \Dotclear\Helper\File\Files::str2bytes('1k')
        );
        $this->assertEquals(
            (float) 1024 * 1024,
            \Dotclear\Helper\File\Files::str2bytes('1M')
        );
        $this->assertEquals(
            (float) 2 * 1024 * 1024 * 1024,
            \Dotclear\Helper\File\Files::str2bytes('2G')
        );
    }

    /**
     * Test uploadStatus
     *
     * This must fail until Files::uploadStatus don't handle UPLOAD_ERR_EXTENSION
     */
    #[Depends('testStr2Bytes')]
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

        $this->assertTrue(
            \Dotclear\Helper\File\Files::uploadStatus($file)
        );

        // Simulate error
        $file['error'] = UPLOAD_ERR_INI_SIZE;
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        $file['error'] = UPLOAD_ERR_FORM_SIZE;
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        $file['error'] = UPLOAD_ERR_PARTIAL;
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        $file['error'] = UPLOAD_ERR_NO_TMP_DIR; // Since PHP 5.0.3
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        $file['error'] = UPLOAD_ERR_NO_FILE;
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        $file['error'] = UPLOAD_ERR_CANT_WRITE;
        $this->expectException(Exception::class);
        \Dotclear\Helper\File\Files::uploadStatus($file);

        // This part might fail
        if (version_compare(phpversion(), '5.2.0', '>')) {
            $file['error'] = UPLOAD_ERR_EXTENSION; // Since PHP 5.2
            $this->expectException(Exception::class);
            \Dotclear\Helper\File\Files::uploadStatus($file);
        }
    }

    #[Depends('testUploadStatus')]
    public function testGetDirList()
    {
        $dir = $this->getTempDir();

        $arr = [];
        \Dotclear\Helper\File\Files::getDirList($dir, $arr);

        $this->assertNotEmpty(
            $arr
        );
        $this->assertArrayHasKey(
            'files',
            $arr
        );
        $this->assertArrayHasKey(
            'dirs',
            $arr
        );
        $this->assertNotEmpty(
            $arr['files']
        );
        $this->assertNotEmpty(
            $arr['dirs']
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf('%s is not a directory.', $dir . DIRECTORY_SEPARATOR . 'void'));

        \Dotclear\Helper\File\Files::getDirList($dir . DIRECTORY_SEPARATOR . 'void', $arr);

        // Deep structure read
        $dirstructure = join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList', 'tests', 'are', 'good', 'for', 'you']);
        mkdir($dirstructure, self::READ_WRITE_EXECUTE, true);
        \Dotclear\Helper\File\Files::getDirList(join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList']), $arr);

        $this->assertNotEmpty(
            $arr['dirs']
        );

        \Dotclear\Helper\File\Files::deltree(join(DIRECTORY_SEPARATOR, [$dir, 'testGetDirList']));

        // Unreadable dir
        $dirname = $dir . DIRECTORY_SEPARATOR . 'testGetDirListVoid';
        mkdir($dirname);
        chmod($dirname, self::WRITE_ONLY);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to open directory.');

        \Dotclear\Helper\File\Files::getDirList($dirname, $arr);

        chmod($dirname, self::READ_WRITE_EXECUTE);

        $this->delTempDir($dir);
    }

    #[Depends('testGetDirList')]
    public function testTidyFilename()
    {
        $this->assertEquals(
            'a_test_file.txt',
            \Dotclear\Helper\File\Files::tidyFileName('a test file.txt')
        );
    }
}
