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
 * Files management class
 */
class Manager
{
    /**
     * Files manager root path
     *
     * @deprecated since 2.28, use self::getRoot() instead
     *
     * @var string
     */
    public $root;

    /**
     * Files manager root URL
     *
     * @deprecated since 2.28, use self::getRootUrl() instead
     *
     * @var string
     */
    public $root_url;

    /**
     * Working (current) directory
     *
     * @var string
     */
    protected $pwd;

    /**
     * Array of excluded items (path beginning with)
     *
     * @var        array<string>
     */
    protected $exclude_list = [];

    /**
     * Files exclusion regexp pattern
     *
     * @var        string
     */
    protected $exclude_pattern = '';

    /**
     * Files exclusion regexp pattern list
     *
     * @var        array<string>
     */
    protected $exclude_pattern_list = [];

    /**
     * Current directory content array
     *
     * @deprecated since 2.28, use self::getDirs() or self::getFiles();
     *
     * @var        array<string, array<File>>
     */
    public $dir = [
        'dirs'  => [],
        'files' => [],
    ];

    /**
     * Constructor
     *
     * New filemanage istance. Note that filemanage is a jail in given root
     * path. You won't be able to access files outside {@link $root} path with
     * the object's methods.
     *
     * @param string    $root           Root path
     * @param string    $root_url       Root URL
     */
    public function __construct(?string $root, ?string $root_url = '')
    {
        $this->root     = $this->pwd = (string) Path::real((string) $root);
        $this->root_url = (string) $root_url;

        if (!preg_match('#/$#', $this->root_url)) {
            $this->root_url .= '/';
        }

        if ($this->root === '') {
            throw new Exception('Invalid root directory.');
        }
    }

    /**
     * Get current root path.
     *
     * @return  string The current root path
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Get current root public URL.
     *
     * @return  string The current root URL
     */
    public function getRootUrl(): string
    {
        return $this->root_url;
    }

    /**
     * Change directory
     *
     * Changes working directory. $dir is relative to instance {@link $root}
     * directory.
     *
     * @param string    $dir            Directory
     */
    public function chdir(?string $dir): void
    {
        $realdir = Path::real($this->root . '/' . Path::clean($dir));
        if (!$realdir || !is_dir($realdir)) {
            throw new Exception('Invalid directory.');
        }

        if ($this->isExclude($realdir)) {
            throw new Exception('Directory is excluded.');
        }

        $this->pwd = $realdir;
    }

    /**
     * Get working directory
     *
     * Returns working directory path.
     */
    public function getPwd(): string
    {
        return (string) $this->pwd;
    }

    /**
     * Current directory is writable
     *
     * @return bool    true if working directory is writable
     */
    public function writable(): bool
    {
        if (!$this->pwd) {
            return false;
        }

        return is_writable($this->pwd);
    }

    /**
     * Add exclusion
     *
     * Appends an exclusion to exclusions list.
     *
     * @see $exclude_list
     *
     * @param array<string>|string    $list            Exclusion regexp
     */
    public function addExclusion($list): void
    {
        if (is_array($list)) {
            foreach ($list as $item) {
                if (($res = Path::real($item)) !== false) {
                    $this->exclude_list[] = $res;
                }
            }
        } elseif (($res = Path::real($list)) !== false) {
            $this->exclude_list[] = $res;
        }
    }

    /**
     * Path is excluded
     *
     * Returns true if path (file or directory) $path is excluded. $path is
     * relative to {@link $root} path.
     *
     * @see $exclude_list
     *
     * @param string    $path            Path to match
     */
    protected function isExclude(string $path): bool
    {
        foreach ($this->exclude_list as $item) {
            if (str_starts_with($path, (string) $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add an exclude pattern.
     *
     * @param      string  $pattern  The regexp pattern
     */
    public function addExcludePattern(string $pattern): void
    {
        $this->exclude_pattern_list[] = $pattern;
    }

    /**
     * Set an exclude pattern.
     *
     * @param      string  $pattern  The regexp pattern
     *
     * @deprecated use addExcludePattern() instead
     */
    public function setExcludePattern(string $pattern): void
    {
        $this->addExcludePattern($pattern);
    }

    /**
     * File is excluded
     *
     * Returns true if file $file is excluded. $file is relative to {@link $root}
     * path.
     *
     * @see $exclude_pattern
     *
     * @param string    $file            File to match
     */
    protected function isFileExclude(string $file): bool
    {
        if ($this->exclude_pattern === '' && $this->exclude_pattern_list === []) {
            return false;
        }

        if ($this->exclude_pattern !== '' && (bool) preg_match($this->exclude_pattern, $file)) {
            return true;
        }

        foreach ($this->exclude_pattern_list as $pattern) {
            if ((bool) preg_match($pattern, $file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Item in jail
     *
     * Returns true if file or directory $path is in jail (ie. not outside the {@link $root} directory).
     *
     * @param string    $path            Path to match
     */
    protected function inJail(string $path): bool
    {
        $path = Path::real($path);

        if ($path !== false) {
            return (bool) preg_match('|^' . preg_quote($this->root, '|') . '|', $path);
        }

        return false;
    }

    /**
     * File in files
     *
     * Returns true if file $file is in files array of {@link $dir}.
     *
     * @param string    $file            File to match (relative to root)
     */
    public function inFiles(string $file): bool
    {
        foreach ($this->dir['files'] as $item) {
            if ($item->relname === $file) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current dirs.
     *
     * @return array<int,File>
     */
    public function getDirs(): array
    {
        return $this->dir['dirs'];
    }

    /**
     * Get current dirs.
     *
     * @return array<int,File>
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
     * @uses sortHandler(), File
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
                            $directory = new File($fullname, $this->root, $this->root_url);
                            if ($filename === '..') {
                                $directory->parent = true;
                            }
                            $directories[] = $directory;
                        }
                    } elseif (!str_starts_with($filename, '.') && !$this->isFileExclude($filename)) {
                        $files[] = new File($fullname, $this->root, $this->root_url);
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
            usort($this->dir['dirs'], $this->sortHandler(...));
        }
        if ($sort_files) {
            usort($this->dir['files'], $this->sortHandler(...));
        }
    }

    /**
     * Root directories
     *
     * Returns an array of directory under {@link $root} directory.
     *
     * @uses File
     *
     * @return array<File>
     */
    public function getRootDirs(): array
    {
        $directories = Files::getDirList($this->root);

        $res = [];
        if ($directories) {
            foreach ($directories['dirs'] as $directory) {
                $res[] = new File($directory, $this->root, $this->root_url);
            }
        }

        return $res;
    }

    /**
     * Upload file
     *
     * Move <var>$tmp</var> file to its final destination <var>$dest</var> and
     * returns the destination file path.
     *
     * <var>$dest</var> should be in jail. This method will throw exception
     * if the file cannot be written.
     *
     * You should first verify upload status, with {@link Files::uploadStatus()}
     * or PHP native functions.
     *
     * @see Files::uploadStatus()
     *
     * @param string    $tmp            Temporary uploaded file path
     * @param string    $dest           Destination file
     * @param bool      $overwrite      Overwrite mode
     *
     * @return string                Destination real path
     */
    public function uploadFile(string $tmp, string $dest, bool $overwrite = false): string
    {
        $dest = $this->pwd . '/' . Path::clean($dest);

        if ($this->isFileExclude($dest)) {
            throw new Exception(__('Uploading this file is not allowed.'));
        }

        if (!$this->inJail(dirname($dest))) {
            throw new Exception(__('Destination directory is not in jail.'));
        }

        if (!$overwrite && file_exists($dest)) {
            throw new Exception(__('File already exists.'));
        }

        if (!is_writable(dirname($dest))) {
            throw new Exception(__('Cannot write in this directory.'));
        }

        if (@move_uploaded_file($tmp, $dest) === false) {
            throw new Exception(__('An error occurred while writing the file.'));
        }

        Files::inheritChmod($dest);

        return (string) Path::real($dest);
    }

    /**
     * Upload file by bits
     *
     * Creates a new file <var>$name</var> with contents of <var>$bits</var> and
     * return the destination file path.
     *
     * <var>$name</var> should be in jail. This method will throw exception
     * if file cannot be written.
     *
     * @param string    $name        Destination file
     * @param string    $bits        Destination file content
     *
     * @return string                Destination real path
     */
    public function uploadBits(string $name, string $bits): string
    {
        $dest = $this->pwd . '/' . Path::clean($name);

        if ($this->isFileExclude($dest)) {
            throw new Exception(__('Uploading this file is not allowed.'));
        }

        if (!$this->inJail(dirname($dest))) {
            throw new Exception(__('Destination directory is not in jail.'));
        }

        if (!is_writable(dirname($dest))) {
            throw new Exception(__('Cannot write in this directory.'));
        }

        $fp = @fopen($dest, 'wb');
        if ($fp === false) {
            throw new Exception(__('An error occurred while writing the file.'));
        }

        fwrite($fp, $bits);
        fclose($fp);
        Files::inheritChmod($dest);

        return (string) Path::real($dest);
    }

    /**
     * New directory
     *
     * Creates a new directory relative to working directory.
     *
     * @param string    $name            Directory name
     */
    public function makeDir(?string $name): void
    {
        Files::makeDir($this->pwd . '/' . Path::clean($name));
    }

    /**
     * Move file
     *
     * Moves a file to a new destination. Both paths are relative to {@link $root}.
     *
     * @param string    $src_path            Source file path
     * @param string    $dst_path            Destination file path
     */
    public function moveFile(?string $src_path, ?string $dst_path): void
    {
        $src_path = $this->root . '/' . Path::clean($src_path);
        $dst_path = $this->root . '/' . Path::clean($dst_path);

        if (($src_path = Path::real($src_path)) === false) {
            throw new Exception(__('Source file does not exist.'));
        }

        $dest_dir = (string) Path::real(dirname($dst_path));

        if (!$this->inJail($src_path)) {
            throw new Exception(__('File is not in jail.'));
        }
        if (!$this->inJail($dest_dir)) {
            throw new Exception(__('File is not in jail.'));
        }

        if (!is_writable($dest_dir)) {
            throw new Exception(__('Destination directory is not writable.'));
        }

        if (@rename($src_path, $dst_path) === false) {
            throw new Exception(__('Unable to rename file.'));
        }
    }

    /**
     * Remove item
     *
     * Removes a file or directory which is relative to working directory.
     *
     * @param string    $name            Item to remove
     */
    public function removeItem(?string $name): void
    {
        $file = (string) Path::real($this->pwd . '/' . Path::clean($name));

        if (is_file($file)) {
            $this->removeFile($name);
        } elseif (is_dir($file)) {
            $this->removeDir($name);
        }
    }

    /**
     * Remove item
     *
     * Removes a file which is relative to working directory.
     *
     * @param string    $file            File to remove
     */
    public function removeFile(?string $file): void
    {
        $path = (string) Path::real($this->pwd . '/' . Path::clean($file));

        if (!$this->inJail($path)) {
            throw new Exception(__('File is not in jail.'));
        }

        if (!Files::isDeletable($path)) {
            throw new Exception(__('File cannot be removed.'));
        }

        if (@unlink($path) === false) {
            throw new Exception(__('File cannot be removed.'));
        }
    }

    /**
     * Remove item
     *
     * Removes a directory which is relative to working directory.
     *
     * @param string    $directory            Directory to remove
     */
    public function removeDir(?string $directory): void
    {
        $path = (string) Path::real($this->pwd . '/' . Path::clean($directory));

        if (!$this->inJail($path)) {
            throw new Exception(__('Directory is not in jail.'));
        }

        if (!Files::isDeletable($path)) {
            throw new Exception(__('Directory cannot be removed.'));
        }

        if (@rmdir($path) === false) {
            throw new Exception(__('Directory cannot be removed.'));
        }
    }

    /**
     * SortHandler
     *
     * This method is called by {@link getDir()} to sort files. Can be overrided
     * in inherited classes.
     *
     * @param File    $a            File object
     * @param File    $b            File object
     */
    protected function sortHandler(File $a, File $b): int
    {
        if ($a->parent && !$b->parent || !$a->parent && $b->parent) {
            return ($a->parent) ? -1 : 1;
        }

        return strcasecmp($a->basename, $b->basename);
    }
}
