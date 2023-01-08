<?php
/**
 * @class files
 * @brief Files manipulation utilities
 *
 * @package Clearbricks
 * @subpackage Common
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
class files
{
    /**
     * Default directories mode
     *
     * @var        int|null
     */
    public static $dir_mode = null;

    /**
     * MIME types
     *
     * @var        array
     */
    public static $mime_types = [
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'odp'  => 'application/vnd.oasis.opendocument.presentation',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',

        'sxw'  => 'application/vnd.sun.xml.writer',
        'sxc'  => 'application/vnd.sun.xml.calc',
        'sxi'  => 'application/vnd.sun.xml.impress',

        'ppt'  => 'application/mspowerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/msexcel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        'pdf'  => 'application/pdf',
        'ps'   => 'application/postscript',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
        'json' => 'application/json',
        'xml'  => 'application/xml',

        'bin'  => 'application/octet-stream',
        'exe'  => 'application/octet-stream',

        'bz2'  => 'application/x-bzip',
        'deb'  => 'application/x-debian-package',
        'gz'   => 'application/x-gzip',
        'jar'  => 'application/x-java-archive',
        'rar'  => 'application/rar',
        'rpm'  => 'application/x-redhat-package-manager',
        'tar'  => 'application/x-tar',
        'tgz'  => 'application/x-gtar',
        'zip'  => 'application/zip',

        'aiff' => 'audio/x-aiff',
        'ua'   => 'audio/basic',
        'mp3'  => 'audio/mpeg3',
        'mid'  => 'audio/x-midi',
        'midi' => 'audio/x-midi',
        'ogg'  => 'application/ogg',
        'ra'   => 'audio/x-pn-realaudio',
        'ram'  => 'audio/x-pn-realaudio',
        'wav'  => 'audio/x-wav',
        'wma'  => 'audio/x-ms-wma',

        'swf'  => 'application/x-shockwave-flash',
        'swfl' => 'application/x-shockwave-flash',
        'js'   => 'application/javascript',

        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'ico'  => 'image/vnd.microsoft.icon',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'jpe'  => 'image/jpeg',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'webp' => 'image/webp',
        'xbm'  => 'image/x-xbitmap',

        'css'  => 'text/css',
        'csv'  => 'text/csv',
        'html' => 'text/html',
        'htm'  => 'text/html',
        'txt'  => 'text/plain',
        'rtf'  => 'text/richtext',
        'rtx'  => 'text/richtext',

        'mpg'  => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpe'  => 'video/mpeg',
        'ogv'  => 'video/ogg',
        'viv'  => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        'mp4'  => 'video/mp4',
        'm4v'  => 'video/x-m4v',
        'flv'  => 'video/x-flv',
        'avi'  => 'video/x-msvideo',
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
     * @return array
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

        if (isset($types[$ext])) {
            return $types[$ext];
        }

        return 'application/octet-stream';
    }

    /**
     * MIME types
     *
     * Returns all defined MIME types.
     *
     * @return array
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
     * @param array        $types        New MIME types.
     */
    public static function registerMimeTypes(array $types): void
    {
        self::$mime_types = array_merge(self::$mime_types, $types);
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
            return (is_writable(dirname($filename)) && count(files::scandir($filename)) <= 2);
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
        while ($filename = readdir($current_dir)) {
            if (is_dir($directory . '/' . $filename) && ($filename != '.' && $filename != '..')) {
                if (!files::deltree($directory . '/' . $filename)) {
                    return false;
                }
            } elseif ($filename != '.' && $filename != '..') {
                if (!@unlink($directory . '/' . $filename)) {
                    return false;
                }
            }
        }
        closedir($current_dir);

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
            $path        = path::real($name, false);
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
        if (self::$dir_mode === null) {
            return (bool) @chmod($file, @fileperms(dirname($file)));
        }

        return (bool) @chmod($file, self::$dir_mode);
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
     * @param array        $file        File array as found in $_FILES
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
     * @param string        $directory    Directory name
     * @param array         $list         Contents array (leave it empty)
     *
     * @return array
     */
    public static function getDirList(string $directory, array &$list = null): array
    {
        if (!$list) {
            $list = [
                'dirs'  => [],
                'files' => [],
            ];
        }

        $exclude_list = ['.', '..', '.svn', '.git', '.hg'];

        $directory = preg_replace('|/$|', '', $directory);
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
                    files::getDirList($directory . '/' . $file, $list);
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
        $filename = preg_replace('/^[.]/u', '', text::deaccent($filename));

        return preg_replace('/[^A-Za-z0-9._-]/u', '_', $filename);
    }
}

/**
 * @class path
 * @brief Path manipulation utilities
 *
 * @package Clearbricks
 * @subpackage Common
 */
class path
{
    /**
     * Returns the real path of a file.
     *
     * If parameter $strict is true, file should exist. Returns false if
     * file does not exist.
     *
     * @param string    $filename        Filename
     * @param boolean    $strict    File should exists
     *
     * @return string|false
     */
    public static function real(string $filename, bool $strict = true)
    {
        $os = (DIRECTORY_SEPARATOR == '\\') ? 'win' : 'nix';

        # Absolute path?
        if ($os == 'win') {
            $absolute = preg_match('/^\w+:/', $filename);
        } else {
            $absolute = substr($filename, 0, 1) == '/';
        }

        # Standard path form
        if ($os == 'win') {
            $filename = str_replace('\\', '/', $filename);
        }

        # Adding root if !$_abs
        if (!$absolute) {
            $filename = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $filename;
        }

        # Clean up
        $filename = preg_replace('|/+|', '/', $filename);

        if (strlen($filename) > 1) {
            $filename = preg_replace('|/$|', '', $filename);
        }

        $prefix = '';
        if ($os == 'win') {
            [$prefix, $filename] = explode(':', $filename);
            $prefix .= ':/';
        } else {
            $prefix = '/';
        }
        $filename = substr($filename, 1);

        # Go through
        $parts = explode('/', $filename);
        $res   = [];

        for ($i = 0; $i < count($parts); $i++) {
            if ($parts[$i] == '.') {
                continue;
            }

            if ($parts[$i] == '..') {
                if (count($res) > 0) {
                    array_pop($res);
                }
            } else {
                array_push($res, $parts[$i]);
            }
        }

        $filename = $prefix . implode('/', $res);

        if ($strict && !@file_exists($filename)) {
            return false;
        }

        return $filename;
    }

    /**
     * Returns a clean file path
     *
     * @param string    $filename        File path
     *
     * @return string
     */
    public static function clean(?string $filename): string
    {
        // Remove double point (upper directory)
        $filename = preg_replace(['|^\.\.|', '|/\.\.|', '|\.\.$|'], '', (string) $filename);

        // Replace double slashes by one
        $filename = preg_replace('|/{2,}|', '/', (string) $filename);

        // Remove trailing slash
        $filename = preg_replace('|/$|', '', (string) $filename);

        return $filename;
    }

    /**
     * Path information
     *
     * Returns an array of information:
     * - dirname
     * - basename
     * - extension
     * - base (basename without extension)
     *
     * @param string    $filename        File path
     *
     * @return array
     */
    public static function info(string $filename): array
    {
        $pathinfo = pathinfo($filename);
        $res      = [];

        $res['dirname']   = (string) $pathinfo['dirname'];
        $res['basename']  = (string) $pathinfo['basename'];
        $res['extension'] = $pathinfo['extension'] ?? '';
        $res['base']      = preg_replace('/\.' . preg_quote($res['extension'], '/') . '$/', '', $res['basename']);

        return $res;
    }

    /**
     * Full path with root
     *
     * Returns a path with root concatenation unless path begins with a slash
     *
     * @param string    $path       File path
     * @param string    $root       Root path
     *
     * @return string
     */
    public static function fullFromRoot(string $path, string $root): string
    {
        if (substr($path, 0, 1) == '/') {
            return $path;
        }

        return $root . '/' . $path;
    }
}
