<?php
/**
 * @class HttpCacheStack
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network;

class HttpCacheStack
{
    /** @var    array<int,string>   The files path stack */
    private array $files = [];

    /** @var    array<int,int>  The timestamps stack */
    private array $times = [];

    /**
     * Reset files list.
     */
    public function resetFiles(): void
    {
        $this->files = [];
    }

    /**
     * Add a file to the files list.
     *
     * @param   string  $file   The file path
     */
    public function addFile(string $file): void
    {
        $this->files[] = $file;
    }

    /**
     * Add files to the files list.
     *
     * @param   array<int,string>   $files  The files path to add
     */
    public function addFiles(array $files): void
    {
        foreach($files as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Get the files list.
     *
     * @return  array<int,string>   The files path
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Reset timestamps list.
     */
    public function resetTimes(): void
    {
        $this->times = [];
    }

    /**
     * Add a timestamp to the timestamps list.
     *
     * @param   int     $time   The timestamp
     */
    public function addTime(int $time): void
    {
        $this->times[] = (string) $time;
    }

    /**
     * Add timestamps to the timestamps list.
     *
     * @param   array<int,int>  $times  The timestamps
     */
    public function addTimes(array $times): void
    {
        foreach($times as $time) {
            $this->addTime($time);
        }
    }

    /**
     * Get the timestamps list.
     *
     * @return  array<int,int>  The timestamps
     */
    public function getTimes(): array
    {
        return $this->times;
    }
}