<?php

/**
 * @package Clearbricks
 * @subpackage Zip
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */

namespace Dotclear\Helper\File\Zip;

use Dotclear\Helper\File\Files;
use Exception;

/**
 * @class Zip
 */
class Zip
{
    /**
     * @var        array<string, mixed>
     */
    protected array $entries = [];

    /**
     * @var        array<string>
     */
    protected array $ctrl_dir = [];

    protected string $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";

    /**
     * @var        int
     */
    protected $old_offset = 0;

    /**
     * @var        mixed
     */
    protected $fp;

    /**
     * @var        mixed
     */
    protected $memory_limit;

    /**
     * @var        array<string>
     */
    protected $exclusions = [];

    /**
     * Constructs a new instance.
     *
     * @param      mixed      $out_fp  The out fp
     *
     * @throws     Exception
     */
    public function __construct($out_fp)
    {
        if (!is_resource($out_fp)) {
            throw new Exception('Output file descriptor is not a resource');
        }

        if (!in_array(get_resource_type($out_fp), ['stream', 'file'])) {
            throw new Exception('Output file descriptor is not a valid resource');
        }

        $this->fp = $out_fp;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close
     */
    public function close(): void
    {
        if ($this->memory_limit) {
            ini_set('memory_limit', $this->memory_limit);
        }
    }

    /**
     * Adds an exclusion.
     *
     * @param      string  $reg    The exclusion (regexp)
     */
    public function addExclusion(string $reg): void
    {
        $this->exclusions[] = $reg;
    }

    /**
     * Adds a file.
     *
     * @param      mixed           $file   The file
     * @param      string|null     $name   The name
     *
     * @throws     Exception
     */
    public function addFile($file, ?string $name = null): void
    {
        $file = (string) preg_replace('#[\\\/]+#', '/', (string) $file);

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

        $info = stat($file);

        $this->entries[$name] = [
            'file'   => $file,
            'is_dir' => false,
            'mtime'  => $info ? $info['mtime'] : 0,
            'size'   => $info ? $info['size'] : 0,
        ];
    }

    /**
     * Adds a directory.
     *
     * @param      string       $dir        The dir
     * @param      null|string  $name       The name
     * @param      bool         $recursive  The recursive
     *
     * @throws     Exception
     */
    public function addDirectory($dir, ?string $name = null, bool $recursive = false): void
    {
        $dir = (string) preg_replace('#[\\\/]+#', '/', (string) $dir);
        if (substr($dir, -1 - 1) !== '/') {
            $dir .= '/';
        }

        if (!$name && $name !== '') {
            $name = $dir;
        }

        if ($this->isExcluded($name)) {
            return;
        }

        if ($name !== '') {
            if (!str_ends_with($name, '/')) {
                $name .= '/';
            }

            $name = $this->formatName($name);

            if ($name !== '') {
                $this->entries[$name] = [
                    'file'   => null,
                    'is_dir' => true,
                    'mtime'  => time(),
                    'size'   => 0,
                ];
            }
        }

        if ($recursive) {
            if (!is_dir($dir)) {
                throw new Exception(__('Directory does not exist'));
            }
            if (!is_readable($dir)) {
                throw new Exception(__('Cannot read directory'));
            }

            $D = dir($dir);
            if ($D !== false) {
                while (($e = $D->read()) !== false) {
                    if ($e == '.' || $e == '..') {
                        continue;
                    }

                    if (is_dir($dir . '/' . $e)) {
                        $this->addDirectory($dir . $e, $name . $e, true);
                    } elseif (is_file($dir . '/' . $e)) {
                        $this->addFile($dir . $e, $name . $e);
                    }
                }
            }
        }
    }

    /**
     * Write zip file
     */
    public function write(): void
    {
        foreach ($this->entries as $name => $v) {
            if ($v['is_dir']) {
                $this->writeDirectory($name);
            } else {
                $this->writeFile($name, $v['file'], $v['size'], $v['mtime']);
            }
        }

        $ctrldir = implode('', $this->ctrl_dir);

        fwrite(
            $this->fp,
            $ctrldir .
            $this->eof_ctrl_dir .
            pack('v', count($this->ctrl_dir)) . # total # of entries "on this disk"
            pack('v', count($this->ctrl_dir)) . # total # of entries overall
            pack('V', strlen($ctrldir)) . # size of central dir
            pack('V', $this->old_offset) . # offset to start of central dir
            "\x00\x00" # .zip file comment length
        );
    }

    /**
     * Writes a directory.
     *
     * @param      string  $name   The name
     */
    protected function writeDirectory(string $name): void
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

    /**
     * Writes a file.
     *
     * @param      string     $name   The name
     * @param      string     $file   The file
     * @param      float|int  $size   The size
     * @param      float|int  $mtime  The mtime
     *
     * @throws     Exception
     */
    protected function writeFile(string $name, string $file, int|float $size, int|float $mtime): void
    {
        if (!isset($this->entries[$name])) {
            return;
        }

        $filesize = filesize($file);
        $this->memoryAllocate($filesize * 3);

        $content = file_get_contents($file);

        if ($content !== false) {
            $unc_len = strlen($content);
            $crc     = crc32($content);
            $zdata   = gzdeflate($content);
            $c_len   = $zdata !== false ? strlen($zdata) : 0;

            unset($content);
        } else {
            throw new Exception(__('Unable to write ZIP archive'));
        }

        $mdate = $this->makeDate((int) $mtime);
        $mtime = $this->makeTime((int) $mtime);

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

    /**
     * Format a name
     *
     * @param      null|string  $name   The name
     */
    protected function formatName(?string $name): string
    {
        if ($name === null) {
            return '';
        }
        if (str_starts_with($name, '/')) {
            $name = substr($name, 1);
        }

        return $name;
    }

    /**
     * Determines whether the specified name is excluded.
     *
     * @param      string|null   $name   The name
     *
     * @return     bool    True if the specified name is excluded, False otherwise.
     */
    protected function isExcluded(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        foreach ($this->exclusions as $reg) {
            if (preg_match((string) $reg, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Makes a date.
     *
     * @param      int        $ts     Timestamp
     */
    protected function makeDate(int $ts): int|float
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

    /**
     * Makes a time.
     *
     * @param      int        $ts     Timestamp
     */
    protected function makeTime(int $ts): int|float
    {
        $hour   = sprintf('%05b', date('G', $ts));
        $minute = sprintf('%06b', date('i', $ts));
        $second = sprintf('%05b', ceil(date('s', $ts) / 2));

        return bindec($hour . $minute . $second);
    }

    /**
     * Allocate memory
     *
     * @param      mixed     $size   The size
     *
     * @throws     Exception
     */
    protected function memoryAllocate($size): void
    {
        $mem_used  = function_exists('memory_get_usage') ? @memory_get_usage() : 4_000_000;
        $mem_limit = @ini_get('memory_limit');
        if ($mem_limit && trim($mem_limit) === '-1' || !Files::str2bytes($mem_limit)) {
            // Cope with memory_limit set to -1 in PHP.ini
            return;
        }
        if ($mem_limit !== '') {
            $mem_limit  = Files::str2bytes($mem_limit);
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
