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

/**
 * @tags Zip
 */
class Zip extends atoum
{
    public const ZIP_LEGACY = 'legacy.zip';

    public const ZIP_FOLDER    = 'dotclear-temp-zip';
    public const ZIP_SUBFOLDER = 'subfolder';
    public const ZIP_FILE_1    = 'file_1.txt';
    public const ZIP_FILE_2    = 'file_2.txt';
    public const ZIP_SECRET_1  = 'secret.notyours';
    public const ZIP_SECRET_2  = 'secret.notmine';

    public const ZIP_NAME = 'dotclear';

    public const ZIP_EXCLUDE = '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#';

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
            self::fillFile(implode(DIRECTORY_SEPARATOR, [$rootzip, self::ZIP_SECRET_1]), 0);     // Now

            $subfolder = implode(DIRECTORY_SEPARATOR, [$rootzip, self::ZIP_SUBFOLDER]);
            if (!is_dir($subfolder)) {
                @mkdir($subfolder);
                sleep(1);
                clearstatcache(true, $subfolder);
            }
            if (is_dir($subfolder)) {
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, self::ZIP_FILE_1]), 3600 * 24);    // 1 day before
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, self::ZIP_FILE_2]), 3600 * 36);    // 1.5 day before
                self::fillFile(implode(DIRECTORY_SEPARATOR, [$subfolder, self::ZIP_SECRET_2]), 0);            // Now
            }
        }
    }

    public function setUp()
    {
        $this
            ->dump(sys_get_temp_dir())    // Equal to $TMPDIR on MacOS
            ->dump(realpath(sys_get_temp_dir()))
        ;

        // Executed *before each* test method.
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        self::prepareTests($rootzip);
    }

    private function openArchive(string $archive, &$fp)
    {
        $fp = @fopen($archive, 'wb');
        if ($fp === false) {
            return null;
        }

        return new \Dotclear\Helper\File\Zip\Zip($fp);
    }

    private function closeArchive($zip, $fp, bool $write = false)
    {
        if ($write) {
            $zip->write();
        }
        fclose($fp);
        $zip->close();
    }

    public function testLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        $fp  = null;
        $zip = $this->openArchive($archive, $fp);

        $zip->addExclusion('/(notmine|notyours)$/');
        $zip->addExclusion(self::ZIP_EXCLUDE);
        $zip->addDirectory($rootzip, self::ZIP_NAME, true);

        // Close archive
        $this->closeArchive($zip, $fp, true);

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
    }

    public function testStreamLegacy()
    {
        $this
            ->output(function () {
                $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);

                // Create archive
                $fp  = null;
                $zip = $this->openArchive('php://output', $fp);

                $zip->addExclusion('/(notmine|notyours)$/');
                $zip->addExclusion(self::ZIP_EXCLUDE);
                $zip->addDirectory($rootzip, self::ZIP_NAME, true);

                // Close archive
                $this->closeArchive($zip, $fp, true);
            })
            ->isNotEmpty()
        ;
    }
}
