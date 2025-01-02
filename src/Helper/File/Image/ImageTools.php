<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File\Image;

use Dotclear\Helper\File\Files;
use Exception;

/**
 * @class ImageTools
 *
 * Class to manipulate images. Some methods are based on https://dev.media-box.net/big/
 */
class ImageTools
{
    /**
     * Image resource
     *
     * @var mixed   resource|GdImage|null|false
     */
    public $res;

    /**
     * Memory limit
     *
     * @var mixed   float|null|false
     */
    public $memory_limit;

    /**
     * Constructor, no parameters.
     */
    public function __construct()
    {
        if (!function_exists('imagegd2')) {
            throw new Exception('GD is not installed');
        }
        $this->res = null;
    }

    /**
     * Close
     *
     * Destroy image resource
     */
    public function close(): void
    {
        if (!empty($this->res)) {
            imagedestroy($this->res);
        }

        if ($this->memory_limit) {
            ini_set('memory_limit', $this->memory_limit);
        }
    }

    /**
     * Load image
     *
     * Loads an image content in memory and set {@link $res} property.
     *
     * @param string    $filename        Image file path
     */
    public function loadImage(string $filename): void
    {
        if (!file_exists($filename)) {
            throw new Exception('Image doest not exists');
        }

        if (($info = @getimagesize($filename)) !== false) {
            $this->memoryAllocate(
                $info[0],
                $info[1],
                $info['channels'] ?? 4
            );

            switch ($info[2]) {
                case 3: // IMAGETYPE_PNG:
                    $this->res = @imagecreatefrompng($filename);
                    if (!empty($this->res)) {
                        @imagealphablending($this->res, false);
                        @imagesavealpha($this->res, true);
                    }

                    break;
                case 2: // IMAGETYPE_JPEG:
                    $this->res = @imagecreatefromjpeg($filename);

                    break;
                case 1: // IMAGETYPE_GIF:
                    $this->res = @imagecreatefromgif($filename);

                    break;
                case 18: // IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $this->res = @imagecreatefromwebp($filename);
                        if (!empty($this->res)) {
                            @imagealphablending($this->res, false);
                            @imagesavealpha($this->res, true);
                        }
                    } else {
                        throw new Exception('WebP image format not supported');
                    }

                    break;
                case 19: // IMAGETYPE_AVIF:
                    if (function_exists('imagecreatefromavif')) {
                        // PHP 8.1+
                        $this->res = @imagecreatefromavif($filename);
                        if (!empty($this->res)) {
                            @imagealphablending($this->res, false);
                            @imagesavealpha($this->res, true);
                        }
                    } else {
                        throw new Exception('AVIF image format not supported');
                    }

                    break;
            }
        }

        if (empty($this->res)) {
            throw new Exception('Unable to load image');
        }
    }

    /**
     * Image width
     *
     * @return int            Image width
     */
    public function getW(): int
    {
        return imagesx($this->res);
    }

    /**
     * Image height
     *
     * @return int            Image height
     */
    public function getH(): int
    {
        return imagesy($this->res);
    }

    /**
     * Allocate memory
     *
     * @param      int        $width   The width
     * @param      int        $height  The height
     * @param      int        $bpp     The bits per pixel
     *
     * @throws     Exception
     */
    protected function memoryAllocate(int $width, int $height, int $bpp = 4): void
    {
        $mem_used  = function_exists('memory_get_usage') ? @memory_get_usage() : 4_000_000;
        $mem_limit = @ini_get('memory_limit');
        if ($mem_limit && trim($mem_limit) === '-1' || !Files::str2bytes($mem_limit)) {
            // Cope with memory_limit set to -1 in PHP.ini
            return;
        }
        if ($mem_limit !== '') {
            $mem_limit = Files::str2bytes($mem_limit);
            $mem_avail = $mem_limit - $mem_used - (512 * 1024);

            $mem_needed = $width * $height * $bpp;

            if ($mem_needed > $mem_avail) {
                if (@ini_set('memory_limit', (string) ($mem_limit + $mem_needed + $mem_used)) === false) {
                    throw new Exception(__('Not enough memory to open image.'));
                }

                if (!$this->memory_limit) {
                    $this->memory_limit = $mem_limit;
                }
            }
        }
    }

    /**
     * Image output
     *
     * Returns image content in a file or as HTML output (with headers)
     *
     * @param string         $type        Image type (png, jpg, webp or avif)
     * @param string|null    $file        Output file. If null, output will be echoed in STDOUT
     * @param int            $qual        JPEG/WepB/Avif image quality (0-100)
     */
    public function output(string $type = 'png', ?string $file = null, int $qual = 90): bool
    {
        if (!$file) {
            header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
            switch (strtolower($type)) {
                case 'png':
                    header('Content-type: image/png');
                    imagepng($this->res, null, -1, -1);

                    return true;
                case 'jpeg':
                case 'jpg':
                    header('Content-type: image/jpeg');
                    imagejpeg($this->res, null, $qual);

                    return true;
                case 'gif':
                    header('Content-type: image/gif');
                    imagegif($this->res, null);

                    return true;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        header('Content-type: image/webp');
                        imagewebp($this->res, null, $qual);

                        return true;
                    }

                    return false;
                case 'avif':
                    if (function_exists('imageavif')) {
                        // PHP 8.1+
                        $qual = $qual < 0 ? -1 : abs($qual / 10);
                        header('Content-type: image/avif');
                        imageavif($this->res, null, (int) $qual, -1);

                        return true;
                    }

                    return false;
                default:
                    return false;
            }
        } elseif (is_writable(dirname($file))) {
            switch (strtolower($type)) {
                case 'png':
                    imagepng($this->res, $file);

                    return file_exists($file);

                case 'jpeg':
                case 'jpg':
                    imagejpeg($this->res, $file, $qual);

                    return file_exists($file);

                case 'gif':
                    imagegif($this->res, $file);

                    return file_exists($file);

                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($this->res, $file, $qual);

                        return file_exists($file);
                    }

                    return false;
                case 'avif':
                    if (function_exists('imageavif')) {
                        imageavif($this->res, $file, $qual);

                        return file_exists($file);
                    }

                    return false;

                default:
                    return false;
            }
        }

        return false;
    }

    /**
     * Resize image
     *
     * @param mixed         $width          Image width (px or percent)
     * @param mixed         $height         Image height (px or percent)
     * @param string        $mode           Crop mode (force, crop, ratio)
     * @param boolean       $expand         Allow resize of image
     *
     * @return true
     */
    public function resize($width, $height, string $mode = 'ratio', bool $expand = false): bool
    {
        $computed_height = 0;
        $computed_width  = 0;

        $imgage_width  = $this->getW();
        $imgage_height = $this->getH();

        if (strpos((string) $width, '%', 0)) {
            $width = $imgage_width * (int) trim((string) $width, '%') / 100;
        }

        if (strpos((string) $height, '%', 0)) {
            $height = $imgage_height * (int) trim((string) $height, '%') / 100;
        }

        $ratio = $imgage_width / $imgage_height;

        // Guess resize
        if ($mode === 'ratio') {
            $computed_width = 99999;
            if ($height > 0) {
                $computed_height = $height;
                $computed_width  = $computed_height * $ratio;
            }
            if ($width > 0 && $computed_width > $width) {
                $computed_width  = $width;
                $computed_height = $computed_width / $ratio;
            }

            if (!$expand && $computed_width > $imgage_width) {
                $computed_width  = $imgage_width;
                $computed_height = $imgage_height;
            }
        } else {
            // Crop source image
            $computed_width  = $width;
            $computed_height = $height;
        }

        if ($mode === 'force') {
            $computed_width  = $width  > 0 ? $width : $height * $ratio;
            $computed_height = $height > 0 ? $height : $width / $ratio;

            if (!$expand && $computed_width > $imgage_width) {
                $computed_width  = $imgage_width;
                $computed_height = $imgage_height;
            }

            $crop_width    = $imgage_width;
            $crop_height   = $imgage_height;
            $offset_width  = 0;
            $offset_height = 0;
        } else {
            // Guess real viewport of image
            $innerRatio = $computed_width / $computed_height;
            if ($ratio >= $innerRatio) {
                $crop_height   = $imgage_height;
                $crop_width    = $imgage_height * $innerRatio;
                $offset_height = 0;
                $offset_width  = ($imgage_width - $crop_width) / 2;
            } else {
                $crop_width    = $imgage_width;
                $crop_height   = $imgage_width / $innerRatio;
                $offset_width  = 0;
                $offset_height = ($imgage_height - $crop_height) / 2;
            }
        }

        if ($computed_width < 1) {
            $computed_width = 1;
        }
        if ($computed_height < 1) {
            $computed_height = 1;
        }

        // convert float to int
        $offset_width    = (int) $offset_width;
        $offset_height   = (int) $offset_height;
        $computed_width  = (int) $computed_width;
        $computed_height = (int) $computed_height;
        $crop_width      = (int) $crop_width;
        $crop_height     = (int) $crop_height;

        // truecolor is 24 bit RGB, ie. 3 bytes per pixel.
        $this->memoryAllocate($computed_width, $computed_height, 3);

        $dest = imagecreatetruecolor($computed_width, $computed_height);    // @phpstan-ignore-line
        if ($dest !== false) {
            // Fill image with neutral gray (#808080)
            imagefill($dest, 0, 0, (int) imagecolorallocate($dest, 128, 128, 128));

            // Disable blending mode
            @imagealphablending($dest, false);

            // Preserve alpha channel of image
            @imagesavealpha($dest, true);

            // Copy and resize (with resampling) from source to destination
            imagecopyresampled($dest, $this->res, 0, 0, $offset_width, $offset_height, $computed_width, $computed_height, $crop_width, $crop_height);

            imagedestroy($this->res);
            $this->res = $dest;
        }

        return true;
    }
}
