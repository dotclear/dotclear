<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    private string $root;
    private string $url;

    protected function setUp(): void
    {
        $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'FileManager']));
        $this->url  = 'https://example.com/public/';
    }

    public function test()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root);

        $this->assertNotNull(
            $file
        );
        $this->assertTrue(
            $file instanceof \Dotclear\Helper\File\File,
        );
    }

    #[RunInSeparateProcess]
    public function testStdPropertiesFile()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root, $this->url);

        $this->assertEquals(
            $this->root . DIRECTORY_SEPARATOR . 'valid.md',
            $file->file
        );
        $this->assertEquals(
            'valid.md',
            $file->basename
        );
        $this->assertEquals(
            $this->root,
            $file->dir
        );
        $this->assertEquals(
            $this->url . 'valid.md',
            $file->file_url
        );
        $this->assertEquals(
            rtrim($this->url, '/'),
            $file->dir_url
        );
        $this->assertEquals(
            'md',
            $file->extension
        );
        $this->assertEquals(
            'valid.md',
            $file->relname
        );
        $this->assertFalse(
            $file->parent
        );
        $this->assertEquals(
            'application/octet-stream',
            $file->type
        );
        $this->assertEquals(
            'application',
            $file->type_prefix
        );
        $this->assertGreaterThan(
            0,
            $file->mtime
        );
        $this->assertEquals(
            12,
            $file->size
        );
        $this->assertGreaterThan(
            0,
            $file->mode
        );
        $this->assertGreaterThan(
            0,
            $file->uid
        );
        $this->assertGreaterThan(
            0,
            $file->gid
        );
        $this->assertTrue(
            $file->w
        );
        $this->assertFalse(
            $file->d
        );
        $this->assertFalse(
            $file->x
        );
        $this->assertTrue(
            $file->f
        );
        $this->assertTrue(
            $file->del
        );
    }

    #[RunInSeparateProcess]
    public function testStdPropertiesDir()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'sub', $this->root, $this->url);

        $this->assertEquals(
            $this->root . DIRECTORY_SEPARATOR . 'sub',
            $file->file
        );
        $this->assertEquals(
            'sub',
            $file->basename
        );
        $this->assertEquals(
            $this->root,
            $file->dir
        );
        $this->assertEquals(
            $this->url . 'sub',
            $file->file_url
        );
        $this->assertEquals(
            rtrim($this->url, '/'),
            $file->dir_url
        );
        $this->assertEquals(
            '',
            $file->extension
        );
        $this->assertEquals(
            'sub',
            $file->relname
        );
        $this->assertFalse(
            $file->parent
        );
        $this->assertEquals(
            'application/octet-stream',
            $file->type
        );
        $this->assertEquals(
            'application',
            $file->type_prefix
        );
        $this->assertGreaterThan(
            0,
            $file->mtime
        );
        $this->assertGreaterThan(
            0,
            $file->size
        );
        $this->assertGreaterThan(
            0,
            $file->mode
        );
        $this->assertGreaterThan(
            0,
            $file->uid
        );
        $this->assertGreaterThan(
            0,
            $file->gid
        );
        $this->assertTrue(
            $file->w
        );
        $this->assertTrue(
            $file->d
        );
        $this->assertTrue(
            $file->x
        );
        $this->assertFalse(
            $file->f
        );
        $this->assertFalse(
            $file->del
        );
        ;
    }

    #[RunInSeparateProcess]
    public function testStdPropertiesDirFile()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'valid.md', $this->root, $this->url);

        $this->assertEquals(
            $this->root . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'valid.md',
            $file->file
        );
        $this->assertEquals(
            'valid.md',
            $file->basename
        );
        $this->assertEquals(
            $this->root . '/sub',
            $file->dir
        );
        $this->assertEquals(
            $this->url . 'sub/valid.md',
            $file->file_url
        );
        $this->assertEquals(
            rtrim($this->url . 'sub', '/'),
            $file->dir_url
        );
        $this->assertEquals(
            'md',
            $file->extension
        );
        $this->assertEquals(
            'sub/valid.md',
            $file->relname
        );
        $this->assertFalse(
            $file->parent
        );
        $this->assertEquals(
            'application/octet-stream',
            $file->type
        );
        $this->assertEquals(
            'application',
            $file->type_prefix
        );
        $this->assertGreaterThan(
            0,
            $file->mtime
        );
        $this->assertGreaterThan(
            0,
            $file->size
        );
        $this->assertGreaterThan(
            0,
            $file->mode
        );
        $this->assertGreaterThan(
            0,
            $file->uid
        );
        $this->assertGreaterThan(
            0,
            $file->gid
        );
        $this->assertTrue(
            $file->w
        );
        $this->assertFalse(
            $file->d
        );
        $this->assertFalse(
            $file->x
        );
        $this->assertTrue(
            $file->f
        );
        $this->assertTrue(
            $file->del
        );
    }

    #[RunInSeparateProcess]
    public function testStdPropertiesDirUp()
    {
        $file = new \Dotclear\Helper\File\File(implode(DIRECTORY_SEPARATOR, [$this->root, 'sub', 'subsub', '..']), $this->root, $this->url);

        $this->assertEquals(
            $this->root . DIRECTORY_SEPARATOR . 'sub',
            $file->file
        );
        $this->assertEquals(
            'sub',
            $file->basename
        );
        $this->assertEquals(
            $this->root,
            $file->dir
        );
        $this->assertEquals(
            $this->url . 'sub',
            $file->file_url
        );
        $this->assertEquals(
            rtrim($this->url, '/'),
            $file->dir_url
        );
        $this->assertEquals(
            '',
            $file->extension
        );
        $this->assertEquals(
            'sub',
            $file->relname
        );
        $this->assertFalse(
            $file->parent
        );
        $this->assertEquals(
            'application/octet-stream',
            $file->type
        );
        $this->assertEquals(
            'application',
            $file->type_prefix
        );
        $this->assertGreaterThan(
            0,
            $file->mtime
        );
        $this->assertGreaterThan(
            0,
            $file->size
        );
        $this->assertGreaterThan(
            0,
            $file->mode
        );
        $this->assertGreaterThan(
            0,
            $file->uid
        );
        $this->assertGreaterThan(
            0,
            $file->gid
        );
        $this->assertTrue(
            $file->w
        );
        $this->assertTrue(
            $file->d
        );
        $this->assertTrue(
            $file->x
        );
        $this->assertFalse(
            $file->f
        );
        $this->assertFalse(
            $file->del
        );
        ;
    }

    public function testUserDefinedProperties()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root);

        $file->mySweetProperty = true;

        $this->assertTrue(
            isset($file->mySweetProperty)
        );
        $this->assertFalse(
            isset($file->myUnsetProperty)
        );
        $this->assertTrue(
            $file->mySweetProperty
        );

        unset($file->mySweetProperty);

        $this->assertFalse(
            isset($file->mySweetProperty)
        );
    }

    public function testInvalid()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'invalid.md', $this->root);

        $this->assertNotNull(
            $file
        );
        $this->assertTrue(
            $file instanceof \Dotclear\Helper\File\File,
        );
        $this->assertNull(
            $file->file
        );
    }
}
