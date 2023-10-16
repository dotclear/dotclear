<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface\Core;

/**
 * @brief   Dotclear cache handler interface.
 *
 * @since   2.28
 */
interface CacheInterface
{
    /**
     * Empty templates cache directory.
     */
    public function emptyTemplatesCache(): void;

    /**
     * Empty modules store cache directory.
     */
    public function emptyModulesStoreCache(): void;

    /**
     * Reset files list.
     */
    public function resetFiles(): void;

    /**
     * Add a file to the files list.
     *
     * @param   string  $file   The file path
     */
    public function addFile(string $file): void;

    /**
     * Add files to the files list.
     *
     * @param   array<int,string>   $files  The files path to add
     */
    public function addFiles(array $files): void;

    /**
     * Get the files list.
     *
     * @return  array<int,string>   The files path
     */
    public function getFiles(): array;

    /**
     * Reset timestamps list.
     */
    public function resetTimes(): void;

    /**
     * Add a timestamp to the timestamps list.
     *
     * @param   int     $time   The timestamp
     */
    public function addTime(int $time): void;

    /**
     * Add timestamps to the timestamps list.
     *
     * @param   array<int,int>  $times  The timestamps
     */
    public function addTimes(array $times): void;

    /**
     * Get the timestamps list.
     *
     * @return  array<int,int>  The timestamps
     */
    public function getTimes(): array;
}
