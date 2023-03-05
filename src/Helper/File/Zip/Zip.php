<?php
/**
 * @class Zip
 *
 * @package Clearbricks
 * @subpackage Zip
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Zip;

use Exception;
use files;

class Zip
{
    /**
     * @var string PHP output stream
     */
    public const STREAM = 'php://output';

    /**
     * @var string Prefix for temporary archive name
     */
    public const TMP_PREFIX = 'dc-temp-';

    /**
     * @var string Phar::ZIP buggy archive (file metadata, specially date and time not stored) up to this PHP Version
     *
     * See PHP Issue #10766 https://github.com/php/php-src/issues/10766 — fixed in 8.2.4
     */
    public const PHARZIP_BUGGY_MAX = '8.2.3';

    /**
     * @var string Archive filename
     */
    protected ?string $archive;

    /**
     * @var bool True if stream
     */
    protected bool $stream = false;

    /**
     * @var string Archive name (used for streamed output)
     */
    protected string $filename;

    /**
     * $var PharData|ZipArchive archive object
     */
    protected $zip;

    /**
     * @var array exclusion list
     */
    protected array $exclusions = [];

    /**
     * @var bool True if PharData is enabled
     */
    protected bool $phardata = true;

    /**
     * @var bool True if ZipArchive is enabled
     */
    protected bool $ziparchive = false;

    /**
     * @var bool True if archive has been closed
     */
    protected bool $closed = false;

    // Legacy

    protected $fp;
    protected $memory_limit = null;
    protected $entries      = [];
    protected $ctrl_dir     = [];
    protected $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    protected $old_offset   = 0;

    /**
     * Constructs a new instance.
     *
     * If PharData class exists and is enabled and PHP version is > 8.2.0, use it
     * Else if ZipArchive class exists and is enabled, use it
     * Else use legacy Clearbricks zip archive functions
     *
     * @param      null|string     $output      The archive filename (if null redirect output to php://output stream)
     * @param      null|string     $filename    The archive name (used on streamed output as destination filename)
     *
     * @throws     Exception
     */
    public function __construct(?string $output = null, ?string $filename = null)
    {
        if (!class_exists('PharData') || version_compare(PHP_VERSION, self::PHARZIP_BUGGY_MAX, '<=')) {
            // Cannot use PharData zip archive as file's matadata are not preserved when compressed
            // See PHP Issue #10766 https://github.com/php/php-src/issues/10766
            $this->phardata = false;
            if (class_exists('ZipArchive')) {
                // We will use a ZipArchive archive
                $this->ziparchive = true;
            }
        }

        if (!$output || $output === self::STREAM) {
            // Output zip to stream output
            $this->stream = true;
        }

        if (!$this->stream && file_exists($output)) {
            // Remove existing archive if necessary
            try {
                unlink($output);
            } catch (Exception $e) {
                throw new Exception('Unable to delete existing archive');
            }
        }

        if ($this->stream && ($this->phardata || $this->ziparchive)) {
            // Use a temporary file
            $output = sys_get_temp_dir() . self::TMP_PREFIX . bin2hex(random_bytes(8)) . '.zip';
            if (file_exists($output)) {
                try {
                    unlink($output);
                } catch (Exception $e) {
                    throw new Exception('Unable to delete previous temporary archive');
                }
            }
        }

        $this->archive  = $output;
        $this->filename = $filename ? $filename : $output;

        if ($this->phardata) {
            // Create PharData archive
            $this->zip = new \PharData(
                $output,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS,
                null,
                \Phar::ZIP
            );
        } elseif ($this->ziparchive) {
            // Create ZipArchive archive
            $this->zip = new \ZipArchive();
            $this->zip->open(
                $output,
                \ZipArchive::CREATE | \ZipArchive::OVERWRITE
            );
        } elseif (!$this->stream) {
            // Create legacy archive
            $this->fp = fopen($output, 'wb');
        }
    }

    /**
     * Close the archive
     *
     * @throws     Exception
     */
    public function close()
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;

        if ($this->stream) {
            // Send archive to output stream
            try {
                // Need to leave output buffering if necessary
                if (ob_get_level()) {
                    ob_end_clean();
                }
                // Open output stream
                $this->fp = fopen(self::STREAM, 'wb');
                if ($this->fp) {
                    header('Content-Disposition: attachment;filename=' . $this->filename);
                    header('Content-Type: application/x-zip');
                    if ($this->phardata) {
                        readfile($this->archive);
                    } elseif ($this->ziparchive) {
                        $this->zip->close();
                        readfile($this->archive);
                    } else {
                        $this->write();
                    }
                    fclose($this->fp);
                    unlink($this->archive);
                    unset($this->archive);
                }
            } catch (Exception $e) {
                throw new Exception('Unable to output archive');
            }
        }

        if (!$this->phardata && !$this->ziparchive) {
            // Legacy mode
            if (!$this->stream) {
                // Write legacy archive content
                $this->write();
                fclose($this->fp);
            }
            if ($this->memory_limit) {
                ini_set('memory_limit', $this->memory_limit);
            }
        }
    }

    /**
     * Adds an exclusion in exclusion list.
     *
     * @param      string  $reg    The regexp (name) to exclude
     */
    public function addExclusion($reg)
    {
        $this->exclusions[] = $reg;
    }

    /**
     * Adds a file in archive.
     *
     * @param      string     $file   The file
     * @param      string     $name   The name
     *
     * @throws     Exception
     */
    public function addFile(string $file, ?string $name = null)
    {
        $file = preg_replace('#[\\\/]+#', '/', (string) $file);

        if (!$name) {
            $name = $file;
        }
        $name = $this->formatName($name);

        if ($this->isExcluded($name)) {
            return;
        }

        if (!file_exists($file) || !is_file($file)) {
            throw new Exception(__('File does not exist'));
        }
        if (!is_readable($file)) {
            throw new Exception(__('Cannot read file'));
        }

        if ($this->phardata) {
            // Add file to PharData archive
            $this->zip->addFile($file, $name);
        } elseif ($this->ziparchive) {
            // Add file to ZipArchive archive
            $this->zip->addFile($file, $name);
        } else {
            // Add file to legacy archive
            $info = stat($file);

            $this->entries[$name] = [
                'file'   => $file,
                'is_dir' => false,
                'mtime'  => $info['mtime'],
            ];
        }
    }

    /**
     * Adds a directory in archive.
     *
     * @param      string       $dir        The dir
     * @param      null|string  $dirname    The dir name
     * @param      bool         $recursive  The recursive flag
     *
     * @throws     Exception
     */
    public function addDirectory(string $dir, ?string $dirname = null, bool $recursive = false)
    {
        $dir = preg_replace('#[\\\/]+#', '/', (string) $dir);
        if (substr($dir, -1 - 1) != '/') {
            $dir .= '/';
        }

        if (!$dirname && $dirname !== '') {
            $dirname = $dir;
        }

        if ($this->isExcluded($dirname)) {
            return;
        }

        if ($dirname !== '') {
            if (substr($dirname, -1, 1) != '/') {
                $dirname .= '/';
            }

            $dirname = $this->formatName($dirname);
            if ($dirname !== '') {
                if ($this->phardata) {
                    // Add directory to PharData archive
                    $this->zip->addEmptyDir($dirname);
                } elseif ($this->ziparchive) {
                    // Add directory to ZipArchive archive
                    $this->zip->addEmptyDir($dirname);
                } else {
                    // Add directory to legacy archive
                    $this->entries[$dirname] = [
                        'file'   => null,
                        'is_dir' => true,
                        'mtime'  => time(),
                    ];
                }
            }
        }

        if ($recursive) {
            if (!is_dir($dir)) {
                throw new Exception(__('Directory does not exist'));
            }
            if (!is_readable($dir)) {
                throw new Exception(__('Cannot read directory'));
            }

            $directory = dir($dir);
            while (($file = $directory->read()) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (is_dir($dir . '/' . $file)) {
                    $this->addDirectory($dir . $file, $dirname . $file, true);
                } elseif (is_file($dir . '/' . $file)) {
                    $this->addFile($dir . $file, $dirname . $file);
                }
            }
        }
    }

    /**
     * Format file/directory name
     *
     * @param      string  $name   The name
     *
     * @return     string
     */
    protected function formatName(string $name): string
    {
        if (substr($name, 0, 1) === '/') {
            $name = substr($name, 1);
        }

        return $name;
    }

    /**
     * Determines whether the specified name is excluded.
     *
     * @param      string  $name   The name
     *
     * @return     bool    True if the specified name is excluded, False otherwise.
     */
    protected function isExcluded(string $name): bool
    {
        foreach ($this->exclusions as $reg) {
            if (preg_match((string) $reg, (string) $name)) {
                return true;
            }
        }

        return false;
    }

    // Legacy methods

    public function write()
    {
        foreach ($this->entries as $name => $v) {
            if ($v['is_dir']) {
                $this->writeDirectory($name);
            } else {
                $this->writeFile($name, $v['file'], $v['mtime']);
            }
        }

        $ctrldir = implode('', $this->ctrl_dir);

        fwrite(
            $this->fp,
            $ctrldir .
            $this->eof_ctrl_dir .
            pack('v', sizeof($this->ctrl_dir)) . # total # of entries "on this disk"
            pack('v', sizeof($this->ctrl_dir)) . # total # of entries overall
            pack('V', strlen($ctrldir)) . # size of central dir
            pack('V', $this->old_offset) . # offset to start of central dir
            "\x00\x00" # .zip file comment length
        );
    }

    protected function writeDirectory($name)
    {
        if (!isset($this->entries[$name])) {
            return;
        }

        $mdate = $this->makeDate(time());
        $mtime = $this->makeTime(time());

        # Data descriptor
        $data_desc = "\x50\x4b\x03\x04" .
        "\x0a\x00" . # ver needed to extract
        "\x00\x00" . # gen purpose bit flag
        "\x00\x00" . # compression method
        pack('v', $mtime) . # last mod time
        pack('v', $mdate) . # last mod date
        pack('V', 0) . # crc32
        pack('V', 0) . # compressed filesize
        pack('V', 0) . # uncompressed filesize
        pack('v', strlen($name)) . # length of pathname
        pack('v', 0) . # extra field length
        $name . # end of "local file header" segment
        pack('V', 0) . # crc32
        pack('V', 0) . # compressed filesize
        pack('V', 0); # uncompressed filesize

        $new_offset = $this->old_offset + strlen($data_desc);
        fwrite($this->fp, $data_desc);

        # Add to central record
        $cdrec = "\x50\x4b\x01\x02" .
        "\x00\x00" . # version made by
        "\x0a\x00" . # version needed to extract
        "\x00\x00" . # gen purpose bit flag
        "\x00\x00" . # compression method
        pack('v', $mtime) . # last mod time
        pack('v', $mdate) . # last mod date
        pack('V', 0) . # crc32
        pack('V', 0) . # compressed filesize
        pack('V', 0) . # uncompressed filesize
        pack('v', strlen($name)) . # length of filename
        pack('v', 0) . # extra field length
        pack('v', 0) . # file comment length
        pack('v', 0) . # disk number start
        pack('v', 0) . # internal file attributes
        pack('V', 16) . # external file attributes  - 'directory' bit set
        pack('V', $this->old_offset) . # relative offset of local header
        $name;

        $this->old_offset = $new_offset;
        $this->ctrl_dir[] = $cdrec;
    }

    protected function writeFile($name, $file, $mtime)
    {
        if (!isset($this->entries[$name])) {
            return;
        }

        $filesize = filesize($file);
        $this->memoryAllocate($filesize * 3);

        $content = file_get_contents($file);

        $unc_len = strlen($content);
        $crc     = crc32($content);
        $zdata   = gzdeflate($content);
        $c_len   = strlen($zdata);

        unset($content);

        $mdate = $this->makeDate($mtime);
        $mtime = $this->makeTime($mtime);

        # Data descriptor
        $data_desc = "\x50\x4b\x03\x04" .
        "\x14\x00" . # ver needed to extract
        "\x00\x00" . # gen purpose bit flag
        "\x08\x00" . # compression method
        pack('v', $mtime) . # last mod time
        pack('v', $mdate) . # last mod date
        pack('V', $crc) . # crc32
        pack('V', $c_len) . # compressed filesize
        pack('V', $unc_len) . # uncompressed filesize
        pack('v', strlen($name)) . # length of filename
        pack('v', 0) . # extra field length
        $name . # end of "local file header" segment
        $zdata . # "file data" segment
        pack('V', $crc) . # crc32
        pack('V', $c_len) . # compressed filesize
        pack('V', $unc_len); # uncompressed filesize

        fwrite($this->fp, $data_desc);
        unset($zdata);

        $new_offset = $this->old_offset + strlen($data_desc);

        # Add to central directory record
        $cdrec = "\x50\x4b\x01\x02" .
        "\x00\x00" . # version made by
        "\x14\x00" . # version needed to extract
        "\x00\x00" . # gen purpose bit flag
        "\x08\x00" . # compression method
        pack('v', $mtime) . # last mod time
        pack('v', $mdate) . # last mod date
        pack('V', $crc) . # crc32
        pack('V', $c_len) . # compressed filesize
        pack('V', $unc_len) . # uncompressed filesize
        pack('v', strlen($name)) . # length of filename
        pack('v', 0) . # extra field length
        pack('v', 0) . # file comment length
        pack('v', 0) . # disk number start
        pack('v', 0) . # internal file attributes
        pack('V', 32) . # external file attributes - 'archive' bit set
        pack('V', $this->old_offset) . # relative offset of local header
        $name;

        $this->old_offset = $new_offset;
        $this->ctrl_dir[] = $cdrec;
    }

    protected function makeDate($ts)
    {
        $year = date('Y', $ts) - 1980;
        if ($year < 0) {
            $year = 0;
        }

        $year  = sprintf('%07b', $year);
        $month = sprintf('%04b', date('n', $ts));
        $day   = sprintf('%05b', date('j', $ts));

        return bindec($year . $month . $day);
    }

    protected function makeTime($ts)
    {
        $hour   = sprintf('%05b', date('G', $ts));
        $minute = sprintf('%06b', date('i', $ts));
        $second = sprintf('%05b', ceil(date('s', $ts) / 2));

        return bindec($hour . $minute . $second);
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
