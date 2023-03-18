<?php
/**
 * @class Unzip
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Zip;

use Exception;
use files;

class Unzip
{
    // Constants

    /**
     * Flag to use a specific archive workflow
     *
     * @var        int
     */
    public const USE_PHARDATA   = 0;
    public const USE_ZIPARCHIVE = 1;
    public const USE_LEGACY     = 2;

    // Default workflow
    public const USE_DEFAULT = self::USE_ZIPARCHIVE;

    /**
     * @var string Prefix for temporary archive directory
     */
    public const TMP_DIR = 'dc-temp-unzip';

    // Properties

    /**
     * @var string Archive filename
     */
    protected ?string $archive;

    /**
     * @var bool True if archive has been closed
     */
    protected bool $closed = false;

    /**
     * @var int Type of archive used
     */
    protected int $workflow = self::USE_ZIPARCHIVE;

    /**
     * @var array Manifest of archive (may be filtered)
     */
    protected $manifest = [];

    /**
     * $var PharData|ZipArchive archive object
     */
    protected $zip;

    /**
     * @var string Exclusion pattern
     */
    protected $exclude = '';

    /**
     * @var string Root folder if not root specified in archive
     */
    protected $rootdir = null;

    // Legacy

    protected $fp           = null;
    protected $eo_central   = [];
    protected $zip_sig      = "\x50\x4b\x03\x04"; # local file header signature
    protected $dir_sig      = "\x50\x4b\x01\x02"; # central dir header signature
    protected $dir_sig_e    = "\x50\x4b\x05\x06"; # end of central dir signature
    protected $memory_limit = null;

    /**
     * Constructs a new instance.
     *
     * @param      string  $archive   The archive filename
     * @param      int     $workflow  Specify the workflow to be used (phardata, ziparchive, legacy)
     */
    public function __construct(string $archive, int $workflow = self::USE_ZIPARCHIVE)
    {
        $this->workflow = $this->checkWorkflow($workflow);
        $this->archive  = $archive;

        switch ($this->workflow) {
            case self::USE_PHARDATA:
                $this->zip = new \PharData(
                    $archive,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::CURRENT_AS_FILEINFO,
                    null,
                    \Phar::ZIP
                );

                break;
            case self::USE_ZIPARCHIVE:
                $this->zip = new \ZipArchive();
                if ($this->zip->open($archive) !== true) {
                    unset($this->zip);

                    throw new Exception('Unable to open file.');
                }

                break;
            case self::USE_LEGACY:
                break;
        }
    }

    /**
     * Check required workflow
     *
     * @param      int|null   $workflow  The workflow to be checked
     *
     * @return     int   the effective workflow to be used
     */
    protected function checkWorkflow(?int $workflow): int
    {
        // Check validity of workflow
        if ($workflow === null || !in_array($workflow, [
            self::USE_LEGACY,
            self::USE_PHARDATA,
            self::USE_ZIPARCHIVE,
        ])) {
            // Unknown or null workflow, use default
            $workflow = self::USE_DEFAULT;
        }

        switch ($workflow) {
            case self::USE_PHARDATA:
                // Check if we can use PharData zip archive
                if ($this->checkPharData()) {
                    // We will use a PharData archive
                    return $workflow;
                }
                // Lets try ZipArchive
                if ($this->checkZipArchive()) {
                    // We will use a ZipArchive archive
                    return self::USE_ZIPARCHIVE;
                }

                break;
            case self::USE_ZIPARCHIVE:
                // Check if we can use ZipArchive zip archive
                if ($this->checkZipArchive()) {
                    // We will use a ZipArchive archive
                    return $workflow;
                }
                // Lets try PharData
                if ($this->checkPharData()) {
                    // We will use a PharData archive
                    return self::USE_PHARDATA;
                }

                break;
            case self::USE_LEGACY:

                break;
        }

        // Fallback to legacy
        return self::USE_LEGACY;
    }

    /**
     * Check if PharData archive may be used
     *
     * @return     bool
     */
    protected function checkPharData(): bool
    {
        return class_exists('PharData');
    }

    /**
     * Check if ZipArchive archive may be used
     *
     * @return     bool
     */
    protected function checkZipArchive(): bool
    {
        return class_exists('ZipArchive');
    }

    /**
     * Destroys the object.
     */
    public function __destruct()
    {
        // Close the archive if necessary
        if (!$this->closed) {
            $this->close();
        }
    }

    /**
     * Close the archive
     */
    public function close()
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        switch ($this->workflow) {
            case self::USE_PHARDATA:
                if ($this->zip) {
                    // No need to close archive
                    unset($this->zip);
                }

                break;
            case self::USE_ZIPARCHIVE:
                if ($this->zip) {
                    $this->zip->close();
                    unset($this->zip);
                }

                break;
            case self::USE_LEGACY:
                if ($this->fp) {
                    fclose($this->fp);
                    $this->fp = null;
                }
                if ($this->memory_limit) {
                    ini_set('memory_limit', $this->memory_limit);
                }

                break;
        }
    }

    /**
     * Gets the list.
     *
     * @param      bool|string  $stop_on_file  The stop on file
     * @param      bool|string  $exclude       The exclude
     *
     * @return     array|false  The list.
     */
    public function getList($stop_on_file = false, $exclude = false)
    {
        if (!empty($this->manifest)) {
            return $this->manifest;
        }

        switch ($this->workflow) {
            case self::USE_PHARDATA:
                $this->getListPharArchive(null, $stop_on_file, $exclude);

                break;

            case self::USE_ZIPARCHIVE:
                for ($i = 0; $i < $this->zip->numFiles; $i++) {
                    $stats    = $this->convertZipArchiveStats($this->zip->statIndex($i));
                    $filename = $stats['file_name'];
                    if ($exclude !== false && preg_match($exclude, (string) $filename)) {
                        continue;
                    }
                    // Add item to manifest
                    $this->manifest[$filename] = $stats;

                    // Check if we must stop
                    if ($stop_on_file && strtolower($stop_on_file) === strtolower($filename)) {
                        break;
                    }
                }

                break;

            case self::USE_LEGACY:
                if (!$this->loadFileListByEOF($stop_on_file, $exclude) && !$this->loadFileListBySignatures($stop_on_file, $exclude)) {
                    return false;
                }

                break;
        }

        return $this->manifest;
    }

    /**
     * Gets the list of a Phar archive.
     *
     * @param      null|string  $directory     The directory
     * @param      bool|string  $stop_on_file  The stop on file
     * @param      bool|string  $exclude       The exclude
     */
    protected function getListPharArchive(?string $directory = null, $stop_on_file = false, $exclude = false)
    {
        $list = $directory === null ? new \PharData($this->archive) : new \PharData($directory);
        foreach ($list as $file) {
            // Get relative path
            $path = substr($file->getPathname(), strlen('phar://' . realpath($this->archive)));

            // Get archive root dir if necessary
            if ($directory === null && $this->rootdir === null) {
                $rootdir = realpath(substr($file->getPathinfo()->getPath(), strlen('phar://')));
                if (strpos($rootdir, $path) !== false) {
                    // The archive has no root dir, keep the calculate one
                    $this->rootdir = $rootdir;
                } else {
                    // The archive has a root dir
                    $this->rootdir = '';
                }
            }

            if ($this->rootdir === '' || substr($path, strlen($this->rootdir)) !== '') {
                // Get infos
                $stats = [
                    'file_name'         => $this->cleanFileName($path) . ($file->isDir() ? DIRECTORY_SEPARATOR : ''),
                    'is_dir'            => $file->isDir(),
                    'uncompressed_size' => $file->getSize(),
                    'lastmod_datetime'  => $file->getMTime(),
                ];
                $filename = $stats['file_name'];

                // Check if file excluded
                if ($exclude !== false && preg_match($exclude, (string) $filename)) {
                    continue;
                }

                // Add file info
                $this->manifest[$filename] = $stats;

                // Check if we must stop
                if ($stop_on_file && strtolower($stop_on_file) === strtolower($filename)) {
                    break;
                }
            }

            // Recursive list if it is a directory
            if ($file->isDir()) {
                $this->getListPharArchive($file->getPathname(), $stop_on_file, $exclude);
            }
        }
    }

    /**
     * Convert ZipArchive file stats to legacy stats
     *
     * @param      array  $stats  The statistics
     *
     * @return     array
     */
    protected function convertZipArchiveStats(array $stats): array
    {
        return [
            'file_name'          => $this->cleanFileName($stats['name']),
            'is_dir'             => substr($stats['name'], -1, 1) == '/',
            'uncompressed_size'  => $stats['size'],
            'lastmod_datetime'   => $stats['mtime'],
            'compressed_size'    => $stats['comp_size'],
            'compression_method' => $stats['comp_method'],
        ];
    }

    /**
     * Gets the manifest of the archive (getList() must be called first).
     *
     * @return     array  The manifest.
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * Gets the archive type.
     *
     * @return     int   The archive type.
     */
    public function getWorkflow(): int
    {
        return $this->workflow;
    }

    /**
     * Unzip all from archive
     *
     * @param      string|bool  $target  The target (or false)
     */
    public function unzipAll($target)
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        foreach ($this->manifest as $k => $v) {
            if ($v['is_dir']) {
                continue;
            }

            $this->unzip($k, $target === false ? $target : $target . '/' . $k, $target !== false ? $target : '');
        }
    }

    /**
     * Unzip a file from archive
     *
     * @param      string       $file_name  The file name
     * @param      bool|string  $target     The target
     * @param      string       $folder     The base folder
     *
     * @throws     Exception
     *
     * @return     mixed
     */
    public function unzip($file_name, $target = false, string $folder = '')
    {
        if ($target !== false && $folder === '') {
            $folder = dirname($target);
        }

        if (empty($this->manifest)) {
            $this->getList($file_name);
        }

        if (!isset($this->manifest[$file_name])) {
            throw new Exception(sprintf(__('File %s is not compressed in the zip.'), $file_name));
        }
        if ($this->isFileExcluded($file_name)) {
            return;
        }
        $details = &$this->manifest[$file_name];

        if ($details['is_dir']) {
            throw new Exception(sprintf(__('Trying to unzip a folder name %s'), $file_name));
        }

        if ($target) {
            $this->testTargetDir(dirname($target));
        }

        switch ($this->workflow) {
            case self::USE_PHARDATA:
                // If no target, extract if to a temporary directory
                if ($target === false) {
                    // Use a temporary directory
                    $output = realpath(sys_get_temp_dir()) . self::TMP_DIR;
                    $this->testTargetDir($output);
                    $this->zip->extractTo($output, $file_name, true);

                    return file_get_contents(implode(DIRECTORY_SEPARATOR, [$output, $file_name]));
                }
                $this->zip->extractTo($folder, $file_name, true);
                files::inheritChmod($target);

                break;

            case self::USE_ZIPARCHIVE:
                // If no target, extract if to a temporary directory
                if ($target === false) {
                    // Use a temporary directory
                    $output = realpath(sys_get_temp_dir()) . self::TMP_DIR;
                    $this->testTargetDir($output);
                    $this->zip->extractTo($output, $file_name);

                    return file_get_contents(implode(DIRECTORY_SEPARATOR, [$output, $file_name]));
                }
                $this->zip->extractTo($folder, $file_name);
                files::inheritChmod($target);

                break;

            case self::USE_LEGACY:

                if (!$details['uncompressed_size']) {
                    return $this->putContent('', $target);
                }

                fseek($this->fp(), $details['contents_start_offset']);

                $this->memoryAllocate($details['compressed_size']);

                return $this->uncompress(
                    fread($this->fp(), $details['compressed_size']),
                    $details['compression_method'],
                    $details['uncompressed_size'],
                    $target
                );
        }
    }

    /**
     * Gets the files list.
     *
     * @return     array  The files list.
     */
    public function getFilesList(): array
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        $res = [];
        foreach ($this->manifest as $k => $v) {
            if (!$v['is_dir']) {
                $res[] = $k;
            }
        }

        return $res;
    }

    /**
     * Gets the dirs list.
     *
     * @return     array  The dirs list.
     */
    public function getDirsList(): array
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        $res = [];
        foreach ($this->manifest as $k => $v) {
            if ($v['is_dir']) {
                $res[] = substr($k, 0, -1);
            }
        }

        return $res;
    }

    /**
     * Gets the root dir.
     *
     * @return     false|string  The root dir.
     */
    public function getRootDir()
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        $files = $this->getFilesList();
        $dirs  = $this->getDirsList();

        $root_files = 0;
        $root_dirs  = 0;
        foreach ($files as $v) {
            if (strpos($v, '/') === false) {
                $root_files++;
            }
        }
        foreach ($dirs as $v) {
            if (strpos($v, '/') === false) {
                $root_dirs++;
            }
        }

        if ($root_files == 0 && $root_dirs == 1) {
            return $dirs[0];
        }

        return false;
    }

    /**
     * Determines if archive is empty.
     *
     * @return     bool  True if empty, False otherwise.
     */
    public function isEmpty(): bool
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        return count($this->manifest) === 0;
    }

    /**
     * Determines if file exists in archive.
     *
     * @param      string  $filename  The filename
     *
     * @return     bool    True if file, False otherwise.
     */
    public function hasFile(string $filename): bool
    {
        if (empty($this->manifest)) {
            $this->getList();
        }

        return isset($this->manifest[$filename]);
    }

    /**
     * Sets the exclude pattern.
     *
     * @param      string  $pattern  The pattern
     */
    public function setExcludePattern(string $pattern)
    {
        $this->exclude = $pattern;
    }

    /**
     * Determines whether the specified filename is excluded.
     *
     * @param      string  $filename      The filename
     *
     * @return     bool    True if the specified f is file excluded, False otherwise.
     */
    protected function isFileExcluded(string $filename): bool
    {
        if ($this->exclude === '') {
            return false;
        }

        return (bool) preg_match($this->exclude, (string) $filename);
    }

    /**
     * Check target directory and create it if necessary
     *
     * @param      string     $dir    The target directory
     *
     * @throws     Exception
     */
    protected function testTargetDir(string $dir)
    {
        if (is_dir($dir) && !is_writable($dir)) {
            throw new Exception(__('Unable to write in target directory, permission denied.'));
        }

        if (!is_dir($dir)) {
            files::makeDir($dir, true);
        }
    }

    // Legacy

    protected function putContent($content, $target = false)
    {
        if ($target) {
            $r = @file_put_contents($target, $content);
            if ($r === false) {
                throw new Exception(__('Unable to write destination file.'));
            }
            files::inheritChmod($target);

            return true;
        }

        return $content;
    }

    protected function fp()
    {
        if ($this->fp === null) {
            $this->fp = @fopen($this->archive, 'rb');
        }

        if ($this->fp === false) {
            throw new Exception('Unable to open file.');
        }

        return $this->fp;
    }

    protected function uncompress($content, $mode, $size, $target = false)
    {
        switch ($mode) {
            case 0:
                # Not compressed
                $this->memoryAllocate($size * 2);

                return $this->putContent($content, $target);
            case 1:
                throw new Exception('Shrunk mode is not supported.');
            case 2:
            case 3:
            case 4:
            case 5:
                throw new Exception('Compression factor ' . ($mode - 1) . ' is not supported.');
            case 6:
                throw new Exception('Implode is not supported.');
            case 7:
                throw new Exception('Tokenizing compression algorithm is not supported.');
            case 8:
                # Deflate
                if (!function_exists('gzinflate')) {
                    throw new Exception('Gzip functions are not available.');
                }
                $this->memoryAllocate($size * 2);

                return $this->putContent(gzinflate($content, $size), $target);
            case 9:
                throw new Exception('Enhanced Deflating is not supported.');
            case 10:
                throw new Exception('PKWARE Date Compression Library Impoloding is not supported.');
            case 12:
                # Bzip2
                if (!function_exists('bzdecompress')) {
                    throw new Exception('Bzip2 functions are not available.');
                }
                $this->memoryAllocate($size * 2);

                return $this->putContent(bzdecompress($content), $target);
            case 18:
                throw new Exception('IBM TERSE is not supported.');
            default:
                throw new Exception('Unknown uncompress method');
        }
    }

    protected function loadFileListByEOF($stop_on_file = false, $exclude = false)
    {
        $fp = $this->fp();

        for ($x = 0; $x < 1024; $x++) {
            fseek($fp, -22 - $x, SEEK_END);
            $signature = fread($fp, 4);

            if ($signature == $this->dir_sig_e) {
                $dir_list = [];

                $eodir = [
                    'disk_number_this'   => unpack('v', fread($fp, 2)),
                    'disk_number'        => unpack('v', fread($fp, 2)),
                    'total_entries_this' => unpack('v', fread($fp, 2)),
                    'total_entries'      => unpack('v', fread($fp, 2)),
                    'size_of_cd'         => unpack('V', fread($fp, 4)),
                    'offset_start_cd'    => unpack('V', fread($fp, 4)),
                ];

                $zip_comment_len          = unpack('v', fread($fp, 2));
                $eodir['zipfile_comment'] = $zip_comment_len[1] ? fread($fp, (int) $zip_comment_len) : '';

                $this->eo_central = [
                    'disk_number_this'   => $eodir['disk_number_this'][1],
                    'disk_number'        => $eodir['disk_number'][1],
                    'total_entries_this' => $eodir['total_entries_this'][1],
                    'total_entries'      => $eodir['total_entries'][1],
                    'size_of_cd'         => $eodir['size_of_cd'][1],
                    'offset_start_cd'    => $eodir['offset_start_cd'][1],
                    'zipfile_comment'    => $eodir['zipfile_comment'],
                ];

                fseek($fp, $this->eo_central['offset_start_cd']);
                $signature = fread($fp, 4);

                while ($signature == $this->dir_sig) {
                    $dir                       = [];
                    $dir['version_madeby']     = unpack('v', fread($fp, 2)); # version made by
                    $dir['version_needed']     = unpack('v', fread($fp, 2)); # version needed to extract
                    $dir['general_bit_flag']   = unpack('v', fread($fp, 2)); # general purpose bit flag
                    $dir['compression_method'] = unpack('v', fread($fp, 2)); # compression method
                    $dir['lastmod_time']       = unpack('v', fread($fp, 2)); # last mod file time
                    $dir['lastmod_date']       = unpack('v', fread($fp, 2)); # last mod file date
                    $dir['crc-32']             = fread($fp, 4); # crc-32
                    $dir['compressed_size']    = unpack('V', fread($fp, 4)); # compressed size
                    $dir['uncompressed_size']  = unpack('V', fread($fp, 4)); # uncompressed size

                    $file_name_len    = unpack('v', fread($fp, 2)); # filename length
                    $extra_field_len  = unpack('v', fread($fp, 2)); # extra field length
                    $file_comment_len = unpack('v', fread($fp, 2)); # file comment length

                    $dir['disk_number_start']    = unpack('v', fread($fp, 2)); # disk number start
                    $dir['internal_attributes']  = unpack('v', fread($fp, 2)); # internal file attributes-byte1
                    $dir['external_attributes1'] = unpack('v', fread($fp, 2)); # external file attributes-byte2
                    $dir['external_attributes2'] = unpack('v', fread($fp, 2)); # external file attributes
                    $dir['relative_offset']      = unpack('V', fread($fp, 4)); # relative offset of local header
                    $dir['file_name']            = $this->cleanFileName(fread($fp, $file_name_len[1])); # filename
                    $dir['extra_field']          = $extra_field_len[1] ? fread($fp, $extra_field_len[1]) : ''; # extra field
                    $dir['file_comment']         = $file_comment_len[1] ? fread($fp, $file_comment_len[1]) : ''; # file comment

                    $dir_list[$dir['file_name']] = [
                        'version_madeby'     => $dir['version_madeby'][1],
                        'version_needed'     => $dir['version_needed'][1],
                        'general_bit_flag'   => str_pad(decbin($dir['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
                        'compression_method' => $dir['compression_method'][1],
                        'lastmod_datetime'   => $this->getTimeStamp($dir['lastmod_date'][1], $dir['lastmod_time'][1]),
                        'crc-32'             => str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT) .
                        str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT) .
                        str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT) .
                        str_pad(dechex(ord($dir['crc-32'][0])), 2, '0', STR_PAD_LEFT),
                        'compressed_size'      => $dir['compressed_size'][1],
                        'uncompressed_size'    => $dir['uncompressed_size'][1],
                        'disk_number_start'    => $dir['disk_number_start'][1],
                        'internal_attributes'  => $dir['internal_attributes'][1],
                        'external_attributes1' => $dir['external_attributes1'][1],
                        'external_attributes2' => $dir['external_attributes2'][1],
                        'relative_offset'      => $dir['relative_offset'][1],
                        'file_name'            => $dir['file_name'],
                        'extra_field'          => $dir['extra_field'],
                        'file_comment'         => $dir['file_comment'],
                    ];
                    $signature = fread($fp, 4);
                }

                foreach ($dir_list as $k => $v) {
                    if ($exclude && preg_match($exclude, (string) $k)) {
                        continue;
                    }

                    $i = $this->getFileHeaderInformation($v['relative_offset']);

                    $this->manifest[$k]['file_name']             = $k;
                    $this->manifest[$k]['is_dir']                = $v['external_attributes1'] == 16 || substr($k, -1, 1) == '/';
                    $this->manifest[$k]['compression_method']    = $v['compression_method'];
                    $this->manifest[$k]['version_needed']        = $v['version_needed'];
                    $this->manifest[$k]['lastmod_datetime']      = $v['lastmod_datetime'];
                    $this->manifest[$k]['crc-32']                = $v['crc-32'];
                    $this->manifest[$k]['compressed_size']       = $v['compressed_size'];
                    $this->manifest[$k]['uncompressed_size']     = $v['uncompressed_size'];
                    $this->manifest[$k]['lastmod_datetime']      = $v['lastmod_datetime'];
                    $this->manifest[$k]['extra_field']           = $i['extra_field'];
                    $this->manifest[$k]['contents_start_offset'] = $i['contents_start_offset'];

                    if ($stop_on_file !== false) {
                        if (strtolower($stop_on_file) == strtolower($k)) {
                            break;
                        }
                    }
                }

                return true;
            }
        }

        return false;
    }

    protected function loadFileListBySignatures($stop_on_file = false, $exclude = false)
    {
        $fp = $this->fp();
        fseek($fp, 0);

        $return = false;
        while (true) {
            $details = $this->getFileHeaderInformation();
            if (!$details) {
                fseek($fp, 12 - 4, SEEK_CUR); # 12: Data descriptor - 4: Signature (that will be read again)
                $details = $this->getFileHeaderInformation();
            }
            if (!$details) {
                break;
            }
            $filename = $details['file_name'];

            if ($exclude && preg_match($exclude, (string) $filename)) {
                continue;
            }

            $this->manifest[$filename] = $details;
            $return                    = true;

            if ($stop_on_file !== false) {
                if (strtolower($stop_on_file) == strtolower($filename)) {
                    break;
                }
            }
        }

        return $return;
    }

    protected function getFileHeaderInformation($start_offset = false)
    {
        $fp = $this->fp();

        if ($start_offset !== false) {
            fseek($fp, $start_offset);
        }

        $signature = fread($fp, 4);
        if ($signature == $this->zip_sig) {
            # Get information about the zipped file
            $file                       = [];
            $file['version_needed']     = unpack('v', fread($fp, 2)); # version needed to extract
            $file['general_bit_flag']   = unpack('v', fread($fp, 2)); # general purpose bit flag
            $file['compression_method'] = unpack('v', fread($fp, 2)); # compression method
            $file['lastmod_time']       = unpack('v', fread($fp, 2)); # last mod file time
            $file['lastmod_date']       = unpack('v', fread($fp, 2)); # last mod file date
            $file['crc-32']             = fread($fp, 4); # crc-32
            $file['compressed_size']    = unpack('V', fread($fp, 4)); # compressed size
            $file['uncompressed_size']  = unpack('V', fread($fp, 4)); # uncompressed size

            $file_name_len   = unpack('v', fread($fp, 2)); # filename length
            $extra_field_len = unpack('v', fread($fp, 2)); # extra field length

            $file['file_name']             = $this->cleanFileName(fread($fp, $file_name_len[1])); # filename
            $file['extra_field']           = $extra_field_len[1] ? fread($fp, $extra_field_len[1]) : ''; # extra field
            $file['contents_start_offset'] = ftell($fp);

            # Look for the next file
            fseek($fp, $file['compressed_size'][1], SEEK_CUR);

            # Mount file table
            return [
                'file_name'          => $file['file_name'],
                'is_dir'             => substr($file['file_name'], -1, 1) == '/',
                'compression_method' => $file['compression_method'][1],
                'version_needed'     => $file['version_needed'][1],
                'lastmod_datetime'   => $this->getTimeStamp($file['lastmod_date'][1], $file['lastmod_time'][1]),
                'crc-32'             => str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT) .
                str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT) .
                str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT) .
                str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
                'compressed_size'       => $file['compressed_size'][1],
                'uncompressed_size'     => $file['uncompressed_size'][1],
                'extra_field'           => $file['extra_field'],
                'general_bit_flag'      => str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
                'contents_start_offset' => $file['contents_start_offset'],
            ];
        }

        return false;
    }

    protected function getTimeStamp($date, $time)
    {
        $BINlastmod_date = str_pad(decbin($date), 16, '0', STR_PAD_LEFT);
        $BINlastmod_time = str_pad(decbin($time), 16, '0', STR_PAD_LEFT);
        $lastmod_dateY   = bindec(substr($BINlastmod_date, 0, 7)) + 1980;
        $lastmod_dateM   = bindec(substr($BINlastmod_date, 7, 4));
        $lastmod_dateD   = bindec(substr($BINlastmod_date, 11, 5));
        $lastmod_timeH   = bindec(substr($BINlastmod_time, 0, 5));
        $lastmod_timeM   = bindec(substr($BINlastmod_time, 5, 6));
        $lastmod_timeS   = bindec(substr($BINlastmod_time, 11, 5)) * 2;

        return mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY);
    }

    protected function cleanFileName($n)
    {
        $n = str_replace('../', '', (string) $n);
        $n = preg_replace('#^/+#', '', (string) $n);

        return $n;
    }

    protected function memoryAllocate($size)
    {
        $mem_used  = function_exists('memory_get_usage') ? @memory_get_usage() : 4_000_000;
        $mem_limit = @ini_get('memory_limit');
        if ($mem_limit && trim((string) $mem_limit) === '-1' || !files::str2bytes($mem_limit)) {
            // Cope with memory_limit set to -1 in PHP.ini
            return;
        }
        if ($mem_used && $mem_limit) {
            $mem_limit  = files::str2bytes($mem_limit);
            $mem_avail  = $mem_limit - $mem_used - (512 * 1024);
            $mem_needed = $size;

            if ($mem_needed > $mem_avail) {
                if (@ini_set('memory_limit', (string) ($mem_limit + $mem_needed + $mem_used)) === false) {
                    throw new Exception(__('Not enough memory to open file.'));
                }

                if (!$this->memory_limit) {
                    $this->memory_limit = $mem_limit;
                }
            }
        }
    }
}
