<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\File;

use PHPUnit\Framework\TestCase;

class MediaFileTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'fixtures', 'src', 'Helper', 'FileManager']));
    }

    public function test(): void
    {
        $file = new \Dotclear\Helper\File\MediaFile($this->root . DIRECTORY_SEPARATOR . 'valid.md', $this->root);

        // Nothing to really test here, waiting for getter/setter and may be other methods if any will be defined

        $file->media_id      = 13;
        $file->media_dt      = (int) strtotime('now');
        $file->media_dtstr   = \Dotclear\Helper\Date::str('%Y-%m-%d %H:%M', $file->media_dt);
        $file->media_icon    = 'icon';
        $file->media_image   = true;
        $file->media_meta    = null;
        $file->media_preview = false;
        $file->media_priv    = false;
        $file->media_thumb   = [];
        $file->media_type    = 'image';
        $file->media_user    = 'me';

        $this->assertEquals(
            $this->root . DIRECTORY_SEPARATOR . 'valid.md',
            $file->file
        );
    }
}
