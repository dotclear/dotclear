<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File;

use PHPUnit\Framework\TestCase;

class MediaManagerTest extends TestCase
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
    }

    public function test(): void
    {
        $manager = new \Dotclear\Helper\File\MediaManager($this->root, $this->url);
        $manager->addExclusion([$this->root . DIRECTORY_SEPARATOR . 'private']);

        $this->assertEquals(
            $this->root,
            $manager->getRoot()
        );
        $this->assertEquals(
            $this->url,
            $manager->getRootUrl()
        );

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

        // Extract basenames
        $rootDirs = [];
        foreach ($manager->getRootDirs() as $dir) {
            $rootDirs[] = $dir->relname;
        }

        $this->assertEquals(
            [
                '',
                'sub',
                'private',
            ],
            $rootDirs
        );
    }
}
