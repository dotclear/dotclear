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

namespace tests\unit\Dotclear\Helper\File;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

/**
 * @tags Path
 */
class Path extends atoum
{
    private string $testDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'File']));

        $this
            ->dump($this->testDirectory)
        ;
    }

    public function testRealUnstrict()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', false)))
                ->isEqualTo($this->testDirectory)
                ->string(str_replace('/', '\\', \Dotclear\Helper\File\Path::real('tests/unit/fixtures/files', false)))
                ->isEqualTo('/tests/unit/fixtures/src/Helper/File')
                ->string(str_replace('/', '\\', \Dotclear\Helper\File\Path::real('tests/./unit/fixtures/files', false)))
                ->isEqualTo('/tests/unit/fixtures/src/Helper/File')
            ;
        } else {
            $this
                ->string(\Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', false))
                ->isEqualTo($this->testDirectory)
                ->string(\Dotclear\Helper\File\Path::real('tests/unit/fixtures/src/Helper/File', false))
                ->isEqualTo('/tests/unit/fixtures/src/Helper/File')
                ->string(\Dotclear\Helper\File\Path::real('tests/./unit/fixtures/src/Helper/File', false))
                ->isEqualTo('/tests/unit/fixtures/src/Helper/File')
            ;
        }
    }

    public function testRealStrict()
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Hack to make it works under Windows
            $this
                ->string(str_replace('/', '\\', \Dotclear\Helper\File\Path::real(__DIR__ . '/../fixtures/files', true)))
                ->isEqualTo($this->testDirectory)
            ;
        } else {
            $this
                ->string(\Dotclear\Helper\File\Path::real(__DIR__ . '/../../../fixtures/src/Helper/File', true))
                ->isEqualTo($this->testDirectory)
            ;
        }
    }

    public function testClean()
    {
        $this
            ->string(\Dotclear\Helper\File\Path::clean('..' . DIRECTORY_SEPARATOR . 'testDirectory'))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory')
            ->string(\Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory')
            ->string(\Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory')
            ->string(\Dotclear\Helper\File\Path::clean(DIRECTORY_SEPARATOR . 'testDirectory' . DIRECTORY_SEPARATOR . '..'))
            ->isEqualTo(DIRECTORY_SEPARATOR . 'testDirectory')
        ;
    }

    public function testInfo()
    {
        $info = \Dotclear\Helper\File\Path::info($this->testDirectory . DIRECTORY_SEPARATOR . '1-one.txt');
        $this
            ->array($info)
            ->isNotEmpty()
            ->hasKeys(['dirname', 'basename', 'extension', 'base'])
            ->string($info['dirname'])
            ->isEqualTo($this->testDirectory)
            ->string($info['basename'])
            ->isEqualTo('1-one.txt')
            ->string($info['extension'])
            ->isEqualTo('txt')
            ->string($info['base'])
            ->string('1-one')
        ;
    }

    public function testFullFromRoot()
    {
        $this
            ->string(\Dotclear\Helper\File\Path::fullFromRoot('/test', '/'))
            ->isEqualTo('/test')
            ->string(\Dotclear\Helper\File\Path::fullFromRoot('test/string', '/home/sweethome'))
            ->isEqualTo('/home/sweethome/test/string')
        ;
    }
}
