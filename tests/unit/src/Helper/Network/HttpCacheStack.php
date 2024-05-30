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

namespace tests\unit\Dotclear\Helper\Network;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

class HttpCacheStack extends atoum
{
    public function testResetFiles()
    {
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addFile('file1'))
            ->then
                ->array($this->testedInstance->getFiles())
                    ->isNotEmpty()
            ->if($this->testedInstance->resetFiles())
            ->then
                ->array($this->testedInstance->getFiles())
                    ->isEmpty();
    }

    public function testAddFile()
    {
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addFile('file1'))
            ->then
                ->array($this->testedInstance->getFiles())
                    ->contains('file1');
    }

    public function testAddFiles()
    {
        $files = ['file1', 'file2', 'file3'];
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addFiles($files))
            ->then
                ->array($this->testedInstance->getFiles())
                    ->isEqualTo($files);
    }

    public function testGetFiles()
    {
        $files = ['file1', 'file2'];
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addFiles($files))
            ->then
                ->array($this->testedInstance->getFiles())
                    ->isEqualTo($files);
    }

    public function testResetTimes()
    {
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addTime(1234567890))
            ->then
                ->array($this->testedInstance->getTimes())
                    ->isNotEmpty()
            ->if($this->testedInstance->resetTimes())
            ->then
                ->array($this->testedInstance->getTimes())
                    ->isEmpty();
    }

    public function testAddTime()
    {
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addTime(1234567890))
            ->then
                ->array($this->testedInstance->getTimes())
                    ->contains(1234567890);
    }

    public function testAddTimes()
    {
        $times = [1234567890, 1234567891, 1234567892];
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addTimes($times))
            ->then
                ->array($this->testedInstance->getTimes())
                    ->isEqualTo($times);
    }

    public function testGetTimes()
    {
        $times = [1234567890, 1234567891];
        $this
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->addTimes($times))
            ->then
                ->array($this->testedInstance->getTimes())
                    ->isEqualTo($times);
    }
}
