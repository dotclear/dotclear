<?php

declare(strict_types=1);

namespace Dotclear\Helper\File {
    use Closure;

    // Holds the mock callback

    function move_uploaded_file(string $from, string $to): bool
    {
        return MoveUploadedFileMock::$callback
            ? (MoveUploadedFileMock::$callback)($from, $to)
            : \move_uploaded_file($from, $to); // fallback to real function
    }

    function rename(string $from, string $to, mixed $context = null): bool
    {
        return RenameMock::$callback
            ? (RenameMock::$callback)($from, $to, $context)
            : \rename($from, $to, $context); // fallback to real function
    }

    function unlink(string $filename, mixed $context = null): bool
    {
        return UnlinkMock::$callback
            ? (UnlinkMock::$callback)($filename, $context)
            : \unlink($filename, $context); // fallback to real function
    }

    function rmdir(string $filename, mixed $context = null): bool
    {
        return RmdirMock::$callback
            ? (RmdirMock::$callback)($filename, $context)
            : \rmdir($filename, $context); // fallback to real function
    }

    function is_writable(string $filename): bool
    {
        return IsWritableMock::$callback
            ? (IsWritableMock::$callback)($filename)
            : \is_writable($filename); // fallback to real function
    }

    final class MoveUploadedFileMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class RenameMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class UnlinkMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class RmdirMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }

    final class IsWritableMock
    {
        public static ?Closure $callback = null;
        public static function set(?Closure $callback): void
        {
            self::$callback = $callback;
        }
    }
}

namespace Dotclear\Tests\Helper\File {
    use Dotclear\Helper\File\IsWritableMock;
    use Dotclear\Helper\File\MoveUploadedFileMock;
    use Dotclear\Helper\File\RenameMock;
    use Dotclear\Helper\File\RmdirMock;
    use Dotclear\Helper\File\UnlinkMock;
    use Exception;
    use PHPUnit\Framework\Attributes\Depends;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\TestCase;

    class ManagerTest extends TestCase
    {
        private string $root;
        private string $url;

        protected function setUp(): void
        {
            $this->root = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'FileManager']));
            $this->url  = 'https://example.com/public/';
        }

        protected function tearDown(): void
        {
            chmod($this->root, 0o755);
            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md');
            }
            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-unlink.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-unlink.md');
            }
            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-perm.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-perm.md');
            }
            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'dir-rmdir')) {
                rmdir($this->root . DIRECTORY_SEPARATOR . 'dir-rmdir');
            }
            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'dir-perm')) {
                rmdir($this->root . DIRECTORY_SEPARATOR . 'dir-perm');
            }
            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'dir-item')) {
                rmdir($this->root . DIRECTORY_SEPARATOR . 'dir-item');
            }
        }

        public function test(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $this->assertEquals(
                $this->root,
                $manager->getRoot()
            );
            $this->assertEquals(
                $this->url,
                $manager->getRootUrl()
            );
        }

        #[Depends('test')]
        public function testUnknown(): void
        {
            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid root directory.');

            $manager = new \Dotclear\Helper\File\Manager($this->root . DIRECTORY_SEPARATOR . 'unknown');
        }

        #[Depends('testUnknown')]
        public function testStdProperties(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $dir     = [
                'dirs'  => $manager->getDirs(),
                'files' => $manager->getFiles(),
            ];

            $this->assertEquals(
                $this->root,
                $manager->root
            );
            $this->assertEquals(
                $this->url,
                $manager->root_url
            );
            $this->assertEquals(
                [
                    'dirs'  => [],
                    'files' => [],
                ],
                $dir
            );
        }

        #[Depends('testStdProperties')]
        public function testChdir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $manager->chdir('sub');

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'sub',
                $manager->getPwd()
            );
        }

        #[Depends('testChdir')]
        public function testChdirException(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid directory.');

            $manager->chdir('unknown');

            $this->assertEquals(
                $this->root,
                $manager->getPwd()
            );
        }

        #[Depends('testChdirException')]
        public function testChdirExceptionExclusion(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->addExclusion($this->root . DIRECTORY_SEPARATOR . 'sub');

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Directory is excluded.');

            $manager->chdir('sub');

            $this->assertEquals(
                $this->root,
                $manager->getPwd()
            );
        }

        #[Depends('testChdirExceptionExclusion')]
        public function testWritable(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->chdir('sub');
            $current = fileperms($manager->getPwd());

            $this->assertTrue(
                $manager->writable()
            );

            chmod($manager->getPwd(), 0o400);

            $this->assertFalse(
                $manager->writable()
            );

            chmod($manager->getPwd(), (int) $current);
        }

        #[Depends('testWritable')]
        public function testAddDirExclusion(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Invalid directory.');

            $manager->chdir($this->root . DIRECTORY_SEPARATOR . 'private');

            $this->assertEquals(
                $this->root,
                $manager->getPwd()
            );

            $manager->chdir('sub');

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'sub',
                $manager->getPwd()
            );
        }

        #[Depends('testAddDirExclusion')]
        public function testGetDir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);

            $manager->getDir();

            $this->assertEquals(
                $this->root,
                $manager->getPwd()
            );

            $this->assertEquals(
                1,
                count($manager->getDirs())
            );
            $this->assertEquals(
                3,
                count($manager->getFiles())
            );

            // Extract basenames
            $dirs = $files = [];
            foreach ($manager->getDirs() as $dir) {
                $dirs[] = $dir->relname;
            }
            foreach ($manager->getFiles() as $file) {
                $files[] = $file->relname;
            }

            $this->assertEquals(
                [
                    'sub',
                ],
                $dirs
            );
            $this->assertEquals(
                [
                    'excluded.md',
                    'jail.md',
                    'valid.md',
                ],
                $files
            );

            $this->assertTrue(
                $manager->inFiles('jail.md')
            );
            $this->assertFalse(
                $manager->inFiles('unknown.md')
            );
        }

        #[Depends('testGetDir')]
        public function testGetSubDir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $manager->chdir('sub');
            $manager->getDir();

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'sub',
                $manager->getPwd()
            );
            $this->assertEquals(
                1,
                count($manager->getDirs())
            );
            $this->assertEquals(
                1,
                count($manager->getFiles())
            );

            // Extract basenames
            $dirs = $files = [];
            foreach ($manager->getDirs() as $dir) {
                $dirs[] = $dir->basename;
            }
            foreach ($manager->getFiles() as $file) {
                $files[] = $file->basename;
            }

            $this->assertEquals(
                [
                    'FileManager',
                ],
                $dirs
            );
            $this->assertEquals(
                [
                    'valid.md',
                ],
                $files
            );

            $this->assertTrue(
                $manager->inFiles('sub' . DIRECTORY_SEPARATOR . 'valid.md')
            );
            $this->assertFalse(
                $manager->inFiles('sub' . DIRECTORY_SEPARATOR . 'unknown.md')
            );
        }

        #[Depends('testGetSubDir')]
        public function testGetDirWithExclusionPattern(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);
            $manager->setExcludePattern('/excluded\.md$/');

            $manager->getDir();

            $this->assertEquals(
                $this->root,
                $manager->getPwd()
            );
            $this->assertEquals(
                1,
                count($manager->getDirs())
            );
            $this->assertEquals(
                2,
                count($manager->getFiles())
            );

            // Extract basenames
            $dirs = $files = [];
            foreach ($manager->getDirs() as $dir) {
                $dirs[] = $dir->relname;
            }
            foreach ($manager->getFiles() as $file) {
                $files[] = $file->relname;
            }

            $this->assertEquals(
                [
                    'sub',
                ],
                $dirs
            );
            $this->assertEquals(
                [
                    'jail.md',
                    'valid.md',
                ],
                $files
            );
        }

        #[Depends('testGetDirWithExclusionPattern')]
        public function testGetRootDir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            // Extract basenames
            $dirs = [];
            foreach ($manager->getRootDirs() as $dir) {
                $dirs[] = $dir->basename;
            }
            usort($dirs, fn ($a, $b) => strcasecmp($a, $b));

            $this->assertEquals(
                [
                    'FileManager',
                    'private',
                    'sub',
                ],
                $dirs
            );
        }

        #[Depends('testGetRootDir')]
        public function testUploadFile(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->setExcludePattern('/stop\.md$/');

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md');
            }

            // Mock move_uploaded_file()
            MoveUploadedFileMock::set(fn (string $from, string $to): bool => copy($from, $to));

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'valid-clone.md',
                $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md')
            );

            $manager->getDir();

            $this->assertTrue(
                $manager->inFiles('valid-clone.md')
            );

            // Test exceptions

            // Mock move_uploaded_file()
            MoveUploadedFileMock::set(fn (string $from, string $to): bool => false);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('An error occurred while writing the file.');

            $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md', true);

            // Mock move_uploaded_file()
            MoveUploadedFileMock::set(fn (string $from, string $to): bool => copy($from, $to));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('File already exists.');

            $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md');

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md');
            }

            // Mock move_uploaded_file()
            MoveUploadedFileMock::set(fn (string $from, string $to): bool => copy($from, $to));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Uploading this file is not allowed.');

            $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'stop.md');

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'stop.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'stop.md');
            }

            $manager->chdir('sub');
            $current = fileperms($manager->getPwd());
            chmod($manager->getPwd(), 0o400);

            // Mock move_uploaded_file()
            MoveUploadedFileMock::set(fn (string $from, string $to): bool => copy($from, $to));

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Cannot write in this directory.');

            $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md');

            chmod($manager->getPwd(), (int) $current);

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'stop.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'stop.md');
            }

            // Unmock move_uploaded_file()
            MoveUploadedFileMock::set(null);
        }

        #[Depends('testUploadFile')]
        public function testUploadBits(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
            $manager->setExcludePattern('/warning\.md$/');

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md');
            }

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'valid-bits.md',
                $manager->uploadBits('valid-bits.md', 'I\'m validl!')
            );

            $manager->getDir();

            $this->assertTrue(
                $manager->inFiles('valid-bits.md')
            );

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md');
            }

            // Test exceptions

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Uploading this file is not allowed.');

            $manager->uploadBits('warning.md', 'I\'m validl!');

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'warning.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'warning.md');
            }

            $manager->chdir('sub');
            $current = fileperms($manager->getPwd());
            chmod($manager->getPwd(), 0o400);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Cannot write in this directory.');

            $manager->uploadBits('valid-bits.md', 'I\'m validl!');

            chmod($manager->getPwd(), (int) $current);

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md');
            }

            $manager->chdir('');
            $manager->uploadBits('valid-bits.md', 'I\'m validl!');
            chmod($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md', 0o400);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('An error occurred while writing the file.');

            $manager->uploadBits('valid-bits.md', 'I\'m validl!');

            chmod($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md', 0o755);
            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md');
            }
        }

        #[Depends('testUploadBits')]
        public function testMakeDir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'hello')) {
                @rmdir($this->root . DIRECTORY_SEPARATOR . 'hello');
            }

            $manager->makeDir('hello');
            $manager->chdir('hello');

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'hello',
                $manager->getPwd()
            );

            $manager->chdir('');
            $manager->removeDir('hello');

            $this->assertFalse(
                is_dir($this->root . DIRECTORY_SEPARATOR . 'hello')
            );
        }

        #[Depends('testMakeDir')]
        public function testMoveFile(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (file_exists($this->root . DIRECTORY_SEPARATOR . 'hello.md')) {
                unlink($this->root . DIRECTORY_SEPARATOR . 'hello.md');
            }

            $manager->moveFile('jail.md', 'hello.md');
            $manager->getDir();

            $this->assertFalse(
                $manager->inFiles('jail.md')
            );
            $this->assertTrue(
                $manager->inFiles('hello.md')
            );

            $manager->moveFile('hello.md', 'jail.md');

            // Test exceptions

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Source file does not exist.');

            $manager->moveFile('hello.md', 'hello-clone.md');

            $manager->chdir('sub');
            $current = fileperms($manager->getPwd());
            chmod($manager->getPwd(), 0o500);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Destination directory is not writable.');

            $manager->moveFile('sub' . DIRECTORY_SEPARATOR . 'valid.md', 'sub' . DIRECTORY_SEPARATOR . 'valid-clone.md');

            chmod($manager->getPwd(), (int) $current);
        }

        #[Depends('testMoveFile')]
        public function testMoveFileError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            // Mock rename()
            RenameMock::set(fn (string $from, string $to, $context = null): bool => false);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Unable to rename file.');

            $manager->moveFile('valid.md', 'valid-error.md');

            // Unmock rename()
            RenameMock::set(null);
        }

        #[Depends('testMoveFileError')]
        public function testRemoveFile(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $manager->uploadBits('valid-file.md', 'I\'m validl!');
            $manager->removeFile('valid-file.md');
            $manager->getDir();

            $this->assertFalse(
                $manager->inFiles('valid-file.md')
            );

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-file.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-file.md');
            }
        }

        #[Depends('testRemoveFile')]
        public function testRemoveFileUnlinkError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md');
            }

            $manager->uploadBits('valid-unlink.md', 'I\'m validl!');

            // Mock unlink()
            UnlinkMock::set(fn (string $filename, $context = null): bool => false);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('File cannot be removed.');

            $manager->removeFile('valid-unlink.md');

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md');
            }

            // Unmock unlink()
            UnlinkMock::set(null);
        }

        #[Depends('testRemoveFileUnlinkError')]
        public function testRemoveFileInJailError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('File is not in jail.');

            $manager->removeFile('valid-injail.md');
        }

        #[Depends('testRemoveFileInJailError')]
        public function testRemoveFilePermError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md');
            }

            $manager->uploadBits('valid-perm.md', 'I\'m validl!');
            $current = fileperms($manager->getPwd());
            chmod($manager->getPwd(), 0o500);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('File cannot be removed.');

            $manager->removeFile('valid-perm.md');

            chmod($manager->getPwd(), (int) $current);
            chmod($this->root, 0o755);

            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md');
            }
        }

        #[Depends('testRemoveFilePermError')]
        public function testRemoveDir(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $manager->makeDir('valid-dir');
            $manager->removeDir('valid-dir');

            $this->assertFalse(
                is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir')
            );

            if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir')) {
                rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir');
            }
        }

        #[Depends('testRemoveDir')]
        public function testRemoveDirRmdirError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir')) {
                rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir');
            }

            $manager->makeDir('dir-rmdir');

            // Mock rmdir()
            RmdirMock::set(fn (string $filename, $context = null): bool => false);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Directory cannot be removed.');

            $manager->removeDir('dir-rmdir');

            if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir')) {
                rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir');
            }

            // Unmock rmdir()
            RmdirMock::set(null);
        }

        #[Depends('testRemoveDirRmdirError')]
        public function testRemoveDirInJailError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Directory is not in jail.');

            $manager->removeDir('dir-jail');
        }

        #[Depends('testRemoveDirInJailError')]
        public function testRemoveDirPermError(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm')) {
                rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm');
            }

            $manager->makeDir('dir-perm');

            // Mock is_writable()
            IsWritableMock::set(fn (string $filename): bool => false);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Directory cannot be removed.');

            $manager->removeDir('dir-perm');

            if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm')) {
                rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm');
            }

            // Unmock is_writable()
            IsWritableMock::set(null);
        }

        #[Depends('testRemoveDirPermError'), RunInSeparateProcess]
        public function testRemoveItem(): void
        {
            $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

            if (is_dir($this->root . DIRECTORY_SEPARATOR . 'dir-item')) {
                @rmdir($this->root . DIRECTORY_SEPARATOR . 'dir-item');
            }
            if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md')) {
                unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md');
            }

            $manager->makeDir('dir-item');
            $manager->chdir('dir-item');

            $this->assertEquals(
                $this->root . DIRECTORY_SEPARATOR . 'dir-item',
                $manager->getPwd()
            );

            $manager->chdir('');
            $manager->removeItem('dir-item');

            $this->assertFalse(
                is_dir($this->root . DIRECTORY_SEPARATOR . 'dir-item')
            );

            $manager->uploadBits('valid-item.md', 'I\'m validl!');
            $manager->removeItem('valid-item.md');
            $manager->getDir();

            $this->assertFalse(
                $manager->inFiles($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md')
            );
        }
    }
}
