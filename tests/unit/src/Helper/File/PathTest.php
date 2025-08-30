<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\RequiresFunction;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[BackupGlobals(true)]
class PathTest extends TestCase
{
    private string $testDirectory;
    private string $rootDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));
        $this->rootDirectory = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', '..']));

        $_SERVER['SCRIPT_FILENAME'] = __FILE__;
    }

    public function testRealUnstrict(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Hack to make it works under Windows
            $this->assertEquals(
                $this->testDirectory,
                str_replace('/', '\\', (string) \Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', false))
            );
            $this->assertEquals(
                '/tests/unit/fixtures/src/Helper/File',
                str_replace('/', '\\', (string) \Dotclear\Helper\File\Path::real('tests/unit/fixtures/files', false))
            );
            $this->assertEquals(
                '/tests/unit/fixtures/src/Helper/File',
                str_replace('/', '\\', (string) \Dotclear\Helper\File\Path::real('tests/./unit/fixtures/files', false))
            );
        } else {
            $this->assertEquals(
                $this->testDirectory,
                \Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', false)
            );
            $this->assertEquals(
                $this->rootDirectory . '/tests/unit/src/Helper/File/tests/unit/fixtures/files',
                \Dotclear\Helper\File\Path::real('tests/unit/fixtures/files', false)
            );
            $this->assertEquals(
                $this->rootDirectory . '/tests/unit/src/Helper/File/tests/unit/fixtures/files',
                \Dotclear\Helper\File\Path::real('tests/./unit/fixtures/files', false)
            );
        }
    }

    public function testRealStrict(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Hack to make it works under Windows
            $this->assertEquals(
                $this->testDirectory,
                str_replace('/', '\\', (string) \Dotclear\Helper\File\Path::real(__DIR__ . '/../fixtures/files', true))
            );
        } else {
            $this->assertEquals(
                $this->testDirectory,
                \Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', true)
            );
        }
    }

    public function testReduce(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Hack to make it works under Windows
            $this->assertEquals(
                $this->testDirectory,
                str_replace('/', '\\', \Dotclear\Helper\File\Path::reduce([__DIR__, '/../fixtures/files']))
            );
        } else {
            $this->assertEquals(
                $this->testDirectory,
                \Dotclear\Helper\File\Path::reduce([__DIR__, '/../../../../fixtures/src/Helper/File'])
            );
        }
    }

    public function testClean(): void
    {
        $this->assertEquals(
            DIRECTORY_SEPARATOR . 'testDirectory',
            \Dotclear\Helper\File\Path::clean('..' . DIRECTORY_SEPARATOR . 'testDirectory')
        );
        $this->assertEquals(
            DIRECTORY_SEPARATOR . 'testDirectory',
            \Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR)
        );
        $this->assertEquals(
            DIRECTORY_SEPARATOR . 'testDirectory',
            \Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR)
        );
        $this->assertEquals(
            DIRECTORY_SEPARATOR . 'testDirectory',
            \Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR . '..')
        );
    }

    public function testInfo(): void
    {
        $info = \Dotclear\Helper\File\Path::info($this->testDirectory . DIRECTORY_SEPARATOR . '1-one.txt');

        $this->assertNotEmpty(
            $info
        );
        $this->assertArrayHasKey(
            'dirname',
            $info
        );
        $this->assertArrayHasKey(
            'basename',
            $info
        );
        $this->assertArrayHasKey(
            'extension',
            $info
        );
        $this->assertArrayHasKey(
            'base',
            $info
        );
        $this->assertEquals(
            $this->testDirectory,
            $info['dirname']
        );
        $this->assertEquals(
            '1-one.txt',
            $info['basename']
        );
        $this->assertEquals(
            'txt',
            $info['extension']
        );
        $this->assertEquals(
            '1-one',
            $info['base']
        );
    }

    public function testFullFromRoot(): void
    {
        $this->assertEquals(
            '/test',
            \Dotclear\Helper\File\Path::fullFromRoot('/test', '/')
        );
        $this->assertEquals(
            '/home/sweethome/test/string',
            \Dotclear\Helper\File\Path::fullFromRoot('test/string', '/home/sweethome')
        );
    }

    public function testdirWithSym(): void
    {
        $target = $this->testDirectory . DIRECTORY_SEPARATOR . 'target';
        if (!is_dir($target)) {
            mkdir($target);
        }

        $symlink = $this->testDirectory . DIRECTORY_SEPARATOR . 'symlink';
        if (!file_exists($symlink)) {
            symlink($target, $symlink);
        }

        $this->assertTrue(
            is_link($symlink)
        );
        $this->assertEquals(
            $target,
            \Dotclear\Helper\File\Path::dirWithSym($symlink)
        );
        $this->assertEquals(
            $target,
            \Dotclear\Helper\File\Path::dirWithSym($target)
        );
        $this->assertEquals(
            '',
            \Dotclear\Helper\File\Path::dirWithSym($this->testDirectory . DIRECTORY_SEPARATOR . '1-one.txt')
        );

        if (file_exists($symlink)) {
            unlink($symlink);
        }
        if (is_dir($target)) {
            rmdir($target);
        }
    }

    #[RequiresPhpExtension('opcache'), RequiresFunction('opcache_get_status'), RequiresFunction('opcache_reset')]
    public function testResetCache(): void
    {
        $status = opcache_get_status();

        \Dotclear\Helper\File\Path::resetServerCache();

        $this->assertNotEquals(
            $status,
            opcache_get_status()
        );
    }
}
