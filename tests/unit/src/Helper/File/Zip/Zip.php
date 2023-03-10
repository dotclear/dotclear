<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\File\Zip;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;
use SplFileInfo;

class Zip extends atoum
{
    public const ZIP_PHARDATA   = 'phardata.zip';
    public const ZIP_ZIPARCHIVE = 'ziparchive.zip';
    public const ZIP_LEGACY     = 'legacy.zip';

    public const ZIP_FOLDER    = 'dotclear-temp-zip';
    public const ZIP_SUBFOLDER = 'subfolder';
    public const ZIP_FILE_1    = 'file_1.txt';
    public const ZIP_FILE_2    = 'file_2.txt';

    private static function fillFile($filename, int $timeOffset = 0)
    {
        if (!file_exists($filename)) {
            touch($filename, time() - $timeOffset);
            sleep(1);
            clearstatcache(true, $filename);
        }
        if (file_exists($filename)) {
            $fp = fopen($filename, 'wb');
            fwrite($fp, file_get_contents(__FILE__));
            fclose($fp);
            touch($filename, time() - $timeOffset);
            sleep(1);
            clearstatcache(true, $filename);
        }
    }

    public static function prepareTests(string $rootzip)
    {
        // Create a folder with two files and a sub-folder with the two same files inside in the tmp directory
        if (!is_dir($rootzip)) {
            @mkdir($rootzip);
            sleep(1);
            clearstatcache(true, $rootzip);
        }
        if (is_dir($rootzip)) {
            self::fillFile(implode(DIRECTORY_SEPARATOR, [$rootzip, self::ZIP_FILE_1]), 0);      // Now
            self::fillFile(implode(DIRECTORY_SEPARATOR, [$rootzip, self::ZIP_FILE_2]), 3600);   // 1 hour before
            self::fillFile(implode(DIRECTORY_SEPARATOR, [$rootzip, 'secret.notyours']), 0);     // Now

            $subfolder = implode(DIRECTORY_SEPARATOR, [$rootzip, self::ZIP_SUBFOLDER]);
            if (!is_dir($subfolder)) {
                @mkdir($subfolder);
                sleep(1);
                clearstatcache(true, $subfolder);
            }
            if (is_dir($subfolder)) {
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, self::ZIP_FILE_1]), 3600 * 24);    // 1 day before
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, self::ZIP_FILE_2]), 3600 * 36);    // 1.5 day before
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, 'secret.notmine']), 0);            // Now
            }
        }
    }

    public function setUp()
    {
        // Executed *before each* test method.
        $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);
        self::prepareTests($rootzip);
    }

    public function testPharData()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER . '-' . self::ZIP_PHARDATA]);

        // Create archive
        $zip = new \Dotclear\Helper\File\Zip\Zip($archive, null, \Dotclear\Helper\File\Zip\Zip::USE_PHARDATA);

        $type = $zip->getArchiveType();

        $zip->addExclusion('/(notmine|notyours)$/');
        $zip->addDirectory($rootzip, 'dotclear', true);
        $zip->close();

        if (!file_exists($archive)) {
            clearstatcache(true, $archive);
            sleep(2); // Let system finishing its work
        } else {
            if (!stat($archive)) {
                clearstatcache(true, $archive);
                sleep(2); // Let system finishing its work
            }
        }

        clearstatcache(); // stats are cached, clear them!
        $sts = @stat($archive);

        $ret = $sts;
        if ((bool) $ret) {
            $sts = (int) $sts['mtime'];
        }

        $spl = new SplFileInfo($archive);

        $this
            ->variable($ret)
            ->isNotFalse()
            ->integer($sts)
            ->isGreaterThan(0)
            ->variable($zip)
            ->isNotNull()
            ->integer($spl->getSize())
            ->isGreaterThan(0)
        ;

        // Test archive type depending on PHP version and existing classes
        if (class_exists('PharData')) {
            if (version_compare(PHP_VERSION, \Dotclear\Helper\File\Zip\Zip::PHARZIP_BUGGY_81_MAX, '<=') || ((version_compare(PHP_VERSION, \Dotclear\Helper\File\Zip\Zip::PHARZIP_BUGGY_82_MIN, '>=') && version_compare(PHP_VERSION, \Dotclear\Helper\File\Zip\Zip::PHARZIP_BUGGY_82_MAX, '<=')))) {
                if (class_exists('ZipArchive')) {
                    $this
                        ->integer($type)
                        ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_ZIPARCHIVE)
                    ;
                } else {
                    $this
                        ->integer($type)
                        ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_LEGACY)
                    ;
                }
            } else {
                $this
                    ->integer($type)
                    ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_PHARDATA)
                ;
            }
        } elseif (class_exists('ZipArchive')) {
            $this
                ->integer($type)
                ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_ZIPARCHIVE)
            ;
        } else {
            $this
                ->integer($type)
                ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_LEGACY)
            ;
        }
    }

    public function testPharDataWithUnset()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER . '--' . self::ZIP_PHARDATA]);

        // Create archive
        $zip = new \Dotclear\Helper\File\Zip\Zip($archive, null, \Dotclear\Helper\File\Zip\Zip::USE_PHARDATA);

        $zip->addExclusion('/(notmine|notyours)$/');
        $zip->addDirectory($rootzip, 'dotclear', true);
        unset($zip);

        if (!file_exists($archive)) {
            clearstatcache(true, $archive);
            sleep(2); // Let system finishing its work
        } else {
            if (!stat($archive)) {
                clearstatcache(true, $archive);
                sleep(2); // Let system finishing its work
            }
        }

        clearstatcache(); // stats are cached, clear them!
        $sts = @stat($archive);

        $ret = $sts;
        if ((bool) $ret) {
            $sts = (int) $sts['mtime'];
        }

        $spl = new SplFileInfo($archive);

        $this
            ->variable($ret)
            ->isNotFalse()
            ->integer($sts)
            ->isGreaterThan(0)
            ->integer($spl->getSize())
            ->isGreaterThan(0)
        ;
    }

    public function testZipArchive()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER . '-' . self::ZIP_ZIPARCHIVE]);

        // Create archive
        $zip = new \Dotclear\Helper\File\Zip\Zip($archive, null, \Dotclear\Helper\File\Zip\Zip::USE_ZIPARCHIVE);

        $type = $zip->getArchiveType();

        $zip->addExclusion('/(notmine|notyours)$/');
        $zip->addDirectory($rootzip, 'dotclear', true);
        $zip->close();

        if (!file_exists($archive)) {
            clearstatcache(true, $archive);
            sleep(2); // Let system finishing its work
        } else {
            if (!stat($archive)) {
                clearstatcache(true, $archive);
                sleep(2); // Let system finishing its work
            }
        }

        clearstatcache(); // stats are cached, clear them!
        $sts = @stat($archive);

        $ret = $sts;
        if ((bool) $ret) {
            $sts = (int) $sts['mtime'];
        }

        $spl = new SplFileInfo($archive);

        $this
            ->variable($ret)
            ->isNotFalse()
            ->integer($sts)
            ->isGreaterThan(0)
            ->variable($zip)
            ->isNotNull()
            ->integer($spl->getSize())
            ->isGreaterThan(0)
        ;

        // Test archive type depending on PHP version and existing classes
        if (class_exists('ZipArchive')) {
            $this
                ->integer($type)
                ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_ZIPARCHIVE)
            ;
        } else {
            $this
                ->integer($type)
                ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_LEGACY)
            ;
        }
    }

    public function testLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        $zip = new \Dotclear\Helper\File\Zip\Zip($archive, null, \Dotclear\Helper\File\Zip\Zip::USE_LEGACY);

        $type = $zip->getArchiveType();

        $zip->addExclusion('/(notmine|notyours)$/');
        $zip->addDirectory($rootzip, 'dotclear', true);
        $zip->close();

        if (!file_exists($archive)) {
            clearstatcache(true, $archive);
            sleep(2); // Let system finishing its work
        } else {
            if (!stat($archive)) {
                clearstatcache(true, $archive);
                sleep(2); // Let system finishing its work
            }
        }

        clearstatcache(); // stats are cached, clear them!
        $sts = @stat($archive);

        $ret = $sts;
        if ((bool) $ret) {
            $sts = (int) $sts['mtime'];
        }

        $spl = new SplFileInfo($archive);

        $this
            ->variable($ret)
            ->isNotFalse()
            ->integer($sts)
            ->isGreaterThan(0)
            ->variable($zip)
            ->isNotNull()
            ->integer($spl->getSize())
            ->isGreaterThan(0)
        ;

        // Test archive type depending on PHP version and existing classes
        $this
            ->integer($type)
            ->isEqualTo(\Dotclear\Helper\File\Zip\Zip::USE_LEGACY)
        ;
    }

    public function testStreamPharData()
    {
        $this
            ->output(function () {
                $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);

                // Create archive
                $zip = new \Dotclear\Helper\File\Zip\Zip(null, self::ZIP_PHARDATA, \Dotclear\Helper\File\Zip\Zip::USE_PHARDATA);

                $zip->addExclusion('/(notmine|notyours)$/');
                $zip->addDirectory($rootzip, 'dotclear', true);
                $zip->close();
            })
            ->isNotEmpty()
        ;
    }

    public function testStreamZipArchive()
    {
        $this
            ->output(function () {
                $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);

                // Create archive
                $zip = new \Dotclear\Helper\File\Zip\Zip(null, self::ZIP_ZIPARCHIVE, \Dotclear\Helper\File\Zip\Zip::USE_ZIPARCHIVE);

                $zip->addExclusion('/(notmine|notyours)$/');
                $zip->addDirectory($rootzip, 'dotclear', true);
                $zip->close();
            })
            ->isNotEmpty()
        ;
    }

    public function testStreamLegacy()
    {
        $this
            ->output(function () {
                $rootzip = implode(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), self::ZIP_FOLDER]);

                // Create archive
                $zip = new \Dotclear\Helper\File\Zip\Zip(null, self::ZIP_LEGACY, \Dotclear\Helper\File\Zip\Zip::USE_LEGACY);

                $zip->addExclusion('/(notmine|notyours)$/');
                $zip->addDirectory($rootzip, 'dotclear', true);
                $zip->close();
            })
            ->isNotEmpty()
        ;
    }
}
