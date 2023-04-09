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
 * @tags Filemanager
 */
class File extends atoum
{
    private string $root;
    private string $url;

    public function __construct()
    {
        parent::__construct();

        $this->root = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'FileManager']));
        $this->url  = 'https://example.com/public/';
    }

    public function test()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root);

        $this
            ->object($file)
            ->isNotNull()
            ->class('\Dotclear\Helper\File\File')
        ;
    }

    public function testStdPropertiesFile()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root, $this->url);

        $this
            ->string($file->file)
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'valid.md')
            ->string($file->basename)
            ->isEqualTo('valid.md')
            ->string($file->dir)
            ->isEqualTo($this->root)
            ->string($file->file_url)
            ->isEqualTo($this->url . 'valid.md')
            ->string($file->dir_url)
            ->isEqualTo(rtrim($this->url, '/'))
            ->string($file->extension)
            ->isEqualTo('md')
            ->string($file->relname)
            ->isEqualTo('valid.md')
            ->boolean($file->parent)
            ->isFalse()
            ->string($file->type)
            ->isEqualTo('application/octet-stream')
            ->string($file->type_prefix)
            ->isEqualTo('application')
            ->integer($file->mtime)
            ->isGreaterThan(0)
            ->integer($file->size)
            ->isEqualTo(12)
            ->integer($file->mode)
            ->isGreaterThan(0)
            ->integer($file->uid)
            ->isGreaterThan(0)
            ->integer($file->gid)
            ->isGreaterThan(0)
            ->boolean($file->w)
            ->isTrue()
            ->boolean($file->d)
            ->isFalse()
            ->boolean($file->x)
            ->isFalse()
            ->boolean($file->f)
            ->isTrue()
            ->boolean($file->del)
            ->isTrue()
        ;
    }

    public function testStdPropertiesDir()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'sub', $this->root, $this->url);

        $this
            ->string($file->file)
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub')
            ->string($file->basename)
            ->isEqualTo('sub')
            ->string($file->dir)
            ->isEqualTo($this->root)
            ->string($file->file_url)
            ->isEqualTo($this->url . 'sub')
            ->string($file->dir_url)
            ->isEqualTo(rtrim($this->url, '/'))
            ->string($file->extension)
            ->isEqualTo('')
            ->string($file->relname)
            ->isEqualTo('sub')
            ->boolean($file->parent)
            ->isFalse()
            ->string($file->type)
            ->isEqualTo('application/octet-stream')
            ->string($file->type_prefix)
            ->isEqualTo('application')
            ->integer($file->mtime)
            ->isGreaterThan(0)
            ->integer($file->size)
            ->isGreaterThan(0)
            ->integer($file->mode)
            ->isGreaterThan(0)
            ->integer($file->uid)
            ->isGreaterThan(0)
            ->integer($file->gid)
            ->isGreaterThan(0)
            ->boolean($file->w)
            ->isTrue()
            ->boolean($file->d)
            ->isTrue()
            ->boolean($file->x)
            ->isTrue()
            ->boolean($file->f)
            ->isFalse()
            ->boolean($file->del)
            ->isFalse()
        ;
    }

    public function testStdPropertiesDirFile()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'valid.md', $this->root, $this->url);

        $this
            ->string($file->file)
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'valid.md')
            ->string($file->basename)
            ->isEqualTo('valid.md')
            ->string($file->dir)
            ->isEqualTo($this->root . '/sub')
            ->string($file->file_url)
            ->isEqualTo($this->url . 'sub/valid.md')
            ->string($file->dir_url)
            ->isEqualTo(rtrim($this->url . 'sub', '/'))
            ->string($file->extension)
            ->isEqualTo('md')
            ->string($file->relname)
            ->isEqualTo('sub/valid.md')
            ->boolean($file->parent)
            ->isFalse()
            ->string($file->type)
            ->isEqualTo('application/octet-stream')
            ->string($file->type_prefix)
            ->isEqualTo('application')
            ->integer($file->mtime)
            ->isGreaterThan(0)
            ->integer($file->size)
            ->isGreaterThan(0)
            ->integer($file->mode)
            ->isGreaterThan(0)
            ->integer($file->uid)
            ->isGreaterThan(0)
            ->integer($file->gid)
            ->isGreaterThan(0)
            ->boolean($file->w)
            ->isTrue()
            ->boolean($file->d)
            ->isFalse()
            ->boolean($file->x)
            ->isFalse()
            ->boolean($file->f)
            ->isTrue()
            ->boolean($file->del)
            ->isTrue()
        ;
    }

    public function testStdPropertiesDirUp()
    {
        $file = new \Dotclear\Helper\File\File(implode(DIRECTORY_SEPARATOR, [$this->root, 'sub', 'subsub', '..']), $this->root, $this->url);

        $this
            ->string($file->file)
            ->isEqualTo($this->root . DIRECTORY_SEPARATOR . 'sub')
            ->string($file->basename)
            ->isEqualTo('sub')
            ->string($file->dir)
            ->isEqualTo($this->root)
            ->string($file->file_url)
            ->isEqualTo($this->url . 'sub')
            ->string($file->dir_url)
            ->isEqualTo(rtrim($this->url, '/'))
            ->string($file->extension)
            ->isEqualTo('')
            ->string($file->relname)
            ->isEqualTo('sub')
            ->boolean($file->parent)
            ->isFalse()
            ->string($file->type)
            ->isEqualTo('application/octet-stream')
            ->string($file->type_prefix)
            ->isEqualTo('application')
            ->integer($file->mtime)
            ->isGreaterThan(0)
            ->integer($file->size)
            ->isGreaterThan(0)
            ->integer($file->mode)
            ->isGreaterThan(0)
            ->integer($file->uid)
            ->isGreaterThan(0)
            ->integer($file->gid)
            ->isGreaterThan(0)
            ->boolean($file->w)
            ->isTrue()
            ->boolean($file->d)
            ->isTrue()
            ->boolean($file->x)
            ->isTrue()
            ->boolean($file->f)
            ->isFalse()
            ->boolean($file->del)
            ->isFalse()
        ;
    }

    public function testUserDefinedProperties()
    {
        $file = new \Dotclear\Helper\File\File($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root);

        $this
            ->when($file->mySweetProperty = true)
            ->boolean(isset($file->mySweetProperty))
            ->isTrue()
            ->boolean(isset($file->myUnsetProperty))
            ->isFalse()
            ->boolean($file->mySweetProperty)
            ->isTrue()
        ;

        unset($file->mySweetProperty);

        $this
            ->boolean(isset($file->mySweetProperty))
            ->isFalse()
        ;
    }
}
