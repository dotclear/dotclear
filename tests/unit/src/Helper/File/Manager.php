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

/**
 * @tags Filemanager
 * @engine isolate
 */
class Manager extends atoum
{
    private string $root;
    private string $url;

    public function __construct()
    {
        parent::__construct();

        $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'FileManager']));
        $this->url  = 'https://example.com/public/';
    }

    public function test()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->object($manager)
            ->isNotNull()
            ->class('\Dotclear\Helper\File\Manager')
        ;
    }

    public function testUnknown()
    {
        $this
            ->exception(function () {
                $manager = new \Dotclear\Helper\File\Manager($this->root . DIRECTORY_SEPARATOR . 'unknown');
            })
            ->hasMessage('Invalid root directory.')
        ;
    }

    public function testStdProperties()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->string($manager->root)
            ->isEqualTo($this->root)
            ->string($manager->root_url)
            ->isEqualTo($this->url)
            ->array($manager->dir)
            ->isEqualTo([
                'dirs'  => [],
                'files' => [],
            ])
        ;
    }

    public function testChdir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->given($manager->chdir('sub'))
            ->string($manager->getPwd())
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub')
        ;
    }

    public function testChdirException()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->exception(function () use ($manager) {
                $manager->chdir('unknown');
            })
            ->hasMessage('Invalid directory.')
            ->string($manager->getPwd())
            ->isEqualTo($this->root)
        ;
    }

    public function testChdirExceptionExclusion()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->addExclusion($this->root . DIRECTORY_SEPARATOR . 'sub');

        $this
            ->exception(function () use ($manager) {
                $manager->chdir('sub');
            })
            ->hasMessage('Directory is excluded.')
            ->string($manager->getPwd())
            ->isEqualTo($this->root)
        ;
    }

    public function testWritable()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->chdir('sub');
        $current = fileperms($manager->getPwd());

        $this
            ->boolean($manager->writable())
            ->isTrue()
        ;

        chmod($manager->getPwd(), 0400);

        $this
            ->boolean($manager->writable())
            ->isFalse()
        ;

        chmod($manager->getPwd(), $current);
    }

    public function testAddDirExclusion()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);

        $this
            ->exception(function () use ($manager) {
                $manager->chdir($this->root . DIRECTORY_SEPARATOR . 'private');
            })
            ->hasMessage('Invalid directory.')
            ->string($manager->getPwd())
            ->isEqualTo($this->root)
            ->given($manager->chdir('sub'))
            ->string($manager->getPwd())
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub')
        ;
    }

    public function testGetDir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);

        $this
            ->given($manager->getDir())
            ->string($manager->getPwd())
            ->isEqualTo($this->root)
            ->integer(count($manager->dir['dirs']))
            ->isEqualTo(1)
            ->integer(count($manager->dir['files']))
            ->isEqualTo(3)
        ;

        // Extract basenames
        $dirs = $files = [];
        foreach ($manager->dir['dirs'] as $dir) {
            $dirs[] = $dir->relname;
        }
        foreach ($manager->dir['files'] as $file) {
            $files[] = $file->relname;
        }

        $this
            ->array($dirs)
            ->isEqualTo([
                'sub',
            ])
            ->array($files)
            ->isEqualTo([
                'excluded.md',
                'jail.md',
                'valid.md',
            ])
        ;

        $this
            ->boolean($manager->inFiles('jail.md'))
            ->isTrue()
            ->boolean($manager->inFiles('unknown.md'))
            ->isFalse()
        ;
    }

    public function testGetSubDir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->chdir('sub');

        $this
            ->given($manager->getDir())
            ->string($manager->getPwd())
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub')
            ->integer(count($manager->dir['dirs']))
            ->isEqualTo(1)
            ->integer(count($manager->dir['files']))
            ->isEqualTo(1)
        ;

        // Extract basenames
        $dirs = $files = [];
        foreach ($manager->dir['dirs'] as $dir) {
            $dirs[] = $dir->basename;
        }
        foreach ($manager->dir['files'] as $file) {
            $files[] = $file->basename;
        }

        $this
            ->array($dirs)
            ->isEqualTo([
                'FileManager',
            ])
            ->array($files)
            ->isEqualTo([
                'valid.md',
            ])
        ;

        $this
            ->boolean($manager->inFiles('sub' . DIRECTORY_SEPARATOR . 'valid.md'))
            ->isTrue()
            ->boolean($manager->inFiles('sub' . DIRECTORY_SEPARATOR . 'unknown.md'))
            ->isFalse()
        ;
    }

    public function testGetDirWithExclusionPattern()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);
        $manager->setExcludePattern('/excluded\.md$/');

        $this
            ->given($manager->getDir())
            ->string($manager->getPwd())
            ->isEqualTo($this->root)
            ->integer(count($manager->dir['dirs']))
            ->isEqualTo(1)
            ->integer(count($manager->dir['files']))
            ->isEqualTo(2)
        ;

        // Extract basenames
        $dirs = $files = [];
        foreach ($manager->dir['dirs'] as $dir) {
            $dirs[] = $dir->relname;
        }
        foreach ($manager->dir['files'] as $file) {
            $files[] = $file->relname;
        }

        $this
            ->array($dirs)
            ->isEqualTo([
                'sub',
            ])
            ->array($files)
            ->isEqualTo([
                'jail.md',
                'valid.md',
            ])
        ;
    }

    public function testGetRootDir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        // Extract basenames
        $dirs = [];
        foreach ($manager->getRootDirs() as $dir) {
            $dirs[] = $dir->basename;
        }
        usort($dirs, fn ($a, $b) => strcasecmp($a, $b));

        $this
            ->array($dirs)
            ->isEqualTo([
                'FileManager',
                'private',
                'sub',
            ])
        ;
    }

    public function testUploadFile()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->setExcludePattern('/stop\.md$/');

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md');
        }

        $this
            // Mock move_uploaded_file()
            ->if($this->function->move_uploaded_file = function (string $from, string $to) {
                copy($from, $to);
            })
            // Test method
            ->then
                ->string($manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md'))
                ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')
        ;

        $this
            ->then($manager->getDir())
            ->boolean($manager->inFiles('valid-clone.md'))
            ->isTrue()
        ;

        // Test exceptions

        $this
            // Mock move_uploaded_file()
            ->if($this->function->move_uploaded_file = fn (string $from, string $to) => false)
            // Test method
            ->exception(function () use ($manager) {
                $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md', true);
            })
            ->hasMessage('An error occurred while writing the file.')
        ;

        $this
            // Mock move_uploaded_file()
            ->if($this->function->move_uploaded_file = function (string $from, string $to) {
                copy($from, $to);
            })
            // Test method
            ->exception(function () use ($manager) {
                $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md');
            })
            ->hasMessage('File already exists.')
        ;

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'valid-clone.md');
        }

        $this
            // Mock move_uploaded_file()
            ->if($this->function->move_uploaded_file = function (string $from, string $to) {
                copy($from, $to);
            })
            // Test method
            ->exception(function () use ($manager) {
                $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'stop.md');
            })
            ->hasMessage('Uploading this file is not allowed.')
        ;

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'stop.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'stop.md');
        }

        $manager->chdir('sub');
        $current = fileperms($manager->getPwd());
        chmod($manager->getPwd(), 0400);

        $this
            // Mock move_uploaded_file()
            ->if($this->function->move_uploaded_file = function (string $from, string $to) {
                copy($from, $to);
            })
            // Test method
            ->exception(function () use ($manager) {
                $manager->uploadFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', 'valid-clone.md');
            })
            ->hasMessage('Cannot write in this directory.')
        ;

        chmod($manager->getPwd(), $current);

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'stop.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'stop.md');
        }
    }

    public function testUploadBits()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);
        $manager->setExcludePattern('/warning\.md$/');

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md');
        }

        $this
            ->string($manager->uploadBits('valid-bits.md', 'I\'m validl!'))
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md')
        ;

        $this
            ->then($manager->getDir())
            ->boolean($manager->inFiles('valid-bits.md'))
            ->isTrue()
        ;

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'valid-bits.md');
        }

        // Test exceptions

        $this
            ->exception(function () use ($manager) {
                $manager->uploadBits('warning.md', 'I\'m validl!');
            })
            ->hasMessage('Uploading this file is not allowed.')
        ;

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'warning.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'warning.md');
        }

        $manager->chdir('sub');
        $current = fileperms($manager->getPwd());
        chmod($manager->getPwd(), 0400);

        $this
            ->exception(function () use ($manager) {
                $manager->uploadBits('valid-bits.md', 'I\'m validl!');
            })
            ->hasMessage('Cannot write in this directory.')
        ;

        chmod($manager->getPwd(), $current);

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md');
        }

        $manager->chdir('');
        $manager->uploadBits('valid-bits.md', 'I\'m validl!');
        chmod($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md', 0400);

        $this
            ->exception(function () use ($manager) {
                $manager->uploadBits('valid-bits.md', 'I\'m validl!');
            })
            ->hasMessage('An error occurred while writing the file.')
        ;

        chmod($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md', 0755);
        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-bits.md');
        }
    }

    public function testMakeDir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (is_dir($this->root . DIRECTORY_SEPARATOR . 'hello')) {
            @rmdir($this->root . DIRECTORY_SEPARATOR . 'hello');
        }

        $this
            ->given($manager->makeDir('hello'))
            ->and($manager->chdir('hello'))
            ->string($manager->getPwd())
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'hello')
            ->then($manager->chdir(''))
            ->given($manager->removeDir('hello'))
            ->boolean(is_dir($this->root . DIRECTORY_SEPARATOR . 'hello'))
            ->isFalse()
        ;
    }

    public function testMoveFile()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (file_exists($this->root . DIRECTORY_SEPARATOR . 'hello.md')) {
            unlink($this->root . DIRECTORY_SEPARATOR . 'hello.md');
        }

        $this
            ->given($manager->moveFile('jail.md', 'hello.md'))
            ->then($manager->getDir())
            ->boolean($manager->inFiles('jail.md'))
            ->isFalse()
            ->boolean($manager->inFiles('hello.md'))
            ->isTrue()
            ->then($manager->moveFile('hello.md', 'jail.md'))
        ;

        // Test exceptions

        $this
            ->exception(function () use ($manager) {
                $manager->moveFile('hello.md', 'hello-clone.md');
            })
            ->hasMessage('Source file does not exist.')
        ;

        $manager->chdir('sub');
        $current = fileperms($manager->getPwd());
        chmod($manager->getPwd(), 0500);

        $this
            ->exception(function () use ($manager) {
                $manager->moveFile('sub' . DIRECTORY_SEPARATOR . 'valid.md', 'sub' . DIRECTORY_SEPARATOR . 'valid-clone.md');
            })
            ->hasMessage('Destination directory is not writable.')
        ;

        chmod($manager->getPwd(), $current);
    }

    public function testMoveFileError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            // Mock rename()
            ->if($this->function->rename = fn (string $from, string $to, $context = null) => false)
            // Test method
            ->exception(function () use ($manager) {
                $manager->moveFile('valid.md', 'valid-error.md');
            })
            ->hasMessage('Unable to rename file.')
        ;
    }

    public function testRemoveFile()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->given($manager->uploadBits('valid-file.md', 'I\'m validl!'))
            ->then($manager->removeFile('valid-file.md'))
            ->then($manager->getDir())
            ->boolean($manager->inFiles('valid-file.md'))
            ->isFalse()
        ;

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-file.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-file.md');
        }
    }

    public function testRemoveFileUnlinkError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md');
        }

        $manager->uploadBits('valid-unlink.md', 'I\'m validl!');

        $this
            ->if($this->function->unlink = fn (string $filename, $context = null) => false)
            ->exception(function () use ($manager) {
                $manager->removeFile('valid-unlink.md');
            })
            ->hasMessage('File cannot be removed.')
        ;

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-unlink.md');
        }
    }

    public function testRemoveFileInJailError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->exception(function () use ($manager) {
                $manager->removeFile('valid-injail.md');
            })
            ->hasMessage('File is not in jail.')
        ;
    }

    public function testRemoveFilePermError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md');
        }

        $manager->uploadBits('valid-perm.md', 'I\'m validl!');
        $current = fileperms($manager->getPwd());
        chmod($manager->getPwd(), 0500);

        $this
            ->exception(function () use ($manager) {
                $manager->removeFile('valid-perm.md');
            })
            ->hasMessage('File cannot be removed.')
        ;

        chmod($manager->getPwd(), $current);

        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-perm.md');
        }
    }

    public function testRemoveDir()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->given($manager->makeDir('valid-dir'))
            ->then($manager->removeDir('valid-dir'))
            ->boolean(is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir'))
            ->isFalse()
        ;

        if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir')) {
            rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-dir');
        }
    }

    public function testRemoveDirRmdirError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir')) {
            rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir');
        }

        $manager->makeDir('dir-rmdir');

        $this
            ->if($this->function->rmdir = fn (string $filename, $context = null) => false)
            ->exception(function () use ($manager) {
                $manager->removeDir('dir-rmdir');
            })
            ->hasMessage('Directory cannot be removed.')
        ;

        if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir')) {
            rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-rmdir');
        }
    }

    public function testRemoveDirInJailError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        $this
            ->exception(function () use ($manager) {
                $manager->removeDir('dir-jail');
            })
            ->hasMessage('Directory is not in jail.')
        ;
    }

    public function testRemoveDirPermError()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm')) {
            rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm');
        }

        $manager->makeDir('dir-perm');

        $this
            // Mock is_writable()
            ->if($this->function->is_writable = fn (string $filename) => false)
            // Test method
            ->exception(function () use ($manager) {
                $manager->removeDir('dir-perm');
            })
            ->hasMessage('Directory cannot be removed.')
        ;

        if (is_dir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm')) {
            rmdir($manager->getPwd() . DIRECTORY_SEPARATOR . 'dir-perm');
        }
    }

    public function testRemoveItem()
    {
        $manager = new \Dotclear\Helper\File\Manager($this->root, $this->url);

        if (is_dir($this->root . DIRECTORY_SEPARATOR . 'item')) {
            @rmdir($this->root . DIRECTORY_SEPARATOR . 'item');
        }
        if (file_exists($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md')) {
            unlink($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md');
        }

        $this
            ->given($manager->makeDir('item'))
            ->and($manager->chdir('item'))
            ->string($manager->getPwd())
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'item')
            ->then($manager->chdir(''))
            ->given($manager->removeItem('item'))
            ->boolean(is_dir($this->root . DIRECTORY_SEPARATOR . 'item'))
            ->isFalse()
            ->given($manager->uploadBits('valid-item.md', 'I\'m validl!'))
            ->given($manager->removeItem('valid-item.md'))
            ->given($manager->getDir())
            ->boolean($manager->inFiles($manager->getPwd() . DIRECTORY_SEPARATOR . 'valid-item.md'))
            ->isFalse()
        ;
    }
}
