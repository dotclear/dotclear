<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File;

use DirectoryIterator;
use Exception;

/**
 * @class Manager
 *
 * Media files management class
 */
class MediaManager extends Manager
{
    /**
     * Current directory content array
     *
     * @var        array<string, MediaFile[]>    $dir
     */
    // @phpstan-ignore property.phpDocType
    protected $dir = [
        'dirs'  => [],
        'files' => [],
    ];

    /**
     * Get current dirs.
     *
     * @return MediaFile[]
     */
    public function getDirs(): array
    {
        return $this->dir['dirs'];
    }

    /**
     * Get current dirs.
     *
     * @return MediaFile[]
     */
    public function getFiles(): array
    {
        return $this->dir['files'];
    }

    /**
     * Directory list
     *
     * Creates list of items in working directory and append it to {@link $dir}
     *
     * @uses Dotclear\Helper\File\Manager::sortHandler()
     * @uses Dotclear\Helper\Helper\MediaFile
     */
    public function getDir(bool $sort_dirs = true, bool $sort_files = true): void
    {
        $dir         = Path::clean($this->pwd);
        $directories = [];
        $files       = [];

        try {
            $dirfiles = new DirectoryIterator($dir);
            foreach ($dirfiles as $file) {
                $fullname = $file->getPathname();
                if ($this->inJail($fullname) && !$this->isExclude($fullname)) {
                    $filename = $file->getFilename();
                    if ($file->isDir()) {
                        if ($filename !== '.') {
                            $directory = new MediaFile($fullname, $this->root, $this->root_url);
                            if ($filename === '..') {
                                $directory->parent = true;
                            }
                            $directories[] = $directory;
                        }
                    } elseif (!str_starts_with($filename, '.') && !$this->isFileExclude($filename)) {
                        $files[] = new MediaFile($fullname, $this->root, $this->root_url);
                    }
                }
            }
        } catch (Exception) {
            throw new Exception('Unable to read directory.');
        }

        $this->dir = [
            'dirs'  => $directories,
            'files' => $files,
        ];

        if ($sort_dirs) {
            usort($this->dir['dirs'], $this->sortMediaHandler(...));
        }
        if ($sort_files) {
            usort($this->dir['files'], $this->sortMediaHandler(...));
        }
    }

    /**
     * Root directories
     *
     * Returns an array of directory under {@link $root} directory.
     *
     * @uses MediaFile
     *
     * @return MediaFile[]
     */
    public function getRootDirs(): array
    {
        $directories = Files::getDirList($this->root);

        $res = [];
        if ($directories) {
            foreach ($directories['dirs'] as $directory) {
                $res[] = new MediaFile($directory, $this->root, $this->root_url);
            }
        }

        return $res;
    }

    /**
     * SortHandler
     *
     * This method is called by {@link getDir()} to sort files.
     *
     * @param MediaFile    $a            MediaFile object
     * @param MediaFile    $b            MediaFile object
     */
    protected function sortMediaHandler(MediaFile $a, MediaFile $b): int
    {
        if ($a->parent && !$b->parent || !$a->parent && $b->parent) {
            return ($a->parent) ? -1 : 1;
        }

        return strcasecmp($a->basename, $b->basename);
    }
}
