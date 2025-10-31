<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
     * Empty templates cache directory.
     */
    public function emptyFeedsCache(): void;

    /**
     * Empty modules store cache directory.
     */
    public function emptyModulesStoreCache(): void;

    /**
     * Empty Dotclear versions cache directory.
     */
    public function emptyDotclearVersionsCache(): void;

    /**
     * Avoid browser cache usage.
     */
    public function setAvoidCache(bool $avoid): void;

    /**
     * Check if browser cache usage is avoid.
     *
     * @return  bool    True if browser cache usage is avoid
     */
    public function isAvoidCache(): bool;

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
     * @param   string[]   $files  The files path to add
     */
    public function addFiles(array $files): void;

    /**
     * Get the files list.
     *
     * @return  string[]   The files path
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
     * @param   int[]  $times  The timestamps
     */
    public function addTimes(array $times): void;

    /**
     * Get the timestamps list.
     *
     * @return  int[]  The timestamps
     */
    public function getTimes(): array;
}
