<?php

/**
 * @package         Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Zip;

use Dotclear\Helper\File\Files;
use Exception;

/**
 * @class Unzip
 */
class Unzip
{
    /**
     * @var array<string, array<string, mixed> > $compressed_list
     */
    protected array $compressed_list = [];

    /**
     * @var array<string, mixed>     $eo_central
     */
    protected array $eo_central = [];

    /**
     * Local file header signature
     */
    protected string $zip_sig = "\x50\x4b\x03\x04"; #

    /**
     * Central dir header signature
     */
    protected string $dir_sig = "\x50\x4b\x01\x02";

    /**
     * End of central dir signature
     */
    protected string $dir_sig_e = "\x50\x4b\x05\x06";

    /**
     * @var ?resource    $fp
     */
    protected $fp;

    protected string|int $memory_limit;

    /**
     * Memory limit has been modified during process?
     */
    protected bool $memory_limit_set = false;

    protected string $exclude_pattern = '';

    /**
     * Constructs a new instance.
     *
     * @param      string  $file_name  The file name
     */
    public function __construct(
        protected string $file_name
    ) {
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close instance
     */
    public function close(): void
    {
        if ($this->fp !== null) {
            fclose($this->fp);
            $this->fp = null;
        }

        if ($this->memory_limit_set) {
            ini_set('memory_limit', $this->memory_limit);
        }
    }

    /**
     * Gets the list.
     *
     * @param      false|string  $stop_on_file  The stop on file
     * @param      false|string  $exclude       The exclude
     *
     * @return     array<string, array<string, mixed>>|false   The list.
     */
    public function getList(bool|string $stop_on_file = false, bool|string $exclude = false): array|false
    {
        if ($this->compressed_list !== []) {
            return $this->compressed_list;
        }

        if (!$this->loadFileListByEOF($stop_on_file, $exclude) && !$this->loadFileListBySignatures($stop_on_file, $exclude)) {
            return false;
        }

        return $this->compressed_list;
    }

    /**
     * Unzip all
     *
     * @param      false|string  $target  The target
     */
    public function unzipAll(bool|string $target): void
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        foreach ($this->compressed_list as $k => $v) {
            if ($v['is_dir']) {
                continue;
            }

            $this->unzip($k, $target !== false ? $target . '/' . $k : false);
        }
    }

    /**
     * Unzip a file
     *
     * @param      string        $file_name  The file name
     * @param      false|string  $target     The target
     *
     * @throws     Exception
     */
    public function unzip(string $file_name, bool|string $target = false): null|string|true
    {
        $fp = $this->fp();
        if ($fp === null) {
            return null;
        }

        if ($this->compressed_list === []) {
            $this->getList($file_name);
        }

        if (!isset($this->compressed_list[$file_name])) {
            throw new Exception(sprintf(__('File %s is not compressed in the zip.'), $file_name));
        }
        if ($this->isFileExcluded($file_name)) {
            return null;
        }
        $details = &$this->compressed_list[$file_name];

        if ($details['is_dir']) {
            throw new Exception(sprintf(__('Trying to unzip a folder name %s'), $file_name));
        }

        if ($target !== false) {
            $this->testTargetDir(dirname($target));
        }

        $uncompressed_size = is_numeric($uncompressed_size = $details['uncompressed_size']) ? (int) $uncompressed_size : 0;
        if ($uncompressed_size <= 0) {
            return $this->putContent('', $target);
        }

        $contents_start_offset = is_numeric($contents_start_offset = $details['contents_start_offset']) ? (int) $contents_start_offset : 0;
        fseek($fp, $contents_start_offset);

        $compressed_size = is_numeric($compressed_size = $details['compressed_size']) ? (int) $compressed_size : 0;
        if ($compressed_size <= 0) {
            return $this->putContent('', $target);
        }

        $this->memoryAllocate($compressed_size);

        $compression_method = is_numeric($compression_method = $details['compression_method']) ? (int) $compression_method : 0;

        $buffer = fread($fp, $compressed_size);
        if ($buffer === false) {
            return null;
        }

        return $this->uncompress(
            $buffer,
            $compression_method,
            $uncompressed_size,
            $target
        );
    }

    /**
     * Gets the files list.
     *
     * @return     array<string>  The files list.
     */
    public function getFilesList(): array
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        $res = [];
        foreach ($this->compressed_list as $k => $v) {
            if (!$v['is_dir']) {
                $res[] = $k;
            }
        }

        return $res;
    }

    /**
     * Gets the dirs list.
     *
     * @return     array<string>  The dirs list.
     */
    public function getDirsList(): array
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        $res = [];
        foreach ($this->compressed_list as $k => $v) {
            if ($v['is_dir']) {
                $res[] = substr($k, 0, -1);
            }
        }

        return $res;
    }

    /**
     * Gets the root dir.
     *
     * @return     false|string  The root dir (false if none)
     */
    public function getRootDir(): string|false
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        $files = $this->getFilesList();
        $dirs  = $this->getDirsList();

        $root_files = 0;
        $root_dirs  = 0;
        foreach ($files as $v) {
            if (!str_contains($v, '/')) {
                $root_files++;
            }
        }
        foreach ($dirs as $v) {
            if (!str_contains($v, '/')) {
                $root_dirs++;
            }
        }

        if ($root_files === 0 && $root_dirs === 1) {
            return $dirs[0];
        }

        return false;
    }

    /**
     * Determines if list is empty.
     *
     * @return     bool  True if empty, False otherwise.
     */
    public function isEmpty(): bool
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        return $this->compressed_list === [];
    }

    /**
     * Determines if file exist in zip.
     *
     * @param      string  $f      Filename
     */
    public function hasFile(string $f): bool
    {
        if ($this->compressed_list === []) {
            $this->getList();
        }

        return isset($this->compressed_list[$f]);
    }

    /**
     * Sets the exclude pattern.
     *
     * @param      string  $pattern  The pattern
     */
    public function setExcludePattern(string $pattern): void
    {
        $this->exclude_pattern = $pattern;
    }

    /**
     * Open Zip file
     *
     * @throws     Exception
     *
     * @return     ?resource   Zip handler
     */
    protected function fp(): mixed
    {
        if ($this->fp === null) {
            $fp = @fopen($this->file_name, 'rb');
            if ($fp !== false) {
                $this->fp = $fp;
            }
        }

        if ($this->fp === null) {
            throw new Exception('Unable to open file.');
        }

        return $this->fp;
    }

    /**
     * Determines whether the specified f is file excluded.
     *
     * @param      string    $f      Filename
     *
     * @return     bool|int  True if the specified f is file excluded, False otherwise.
     */
    protected function isFileExcluded(string $f): int|bool
    {
        if ($this->exclude_pattern === '') {
            return false;
        }

        return preg_match($this->exclude_pattern, $f);
    }

    /**
     * Puts a content.
     *
     * @param      string           $content  The content
     * @param      false|string     $target   The target
     *
     * @throws     Exception
     *
     * @return     ($target is false ? string : true)
     */
    protected function putContent(string $content, bool|string $target = false): true|string
    {
        if ($target !== false) {
            $r = @file_put_contents($target, $content);
            if ($r === false) {
                throw new Exception(__('Unable to write destination file.'));
            }
            Files::inheritChmod($target);

            return true;
        }

        return $content;
    }

    /**
     * Test a target directory
     *
     * @param      string     $dir    The dir
     *
     * @throws     Exception
     */
    protected function testTargetDir(string $dir): void
    {
        if (is_dir($dir) && !is_writable($dir)) {
            throw new Exception(__('Unable to write in target directory, permission denied.'));
        }

        if (!is_dir($dir)) {
            Files::makeDir($dir, true);
        }
    }

    /**
     * Uncompress
     *
     * @param      string           $content  The content
     * @param      int              $mode     The mode
     * @param      int              $size     The size
     * @param      false|string     $target   The target
     *
     * @throws     Exception
     *
     * @return     ($target is false ? string : true)
     */
    protected function uncompress(string $content, int $mode, int $size, bool|string $target = false): true|string
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

                $buffer = gzinflate($content, $size);
                if ($buffer === false) {
                    throw new Exception('gzinflate() error.');
                }

                return $this->putContent($buffer, $target);
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

                $buffer = bzdecompress($content);
                if (!is_string($buffer)) {
                    throw new Exception('bzdecompress() error' . ($buffer ? sprintf(' (%d)', $buffer) : ''));
                }

                return $this->putContent($buffer, $target);
            case 18:
                throw new Exception('IBM TERSE is not supported.');
            default:
                throw new Exception('Unknown uncompress method');
        }
    }

    /**
     * Loads a file list by eof.
     *
     * @param      false|string  $stop_on_file  The stop on file
     * @param      false|string  $exclude       The exclude
     */
    protected function loadFileListByEOF(bool|string $stop_on_file = false, bool|string $exclude = false): bool
    {
        $fp = $this->fp();
        if ($fp === null) {
            return false;
        }

        for ($x = 0; $x < 1024; $x++) {
            fseek($fp, -22 - $x, SEEK_END);
            $signature = $this->zipRead(4);

            if ($signature === $this->dir_sig_e) {
                $dir_list = [];

                $eodir = [
                    'disk_number_this'   => $this->zipUnpack(2, 'v'),
                    'disk_number'        => $this->zipUnpack(2, 'v'),
                    'total_entries_this' => $this->zipUnpack(2, 'v'),
                    'total_entries'      => $this->zipUnpack(2, 'v'),
                    'size_of_cd'         => $this->zipUnpack(4, 'V'),
                    'offset_start_cd'    => $this->zipUnpack(4, 'V'),
                ];

                $zip_comment_len          = $this->zipUnpack(2, 'v');
                $eodir['zipfile_comment'] = $zip_comment_len[1] && (int) $zip_comment_len > 0 ? fread($fp, (int) $zip_comment_len) : '';

                $this->eo_central = [
                    'disk_number_this'   => $eodir['disk_number_this'][1],
                    'disk_number'        => $eodir['disk_number'][1],
                    'total_entries_this' => $eodir['total_entries_this'][1],
                    'total_entries'      => $eodir['total_entries'][1],
                    'size_of_cd'         => $eodir['size_of_cd'][1],
                    'offset_start_cd'    => $eodir['offset_start_cd'][1],
                    'zipfile_comment'    => $eodir['zipfile_comment'],
                ];

                $offset_start_cd = is_numeric($offset_start_cd = $this->eo_central['offset_start_cd']) ? (int) $offset_start_cd : 0;
                fseek($fp, $offset_start_cd);

                $signature = $this->zipRead(4);

                while ($signature === $this->dir_sig) {
                    $dir                       = [];
                    $dir['version_madeby']     = $this->zipUnpack(2, 'v'); # version made by
                    $dir['version_needed']     = $this->zipUnpack(2, 'v'); # version needed to extract
                    $dir['general_bit_flag']   = $this->zipUnpack(2, 'v'); # general purpose bit flag
                    $dir['compression_method'] = $this->zipUnpack(2, 'v'); # compression method
                    $dir['lastmod_time']       = $this->zipUnpack(2, 'v'); # last mod file time
                    $dir['lastmod_date']       = $this->zipUnpack(2, 'v'); # last mod file date
                    $dir['crc-32']             = $this->zipRead(4); # crc-32
                    $dir['compressed_size']    = $this->zipUnpack(4, 'V'); # compressed size
                    $dir['uncompressed_size']  = $this->zipUnpack(4, 'V'); # uncompressed size

                    $file_name_len    = $this->zipUnpack(2, 'v'); # filename length
                    $extra_field_len  = $this->zipUnpack(2, 'v'); # extra field length
                    $file_comment_len = $this->zipUnpack(2, 'v'); # file comment length

                    $dir['disk_number_start']    = $this->zipUnpack(2, 'v'); # disk number start
                    $dir['internal_attributes']  = $this->zipUnpack(2, 'v'); # internal file attributes-byte1
                    $dir['external_attributes1'] = $this->zipUnpack(2, 'v'); # external file attributes-byte2
                    $dir['external_attributes2'] = $this->zipUnpack(2, 'v'); # external file attributes
                    $dir['relative_offset']      = $this->zipUnpack(4, 'V'); # relative offset of local header

                    $file_name_len_value    = is_numeric($file_name_len_value = $file_name_len[1]) ? (int) $file_name_len_value : 0;
                    $extra_field_len_value  = is_numeric($extra_field_len_value = $extra_field_len[1]) ? (int) $extra_field_len_value : 0;
                    $file_comment_len_value = is_numeric($file_comment_len_value = $file_comment_len[1]) ? (int) $file_comment_len_value : 0;

                    $dir['file_name']    = $file_name_len_value    > 0 ? $this->cleanFileName(fread($fp, $file_name_len_value)) : ''; # filename
                    $dir['extra_field']  = $extra_field_len_value  > 0 ? fread($fp, $extra_field_len_value) : ''; # extra field
                    $dir['file_comment'] = $file_comment_len_value > 0 ? fread($fp, $file_comment_len_value) : ''; # file comment

                    $general_bit_flag = is_numeric($general_bit_flag = $dir['general_bit_flag'][1]) ? (int) $general_bit_flag : 0;
                    $lastmod_date     = is_numeric($lastmod_date = $dir['lastmod_date'][1]) ? (int) $lastmod_date : 0;
                    $lastmod_time     = is_numeric($lastmod_time = $dir['lastmod_time'][1]) ? (int) $lastmod_time : 0;

                    $dir_list[$dir['file_name']] = [
                        'version_madeby'     => $dir['version_madeby'][1],
                        'version_needed'     => $dir['version_needed'][1],
                        'general_bit_flag'   => str_pad(decbin($general_bit_flag), 8, '0', STR_PAD_LEFT),
                        'compression_method' => $dir['compression_method'][1],
                        'lastmod_datetime'   => $this->getTimeStamp($lastmod_date, $lastmod_time),
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
                    $signature = $this->zipRead(4);
                }

                foreach ($dir_list as $k => $v) {
                    if (($exclude !== false) && preg_match($exclude, $k)) {
                        continue;
                    }

                    $relative_offset = is_numeric($relative_offset = $v['relative_offset']) ? (int) $relative_offset : 0;

                    $i = $this->getFileHeaderInformation($relative_offset);

                    $this->compressed_list[$k]['file_name']          = $k;
                    $this->compressed_list[$k]['is_dir']             = $v['external_attributes1'] == 16 || str_ends_with($k, '/');
                    $this->compressed_list[$k]['compression_method'] = $v['compression_method'];
                    $this->compressed_list[$k]['version_needed']     = $v['version_needed'];
                    $this->compressed_list[$k]['lastmod_datetime']   = $v['lastmod_datetime'];
                    $this->compressed_list[$k]['crc-32']             = $v['crc-32'];
                    $this->compressed_list[$k]['compressed_size']    = $v['compressed_size'];
                    $this->compressed_list[$k]['uncompressed_size']  = $v['uncompressed_size'];
                    $this->compressed_list[$k]['lastmod_datetime']   = $v['lastmod_datetime'];

                    if ($i !== false) {
                        $this->compressed_list[$k]['extra_field']           = $i['extra_field'];
                        $this->compressed_list[$k]['contents_start_offset'] = $i['contents_start_offset'];
                    }

                    if (($stop_on_file !== false) && (strtolower($stop_on_file) === strtolower($k))) {
                        break;
                    }
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Loads file list by signatures.
     *
     * @param      false|string  $stop_on_file  The stop on file
     * @param      false|string  $exclude       The exclude
     */
    protected function loadFileListBySignatures(bool|string $stop_on_file = false, bool|string $exclude = false): bool
    {
        $fp = $this->fp();
        if ($fp === null) {
            return false;
        }

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

            if (($exclude !== false) && preg_match($exclude, (string) $filename)) {
                continue;
            }

            $this->compressed_list[$filename] = $details;
            $return                           = true;

            if (($stop_on_file !== false) && (strtolower($stop_on_file) === strtolower((string) $filename))) {
                break;
            }
        }

        return $return;
    }

    /**
     * Gets the file header information.
     *
     * @param      false|int    $start_offset  The start offset
     *
     * @return     false|array{
     *                 file_name: string,
     *                 is_dir: bool,
     *                 compression_method: int,
     *                 version_needed: int,
     *                 lastmod_datetime: int,
     *                 crc-32: string,
     *                 compressed_size: int,
     *                 uncompressed_size: int,
     *                 extra_field: string,
     *                 general_bit_flag: string,
     *                 contents_start_offset: int
     *             }                                The file header information.
     */
    protected function getFileHeaderInformation(bool|int $start_offset = false): false|array
    {
        $fp = $this->fp();
        if ($fp === null) {
            return false;
        }

        if ($start_offset !== false) {
            fseek($fp, $start_offset);
        }

        $signature = $this->zipRead(4);
        if ($signature === $this->zip_sig) {
            # Get information about the zipped file
            $file                       = [];
            $file['version_needed']     = $this->zipUnpack(2, 'v'); # version needed to extract
            $file['general_bit_flag']   = $this->zipUnpack(2, 'v'); # general purpose bit flag
            $file['compression_method'] = $this->zipUnpack(2, 'v'); # compression method
            $file['lastmod_time']       = $this->zipUnpack(2, 'v'); # last mod file time
            $file['lastmod_date']       = $this->zipUnpack(2, 'v'); # last mod file date
            $file['crc-32']             = $this->zipRead(4); # crc-32
            $file['compressed_size']    = $this->zipUnpack(4, 'V'); # compressed size
            $file['uncompressed_size']  = $this->zipUnpack(4, 'V'); # uncompressed size

            $file_name_len   = $this->zipUnpack(2, 'v'); # filename length
            $extra_field_len = $this->zipUnpack(2, 'v'); # extra field length

            $file_name_len_value   = is_numeric($file_name_len_value = $file_name_len[1]) ? (int) $file_name_len_value : 0;
            $extra_field_len_value = is_numeric($extra_field_len_value = $extra_field_len[1]) ? (int) $extra_field_len_value : 0;

            $file['file_name']             = $file_name_len_value   > 0 ? $this->cleanFileName(fread($fp, $file_name_len_value)) : ''; # filename
            $file['extra_field']           = $extra_field_len_value > 0 ? fread($fp, $extra_field_len_value) : ''; # extra field
            $file['contents_start_offset'] = (int) ftell($fp);

            # Look for the next file
            $compressed_size = is_numeric($compressed_size = $file['compressed_size'][1]) ? (int) $compressed_size : 0;
            fseek($fp, $compressed_size, SEEK_CUR);

            # Mount file table
            $lastmod_date       = is_numeric($lastmod_date = $file['lastmod_date'][1]) ? (int) $lastmod_date : 0;
            $lastmod_time       = is_numeric($lastmod_time = $file['lastmod_time'][1]) ? (int) $lastmod_time : 0;
            $general_bit_flag   = is_numeric($general_bit_flag = $file['general_bit_flag'][1]) ? (int) $general_bit_flag : 0;
            $compression_method = is_numeric($compression_method = $file['compression_method'][1]) ? (int) $compression_method : 0;
            $uncompressed_size  = is_numeric($uncompressed_size = $file['uncompressed_size'][1]) ? (int) $uncompressed_size : 0;
            $version_needed     = is_numeric($version_needed = $file['version_needed'][1]) ? (int) $version_needed : 0;

            $extra_field = is_string($extra_field = $file['extra_field']) ? $extra_field : '';

            return [
                'file_name'          => $file['file_name'],
                'is_dir'             => str_ends_with($file['file_name'], '/'),
                'compression_method' => $compression_method,
                'version_needed'     => $version_needed,
                'lastmod_datetime'   => (int) $this->getTimeStamp($lastmod_date, $lastmod_time),
                'crc-32'             => str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT) .
                                        str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT) .
                                        str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT) .
                                        str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
                'compressed_size'       => $compressed_size,
                'uncompressed_size'     => $uncompressed_size,
                'extra_field'           => $extra_field,
                'general_bit_flag'      => str_pad(decbin($general_bit_flag), 8, '0', STR_PAD_LEFT),
                'contents_start_offset' => $file['contents_start_offset'],
            ];
        }

        return false;
    }

    /**
     * Read from ZIP archive
     *
     * @param      int     $len     The length
     */
    protected function zipRead(int $len): string
    {
        if (abs($len) < 1) {
            return '';
        }

        $fp = $this->fp();
        if ($fp === null) {
            return '';
        }

        $buffer = fread($fp, abs($len));

        return $buffer === false ? '' : $buffer;
    }

    /**
     * Unpack a buffer read from ZIP archive
     *
     * @param      int          $len     The length
     * @param      string       $format  The format
     *
     * @return     array<array-key, mixed>
     */
    protected function zipUnpack(int $len, string $format): array
    {
        $ret = unpack($format, $this->zipRead($len));

        return $ret === false ? [] : $ret;
    }

    /**
     * Gets the time stamp.
     *
     * @param      int       $date   The date
     * @param      int       $time   The time
     *
     * @return     false|int  The time stamp.
     */
    protected function getTimeStamp(int $date, int $time): int|false
    {
        $BINlastmod_date = str_pad(decbin($date), 16, '0', STR_PAD_LEFT);
        $BINlastmod_time = str_pad(decbin($time), 16, '0', STR_PAD_LEFT);
        $lastmod_dateY   = (int) bindec(substr($BINlastmod_date, 0, 7)) + 1980;
        $lastmod_dateM   = (int) bindec(substr($BINlastmod_date, 7, 4));
        $lastmod_dateD   = (int) bindec(substr($BINlastmod_date, 11, 5));
        $lastmod_timeH   = (int) bindec(substr($BINlastmod_time, 0, 5));
        $lastmod_timeM   = (int) bindec(substr($BINlastmod_time, 5, 6));
        $lastmod_timeS   = (int) bindec(substr($BINlastmod_time, 11, 5)) * 2;

        return mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY);
    }

    /**
     * Clean a filename
     */
    protected function cleanFileName(string|false $filename): string
    {
        if ($filename === false) {
            return '';
        }

        $filename = str_replace('../', '', $filename);

        return (string) preg_replace('#^/+#', '', $filename);
    }

    /**
     * Allocate memory
     *
     * @param      float|int  $size   The size
     *
     * @throws     Exception
     */
    protected function memoryAllocate(int|float $size): void
    {
        $mem_used  = (float) (function_exists('memory_get_usage') ? @memory_get_usage() : 4_000_000);
        $mem_limit = @ini_get('memory_limit');
        if ($mem_limit && trim($mem_limit) === '-1' || !Files::str2bytes($mem_limit)) {
            // Cope with memory_limit set to -1 in PHP.ini
            return;
        }

        if ($mem_limit !== '') {
            $mem_limit  = Files::str2bytes($mem_limit);
            $mem_avail  = $mem_limit - $mem_used - (512.0 * 1024.0);
            $mem_needed = (float) $size;

            if ($mem_needed > $mem_avail) {
                if (@ini_set('memory_limit', (string) ($mem_limit + $mem_needed + $mem_used)) === false) {
                    throw new Exception(__('Not enough memory to open file.'));
                }

                if ($this->memory_limit_set === false) {
                    $this->memory_limit_set = true;
                    $this->memory_limit     = (int) $mem_limit;
                }
            }
        }
    }
}
