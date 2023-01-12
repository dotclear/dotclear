<?php
/**
 * @class filemanager
 * @brief Files management class
 *
 * @package Clearbricks
 * @subpackage Filemanager
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class filemanager
{
    /**
     * Files manager root path
     *
     * @var string
     */
    public $root;

    /**
     * Files manager root URL
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
     * Array of regexps defining excluded items
     *
     * @var        array
     */
    protected $exclude_list = [];

    /**
     * Files exclusion regexp pattern
     *
     * @var        string
     */
    protected $exclude_pattern = '';

    /**
     * Current directory content array
     *
     * @var        array
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
        $this->root     = $this->pwd     = path::real($root);
        $this->root_url = $root_url;

        if (!preg_match('#/$#', (string) $this->root_url)) {
            $this->root_url = $this->root_url . '/';
        }

        if (!$this->root) {
            throw new Exception('Invalid root directory.');
        }
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
        $realdir = path::real($this->root . '/' . path::clean($dir));
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
     *
     * @return string
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
     * Appends an exclusion to exclusions list. $f should be a regexp.
     *
     * @see $exclude_list
     *
     * @param array|string    $list            Exclusion regexp
     */
    public function addExclusion($list): void
    {
        if (is_array($list)) {
            foreach ($list as $item) {
                if (($res = path::real($item)) !== false) {
                    $this->exclude_list[] = $res;
                }
            }
        } elseif (($res = path::real($list)) !== false) {
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
     *
     * @return bool
     */
    protected function isExclude(string $path): bool
    {
        foreach ($this->exclude_list as $item) {
            if (strpos($path, (string) $item) === 0) {
                return true;
            }
        }

        return false;
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
     *
     * @return bool
     */
    protected function isFileExclude(string $file): bool
    {
        if (!$this->exclude_pattern) {
            return false;
        }

        return preg_match($this->exclude_pattern, $file);
    }

    /**
     * Item in jail
     *
     * Returns true if file or directory $path is in jail (ie. not outside the {@link $root} directory).
     *
     * @param string    $path            Path to match
     *
     * @return bool
     */
    protected function inJail(string $path): bool
    {
        $path = path::real($path);

        if ($path !== false) {
            return preg_match('|^' . preg_quote($this->root, '|') . '|', $path);
        }

        return false;
    }

    /**
     * File in files
     *
     * Returns true if file $file is in files array of {@link $dir}.
     *
     * @param string    $file            File to match
     *
     * @return bool
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
     * Directory list
     *
     * Creates list of items in working directory and append it to {@link $dir}
     *
     * @uses sortHandler(), fileItem
     */
    public function getDir(): void
    {
        $dir = path::clean($this->pwd);

        $handle = @opendir($dir);
        if ($handle === false) {
            throw new Exception('Unable to read directory.');
        }

        $directories = $files = [];

        while (($file = readdir($handle)) !== false) {
            $filename = $dir . '/' . $file;

            if ($this->inJail($filename) && !$this->isExclude($filename)) {
                if (is_dir($filename) && $file !== '.') {
                    $directory = new fileItem($filename, $this->root, $this->root_url);
                    if ($file === '..') {
                        $directory->parent = true;
                    }
                    $directories[] = $directory;
                }

                if (is_file($filename) && strpos($file, '.') !== 0 && !$this->isFileExclude($file)) {
                    $files[] = new fileItem($filename, $this->root, $this->root_url);
                }
            }
        }
        closedir($handle);

        $this->dir = [
            'dirs'  => $directories,
            'files' => $files,
        ];

        usort($this->dir['dirs'], [$this, 'sortHandler']);
        usort($this->dir['files'], [$this, 'sortHandler']);
    }

    /**
     * Root directories
     *
     * Returns an array of directory under {@link $root} directory.
     *
     * @uses fileItem
     *
     * @return array
     */
    public function getRootDirs(): array
    {
        $directories = files::getDirList($this->root);

        $res = [];
        foreach ($directories['dirs'] as $directory) {
            $res[] = new fileItem($directory, $this->root, $this->root_url);
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
     * You should first verify upload status, with {@link files::uploadStatus()}
     * or PHP native functions.
     *
     * @see files::uploadStatus()
     *
     * @param string    $tmp            Temporary uploaded file path
     * @param string    $dest           Destination file
     * @param bool      $overwrite      Overwrite mode
     *
     * @return string                Destination real path
     */
    public function uploadFile(string $tmp, string $dest, bool $overwrite = false)
    {
        $dest = $this->pwd . '/' . path::clean($dest);

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

        files::inheritChmod($dest);

        return (string) path::real($dest);
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
        $dest = $this->pwd . '/' . path::clean($name);

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
        files::inheritChmod($dest);

        return (string) path::real($dest);
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
        files::makeDir($this->pwd . '/' . path::clean($name));
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
        $src_path = $this->root . '/' . path::clean($src_path);
        $dst_path = $this->root . '/' . path::clean($dst_path);

        if (($src_path = path::real($src_path)) === false) {
            throw new Exception(__('Source file does not exist.'));
        }

        $dest_dir = path::real(dirname($dst_path));

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
        $file = (string) path::real($this->pwd . '/' . path::clean($name));

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
        $path = (string) path::real($this->pwd . '/' . path::clean($file));

        if (!$this->inJail($path)) {
            throw new Exception(__('File is not in jail.'));
        }

        if (!files::isDeletable($path)) {
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
        $path = (string) path::real($this->pwd . '/' . path::clean($directory));

        if (!$this->inJail($path)) {
            throw new Exception(__('Directory is not in jail.'));
        }

        if (!files::isDeletable($path)) {
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
     * @param fileItem    $a            fileItem object
     * @param fileItem    $b            fileItem object
     *
     * @return int
     */
    protected function sortHandler(fileItem $a, fileItem $b): int
    {
        if ($a->parent && !$b->parent || !$a->parent && $b->parent) {
            return ($a->parent) ? -1 : 1;
        }

        return strcasecmp($a->basename, $b->basename);
    }
}

/**
 * @class fileItem
 * @brief File item
 *
 * File item class used by {@link filemanager}. In this class {@link $file} could
 * be either a file or a directory.
 *
 * @package Clearbricks
 * @subpackage Filemanager
 */
class fileItem
{
    /**
     * Complete path to file
     *
     * @var string
     */
    public $file;

    /**
     * File basename
     *
     * @var string
     */
    public $basename;

    /**
     * File directory name
     *
     * @var string
     */
    public $dir;

    /**
     * File URL
     *
     * @var string
     */
    public $file_url;

    /**
     * File directory URL
     *
     * @var string
     */
    public $dir_url;

    /**
     * File extension
     *
     * @var string
     */
    public $extension;

    /**
     * File path relative to <var>$root</var> given in constructor
     *
     * @var string
     */
    public $relname;

    /**
     * Parent directory (ie. "..")
     *
     * @var        bool
     */
    public $parent = false;

    /**
     * File MimeType
     *
     * @see {@link files::getMimeType()}
     *
     * @var string
     */
    public $type;

    /**
     * File MimeType prefix
     *
     * @var string
     */
    public $type_prefix;

    /**
     * File modification timestamp
     *
     * @var int
     */
    public $mtime;

    /**
     * File size
     *
     * @var int
     */
    public $size;

    /**
     * File permissions mode
     *
     * @var int
     */
    public $mode;

    /**
     * File owner ID
     *
     * @var int
     */
    public $uid;

    /**
     * File group ID
     *
     * @var int
     */
    public $gid;

    /**
     * True if file or directory is writable
     *
     * @var bool
     */
    public $w;

    /**
     * True if file is a directory
     *
     * @var bool
     */
    public $d;

    /**
     * True if file file is executable or directory is traversable
     *
     * @var bool
     */
    public $x;

    /**
     * True if file is a file
     *
     * @var bool
     */
    public $f;

    /**
     * True if file or directory is deletable
     *
     * @var bool
     */
    public $del;

    /**
     * Constructor
     *
     * Creates an instance of fileItem object.
     *
     * @param string    $file           Absolute file or directory path
     * @param string    $root           File root path
     * @param string    $root_url       File root URL
     */
    public function __construct(string $file, ?string $root, ?string $root_url = '')
    {
        $file = path::real($file);
        $stat = stat($file);
        $path = path::info($file);

        $rel = preg_replace('/^' . preg_quote($root, '/') . '\/?/', '', (string) $file);

        // Properties
        $this->file     = $file;
        $this->basename = $path['basename'];
        $this->dir      = $path['dirname'];
        $this->relname  = $rel;

        // URL
        $this->file_url = $root_url . str_replace('%2F', '/', rawurlencode($rel));
        $this->dir_url  = dirname($this->file_url);

        // File type
        $this->extension   = $path['extension'];
        $this->type        = $this->d ? null : files::getMimeType($file);
        $this->type_prefix = preg_replace('/^(.+?)\/.+$/', '$1', (string) $this->type);

        // Filesystem infos
        $this->mtime = $stat[9];
        $this->size  = $stat[7];
        $this->mode  = $stat[2];
        $this->uid   = $stat[4];
        $this->gid   = $stat[5];

        // Flags
        $this->w   = is_writable($file);
        $this->d   = is_dir($file);
        $this->f   = is_file($file);
        $this->x   = $this->d ? file_exists($file . '/.') : false;
        $this->del = files::isDeletable($file);
    }
}
