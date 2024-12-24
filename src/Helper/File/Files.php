<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File;

use Dotclear\Helper\Text;
use Exception;

/**
 * @class Files
 *
 * Files manipulation utilities
 */
class Files
{
    /**
     * Default directories mode
     *
     * @var        int|null
     */
    public static $dir_mode = null;

    /**
     * Locked files resource stack.
     *
     * @var    array<string, resource>
     */
    protected static $lock_stack = [];

    /**
     * Locked files status stack.
     *
     * @var    array<string, bool>
     */
    protected static $lock_disposable = [];

    /**
     * Last lock attempt error
     *
     * @var    string
     */
    protected static $lock_error = '';

    /**
     * MIME types
     *
     * @var        array<string, string>
     */
    public static $mime_types = [

        // Open-office
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',

        // Sun
        'sxc' => 'application/vnd.sun.xml.calc',
        'sxi' => 'application/vnd.sun.xml.impress',
        'sxw' => 'application/vnd.sun.xml.writer',

        // Microsoft
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt'  => 'application/mspowerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls'  => 'application/msexcel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        // Adobe
        'ai'  => 'application/postscript',
        'eps' => 'application/postscript',
        'pdf' => 'application/pdf',
        'ps'  => 'application/postscript',

        // Data exchange
        'json' => 'application/json',
        'xml'  => 'application/xml',

        // Executable
        'bin' => 'application/octet-stream',
        'exe' => 'application/octet-stream',

        // Archive
        'bz2' => 'application/x-bzip',
        'deb' => 'application/x-debian-package',
        'gz'  => 'application/x-gzip',
        'jar' => 'application/x-java-archive',
        'rar' => 'application/rar',
        'rpm' => 'application/x-redhat-package-manager',
        'tar' => 'application/x-tar',
        'tgz' => 'application/x-gtar',
        'zip' => 'application/zip',

        // Audio
        'aac'  => 'audio/aac',
        'aiff' => 'audio/x-aiff',
        'ua'   => 'audio/basic',
        'm4a'  => 'audio/mp4',
        'mid'  => 'audio/x-midi',
        'midi' => 'audio/x-midi',
        'mp3'  => 'audio/mpeg3',
        'oga'  => 'audio/ogg',
        'ogg'  => 'audio/ogg',
        'ra'   => 'audio/x-pn-realaudio',
        'ram'  => 'audio/x-pn-realaudio',
        'wav'  => 'audio/x-wav',
        'weba' => 'audio/webm',
        'wma'  => 'audio/x-ms-wma',

        // Flash
        'swf'  => 'application/x-shockwave-flash',
        'swfl' => 'application/x-shockwave-flash',

        // Image
        'avif' => 'image/avif',
        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'ico'  => 'image/vnd.microsoft.icon',
        'jpeg' => 'image/jpeg',
        'jpe'  => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'jxl'  => 'image/jxl',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'webp' => 'image/webp',
        'xbm'  => 'image/x-xbitmap',

        // Text
        'css'  => 'text/css',
        'csv'  => 'text/csv',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'js'   => 'text/javascript',
        'mjs'  => 'text/javascript',
        'txt'  => 'text/plain',
        'rtf'  => 'text/richtext',
        'rtx'  => 'text/richtext',

        // Video
        'avi'  => 'video/x-msvideo',
        'flv'  => 'video/x-flv',
        'm4p'  => 'video/mp4',
        'm4v'  => 'video/x-m4v',
        'mov'  => 'video/quicktime',
        'mp4'  => 'video/mp4',
        'mpg'  => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpe'  => 'video/mpeg',
        'ogv'  => 'video/ogg',
        'qt'   => 'video/quicktime',
        'viv'  => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'webm' => 'video/webm',
        'wmv'  => 'video/x-ms-wmv',
    ];

    /**
     * Directory scanning
     *
     * Returns a directory child files and directories.
     *
     * @param string     $directory     Path to scan
     * @param boolean    $order         Order results
     *
     * @return array<string>
     */
    public static function scandir(string $directory, bool $order = true): array
    {
        $res    = [];
        $handle = @opendir($directory);

        if ($handle === false) {
            throw new Exception(__('Unable to open directory.'));
        }

        while (($file = readdir($handle)) !== false) {
            $res[] = $file;
        }
        closedir($handle);

        if ($order) {
            sort($res);
        }

        return $res;
    }

    /**
     * File extension
     *
     * Returns a file extension.
     *
     * @param string    $filename    File name
     *
     * @return string
     */
    public static function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * MIME type
     *
     * Returns a file MIME type, based on static var {@link $mime_types}
     *
     * @param string    $filename    File name
     *
     * @return string
     */
    public static function getMimeType(string $filename): string
    {
        $ext   = self::getExtension($filename);
        $types = self::mimeTypes();

        return $types[$ext] ?? 'application/octet-stream';
    }

    /**
     * MIME types
     *
     * Returns all defined MIME types.
     *
     * @return array<string, string>
     */
    public static function mimeTypes(): array
    {
        return self::$mime_types;
    }

    /**
     * New MIME types
     *
     * Append new MIME types to defined MIME types.
     *
     * @param array<string, string>        $types        New MIME types.
     */
    public static function registerMimeTypes(array $types): void
    {
        self::$mime_types = [...self::$mime_types, ...$types];
    }

    /**
     * Is a file or directory deletable.
     *
     * Returns true if $f is a file or directory and is deletable.
     *
     * @param string    $filename    File or directory
     *
     * @return boolean
     */
    public static function isDeletable(string $filename): bool
    {
        if (is_file($filename)) {
            return is_writable(dirname($filename));
        } elseif (is_dir($filename)) {
            return is_writable(dirname($filename)) && count(static::scandir($filename)) <= 2;
        }

        return false;
    }

    /**
     * Recursive removal
     *
     * Remove recursively a directory.
     *
     * @param string    $directory        Directory patch
     *
     * @return boolean
     */
    public static function deltree(string $directory): bool
    {
        $current_dir = opendir($directory);
        if ($current_dir !== false) {
            while ($filename = readdir($current_dir)) {
                if (is_dir($directory . '/' . $filename) && ($filename != '.' && $filename != '..')) {
                    if (!static::deltree($directory . '/' . $filename)) {
                        return false;
                    }
                } elseif ($filename != '.' && $filename != '..') {
                    if (!@unlink($directory . '/' . $filename)) {
                        return false;
                    }
                }
            }
            closedir($current_dir);
        }

        return @rmdir($directory);
    }

    /**
     * Touch file
     *
     * Set file modification time to now.
     *
     * @param string    $filename        File to change
     */
    public static function touch(string $filename): void
    {
        if (is_writable($filename)) {
            @touch($filename);
        }
    }

    /**
     * Directory creation.
     *
     * Creates directory $f. If $r is true, attempts to create needed parents
     * directories.
     *
     * @param string     $name              Directory to create
     * @param boolean    $recursive         Create parent directories
     */
    public static function makeDir(string $name, bool $recursive = false): void
    {
        if (empty($name)) {
            return;
        }

        if (DIRECTORY_SEPARATOR == '\\') {
            $name = str_replace('/', '\\', $name);
        }

        if (is_dir($name)) {
            return;
        }

        if ($recursive) {
            $path        = (string) Path::real($name, false);
            $directories = [];

            while (!is_dir($path)) {
                array_unshift($directories, basename($path));
                $path = dirname($path);
            }

            foreach ($directories as $directory) {
                $path .= DIRECTORY_SEPARATOR . $directory;
                if ($directory != '' && !is_dir($path)) {
                    self::makeDir($path);
                }
            }
        } else {
            if (@mkdir($name) === false) {
                throw new Exception(__('Unable to create directory.'));
            }
            self::inheritChmod($name);
        }
    }

    /**
     * Mode inheritage
     *
     * Sets file or directory mode according to its parent.
     *
     * @param string    $file        File to change
     */
    public static function inheritChmod(string $file): bool
    {
        if (function_exists('chmod')) {
            try {
                if (self::$dir_mode === null) {
                    $perms = fileperms(dirname($file));

                    return $perms !== false ? (bool) @chmod($file, $perms) : false;
                }

                return (bool) @chmod($file, self::$dir_mode);
            } catch (Exception) {
                // chmod and maybe fileperms functions may be disabled so catch exception and return false
            }
        }

        return false;
    }

    /**
     * Changes file content.
     *
     * Writes $f_content into $f file.
     *
     * @param string    $file       File to edit
     * @param string    $content    Content to write
     *
     * @return bool
     */
    public static function putContent(string $file, string $content): bool
    {
        if (file_exists($file) && !is_writable($file)) {
            throw new Exception(__('File is not writable.'));
        }

        $handle = @fopen($file, 'w');

        if ($handle === false) {
            throw new Exception(__('Unable to open file.'));
        }

        fwrite($handle, $content, strlen($content));
        fclose($handle);

        return true;
    }

    /**
     * Human readable file size.
     *
     * @param integer    $size        Bytes
     *
     * @return string
     */
    public static function size(int $size): string
    {
        $kb = 1024;
        $mb = 1024 * $kb;
        $gb = 1024 * $mb;
        $tb = 1024 * $gb;

        if ($size < $kb) {
            return $size . ' B';
        } elseif ($size < $mb) {
            return round($size / $kb, 2) . ' KB';
        } elseif ($size < $gb) {
            return round($size / $mb, 2) . ' MB';
        } elseif ($size < $tb) {
            return round($size / $gb, 2) . ' GB';
        }

        return round($size / $tb, 2) . ' TB';
    }

    /**
     * Converts a human readable file size to bytes.
     *
     * @param string    $size            Size
     *
     * @return float
     */
    public static function str2bytes(string $size): float
    {
        $size = trim($size);
        $last = strtolower(substr($size, -1, 1));
        $size = (float) substr($size, 0, -1);
        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    /**
     * Upload status
     *
     * Returns true if upload status is ok, throws an exception instead.
     *
     * @param array<string, array<string, mixed>>        $file        File array as found in $_FILES
     *
     * @return boolean
     */
    public static function uploadStatus(array $file): bool
    {
        if (!isset($file['error'])) {
            throw new Exception(__('Not an uploaded file.'));
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                return true;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception(__('The uploaded file exceeds the maximum file size allowed.'));
            case UPLOAD_ERR_PARTIAL:
                throw new Exception(__('The uploaded file was only partially uploaded.'));
            case UPLOAD_ERR_NO_FILE:
                throw new Exception(__('No file was uploaded.'));
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception(__('Missing a temporary folder.'));
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception(__('Failed to write file to disk.'));
            case UPLOAD_ERR_EXTENSION:
                throw new Exception(__('A PHP extension stopped the file upload.'));
            default:
                return true;
        }
    }

    # Packages generation methods
    #

    /**
     * Recursive directory scanning
     *
     * Returns an array of a given directory's content. The array contains two arrays: dirs and files.
     * Directory's content is fetched recursively.
     *
     * @param string                                $directory    Directory name
     * @param array<string, array<string>>|null     $list         Contents array (leave it empty)
     *
     * @return array<string, array<string>>|null
     */
    public static function getDirList(string $directory, ?array &$list = null): ?array
    {
        if (!$list) {
            $list = [
                'dirs'  => [],
                'files' => [],
            ];
        }

        $exclude_list = ['.', '..', '.svn', '.git', '.hg'];

        $directory = (string) preg_replace('|/$|', '', $directory);
        if (!is_dir($directory)) {
            throw new Exception(sprintf(__('%s is not a directory.'), $directory));
        }

        $list['dirs'][] = $directory;

        $handle = @dir($directory);
        if ($handle === false) {
            throw new Exception(__('Unable to open directory.'));
        }

        while ($file = $handle->read()) {
            if (!in_array($file, $exclude_list)) {
                if (is_dir($directory . '/' . $file)) {
                    static::getDirList($directory . '/' . $file, $list);
                } else {
                    $list['files'][] = $directory . '/' . $file;
                }
            }
        }
        $handle->close();

        return $list;
    }

    /**
     * Filename cleanup
     *
     * Removes unwanted characters in a filename.
     *
     * @param string    $filename        Filename
     *
     * @return string
     */
    public static function tidyFileName(string $filename): string
    {
        $filename = (string) preg_replace('/^[.]/u', '', Text::deaccent($filename));

        return (string) preg_replace('/[^A-Za-z0-9._-]/u', '_', $filename);
    }

    /**
     * Lock file.
     *
     * @param   string  $file           The file path
     * @param   bool    $disposable     File only use to lock
     *
     * @return null|string    Clean file path on success, empty string on error, null if already locked
     */
    public static function lock(string $file, bool $disposable = false): ?string
    {
        # Real path
        $file = Path::real($file, false);
        if (false === $file) {
            self::$lock_error = __("Can't get file path");

            return '';
        }

        # not a dir
        if (is_dir($file)) {
            self::$lock_error = __("Can't lock a directory");

            return '';
        }

        # already marked as locked
        if (isset(self::$lock_stack[$file]) || $disposable && file_exists($file)) {
            return null;
        }

        # Need flock function
        if (!function_exists('flock')) {
            self::$lock_error = __("Can't call php function named flock");

            return '';
        }

        # Make dir
        if (!is_dir(dirname($file))) {
            Files::makeDir(dirname($file), true);
        }

        # Open new file
        if (!file_exists($file)) {
            $resource = @fopen($file, 'w');
            if ($resource === false) {
                self::$lock_error = __("Can't create file");

                return '';
            }
            fwrite($resource, '1', strlen('1'));
        } else {
            # Open existsing file
            $resource = @fopen($file, 'r+');
            if ($resource === false) {
                self::$lock_error = __("Can't open file");

                return '';
            }
        }

        # Lock file
        if (!flock($resource, LOCK_EX | LOCK_NB)) {
            self::$lock_error = __("Can't lock file");

            return '';
        }

        self::$lock_stack[$file]      = $resource;
        self::$lock_disposable[$file] = $disposable;

        return $file;
    }

    /**
     * Unlock file.
     *
     * @param   string  $file           The file to unlock
     */
    public static function unlock(string $file): void
    {
        if (isset(self::$lock_stack[$file])) {
            fclose(self::$lock_stack[$file]);
            if (self::$lock_disposable[$file] && file_exists($file)) {
                if (@unlink($file) === false) {
                    throw new Exception(__('File cannot be removed.'));
                }
            }
            unset(
                self::$lock_stack[$file],
                self::$lock_disposable[$file]
            );
        }
    }

    /**
     * Gets the lock handle.
     *
     * @param      string  $file   The file
     *
     * @return     resource|null  The lock handle.
     */
    public static function getLockHandle(string $file)
    {
        return self::$lock_stack[$file] ?? null;
    }

    /**
     * Get last error from lock method.
     *
     * @return  string  The last lock error
     */
    public static function getlastLockError(): string
    {
        return self::$lock_error;
    }
}
