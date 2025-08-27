<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network;

use PHPUnit\Framework\TestCase;

class HttpCacheStackTest extends TestCase
{
    public function testResetFiles(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addFile('file1');

        $this->assertNotEmpty(
            $instance->getFiles()
        );

        $instance->resetFiles();

        $this->assertEmpty(
            $instance->getFiles()
        );
    }

    public function testAddFile(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addFile('file1');

        $this->assertContains(
            'file1',
            $instance->getFiles()
        );
    }

    public function testAddFiles(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $files    = ['file1', 'file2', 'file3'];

        $instance->addFiles($files);

        $this->assertEquals(
            $files,
            $instance->getFiles()
        );
    }

    public function testGetFiles(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $files    = ['file1', 'file2'];

        $instance->addFiles($files);

        $this->assertEquals(
            $files,
            $instance->getFiles()
        );
    }

    public function testResetTimes(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addTime(1234567890);

        $this->assertNotEmpty(
            $instance->getTimes()
        );

        $instance->resetTimes();

        $this->assertEmpty(
            $instance->getTimes()
        );
    }

    public function testAddTime(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addTime(1234567890);

        $this->assertContains(
            1234567890,
            $instance->getTimes()
        );
    }

    public function testAddTimes(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $times    = [1234567890, 1234567891, 1234567892];

        $instance->addTimes($times);

        $this->assertEquals(
            $times,
            $instance->getTimes()
        );
    }

    public function testGetTimes(): void
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $times    = [1234567890, 1234567891];

        $instance->addTimes($times);

        $this->assertEquals(
            $times,
            $instance->getTimes()
        );
    }
}
