<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Exception;

/**
 * Helper for theme configurators
 *
 * @since 2.7
 */
class ThemeConfig
{
    /**
     * Compute contrast ratio between two colors
     *
     * @param  string $color      text color
     * @param  string $background background color
     *
     * @return float             computed ratio
     */
    public static function computeContrastRatio(?string $color, ?string $background): float
    {
        // Compute contrast ratio between two colors

        if (!$color || !$background) {
            return 0;
        }

        $color = self::adjustColor($color);
        if (($color == '') || (strlen($color) != 7)) {
            return 0;
        }

        $background = self::adjustColor($background);
        if (($background == '') || (strlen($background) != 7)) {
            return 0;
        }

        $l1 = (0.2126 * (hexdec(substr($color, 1, 2)) / 255) ** 2.2) + (0.7152 * (hexdec(substr($color, 3, 2)) / 255) ** 2.2) + (0.0722 * (hexdec(substr($color, 5, 2)) / 255) ** 2.2);

        $l2 = (0.2126 * (hexdec(substr($background, 1, 2)) / 255) ** 2.2) + (0.7152 * (hexdec(substr($background, 3, 2)) / 255) ** 2.2) + (0.0722 * (hexdec(substr($background, 5, 2)) / 255) ** 2.2);

        if ($l1 <= $l2) {
            [$l1, $l2] = [$l2, $l1];
        }

        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /**
     * Compute WCAG contrast ration level
     *
     * @param  float   $ratio computed ratio between foreground and backround color
     * @param  string  $size  font size as defined in CSS
     * @param  boolean $bold  true if bold font
     *
     * @return string         WCAG contrast ratio level (AAA, AA or <nothing>)
     */
    public static function contrastRatioLevel(float $ratio, ?string $size, bool $bold = false): string
    {
        if ($size && $size !== '') {
            if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex|rem|ch)?$/', $size, $matches)) {
                if (empty($matches[2])) {
                    $matches[2] = 'em';
                }
                $absolute_size = match ($matches[2]) {
                    '%'  => (float) $matches[1] / 100,
                    'pt' => (float) $matches[1] / 12,
                    'px' => (float) $matches[1] / 16,
                    'rem', 'em' => (float) $matches[1],
                    'ex', 'ch' => (float) $matches[1] / 2,
                };

                if ($absolute_size) {
                    $large = ((($absolute_size > 1.5) && (!$bold)) || (($absolute_size > 1.2) && ($bold)));

                    // Check ratio
                    if ($ratio > 7 || (($ratio > 4.5) && $large)) {
                        return 'AAA';
                    } elseif ($ratio > 4.5 || (($ratio > 3) && $large)) {
                        return 'AA';
                    }
                }
            }
        }

        return '';
    }

    /**
     * Return full information about constrat ratio
     *
     * @param  string  $color      text color
     * @param  string  $background background color
     * @param  string  $size       font size
     * @param  boolean $bold       bold font
     *
     * @return string              contrast ratio including WCAG level
     */
    public static function contrastRatio(?string $color, ?string $background, string $size = '', bool $bold = false): string
    {
        if ($color && $background && $color !== '' && $background !== '') {
            $ratio = self::computeContrastRatio($color, $background);
            $level = self::contrastRatioLevel($ratio, $size, $bold);

            return
            sprintf(__('ratio %.1f'), $ratio) . ($level !== '' ? ' ' . sprintf(__('(%s)'), $level) : '');
        }

        return '';
    }

    /**
     * Check font size
     *
     * @param  string $size font size
     *
     * @return string|null    checked font size
     */
    public static function adjustFontSize(?string $size): ?string
    {
        if ($size) {
            if (preg_match('/^([0-9.]+)\s*(%|pt|px|em|ex|rem|ch)?$/', $size, $matches)) {
                if (empty($matches[2])) {
                    $matches[2] = 'em';
                }

                return $matches[1] . $matches[2];
            }
        }

        return $size;
    }

    /**
     * Check object position, should be x:y
     *
     * @param  string $position position
     *
     * @return string    checked position
     */
    public static function adjustPosition(?string $position): string
    {
        if (!$position) {
            return '';
        }

        if (!preg_match('/^\d+(:\d+)?$/', $position)) {
            return '';
        }
        $position = explode(':', $position);

        return $position[0] . (count($position) == 1 ? ':0' : ':' . $position[1]);
    }

    /**
     * Check a CSS color
     *
     * @param  string $color CSS color
     *
     * @return string    checked CSS color
     */
    public static function adjustColor(?string $color): string
    {
        if (!$color) {
            return '';
        }

        $color = strtoupper($color);

        if (preg_match('/^[A-F0-9]{3,6}$/', $color)) {
            $color = '#' . $color;
        }
        if (preg_match('/^#[A-F0-9]{6}$/', $color)) {
            return $color;
        }
        if (preg_match('/^#[A-F0-9]{3,}$/', $color)) {
            return '#' . substr($color, 1, 1) . substr($color, 1, 1) . substr($color, 2, 1) . substr($color, 2, 1) . substr($color, 3, 1) . substr($color, 3, 1);
        }

        return '';
    }

    /**
     * Check and clean CSS. (not implemented)
     *
     * @todo    Implement ModulesList::displaySort method
     *
     * @param   string  $css    CSS to be checked
     *
     * @return  string  checked CSS
     */
    public static function cleanCSS(string $css): string
    {
        return $css;
    }

    /**
     * Return real path of a user defined CSS
     *
     * @param  string $folder CSS folder
     *
     * @return string         real path of CSS
     */
    public static function cssPath(string $folder): string
    {
        return Path::real(App::blog()->publicPath()) . '/' . $folder;
    }

    /**
     * Return URL of a user defined CSS
     *
     * @param  string $folder CSS folder
     *
     * @return string         CSS URL
     */
    public static function cssURL(string $folder): string
    {
        return App::blog()->settings()->system->public_url . '/' . $folder;
    }

    /**
     * Check if user defined CSS may be written
     *
     * @param  string  $folder CSS folder
     * @param  boolean $create create CSS folder if necessary
     *
     * @return boolean          true if CSS folder exists and may be written, else false
     */
    public static function canWriteCss(string $folder, bool $create = false): bool
    {
        $public = Path::real(App::blog()->publicPath());
        $css    = self::cssPath($folder);

        if ($public === false || !is_dir($public)) {
            App::error()->add(__('The \'public\' directory does not exist.'));

            return false;
        }

        if (!is_dir($css)) {
            if (!is_writable($public)) {
                App::error()->add(sprintf(__('The \'%s\' directory cannot be modified.'), 'public'));

                return false;
            }
            if ($create) {
                Files::makeDir($css);
            }

            return true;
        }

        if (!is_writable($css)) {
            App::error()->add(sprintf(__('The \'%s\' directory cannot be modified.'), 'public/' . $folder));

            return false;
        }

        return true;
    }

    /**
     * Store CSS property value in associated array
     *
     * @param  array<string, array<string, string>>     $css      CSS associated array
     * @param  string                                   $selector selector
     * @param  string                                   $prop     property
     * @param  mixed                                    $value    value
     */
    public static function prop(array &$css, string $selector, string $prop, $value): void
    {
        if ($value) {
            $css[$selector][$prop] = $value;
        }
    }

    /**
     * Store background image property in CSS associated array
     *
     * @param  string                                   $folder image folder
     * @param  array<string, array<string, string>>     $css    CSS associated array
     * @param  string                                   $selector   selector
     * @param  boolean                                  $value  false for default, true if image should be set
     * @param  string                                   $image  image filename
     */
    public static function backgroundImg(string $folder, array &$css, string $selector, bool $value, string $image): void
    {
        $file = self::imagesPath($folder) . '/' . $image;
        if ($value && file_exists($file)) {
            $css[$selector]['background-image'] = 'url(' . self::imagesURL($folder) . '/' . $image . ')';
        }
    }

    /**
     * Write CSS file
     *
     * @param  string $folder CSS folder
     * @param  string $theme  CSS filename
     * @param  string $css    CSS file content
     */
    public static function writeCss(string $folder, string $theme, string $css): void
    {
        file_put_contents(self::cssPath($folder) . '/' . $theme . '.css', $css);
    }

    /**
     * Delete CSS file
     *
     * @param  string $folder CSS folder
     * @param  string $theme  CSS filename to be removed
     */
    public static function dropCss(string $folder, string $theme): void
    {
        $file = Path::real(self::cssPath($folder) . '/' . $theme . '.css');
        if ($file && is_writable(dirname($file))) {
            @unlink($file);
        }
    }

    /**
     * Return public URL of user defined CSS
     *
     * @param  string $folder CSS folder
     *
     * @return mixed         CSS file URL
     */
    public static function publicCssUrlHelper(string $folder)
    {
        $theme = App::blog()->settings()->system->theme;
        $url   = self::cssURL($folder);
        $path  = self::cssPath($folder);

        if (file_exists($path . '/' . $theme . '.css')) {
            return $url . '/' . $theme . '.css';
        }
    }

    /**
     * Return real path of folder images
     *
     * @param  string $folder images folder
     *
     * @return string|false         real path of folder
     */
    public static function imagesPath(string $folder): string|bool
    {
        return Path::real(App::blog()->publicPath()) . '/' . $folder;
    }

    /**
     * Return URL of images folder
     *
     * @param  string $folder images folder
     *
     * @return string         URL of images folder
     */
    public static function imagesURL(string $folder): string
    {
        return App::blog()->settings()->system->public_url . '/' . $folder;
    }

    /**
     * Check if images folder exists and may be written
     *
     * @param  string  $folder images folder
     * @param  bool    $create create the folder if not exists
     *
     * @return bool             true if folder exists and may be written
     */
    public static function canWriteImages(string $folder, bool $create = false): bool
    {
        $public = Path::real(App::blog()->publicPath());
        $imgs   = self::imagesPath($folder);

        if ($imgs === false) {
            App::error()->add(sprintf(__('The \'%s\' directory cannot be created.'), $folder));

            return false;
        }

        if (!function_exists('imagecreatetruecolor') || !function_exists('imagepng') || !function_exists('imagecreatefrompng')) {
            App::error()->add(__('At least one of the following functions is not available: ' .
                'imagecreatetruecolor, imagepng & imagecreatefrompng.'));

            return false;
        }

        if ($public === false || !is_dir($public)) {
            App::error()->add(__('The \'public\' directory does not exist.'));

            return false;
        }

        if (!is_dir($imgs)) {
            if (!is_writable($public)) {
                App::error()->add(sprintf(__('The \'%s\' directory cannot be modified.'), 'public'));

                return false;
            }
            if ($create) {
                Files::makeDir($imgs);
            }

            return true;
        }

        if (!is_writable($imgs)) {
            App::error()->add(sprintf(__('The \'%s\' directory cannot be modified.'), 'public/' . $folder));

            return false;
        }

        return true;
    }

    /**
     * Upload an image in images folder
     *
     * @param  string                $folder images folder
     * @param  array<string, mixed>  $file   selected image file
     * @param  int                   $width  check accurate width of uploaded image if <> 0
     *
     * @return string         full pathname of uploaded image
     */
    public static function uploadImage(string $folder, array $file, int $width = 0): string
    {
        if (!self::canWriteImages($folder, true)) {
            throw new Exception(__('Unable to create images.'));
        }

        $name = $file['name'];
        $type = Files::getMimeType($name);

        if ($type != 'image/jpeg' && $type != 'image/png') {
            throw new Exception(__('Invalid file type.'));
        }

        $dest = self::imagesPath($folder) . '/uploaded' . ($type == 'image/png' ? '.png' : '.jpg');

        if (@move_uploaded_file($file['tmp_name'], $dest) === false) {
            throw new Exception(__('An error occurred while writing the file.'));
        }

        if ($width) {
            $size = getimagesize($dest);
            if ($size !== false && $size[0] != $width) {
                throw new Exception(sprintf(__('Uploaded image is not %s pixels wide.'), $width));
            }
        }

        return $dest;
    }

    /**
     * Delete an image from images folder (with its thumbnails if any)
     *
     * @param  string $folder images folder
     * @param  string $img    image filename
     */
    public static function dropImage(string $folder, string $img): void
    {
        $img = Path::real(self::imagesPath($folder) . '/' . $img);
        if ($img !== false && is_writable(dirname($img))) {
            // Delete thumbnails if any
            try {
                App::media()->imageThumbRemove($img);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
            // Delete image
            @unlink($img);
        }
    }
}
