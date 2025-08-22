<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network;

use PHPUnit\Framework\TestCase;

class HttpCacheStackTest extends TestCase
{
    public function testResetFiles()
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

    public function testAddFile()
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addFile('file1');

        $this->assertContains(
            'file1',
            $instance->getFiles()
        );
    }

    public function testAddFiles()
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $files    = ['file1', 'file2', 'file3'];

        $instance->addFiles($files);

        $this->assertEquals(
            $files,
            $instance->getFiles()
        );
    }

    public function testGetFiles()
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $files    = ['file1', 'file2'];

        $instance->addFiles($files);

        $this->assertEquals(
            $files,
            $instance->getFiles()
        );
    }

    public function testResetTimes()
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

    public function testAddTime()
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();

        $instance->addTime(1234567890);

        $this->assertContains(
            1234567890,
            $instance->getTimes()
        );
    }

    public function testAddTimes()
    {
        $instance = new \Dotclear\Helper\Network\HttpCacheStack();
        $times    = [1234567890, 1234567891, 1234567892];

        $instance->addTimes($times);

        $this->assertEquals(
            $times,
            $instance->getTimes()
        );
    }

    public function testGetTimes()
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
