<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Stack;

use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    public function test(): void
    {
        $status = new \Dotclear\Helper\Stack\Status(0, 'status_id', 'status', 'statuses', 'icon', 'icon_dark', false);

        $this->assertEquals(
            0,
            $status->level()
        );
        $this->assertEquals(
            'status_id',
            $status->id()
        );
        $this->assertEquals(
            'status',
            $status->name()
        );
        $this->assertEquals(
            'statuses',
            $status->pluralName()
        );
        $this->assertEquals(
            'icon',
            $status->icon()
        );
        $this->assertEquals(
            'icon_dark',
            $status->iconDark()
        );
        $this->assertFalse(
            $status->hidden()
        );
    }
}
