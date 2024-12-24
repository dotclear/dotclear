<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\File;

use Exception;

/**
 * @class Path
 *
 * Path manipulation utilities
 */
class Path
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
            $absolute = str_starts_with($filename, '/');
        }

        # Standard path form
        if ($os == 'win') {
            $filename = str_replace('\\', '/', $filename);
        }

        # Adding root if !$_abs
        if (!$absolute) {
            $filename = dirname((string) $_SERVER['SCRIPT_FILENAME']) . '/' . $filename;
        }

        # Clean up
        $filename = (string) preg_replace('|/+|', '/', $filename);

        if (strlen($filename) > 1) {
            $filename = (string) preg_replace('|/$|', '', $filename);
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
        $filename = (string) preg_replace(['|^\.\.|', '|/\.\.|', '|\.\.$|'], '', (string) $filename);

        // Replace double slashes by one
        $filename = (string) preg_replace('|/{2,}|', '/', (string) $filename);

        // Remove trailing slash
        $filename = (string) preg_replace('|/$|', '', (string) $filename);

        return $filename;
    }

    /**
     * Make a path from a list of names.
     *
     * The .. (parent folder) names will be reduce if possible by removing their's previous item
     * Ex: path(['main', 'sub', '..', 'inc']) will return 'main/inc'
     *
     * @param   array<int,string>   $elements   The elements
     * @param   string              $separator  The separator
     *
     * @return  string
     */
    public static function reduce(array $elements, string $separator = DIRECTORY_SEPARATOR): string
    {
        // Flattened all elements in list
        $flatten = function (array $list) {
            $new = [];
            array_walk_recursive($list, function ($array) use (&$new) { $new[] = $array; });

            return $new;
        };
        $flat = $flatten($elements);

        if ($separator !== '') {
            // Explode all elements with given separator
            $list = [];
            foreach ($flat as $value) {
                array_push($list, ... explode($separator, (string) $value));
            }
        } else {
            $list = $flat;
        }

        $table = [];
        foreach ($list as $element) {
            if ($element === '..' && count($table)) {
                array_pop($table);  // Remove previous element from $table
            } elseif ($element !== '.') {
                array_push($table, $element);   // Add element to $table
            }
        }

        return implode($separator, $table);
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
     * @return array<string, string>
     */
    public static function info(string $filename): array
    {
        $pathinfo = pathinfo($filename);
        $res      = [];

        $res['dirname']   = $pathinfo['dirname'] ?? '.';
        $res['basename']  = (string) $pathinfo['basename'];
        $res['extension'] = $pathinfo['extension'] ?? '';
        $res['base']      = (string) preg_replace('/\.' . preg_quote($res['extension'], '/') . '$/', '', $res['basename']);

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
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return $root . '/' . $path;
    }

    /**
     * Reset server agressive cache.
     *
     * Try to clear PHP OPcache to avoid running old code after update
     */
    public static function resetServerCache(): void
    {
        try {
            if (extension_loaded('opcache') || extension_loaded('Zend OPcache')) {
                if (function_exists('opcache_get_status') && function_exists('opcache_reset')) {
                    if (ini_get('opcache.restrict_api') !== false && ini_get('opcache.restrict_api') !== '') {
                        // OPCache API is restricted via .htaccess (or web server config), PHP_INI_USER or PHP_INI_PERDIR
                        return;
                    }
                    if (get_cfg_var('opcache.restrict_api') !== false && get_cfg_var('opcache.restrict_api') !== '') {
                        // OPCache API is restricted via PHP.ini
                        return;
                    }

                    if (is_array(opcache_get_status())) {
                        opcache_reset();
                    }
                }
            }
        } catch (Exception) {
        }
    }

    /**
     * Get real directory path.
     *
     * If $dir does not exist, it returns empty string.
     * If $dir is a symbolic link it returns the real path.
     * Else it returns $dir.
     *
     * @param   string  $dir    The directory path to test
     *
     * @return  string  The real path
     */
    public static function dirWithSym(string $dir): string
    {
        if (empty($dir) || !is_dir($dir)) {
            return '';
        }

        $info = pathinfo((string) self::real($dir, false));
        $dir  = ($info['dirname'] ?? '.') . DIRECTORY_SEPARATOR . $info['basename'];

        if (!is_link($dir)) {
            return $dir;
        }

        $info = linkinfo($dir);
        if (-1 === $info || false === $info) {
            return $dir;
        }

        return (string) readlink($dir);
    }
}
