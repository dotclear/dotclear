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

/**
 * @tags Unzip
 */
class Unzip extends atoum
{
    public const ZIP_LEGACY = 'legacy.zip';

    public const ZIP_FOLDER    = 'dotclear-temp-unzip';
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

    public static function prepareArchive(string $archive, string $rootzip, ?string $dirname)
    {
        // Create archive

        $fp = @fopen($archive, 'wb');
        if ($fp === false) {
            return;
        }
        $zip = new \Dotclear\Helper\File\Zip\Zip($fp);
        $zip->addDirectory($rootzip, $dirname, true);
        $zip->addExclusion(self::ZIP_EXCLUDE);
        $zip->write();
        fclose($fp);
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
        sleep(2);
    }

    public static function sortArray(array $array): array
    {
        $values = $array;
        sort($values);

        return $values;
    }

    public function testLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Uncompress archive
        $folder = $rootzip . '-' . substr(self::ZIP_LEGACY, 0, -4);
        if (is_dir($folder)) {
            \Dotclear\Helper\File\Files::delTree($folder);
            clearstatcache(); // stats are cached, clear them!
            sleep(2);
        }

        $unzip = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $unzip->setExcludePattern('/(notmine|notyours)$/');
        $unzip->unzipAll($folder);

        clearstatcache(); // stats are cached, clear them!
        sleep(2);

        $this
            //->dump($unzip->getFilesList())
            //->dump($unzip->getDirsList())
            //->dump($unzip->getManifest())
            ->variable($unzip)
            ->isNotNull()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_FILE_1])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_FILE_2])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SECRET_1])))
            ->isFalse()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER, self::ZIP_FILE_1])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER, self::ZIP_FILE_2])))
            ->isTrue()
            ->boolean(file_exists(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER, self::ZIP_SECRET_2])))
            ->isFalse()
            ->boolean(is_dir(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_FILE_1])))
            ->isFalse()
            ->boolean(is_dir(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_FILE_2])))
            ->isFalse()
            ->boolean(is_dir(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER, self::ZIP_FILE_1])))
            ->isFalse()
            ->boolean(is_dir(implode(DIRECTORY_SEPARATOR, [$folder, self::ZIP_NAME, self::ZIP_SUBFOLDER, self::ZIP_FILE_2])))
            ->isFalse()
        ;
    }

    public function testGetListLegacyWithExclusion()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Test list with exclusion

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getList(false, '/(notmine|notyours)$/');

        $this
            ->array($manifest)
            ->hasSize(6)
        ;

        // Check if the list is always the same
        $manifest = $unzip->getList(false, '/(notmine|notyours)$/');

        $this
            ->array($manifest)
            ->hasSize(6)
        ;
    }

    public function testGetListLegacyWithoutExclusion()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Test list without exclusion

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getList(false, false);

        $this
            ->array($manifest)
            ->hasSize(8)
        ;

        // Check if the list is always the same
        $manifest = $unzip->getList(false, false);

        $this
            ->array($manifest)
            ->hasSize(8)
        ;

        $unzip->close();
    }

    public function testGetFilesListLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getFilesList();

        $this
            ->array(self::sortArray($manifest))
            ->isEqualTo(self::sortArray([
                'dotclear/subfolder/file_1.txt',
                'dotclear/subfolder/secret.notmine',
                'dotclear/subfolder/file_2.txt',
                'dotclear/file_1.txt',
                'dotclear/file_2.txt',
                'dotclear/secret.notyours',
            ]))
            ->hasSize(6)
        ;

        // Check if the list is always the same
        $manifest = $unzip->getFilesList();

        $this
            ->array(self::sortArray($manifest))
            ->isEqualTo(self::sortArray([
                'dotclear/subfolder/file_1.txt',
                'dotclear/subfolder/secret.notmine',
                'dotclear/subfolder/file_2.txt',
                'dotclear/file_1.txt',
                'dotclear/file_2.txt',
                'dotclear/secret.notyours',
            ]))
            ->hasSize(6)
        ;

        $unzip->close();
    }

    public function testGetDirsListLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getDirsList();

        $this
            ->array(self::sortArray($manifest))
            ->isEqualTo(self::sortArray([
                'dotclear',
                'dotclear/subfolder',
            ]))
            ->hasSize(2)
        ;

        // Check if the list is always the same
        $manifest = $unzip->getDirsList();

        $this
            ->array(self::sortArray($manifest))
            ->isEqualTo(self::sortArray([
                'dotclear',
                'dotclear/subfolder',
            ]))
            ->hasSize(2)
        ;

        $unzip->close();
    }

    public function testGetRootDirLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getRootDir();

        $this
            ->string($manifest)
            ->isEqualTo('dotclear')
        ;

        // Check if the rootdir is always the same
        $manifest = $unzip->getRootDir();

        $this
            ->string($manifest)
            ->isEqualTo('dotclear')
        ;

        $unzip->close();
    }

    public function testGetRootDirNoRootLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-no-root-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, null);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->getRootDir();

        $this
            ->boolean($manifest)
            ->isFalse()
        ;

        // Check if the rootdir is always the same
        $manifest = $unzip->getRootDir();

        $this
            ->boolean($manifest)
            ->isFalse()
        ;

        $manifest = $unzip->getFilesList();
        $tmpdir   = substr(realpath(sys_get_temp_dir()), 1);

        $this
            ->array(self::sortArray($manifest))
            ->isEqualTo(self::sortArray([
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_SUBFOLDER, self::ZIP_FILE_1]),
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_SUBFOLDER, self::ZIP_SECRET_2]),
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_SUBFOLDER, self::ZIP_FILE_2]),
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_FILE_1]),
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_FILE_2]),
                implode(DIRECTORY_SEPARATOR, [$tmpdir, self::ZIP_FOLDER, self::ZIP_SECRET_1]),
            ]))
        ;

        $unzip->close();
    }

    public function testIsEmptyLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->isEmpty();

        $this
            ->boolean($manifest)
            ->isFalse()
        ;

        // Check if the result is always the same
        $manifest = $unzip->isEmpty();

        $this
            ->boolean($manifest)
            ->isFalse()
        ;

        $unzip->close();
    }

    public function testHasFileLegacy()
    {
        $rootzip = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER]);
        $archive = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), self::ZIP_FOLDER . '-' . self::ZIP_LEGACY]);

        // Create archive
        self::prepareArchive($archive, $rootzip, self::ZIP_NAME);

        // Open archive
        $unzip    = new \Dotclear\Helper\File\Zip\Unzip($archive);
        $manifest = $unzip->hasFile(implode(DIRECTORY_SEPARATOR, ['dotclear', self::ZIP_SUBFOLDER, self::ZIP_FILE_1]));

        $this
            ->boolean($manifest)
            ->isTrue()
        ;

        // Check if the result is always the same
        $manifest = $unzip->hasFile(implode(DIRECTORY_SEPARATOR, ['dotclear', self::ZIP_SUBFOLDER, self::ZIP_FILE_1]));

        $this
            ->boolean($manifest)
            ->isTrue()
        ;

        // Test with unknown file
        $manifest = $unzip->hasFile(implode(DIRECTORY_SEPARATOR, ['dotclear', self::ZIP_FOLDER, self::ZIP_FILE_1]));

        $this
            ->boolean($manifest)
            ->isFalse()
        ;

        $unzip->close();
    }
}
